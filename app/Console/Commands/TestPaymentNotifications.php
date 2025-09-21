<?php

namespace App\Console\Commands;

use App\Jobs\SendOrderNotificationsJob;
use Illuminate\Console\Command;

class TestPaymentNotifications extends Command
{
    protected $signature = 'test:payment-notifications {order_id}';
    protected $description = 'Test notifications for all payment methods';

    public function handle()
    {
        $orderId = $this->argument('order_id');
        
        $this->info("🔔 Testing Payment Notifications for Order: {$orderId}");
        $this->line("═══════════════════════════════════════════════");
        
        // Test data for different payment methods
        $paymentMethods = [
            [
                'name' => 'MyFatoorah',
                'data' => [
                    'transaction_reference' => 'MF_' . time(),
                    'payment_amount' => 150.00,
                    'payment_method' => 'MyFatoorah'
                ]
            ],
            [
                'name' => 'Tamara',
                'data' => [
                    'transaction_reference' => 'TAM_' . time(),
                    'payment_amount' => 200.00,
                    'payment_method' => 'Tamara'
                ]
            ],
            [
                'name' => 'Tabby',
                'data' => [
                    'transaction_reference' => 'TAB_' . time(),
                    'payment_amount' => 300.00,
                    'payment_method' => 'Tabby'
                ]
            ]
        ];
        
        foreach ($paymentMethods as $method) {
            $this->info("💳 Testing {$method['name']} Notifications:");
            $this->line("  Amount: {$method['data']['payment_amount']} SAR");
            $this->line("  Reference: {$method['data']['transaction_reference']}");
            
            try {
                // Dispatch the job
                SendOrderNotificationsJob::dispatch($orderId, $method['data']);
                $this->info("  ✅ {$method['name']} notification job dispatched successfully!");
                
                // Add small delay to see the effect
                sleep(1);
                
            } catch (\Exception $e) {
                $this->error("  ❌ {$method['name']} notification failed: " . $e->getMessage());
            }
            
            $this->line("");
        }
        
        $this->info("🎯 Summary:");
        $this->line("All payment methods now support:");
        $this->line("  📱 SMS notifications via Taqnyat");
        $this->line("  💬 WhatsApp notifications via Taqnyat");
        $this->line("  🔄 Fallback SMS if WhatsApp fails");
        $this->line("  📧 Notifications to admin (966548060989)");
        $this->line("  📲 Notifications to customer");
        
        return 0;
    }
}
