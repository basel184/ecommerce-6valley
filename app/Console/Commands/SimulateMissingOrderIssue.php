<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OrderNotificationService;

class SimulateMissingOrderIssue extends Command
{
    protected $signature = 'simulate:missing-order {order_id=1752009200}';
    protected $description = 'Simulate the missing order issue to test diagnostic improvements';

    public function handle()
    {
        $orderId = $this->argument('order_id');
        
        $this->info("🔧 محاكاة مشكلة الطلب المفقود: {$orderId}");
        $this->line("====================================");
        
        $notificationService = app(OrderNotificationService::class);
        
        $this->line("📞 محاولة إرسال إشعارات للطلب المفقود...");
        
        $result = $notificationService->sendOrderNotifications($orderId, [
            'payment_method' => 'Test Simulation',
            'transaction_reference' => 'SIMULATE_' . time(),
            'customer_reference' => '55979821-8a49-42d6-9838-4d41eccae9f3'
        ]);
        
        if ($result) {
            $this->info("✅ تم إرسال الإشعارات بنجاح (غير متوقع!)");
        } else {
            $this->error("❌ فشل في إرسال الإشعارات (متوقع)");
            $this->line("📋 تحقق من ملف اللوجز للتفاصيل التشخيصية المفصلة");
        }
        
        $this->line("\n💡 للتحقق من التشخيص:");
        $this->line("tail -f storage/logs/laravel.log | grep -A 20 'MISSING ORDER DIAGNOSTIC'");
        
        return Command::SUCCESS;
    }
}
