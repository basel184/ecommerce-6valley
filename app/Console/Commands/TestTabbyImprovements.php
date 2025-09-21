<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentRequest;
use App\Services\TabbyService;
use Illuminate\Support\Facades\Log;

class TestTabbyImprovements extends Command
{
    protected $signature = 'test:tabby-improvements {payment_id?} {--verbose}';
    protected $description = 'اختبار التحسينات المطبقة على Tabby Payment';

    public function handle()
    {
        $paymentId = $this->argument('payment_id');
        $verbose = $this->option('verbose');

        $this->info('🧪 اختبار التحسينات المطبقة على Tabby Payment');
        $this->newLine();

        if ($paymentId) {
            $this->testSpecificPayment($paymentId, $verbose);
        } else {
            $this->testAllTabbyPayments($verbose);
        }
    }

    private function testSpecificPayment($paymentId, $verbose = false)
    {
        $this->info("🔍 اختبار الدفع المحدد: {$paymentId}");

        $payment = PaymentRequest::where('id', $paymentId)->first();
        if (!$payment) {
            $this->error("❌ الدفع غير موجود: {$paymentId}");
            return;
        }

        if ($payment->payment_method !== 'tabby') {
            $this->error("❌ هذا الدفع ليس من Tabby: {$payment->payment_method}");
            return;
        }

        $this->displayPaymentInfo($payment, $verbose);
        $this->testPaymentFlow($payment, $verbose);
    }

    private function testAllTabbyPayments($verbose = false)
    {
        $this->info('🔍 اختبار جميع دفعات Tabby...');

        $payments = PaymentRequest::where('payment_method', 'tabby')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->get();

        if ($payments->isEmpty()) {
            $this->info('✅ لا توجد دفعات Tabby في الأسبوع الماضي');
            return;
        }

        $this->info("📊 تم العثور على {$payments->count()} دفعة Tabby");

        $successCount = 0;
        $issueCount = 0;

        foreach ($payments as $payment) {
            $this->newLine();
            $this->info("🔍 اختبار الدفع: {$payment->id}");
            
            $issues = $this->identifyPaymentIssues($payment);
            
            if (empty($issues)) {
                $this->info('✅ لا توجد مشاكل');
                $successCount++;
            } else {
                $this->warn('⚠️ تم العثور على مشاكل:');
                foreach ($issues as $issue) {
                    $this->line("   - {$issue}");
                }
                $issueCount++;
            }

            if ($verbose) {
                $this->displayPaymentInfo($payment, true);
            }
        }

        $this->newLine();
        $this->info('📊 ملخص النتائج:');
        $this->table(
            ['الحالة', 'العدد'],
            [
                ['✅ ناجح', $successCount],
                ['⚠️ يحتاج إصلاح', $issueCount],
                ['📊 المجموع', $payments->count()],
            ]
        );
    }

    private function displayPaymentInfo($payment, $verbose = false)
    {
        $this->info('📋 تفاصيل الدفع:');
        $this->table(
            ['الحقل', 'القيمة', 'الحالة'],
            [
                ['Payment ID', $payment->id, $this->getStatusIcon($payment->id)],
                ['المبلغ', $payment->payment_amount, $this->getStatusIcon($payment->payment_amount)],
                ['الحالة', $payment->payment_status ?? 'NULL', $this->getPaymentStatusIcon($payment->payment_status)],
                ['مدفوع', $payment->is_paid ? 'نعم' : 'لا', $this->getStatusIcon($payment->is_paid)],
                ['Transaction Reference', $payment->transaction_reference ?? 'NULL', $this->getStatusIcon($payment->transaction_reference)],
                ['Order IDs', $payment->order_ids ?? 'NULL', $this->getStatusIcon($payment->order_ids)],
                ['Success Hook', $payment->success_hook ?? 'NULL', $this->getStatusIcon($payment->success_hook)],
                ['Created At', $payment->created_at, $this->getStatusIcon($payment->created_at)],
            ]
        );

        if ($verbose) {
            $this->displayVerboseInfo($payment);
        }
    }

    private function testPaymentFlow($payment, $verbose = false)
    {
        $this->info('🔄 اختبار تدفق الدفع:');

        // اختبار 1: التحقق من وجود session_id
        if ($payment->transaction_reference) {
            $this->info('✅ Session ID موجود في قاعدة البيانات');
            
            if ($verbose) {
                $this->line("   Session ID: {$payment->transaction_reference}");
            }

            // اختبار 2: التحقق من حالة الدفع في Tabby
            $this->info('🔍 التحقق من حالة الدفع في Tabby...');
            $tabbyService = app(TabbyService::class);
            $paymentStatus = $tabbyService->getPaymentStatus($payment->transaction_reference);

            if ($paymentStatus['success']) {
                $this->info('✅ تم الاتصال بـ Tabby بنجاح');
                $this->line("   Status: " . ($paymentStatus['status'] ?? 'unknown'));
                
                if (in_array($paymentStatus['status'], ['AUTHORIZED', 'PAID', 'CAPTURED'])) {
                    $this->info('✅ الدفع ناجح في Tabby');
                } else {
                    $this->warn('⚠️ الدفع غير ناجح في Tabby');
                }
            } else {
                $this->warn('⚠️ فشل الاتصال بـ Tabby');
                if ($verbose) {
                    $this->line("   Error: " . ($paymentStatus['error'] ?? 'Unknown error'));
                }
            }
        } else {
            $this->warn('⚠️ Session ID غير موجود في قاعدة البيانات');
        }

        // اختبار 3: التحقق من الطلبات المرتبطة
        if (!empty($payment->order_ids)) {
            $this->info('📦 التحقق من الطلبات المرتبطة...');
            $orderIds = json_decode($payment->order_ids, true);
            if (!is_array($orderIds)) {
                $orderIds = explode(',', $payment->order_ids);
            }

            foreach ($orderIds as $orderId) {
                $order = \App\Models\Order::find($orderId);
                if ($order) {
                    $this->info("✅ الطلب {$orderId}: {$order->order_status} - {$order->payment_status}");
                } else {
                    $this->warn("⚠️ الطلب {$orderId}: غير موجود");
                }
            }
        } else {
            $this->warn('⚠️ لا توجد طلبات مرتبطة');
        }
    }

    private function identifyPaymentIssues($payment)
    {
        $issues = [];

        // التحقق من وجود session_id
        if (empty($payment->transaction_reference)) {
            $issues[] = 'Session ID غير موجود في قاعدة البيانات';
        }

        // التحقق من حالة الدفع
        if ($payment->payment_status !== 'success') {
            $issues[] = 'حالة الدفع ليست ناجحة';
        }

        // التحقق من is_paid
        if (!$payment->is_paid) {
            $issues[] = 'الدفع غير محدد كمدفوع';
        }

        // التحقق من الطلبات المرتبطة
        if (empty($payment->order_ids)) {
            $issues[] = 'لا توجد طلبات مرتبطة';
        }

        return $issues;
    }

    private function displayVerboseInfo($payment)
    {
        $this->info('📝 معلومات مفصلة:');

        // فحص السجلات
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            $paymentLogs = preg_grep("/.*{$payment->id}.*/", explode("\n", $logContent));
            
            if (!empty($paymentLogs)) {
                $this->info('📝 سجلات الدفع (آخر 5):');
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
                $order = \App\Models\Order::find($orderId);
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
        } elseif ($status === 'pending') {
            return '⏳';
        } else {
            return '⚠️';
        }
    }
}