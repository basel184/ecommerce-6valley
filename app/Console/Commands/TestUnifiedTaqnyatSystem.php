<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TaqnyatSmsService;
use App\Services\OrderNotificationService;
use App\Models\Order;

class TestUnifiedTaqnyatSystem extends Command
{
    /**
     * اسم الأمر
     */
    protected $signature = 'test:taqnyat-unified {phone?} {--order=} {--mode=all}';

    /**
     * وصف الأمر
     */
    protected $description = 'اختبار نظام Taqnyat الموحد للإشعارات';

    /**
     * تشغيل الأمر
     */
    public function handle()
    {
        $this->info('🧪 اختبار نظام Taqnyat الموحد');
        $this->newLine();

        // التحقق من الإعدادات
        $this->checkConfiguration();
        
        $mode = $this->option('mode');
        $phone = $this->argument('phone') ?: config('sms.taqnyat.admin_phone');
        
        switch ($mode) {
            case 'sms':
                $this->testSMS($phone);
                break;
            case 'whatsapp':
                $this->testWhatsApp($phone);
                break;
            case 'order':
                $this->testOrderNotifications();
                break;
            default:
                $this->testAll($phone);
                break;
        }
    }

    /**
     * التحقق من الإعدادات
     */
    protected function checkConfiguration()
    {
        $this->info('📋 التحقق من الإعدادات:');
        
        $apiKey = config('sms.taqnyat.api_key');
        $sender = config('sms.taqnyat.sender');
        $adminPhone = config('sms.taqnyat.admin_phone');
        $smsEnabled = config('sms.taqnyat.sms_enabled');
        $whatsappEnabled = config('sms.taqnyat.whatsapp_enabled');
        
        $this->line("• Taqnyat API Key: " . ($apiKey ? '✅ مُعد' : '❌ غير مُعد'));
        $this->line("• المُرسل: " . ($sender ?: 'غير محدد'));
        $this->line("• رقم الإدارة: " . ($adminPhone ?: 'غير محدد'));
        $this->line("• SMS مُفعل: " . ($smsEnabled ? '✅' : '❌'));
        $this->line("• WhatsApp مُفعل: " . ($whatsappEnabled ? '✅' : '❌'));
        
        $this->newLine();
        
        if (!$apiKey) {
            $this->error('❌ مفتاح Taqnyat API غير مُعد في ملف .env');
            return;
        }
    }

    /**
     * اختبار جميع الوظائف
     */
    protected function testAll($phone)
    {
        $this->testSMS($phone);
        $this->newLine();
        $this->testWhatsApp($phone);
        $this->newLine();
        $this->testOrderNotifications();
    }

    /**
     * اختبار SMS
     */
    protected function testSMS($phone)
    {
        $this->info('📱 اختبار SMS عبر Taqnyat:');
        
        $smsService = app(TaqnyatSmsService::class);
        $message = "🧪 رسالة اختبار من BERN\nالوقت: " . now()->format('Y-m-d H:i:s') . "\nنظام Taqnyat الموحد يعمل بنجاح! ✅";
        
        $this->line("الرقم: {$phone}");
        $this->line("الرسالة: {$message}");
        
        if ($this->confirm('هل تريد إرسال رسالة SMS؟')) {
            $result = $smsService->sendSms($phone, $message);
            
            if ($result['success']) {
                $this->info('✅ تم إرسال SMS بنجاح');
            } else {
                $this->error('❌ فشل في إرسال SMS: ' . $result['message']);
            }
        }
    }

    /**
     * اختبار WhatsApp
     */
    protected function testWhatsApp($phone)
    {
        $this->info('💬 اختبار WhatsApp عبر Taqnyat:');
        
        $smsService = app(TaqnyatSmsService::class);
        $message = "🧪 رسالة اختبار WhatsApp من BERN\nالوقت: " . now()->format('Y-m-d H:i:s') . "\nنظام Taqnyat الموحد يعمل بنجاح! ✅";
        
        $this->line("الرقم: {$phone}");
        $this->line("الرسالة: {$message}");
        
        if ($this->confirm('هل تريد إرسال رسالة WhatsApp؟')) {
            $result = $smsService->sendWhatsApp($phone, $message);
            
            if ($result['success']) {
                $this->info('✅ تم إرسال WhatsApp بنجاح');
            } else {
                $this->error('❌ فشل في إرسال WhatsApp: ' . $result['message']);
            }
        }
    }

    /**
     * اختبار إشعارات الطلبات
     */
    protected function testOrderNotifications()
    {
        $this->info('🛍️ اختبار إشعارات الطلبات:');
        
        $orderId = $this->option('order');
        
        if (!$orderId) {
            // البحث عن آخر طلب
            $order = Order::with('customer')->latest()->first();
            if (!$order) {
                $this->error('❌ لم يتم العثور على أي طلبات');
                return;
            }
            $orderId = $order->id;
        } else {
            $order = Order::with('customer')->find($orderId);
            if (!$order) {
                $this->error("❌ لم يتم العثور على الطلب رقم {$orderId}");
                return;
            }
        }
        
        $this->line("الطلب: {$order->id}");
        $this->line("العميل: {$order->customer->f_name} {$order->customer->l_name}");
        $this->line("المبلغ: {$order->order_amount} {$order->currency_code}");
        
        if ($this->confirm('هل تريد إرسال إشعارات للطلب؟')) {
            $notificationService = app(OrderNotificationService::class);
            
            $paymentData = [
                'payment_method' => 'Test Payment',
                'transaction_reference' => 'TEST_' . time()
            ];
            
            $result = $notificationService->sendOrderNotifications($order->id, $paymentData);
            
            if ($result) {
                $this->info('✅ تم إرسال إشعارات الطلب بنجاح');
            } else {
                $this->error('❌ فشل في إرسال إشعارات الطلب');
            }
        }
    }
}
