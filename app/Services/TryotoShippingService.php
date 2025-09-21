<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TryotoShippingService
{
    private $refreshToken;
    private $baseUrl;

    public function __construct()
    {
        $this->refreshToken = config('services.tryoto.refresh_token', 'AMf-vBy0XdSuGDMN5ia_XRCpGxMKnYI2TxNHzNvOtqSVArEC5qeiyiuVUnaWnLXV6TpuJadhzUj8oIMsT1unvC7OWMsLV0Npon_tD4iH9MOWp42EY4VKhT9zBxXSZOED3J48jOe_p9A9r9JtmjA4RhbosCs9TaKx95rv0fvp5IKDA50UA1qLFbR8p7LeI1DqRNcqZ4GhnCh3k-WxkmkilMsVC4zd9GIpXw');
        $this->baseUrl = 'https://api.tryoto.com/rest/v2/';
    }

    public function createOrder($orderId)
    {
        // Set timezone to Saudi Arabia
        config(['app.timezone' => 'Asia/Riyadh']);
        Carbon::setToStringFormat('d/m/Y H:i');

        $order = Order::with(['details.product', 'shippingAddress', 'customer'])->find($orderId);
        
        if (!$order) {
            Log::error("Tryoto: Order with ID $orderId not found.");
            return null;
        }

        // Use the working format that we tested successfully
        return $this->createOrderWithCorrectFormat($orderId);
    }

    /**
     * Create order using the correct format that works with Postman
     */
    public function createOrderWithCorrectFormat($orderId)
    {
        try {
            Log::info('Tryoto: Creating order with correct format...');
            
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => 'فشل في الحصول على access token'
                ];
            }

            $order = Order::with(['details.product', 'shippingAddress', 'customer'])->find($orderId);
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'الطلب غير موجود'
                ];
            }

            // Use the exact format that works in Postman
            $orderData = [
                "orderId" => $order->id,
                "ref1" => "BERNSA-REF-" . time(),
                "deliveryOptionId" => "12364",
                "serviceType" => "standard",
                "createShipment" => true,
                "storeName" => "شركة بيرن التجارية",
                "payment_method" => "paid",
                "amount" => (float) $order->order_amount,
                "amount_due" => 0,
                "customsValue" => "12",
                "customsCurrency" => "SAR",
                "shippingAmount" => (float) $order->shipping_cost,
                "subtotal" => (float) $order->order_amount - $order->shipping_cost,
                "currency" => "SAR",
                "shippingNotes" => "يرجى التعامل بحذر مع المنتجات",
                "packageSize" => "small",
                "packageCount" => 1,
                "packageWeight" => 1,
                "boxWidth" => 10,
                "boxLength" => 10,
                "boxHeight" => 10,
                "orderDate" => Carbon::now('Asia/Riyadh')->format('d/m/Y H:i'),
                "deliverySlotDate" => Carbon::now('Asia/Riyadh')->addDay()->format('d/m/Y'),
                "deliverySlotTo" => "12:00",
                "deliverySlotFrom" => "14:30",
                "senderName" => "شركة بيرن التجارية",
                "senderInformation" => [
                    "senderAddressName" => "شركة بيرن التجارية",
                    "senderId" => "BERNSA001",
                    "senderFullName" => "شركة بيرن التجارية",
                    "senderMobile" => "966548060989",
                    "senderEmail" => "bernsa2030@gmail.com",
                    "senderCountry" => "SA",
                    "senderCity" => "Jeddah",
                    "senderPostcode" => "23421",
                    "senderAddressLine" => "جدة حي البغدادية",
                    "lat" => "21.5433912",
                    "lon" => "39.1728825"
                ],
                "customer" => [
                    "name" => $order->customer ? $order->customer->f_name . ' ' . $order->customer->l_name : 'Guest Customer',
                    "email" => $order->customer ? $order->customer->email : 'guest@bernsa.com',
                    "mobile" => $order->customer ? $order->customer->phone : '500000000',
                    "address" => $order->shippingAddress ? $order->shippingAddress->address : 'Default Address',
                    "district" => "",
                    "city" => $order->shippingAddress ? $order->shippingAddress->city : 'Riyadh',
                    "country" => $order->shippingAddress ? $order->shippingAddress->country : 'SA',
                    "postcode" => $order->shippingAddress ? $order->shippingAddress->zip : '12488',
                    "lat" => "24.7747262",
                    "lon" => "46.7041824",
                    "refID" => $order->id,
                    "W3WAddress" => "default.address.saudi"
                ],
                "items" => $this->prepareItemsData($order)
            ];

            Log::info('Tryoto: Sending order with correct format...', ['orderId' => $order->id]);

            $response = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'createOrder', $orderData);

            Log::info('Tryoto: Response status: ' . $response->status());
            Log::info('Tryoto: Response body: ' . $response->body());

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Save tracking information
                $this->saveTrackingInfo($orderId, $responseData);
                
                return [
                    'success' => true,
                    'message' => 'تم إنشاء الطلب بنجاح',
                    'order_id' => $order->id,
                    'oto_id' => $responseData['otoId'] ?? null,
                    'response' => $responseData,
                    'dashboard_url' => 'https://app.tryoto.com/'
                ];
            } else {
                $errorData = $response->json();
                return [
                    'success' => false,
                    'message' => 'فشل في إنشاء الطلب',
                    'status_code' => $response->status(),
                    'error_details' => $errorData,
                    'order_id' => $order->id
                ];
            }

        } catch (\Exception $e) {
            Log::error('Tryoto CreateOrder Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطأ في إنشاء الطلب: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Try different createOrder endpoints and formats
     */
    public function tryCreateOrderAlternatives($orderId)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'فشل في الحصول على access token'];
        }

        $order = Order::with(['details.product', 'shippingAddress', 'customer'])->find($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'الطلب غير موجود'];
        }

        $results = [];
        
        // Try different endpoints
        $endpoints = [
            'createOrder',
            'orders/create',
            'order/create',
            'shipment/create',
            'create-shipment'
        ];

        // Try different data formats
        $dataFormats = [
            'minimal' => $this->createMinimalOrderData($order),
            'standard' => $this->createStandardOrderData($order),
            'detailed' => $this->createDetailedOrderData($order)
        ];

        foreach ($endpoints as $endpoint) {
            foreach ($dataFormats as $format => $data) {
                try {
                    $response = Http::timeout(60)->withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])->post($this->baseUrl . $endpoint, $data);

                    $results["{$endpoint}_{$format}"] = [
                        'endpoint' => $endpoint,
                        'format' => $format,
                        'status' => $response->status(),
                        'success' => $response->successful(),
                        'response' => $response->body()
                    ];

                    // If successful, return immediately
                    if ($response->successful()) {
                        return [
                            'success' => true,
                            'message' => "تم إنشاء الطلب بنجاح باستخدام {$endpoint} و {$format}",
                            'endpoint' => $endpoint,
                            'format' => $format,
                            'response' => $response->json()
                        ];
                    }

                } catch (\Exception $e) {
                    $results["{$endpoint}_{$format}"] = [
                        'endpoint' => $endpoint,
                        'format' => $format,
                        'status' => 'error',
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        return [
            'success' => false,
            'message' => 'فشل في إنشاء الطلب بجميع المحاولات',
            'attempts' => $results
        ];
    }

    /**
     * Create minimal order data
     */
    private function createMinimalOrderData($order)
    {
        return [
            'orderId' => $order->id,
            'amount' => (float) $order->order_amount,
            'currency' => 'SAR',
            'customer' => [
                'name' => $order->customer ? $order->customer->f_name . ' ' . $order->customer->l_name : 'Guest',
                'mobile' => $order->customer ? $order->customer->phone : '500000000',
                'email' => $order->customer ? $order->customer->email : 'guest@bernsa.com'
            ]
        ];
    }

    /**
     * Create standard order data
     */
    private function createStandardOrderData($order)
    {
        $addressData = $this->prepareAddressData($order);
        $customerData = $this->prepareCustomerData($order, $addressData);
        $itemsData = $this->prepareItemsData($order);

        return [
            'orderId' => $order->id,
            'amount' => (float) $order->order_amount,
            'currency' => 'SAR',
            'customer' => $customerData,
            'items' => $itemsData,
            'shippingAddress' => $addressData
        ];
    }

    /**
     * Create detailed order data (original format)
     */
    private function createDetailedOrderData($order)
    {
        $addressData = $this->prepareAddressData($order);
        $customerData = $this->prepareCustomerData($order, $addressData);
        $itemsData = $this->prepareItemsData($order);
        
        return [
            "orderId" => $order->id,
            "ref1" => "REF" . time(),
            "deliveryOptionId" => "12364",
            "serviceType" => "standard",
            "createShipment" => false,
            "storeName" => "شركة بيرن التجارية",
            "payment_method" => "paid",
            "amount" => (float) $order->order_amount,
            "amount_due" => 0,
            "customsValue" => "12",
            "customsCurrency" => "SAR",
            "shippingAmount" => (float) $order->shipping_cost,
            "subtotal" => (float) $order->order_amount - $order->shipping_cost,
            "currency" => "SAR",
            "shippingNotes" => "يرجى التعامل بحذر مع المنتجات",
            "packageSize" => "small",
            "packageCount" => 1,
            "packageWeight" => 1,
            "boxWidth" => 10,
            "boxLength" => 10,
            "boxHeight" => 10,
            "orderDate" => Carbon::now('Asia/Riyadh')->format('d/m/Y H:i'),
            "deliverySlotDate" => Carbon::now('Asia/Riyadh')->addDay()->format('d/m/Y'),
            "deliverySlotTo" => "12:00",
            "deliverySlotFrom" => "14:30",
            "senderName" => "شركة بيرن التجارية",
            "senderInformation" => [
                "senderAddressName" => "شركة بيرن التجارية",
                "senderId" => "123452",
                "senderFullName" => "شركة بيرن التجارية",
                "senderMobile" => "966548060989",
                "senderEmail" => "bernsa2030@gmail.com",
                "senderCountry" => "SA",
                "senderCity" => "Jeddah",
                "senderPostcode" => "23421",
                "senderAddressLine" => "جدة حي البغدادية",
                "lat" => "21.5433912",
                "lon" => "39.1728825"
            ],
            "customer" => $customerData,
            "items" => $itemsData
        ];
    }

    /**
     * Test API connectivity and create a test order
     */
    public function testApiConnection()
    {
        try {
            Log::info('Tryoto: Starting API connection test...');
            
            // Test 1: Check if we can reach the API
            $testResponse = Http::timeout(30)->get($this->baseUrl);
            Log::info('Tryoto: Base URL test response status: ' . $testResponse->status());
            
            // Test 2: Try to get access token
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                Log::error('Tryoto: Failed to get access token during test');
                return [
                    'success' => false,
                    'message' => 'فشل في الحصول على access token',
                    'details' => 'تأكد من صحة refresh token'
                ];
            }
            
            Log::info('Tryoto: Successfully obtained access token during test');
            
            // Test 3: Create a test order
            $testOrderData = $this->createTestOrderPayload();
            $testResult = $this->sendTestOrderToTryoto($testOrderData, $accessToken);
            
            return [
                'success' => true,
                'message' => 'تم اختبار الاتصال بنجاح',
                'access_token' => $accessToken ? 'تم الحصول عليه' : 'فشل',
                'test_order_result' => $testResult
            ];
            
        } catch (\Exception $e) {
            Log::error('Tryoto Test Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطأ في الاتصال: ' . $e->getMessage(),
                'details' => 'تأكد من صحة الرابط: ' . $this->baseUrl
            ];
        }
    }

    /**
     * Test different API endpoints
     */
    public function testEndpoints($accessToken)
    {
        $endpoints = [
            'createOrder',
            'orders',
            'order',
            'shipment',
            'create-shipment'
        ];
        
        $results = [];
        
        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::timeout(30)->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->get($this->baseUrl . $endpoint);
                
                $results[$endpoint] = [
                    'status' => $response->status(),
                    'success' => $response->successful(),
                    'body' => $response->body()
                ];
                
                Log::info("Tryoto: Tested endpoint $endpoint - Status: " . $response->status());
                
            } catch (\Exception $e) {
                $results[$endpoint] = [
                    'status' => 'error',
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                
                Log::error("Tryoto: Error testing endpoint $endpoint: " . $e->getMessage());
            }
        }
        
        return $results;
    }

    /**
     * Check account permissions and API access
     */
    public function checkAccountPermissions()
    {
        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => 'فشل في الحصول على access token'
                ];
            }

            // Test different API endpoints to check permissions
            $endpoints = [
                'orders' => 'GET',
                'createOrder' => 'POST',
                'shipments' => 'GET',
                'account' => 'GET',
                'profile' => 'GET'
            ];

            $results = [];
            foreach ($endpoints as $endpoint => $method) {
                try {
                    if ($method === 'GET') {
                        $response = Http::timeout(30)->withHeaders([
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Accept' => 'application/json',
                        ])->get($this->baseUrl . $endpoint);
                    } else {
                        $response = Http::timeout(30)->withHeaders([
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ])->post($this->baseUrl . $endpoint, []);
                    }

                    $results[$endpoint] = [
                        'status' => $response->status(),
                        'success' => $response->successful(),
                        'body' => $response->body(),
                        'permission' => $response->status() === 200 || $response->status() === 201 ? 'allowed' : 'denied'
                    ];
                } catch (\Exception $e) {
                    $results[$endpoint] = [
                        'status' => 'error',
                        'success' => false,
                        'error' => $e->getMessage(),
                        'permission' => 'error'
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'تم فحص صلاحيات الحساب',
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('Tryoto Account Permissions Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطأ في فحص الصلاحيات: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test different API versions
     */
    public function testApiVersions()
    {
        $versions = [
            'v1' => 'https://api.tryoto.com/rest/v1/',
            'v2' => 'https://api.tryoto.com/rest/v2/',
            'v3' => 'https://api.tryoto.com/rest/v3/'
        ];

        $results = [];
        foreach ($versions as $version => $url) {
            try {
                $response = Http::timeout(10)->get($url);
                $results[$version] = [
                    'url' => $url,
                    'status' => $response->status(),
                    'accessible' => $response->successful(),
                    'body' => $response->body()
                ];
            } catch (\Exception $e) {
                $results[$version] = [
                    'url' => $url,
                    'status' => 'error',
                    'accessible' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Create test order payload
     */
    private function createTestOrderPayload()
    {
        return [
            "orderId" => "TEST-" . time(),
            "ref1" => "TEST-REF-" . time(),
            "deliveryOptionId" => "12364",
            "serviceType" => "standard",
            "createShipment" => false,
            "storeName" => "شركة بيرن التجارية - اختبار",
            "payment_method" => "paid",
            "amount" => 100.00,
            "amount_due" => 0,
            "customsValue" => "12",
            "customsCurrency" => "SAR",
            "shippingAmount" => 15.00,
            "subtotal" => 85.00,
            "currency" => "SAR",
            "shippingNotes" => "طلب تجريبي للاختبار",
            "packageSize" => "small",
            "packageCount" => 1,
            "packageWeight" => 1,
            "boxWidth" => 10,
            "boxLength" => 10,
            "boxHeight" => 10,
            "orderDate" => Carbon::now('Asia/Riyadh')->format('d/m/Y H:i'),
            "deliverySlotDate" => Carbon::now('Asia/Riyadh')->addDay()->format('d/m/Y'),
            "deliverySlotTo" => "12:00",
            "deliverySlotFrom" => "14:30",
            "senderName" => "شركة بيرن التجارية",
            "senderInformation" => [
                "senderAddressName" => "شركة بيرن التجارية",
                "senderId" => "123452",
                "senderFullName" => "شركة بيرن التجارية",
                "senderMobile" => "966548060989",
                "senderEmail" => "bernsa2030@gmail.com",
                "senderCountry" => "SA",
                "senderCity" => "Jeddah",
                "senderPostcode" => "23421",
                "senderAddressLine" => "جدة حي البغدادية",
                "lat" => "21.5433912",
                "lon" => "39.1728825"
            ],
            "customer" => [
                "name" => "عميل تجريبي",
                "email" => "test@bernsa.com",
                "mobile" => "500000000",
                "address" => "عنوان تجريبي",
                "district" => "",
                "city" => "Riyadh",
                "country" => "SA",
                "postcode" => "12488",
                "lat" => "24.7747262",
                "lon" => "46.7041824",
                "refID" => "TEST-" . time(),
                "W3WAddress" => "test.address.saudi"
            ],
            "items" => [
                [
                    'productId' => 'TEST-001',
                    'name' => 'منتج تجريبي',
                    'price' => 85.00,
                    'rowTotal' => 85.00,
                    'taxAmount' => 15,
                    'quantity' => 1,
                    'sku' => 'TEST-SKU-001',
                    'image' => 'https://bernsa.com/default-product-image.jpg'
                ]
            ]
        ];
    }

    /**
     * Create a simple test order that might work
     */
    public function createSimpleTestOrder()
    {
        try {
            Log::info('Tryoto: Creating simple test order...');
            
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => 'فشل في الحصول على access token'
                ];
            }

            // Create a very simple test order with address
            $simpleOrderData = [
                "orderId" => "SIMPLE-TEST-" . time(),
                "amount" => 100.00,
                "currency" => "SAR",
                "customer" => [
                    "name" => "عميل تجريبي بسيط",
                    "mobile" => "966501234567",
                    "email" => "simple-test@bernsa.com",
                    "address" => "الرياض - حي النرجس - شارع الملك فهد",
                    "city" => "Riyadh",
                    "country" => "SA",
                    "postcode" => "12488"
                ],
                "items" => [
                    [
                        'name' => 'منتج تجريبي بسيط',
                        'price' => 100.00,
                        'quantity' => 1
                    ]
                ]
            ];

            // Try with minimal data first
            $response = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'createOrder', $simpleOrderData);

            Log::info("Tryoto: Simple test response - Status: " . $response->status());
            Log::info("Tryoto: Simple test response body: " . $response->body());

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'تم إنشاء الطلب البسيط بنجاح',
                    'order_id' => $simpleOrderData['orderId'],
                    'response' => $response->json(),
                    'dashboard_url' => 'https://app.tryoto.com/'
                ];
            } else {
                // Try to get more information about the error
                $errorData = $response->json();
                return [
                    'success' => false,
                    'message' => 'فشل في إنشاء الطلب البسيط',
                    'status_code' => $response->status(),
                    'error_details' => $errorData,
                    'order_id' => $simpleOrderData['orderId'],
                    'note' => 'راجع تفاصيل الخطأ أدناه'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Tryoto Simple Test Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطأ في إنشاء الطلب البسيط: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a test order that should appear in Tryoto dashboard
     */
    public function createDashboardTestOrder()
    {
        try {
            Log::info('Tryoto: Creating dashboard test order...');
            
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => 'فشل في الحصول على access token'
                ];
            }

            // Create a unique test order ID
            $testOrderId = 'BERNSA-TEST-' . time();
            
            // Prepare test order data that should work with Tryoto dashboard
            $testOrderData = [
                "orderId" => $testOrderId,
                "ref1" => "BERNSA-REF-" . time(),
                "deliveryOptionId" => "12364",
                "serviceType" => "standard",
                "createShipment" => true, // Set to true to create shipment
                "storeName" => "شركة بيرن التجارية - اختبار لوحة التحكم",
                "payment_method" => "paid",
                "amount" => 150.00,
                "amount_due" => 0,
                "customsValue" => "150",
                "customsCurrency" => "SAR",
                "shippingAmount" => 25.00,
                "subtotal" => 125.00,
                "currency" => "SAR",
                "shippingNotes" => "طلب تجريبي للاختبار في لوحة التحكم - يرجى التعامل بحذر",
                "packageSize" => "medium",
                "packageCount" => 1,
                "packageWeight" => 2.5,
                "boxWidth" => 20,
                "boxLength" => 20,
                "boxHeight" => 15,
                "orderDate" => Carbon::now('Asia/Riyadh')->format('d/m/Y H:i'),
                "deliverySlotDate" => Carbon::now('Asia/Riyadh')->addDay()->format('d/m/Y'),
                "deliverySlotTo" => "14:00",
                "deliverySlotFrom" => "16:00",
                "senderName" => "شركة بيرن التجارية",
                "senderInformation" => [
                    "senderAddressName" => "شركة بيرن التجارية",
                    "senderId" => "BERNSA001",
                    "senderFullName" => "شركة بيرن التجارية",
                    "senderMobile" => "966548060989",
                    "senderEmail" => "bernsa2030@gmail.com",
                    "senderCountry" => "SA",
                    "senderCity" => "Jeddah",
                    "senderPostcode" => "23421",
                    "senderAddressLine" => "جدة حي البغدادية - شارع التحلية",
                    "lat" => "21.5433912",
                    "lon" => "39.1728825"
                ],
                "customer" => [
                    "name" => "عميل تجريبي - لوحة التحكم",
                    "email" => "test-dashboard@bernsa.com",
                    "mobile" => "966501234567",
                    "address" => "الرياض - حي النرجس - شارع الملك فهد",
                    "district" => "النرجس",
                    "city" => "Riyadh",
                    "country" => "SA",
                    "postcode" => "12488",
                    "lat" => "24.7747262",
                    "lon" => "46.7041824",
                    "refID" => $testOrderId,
                    "W3WAddress" => "test.dashboard.riyadh"
                ],
                "items" => [
                    [
                        'productId' => 'BERNSA-TEST-001',
                        'name' => 'منتج تجريبي للوحة التحكم',
                        'price' => 125.00,
                        'rowTotal' => 125.00,
                        'taxAmount' => 15,
                        'quantity' => 1,
                        'sku' => 'BERNSA-SKU-TEST-001',
                        'image' => 'https://bernsa.com/images/test-product.jpg'
                    ]
                ]
            ];

            // Try different endpoints for creating the order
            $endpoints = [
                'createOrder',
                'orders/create',
                'order/create',
                'shipment/create'
            ];

            foreach ($endpoints as $endpoint) {
                try {
                    Log::info("Tryoto: Trying endpoint: {$endpoint}");
                    
                    $response = Http::timeout(60)->withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])->post($this->baseUrl . $endpoint, $testOrderData);

                    Log::info("Tryoto: Response from {$endpoint} - Status: " . $response->status());
                    Log::info("Tryoto: Response body: " . $response->body());

                    if ($response->successful()) {
                        $responseData = $response->json();
                        
                        return [
                            'success' => true,
                            'message' => 'تم إنشاء الطلب التجريبي بنجاح في لوحة التحكم',
                            'order_id' => $testOrderId,
                            'endpoint' => $endpoint,
                            'response' => $responseData,
                            'dashboard_url' => 'https://app.tryoto.com/',
                            'note' => 'يمكنك الآن التحقق من الطلب في لوحة التحكم'
                        ];
                    } else {
                        Log::warning("Tryoto: Failed with endpoint {$endpoint} - Status: " . $response->status());
                    }
                } catch (\Exception $e) {
                    Log::error("Tryoto: Error with endpoint {$endpoint}: " . $e->getMessage());
                }
            }

            return [
                'success' => false,
                'message' => 'فشل في إنشاء الطلب التجريبي في جميع المحاولات',
                'order_id' => $testOrderId,
                'attempted_endpoints' => $endpoints,
                'note' => 'راجع السجلات للحصول على تفاصيل أكثر'
            ];

        } catch (\Exception $e) {
            Log::error('Tryoto Dashboard Test Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطأ في إنشاء الطلب التجريبي: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send test order to Tryoto
     */
    private function sendTestOrderToTryoto($payload, $accessToken)
    {
        try {
            Log::info('Tryoto: Sending test order...', ['orderId' => $payload['orderId']]);
            
            $response = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'createOrder', $payload);

            Log::info('Tryoto: Test order response status: ' . $response->status());
            Log::info('Tryoto: Test order response body: ' . $response->body());

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'تم إنشاء الطلب التجريبي بنجاح',
                    'response' => $data
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'فشل في إنشاء الطلب التجريبي',
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Tryoto Test Order Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطأ في إرسال الطلب التجريبي: ' . $e->getMessage()
            ];
        }
    }

    public function getAccessToken()
    {
        try {
            $response = Http::post($this->baseUrl . 'refreshToken', [
                'refresh_token' => $this->refreshToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }

            Log::error('Tryoto: Failed to retrieve access_token. Response: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Tryoto Token Error: ' . $e->getMessage());
            return null;
        }
    }

    private function prepareAddressData($order)
    {
        $defaultAddress = [
            'address_line1' => 'Default Address',
            'locality' => 'Riyadh',
            'country_code' => 'SA',
            'postal_code' => '12488',
        ];

        if ($order->shippingAddress) {
            return [
                'address_line1' => $order->shippingAddress->address ?? $defaultAddress['address_line1'],
                'locality' => $order->shippingAddress->city ?? $defaultAddress['locality'],
                'country_code' => $order->shippingAddress->country ?? $defaultAddress['country_code'],
                'postal_code' => $order->shippingAddress->zip ?? $defaultAddress['postal_code'],
            ];
        }

        return $defaultAddress;
    }

    private function prepareCustomerData($order, $addressData)
    {
        $customerName = 'Guest Customer';
        $phone = '500000000';
        
        if ($order->customer) {
            $customerName = $order->customer->f_name . ' ' . $order->customer->l_name;
            $phone = $order->customer->phone ?? $phone;
            
            // Remove country code if present
            if (strpos($phone, '966') === 0) {
                $phone = substr($phone, 3);
            }
        }

        return [
            "name" => $customerName,
            "email" => $order->customer->email ?? 'default@bernsa.com',
            "mobile" => $phone,
            "address" => $addressData['address_line1'],
            "district" => "",
            "city" => $addressData['locality'],
            "country" => $addressData['country_code'],
            "postcode" => $addressData['postal_code'],
            "lat" => "24.7747262",
            "lon" => "46.7041824",
            "refID" => $order->id,
            "W3WAddress" => "default.address.saudi"
        ];
    }

    private function prepareItemsData($order)
    {
        $itemsArray = [];

        foreach ($order->details as $detail) {
            $product = $detail->product;
            $unitPrice = (float) $detail->price;
            $totalPrice = $unitPrice * $detail->qty;
            
            $itemsArray[] = [
                'productId' => $detail->id,
                'name' => $product->name ?? 'Unknown Product',
                'price' => $unitPrice,
                'rowTotal' => $totalPrice,
                'taxAmount' => 15,
                'quantity' => $detail->qty,
                'sku' => $product->code ?? 'DEFAULT-SKU',
                'image' => $product->thumbnail_full_url['path'] ?? 'https://bernsa.com/default-product-image.jpg'
            ];
        }

        // Return default item if no items found
        if (empty($itemsArray)) {
            return [
                [
                    'productId' => '0',
                    'name' => 'Default Product',
                    'price' => 0,
                    'rowTotal' => 0,
                    'taxAmount' => 15,
                    'quantity' => 1,
                    'sku' => 'DEFAULT-SKU',
                    'image' => 'https://bernsa.com/default-product-image.jpg'
                ]
            ];
        }

        return $itemsArray;
    }

    private function sendOrderToTryoto($payload, $accessToken, $orderId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'createOrder', $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Tryoto: Order created successfully for order ID: ' . $orderId);
                
                // Save tracking information to database if needed
                $this->saveTrackingInfo($orderId, $data);
                
                return $data;
            }

            Log::error('Tryoto: Failed to create order. Response: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Tryoto CreateOrder Error: ' . $e->getMessage());
            return null;
        }
    }

    private function saveTrackingInfo($orderId, $responseData)
    {
        try {
            $updateData = [
                'tryoto_response' => $responseData,
                'tryoto_status' => 'processing'
            ];
            
            // Save shipment ID if available
            if (isset($responseData['otoId'])) {
                $updateData['tryoto_shipment_id'] = $responseData['otoId'];
            } elseif (isset($responseData['shipmentId'])) {
                $updateData['tryoto_shipment_id'] = $responseData['shipmentId'];
            } elseif (isset($responseData['trackingNumber'])) {
                $updateData['tryoto_shipment_id'] = $responseData['trackingNumber'];
            } elseif (isset($responseData['awbNumber'])) {
                $updateData['tryoto_shipment_id'] = $responseData['awbNumber'];
            }
            
            // Update order with Tryoto information
            Order::where('id', $orderId)->update($updateData);
            
            Log::info("Tryoto: Saved tracking info for order $orderId", $updateData);
        } catch (\Exception $e) {
            Log::error("Tryoto: Failed to save tracking info for order $orderId: " . $e->getMessage());
        }
    }
}
