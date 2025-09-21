<?php

namespace App\Console\Commands;

use App\Services\TryotoShippingService;
use Illuminate\Console\Command;

class CreateTryotoShipment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tryoto:create-shipment {order_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Tryoto shipment for an order';

    /**
     * Execute the console command.
     */
    public function handle(TryotoShippingService $tryotoService)
    {
        $orderId = $this->argument('order_id');
        
        $this->info("Creating Tryoto shipment for order ID: $orderId");
        
        // Check if order exists
        $order = \App\Models\Order::find($orderId);
        if (!$order) {
            $this->error("❌ Order with ID $orderId not found!");
            return 1;
        }
        
        $this->info("📦 Order found: {$order->id} - Amount: {$order->order_amount} SAR");
        $this->info("💳 Payment Status: {$order->payment_status}");
        $this->info("📋 Order Status: {$order->order_status}");
        
        if ($order->payment_status !== 'paid') {
            $this->warn("⚠️  Order is not paid yet. Payment status: {$order->payment_status}");
            if (!$this->confirm('Do you want to create shipment anyway?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }
        
        $result = $tryotoService->createOrder($orderId);
        
        if ($result && isset($result['success']) && $result['success']) {
            $this->info("✅ Shipment created successfully!");
            $this->line("📊 Response: " . json_encode($result, JSON_PRETTY_PRINT));
            
            if (isset($result['oto_id'])) {
                $this->info("🆔 Tryoto ID: {$result['oto_id']}");
            }
            
            if (isset($result['dashboard_url'])) {
                $this->info("🔗 Dashboard: {$result['dashboard_url']}");
            }
        } else {
            $this->error("❌ Failed to create shipment. Check the logs for details.");
            if ($result) {
                $this->line("📊 Response: " . json_encode($result, JSON_PRETTY_PRINT));
            }
            return 1;
        }
        
        return 0;
    }
}
