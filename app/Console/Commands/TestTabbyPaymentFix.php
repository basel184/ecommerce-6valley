<?php

namespace App\Console\Commands;

use App\Models\PaymentRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestTabbyPaymentFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:tabby-fix {payment_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Tabby payment fix by simulating callback';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $paymentId = $this->argument('payment_id');
        
        if (!$paymentId) {
            $this->info('🔍 البحث عن مدفوعات Tabby حديثة...');
            
            $recentPayments = PaymentRequest::where('payment_method', 'tabby')
                ->where('created_at', '>=', now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(['id', 'payment_amount', 'payment_status', 'is_paid', 'created_at']);
            
            if ($recentPayments->isEmpty()) {
                $this->error('❌ لم يتم العثور على مدفوعات Tabby حديثة');
                return;
            }
            
            $this->info('📋 المدفوعات الحديثة:');
            foreach ($recentPayments as $index => $payment) {
                $status = $payment->payment_status === 'success' ? '✅' : '❌';
                $indexNum = $index + 1;
                $this->line("{$indexNum}. {$status} ID: {$payment->id} - المبلغ: {$payment->payment_amount} - الحالة: {$payment->payment_status}");
            }
            
            $choice = $this->choice('اختر رقم الدفع لاختباره:', range(1, $recentPayments->count()));
            $paymentId = $recentPayments[$choice - 1]->id;
        }
        
        $this->info("🧪 اختبار إصلاح Tabby للدفع: {$paymentId}");
        
        $payment = PaymentRequest::find($paymentId);
        
        if (!$payment) {
            $this->error("❌ لم يتم العثور على الدفع: {$paymentId}");
            return;
        }
        
        if ($payment->payment_method !== 'tabby') {
            $this->error("❌ هذا الدفع ليس من Tabby: {$payment->payment_method}");
            return;
        }
        
        $this->info("📊 معلومات الدفع:");
        $this->table(
            ['الحقل', 'القيمة'],
            [
                ['ID', $payment->id],
                ['المبلغ', $payment->payment_amount],
                ['الحالة الحالية', $payment->payment_status],
                ['مدفوع', $payment->is_paid ? 'نعم' : 'لا'],
                ['Success Hook', $payment->success_hook ?? 'غير محدد'],
                ['Order IDs', $payment->order_ids ?? 'غير محدد'],
                ['Attribute ID', $payment->attribute_id ?? 'غير محدد'],
                ['External Redirect', $payment->external_redirect_link ?? 'غير محدد'],
            ]
        );
        
        if (!$this->confirm('هل تريد المتابعة مع اختبار الإصلاح؟')) {
            $this->info('❌ تم إلغاء الاختبار');
            return;
        }
        
        $this->info('⚡ بدء اختبار الإصلاح...');
        
        try {
            DB::beginTransaction();
            
            // 1. تحديث حالة الدفع
            $payment->payment_status = 'success';
            $payment->is_paid = 1;
            $payment->transaction_reference = 'TEST_FIX_' . time();
            $payment->save();
            
            $this->info('✅ تم تحديث حالة الدفع');
            
            // 2. اختبار success_hook
            if (!empty($payment->success_hook) && function_exists($payment->success_hook)) {
                $this->info("🔧 اختبار success_hook: {$payment->success_hook}");
                
                Log::info('=== TABBY FIX TEST START ===', [
                    'payment_id' => $payment->id,
                    'test_type' => 'success_hook'
                ]);
                
                $result = call_user_func($payment->success_hook, $payment);
                
                Log::info('=== TABBY FIX TEST END ===', [
                    'payment_id' => $payment->id,
                    'result_type' => gettype($result)
                ]);
                
                $this->info('✅ تم تنفيذ success_hook بنجاح');
                $this->info("📤 نوع النتيجة: " . gettype($result));
                
                if (is_object($result)) {
                    $this->info("📤 فئة النتيجة: " . get_class($result));
                }
                
            } else {
                $this->warn('⚠️ لا يوجد success_hook أو الدالة غير موجودة');
            }
            
            // 3. إعادة تحميل البيانات
            $payment->refresh();
            
            $this->info('📊 النتائج النهائية:');
            $this->table(
                ['الحقل', 'القيمة'],
                [
                    ['Payment Status', $payment->payment_status],
                    ['Is Paid', $payment->is_paid ? 'نعم' : 'لا'],
                    ['Transaction Reference', $payment->transaction_reference ?? 'غير محدد'],
                    ['Order IDs', $payment->order_ids ?? 'غير محدد'],
                ]
            );
            
            // 4. التحقق من إنشاء الطلبات
            if (!empty($payment->order_ids)) {
                $orderIds = json_decode($payment->order_ids, true);
                if (!is_array($orderIds)) {
                    $orderIds = explode(',', $payment->order_ids);
                }
                
                $this->info("📦 تم إنشاء الطلبات: " . implode(', ', $orderIds));
                
                foreach ($orderIds as $orderId) {
                    $order = \App\Models\Order::find($orderId);
                    if ($order) {
                        $this->info("✅ الطلب {$orderId}: {$order->order_status} - {$order->payment_status}");
                    } else {
                        $this->warn("⚠️ الطلب {$orderId}: غير موجود");
                    }
                }
            } else {
                $this->warn('⚠️ لم يتم إنشاء أي طلبات');
            }
            
            DB::commit();
            $this->info('🎉 تم اختبار الإصلاح بنجاح!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ خطأ في الاختبار: " . $e->getMessage());
            $this->error("📋 التفاصيل: " . $e->getTraceAsString());
        }
    }
} 