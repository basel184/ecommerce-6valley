<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentRequest;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class TestPaymentFailureSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:payment-failure {payment_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'اختبار نظام معالجة فشل الدفع وإرسال الطلبات إلى الطلبات الملغية';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 بدء اختبار نظام معالجة فشل الدفع...');
        
        $paymentId = $this->argument('payment_id');
        
        if ($paymentId) {
            $this->testSpecificPayment($paymentId);
        } else {
            $this->testPaymentFailureSystem();
        }
        
        $this->info('✅ انتهى اختبار نظام معالجة فشل الدفع');
    }
    
    private function testSpecificPayment($paymentId)
    {
        $this->info("🔍 اختبار دفع محدد: {$paymentId}");
        
        $payment = PaymentRequest::find($paymentId);
        
        if (!$payment) {
            $this->error("❌ لم يتم العثور على الدفع: {$paymentId}");
            return;
        }
        
        $this->info("📋 معلومات الدفع:");
        $this->table(
            ['الحقل', 'القيمة'],
            [
                ['ID', $payment->id],
                ['طريقة الدفع', $payment->payment_method],
                ['حالة الدفع', $payment->payment_status],
                ['مبلغ الدفع', $payment->payment_amount],
                ['Success Hook', $payment->success_hook],
                ['Failure Hook', $payment->failure_hook],
                ['Order IDs', $payment->order_ids],
            ]
        );
        
        if ($this->confirm('هل تريد محاكاة فشل هذا الدفع؟')) {
            $this->simulatePaymentFailure($payment);
        }
    }
    
    private function testPaymentFailureSystem()
    {
        $this->info('📊 إحصائيات المدفوعات:');
        
        // إحصائيات عامة
        $totalPayments = PaymentRequest::count();
        $successfulPayments = PaymentRequest::where('payment_status', 'success')->count();
        $failedPayments = PaymentRequest::where('payment_status', 'failed')->count();
        $pendingPayments = PaymentRequest::where('payment_status', 'pending')->count();
        
        $this->table(
            ['الحالة', 'العدد'],
            [
                ['إجمالي المدفوعات', $totalPayments],
                ['المدفوعات الناجحة', $successfulPayments],
                ['المدفوعات الفاشلة', $failedPayments],
                ['المدفوعات المعلقة', $pendingPayments],
            ]
        );
        
        // إحصائيات الطلبات الملغية
        $this->info('📋 إحصائيات الطلبات الملغية:');
        
        $totalOrders = Order::count();
        $canceledOrders = Order::where('order_status', 'canceled')->count();
        $failedOrders = Order::where('order_status', 'failed')->count();
        
        $this->table(
            ['الحالة', 'العدد'],
            [
                ['إجمالي الطلبات', $totalOrders],
                ['الطلبات الملغية', $canceledOrders],
                ['الطلبات الفاشلة', $failedOrders],
            ]
        );
        
        // البحث عن طلبات ملغية بسبب فشل الدفع
        $this->info('🔍 البحث عن طلبات ملغية بسبب فشل الدفع:');
        
        $canceledDueToPaymentFailure = Order::where('order_status', 'canceled')
            ->where('cause', 'like', '%فشل في عملية الدفع%')
            ->get();
        
        if ($canceledDueToPaymentFailure->count() > 0) {
            $this->table(
                ['ID الطلب', 'سبب الإلغاء', 'تاريخ الإنشاء'],
                $canceledDueToPaymentFailure->map(function ($order) {
                    return [
                        $order->id,
                        $order->cause,
                        $order->created_at->format('Y-m-d H:i:s')
                    ];
                })->toArray()
            );
        } else {
            $this->info('✅ لا توجد طلبات ملغية بسبب فشل الدفع');
        }
        
        // اختبار محاكاة فشل الدفع
        if ($this->confirm('هل تريد محاكاة فشل دفع جديد؟')) {
            $this->simulateNewPaymentFailure();
        }
    }
    
    private function simulatePaymentFailure($payment)
    {
        $this->info('🔄 محاكاة فشل الدفع...');
        
        try {
            // تحديث حالة الدفع إلى فاشل
            $payment->payment_status = 'failed';
            $payment->is_paid = 0;
            $payment->save();
            
            $this->info('✅ تم تحديث حالة الدفع إلى فاشل');
            
            // استدعاء دالة فشل الدفع
            if (function_exists($payment->failure_hook)) {
                $this->info("📞 استدعاء {$payment->failure_hook}...");
                call_user_func($payment->failure_hook, $payment);
                $this->info('✅ تم استدعاء دالة فشل الدفع بنجاح');
            } else {
                $this->warn("⚠️ دالة {$payment->failure_hook} غير موجودة");
            }
            
            // التحقق من الطلبات المرتبطة
            $orderIds = json_decode($payment->order_ids ?? '[]', true);
            
            if (!empty($orderIds)) {
                $this->info('📋 التحقق من الطلبات المرتبطة:');
                
                foreach ($orderIds as $orderId) {
                    $order = Order::find($orderId);
                    
                    if ($order) {
                        $this->info("   - الطلب {$orderId}: {$order->order_status}");
                        
                        if ($order->order_status === 'canceled') {
                            $this->info("     ✅ تم إلغاء الطلب بنجاح");
                            $this->info("     📝 السبب: {$order->cause}");
                        } else {
                            $this->warn("     ⚠️ الطلب لم يتم إلغاؤه");
                        }
                    } else {
                        $this->error("     ❌ الطلب {$orderId} غير موجود");
                    }
                }
            } else {
                $this->warn('⚠️ لا توجد طلبات مرتبطة بهذا الدفع');
            }
            
        } catch (\Exception $e) {
            $this->error("❌ خطأ في محاكاة فشل الدفع: {$e->getMessage()}");
            Log::error('TestPaymentFailureSystem: Error simulating payment failure', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    private function simulateNewPaymentFailure()
    {
        $this->info('🔄 إنشاء دفع جديد لمحاكاة الفشل...');
        
        try {
            // إنشاء دفع تجريبي
            $payment = PaymentRequest::create([
                'id' => 'TEST_' . time(),
                'payment_method' => 'tabby',
                'payment_status' => 'pending',
                'is_paid' => 0,
                'payment_amount' => 100.00,
                'currency_code' => 'SAR',
                'transaction_reference' => 'TEST_' . time(),
                'payer_information' => json_encode([
                    'name' => 'Test Customer',
                    'email' => 'test@example.com',
                    'phone' => '0500000000'
                ]),
                'receiver_information' => json_encode([
                    'name' => 'Test Store',
                    'image' => 'store.png'
                ]),
                'additional_data' => json_encode([
                    'customer_id' => 1,
                    'is_guest' => 0,
                    'payment_request_from' => 'web'
                ]),
                'success_hook' => 'digital_payment_success',
                'failure_hook' => 'digital_payment_fail',
                'order_ids' => json_encode([1, 2, 3]), // طلبات تجريبية
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->info("✅ تم إنشاء دفع تجريبي: {$payment->id}");
            
            // محاكاة فشل الدفع
            $this->simulatePaymentFailure($payment);
            
        } catch (\Exception $e) {
            $this->error("❌ خطأ في إنشاء دفع تجريبي: {$e->getMessage()}");
        }
    }
}