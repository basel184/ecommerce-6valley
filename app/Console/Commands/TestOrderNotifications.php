<?php

namespace App\Console\Commands;

use App\Services\OrderNotificationService;
use Illuminate\Console\Command;

class TestOrderNotifications extends Command
{
    protected $signature = 'test:order-notifications {order_id}';
    protected $description = 'Test order notifications for a specific order';

    public function handle(OrderNotificationService $notificationService)
    {
        $orderId = $this->argument('order_id');
        
        $this->info("Testing order notifications for order ID: {$orderId}");
        
        $result = $notificationService->sendOrderNotifications($orderId, [
            'transaction_reference' => 'TEST_' . time(),
            'payment_amount' => 100.00
        ]);
        
        if ($result) {
            $this->info('✅ Notifications sent successfully!');
        } else {
            $this->error('❌ Failed to send notifications.');
        }
        
        return $result ? 0 : 1;
    }
}
