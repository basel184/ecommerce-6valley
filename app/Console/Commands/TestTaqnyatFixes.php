<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TaqnyatSmsService;
use App\Services\OrderNotificationService;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;

class TestTaqnyatFixes extends Command
{
    /**
     * اسم الأمر
     */
    protected $signature = 'test:taqnyat-fixes {--clear-cache} {--test-order=} {--notifications}';

    /**
     * وصف الأمر
     */
    protected $description = 'اختبار الإصلاحات الجديدة لنظام Taqnyat';

    /**
     * تشغيل الأمر
     */
    public function handle()
    {
        $this->info('🔧 اختبار الإصلاحات الجديدة لنظام Taqnyat');
        $this->newLine();

        // مسح الكاش إذا طُلب ذلك
        if ($this->option('clear-cache')) {
            Cache::forget('taqnyat_whatsapp_available');
            $this->info('✅ تم مسح كاش WhatsApp');
        }

        // عرض حالة الكاش
        $this->checkCacheStatus();
        $this->newLine();

        // اختبار البحث عن الطلبات
        $this->testOrderSearch();
        $this->newLine();

        // اختبار الإشعارات إذا طُلب ذلك
        if ($this->option('notifications')) {
            $this->testNotifications();
            $this->newLine();
        }

        // اختبار WhatsApp المحسن
        $this->testImprovedWhatsApp();
    }

    /**
     * فحص حالة الكاش
     */
    protected function checkCacheStatus()
    {
        $this->info('📋 حالة الكاش:');
        
        $whatsappAvailable = Cache::get('taqnyat_whatsapp_available');
        
        if ($whatsappAvailable === null) {
            $this->line('• WhatsApp Status: غير محدد (سيتم المحاولة)');
        } elseif ($whatsappAvailable === false) {
            $this->line('• WhatsApp Status: ❌ غير متاح (محفوظ في الكاش)');
        } else {
            $this->line('• WhatsApp Status: ✅ متاح');
        }
    }

    /**
     * اختبار البحث عن الطلبات
     */
    protected function testOrderSearch()
    {
        $this->info('🔍 اختبار البحث عن الطلبات:');
        
        $testOrderId = $this->option('test-order');
        
        if (!$testOrderId) {
            // البحث عن أحدث طلب
            $order = Order::latest()->first();
            if (!$order) {
                $this->error('❌ لم يتم العثور على أي طلبات للاختبار');
                return;
            }
            $testOrderId = $order->id;
        }
        
        $this->line("اختبار البحث عن الطلب: {$testOrderId}");
        
        $notificationService = app(OrderNotificationService::class);
        
        // محاولة البحث
        $found = $notificationService->sendOrderNotifications($testOrderId, [
            'payment_method' => 'Test Search',
            'transaction_reference' => 'TEST_SEARCH_' . time()
        ]);
        
        if ($found) {
            $this->info('✅ تم العثور على الطلب وإرسال الإشعارات');
        } else {
            $this->error('❌ لم يتم العثور على الطلب');
        }
    }

    /**
     * اختبار الإشعارات
     */
    protected function testNotifications()
    {
        $this->info('📨 اختبار إشعارات النظام الجديد:');
        
        $testOrderId = $this->option('test-order');
        
        if (!$testOrderId) {
            // البحث عن أحدث طلب
            $order = Order::latest()->first();
            if (!$order) {
                $this->error('❌ لم يتم العثور على أي طلبات للاختبار');
                return;
            }
            $testOrderId = $order->id;
        }
        
        $this->line("اختبار إشعارات الطلب: {$testOrderId}");
        
        if ($this->confirm('هل تريد اختبار إرسال الإشعارات؟')) {
            $notificationService = app(OrderNotificationService::class);
            
            $result = $notificationService->sendOrderNotifications($testOrderId, [
                'payment_method' => 'WhatsApp Template Test',
                'transaction_reference' => 'TEMPLATE_TEST_' . time()
            ]);
            
            if ($result) {
                $this->info('✅ تم إرسال الإشعارات بنجاح (template + SMS)');
                $this->info('📋 تم استخدام template messages حسب التوثيق الجديد');
            } else {
                $this->error('❌ فشل في إرسال الإشعارات');
            }
        }
    }

    /**
     * اختبار WhatsApp المحسن
     */
    protected function testImprovedWhatsApp()
    {
        $this->info('📱 اختبار WhatsApp المحسن:');
        
        $phone = config('sms.taqnyat.admin_phone');
        $message = "🧪 اختبار WhatsApp المحسن\nالوقت: " . now()->format('Y-m-d H:i:s');
        
        $this->line("الرقم: {$phone}");
        $this->line("الرسالة: {$message}");
        
        if ($this->confirm('هل تريد اختبار WhatsApp المحسن؟')) {
            $smsService = app(TaqnyatSmsService::class);
            
            $result = $smsService->sendWhatsApp($phone, $message);
            
            if ($result['success']) {
                $this->info('✅ تم إرسال الرسالة بنجاح');
            } else {
                $this->error('❌ فشل في إرسال الرسالة: ' . $result['message']);
            }
            
            // عرض حالة الكاش بعد المحاولة
            $this->newLine();
            $this->checkCacheStatus();
        }
    }
}
