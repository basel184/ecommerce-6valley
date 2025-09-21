<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentRequest;
use App\Models\Order;
use App\Services\TabbyService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class DiagnoseTabbyIssues extends Command
{
    protected $signature = 'diagnose:tabby-issues {--payment-id=} {--fix} {--detailed}';
    protected $description = 'Diagnose and fix Tabby payment issues comprehensively';

    public function handle()
    {
        $paymentId = $this->option('payment-id');
        $fix = $this->option('fix');
        $detailed = $this->option('detailed');

        $this->info('🔍 بدء تشخيص مشاكل Tabby...');
        $this->newLine();

        // 1. فحص إعدادات Tabby
        $this->checkTabbyConfiguration();

        // 2. فحص قاعدة البيانات
        $this->checkDatabaseIssues();

        // 3. فحص السجلات
        $this->checkLogIssues();

        // 4. فحص دفع محدد إذا تم تحديده
        if ($paymentId) {
            $this->diagnoseSpecificPayment($paymentId, $fix, $detailed);
        } else {
            // 5. فحص جميع المدفوعات الحديثة
            $this->diagnoseRecentPayments($fix, $detailed);
        }

        // 6. فحص الروابط والمسارات
        $this->checkRoutesAndLinks();

        // 7. فحص الإشعارات
        $this->checkNotificationIssues();

        $this->newLine();
        $this->info('✅ تم إنهاء التشخيص');
        
        if ($fix) {
            $this->info('🔧 تم تطبيق الإصلاحات المطلوبة');
        }
    }

    private function checkTabbyConfiguration()
    {
        $this->info('📋 فحص إعدادات Tabby...');

        $config = [
            'api_key' => config('tabby.api_key'),
            'mode' => config('tabby.mode'),
            'test_api_url' => config('tabby.test_api_url'),
            'live_api_url' => config('tabby.live_api_url'),
            'merchant_code' => config('tabby.merchant_code'),
            'currency' => config('tabby.currency'),
        ];

        $issues = [];
        $warnings = [];

        // فحص API Key
        if (empty($config['api_key'])) {
            $issues[] = 'API Key غير محدد';
        } elseif (substr($config['api_key'], 0, 3) !== 'sk_') {
            $warnings[] = 'API Key قد لا يكون صحيحاً (يجب أن يبدأ بـ sk_)';
        }

        // فحص Mode
        if (!in_array($config['mode'], ['test', 'live'])) {
            $issues[] = 'Mode غير صحيح (يجب أن يكون test أو live)';
        }

        // فحص URLs
        if (empty($config['test_api_url']) || empty($config['live_api_url'])) {
            $issues[] = 'API URLs غير محددة';
        }

        // فحص Merchant Code
        if (empty($config['merchant_code'])) {
            $warnings[] = 'Merchant Code غير محدد';
        }

        // عرض النتائج
        if (empty($issues) && empty($warnings)) {
            $this->info('✅ إعدادات Tabby صحيحة');
        } else {
            if (!empty($issues)) {
                $this->error('❌ مشاكل في الإعدادات:');
                foreach ($issues as $issue) {
                    $this->error("   - {$issue}");
                }
            }
            
            if (!empty($warnings)) {
                $this->warn('⚠️ تحذيرات:');
                foreach ($warnings as $warning) {
                    $this->warn("   - {$warning}");
                }
            }
        }

        $this->newLine();
    }

    private function checkDatabaseIssues()
    {
        $this->info('🗄️ فحص قاعدة البيانات...');

        // فحص جدول payment_requests
        $paymentRequestsCount = PaymentRequest::where('payment_method', 'tabby')->count();
        $this->info("📊 إجمالي مدفوعات Tabby: {$paymentRequestsCount}");

        // فحص المدفوعات المفقودة
        $missingPayments = PaymentRequest::where('payment_method', 'tabby')
            ->where(function($query) {
                $query->whereNull('payment_status')
                      ->orWhere('payment_status', '!=', 'success')
                      ->orWhere('is_paid', 0);
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($missingPayments > 0) {
            $this->warn("⚠️ {$missingPayments} دفعة تحتاج معالجة");
        } else {
            $this->info('✅ لا توجد مدفوعات مفقودة');
        }

        // فحص الطلبات بدون order_ids
        $paymentsWithoutOrders = PaymentRequest::where('payment_method', 'tabby')
            ->whereNull('order_ids')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($paymentsWithoutOrders > 0) {
            $this->warn("⚠️ {$paymentsWithoutOrders} دفعة بدون طلبات مرتبطة");
        }

        $this->newLine();
    }

    private function checkLogIssues()
    {
        $this->info('📝 فحص السجلات...');

        $logFile = storage_path('logs/laravel.log');
        
        if (!file_exists($logFile)) {
            $this->warn('⚠️ ملف السجل غير موجود');
            return;
        }

        $logContent = file_get_contents($logFile);
        
        // البحث عن أخطاء Tabby
        $tabbyErrors = substr_count($logContent, 'Tabby.*ERROR');
        $tabbyWarnings = substr_count($logContent, 'Tabby.*WARNING');
        $tabbyCallbacks = substr_count($logContent, 'Tabby Callback');

        $this->info("📊 إحصائيات السجلات:");
        $this->line("   - أخطاء Tabby: {$tabbyErrors}");
        $this->line("   - تحذيرات Tabby: {$tabbyWarnings}");
        $this->line("   - استدعاءات Callback: {$tabbyCallbacks}");

        if ($tabbyErrors > 0) {
            $this->warn("⚠️ يوجد {$tabbyErrors} خطأ في Tabby");
        }

        $this->newLine();
    }

    private function diagnoseSpecificPayment($paymentId, $fix = false, $detailed = false)
    {
        $this->info("🔍 تشخيص دفع محدد: {$paymentId}");

        $payment = PaymentRequest::where('id', $paymentId)->first();
        
        if (!$payment) {
            $this->error("❌ الدفع غير موجود: {$paymentId}");
            return;
        }

        if ($payment->payment_method !== 'tabby') {
            $this->error("❌ هذا الدفع ليس من Tabby: {$payment->payment_method}");
            return;
        }

        $this->info("📋 تفاصيل الدفع:");
        $this->table(
            ['الحقل', 'القيمة', 'الحالة'],
            [
                ['Payment ID', $payment->id, $this->getStatusIcon($payment->id)],
                ['المبلغ', $payment->payment_amount, $this->getStatusIcon($payment->payment_amount)],
                ['الحالة', $payment->payment_status ?? 'NULL', $this->getPaymentStatusIcon($payment->payment_status)],
                ['مدفوع', $payment->is_paid ? 'نعم' : 'لا', $this->getStatusIcon($payment->is_paid)],
                ['Transaction Reference', $payment->transaction_reference ?? 'NULL', $this->getStatusIcon($payment->transaction_reference)],
                ['Order IDs', $payment->order_ids ?? 'NULL', $this->getStatusIcon($payment->order_ids)],
                ['Attribute ID', $payment->attribute_id ?? 'NULL', $this->getStatusIcon($payment->attribute_id)],
                ['Success Hook', $payment->success_hook ?? 'NULL', $this->getStatusIcon($payment->success_hook)],
                ['Created At', $payment->created_at, $this->getStatusIcon($payment->created_at)],
            ]
        );

        // فحص المشاكل
        $issues = $this->identifyPaymentIssues($payment);
        
        if (empty($issues)) {
            $this->info('✅ لا توجد مشاكل في هذا الدفع');
        } else {
            $this->warn('⚠️ المشاكل المكتشفة:');
            foreach ($issues as $issue) {
                $this->warn("   - {$issue}");
            }

            if ($fix) {
                $this->fixPaymentIssues($payment, $issues);
            }
        }

        if ($detailed) {
            $this->showVerboseInfo($payment);
        }

        $this->newLine();
    }

    private function diagnoseRecentPayments($fix = false, $detailed = false)
    {
        $this->info('📊 تشخيص المدفوعات الحديثة...');

        $recentPayments = PaymentRequest::where('payment_method', 'tabby')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($recentPayments->isEmpty()) {
            $this->info('✅ لا توجد مدفوعات حديثة');
            return;
        }

        $this->info("📋 آخر {$recentPayments->count()} مدفوعات:");

        foreach ($recentPayments as $payment) {
            $status = $payment->payment_status === 'success' ? '✅' : '❌';
            $this->line("{$status} {$payment->id} - {$payment->payment_amount} - {$payment->payment_status} - {$payment->created_at}");
            
            if ($detailed) {
                $issues = $this->identifyPaymentIssues($payment);
                if (!empty($issues)) {
                    foreach ($issues as $issue) {
                        $this->line("   ⚠️ {$issue}");
                    }
                }
            }
        }

        $this->newLine();
    }

    private function checkRoutesAndLinks()
    {
        $this->info('🔗 فحص الروابط والمسارات...');

        $routes = [
            'tabby.pay' => route('tabby.pay'),
            'tabby.callback' => route('tabby.callback'),
            'tabby.webhook' => route('tabby.webhook'),
        ];

        foreach ($routes as $name => $url) {
            $this->line("   {$name}: {$url}");
        }

        $this->newLine();
    }

    private function checkNotificationIssues()
    {
        $this->info('📱 فحص الإشعارات...');

        // فحص إعدادات SMS
        $smsEnabled = config('sms.taqnyat.sms_enabled', false);
        $adminPhone = config('sms.taqnyat.admin_phone');

        $this->line("   SMS مفعل: " . ($smsEnabled ? 'نعم' : 'لا'));
        $this->line("   رقم الإدارة: {$adminPhone}");

        if (!$smsEnabled) {
            $this->warn('⚠️ SMS غير مفعل');
        }

        if (empty($adminPhone)) {
            $this->warn('⚠️ رقم الإدارة غير محدد');
        }

        $this->newLine();
    }

    private function identifyPaymentIssues($payment)
    {
        $issues = [];

        // فحص حالة الدفع
        if (empty($payment->payment_status) || $payment->payment_status !== 'success') {
            $issues[] = 'حالة الدفع غير ناجحة';
        }

        // فحص is_paid
        if (!$payment->is_paid) {
            $issues[] = 'الدفع غير محدد كمدفوع';
        }

        // فحص transaction_reference
        if (empty($payment->transaction_reference)) {
            $issues[] = 'مرجع المعاملة مفقود';
        }

        // فحص order_ids
        if (empty($payment->order_ids)) {
            $issues[] = 'معرفات الطلبات مفقودة';
        }

        // فحص success_hook
        if (empty($payment->success_hook)) {
            $issues[] = 'دالة النجاح مفقودة';
        } elseif (!function_exists($payment->success_hook)) {
            $issues[] = 'دالة النجاح غير موجودة';
        }

        return $issues;
    }

    private function fixPaymentIssues($payment, $issues)
    {
        $this->info('🔧 إصلاح مشاكل الدفع...');

        try {
            DB::beginTransaction();

            foreach ($issues as $issue) {
                switch ($issue) {
                    case 'حالة الدفع غير ناجحة':
                        $payment->payment_status = 'success';
                        $this->info('✅ تم تحديث حالة الدفع');
                        break;

                    case 'الدفع غير محدد كمدفوع':
                        $payment->is_paid = 1;
                        $this->info('✅ تم تحديث حالة الدفع كمدفوع');
                        break;

                    case 'مرجع المعاملة مفقود':
                        $payment->transaction_reference = 'MANUAL_FIX_' . time();
                        $this->info('✅ تم إضافة مرجع المعاملة');
                        break;
                }
            }

            $payment->save();
            DB::commit();

            $this->info('🎉 تم إصلاح المشاكل بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ خطأ في الإصلاح: " . $e->getMessage());
        }
    }

    private function showVerboseInfo($payment)
    {
        $this->info('📋 معلومات مفصلة:');

        // فحص السجلات لهذا الدفع
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            $paymentLogs = preg_grep("/.*{$payment->id}.*/", explode("\n", $logContent));
            
            if (!empty($paymentLogs)) {
                $this->info('📝 سجلات الدفع:');
                foreach (array_slice($paymentLogs, -5) as $log) {
                    $this->line("   {$log}");
                }
            }
        }

        // فحص الطلبات المرتبطة
        if (!empty($payment->order_ids)) {
            $orderIds = json_decode($payment->order_ids, true);
            if (!is_array($orderIds)) {
                $orderIds = explode(',', $payment->order_ids);
            }

            $this->info('📦 الطلبات المرتبطة:');
            foreach ($orderIds as $orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    $this->line("   ✅ {$orderId}: {$order->order_status} - {$order->payment_status}");
                } else {
                    $this->line("   ❌ {$orderId}: غير موجود");
                }
            }
        }
    }

    private function getStatusIcon($value)
    {
        if (empty($value)) {
            return '❌';
        }
        return '✅';
    }

    private function getPaymentStatusIcon($status)
    {
        if ($status === 'success') {
            return '✅';
        } elseif ($status === 'failed') {
            return '❌';
        } else {
            return '⚠️';
        }
    }
}