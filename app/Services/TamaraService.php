<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TamaraService
{
    protected $apiKey;
    protected $apiURL;
    protected $mode;
    protected $notificationToken;
    protected $merchantId;
    
    public function __construct()
    {
    // Normalize mode values from env (sandbox|test|live|production)
    $rawMode = strtolower((string) config('tamara.mode', 'sandbox'));
    $this->mode = in_array($rawMode, ['sandbox', 'test']) ? 'sandbox' : (in_array($rawMode, ['live', 'production']) ? 'live' : 'sandbox');
        
        // استخدام المفتاح المناسب بناءً على الوضع
        if ($this->mode === 'sandbox') {
            // استخدام مفتاح Sandbox API
            $sandboxApiKey = config('tamara.sandbox_api_key');
            
            // التأكد من أن المفتاح ليس فارغًا
            if (empty($sandboxApiKey)) {
                // محاولة الحصول على المفتاح من متغيرات البيئة مباشرة
                $sandboxApiKey = env('TAMARA_SANDBOX_API_KEY');
            }
            
            if (empty($sandboxApiKey)) {
                Log::error('Tamara Sandbox API key is missing');
                throw new \Exception('Tamara Sandbox API key is required but not configured');
            }
            
            $this->apiKey = $sandboxApiKey;
            
            Log::debug('Using Sandbox API Key', [
                'key_length' => strlen($sandboxApiKey),
                'key_first10' => substr($sandboxApiKey, 0, 10),
                'key_type' => 'sandbox'
            ]);
        } else {
            // وضع الإنتاج، استخدام مفتاح الإنتاج
            $liveApiKey = config('tamara.api_key');
            
            // التأكد من أن المفتاح ليس فارغًا
            if (empty($liveApiKey)) {
                // محاولة الحصول على المفتاح من متغيرات البيئة مباشرة
                $liveApiKey = env('TAMARA_API_KEY');
            }
            
            if (empty($liveApiKey)) {
                Log::error('Tamara Live API key is missing');
                throw new \Exception('Tamara Live API key is required but not configured');
            }
            
            $this->apiKey = $liveApiKey;
            
            Log::debug('Using Live API Key', [
                'key_length' => strlen($this->apiKey),
                'key_first10' => substr($this->apiKey, 0, 10),
                'key_type' => 'live'
            ]);
        }
        
        // استخدام عنوان API الصحيح بناءً على الوضع
        $this->apiURL = ($this->mode === 'sandbox') 
            ? config('tamara.sandbox_api_url') 
            : config('tamara.live_api_url');
            
        $this->notificationToken = config('tamara.notification_token');
        // Use environment-specific merchant ID when available
        $this->merchantId = ($this->mode === 'sandbox')
            ? (config('tamara.sandbox_merchant_id') ?: config('tamara.merchant_id'))
            : config('tamara.merchant_id');
        
        // التأكد من وجود "/" في نهاية عنوان API
        if (!empty($this->apiURL) && substr($this->apiURL, -1) !== '/') {
            $this->apiURL .= '/';
        }
        
        // Log the mode we're running in
        Log::info('Tamara Service Mode', [
            'mode' => $this->mode,
            'api_url' => $this->apiURL,
            'merchant_id' => $this->merchantId
        ]);

        // Log initialization data (with masked API key)
        $apiKeyStart = substr($this->apiKey, 0, 6);
        $apiKeyEnd = substr($this->apiKey, -4);
        $apiKeyLength = strlen($this->apiKey);
        $maskedLength = max(0, $apiKeyLength - 10); // Ensure length is not negative
        $maskedApiKey = $apiKeyStart . str_repeat('*', $maskedLength) . $apiKeyEnd;
        
        Log::info('Tamara Service Initialization', [
            'mode' => $this->mode,
            'apiUrl' => $this->apiURL,
            'apiKeyPartial' => $maskedApiKey,
            'merchantId' => $this->merchantId,
        ]);
    }

    /**
     * Create a checkout session with Tamara
     *
     * @param float $amount Amount to be paid
     * @param string $customerName Customer name
     * @param string $customerEmail Customer email
     * @param string $customerPhone Customer phone
     * @param string $successUrl URL to redirect after successful payment
     * @param string $failureUrl URL to redirect after failed payment
     * @param string $cancelUrl URL to redirect after cancelled payment
     * @param array $items Order items
     * @param string $reference Your system's reference ID
     * @return array Payment URL and checkout ID
     */
    public function createCheckout($amount, $customerName, $customerEmail, $customerPhone, $successUrl, $failureUrl, $cancelUrl, $items = [], $reference = '')
    {
        // Check circuit breaker before attempting API call
        if ($this->isCircuitBreakerOpen()) {
            Log::warning('Tamara Circuit Breaker Open - Skipping API Call', [
                'order_reference' => $reference,
                'amount' => $amount
            ]);

            // Build a consistent fallback response (array) for the controller to redirect
            $fallbackCheckoutId = md5(($reference ?: 'unknown') . '-' . microtime(true));
            $fallbackUrl = route('payment-fallback', [
                'gateway' => 'tamara',
                'order_id' => $reference ?: 'unknown',
                'checkout_id' => $fallbackCheckoutId,
            ]);

            Log::info('Payment Fallback Triggered', [
                'gateway' => 'tamara',
                'order_id' => $reference ?: 'unknown',
                'checkout_id' => $fallbackCheckoutId
            ]);

            return [
                'success' => true,
                'payment_url' => $fallbackUrl,
                'checkout_id' => $fallbackCheckoutId,
                'is_fallback' => true,
                'original_error' => 'TAMARA_CIRCUIT_BREAKER_OPEN'
            ];
        }
        
        // Format the phone number (remove country code if present)
        $formattedPhone = $this->formatPhoneNumber($customerPhone);
        
        // Ensure items array is never empty by adding a default item if necessary
        if (empty($items)) {
                    
            $items = [
                [
                    'reference_id' => $reference,
                    'type' => 'digital',
                    'name' => 'Order #' . $reference,
                    'sku' => 'order-' . $reference,
                    'quantity' => 1,
                    'unit_price' => [
                        'amount' => $amount,
                        'currency' => config('tamara.currency')
                    ],
                    'discount_amount' => [
                        'amount' => '0.00',
                        'currency' => config('tamara.currency')
                    ],
                    'tax_amount' => [
                        'amount' => '0.00',
                        'currency' => config('tamara.currency')
                    ],
                    'total_amount' => [
                        'amount' => $amount,
                        'currency' => config('tamara.currency')
                    ]
                ]
            ];
    }                // تعديل صيغة البيانات المُرسلة لتمارا حسب متطلبات API
    // البدء بـ PAY_BY_INSTALMENTS حسب نموذج Postman الموثوق (والتبديل لاحقاً عند الفشل)
    $paymentType = 'PAY_BY_INSTALMENTS';
        
        $currency = config('tamara.currency');
        $payload = [
            'order_reference_id' => (string) $reference,
            'order_number' => (string) $reference,
            'total_amount' => [
                'amount' => round((float)$amount, 2),
                'currency' => $currency
            ],
            'shipping_amount' => [
                'amount' => 0.00,
                'currency' => $currency
            ],
            'tax_amount' => [
                'amount' => 0.00,
                'currency' => $currency
            ],
            'discount' => [
                'name' => 'Order Discount',
                'amount' => [
                    'amount' => 0.00,
                    'currency' => $currency
                ]
            ],
            'description' => 'Order #' . $reference,
            'country_code' => 'SA',
            'payment_type' => $paymentType,
            // instalments مطلوبة عند الدفع بالتقسيط
            'instalments' => (int) config('tamara.default_installments', 3),
            'merchant_url' => [
                'success' => (string) $successUrl,
                'failure' => (string) $failureUrl,
                'cancel' => (string) $cancelUrl,
                'notification' => (string) url('/tamara/notification')
            ],
            'platform' => 'Laravel Web App',
            'items' => array_map(function($item) use ($currency) {
                // ضمان الأشكال والقيم كما في Postman: أرقام عشرية وكمية صحيحة ونوع Capitalized
                if (isset($item['unit_price']['amount'])) {
                    $item['unit_price']['amount'] = round((float) $item['unit_price']['amount'], 2);
                }
                if (isset($item['discount_amount']['amount'])) {
                    $item['discount_amount']['amount'] = round((float) $item['discount_amount']['amount'], 2);
                }
                if (isset($item['tax_amount']['amount'])) {
                    $item['tax_amount']['amount'] = round((float) $item['tax_amount']['amount'], 2);
                }
                if (isset($item['total_amount']['amount'])) {
                    $item['total_amount']['amount'] = round((float) $item['total_amount']['amount'], 2);
                }
                if (isset($item['quantity'])) {
                    $item['quantity'] = (int) $item['quantity'];
                }
                if (isset($item['type'])) {
                    $item['type'] = ucfirst(strtolower($item['type'])); // Digital
                } else {
                    $item['type'] = 'Digital';
                }
                // تأكد من وجود currency في الحقول النقدية
                foreach (['unit_price','discount_amount','tax_amount','total_amount'] as $priceField) {
                    if (isset($item[$priceField]) && !isset($item[$priceField]['currency'])) {
                        $item[$priceField]['currency'] = $currency;
                    }
                }
                return $item;
            }, $items),
            'consumer' => $this->sanitizeConsumerData([
                'first_name' => $this->getFirstName($customerName),
                'last_name' => $this->getLastName($customerName),
                'email' => $customerEmail,
                'phone_number' => (string) $formattedPhone
            ]),
            'shipping_address' => $this->sanitizeConsumerData([
                'first_name' => $this->getFirstName($customerName),
                'last_name' => $this->getLastName($customerName),
                'phone_number' => (string) $formattedPhone,
                'line1' => 'N/A',
                'city' => 'Riyadh',
                'country_code' => 'SA'
            ]),
            'billing_address' => $this->sanitizeConsumerData([
                'first_name' => $this->getFirstName($customerName),
                'last_name' => $this->getLastName($customerName),
                'phone_number' => (string) $formattedPhone,
                'line1' => 'N/A',
                'city' => 'Riyadh',
                'country_code' => 'SA'
            ]),
            'merchant_id' => (string) $this->merchantId,
            'locale' => app()->getLocale() == 'ar' ? 'ar_SA' : 'en_US'
        ];

        //dd($payload);
        // لم يعد هناك إيقاف للتنفيذ هنا؛ نُرسل الطلب مباشرةً
    
        // Check and log any API requirements that might be missing
        $this->debugAPIRequirements($payload);
        
        Log::info('Tamara API Request', [
            'endpoint' => rtrim($this->apiURL, '/') . '/checkout',
            'mode' => $this->mode,
            'payload' => $payload
        ]);
        try {
            Log::debug('Tamara API Headers', [
                'authorization_length' => strlen($this->apiKey),
                'api_url' => $this->apiURL,
                'merchant_id' => $this->merchantId
            ]);
            
            // Create HTTP client instance with debugging features
            $headers = [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json'
            ];
            
            // Only add merchant ID header in live mode
            if ($this->mode === 'live') {
                $headers['X-Merchant-Id'] = $this->merchantId;
                Log::info('Using Merchant ID header for live mode', [
                    'merchant_id' => $this->merchantId,
                    'headers' => $headers
                ]);
            }
            
            // Apply sane timeouts and a light retry for transient network issues
            $client = Http::withHeaders($headers)->timeout(15)->retry(1, 200);
            
            // Add debug log for headers
            Log::debug('Tamara API Request Headers', [
                'headers' => array_merge(
                    ['Authorization' => "Bearer " . substr($this->apiKey, 0, 10) . "..."],
                    ['Content-Type' => 'application/json'],
                    $this->mode === 'live' ? ['X-Merchant-Id' => $this->merchantId] : []
                )
            ]);
            
            // Ensure the URL ends with a trailing slash
            $baseUrl = rtrim($this->apiURL, '/') . '/';
            
            // Make the POST request and capture the response
            $response = $client->post("{$baseUrl}checkout", $payload);
             
            // Log the full response details
            $responseData = $response->json();
           
            $responseStatus = $response->status();
            $rawResponseBody = $response->body();
            
            Log::info('Tamara API Response', [
                'status' => $responseStatus,
                'body' => $responseData,
                'raw_body_length' => strlen($rawResponseBody),
                'headers' => $response->headers()
            ]);                // For any non-success response, log more details
            if ($responseStatus < 200 || $responseStatus >= 300) {
                Log::error('Tamara API Error', [
                    'status' => $responseStatus,
                    'body' => $rawResponseBody,
                    'headers' => $response->headers(),
                    'request' => [
                        'url' => rtrim($this->apiURL, '/') . '/checkout',
                        'payload' => $payload,
                        'headers' => array_merge(
                            ['Authorization' => "Bearer " . substr($this->apiKey, 0, 10) . "..."],
                            ['Content-Type' => 'application/json'],
                            $this->mode === 'live' ? ['X-Merchant-Id' => $this->merchantId] : []
                        )
                    ]
                ]);
                
                // معالجة الأخطاء 400 (Bad Request) أو 401 (خطأ المصادقة) أو 500 (خطأ الخادم)
                if ($responseStatus == 400 || $responseStatus == 401 || ($responseStatus == 500 && strpos($rawResponseBody, 'Something went wrong with us') !== false)) {
                    $errorType = $responseStatus == 400 ? 'Bad Request' : ($responseStatus == 401 ? 'Authentication Error' : 'Server Error');
                    
                    // معالجة خاصة لـ Server Error 500 - خطأ خادم Tamara
                    if ($responseStatus == 500 && strpos($rawResponseBody, 'Something went wrong with us') !== false) {
                        Log::warning('Tamara API Server Error (500) detected - Tamara internal server issue', [
                            'status' => $responseStatus,
                            'response_body' => $rawResponseBody,
                            'current_payload_type' => $payload['payment_type'] ?? 'unknown',
                            'message' => 'Tamara servers are experiencing issues, using fallback mechanism'
                        ]);
                        
                        // عدم إعادة المحاولة مع خطأ 500 من Tamara لأنه خطأ في خوادمهم
                        // بدلاً من ذلك، استخدم آلية fallback مباشرة
                        
                        // سجل الفشل للـ circuit breaker
                        $this->recordFailure();
                        
                        // توجيه المستخدم لطريقة دفع بديلة مع رسالة واضحة
                        Log::warning('Tamara API Server Error, using fallback redirect', [
                            'status' => $responseStatus,
                            'message' => 'Using fallback redirect mechanism due to Server Error'
                        ]);
                        
                        // إنشاء رابط نظام بديل وإرجاعه لعمل إعادة توجيه فورية للمستخدم
                        $orderId = $payload['order_reference_id'] ?? 'unknown';
                        $fallbackCheckoutId = md5($orderId . '-' . microtime(true));
                        $fallbackUrl = url("/payment-fallback?gateway=tamara&order_id={$orderId}&checkout_id={$fallbackCheckoutId}");
                        
                        Log::info('Payment Fallback Triggered', [
                            'gateway' => 'tamara',
                            'order_id' => $orderId,
                            'checkout_id' => $fallbackCheckoutId
                        ]);
                        
                        return [
                            'success' => true,
                            'payment_url' => $fallbackUrl,
                            'checkout_id' => $fallbackCheckoutId,
                            'is_fallback' => true,
                            'original_error' => 'Tamara API returned 500 Server Error'
                        ];
                    }
                    
                    // معالجة الأخطاء الأخرى (400, 401)
                    if ($responseStatus != 500) {
                        Log::warning("Tamara API {$errorType} detected", [
                            'status' => $responseStatus,
                            'message' => $rawResponseBody
                        ]);
                    }
                    
                    // إذا كان هناك خطأ بنوع البيانات، نحاول تصليح التنسيق
                    if ($responseStatus == 400 && strpos($rawResponseBody, 'wrong data type') !== false) {
                        Log::warning('Tamara API Bad Request Error, trying with adjusted data types', [
                            'status' => $responseStatus,
                            'message' => 'Retrying with strict data types'
                        ]);
                        
                        // تحويل جميع الأرقام في الطلب إلى نصوص
                        array_walk_recursive($payload, function(&$item) {
                            if (is_numeric($item)) {
                                $item = (string) $item;
                            }
                        });
                        
                        // التأكد من أن instalments موجود وهو رقم صحيح (integer)
                        $payload['instalments'] = 3;
                        
                        Log::info('Retrying Tamara API with adjusted data types', [
                            'modified_payload_sample' => [
                                'payment_type' => $payload['payment_type'],
                                'instalments' => $payload['instalments'],
                                'total_amount' => $payload['total_amount']
                            ]
                        ]);
                        
                        try {
                            $headers2 = [
                                'Authorization' => "Bearer {$this->apiKey}",
                                'Content-Type' => 'application/json',
                            ];
                            if ($this->mode === 'live') {
                                $headers2['X-Merchant-Id'] = $this->merchantId;
                            }
                            $secondResponse = Http::withHeaders($headers2)->post("{$baseUrl}checkout", $payload);
                            
                            if ($secondResponse->successful()) {
                                return $this->processSuccessfulResponse($secondResponse);
                            }
                        } catch (\Exception $e) {
                            Log::error('Tamara Second Attempt Error', [
                                'message' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    // إذا كان الخطأ قد يكون في نوع الدفع، نحاول طريقة دفع أخرى
                    if (isset($payload['payment_type'])) {
                        $alternativePaymentType = $payload['payment_type'] === 'PAY_BY_LATER' ? 'PAY_BY_INSTALMENTS' : 'PAY_BY_LATER';
                        
                        Log::warning("Tamara API {$errorType} with {$payload['payment_type']}, trying {$alternativePaymentType}", [
                            'status' => $responseStatus,
                            'message' => "Retrying with {$alternativePaymentType}",
                            'response_body' => $rawResponseBody
                        ]);
                        
                        $payload['payment_type'] = $alternativePaymentType;
                        
                        // التعامل مع حقل الأقساط بناءً على نوع الدفع
                        if ($alternativePaymentType === 'PAY_BY_INSTALMENTS') {
                            // تأكد من أن instalments عدد صحيح بناءً على إعدادات التطبيق أو القيمة الافتراضية 3
                            $payload['instalments'] = (int) config('tamara.default_installments', 3);
                            
                            Log::info('Setting up instalments payment', [
                                'instalments' => $payload['instalments']
                            ]);
                        } else {
                            // إزالة حقل instalments لأنه غير مطلوب مع PAY_BY_LATER
                            if (isset($payload['instalments'])) {
                                unset($payload['instalments']);
                                Log::info('Removed instalments field for PAY_BY_LATER');
                            }
                        }
                        
                        try {
                            $headers3 = [
                                'Authorization' => "Bearer {$this->apiKey}",
                                'Content-Type' => 'application/json',
                            ];
                            if ($this->mode === 'live') {
                                $headers3['X-Merchant-Id'] = $this->merchantId;
                            }
                            $secondResponse = Http::withHeaders($headers3)->post("{$baseUrl}checkout", $payload);
                            
                            if ($secondResponse->successful()) {
                                $secondResponseData = $secondResponse->json();
                                Log::info('Tamara Second Attempt Successful', [
                                    'response' => $secondResponseData
                                ]);
                                
                                // البحث عن رابط الدفع
                if (isset($secondResponseData['checkout_url'])) {
                                    return [
                                        'success' => true,
                                        'payment_url' => $secondResponseData['checkout_url'],
                    'checkout_id' => $secondResponseData['checkout_id'] ?? null,
                    'order_id' => $secondResponseData['order_id'] ?? null,
                                    ];
                                } elseif (isset($secondResponseData['url'])) {
                                    return [
                                        'success' => true,
                                        'payment_url' => $secondResponseData['url'],
                    'checkout_id' => $secondResponseData['id'] ?? null,
                    'order_id' => $secondResponseData['order_id'] ?? null,
                                    ];
                                } elseif (isset($secondResponseData['redirect_url'])) {
                                    return [
                                        'success' => true,
                                        'payment_url' => $secondResponseData['redirect_url'],
                    'checkout_id' => $secondResponseData['id'] ?? null,
                    'order_id' => $secondResponseData['order_id'] ?? null,
                                    ];
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('Tamara Second Attempt Exception', [
                                'message' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    // فحص إذا كان الخطأ بسبب عدم توفر تمارا في البلد
                    $countryNotSupportedErrors = ["Country code not allowed", "service not available"];
                    $isCountryError = false;
                    
                    foreach ($countryNotSupportedErrors as $errorText) {
                        if (strpos(strtolower($rawResponseBody), strtolower($errorText)) !== false) {
                            $isCountryError = true;
                            break;
                        }
                    }
                    
                    if ($isCountryError) {
                        Log::warning('Tamara not available in this country', [
                            'status' => $responseStatus,
                            'response' => $rawResponseBody
                        ]);
                    }
                    
                    // محاولة أخيرة: تجربة معلمات مختلفة تماماً
                    if (!$isCountryError) {
                        try {
                            // إعداد الـ payload مرة أخرى مع تعديلات إضافية
                            $lastAttemptPayload = $payload;
                            // تحويل جميع المبالغ إلى سلاسل نصية ما عدا الكميات
                            array_walk_recursive($lastAttemptPayload, function (&$value, $key) {
                                if (is_numeric($value) && $key !== 'quantity' && $key !== 'instalments') {
                                    $value = (string) number_format((float) $value, 2, '.', '');
                                } elseif ($key === 'instalments') {
                                    $value = (int) $value; // instalments يجب أن يكون عدد صحيح
                                }
                            });
                            
                            Log::info('Final Tamara API attempt with fully sanitized payload');
                            
                            $headers4 = [
                                'Authorization' => "Bearer {$this->apiKey}",
                                'Content-Type' => 'application/json',
                            ];
                            if ($this->mode === 'live') {
                                $headers4['X-Merchant-Id'] = $this->merchantId;
                            }
                            $finalResponse = Http::withHeaders($headers4)->post("{$baseUrl}checkout", $lastAttemptPayload);
                            
                            if ($finalResponse->successful()) {
                                return $this->processSuccessfulResponse($finalResponse);
                            }
                        } catch (\Exception $e) {
                            Log::error('Final Tamara API attempt failed', [
                                'message' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    // إذا فشلت كل المحاولات، نستخدم آلية النظام البديل
                    Log::warning('Tamara API ' . $errorType . ', using fallback redirect', [
                        'status' => $responseStatus,
                        'message' => 'Using fallback redirect mechanism due to ' . $errorType
                    ]);
                    
                    // Record failure for circuit breaker
                    $this->recordFailure();
                    
                    // إنشاء رابط النظام البديل
                    $fallbackCheckoutId = md5(uniqid() . $reference);
                    $fallbackOrderId = $reference;
                    $fallbackUrl = url("/payment-fallback?gateway=tamara&order_id={$fallbackOrderId}&checkout_id={$fallbackCheckoutId}");
                    
                    return [
                        'success' => true,
                        'payment_url' => $fallbackUrl,
                        'checkout_id' => $fallbackCheckoutId,
                        'is_fallback' => true,
                        'original_error' => $responseData['message'] ?? 'Unknown error'
                    ];
                }
            }
            
            if ($response->successful()) {
                return $this->processSuccessfulResponse($response);
            }
            
            return [
                'success' => false,
                'error' => $responseData['message'] ?? 'Failed to create Tamara checkout session',
                'details' => $responseData
            ];
        } catch (\Exception $e) {
            Log::error('Tamara API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get order status from Tamara
     *
     * @param string $orderId Tamara order ID
     * @return array Order status details
     */
    public function getOrderStatus($orderId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->get("{$this->apiURL}orders/{$orderId}");
            
            Log::info('Tamara Order Status Check', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'status' => $responseData['status'],
                    'data' => $responseData,
                ];
            }
            
            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to retrieve order status',
                'details' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Tamara Order Status Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Authorize payment on Tamara
     *
     * @param string $orderId Tamara order ID
     * @return array Authorization result
     */
    public function authorizePayment($orderId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiURL}orders/{$orderId}/authorise");
            
            Log::info('Tamara Payment Authorization', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }
            
            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to authorize payment',
                'details' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Tamara Authorization Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Format phone number for Tamara API
     *
     * @param string $phoneNumber
     * @return string Formatted phone number
     */
    /**
     * تنسيق رقم الهاتف وفقاً لمتطلبات API تمارا
     *
     * @param string $phoneNumber
     * @return string الرقم منسقاً
     */
    protected function formatPhoneNumber($phoneNumber)
    {
    $rawInput = $phoneNumber;
    // إزالة أي أحرف غير رقمية ورمز +
    $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // التحقق من وجود رمز الدولة السعودي (966)
        if (strpos($phoneNumber, '966') === 0) {
            // الرقم يحتوي على رمز الدولة بالفعل
            // التأكد من أنه ليس أطول من 12 رقمًا (966 + 9 أرقام)
            if (strlen($phoneNumber) > 12) {
                $phoneNumber = substr($phoneNumber, 0, 12);
            }
        } else {
            // الرقم لا يحتوي على رمز الدولة، إضافته
            // أولاً تأكد من أنه ليس أطول من 9 أرقام
            if (strlen($phoneNumber) > 9) {
                $phoneNumber = substr($phoneNumber, -9);
            }
            
            // إضافة رمز الدولة
            $phoneNumber = '966' . $phoneNumber;
        }
        
        // تمارا تتوقع أن يكون الرقم في تنسيق دولي مع + قبل رمز الدولة
        $formatted = '+' . $phoneNumber;

        // تسجيل رقم الهاتف المنسق
        Log::info('Phone number formatted', [
            'original' => $rawInput,
            'formatted' => $formatted
        ]);
        
        return $formatted;
    }
    
    /**
     * Verify webhook notification from Tamara
     *
     * @param string $payload Raw request body
     * @param string $signature Signature from header
     * @return bool Verification result
     */
    public function verifyWebhook($payload, $signature)
    {
        // Require configured token
        if (empty($this->notificationToken)) {
            Log::warning('Tamara webhook verification skipped: missing notification token');
            return false;
        }

        // Require signature header
        if (!is_string($signature) || trim($signature) === '') {
            Log::warning('Tamara webhook missing signature header');
            return false;
        }

        // Normalize payload to string
        if (!is_string($payload)) {
            $payload = (string) $payload;
        }

        $computedSignature = hash_hmac('sha256', $payload, $this->notificationToken);
        $verified = hash_equals($computedSignature, $signature);

        if (!$verified) {
            Log::warning('Tamara webhook signature mismatch', [
                'expected_prefix' => substr($computedSignature, 0, 12),
                'provided_prefix' => substr($signature, 0, 12),
            ]);
        }

        return $verified;
    }
    
    /**
     * Check and log any API requirements that might be missing
     * 
     * @param array $payload The API payload to check
     * @return void
     */
    /**
     * معالجة الاستجابة الناجحة من API تمارا
     * 
     * @param \Illuminate\Http\Client\Response $response
     * @return array
     */
    protected function processSuccessfulResponse($response)
    {
        // Reset circuit breaker on successful response
        $this->resetCircuitBreaker();
        
        $responseData = $response->json();
        
        // تسجيل كل مفاتيح الاستجابة لتشخيص أفضل
        Log::info('Tamara API Response Keys', [
            'keys' => array_keys($responseData),
            'response_sample' => json_encode(array_slice($responseData, 0, 5)),
        ]);
        
        // التحقق من وجود checkout_url
        if (isset($responseData['checkout_url'])) {
            // تسجيل عثورنا على رابط الدفع
            Log::info('Tamara Checkout URL Found', [
                'checkout_url' => $responseData['checkout_url'],
                'checkout_id' => $responseData['checkout_id'] ?? 'Not provided'
            ]);
            
            return [
                'success' => true,
                'payment_url' => $responseData['checkout_url'],
                'checkout_id' => $responseData['checkout_id'] ?? null,
                'order_id' => $responseData['order_id'] ?? null,
            ];
        }
        // التحقق من وجود redirect_url
        else if (isset($responseData['redirect_url'])) {
            Log::info('Tamara Redirect URL Found', [
                'redirect_url' => $responseData['redirect_url'],
                'checkout_id' => $responseData['checkout_id'] ?? $responseData['order_id'] ?? null,
            ]);
            
            return [
                'success' => true,
                'payment_url' => $responseData['redirect_url'],
                'checkout_id' => $responseData['checkout_id'] ?? $responseData['order_id'] ?? null,
                'order_id' => $responseData['order_id'] ?? null,
            ];
        }
        // التحقق من وجود رابط تمارا في url
        else if (isset($responseData['url'])) {
            Log::info('Tamara URL Found', [
                'url' => $responseData['url'],
                'checkout_id' => $responseData['id'] ?? null,
            ]);
            
            return [
                'success' => true,
                'payment_url' => $responseData['url'],
                'checkout_id' => $responseData['id'] ?? null,
                'order_id' => $responseData['order_id'] ?? null,
            ];
        }
        // التحقق من وجود حقل آخر معروف للرابط
        else if (isset($responseData['checkout_session_id']) && isset($responseData['checkout_session_url'])) {
            Log::info('Tamara Checkout Session URL Found', [
                'checkout_url' => $responseData['checkout_session_url'],
                'session_id' => $responseData['checkout_session_id']
            ]);
            
            return [
                'success' => true,
                'payment_url' => $responseData['checkout_session_url'],
                'checkout_id' => $responseData['checkout_session_id'],
                'order_id' => $responseData['order_id'] ?? null,
            ];
        }
        // التحقق من أي حقول أخرى قد تحتوي على الرابط
        else {
            // تسجيل أن لدينا استجابة ناجحة ولكن بدون رابط معروف
            Log::warning('Tamara API Success But No Checkout URL', [
                'response' => $responseData
            ]);
            
            // البحث عن أي حقل في الاستجابة قد يكون رابط
            $possibleUrlFields = [
                'url', 'payment_url', 'checkout_link', 'redirect_to', 'paymentUrl', 
                'checkout_session_url', 'checkoutUrl', 'payment_link'
            ];
            foreach ($possibleUrlFields as $field) {
                if (isset($responseData[$field])) {
                    Log::info("Found Tamara URL in field: {$field}", [
                        'url' => $responseData[$field]
                    ]);
                    
                    return [
                        'success' => true,
                        'payment_url' => $responseData[$field],
                        'checkout_id' => $responseData['id'] ?? $responseData['checkout_id'] ?? $responseData['order_id'] ?? null,
                    ];
                }
            }
            
            // إذا وصلنا إلى هنا، فلم نعثر على أي رابط في الاستجابة
            Log::error('No payment URL found in Tamara response', [
                'response_keys' => array_keys($responseData)
            ]);
            
            return [
                'success' => false,
                'error' => 'No payment URL found in Tamara response',
                'details' => $responseData
            ];
        }
    }
    
    protected function debugAPIRequirements($payload)
    {
        // حقول مطلوبة أساسية لجميع أنواع الدفع
        $requiredFields = [
            'order_reference_id',
            'total_amount',
            'description',
            'country_code',
            'payment_type',
            'locale',
            'items',
            'consumer',
            'merchant_url'
        ];
        
        // إضافة حقل instalments فقط إذا كان نوع الدفع بالتقسيط
        if (isset($payload['payment_type']) && $payload['payment_type'] === 'PAY_BY_INSTALMENTS') {
            $requiredFields[] = 'instalments';
        }
        
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                $missingFields[] = $field;
            } elseif ($field === 'items' && empty($payload[$field])) {
                $missingFields[] = $field . ' (empty array)';
            }
        }
        
        if (!empty($missingFields)) {
            Log::warning('Tamara API Missing Required Fields', [
                'missing' => $missingFields
            ]);
        }
        
        // Check if we have valid consumer info
        if (isset($payload['consumer'])) {
            $consumerFields = ['first_name', 'last_name', 'email', 'phone_number'];
            $missingConsumerFields = [];
            foreach ($consumerFields as $field) {
                if (!isset($payload['consumer'][$field]) || empty($payload['consumer'][$field])) {
                    $missingConsumerFields[] = $field;
                }
            }
            
            if (!empty($missingConsumerFields)) {
                Log::warning('Tamara API Missing Consumer Fields', [
                    'missing' => $missingConsumerFields
                ]);
            }
        }
        
        // Check total_amount format
    if (isset($payload['total_amount'])) {
            if (!isset($payload['total_amount']['amount']) || !isset($payload['total_amount']['currency'])) {
                Log::warning('Tamara API Invalid Total Amount Format', [
                    'total_amount' => $payload['total_amount']
                ]);
            }
        }
    }
    
    /**
     * Extract first name from customer name
     */
    private function getFirstName($customerName)
    {
        if (empty($customerName)) {
            return 'مستخدم';
        }
        
        $nameParts = explode(' ', trim($customerName));
        return !empty($nameParts[0]) ? $nameParts[0] : 'مستخدم';
    }
    
    /**
     * Extract last name from customer name
     */
    private function getLastName($customerName)
    {
        if (empty($customerName)) {
            return 'جديد';
        }
        
        $nameParts = explode(' ', trim($customerName));
        
        // If we have more than one part, return everything except the first
        if (count($nameParts) > 1) {
            array_shift($nameParts); // Remove first name
            $lastName = implode(' ', $nameParts);
            return !empty($lastName) ? $lastName : 'جديد';
        }
        
        // If only one name part, return a default last name
        return 'جديد';
    }
    
    /**
     * Validate and sanitize consumer data for Tamara API
     */
    private function sanitizeConsumerData($data)
    {
        // Ensure required fields are not empty
        $data['first_name'] = !empty($data['first_name']) ? $data['first_name'] : 'مستخدم';
        $data['last_name'] = !empty($data['last_name']) ? $data['last_name'] : 'جديد';
        $data['email'] = !empty($data['email']) ? $data['email'] : 'customer@doorwqossor.com';
        
        // Normalize and validate phone key to phone_number
        if (isset($data['phone']) && !isset($data['phone_number'])) {
            $data['phone_number'] = $data['phone'];
            unset($data['phone']);
        }
        if (isset($data['phone_number'])) {
            $data['phone_number'] = $this->formatPhoneNumber($data['phone_number']);
        }
        
        return $data;
    }
    
    /**
     * Check if Tamara service is available
     */
    public function checkServiceStatus()
    {
        try {
            $baseUrl = rtrim($this->apiURL, '/');
            $response = Http::timeout(10)->get($baseUrl . '/health');
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Tamara Service Status Check Failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get service status for frontend
     */
    public static function getStatus()
    {
        $service = new self();
        return $service->checkServiceStatus();
    }
    
    /**
     * Check if Tamara is in circuit breaker mode (too many recent failures)
     */
    private function isCircuitBreakerOpen()
    {
        $failureCount = Cache::get('tamara_failure_count', 0);
        $lastFailure = Cache::get('tamara_last_failure');
        
        Log::info('Circuit Breaker Check', [
            'failure_count' => $failureCount,
            'last_failure' => $lastFailure,
            'threshold' => 3,
            'time_window_minutes' => 10
        ]);
        
        // If we've had 3 or more failures in the last 10 minutes, open circuit breaker
        if ($failureCount >= 3 && $lastFailure && now()->diffInMinutes($lastFailure) < 10) {
            Log::warning('Tamara Circuit Breaker CLOSED - Service Blocked', [
                'failure_count' => $failureCount,
                'last_failure' => $lastFailure,
                'minutes_since_last_failure' => now()->diffInMinutes($lastFailure),
                'status' => 'BLOCKING_SERVICE'
            ]);
            return true;
        }
        
        // Reset counter if it's been more than 10 minutes since last failure
        if ($lastFailure && now()->diffInMinutes($lastFailure) > 10) {
            Log::info('Circuit Breaker Auto-Reset - Time Window Expired', [
                'old_failure_count' => $failureCount,
                'minutes_since_last_failure' => now()->diffInMinutes($lastFailure)
            ]);
            Cache::forget('tamara_failure_count');
            Cache::forget('tamara_last_failure');
        }
        
        Log::info('Tamara Circuit Breaker OPEN - Service Available', [
            'failure_count' => $failureCount,
            'status' => 'ALLOWING_REQUESTS'
        ]);
        return false;
    }
    
    /**
     * Record a failure for circuit breaker
     */
    private function recordFailure()
    {
        $failureCount = Cache::get('tamara_failure_count', 0) + 1;
        Cache::put('tamara_failure_count', $failureCount, now()->addMinutes(15));
        Cache::put('tamara_last_failure', now(), now()->addMinutes(15));
        
        Log::info('Tamara Failure Recorded', [
            'failure_count' => $failureCount,
            'timestamp' => now()
        ]);
    }
    
    /**
     * Reset circuit breaker on successful request
     */
    private function resetCircuitBreaker()
    {
        Cache::forget('tamara_failure_count');
        Cache::forget('tamara_last_failure');
        Log::info('Tamara Circuit Breaker Reset - Successful Request');
    }
    
    /**
     * Check if Tamara service is currently available
     */
    public static function isServiceAvailable()
    {
        $service = new self();
        return !$service->isCircuitBreakerOpen();
    }
}
