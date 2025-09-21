<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentRequest;
use App\Models\Order;
use App\Jobs\SendOrderNotificationsJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SimulatePaymentProcess extends Command
{
    protected $signature = 'simulate:payment-process {--order-id=} {--real-test}';
    protected $description = 'Simulate complete payment process to test notifications';

    public function handle()
    {
        $this->info("🛒 محاكاة عملية دفع كاملة");
        $this->line("============================");
        
        if ($this->option('real-test')) {
            return $this->testRealPayment();
        }
        
        return $this->simulateFullProcess();
    }
    
    /**
     * اختبار payment_request موجود
     */
    protected function testRealPayment()
    {
        $this->info("🔍 اختبار payment_request موجود:");
        
        // البحث عن آخر payment_request ناجح
        $payment = DB::table('payment_requests')
            ->where('payment_status', 'success')
            ->where('is_paid', 1)
            ->whereNotNull('order_ids')
            ->where('order_ids', '!=', '')
            ->where('order_ids', '!=', 'null')
            ->latest()
            ->first();
        
        if (!$payment) {
            $this->error("❌ لا توجد payment_requests ناجحة للاختبار");
            return Command::FAILURE;
        }
        
        $this->info("✅ وُجد payment_request ناجح:");
        $this->line("   Attribute ID: {$payment->attribute_id}");
        $this->line("   Order IDs: {$payment->order_ids}");
        $this->line("   Status: {$payment->payment_status}");
        $this->line("   Created: {$payment->created_at}");
        
        // اختبار إرسال الإشعارات
        $this->line("\n📱 اختبار إرسال الإشعارات...");
        
        if ($this->confirm("هل تريد إرسال إشعارات لهذا الدفع؟")) {
            $this->info("🚀 إرسال Job للإشعارات...");
            
            SendOrderNotificationsJob::dispatch($payment->attribute_id, [
                'transaction_reference' => 'TEST_' . time(),
                'payment_amount' => 1.00,
                'payment_method' => 'Test Payment',
                'customer_reference' => $payment->id
            ]);
            
            $this->info("✅ تم إرسال Job بنجاح!");
            $this->line("📋 تحقق من اللوجز لمعرفة النتيجة:");
            $this->line("tail -f storage/logs/laravel.log | grep -A 10 'order notifications job'");
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * محاكاة عملية دفع كاملة
     */
    protected function simulateFullProcess()
    {
        $this->info("🎭 محاكاة عملية دفع كاملة");
        
        // 1. العثور على طلب موجود
        $orderId = $this->option('order-id');
        if (!$orderId) {
            $order = Order::latest()->first();
            if (!$order) {
                $this->error("❌ لا توجد طلبات في النظام للاختبار");
                return Command::FAILURE;
            }
            $orderId = $order->id;
        } else {
            $order = Order::find($orderId);
            if (!$order) {
                $this->error("❌ الطلب {$orderId} غير موجود");
                return Command::FAILURE;
            }
        }
        
        $this->info("📦 الطلب المختار للاختبار:");
        $this->line("   Order ID: {$order->id}");
        $this->line("   Customer: {$order->customer_id}");
        $this->line("   Amount: {$order->order_amount}");
        $this->line("   Created: {$order->created_at}");
        
        // 2. إنشاء payment_request جديد
        $attributeId = time(); // رقم فريد للاختبار
        
        $this->line("\n💳 إنشاء payment_request للاختبار...");
        
        $paymentRequest = [
            'id' => Str::uuid(),
            'attribute_id' => $attributeId,
            'order_ids' => json_encode([$order->id]),
            'payment_amount' => $order->order_amount,
            'payment_status' => 'success',
            'is_paid' => 1,
            'payment_method' => 'fatoorah',
            'transaction_reference' => 'TEST_' . $attributeId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        DB::table('payment_requests')->insert($paymentRequest);
        
        $this->info("✅ تم إنشاء payment_request:");
        $this->line("   Payment ID: {$paymentRequest['id']}");
        $this->line("   Attribute ID: {$attributeId}");
        $this->line("   Order IDs: " . $paymentRequest['order_ids']);
        
        // 3. محاكاة callback من MyFatoorah
        $this->line("\n🔄 محاكاة MyFatoorah callback...");
        
        if ($this->confirm("هل تريد إرسال Job للإشعارات (كما يحدث في callback حقيقي)؟")) {
            $this->info("🚀 إرسال SendOrderNotificationsJob...");
            
            // محاكاة ما يحدث في FatoorahController
            SendOrderNotificationsJob::dispatch($attributeId, [
                'transaction_reference' => $paymentRequest['transaction_reference'],
                'payment_amount' => $paymentRequest['payment_amount'],
                'payment_method' => 'MyFatoorah (Test)',
                'customer_reference' => $paymentRequest['id']
            ]);
            
            $this->info("✅ تم إرسال Job بنجاح!");
            $this->line("\n📱 الآن يجب أن تصل الإشعارات:");
            $this->line("   • SMS للإدارة: " . config('sms.taqnyat.admin_phone'));
            $this->line("   • SMS للعميل: (حسب بيانات العميل في الطلب)");
            $this->line("   • WhatsApp للإدارة والعميل (أو SMS fallback)");
            
            $this->line("\n📋 لمراقبة النتائج:");
            $this->line("tail -f storage/logs/laravel.log | grep -A 15 'Starting order notifications job'");
            
            // انتظار قليل ثم عرض النتائج
            $this->line("\n⏳ انتظار 3 ثوان لمعالجة Job...");
            sleep(3);
            
            $this->line("\n📊 فحص آخر سجلات الإشعارات:");
            $this->showRecentNotificationLogs();
        }
        
        // 4. تنظيف البيانات التجريبية
        if ($this->confirm("\nهل تريد حذف payment_request التجريبي؟")) {
            DB::table('payment_requests')->where('id', $paymentRequest['id'])->delete();
            $this->info("🗑️ تم حذف البيانات التجريبية");
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * عرض آخر سجلات الإشعارات
     */
    protected function showRecentNotificationLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            $this->warn("⚠️ ملف اللوجز غير موجود");
            return;
        }
        
        $logContent = file_get_contents($logPath);
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -20); // آخر 20 سطر
        
        $notificationLines = array_filter($recentLines, function($line) {
            return strpos($line, 'order notifications') !== false ||
                   strpos($line, 'Taqnyat SMS Response') !== false ||
                   strpos($line, 'Admin notification sent') !== false ||
                   strpos($line, 'Customer notification sent') !== false;
        });
        
        if (empty($notificationLines)) {
            $this->warn("⚠️ لا توجد سجلات إشعارات حديثة");
        } else {
            foreach ($notificationLines as $line) {
                if (strpos($line, 'successfully') !== false || strpos($line, 'sent') !== false) {
                    $this->info("✅ " . trim($line));
                } else {
                    $this->line("📋 " . trim($line));
                }
            }
        }
    }
}
