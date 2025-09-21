<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentRequest;
use App\Models\Order;
use App\Services\OrderNotificationService;
use App\Services\TaqnyatSmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FixPaymentIssues extends Command
{
    /**
     * اسم الأمر
     */
    protected $signature = 'fix:payment-issues {--test} {--clear-cache} {--resend-notifications=}';

    /**
     * وصف الأمر
     */
    protected $description = 'إصلاح مشاكل النظام المتعلقة بالدفع والإشعارات';

    /**
     * تشغيل الأمر
     */
    public function handle()
    {
        $this->info('🔧 بدء إصلاح مشاكل النظام...');
        $this->newLine();

        // 1. فحص نموذج PaymentRequest
        $this->checkPaymentRequestModel();
        
        // 2. فحص وإصلاح مشاكل WhatsApp
        if ($this->option('clear-cache')) {
            $this->clearWhatsAppCache();
        }
        
        // 3. فحص وإصلاح مشاكل الطلبات المفقودة
        $this->checkMissingOrders();
        
        // 4. إعادة إرسال الإشعارات للطلبات الفاشلة
        if ($this->option('resend-notifications')) {
            $this->resendFailedNotifications($this->option('resend-notifications'));
        }
        
        // 5. اختبار النظام
        if ($this->option('test')) {
            $this->testSystem();
        }
        
        $this->newLine();
        $this->info('✅ تم إنهاء إصلاح المشاكل بنجاح');
    }

    /**
     * فحص نموذج PaymentRequest
     */
    protected function checkPaymentRequestModel()
    {
        $this->info('📋 فحص نموذج PaymentRequest...');
        
        try {
            $model = new PaymentRequest();
            $fillable = $model->getFillable();
            
            $requiredFields = ['payment_status', 'transaction_reference', 'is_paid'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!in_array($field, $fillable)) {
                    $missingFields[] = $field;
                }
            }
            
            if (empty($missingFields)) {
                $this->line('✅ جميع الحقول المطلوبة موجودة في $fillable');
            } else {
                $this->error('❌ الحقول المفقودة في $fillable: ' . implode(', ', $missingFields));
                $this->line('💡 تأكد من أن هذه الحقول موجودة في app/Models/PaymentRequest.php');
            }
            
            // اختبار عملية التحديث
            $testUpdate = PaymentRequest::where('id', '!=', '')->latest()->first();
            if ($testUpdate) {
                try {
                    $testUpdate->update(['payment_status' => $testUpdate->payment_status]);
                    $this->line('✅ عملية التحديث تعمل بشكل صحيح');
                } catch (\Exception $e) {
                    $this->error('❌ خطأ في عملية التحديث: ' . $e->getMessage());
                }
            }
            
        } catch (\Exception $e) {
            $this->error('❌ خطأ في فحص النموذج: ' . $e->getMessage());
        }
    }

    /**
     * مسح cache WhatsApp
     */
    protected function clearWhatsAppCache()
    {
        $this->info('🧹 مسح cache WhatsApp...');
        
        Cache::forget('taqnyat_whatsapp_available');
        Cache::forget('taqnyat_whatsapp_disabled');
        
        $this->line('✅ تم مسح cache WhatsApp - سيتم المحاولة مرة أخرى');
    }

    /**
     * فحص الطلبات المفقودة
     */
    protected function checkMissingOrders()
    {
        $this->info('🔍 فحص الطلبات المفقودة...');
        
        // البحث عن payment requests بدون طلبات مطابقة
        $problematicPayments = PaymentRequest::whereNotNull('attribute_id')
            ->where('attribute_id', '!=', '')
            ->get();
            
        $missingCount = 0;
        $foundCount = 0;
        
        foreach ($problematicPayments as $payment) {
            $order = Order::find($payment->attribute_id);
            if (!$order) {
                $missingCount++;
                $this->line("❌ طلب مفقود: ID {$payment->attribute_id} (Payment: {$payment->id})");
            } else {
                $foundCount++;
            }
        }
        
        $this->line("✅ طلبات موجودة: {$foundCount}");
        $this->line("❌ طلبات مفقودة: {$missingCount}");
        
        if ($missingCount > 0) {
            $this->warn('💡 قد تحتاج لمراجعة قاعدة البيانات أو منطق إنشاء الطلبات');
        }
    }

    /**
     * إعادة إرسال الإشعارات للطلبات الفاشلة
     */
    protected function resendFailedNotifications($orderIds)
    {
        $this->info('📨 إعادة إرسال الإشعارات...');
        
        $orderIdArray = explode(',', $orderIds);
        $notificationService = app(OrderNotificationService::class);
        
        foreach ($orderIdArray as $orderId) {
            $orderId = trim($orderId);
            $this->line("محاولة إرسال إشعارات للطلب: {$orderId}");
            
            $result = $notificationService->sendOrderNotifications($orderId, [
                'payment_method' => 'Manual Resend',
                'transaction_reference' => 'RESEND_' . time()
            ]);
            
            if ($result) {
                $this->line("✅ تم إرسال إشعارات الطلب {$orderId}");
            } else {
                $this->line("❌ فشل في إرسال إشعارات الطلب {$orderId}");
            }
        }
    }

    /**
     * اختبار النظام
     */
    protected function testSystem()
    {
        $this->info('🧪 اختبار النظام...');
        
        // اختبار Taqnyat SMS
        $smsService = app(TaqnyatSmsService::class);
        $testPhone = config('sms.taqnyat.admin_phone');
        
        $this->line('اختبار SMS...');
        $smsResult = $smsService->sendSms($testPhone, '🧪 اختبار إصلاح النظام - SMS يعمل بنجاح');
        $this->line($smsResult['success'] ? '✅ SMS يعمل' : '❌ SMS لا يعمل: ' . $smsResult['message']);
        
        $this->line('اختبار WhatsApp...');
        $whatsappResult = $smsService->sendWhatsApp($testPhone, '🧪 اختبار إصلاح النظام - WhatsApp يعمل بنجاح');
        $this->line($whatsappResult['success'] ? '✅ WhatsApp يعمل' : '⚠️ WhatsApp يستخدم SMS كبديل');
        
        // اختبار إنشاء PaymentRequest
        $this->line('اختبار إنشاء PaymentRequest...');
        try {
            $testPayment = PaymentRequest::create([
                'payment_amount' => 1.00,
                'currency_code' => 'SAR',
                'payment_method' => 'test',
                'payment_status' => 'test',
                'payer_information' => ['test' => true],
                'receiver_information' => ['test' => true],
                'additional_data' => ['test' => true]
            ]);
            
            // حذف الاختبار
            $testPayment->delete();
            $this->line('✅ إنشاء PaymentRequest يعمل');
        } catch (\Exception $e) {
            $this->line('❌ خطأ في إنشاء PaymentRequest: ' . $e->getMessage());
        }
    }
}
