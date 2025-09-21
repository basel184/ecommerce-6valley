<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OrderNotificationService;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DiagnoseNotificationIssue extends Command
{
    protected $signature = 'diagnose:notifications {--order-id=} {--test-latest} {--force-send}';
    protected $description = 'Diagnose notification issues for orders';

    public function handle()
    {
        $this->info("🔍 تشخيص مشاكل الإشعارات");
        $this->line("========================");
        
        $orderId = $this->option('order-id');
        
        // إذا لم يتم تحديد order-id، ابحث عن آخر الطلبات
        if (!$orderId || $this->option('test-latest')) {
            $this->line("📋 آخر الطلبات في النظام:");
            $latestOrders = Order::with('customer')->latest()->take(5)->get();
            
            foreach ($latestOrders as $order) {
                $customerName = $order->customer ? "{$order->customer->f_name} {$order->customer->l_name}" : 'غير محدد';
                $customerPhone = $order->customer ? $order->customer->phone : 'غير محدد';
                
                $this->line("  طلب {$order->id}: {$customerName} ({$customerPhone}) - {$order->created_at}");
            }
            
            if (!$orderId) {
                $orderId = $this->ask('أدخل رقم الطلب المراد اختباره', $latestOrders->first()->id ?? null);
            }
        }
        
        if (!$orderId) {
            $this->error("يرجى تحديد رقم الطلب");
            return Command::FAILURE;
        }
        
        // البحث عن الطلب
        $this->line("\n🔍 البحث عن الطلب: {$orderId}");
        $order = Order::with('customer')->find($orderId);
        
        if (!$order) {
            $this->error("❌ الطلب غير موجود: {$orderId}");
            
            // البحث في payment_requests
            $this->line("\n💳 البحث في payment_requests:");
            $payment = DB::table('payment_requests')->where('attribute_id', $orderId)->first();
            
            if ($payment) {
                $this->info("✅ تم العثور على payment_request:");
                $this->line("   Payment ID: {$payment->id}");
                $this->line("   Order IDs: " . ($payment->order_ids ?? 'NULL'));
                $this->line("   Is Paid: " . ($payment->is_paid ? 'Yes' : 'No'));
                $this->line("   Created: {$payment->created_at}");
                
                if (empty($payment->order_ids)) {
                    $this->error("❌ المشكلة: order_ids فارغ - الطلب لم يتم إنشاؤه بعد الدفع!");
                }
            } else {
                $this->error("❌ لم يتم العثور على payment_request أيضاً");
            }
            
            return Command::FAILURE;
        }
        
        // عرض تفاصيل الطلب
        $this->info("✅ تم العثور على الطلب:");
        $this->line("   رقم الطلب: {$order->id}");
        $this->line("   العميل: {$order->customer->f_name} {$order->customer->l_name}");
        $this->line("   الهاتف: {$order->customer->phone}");
        $this->line("   المبلغ: {$order->order_amount} {$order->currency_code}");
        $this->line("   التاريخ: {$order->created_at}");
        
        // التحقق من صحة رقم الهاتف
        $this->line("\n📱 فحص رقم الهاتف:");
        $customerPhone = $order->customer->phone;
        
        if (empty($customerPhone)) {
            $this->error("❌ رقم الهاتف فارغ!");
            return Command::FAILURE;
        }
        
        $this->info("✅ رقم الهاتف: {$customerPhone}");
        
        // فحص تنسيق الرقم
        $notificationService = app(OrderNotificationService::class);
        $formattedPhone = $this->formatPhoneNumber($customerPhone);
        $this->line("   مُنسق: {$formattedPhone}");
        
        // التحقق من إعدادات الإشعارات
        $this->line("\n⚙️ إعدادات الإشعارات:");
        $smsEnabled = config('sms.taqnyat.sms_enabled', true);
        $whatsappEnabled = config('sms.taqnyat.whatsapp_enabled', true);
        
        $this->line("   SMS: " . ($smsEnabled ? '✅ مُفعل' : '❌ معطل'));
        $this->line("   WhatsApp: " . ($whatsappEnabled ? '✅ مُفعل' : '❌ معطل'));
        
        // اختبار الإشعارات إذا طُلب ذلك
        if ($this->option('force-send') || $this->confirm('هل تريد اختبار إرسال الإشعارات لهذا الطلب؟')) {
            $this->line("\n📤 إرسال الإشعارات...");
            
            $result = $notificationService->sendOrderNotifications($orderId, [
                'payment_method' => 'Manual Test',
                'transaction_reference' => 'TEST_' . time()
            ]);
            
            if ($result) {
                $this->info("✅ تم إرسال الإشعارات بنجاح!");
            } else {
                $this->error("❌ فشل في إرسال الإشعارات");
                $this->line("📋 تحقق من ملف اللوجز للتفاصيل:");
                $this->line("tail -f storage/logs/laravel.log");
            }
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * تنسيق رقم الهاتف (نسخ من OrderNotificationService)
     */
    protected function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (substr($phone, 0, 2) === '05') {
            $phone = '9665' . substr($phone, 2);
        } elseif (substr($phone, 0, 1) === '5' && strlen($phone) === 8) {
            $phone = '9665' . $phone;
        } elseif (substr($phone, 0, 3) !== '966') {
            $phone = '966' . $phone;
        }
        
        return $phone;
    }
}
