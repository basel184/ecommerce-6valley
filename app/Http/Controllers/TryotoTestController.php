<?php

namespace App\Http\Controllers;

use App\Services\TryotoShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TryotoTestController extends Controller
{
    protected $tryotoService;

    public function __construct(TryotoShippingService $tryotoService)
    {
        $this->tryotoService = $tryotoService;
    }

    /**
     * Test Tryoto API connection
     */
    public function testApi()
    {
        try {
            Log::info('Tryoto: Starting API test from controller...');
            
            $result = $this->tryotoService->testApiConnection();
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Tryoto Test Controller Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في اختبار API: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    /**
     * Test specific API endpoint
     */
    public function testEndpoint(Request $request)
    {
        $endpoint = $request->input('endpoint', 'refreshToken');
        
        try {
            $baseUrl = 'https://api.tryoto.com/rest/v2/';
            $url = $baseUrl . $endpoint;
            
            Log::info("Tryoto: Testing endpoint: $url");
            
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($url);
            
            return response()->json([
                'success' => true,
                'endpoint' => $endpoint,
                'url' => $url,
                'status_code' => $response->status(),
                'response' => $response->body(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error("Tryoto Endpoint Test Error: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'endpoint' => $endpoint,
                'message' => 'خطأ في اختبار النقطة: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    /**
     * Test different endpoints
     */
    public function testEndpoints()
    {
        try {
            $accessToken = $this->tryotoService->getAccessToken();
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في الحصول على access token'
                ], 500);
            }
            
            $results = $this->tryotoService->testEndpoints($accessToken);
            
            return response()->json([
                'success' => true,
                'message' => 'تم اختبار الـ endpoints',
                'results' => $results,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Tryoto Endpoints Test Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في اختبار الـ endpoints: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    /**
     * Check account permissions
     */
    public function checkPermissions()
    {
        try {
            $result = $this->tryotoService->checkAccountPermissions();
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Tryoto Permissions Check Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في فحص الصلاحيات: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    /**
     * Test API versions
     */
    public function testVersions()
    {
        try {
            $results = $this->tryotoService->testApiVersions();
            
            return response()->json([
                'success' => true,
                'message' => 'تم اختبار إصدارات API',
                'results' => $results,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Tryoto Versions Test Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في اختبار الإصدارات: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    /**
     * Test create order with correct format
     */
    public function testCreateOrderCorrect()
    {
        try {
            Log::info('Tryoto: Testing create order with correct format...');
            
            // Use order ID 100001 for testing (first existing order)
            $orderId = 100001;
            $result = $this->tryotoService->createOrderWithCorrectFormat($orderId);
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result,
                'dashboard_url' => 'https://app.tryoto.com/',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Tryoto Create Order Test Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في اختبار إنشاء الطلب: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    /**
     * Create simple test order
     */
    public function createSimpleTest()
    {
        try {
            Log::info('Tryoto: Starting simple test order creation...');
            
            $result = $this->tryotoService->createSimpleTestOrder();
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result,
                'dashboard_url' => 'https://app.tryoto.com/',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Tryoto Simple Test Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في إنشاء الطلب البسيط: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    /**
     * Create dashboard test order
     */
    public function createDashboardTest()
    {
        try {
            Log::info('Tryoto: Starting dashboard test order creation...');
            
            $result = $this->tryotoService->createDashboardTestOrder();
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result,
                'dashboard_url' => 'https://app.tryoto.com/',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Tryoto Dashboard Test Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في إنشاء الطلب التجريبي: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    /**
     * Try alternative create order methods
     */
    public function tryAlternatives(Request $request)
    {
        try {
            $orderId = $request->input('order_id', 1); // Default to order ID 1 for testing
            $result = $this->tryotoService->tryCreateOrderAlternatives($orderId);
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Tryoto Alternatives Test Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في اختبار البدائل: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    /**
     * Check if API is accessible
     */
    public function checkApiAccess()
    {
        try {
            $baseUrl = 'https://api.tryoto.com/rest/v2/';
            
            // Test basic connectivity
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($baseUrl);
            
            $result = [
                'base_url' => $baseUrl,
                'status_code' => $response->status(),
                'accessible' => $response->successful(),
                'response_time' => $response->handlerStats()['total_time'] ?? 'unknown',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ];
            
            if ($response->successful()) {
                $result['message'] = 'API قابل للوصول';
            } else {
                $result['message'] = 'API غير قابل للوصول - رمز الحالة: ' . $response->status();
            }
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Tryoto API Access Check Error: ' . $e->getMessage());
            
            return response()->json([
                'base_url' => 'https://api.tryoto.com/rest/v2/',
                'accessible' => false,
                'message' => 'خطأ في الاتصال: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }
} 