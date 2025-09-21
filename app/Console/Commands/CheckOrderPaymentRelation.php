<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CheckOrderPaymentRelation extends Command
{
    protected $signature = 'check:order-payment-relation';
    protected $description = 'Check the relationship between orders and payment_requests';

    public function handle()
    {
        $this->info("🔍 فحص العلاقة بين الطلبات و payment_requests");
        $this->line("==================================");
        
        // آخر 5 طلبات
        $this->line("📋 آخر 5 طلبات في النظام:");
        $orders = Order::latest()->take(5)->get(['id', 'created_at', 'customer_id', 'order_amount']);
        foreach ($orders as $order) {
            $this->line("   Order {$order->id}: Customer {$order->customer_id}, Amount {$order->order_amount}, Time {$order->created_at}");
        }
        
        $this->line("\n💳 آخر 5 payment_requests:");
        $payments = DB::table('payment_requests')
            ->latest()
            ->take(5)
            ->get(['id', 'attribute_id', 'order_ids', 'created_at', 'payment_status', 'is_paid']);
        
        foreach ($payments as $payment) {
            $this->line("   Payment {$payment->attribute_id}: order_ids={$payment->order_ids}, status={$payment->payment_status}, paid={$payment->is_paid}");
        }
        
        // فحص الطلب المفقود
        $this->line("\n🔍 فحص الطلب المفقود 1752009200:");
        $missingPayment = DB::table('payment_requests')->where('attribute_id', '1752009200')->first();
        if ($missingPayment) {
            $this->line("   ✅ Payment موجود:");
            $this->line("   ID: {$missingPayment->id}");
            $this->line("   Order IDs: " . ($missingPayment->order_ids ?? 'NULL'));
            $this->line("   Status: {$missingPayment->payment_status}");
            $this->line("   Paid: {$missingPayment->is_paid}");
            $this->line("   Created: {$missingPayment->created_at}");
            
            if (empty($missingPayment->order_ids)) {
                $this->error("   ❌ المشكلة: order_ids فارغ!");
                $this->line("   💡 هذا يعني أن الدفع تم لكن الطلب لم يُربط بـ payment_request");
            }
        } else {
            $this->error("   ❌ Payment غير موجود");
        }
        
        // فحص إذا كان هناك طلب بنفس التوقيت
        $this->line("\n🕒 البحث عن طلبات بنفس التوقيت:");
        if ($missingPayment) {
            $timeAround = $missingPayment->created_at;
            $ordersNearTime = Order::whereBetween('created_at', [
                date('Y-m-d H:i:s', strtotime($timeAround) - 300), // 5 دقائق قبل
                date('Y-m-d H:i:s', strtotime($timeAround) + 300)  // 5 دقائق بعد
            ])->get(['id', 'created_at', 'customer_id']);
            
            if ($ordersNearTime->count() > 0) {
                $this->info("   ✅ وجدت طلبات قريبة من وقت الدفع:");
                foreach ($ordersNearTime as $order) {
                    $this->line("      Order {$order->id}: Customer {$order->customer_id}, Time {$order->created_at}");
                }
                
                $this->line("\n💡 التوصية: يجب ربط أحد هذه الطلبات بـ payment_request");
            } else {
                $this->warn("   ⚠️  لا توجد طلبات قريبة من وقت الدفع");
            }
        }
        
        return Command::SUCCESS;
    }
}
