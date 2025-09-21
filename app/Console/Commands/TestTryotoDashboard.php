<?php

namespace App\Console\Commands;

use App\Services\TryotoShippingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestTryotoDashboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tryoto:dashboard-test {--detailed : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Tryoto dashboard connectivity and create test orders';

    protected $tryotoService;

    /**
     * Create a new command instance.
     *
     * @return int
     */
    public function __construct(TryotoShippingService $tryotoService)
    {
        parent::__construct();
        $this->tryotoService = $tryotoService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔧 بدء اختبار لوحة التحكم Tryoto...');
        $this->newLine();

        // Test 1: Check API connectivity
        $this->info('1️⃣ فحص الاتصال بالـ API...');
        $this->testApiConnectivity();
        $this->newLine();

        // Test 2: Check authentication
        $this->info('2️⃣ فحص الـ Authentication...');
        $this->testAuthentication();
        $this->newLine();

        // Test 3: Check existing orders
        $this->info('3️⃣ فحص الطلبات الموجودة...');
        $this->testExistingOrders();
        $this->newLine();

        // Test 4: Try to create test order
        $this->info('4️⃣ محاولة إنشاء طلب تجريبي...');
        $this->testCreateOrder();
        $this->newLine();

        // Test 5: Check dashboard access
        $this->info('5️⃣ فحص إمكانية الوصول للوحة التحكم...');
        $this->testDashboardAccess();
        $this->newLine();

        $this->info('✅ تم الانتهاء من اختبار لوحة التحكم');
        $this->info('🔗 لوحة التحكم: https://app.tryoto.com/');
        
        return 0;
    }

    private function testApiConnectivity()
    {
        try {
            $response = Http::timeout(10)->get('https://api.tryoto.com/rest/v2/');
            
            if ($response->status() === 401) {
                $this->info('✅ API قابل للوصول (401 طبيعي - يحتاج authentication)');
            } else {
                $this->info('✅ API قابل للوصول - Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('❌ خطأ في الاتصال: ' . $e->getMessage());
        }
    }

    private function testAuthentication()
    {
        try {
            $accessToken = $this->tryotoService->getAccessToken();
            
            if ($accessToken) {
                $this->info('✅ تم الحصول على Access Token بنجاح');
                if ($this->option('detailed')) {
                    $this->line('Token: ' . substr($accessToken, 0, 20) . '...');
                }
            } else {
                $this->error('❌ فشل في الحصول على Access Token');
            }
        } catch (\Exception $e) {
            $this->error('❌ خطأ في الـ Authentication: ' . $e->getMessage());
        }
    }

    private function testExistingOrders()
    {
        try {
            $accessToken = $this->tryotoService->getAccessToken();
            if (!$accessToken) {
                $this->error('❌ لا يمكن فحص الطلبات - فشل في الحصول على token');
                return;
            }

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])->get('https://api.tryoto.com/rest/v2/orders');

            if ($response->successful()) {
                $data = $response->json();
                $ordersCount = count($data['orders'] ?? []);
                $this->info("✅ تم العثور على {$ordersCount} طلب في لوحة التحكم");
                
                if ($this->option('detailed') && $ordersCount > 0) {
                    $this->line('أمثلة على الطلبات:');
                    foreach (array_slice($data['orders'], 0, 3) as $order) {
                        $this->line("- Order ID: {$order['orderId']} - Amount: {$order['amount']} SAR");
                    }
                }
            } else {
                $this->error('❌ فشل في جلب الطلبات - Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('❌ خطأ في فحص الطلبات: ' . $e->getMessage());
        }
    }

    private function testCreateOrder()
    {
        try {
            $result = $this->tryotoService->createSimpleTestOrder();
            
            if ($result['success']) {
                $this->info('✅ تم إنشاء الطلب التجريبي بنجاح!');
                $this->line("Order ID: {$result['order_id']}");
                $this->line("Dashboard URL: https://app.tryoto.com/");
            } else {
                $this->error('❌ فشل في إنشاء الطلب التجريبي');
                $this->line("Error: {$result['message']}");
                
                if (isset($result['status_code'])) {
                    $this->line("Status Code: {$result['status_code']}");
                }
                
                if (isset($result['error_details'])) {
                    $this->line("Error Details: " . json_encode($result['error_details']));
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ خطأ في إنشاء الطلب: ' . $e->getMessage());
        }
    }

    private function testDashboardAccess()
    {
        try {
            $response = Http::timeout(10)->get('https://app.tryoto.com/');
            
            if ($response->successful()) {
                $this->info('✅ لوحة التحكم متاحة للوصول');
                $this->line('URL: https://app.tryoto.com/');
            } else {
                $this->warning('⚠️ لوحة التحكم غير متاحة - Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('❌ خطأ في الوصول للوحة التحكم: ' . $e->getMessage());
        }
    }
} 