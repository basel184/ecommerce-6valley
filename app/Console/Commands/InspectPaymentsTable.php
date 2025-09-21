<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReverseTransferService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class InspectPaymentsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:inspect {--order-id= : فحص دفعة لطلب محدد}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'فحص هيكل جدول payments والبيانات';

    /**
     * Execute the console command.
     */
    public function handle(ReverseTransferService $reverseTransferService)
    {
        $this->info('🔍 فحص جدول payments...');

        try {
            // فحص مباشر لجدول payments
            $this->inspectPaymentsTableDirectly();
            
            // فحص باستخدام ReverseTransferService
            $this->inspectUsingService($reverseTransferService);

        } catch (\Exception $e) {
            $this->error('❌ خطأ: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * فحص مباشر لجدول payments
     */
    protected function inspectPaymentsTableDirectly()
    {
        $this->info('📋 1. فحص مباشر لجدول payments...');

        try {
            // فحص وجود الجدول
            if (!Schema::hasTable('payments')) {
                $this->error('❌ جدول payments غير موجود');
                return;
            }

            $this->info('✅ جدول payments موجود');

            // فحص هيكل الجدول
            $columns = Schema::getColumnListing('payments');
            $this->info('📊 أعمدة جدول payments:');
            $this->table(
                ['العمود', 'النوع', 'مطلوب', 'افتراضي'],
                collect($columns)->map(function($column) {
                    $columnType = Schema::getColumnType('payments', $column);
                    $isNullable = Schema::getConnection()->getDoctrineSchemaManager()->listTableDetails('payments')->getColumn($column)->getNotnull();
                    $default = Schema::getConnection()->getDoctrineSchemaManager()->listTableDetails('payments')->getColumn($column)->getDefault();
                    
                    return [
                        $column,
                        $columnType,
                        $isNullable ? 'لا' : 'نعم',
                        $default ?? 'لا يوجد'
                    ];
                })->toArray()
            );

            // فحص البيانات
            $totalPayments = DB::table('payments')->count();
            $this->info("📊 إجمالي المدفوعات: {$totalPayments}");

            if ($totalPayments > 0) {
                // عينة من البيانات
                $samplePayments = DB::table('payments')
                    ->select('id', 'order_id', 'amount', 'status', 'created_at')
                    ->orderBy('id', 'desc')
                    ->limit(10)
                    ->get();

                $this->info('📋 عينة من المدفوعات (أحدث 10):');
                $this->table(
                    ['ID', 'Order ID', 'Amount', 'Status', 'Created At'],
                    $samplePayments->map(function($payment) {
                        return [
                            $payment->id,
                            $payment->order_id ?? 'غير محدد',
                            $payment->amount ?? 'غير محدد',
                            $payment->status ?? 'غير محدد',
                            $payment->created_at ?? 'غير محدد'
                        ];
                    })->toArray()
                );

                // فحص المدفوعات المرتبطة بالطلبات
                $paymentsWithOrders = DB::table('payments')
                    ->join('orders', 'payments.order_id', '=', 'orders.id')
                    ->select('payments.id', 'payments.order_id', 'payments.amount', 'orders.order_amount', 'orders.payment_status')
                    ->limit(10)
                    ->get();

                if ($paymentsWithOrders->isNotEmpty()) {
                    $this->info('📋 المدفوعات المرتبطة بالطلبات (أحدث 10):');
                    $this->table(
                        ['Payment ID', 'Order ID', 'Payment Amount', 'Order Amount', 'Payment Status'],
                        $paymentsWithOrders->map(function($item) {
                            return [
                                $item->id,
                                $item->order_id,
                                $item->amount ?? 'غير محدد',
                                $item->order_amount ?? 'غير محدد',
                                $item->payment_status ?? 'غير محدد'
                            ];
                        })->toArray()
                    );
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ خطأ في الفحص المباشر: ' . $e->getMessage());
        }
    }

    /**
     * فحص باستخدام ReverseTransferService
     */
    protected function inspectUsingService($reverseTransferService)
    {
        $this->info('📋 2. فحص باستخدام ReverseTransferService...');

        try {
            $result = $reverseTransferService->inspectPaymentsTable();
            
            if ($result['success']) {
                $this->info('✅ تم فحص جدول payments بنجاح');
                
                if (isset($result['columns'])) {
                    $this->info('📊 أعمدة الجدول:');
                    $this->table(
                        ['العمود', 'النوع', 'مطلوب', 'افتراضي'],
                        $result['columns']
                    );
                }

                if (isset($result['sample_data']) && !empty($result['sample_data'])) {
                    $this->info('📋 عينة من البيانات:');
                    $this->table(
                        ['ID', 'Order ID', 'Amount', 'Status', 'Created At'],
                        $result['sample_data']
                    );
                }
            } else {
                $this->warn('⚠️ ' . $result['message']);
            }

        } catch (\Exception $e) {
            $this->error('❌ خطأ في فحص ReverseTransferService: ' . $e->getMessage());
        }
    }

    /**
     * فحص دفعة لطلب محدد
     */
    protected function inspectOrderPayment($reverseTransferService, $orderId)
    {
        try {
            // البحث عن الطلب
            $order = \App\Models\Order::find($orderId);
            if (!$order) {
                $this->error("❌ الطلب #{$orderId} غير موجود");
                return;
            }

            $this->info("📋 معلومات الطلب:");
            $this->table(
                ['المؤشر', 'القيمة'],
                [
                    ['معرف الطلب', $order->id],
                    ['حالة الدفع', $order->payment_status],
                    ['طريقة الدفع', $order->payment_method ?? 'غير محدد'],
                    ['المبلغ', $order->order_amount ?? 'غير محدد'],
                    ['تاريخ الإنشاء', $order->created_at->format('Y-m-d H:i:s')]
                ]
            );

            // البحث عن الدفعة
            $payment = $reverseTransferService->findPaymentByOrderId($orderId);
            if ($payment) {
                $this->info("💰 معلومات الدفعة:");
                $this->table(
                    ['المؤشر', 'القيمة'],
                    [
                        ['معرف الدفعة', $payment->id],
                        ['معرف الطلب', $payment->order_id ?? 'غير محدد'],
                        ['معرف المعاملة', 'PAYMENT_' . $payment->id]
                    ]
                );

                // عرض جميع بيانات الدفعة
                $this->info("📊 جميع بيانات الدفعة:");
                $columns = array_keys((array) $payment);
                $this->table(
                    $columns,
                    [array_values((array) $payment)]
                );

            } else {
                $this->warn("⚠️ لم يتم العثور على دفعة للطلب #{$orderId}");
            }

        } catch (\Exception $e) {
            $this->error("❌ خطأ في فحص دفعة الطلب: " . $e->getMessage());
        }
    }
}
