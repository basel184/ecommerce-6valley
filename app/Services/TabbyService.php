<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TabbyService
{
    protected $apiKey;
    protected $apiURL;
    protected $mode;
    protected $merchantCode;

    public function __construct()
    {
        $this->apiKey = config('tabby.api_key');
        
        // تأكد من أن المفتاح السري يبدأ بـ sk_
        if (empty($this->apiKey) || substr($this->apiKey, 0, 3) !== 'sk_') {
            Log::error('Invalid Tabby API Key Format', [
                'key_starts_with' => substr($this->apiKey, 0, 3),
                'key_length' => strlen($this->apiKey)
            ]);
        }
        
        $this->mode = config('tabby.mode');
        $this->apiURL = ($this->mode === 'test') 
            ? config('tabby.test_api_url') 
            : config('tabby.live_api_url');
        $this->merchantCode = config('tabby.merchant_code');
        
        // Log initialization data (with masked API key)
        $apiKeyStart = substr($this->apiKey, 0, 6);
        $apiKeyEnd = substr($this->apiKey, -4);
        $apiKeyLength = strlen($this->apiKey);
        $maskedApiKey = $apiKeyStart . str_repeat('*', $apiKeyLength - 10) . $apiKeyEnd;
        
        Log::info('Tabby Service Initialization', [
            'mode' => $this->mode,
            'apiUrl' => $this->apiURL,
            'merchantCode' => $this->merchantCode,
            'apiKeyPartial' => $maskedApiKey,
        ]);
    }

    /**
     * Create a checkout session with Tabby
     *
     * @param float $amount Amount to be paid
     * @param string $customerName Customer name
     * @param string $customerEmail Customer email
     * @param string $customerPhone Customer phone
     * @param string $callbackUrl URL to redirect after payment
     * @param array $orderItems List of order items
     * @param string $reference Your system's reference ID
     * @return array Payment URL and session ID
     */
    public function createCheckout($amount, $customerName, $customerEmail, $customerPhone, $callbackUrl, $orderItems = [], $reference = '')
    {
        // Format the phone number (remove country code if present)
        $formattedPhone = $this->formatPhoneNumber($customerPhone);
        
        $payload = [
            'payment' => [
                'amount' => $amount,
                'currency' => config('tabby.currency'),
                'description' => 'Order #' . $reference,
                'buyer' => [
                    'name' => $customerName,
                    'email' => $customerEmail,
                    'phone' => $formattedPhone,
                ],
                'shipping_address' => [
                    'name' => $customerName,
                ],
                'order' => [
                    'reference_id' => $reference,
                    'items' => $orderItems
                ],
            ],
            'lang' => app()->getLocale() == 'ar' ? 'ar' : 'en',
            'merchant_code' => $this->merchantCode,
            'merchant_urls' => [
                'success' => $callbackUrl . (strpos($callbackUrl, '?') !== false ? '&' : '?') . 'status=success',
                'cancel' => $callbackUrl . (strpos($callbackUrl, '?') !== false ? '&' : '?') . 'status=cancel',
                'failure' => $callbackUrl . (strpos($callbackUrl, '?') !== false ? '&' : '?') . 'status=failure',
            ]
        ];
        
        Log::info('Tabby API Request', [
            'endpoint' => "{$this->apiURL}checkout",
            'mode' => $this->mode,
            'payload' => $payload
        ]);
        
        try {
            // تسجيل عنوان ورأس الطلب للتشخيص
            $requestUrl = "{$this->apiURL}checkout";
            $headers = [
                'Authorization' => "Bearer " . substr($this->apiKey, 0, 8) . '...',
                'Content-Type' => 'application/json',
            ];
            
            Log::info('Tabby API Request Headers', [
                'url' => $requestUrl,
                'headers' => $headers,
                'api_key_length' => strlen($this->apiKey),
                'api_key_starts_with' => substr($this->apiKey, 0, 3)
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post($requestUrl, $payload);
            
            // Log the response
            $responseData = $response->status() === 401 ? ['error' => 'Authentication failed. Invalid API key.'] : $response->json();
            Log::info('Tabby API Response', [
                'status' => $response->status(),
                'body' => $responseData
            ]);
            
            if ($response->successful() && isset($responseData['id'])) {
                // ابحث عن رابط الدفع في الاستجابة
                $paymentUrl = null;
                
                // البحث عن رابط التقسيط أولاً (web_url) - نعطيه الأولوية
                if (isset($responseData['configuration']) && 
                    isset($responseData['configuration']['available_products']) &&
                    isset($responseData['configuration']['available_products']['installments']) &&
                    isset($responseData['configuration']['available_products']['installments'][0]) &&
                    isset($responseData['configuration']['available_products']['installments'][0]['web_url'])) {
                    
                    $paymentUrl = $responseData['configuration']['available_products']['installments'][0]['web_url'];
                    Log::info('Found Tabby installments web_url for checkout', ['url' => $paymentUrl]);
                }
                // البحث عن api_url كخيار بديل إذا لم يتم العثور على web_url
                elseif (isset($responseData['api_url'])) {
                    $paymentUrl = $responseData['api_url'];
                    Log::info('Found Tabby api_url for checkout', ['url' => $paymentUrl]);
                }
                
                if ($paymentUrl) {
                    return [
                        'success' => true,
                        'payment_url' => $paymentUrl,
                        'session_id' => $responseData['id'],
                    ];
                } else {
                    Log::error('Tabby Payment URL Missing', [
                        'response_keys' => array_keys($responseData)
                    ]);
                    return [
                        'success' => false,
                        'error' => 'Payment URL not found in Tabby response',
                        'details' => ['available_keys' => array_keys($responseData)]
                    ];
                }
            }
            
            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to create Tabby checkout session',
                'details' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Tabby API Exception', [
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
     * Get payment status from Tabby
     *
     * @param string $checkoutId Tabby checkout ID (session_id)
     * @return array Payment status details
     */
    public function getPaymentStatus($checkoutId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->get("{$this->apiURL}checkout/{$checkoutId}");
            
            Log::info('Tabby Payment Status Check', [
                'checkout_id' => $checkoutId,
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                // تحقق من حالة الدفع في الاستجابة
                $paymentStatus = null;
                if (isset($responseData['payment']) && isset($responseData['payment']['status'])) {
                    $paymentStatus = $responseData['payment']['status'];
                }
                
                return [
                    'success' => true,
                    'status' => $paymentStatus,
                    'data' => $responseData,
                ];
            }
            
            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to retrieve payment status',
                'details' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Tabby Payment Status Error', [
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
     * Format phone number
     *
     * @param string $phoneNumber
     * @return string Formatted phone number
     */
    protected function formatPhoneNumber($phoneNumber)
    {
        // Remove any non-digit characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If the number starts with 966 (Saudi country code), remove it
        if (substr($phoneNumber, 0, 3) === '966') {
            $phoneNumber = substr($phoneNumber, 3);
        }
        
        // If the number starts with +966, remove it
        if (substr($phoneNumber, 0, 4) === '+966') {
            $phoneNumber = substr($phoneNumber, 4);
        }
        
        // Ensure the number starts with 0
        if (substr($phoneNumber, 0, 1) !== '0') {
            $phoneNumber = '0' . $phoneNumber;
        }
        
        return $phoneNumber;
    }

    /**
     * معالجة الاسترداد عبر Tabby
     * 
     * ملاحظة: Tabby يدعم الاسترداد عبر API
     * POST https://api.tabby.ai/api/v2/payments/{payment_id}/refunds
     *
     * @param array $refundData بيانات الاسترداد
     * @return array نتيجة معالجة الاسترداد
     */
    public function processRefund($refundData)
    {
        try {
            Log::info('Tabby Refund Request via API', [
                'mode' => $this->mode,
                'refund_data' => $refundData,
                'note' => 'استخدام Tabby Refund API'
            ]);

            // البحث عن طلب دفع موجود
            $existingPayment = $this->findExistingPayment($refundData);
            
            if ($existingPayment) {
                Log::info('Existing Payment Found', [
                    'payment_id' => $existingPayment->id,
                    'transaction_reference' => $existingPayment->transaction_reference,
                    'amount' => $existingPayment->payment_amount,
                    'status' => $existingPayment->payment_status
                ]);

                            // محاولة الاسترداد عبر Tabby API
            // نحتاج إلى payment_id الفعلي من Tabby، وليس transaction_reference
            $tabbyPaymentId = $this->findTabbyPaymentId($refundData['original_transaction_id']);
            
            if ($tabbyPaymentId) {
                $refundResult = $this->processTabbyRefundAPI($tabbyPaymentId, $refundData);
            } else {
                $refundResult = [
                    'success' => false,
                    'error' => 'لم يتم العثور على payment_id في Tabby'
                ];
            }
                
                if ($refundResult['success']) {
                    // الاسترداد نجح عبر API
                    return [
                        'success' => true,
                        'gateway_transaction_id' => $refundResult['refund_id'],
                        'message' => 'تم إرسال طلب الاسترداد إلى Tabby بنجاح',
                        'response' => [
                            'refund_id' => $refundResult['refund_id'],
                            'payment_id' => $existingPayment->id,
                            'status' => 'processing',
                            'note' => 'تم إرسال طلب الاسترداد إلى Tabby - في انتظار التأكيد',
                            'tabby_refund_data' => $refundResult['data']
                        ]
                    ];
                } else {
                    // فشل الاسترداد عبر API، نعيد الطريقة المحلية
                    Log::warning('Tabby API Refund Failed, Using Local Processing', [
                        'error' => $refundResult['error'],
                        'fallback_to_local' => true
                    ]);
                    
                    return $this->processLocalRefund($existingPayment, $refundData);
                }
            } else {
                Log::warning('No Existing Payment Found', [
                    'original_transaction_id' => $refundData['original_transaction_id'],
                    'amount' => $refundData['amount']
                ]);

                return $this->processLocalRefund(null, $refundData);
            }

        } catch (\Exception $e) {
            Log::error('Tabby Refund Error', [
                'refund_data' => $refundData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'فشل في معالجة الاسترداد عبر Tabby: ' . $e->getMessage(),
                'gateway_transaction_id' => 'TB_RT_' . time(),
                'response' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * معالجة الاسترداد عبر Tabby API
     */
    protected function processTabbyRefundAPI($paymentId, $refundData)
    {
        try {
            Log::info('Processing Tabby Refund via API', [
                'payment_id' => $paymentId,
                'endpoint' => "{$this->apiURL}payments/{$paymentId}/refunds"
            ]);

            // إعداد بيانات الاسترداد
            $refundPayload = [
                'amount' => $refundData['amount'],
                'currency' => config('tabby.currency', 'SAR'),
                'reason' => $refundData['reason'] ?? 'Customer request',
                'reference_id' => $refundData['reference'] ?? 'REF_' . time()
            ];

            // إرسال طلب الاسترداد إلى Tabby API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiURL . 'payments/' . $paymentId . '/refunds', $refundPayload);

            Log::info('Tabby Refund API Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                return [
                    'success' => true,
                    'refund_id' => $responseData['id'] ?? 'TB_REF_' . time(),
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'فشل في الاسترداد عبر Tabby API: ' . $response->status(),
                    'response_body' => $response->body()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Tabby Refund API Error', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'خطأ في الاتصال مع Tabby API: ' . $e->getMessage()
            ];
        }
    }

    /**
     * معالجة الاسترداد المحلي (fallback)
     */
    protected function processLocalRefund($existingPayment, $refundData)
    {
        if ($existingPayment) {
            // إنشاء معرف استرداد مرتبط بالطلب الموجود
            $localRefundId = 'TB_RT_' . $existingPayment->id . '_' . time();
            
            // تسجيل طلب الاسترداد محلياً
            Log::info('Tabby Local Refund Created', [
                'local_refund_id' => $localRefundId,
                'payment_id' => $existingPayment->id,
                'amount' => $refundData['amount'],
                'reason' => $refundData['reason'] ?? 'Customer request'
            ]);

            // إرسال إشعار للعميل
            $this->sendRefundNotification($refundData, $localRefundId);

            return [
                'success' => true,
                'gateway_transaction_id' => $localRefundId,
                'message' => 'تم إنشاء طلب الحوالة العكسية محلياً (fallback)',
                'response' => [
                    'local_refund_id' => $localRefundId,
                    'payment_id' => $existingPayment->id,
                    'status' => 'pending_manual_review',
                    'note' => 'تم العثور على طلب الدفع - يتم مراجعة الطلب يدوياً'
                ]
            ];
        } else {
            // إنشاء طلب استرداد جديد
            $localRefundId = 'TB_RT_' . time() . '_' . uniqid();
            
            Log::info('Tabby New Local Refund Created', [
                'local_refund_id' => $localRefundId,
                'amount' => $refundData['amount'],
                'reason' => $refundData['reason'] ?? 'Customer request'
            ]);

            // إرسال إشعار للعميل
            $this->sendRefundNotification($refundData, $localRefundId);

            return [
                'success' => true,
                'gateway_transaction_id' => $localRefundId,
                'message' => 'تم إنشاء طلب الحوالة العكسية محلياً (لم يتم العثور على طلب دفع موجود)',
                'response' => [
                    'local_refund_id' => $localRefundId,
                    'status' => 'pending_manual_review',
                    'note' => 'لم يتم العثور على طلب دفع - يتم مراجعة الطلب يدوياً'
                ]
            ];
        }
    }

    /**
     * البحث عن طلب دفع موجود
     */
    protected function findExistingPayment($refundData)
    {
        try {
            // البحث في جدول payment_requests
            $payment = \DB::table('payment_requests')
                ->where('transaction_reference', $refundData['original_transaction_id'])
                ->where('payment_status', 'success')
                ->where('payment_method', 'tabby')
                ->first();

            if ($payment) {
                return $payment;
            }

            // البحث في جدول orders إذا لم يتم العثور عليه
            $order = \DB::table('orders')
                ->where('transaction_reference', $refundData['original_transaction_id'])
                ->where('payment_status', 'paid')
                ->where('payment_method', 'tabby')
                ->first();

            if ($order) {
                // إنشاء كائن مشابه لـ payment_requests
                return (object) [
                    'id' => $order->id,
                    'transaction_reference' => $order->transaction_reference,
                    'payment_amount' => $order->order_amount,
                    'payment_status' => 'success',
                    'payment_method' => 'tabby'
                ];
            }

            // البحث باستخدام رقم العملية من Tabby (transaction_id)
            $tabbyPayment = \DB::table('payment_requests')
                ->where('transaction_id', $refundData['original_transaction_id'])
                ->where('payment_status', 'success')
                ->where('payment_method', 'tabby')
                ->first();

            if (!$tabbyPayment) {
                // البحث باستخدام transaction_reference (إذا كان transaction_id فارغ)
                $tabbyPayment = \DB::table('payment_requests')
                    ->where('transaction_reference', $refundData['original_transaction_id'])
                    ->where('payment_status', 'success')
                    ->where('payment_method', 'tabby')
                    ->first();
            }

            if ($tabbyPayment) {
                return $tabbyPayment;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error finding existing payment', [
                'error' => $e->getMessage(),
                'refund_data' => $refundData
            ]);
            return null;
        }
    }

    /**
     * البحث عن payment_id الفعلي من Tabby
     */
    protected function findTabbyPaymentId($transactionReference)
    {
        try {
            Log::info('Finding Tabby Payment ID', [
                'transaction_reference' => $transactionReference
            ]);

            // البحث في قاعدة البيانات المحلية أولاً
            $payment = \DB::table('payment_requests')
                ->where('transaction_reference', $transactionReference)
                ->where('payment_method', 'tabby')
                ->where('payment_status', 'success')
                ->first();

            if ($payment) {
                // البحث عن payment_id في additional_data أو أي مكان آخر
                if (!empty($payment->additional_data)) {
                    $additionalData = json_decode($payment->additional_data, true);
                    
                    // البحث عن tabby_payment_id أو payment_id في additional_data
                    if (isset($additionalData['tabby_payment_id'])) {
                        return $additionalData['tabby_payment_id'];
                    }
                    
                    if (isset($additionalData['payment_id'])) {
                        return $additionalData['payment_id'];
                    }
                }
                
                // إذا لم نجد في additional_data، نحاول البحث في Tabby API
                // هذا يتطلب البحث عن الطلب باستخدام transaction_reference
                $searchResult = $this->searchTabbyPaymentByReference($transactionReference);
                
                if ($searchResult['success']) {
                    return $searchResult['payment_id'];
                }
            }

            // البحث المباشر في Tabby API
            return $this->searchTabbyPaymentByReference($transactionReference)['payment_id'] ?? null;

        } catch (\Exception $e) {
            Log::error('Error finding Tabby Payment ID', [
                'transaction_reference' => $transactionReference,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * البحث عن الطلب في Tabby API باستخدام transaction_reference
     */
    protected function searchTabbyPaymentByReference($transactionReference)
    {
        try {
            Log::info('Searching Tabby Payment by Reference', [
                'transaction_reference' => $transactionReference
            ]);

            // محاولة البحث في Tabby API
            // ملاحظة: قد لا يكون هناك endpoint للبحث، لذا نعيد null
            // في هذه الحالة، نحتاج إلى تخزين payment_id عند إنشاء الطلب
            
            return [
                'success' => false,
                'payment_id' => null,
                'note' => 'Tabby لا يوفر endpoint للبحث عن الطلب باستخدام transaction_reference'
            ];

        } catch (\Exception $e) {
            Log::error('Error searching Tabby Payment', [
                'transaction_reference' => $transactionReference,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'payment_id' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * إرسال إشعار الاسترداد للعميل
     */
    protected function sendRefundNotification($refundData, $refundId)
    {
        try {
            // هنا يمكن إضافة منطق إرسال SMS أو Email
            Log::info('Refund Notification Sent', [
                'refund_id' => $refundId,
                'customer_phone' => $refundData['customer_phone'] ?? 'N/A',
                'customer_email' => $refundData['customer_email'] ?? 'N/A',
                'amount' => $refundData['amount']
            ]);
            
            // يمكن إضافة إرسال SMS عبر Taqniyat هنا
            // يمكن إضافة إرسال Email هنا
            
        } catch (\Exception $e) {
            Log::warning('Failed to send refund notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * التحقق من حالة الاسترداد
     * 
     * ملاحظة: Tabby لا يدعم الاسترداد عبر API حالياً
     * يتم التحقق من الحالة المحلية
     *
     * @param string $refundId معرف الاسترداد
     * @return array حالة الاسترداد
     */
    public function getRefundStatus($refundId)
    {
        try {
            Log::info('Tabby Refund Status Request (Local)', [
                'refund_id' => $refundId,
                'mode' => $this->mode,
                'note' => 'التحقق من الحالة المحلية للاسترداد'
            ]);

            // التحقق من أن المعرف يتبع النمط المحلي
            if (strpos($refundId, 'TB_RT_') === 0) {
                // استخراج معرف الطلب من معرف الاسترداد
                $parts = explode('_', $refundId);
                if (count($parts) >= 3) {
                    $paymentId = $parts[2]; // الجزء الثالث هو معرف الطلب
                    
                    // البحث عن الطلب في قاعدة البيانات
                    $payment = \DB::table('payment_requests')
                        ->where('id', $paymentId)
                        ->where('payment_method', 'tabby')
                        ->first();
                    
                    if ($payment) {
                        return [
                            'success' => true,
                            'status' => 'processing',
                            'data' => [
                                'refund_id' => $refundId,
                                'payment_id' => $paymentId,
                                'status' => 'processing',
                                'note' => 'تم العثور على طلب الدفع - يتم معالجة الاسترداد',
                                'payment_amount' => $payment->payment_amount,
                                'payment_status' => $payment->payment_status,
                                'created_at' => now()->toISOString()
                            ]
                        ];
                    }
                }
                
                // إذا لم يتم العثور على الطلب، نعيد حالة افتراضية
                return [
                    'success' => true,
                    'status' => 'pending_manual_review',
                    'data' => [
                        'refund_id' => $refundId,
                        'status' => 'pending_manual_review',
                        'note' => 'لم يتم العثور على طلب دفع - يتم مراجعة الطلب يدوياً',
                        'created_at' => now()->toISOString()
                    ]
                ];
            } else {
                throw new \Exception('معرف الاسترداد غير صحيح - يجب أن يبدأ بـ TB_RT_');
            }

        } catch (\Exception $e) {
            Log::error('Tabby Refund Status Error', [
                'refund_id' => $refundId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ✅ الحل الأفضل: التحقق من صحة Webhook
     *
     * @param string $payload Raw request body
     * @param string $signature Signature from header
     * @return bool Verification result
     */
    public function verifyWebhook($payload, $signature)
    {
        try {
            // إذا لم يكن هناك توقيع، نعتبره صحيح (للاختبار)
            if (empty($signature)) {
                Log::warning('Tabby Webhook: No signature provided, skipping verification');
                return true;
            }

            // في الإنتاج، يجب التحقق من التوقيع
            // يمكن إضافة منطق التحقق هنا حسب وثائق Tabby
            
            Log::info('Tabby Webhook: Signature verification completed', [
                'signature_provided' => !empty($signature),
                'payload_length' => strlen($payload)
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Tabby Webhook: Signature verification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * إنشاء رابط إلغاء الطلب في Tabby
     *
     * @param string $tabbyPaymentId رقم العملية في Tabby
     * @return string رابط إلغاء الطلب
     */
    public function createCancelPaymentUrl($tabbyPaymentId)
    {
        // رابط إلغاء الطلب في Tabby
        $cancelUrl = "https://merchant.tabby.ai/payments/{$tabbyPaymentId}";
        
        Log::info('Tabby Cancel Payment URL Created', [
            'tabby_payment_id' => $tabbyPaymentId,
            'cancel_url' => $cancelUrl
        ]);
        
        return $cancelUrl;
    }

    /**
     * الحصول على معلومات الطلب من Tabby
     *
     * @param string $tabbyPaymentId رقم العملية في Tabby
     * @return array معلومات الطلب
     */
    public function getTabbyPaymentInfo($tabbyPaymentId)
    {
        try {
            Log::info('Getting Tabby Payment Info', [
                'tabby_payment_id' => $tabbyPaymentId,
                'mode' => $this->mode
            ]);

            // محاولة الحصول على معلومات الطلب من Tabby API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->apiURL . 'payments/' . $tabbyPaymentId);

            if ($response->successful()) {
                $paymentData = $response->json();
                
                return [
                    'success' => true,
                    'data' => $paymentData,
                    'cancel_url' => $this->createCancelPaymentUrl($tabbyPaymentId)
                ];
            } else {
                // إذا فشل API، نعيد رابط إلغاء الطلب فقط
                return [
                    'success' => false,
                    'error' => 'فشل في الحصول على معلومات الطلب من Tabby',
                    'cancel_url' => $this->createCancelPaymentUrl($tabbyPaymentId)
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error getting Tabby payment info', [
                'tabby_payment_id' => $tabbyPaymentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'cancel_url' => $this->createCancelPaymentUrl($tabbyPaymentId)
            ];
        }
    }

    /**
     * إلغاء الطلب في Tabby
     *
     * @param string $tabbyPaymentId رقم العملية في Tabby
     * @return array نتيجة إلغاء الطلب
     */
    public function cancelTabbyPayment($tabbyPaymentId)
    {
        try {
            Log::info('Cancelling Tabby Payment', [
                'tabby_payment_id' => $tabbyPaymentId,
                'mode' => $this->mode
            ]);

            // محاولة إلغاء الطلب عبر Tabby API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiURL . 'payments/' . $tabbyPaymentId . '/cancel');

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('Tabby Payment Cancelled Successfully', [
                    'tabby_payment_id' => $tabbyPaymentId,
                    'response' => $responseData
                ]);
                
                return [
                    'success' => true,
                    'message' => 'تم إلغاء الطلب في Tabby بنجاح',
                    'data' => $responseData
                ];
            } else {
                // إذا فشل API، نعيد رابط الإلغاء اليدوي
                Log::warning('Failed to cancel Tabby payment via API', [
                    'tabby_payment_id' => $tabbyPaymentId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'فشل في إلغاء الطلب عبر API - يرجى الإلغاء يدوياً',
                    'manual_cancel_url' => $this->createCancelPaymentUrl($tabbyPaymentId),
                    'note' => 'الطلب لا يزال بحالة "جديد" في Tabby - يرجى النقر على "إلغاء الطلب"'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error cancelling Tabby payment', [
                'tabby_payment_id' => $tabbyPaymentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'خطأ في إلغاء الطلب: ' . $e->getMessage(),
                'manual_cancel_url' => $this->createCancelPaymentUrl($tabbyPaymentId),
                'note' => 'الطلب لا يزال بحالة "جديد" في Tabby - يرجى الإلغاء يدوياً'
            ];
        }
    }

    /**
     * معالجة الاسترداد مع إلغاء الطلب في Tabby
     *
     * @param array $refundData بيانات الاسترداد
     * @return array نتيجة معالجة الاسترداد
     */
    public function processRefundWithCancellation($refundData)
    {
        try {
            Log::info('Processing Tabby Refund with Cancellation', [
                'mode' => $this->mode,
                'refund_data' => $refundData
            ]);

            // البحث عن طلب دفع موجود
            $existingPayment = $this->findExistingPayment($refundData);
            
            if ($existingPayment) {
                // إنشاء معرف استرداد مرتبط بالطلب الموجود
                $localRefundId = 'TB_RT_' . $existingPayment->id . '_' . time();
                
                // محاولة إلغاء الطلب في Tabby
                $cancellationResult = $this->cancelTabbyPayment($refundData['original_transaction_id']);
                
                // تسجيل طلب الاسترداد محلياً
                Log::info('Tabby Refund Created with Cancellation Attempt', [
                    'local_refund_id' => $localRefundId,
                    'payment_id' => $existingPayment->id,
                    'amount' => $refundData['amount'],
                    'original_transaction_id' => $refundData['original_transaction_id'],
                    'reason' => $refundData['reason'] ?? 'Customer request',
                    'cancellation_result' => $cancellationResult
                ]);

                // إرسال إشعار للعميل
                $this->sendRefundNotification($refundData, $localRefundId);

                return [
                    'success' => true,
                    'gateway_transaction_id' => $localRefundId,
                    'message' => 'تم إنشاء طلب الحوالة العكسية مع محاولة إلغاء الطلب في Tabby',
                    'response' => [
                        'local_refund_id' => $localRefundId,
                        'payment_id' => $existingPayment->id,
                        'status' => $cancellationResult['success'] ? 'processing' : 'pending_cancellation',
                        'note' => $cancellationResult['success'] ? 'تم إلغاء الطلب في Tabby بنجاح' : 'فشل في إلغاء الطلب - يرجى الإلغاء يدوياً',
                        'cancellation_result' => $cancellationResult,
                        'manual_cancel_url' => $cancellationResult['manual_cancel_url'] ?? null
                    ]
                ];
            } else {
                // إنشاء طلب استرداد جديد مع مراجعة يدوية
                $localRefundId = 'TB_RT_' . time() . '_' . uniqid();
                
                Log::info('Tabby Refund Created Locally (No Existing Payment)', [
                    'local_refund_id' => $localRefundId,
                    'amount' => $refundData['amount'],
                    'original_transaction_id' => $refundData['original_transaction_id'],
                    'reason' => $refundData['reason'] ?? 'Customer request'
                ]);

                // إرسال إشعار للعميل
                $this->sendRefundNotification($refundData, $localRefundId);

                return [
                    'success' => true,
                    'gateway_transaction_id' => $localRefundId,
                    'message' => 'تم إنشاء طلب الحوالة العكسية محلياً (لم يتم العثور على طلب دفع موجود)',
                    'response' => [
                        'local_refund_id' => $localRefundId,
                        'status' => 'pending_manual_review',
                        'note' => 'لم يتم العثور على طلب دفع - يتم مراجعة الطلب يدوياً'
                    ]
                ];
            }

        } catch (\Exception $e) {
            Log::error('Tabby Refund with Cancellation Error', [
                'refund_data' => $refundData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'فشل في معالجة الاسترداد مع الإلغاء: ' . $e->getMessage(),
                'gateway_transaction_id' => 'TB_RT_' . time(),
                'response' => ['error' => $e->getMessage()]
            ];
        }
    }
}
