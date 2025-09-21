<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class QuickDiagnose extends Command
{
    protected $signature = 'quick:diagnose {order_id}';
    protected $description = 'Quick diagnosis for missing order issue';

    public function handle()
    {
        $orderId = $this->argument('order_id');
        
        $this->info("🔍 Quick Diagnosis for Order: {$orderId}");
        $this->line("================================");
        
        // 1. Check in orders table
        $order = Order::find($orderId);
        $this->line("📋 Orders Table:");
        if ($order) {
            $this->info("   ✅ Found order: {$order->id}");
            $this->line("   Customer: {$order->customer_id}");
            $this->line("   Amount: {$order->order_amount}");
        } else {
            $this->error("   ❌ Order NOT FOUND in orders table");
        }
        
        // 2. Check in payment_requests by attribute_id
        $payment = DB::table('payment_requests')->where('attribute_id', $orderId)->first();
        $this->line("\n💳 Payment Requests (by attribute_id):");
        if ($payment) {
            $this->info("   ✅ Found payment request:");
            $this->line("   Payment ID: {$payment->id}");
            $this->line("   Order IDs: " . ($payment->order_ids ?? 'NULL'));
            $this->line("   Customer Ref: " . ($payment->customer_reference ?? 'NULL'));
            $this->line("   Status: " . ($payment->payment_status ?? 'NULL'));
            $this->line("   Created: " . ($payment->created_at ?? 'NULL'));
        } else {
            $this->error("   ❌ Payment request NOT FOUND");
        }
        
        // 3. Check if order_ids contains this ID
        $paymentsByOrderIds = DB::table('payment_requests')
            ->where('order_ids', 'like', "%{$orderId}%")
            ->get();
        
        $this->line("\n🔗 Payment Requests (by order_ids):");
        if ($paymentsByOrderIds->count() > 0) {
            foreach ($paymentsByOrderIds as $p) {
                $this->info("   ✅ Found in order_ids: Payment {$p->id}");
                $this->line("   Order IDs: {$p->order_ids}");
            }
        } else {
            $this->warn("   ⚠️  No payment requests contain this order_id");
        }
        
        // 4. Latest orders for context
        $this->line("\n📊 Latest Orders (for context):");
        $latest = Order::latest()->take(3)->get(['id', 'created_at']);
        foreach ($latest as $o) {
            $this->line("   Order {$o->id} - {$o->created_at}");
        }
        
        // 5. Latest payment_requests for context
        $this->line("\n💰 Latest Payment Requests:");
        $latestPayments = DB::table('payment_requests')
            ->latest('created_at')
            ->take(3)
            ->get(['id', 'attribute_id', 'order_ids', 'created_at']);
        
        foreach ($latestPayments as $p) {
            $this->line("   Payment {$p->id}: attribute_id={$p->attribute_id}, order_ids={$p->order_ids}");
        }
        
        // 6. Recommendation
        $this->line("\n💡 Analysis:");
        if (!$order && $payment && empty($payment->order_ids)) {
            $this->warn("   The payment_request exists but order_ids is empty!");
            $this->warn("   This suggests the order creation process failed.");
            $this->line("   Check the order creation logic in payment callback.");
        } elseif (!$order && !$payment) {
            $this->error("   Neither order nor payment_request found!");
            $this->error("   This suggests data corruption or wrong ID.");
        } elseif ($order && !$payment) {
            $this->info("   Order exists but no payment_request found.");
            $this->line("   This might be a manual order or different payment method.");
        }
        
        return Command::SUCCESS;
    }
}
