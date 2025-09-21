<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OrderNotificationService;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class TestDuplicateNotificationFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:duplicate-fix {order_id? : Order ID to test} {--real : Use real order instead of mock data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test that duplicate notifications are fixed after payment';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔧 Testing duplicate notification fix...');
        
        $orderId = $this->argument('order_id');
        $useReal = $this->option('real');
        
        if ($useReal && $orderId) {
            $this->testRealOrder($orderId);
        } else {
            $this->testMockNotification($orderId ?: '1752009200');
        }
        
        return 0;
    }
    
    /**
     * Test with real order data
     */
    private function testRealOrder($orderId)
    {
        $this->info("📋 Testing with real order: {$orderId}");
        
        $order = Order::with(['customer', 'orderDetails.product'])->find($orderId);
        
        if (!$order) {
            $this->error("❌ Order {$orderId} not found!");
            return;
        }
        
        $this->info("✅ Order found: {$order->id}");
        $this->info("👤 Customer: {$order->customer->f_name} {$order->customer->l_name}");
        $this->info("📱 Phone: {$order->customer->phone}");
        $this->info("💰 Amount: {$order->order_amount} {$order->currency_code}");
        
        // إرسال الإشعارات
        $notificationService = app(OrderNotificationService::class);
        
        $paymentData = [
            'transaction_reference' => 'TEST_' . time(),
            'payment_amount' => $order->order_amount,
            'payment_method' => 'Test Payment',
            'customer_reference' => uniqid()
        ];
        
        $this->info('📤 Sending notifications...');
        
        // Clear logs to see only our test
        Log::info('=== DUPLICATE FIX TEST START ===');
        
        $result = $notificationService->sendOrderNotifications($orderId, $paymentData);
        
        Log::info('=== DUPLICATE FIX TEST END ===');
        
        if ($result) {
            $this->info('✅ Notifications sent successfully!');
            $this->info('📄 Check logs to verify no duplicates');
        } else {
            $this->error('❌ Failed to send notifications');
        }
    }
    
    /**
     * Test with mock payment data
     */
    private function testMockNotification($orderId)
    {
        $this->info("🧪 Testing with mock data for order ID: {$orderId}");
        
        $notificationService = app(OrderNotificationService::class);
        
        $paymentData = [
            'transaction_reference' => 'TEST_DUPLICATE_FIX_' . time(),
            'payment_amount' => 1.00,
            'payment_method' => 'Test Payment - Duplicate Fix',
            'customer_reference' => 'duplicate-fix-test-' . uniqid()
        ];
        
        $this->info('📤 Sending test notifications...');
        
        // Clear logs to see only our test
        Log::info('=== DUPLICATE FIX TEST START (MOCK) ===', [
            'test_order_id' => $orderId,
            'payment_data' => $paymentData
        ]);
        
        $result = $notificationService->sendOrderNotifications($orderId, $paymentData);
        
        Log::info('=== DUPLICATE FIX TEST END (MOCK) ===');
        
        if ($result) {
            $this->info('✅ Test notifications sent successfully!');
            $this->info('📄 Check logs to verify:');
            $this->info('   • No duplicate SMS messages');
            $this->info('   • WhatsApp fallback working correctly');
            $this->info('   • Only ONE notification per recipient');
        } else {
            $this->error('❌ Failed to send test notifications');
        }
        
        $this->info('');
        $this->info('💡 To test with real order:');
        $this->info('   php artisan test:duplicate-fix <order_id> --real');
    }
}
