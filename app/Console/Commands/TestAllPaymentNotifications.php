<?php

namespace App\Console\Commands;

use App\Services\OrderNotificationService;
use Illuminate\Console\Command;

class TestAllPaymentNotifications extends Command
{
    protected $signature = 'test:all-payment-notifications {order_id}';
    protected $description = 'Test order notifications for all payment methods';

    public function handle(OrderNotificationService $notificationService)
    {
        $orderId = $this->argument('order_id');
        
        $this->info("Testing order notifications for order ID: {$orderId}");
        
        // Test different payment methods
        $paymentMethods = [
            'MyFatoorah' => [
                'transaction_reference' => 'MF_' . time(),
                'payment_amount' => 150.00,
                'payment_method' => 'MyFatoorah'
            ],
            'Tamara' => [
                'transaction_reference' => 'TAM_' . time(),
                'payment_amount' => 200.00,
                'payment_method' => 'Tamara'
            ],
            'Tabby' => [
                'transaction_reference' => 'TAB_' . time(),
                'payment_amount' => 300.00,
                'payment_method' => 'Tabby'
            ]
        ];
        
        foreach ($paymentMethods as $method => $data) {
            $this->info("Testing {$method} notifications...");
            
            $result = $notificationService->sendOrderNotifications($orderId, $data);
            
            if ($result) {
                $this->info("✅ {$method} notifications sent successfully!");
            } else {
                $this->error("❌ Failed to send {$method} notifications.");
            }
        }
        
        return 0;
    }
}
