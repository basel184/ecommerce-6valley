<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OrderNotificationService;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiagnoseMissingOrdersIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:diagnose-issues {order_id?} {--fix} {--test-notification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose missing orders and payment_requests relationship issues';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $orderId = $this->argument('order_id');
        
        if ($orderId) {
            return $this->diagnoseSingleOrder($orderId);
        }
        
        return $this->diagnoseSystem();
    }
    
    /**
     * تشخيص طلب واحد محدد
     */
    protected function diagnoseSingleOrder($orderId)
    {
        $this->info("🔍 تشخيص الطلب: {$orderId}");
        $this->newLine();
        
        // 1. البحث المباشر
        $this->line("1️⃣ البحث المباشر في جدول orders:");
        $order = Order::find($orderId);
        if ($order) {
            $this->info("✅ تم العثور على الطلب: {$order->id}");
            $this->line("   العميل: {$order->customer_id}");
            $this->line("   المبلغ: {$order->order_amount}");
            $this->line("   التاريخ: {$order->created_at}");
        } else {
            $this->error("❌ لم يتم العثور على الطلب في جدول orders");
        }
        
        // 2. البحث في payment_requests
        $this->newLine();
        $this->line("2️⃣ البحث في جدول payment_requests:");
        
        // البحث بـ attribute_id
        $paymentByAttribute = DB::table('payment_requests')->where('attribute_id', $orderId)->first();
        if ($paymentByAttribute) {
            $this->info("✅ تم العثور على payment_request بـ attribute_id:");
            $this->line("   Payment ID: {$paymentByAttribute->id}");
            $this->line("   Order IDs: " . ($paymentByAttribute->order_ids ?? 'NULL'));
            $this->line("   Customer Ref: " . ($paymentByAttribute->customer_reference ?? 'NULL'));
            $this->line("   Status: " . ($paymentByAttribute->payment_status ?? 'NULL'));
        } else {
            $this->warn("⚠️  لم يتم العثور على payment_request بـ attribute_id");
        }
        
        // البحث بـ order_ids
        $paymentsWithOrderId = DB::table('payment_requests')
            ->where('order_ids', 'like', "%{$orderId}%")
            ->get();
        
        if ($paymentsWithOrderId->count() > 0) {
            $this->info("✅ تم العثور على payment_requests تحتوي على order_id:");
            foreach ($paymentsWithOrderId as $payment) {
                $this->line("   Payment ID: {$payment->id}, Order IDs: {$payment->order_ids}");
            }
        } else {
            $this->warn("⚠️  لم يتم العثور على payment_requests تحتوي على order_id");
        }
        
        // البحث بـ ID مباشر
        $paymentById = DB::table('payment_requests')->where('id', $orderId)->first();
        if ($paymentById) {
            $this->info("✅ تم العثور على payment_request بالـ ID:");
            $this->line("   Order IDs: " . ($paymentById->order_ids ?? 'NULL'));
            $this->line("   Attribute ID: " . ($paymentById->attribute_id ?? 'NULL'));
        }
        
        // 3. اختبار إشعارات
        if ($this->option('test-notification')) {
            $this->newLine();
            $this->line("3️⃣ اختبار إرسال الإشعارات:");
            
            $notificationService = app(OrderNotificationService::class);
            $result = $notificationService->sendOrderNotifications($orderId, [
                'payment_method' => 'Diagnostic Test',
                'transaction_reference' => 'DIAG_' . time()
            ]);
            
            if ($result) {
                $this->info("✅ تم إرسال الإشعارات بنجاح");
            } else {
                $this->error("❌ فشل في إرسال الإشعارات");
            }
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * تشخيص شامل للنظام
     */
    protected function diagnoseSystem()
    {
        $this->info("🔍 تشخيص شامل للنظام");
        $this->newLine();
        
        // 1. إحصائيات عامة
        $ordersCount = Order::count();
        $paymentsCount = DB::table('payment_requests')->count();
        
        $this->info("📊 الإحصائيات:");
        $this->line("• عدد الطلبات: {$ordersCount}");
        $this->line("• عدد payment_requests: {$paymentsCount}");
        
        // 2. آخر الطلبات
        $this->newLine();
        $this->line("📋 آخر 5 طلبات:");
        $latestOrders = Order::latest()->limit(5)->get(['id', 'customer_id', 'order_amount', 'created_at']);
        foreach ($latestOrders as $order) {
            $this->line("   طلب {$order->id}: عميل {$order->customer_id}, مبلغ {$order->order_amount}, تاريخ {$order->created_at}");
        }
        
        // 3. آخر payment_requests
        $this->newLine();
        $this->line("💳 آخر 5 payment_requests:");
        $latestPayments = DB::table('payment_requests')
            ->latest()
            ->limit(5)
            ->get(['id', 'attribute_id', 'order_ids', 'payment_status', 'created_at']);
        
        foreach ($latestPayments as $payment) {
            $this->line("   Payment {$payment->id}: attribute_id {$payment->attribute_id}, order_ids {$payment->order_ids}, status {$payment->payment_status}");
        }
        
        // 4. البحث عن payment_requests بدون orders مطابقة
        $this->newLine();
        $this->line("🔍 البحث عن payment_requests بدون orders مطابقة:");
        
        $orphanedPayments = DB::table('payment_requests')
            ->whereNotNull('order_ids')
            ->where('order_ids', '!=', '')
            ->get(['id', 'attribute_id', 'order_ids']);
        
        $orphanedCount = 0;
        foreach ($orphanedPayments as $payment) {
            $orderIds = json_decode($payment->order_ids, true);
            if (!is_array($orderIds)) {
                $orderIds = explode(',', $payment->order_ids);
            }
            
            $foundOrder = false;
            foreach ($orderIds as $oid) {
                $oid = trim($oid);
                if (Order::where('id', $oid)->exists()) {
                    $foundOrder = true;
                    break;
                }
            }
            
            if (!$foundOrder) {
                $orphanedCount++;
                $this->warn("   ⚠️  Payment {$payment->id}: order_ids {$payment->order_ids} - لا توجد طلبات مطابقة");
            }
        }
        
        if ($orphanedCount === 0) {
            $this->info("✅ جميع payment_requests لها طلبات مطابقة");
        } else {
            $this->error("❌ تم العثور على {$orphanedCount} payment_requests بدون طلبات مطابقة");
            
            if ($this->option('fix')) {
                $this->newLine();
                $this->line("🔧 محاولة الإصلاح...");
                // يمكن إضافة منطق الإصلاح هنا
                $this->warn("الإصلاح التلقائي غير متاح حالياً");
            }
        }
        
        // 5. فحص سجلات الخطأ الأخيرة
        $this->newLine();
        $this->line("📝 فحص سجلات الخطأ الأخيرة:");
        
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $logContent = file_get_contents($logPath);
            $orderNotFoundCount = substr_count($logContent, 'Order not found for notifications');
            
            $this->line("• عدد أخطاء 'Order not found': {$orderNotFoundCount}");
            
            // استخراج آخر الطلبات المفقودة
            preg_match_all('/Order not found for notifications.*?"order_id":"([^"]+)"/', $logContent, $matches);
            if (!empty($matches[1])) {
                $missingOrderIds = array_unique(array_slice($matches[1], -5));
                $this->line("• آخر الطلبات المفقودة: " . implode(', ', $missingOrderIds));
            }
        }
        
        return Command::SUCCESS;
    }
}
