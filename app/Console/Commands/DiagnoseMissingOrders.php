<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiagnoseMissingOrders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'diagnose:missing-orders {order_id?} {--search-all} {--fix-payment-requests}';

    /**
     * The console command description.
     */
    protected $description = 'Diagnose and fix missing orders in notification system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 تشخيص الطلبات المفقودة في نظام الإشعارات');
        $this->line('================================================');
        
        $orderId = $this->argument('order_id');
        
        if ($orderId) {
            $this->diagnoseSingleOrder($orderId);
        } else {
            $this->diagnoseSystemWide();
        }
        
        if ($this->option('fix-payment-requests')) {
            $this->fixPaymentRequests();
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Diagnose a single order
     */
    protected function diagnoseSingleOrder($orderId)
    {
        $this->info("🔍 تشخيص الطلب: {$orderId}");
        $this->line("-----------------------------");
        
        // 1. البحث المباشر
        $order = Order::find($orderId);
        $this->line("1️⃣ البحث المباشر: " . ($order ? "✅ موجود" : "❌ غير موجود"));
        
        if ($order) {
            $this->displayOrderInfo($order);
            return;
        }
        
        // 2. البحث بـ LIKE
        $orders = Order::where('id', 'LIKE', "%{$orderId}%")->get();
        $this->line("2️⃣ البحث بـ LIKE: {$orders->count()} نتائج");
        
        if ($orders->count() > 0) {
            foreach ($orders as $foundOrder) {
                $this->line("   📋 وُجد طلب: {$foundOrder->id}");
            }
        }
        
        // 3. البحث في payment_requests
        $paymentRequest = PaymentRequest::where('attribute_id', $orderId)->first();
        $this->line("3️⃣ البحث في payment_requests: " . ($paymentRequest ? "✅ موجود" : "❌ غير موجود"));
        
        if ($paymentRequest) {
            $this->line("   📋 order_ids: " . ($paymentRequest->order_ids ?? 'null'));
            
            if ($paymentRequest->order_ids) {
                $orderIds = json_decode($paymentRequest->order_ids, true);
                if (!is_array($orderIds)) {
                    $orderIds = explode(',', $paymentRequest->order_ids);
                }
                
                foreach ($orderIds as $realOrderId) {
                    $realOrderId = trim($realOrderId);
                    $realOrder = Order::find($realOrderId);
                    $this->line("   📋 التحقق من الطلب الحقيقي {$realOrderId}: " . ($realOrder ? "✅ موجود" : "❌ غير موجود"));
                    
                    if ($realOrder) {
                        $this->displayOrderInfo($realOrder);
                    }
                }
            }
        }
        
        // 4. البحث في جداول أخرى
        $this->searchInOtherTables($orderId);
    }
    
    /**
     * Diagnose system-wide issues
     */
    protected function diagnoseSystemWide()
    {
        $this->info("🔍 تشخيص شامل للنظام");
        $this->line("======================");
        
        // إحصائيات عامة
        $totalOrders = Order::count();
        $totalPaymentRequests = PaymentRequest::count();
        
        $this->line("📊 إحصائيات عامة:");
        $this->line("   • إجمالي الطلبات: {$totalOrders}");
        $this->line("   • إجمالي payment_requests: {$totalPaymentRequests}");
        
        // آخر 10 طلبات
        $this->line("\n📋 آخر 10 طلبات:");
        $recentOrders = Order::latest()->take(10)->get(['id', 'created_at', 'order_status']);
        foreach ($recentOrders as $order) {
            $this->line("   • طلب {$order->id} - {$order->order_status} - {$order->created_at}");
        }
        
        // البحث عن payment_requests بدون طلبات صحيحة
        $this->line("\n🔍 البحث عن payment_requests مع طلبات مفقودة:");
        $problematicRequests = PaymentRequest::whereNotNull('order_ids')
            ->where('order_ids', '!=', '')
            ->take(20)
            ->get();
            
        $issuesFound = 0;
        foreach ($problematicRequests as $request) {
            $orderIds = json_decode($request->order_ids, true);
            if (!is_array($orderIds)) {
                $orderIds = explode(',', $request->order_ids);
            }
            
            foreach ($orderIds as $orderId) {
                $orderId = trim($orderId);
                if (!Order::find($orderId)) {
                    $this->warn("   ⚠️ payment_request {$request->id} يشير لطلب مفقود: {$orderId}");
                    $issuesFound++;
                }
            }
        }
        
        if ($issuesFound == 0) {
            $this->info("✅ لم يتم العثور على مشاكل في الـ payment_requests المفحوصة");
        } else {
            $this->warn("⚠️ تم العثور على {$issuesFound} مشكلة");
        }
    }
    
    /**
     * Display order information
     */
    protected function displayOrderInfo($order)
    {
        $this->line("   📋 معلومات الطلب:");
        $this->line("      • الرقم: {$order->id}");
        $this->line("      • الحالة: {$order->order_status}");
        $this->line("      • المبلغ: {$order->order_amount} {$order->currency_code}");
        $this->line("      • العميل: {$order->customer_id}");
        $this->line("      • تاريخ الإنشاء: {$order->created_at}");
    }
    
    /**
     * Search in other tables
     */
    protected function searchInOtherTables($orderId)
    {
        $this->line("\n4️⃣ البحث في جداول أخرى:");
        
        try {
            // البحث في order_details
            $orderDetails = DB::table('order_details')->where('order_id', $orderId)->count();
            $this->line("   📋 order_details: {$orderDetails} سجل");
            
            // البحث في order_transactions (إذا وجد)
            if (DB::getSchemaBuilder()->hasTable('order_transactions')) {
                $transactions = DB::table('order_transactions')->where('order_id', $orderId)->count();
                $this->line("   📋 order_transactions: {$transactions} سجل");
            }
            
        } catch (\Exception $e) {
            $this->warn("   ⚠️ خطأ في البحث في الجداول الأخرى: " . $e->getMessage());
        }
    }
    
    /**
     * Fix payment requests with wrong order IDs
     */
    protected function fixPaymentRequests()
    {
        $this->info("\n🔧 إصلاح payment_requests");
        $this->line("==========================");
        
        if (!$this->confirm('هل تريد فعلاً إصلاح payment_requests التي تحتوي على order_ids خاطئة؟')) {
            $this->info('تم إلغاء العملية');
            return;
        }
        
        $fixed = 0;
        $paymentRequests = PaymentRequest::whereNotNull('order_ids')
            ->where('order_ids', '!=', '')
            ->get();
            
        foreach ($paymentRequests as $request) {
            $orderIds = json_decode($request->order_ids, true);
            if (!is_array($orderIds)) {
                $orderIds = explode(',', $request->order_ids);
            }
            
            $validOrderIds = [];
            $hasChanges = false;
            
            foreach ($orderIds as $orderId) {
                $orderId = trim($orderId);
                if (Order::find($orderId)) {
                    $validOrderIds[] = $orderId;
                } else {
                    $this->warn("إزالة طلب غير موجود: {$orderId} من payment_request {$request->id}");
                    $hasChanges = true;
                }
            }
            
            if ($hasChanges) {
                if (count($validOrderIds) > 0) {
                    $request->order_ids = json_encode($validOrderIds);
                    $request->save();
                    $this->info("✅ تم إصلاح payment_request {$request->id}");
                } else {
                    $this->warn("⚠️ payment_request {$request->id} لا يحتوي على أي طلبات صحيحة");
                }
                $fixed++;
            }
        }
        
        $this->info("✅ تم إصلاح {$fixed} سجل");
    }
}
