<?php

namespace App\Console\Commands;

use App\Services\TryotoShippingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestTryotoApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tryoto:test {--detailed : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Tryoto API connectivity and create a test order';

    protected $tryotoService;

    /**
     * Create a new command instance.
     *
     * @return void
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
        $this->info('🔧 بدء اختبار API Tryoto...');
        $this->newLine();

        // Test 1: Check API accessibility
        $this->info('1️⃣ فحص إمكانية الوصول للـ API...');
        $this->testApiAccess();
        $this->newLine();

        // Test 2: Test access token
        $this->info('2️⃣ اختبار الحصول على Access Token...');
        $this->testAccessToken();
        $this->newLine();

        // Test 3: Test full API functionality
        $this->info('3️⃣ اختبار إنشاء طلب تجريبي...');
        $this->testFullApi();
        $this->newLine();

        // Test 4: Check Tryoto status
        $this->info('4️⃣ فحص حالة Tryoto والطلبات الأخيرة...');
        $this->checkTryotoStatus();
        $this->newLine();

        $this->info('✅ تم الانتهاء من الاختبارات');
        return 0;
    }

    private function testApiAccess()
    {
        try {
            $baseUrl = 'https://api.tryoto.com/rest/v2/';
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($baseUrl);
            
            if ($response->successful()) {
                $this->info('✅ API قابل للوصول');
                if ($this->option('detailed')) {
                    $this->line('Status Code: ' . $response->status());
                    $this->line('Response Time: ' . ($response->handlerStats()['total_time'] ?? 'unknown') . 's');
                }
            } else {
                $this->error('❌ API غير قابل للوصول - Status: ' . $response->status());
                if ($this->option('detailed')) {
                    $this->line('Response: ' . $response->body());
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ خطأ في الاتصال: ' . $e->getMessage());
        }
    }

    private function testAccessToken()
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
            $this->error('❌ خطأ في الحصول على Access Token: ' . $e->getMessage());
        }
    }

    private function testFullApi()
    {
        try {
            $result = $this->tryotoService->testApiConnection();
            
            if ($result['success']) {
                $this->info('✅ تم اختبار API بنجاح');
                if ($this->option('detailed')) {
                    $this->line('Message: ' . $result['message']);
                    if (isset($result['test_order_result'])) {
                        $this->line('Test Order Result: ' . json_encode($result['test_order_result'], JSON_PRETTY_PRINT));
                    }
                }
            } else {
                $this->error('❌ فشل في اختبار API');
                $this->line('Message: ' . $result['message']);
                if (isset($result['details'])) {
                    $this->line('Details: ' . $result['details']);
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ خطأ في اختبار API: ' . $e->getMessage());
        }
    }

    /**
     * Check Tryoto status and recent shipments
     */
    private function checkTryotoStatus()
    {
        $this->line('🔍 فحص حالة Tryoto:');
        
        // Check configuration
        $enabled = config('services.tryoto.enabled');
        $this->info("   ✓ Tryoto مفعل: " . ($enabled ? 'نعم' : 'لا'));
        
        if (!$enabled) {
            $this->warn('   ⚠ Tryoto معطل في الإعدادات');
            return;
        }
        
        // Check recent orders with Tryoto shipments
        try {
            $recentTryotoOrders = \App\Models\Order::where('created_at', '>=', now()->subDays(7))
                ->whereNotNull('tryoto_shipment_id')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            $this->info("   ✓ الطلبات المرسلة لـ Tryoto (7 أيام): {$recentTryotoOrders->count()}");
            
            if ($recentTryotoOrders->count() > 0) {
                $this->line('   📋 آخر الطلبات:');
                foreach ($recentTryotoOrders as $order) {
                    $this->line("      - الطلب {$order->id}: Tryoto ID {$order->tryoto_shipment_id} - الحالة: {$order->tryoto_status}");
                }
            }
            
            // Check pending orders without Tryoto shipments
            $pendingOrders = \App\Models\Order::where('created_at', '>=', now()->subDays(7))
                ->where('payment_status', 'paid')
                ->whereNull('tryoto_shipment_id')
                ->count();
            
            if ($pendingOrders > 0) {
                $this->warn("   ⚠ يوجد {$pendingOrders} طلب مدفوع بدون شحنة Tryoto");
            }
            
        } catch (\Exception $e) {
            $this->error("   ✗ خطأ في فحص الطلبات: {$e->getMessage()}");
        }
    }
} 