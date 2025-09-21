<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentRequest;
use App\Models\Order;
use App\Services\OrderNotificationService;
use App\Services\TabbyService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FixTabbyCallback extends Command
{
    protected $signature = 'fix:tabby-callback {payment_id?} {--force} {--dry-run}';
    protected $description = 'Fix Tabby callback issues and missing payment data';

    public function handle()
    {
        $paymentId = $this->argument('payment_id');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 تشغيل في وضع الاختبار (لا توجد تغييرات فعلية)');
        }

        if ($paymentId) {
            $this->fixSpecificCallback($paymentId, $force, $dryRun);
        } else {
            $this->fixAllCallbackIssues($force, $dryRun);
        }

        return Command::SUCCESS;
    }

    private function fixSpecificCallback($paymentId, $force = false, $dryRun = false)
    {
        $this->info("🔧 إصلاح callback محدد: {$paymentId}");

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
            ['الحقل', 'القيمة'],
            [
                ['Payment ID', $payment->id],
                ['المبلغ', $payment->payment_amount],
                ['الحالة', $payment->payment_status ?? 'NULL'],
                ['مدفوع', $payment->is_paid ? 'نعم' : 'لا'],
                ['Transaction Reference', $payment->transaction_reference ?? 'NULL'],
                ['Order IDs', $payment->order_ids ?? 'NULL'],
                ['Attribute ID', $payment->attribute_id ?? 'NULL'],
                ['Success Hook', $payment->success_hook ?? 'NULL'],
            ]
        );

        // البحث عن session_id في السجلات
        $sessionId = $this->findSessionIdFromLogs($payment->id);
        
        if ($sessionId) {
            $this->info("🔍 تم العثور على Session ID: {$sessionId}");
            
            if (!$dryRun) {
                // التحقق من حالة الدفع في Tabby
                $tabbyService = app(TabbyService::class);
                $paymentStatus = $tabbyService->getPaymentStatus($sessionId);
                
                if ($paymentStatus['success'] && in_array($paymentStatus['status'], ['AUTHORIZED', 'PAID', 'CAPTURED'])) {
                    $this->info("✅ الدفع ناجح في Tabby");
                    $this->processSuccessfulCallback($payment, $sessionId, $dryRun);
                } else {
                    $this->warn("⚠️ الدفع غير ناجح في Tabby");
                    if ($force && $this->confirm('هل تريد المتابعة رغم ذلك؟')) {
                        $this->processSuccessfulCallback($payment, $sessionId, $dryRun);
                    }
                }
            } else {
                $this->info("🧪 في وضع الاختبار - سيتم محاكاة نجاح الدفع");
            }
        } else {
            $this->warn("⚠️ لم يتم العثور على Session ID");
            if ($force && $this->confirm('هل تريد المتابعة رغم ذلك؟')) {
                $this->processSuccessfulCallback($payment, 'MANUAL_FIX_' . time(), $dryRun);
            }
        }
    }

    private function fixAllCallbackIssues($force = false, $dryRun = false)
    {
        $this->info("🔧 البحث عن مشاكل Tabby callback...");

        $payments = PaymentRequest::where('payment_method', 'tabby')
            ->where(function($query) {
                $query->whereNull('payment_status')
                      ->orWhere('payment_status', '!=', 'success')
                      ->orWhere('is_paid', 0)
                      ->orWhereNull('transaction_reference');
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        $this->info("📊 تم العثور على {$payments->count()} دفعة تحتاج إصلاح");

        if ($payments->isEmpty()) {
            $this->info("✅ لا توجد دفعات تحتاج إصلاح");
            return;
        }

        foreach ($payments as $payment) {
            $this->newLine();
            $this->info("🔧 معالجة الدفع: {$payment->id}");
            
            $sessionId = $this->findSessionIdFromLogs($payment->id);
            if ($sessionId) {
                $this->processSuccessfulCallback($payment, $sessionId, $dryRun);
            } else {
                $this->warn("⚠️ لم يتم العثور على Session ID للدفع: {$payment->id}");
            }
        }
    }

    private function processSuccessfulCallback($payment, $sessionId, $dryRun = false)
    {
        try {
            if (!$dryRun) {
                DB::beginTransaction();
            }

            $this->info("📝 تحديث حالة الدفع...");
            
            if (!$dryRun) {
                // تحديث حالة الدفع
                $payment->payment_status = 'success';
                $payment->is_paid = 1;
                $payment->transaction_reference = $sessionId;
                $payment->save();
            }

            $this->info("✅ تم تحديث حالة الدفع");

            // معالجة success_hook
            if (!empty($payment->success_hook) && function_exists($payment->success_hook)) {
                $this->info("🔧 استدعاء success_hook: {$payment->success_hook}");
                
                if (!$dryRun) {
                    $result = call_user_func($payment->success_hook, $payment);
                    $this->info("✅ تم تنفيذ success_hook بنجاح");
                } else {
                    $this->info("🧪 في وضع الاختبار - سيتم استدعاء success_hook");
                }
            } else {
                $this->warn("⚠️ لا يوجد success_hook أو الدالة غير موجودة");
            }

            // إعادة تحميل البيانات
            if (!$dryRun) {
                $payment->refresh();
            }

            $this->info("📊 النتائج النهائية:");
            $this->table(
                ['الحقل', 'القيمة'],
                [
                    ['Payment Status', $payment->payment_status ?? 'NULL'],
                    ['Is Paid', $payment->is_paid ? 'نعم' : 'لا'],
                    ['Transaction Reference', $payment->transaction_reference ?? 'NULL'],
                    ['Order IDs', $payment->order_ids ?? 'NULL'],
                ]
            );

            // التحقق من إنشاء الطلبات
            if (!empty($payment->order_ids)) {
                $orderIds = json_decode($payment->order_ids, true);
                if (!is_array($orderIds)) {
                    $orderIds = explode(',', $payment->order_ids);
                }

                $this->info("📦 تم إنشاء الطلبات: " . implode(', ', $orderIds));

                foreach ($orderIds as $orderId) {
                    $order = Order::find($orderId);
                    if ($order) {
                        $this->info("✅ الطلب {$orderId}: {$order->order_status} - {$order->payment_status}");
                        
                        // إرسال الإشعارات
                        if (!$dryRun) {
                            $this->sendOrderNotifications($orderId, $payment, $sessionId);
                        }
                    } else {
                        $this->warn("⚠️ الطلب {$orderId}: غير موجود");
                    }
                }
            } else {
                $this->warn("⚠️ لم يتم إنشاء أي طلبات");
            }

            if (!$dryRun) {
                DB::commit();
            }
            
            $this->info("🎉 تم إصلاح callback بنجاح!");

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            $this->error("❌ خطأ في إصلاح callback: " . $e->getMessage());
            Log::error('FixTabbyCallback Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function sendOrderNotifications($orderId, $payment, $sessionId)
    {
        try {
            $order = Order::find($orderId);
            if ($order) {
                // إطلاق Event لإرسال الإشعارات وإنشاء الشحنة
                event(new \App\Events\OrderPlacedEvent((object)['order' => $order]));
                
                // إرسال إشعارات SMS
                $notificationService = app(OrderNotificationService::class);
                $notificationService->sendOrderNotifications($orderId, [
                    'transaction_reference' => $sessionId,
                    'payment_amount' => $payment->payment_amount,
                    'payment_method' => 'Tabby'
                ]);
                
                $this->info("✅ تم إرسال إشعارات للطلب: {$orderId}");
            }
        } catch (\Exception $e) {
            $this->warn("⚠️ خطأ في إرسال الإشعارات: " . $e->getMessage());
        }
    }

    private function findSessionIdFromLogs($paymentId)
    {
        try {
            // البحث في ملف السجل عن session_id للدفع المحدد
            $logFile = storage_path('logs/laravel.log');
            
            if (!file_exists($logFile)) {
                return null;
            }

            $logContent = file_get_contents($logFile);
            
            // البحث عن patterns مختلفة لـ session_id
            $patterns = [
                "/Tabby Payment Redirect.*payment_id.*{$paymentId}.*session_id.*?([a-zA-Z0-9\-_]+)/",
                "/Tabby.*session_id.*?([a-zA-Z0-9\-_]+).*payment_id.*{$paymentId}/",
                "/session_id.*?([a-zA-Z0-9\-_]+).*Tabby.*payment_id.*{$paymentId}/"
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $logContent, $matches)) {
                    return $matches[1];
                }
            }

            return null;
        } catch (\Exception $e) {
            $this->warn("⚠️ خطأ في البحث عن Session ID: " . $e->getMessage());
            return null;
        }
    }
}