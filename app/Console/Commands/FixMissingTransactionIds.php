<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReverseTransferService;
use App\Models\ReverseTransfer; // Added this import
use Illuminate\Support\Facades\Log; // Added this import

class FixMissingTransactionIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverse-transfer:fix-transaction-ids {--check-only : فحص الحالة فقط بدون إصلاح} {--comprehensive : فحص شامل لجميع المشاكل}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إصلاح معرفات المعاملات المفقودة في الحوالات العكسية';

    /**
     * Execute the console command.
     */
    public function handle(ReverseTransferService $reverseTransferService)
    {
        // فحص شامل إذا تم طلبه
        if ($this->option('comprehensive')) {
            $this->comprehensiveCheck($reverseTransferService);
            return 0;
        }

        $this->info('🔍 فحص الحوالات العكسية...');

        try {
            // فحص الحالة أولاً
            $status = $reverseTransferService->checkReverseTransfersStatus();
            
            if (isset($status['error'])) {
                $this->error('❌ خطأ في فحص الحالة: ' . $status['error']);
                return 1;
            }

            $this->info('📊 حالة الحوالات العكسية:');
            $this->table(
                ['المؤشر', 'القيمة'],
                [
                    ['إجمالي الحوالات العكسية', $status['total']],
                    ['مع معرف معاملة', $status['with_transaction_id']],
                    ['بدون معرف معاملة', $status['without_transaction_id']],
                    ['النسبة المئوية', $status['percentage'] . '%']
                ]
            );

            // عرض الحالات
            $this->info('📋 توزيع الحالات:');
            $this->table(
                ['الحالة', 'العدد'],
                [
                    ['معلق', $status['statuses']['pending']],
                    ['موافق', $status['statuses']['approved']],
                    ['معالج', $status['statuses']['processed']],
                    ['مكتمل', $status['statuses']['completed']],
                    ['فشل', $status['statuses']['failed']]
                ]
            );

            if ($status['without_transaction_id'] == 0) {
                $this->info('✅ جميع الحوالات العكسية تحتوي على معرفات معاملات صحيحة');
                
                // فحص إضافي لبيانات العميل
                $this->info('🔍 فحص بيانات العميل...');
                $this->checkCustomerData($reverseTransferService);
                
                return 0;
            }

            if ($this->option('check-only')) {
                $this->warn("⚠️ يوجد {$status['without_transaction_id']} حوالة عكسية تحتاج إلى إصلاح");
                $this->info('استخدم الأمر بدون --check-only للإصلاح');
                return 0;
            }

            // تأكيد الإصلاح
            if (!$this->confirm("هل تريد إصلاح {$status['without_transaction_id']} حوالة عكسية؟")) {
                $this->info('❌ تم إلغاء العملية');
                return 0;
            }

            $this->info('🔧 بدء إصلاح الحوالات العكسية...');
            
            // إصلاح المعرفات المفقودة
            $result = $reverseTransferService->fixExistingReverseTransfers();

            if ($result['success']) {
                $this->info("✅ " . $result['message']);
                
                if ($result['fixed_count'] > 0) {
                    $this->table(
                        ['المؤشر', 'القيمة'],
                        [
                            ['تم إصلاحها', $result['fixed_count']],
                            ['إجمالي المطلوب', $result['total_count']],
                            ['النسبة المئوية', round(($result['fixed_count'] / $result['total_count']) * 100, 2) . '%']
                        ]
                    );
                }
            } else {
                $this->error("❌ فشل في الإصلاح: " . $result['error']);
                return 1;
            }

            // فحص الحالة بعد الإصلاح
            $this->info('🔍 فحص الحالة بعد الإصلاح...');
            $newStatus = $reverseTransferService->checkReverseTransfersStatus();
            
            if (!isset($newStatus['error'])) {
                $this->info('📊 الحالة الجديدة:');
                $this->table(
                    ['المؤشر', 'القيمة'],
                    [
                        ['إجمالي الحوالات العكسية', $newStatus['total']],
                        ['مع معرف معاملة', $newStatus['with_transaction_id']],
                        ['بدون معرف معاملة', $newStatus['without_transaction_id']],
                        ['النسبة المئوية', $newStatus['percentage'] . '%']
                    ]
                );

                if ($newStatus['without_transaction_id'] == 0) {
                    $this->info('🎉 تم إصلاح جميع الحوالات العكسية بنجاح!');
                } else {
                    $this->warn("⚠️ لا يزال يوجد {$newStatus['without_transaction_id']} حوالة عكسية تحتاج إلى إصلاح");
                }
            }

            // فحص بيانات العميل
            $this->info('🔍 فحص بيانات العميل...');
            $this->checkCustomerData($reverseTransferService);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ خطأ: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * فحص شامل لجميع المشاكل
     */
    protected function comprehensiveCheck($reverseTransferService)
    {
        $this->info('🔍 فحص شامل لجميع المشاكل...');

        try {
            // 1. فحص معرفات المعاملات
            $this->info('📋 1. فحص معرفات المعاملات...');
            $status = $reverseTransferService->checkReverseTransfersStatus();
            
            if (isset($status['error'])) {
                $this->error('❌ خطأ في فحص الحالة: ' . $status['error']);
                return;
            }

            $this->table(
                ['المؤشر', 'القيمة'],
                [
                    ['إجمالي الحوالات العكسية', $status['total']],
                    ['مع معرف معاملة', $status['with_transaction_id']],
                    ['بدون معرف معاملة', $status['without_transaction_id']],
                    ['النسبة المئوية', $status['percentage'] . '%']
                ]
            );

            // 2. فحص payments.id كمرجع العميل
            $this->info('📋 2. فحص payments.id كمرجع العميل...');
            $this->checkPaymentsIdAsCustomerReference($reverseTransferService);

            // 3. فحص بيانات العميل
            $this->info('📋 3. فحص بيانات العميل...');
            $this->checkCustomerData($reverseTransferService);

            // 4. فحص الحقول المطلوبة
            $this->info('📋 4. فحص الحقول المطلوبة...');
            $this->checkRequiredFields();

            // 5. فحص العلاقات
            $this->info('📋 5. فحص العلاقات...');
            $this->checkRelationships();

            $this->info('✅ تم الانتهاء من الفحص الشامل');

        } catch (\Exception $e) {
            $this->error('❌ خطأ في الفحص الشامل: ' . $e->getMessage());
        }
    }

    /**
     * فحص الحقول المطلوبة
     */
    protected function checkRequiredFields()
    {
        try {
            // البحث عن الحوالات العكسية بدون بيانات أساسية
            $reverseTransfers = ReverseTransfer::where(function($query) {
                $query->whereNull('amount')
                    ->orWhere('amount', '<=', 0)
                    ->orWhereNull('customer_id')
                    ->orWhereNull('order_id');
            })->get();

            if ($reverseTransfers->isEmpty()) {
                $this->info('✅ جميع الحوالات العكسية تحتوي على الحقول الأساسية');
                return;
            }

            $this->warn("⚠️ يوجد {$reverseTransfers->count()} حوالة عكسية تحتوي على حقول أساسية مفقودة");

            $this->table(
                ['ID', 'Order ID', 'Customer ID', 'Amount', 'Status'],
                $reverseTransfers->map(function($rt) {
                    return [
                        $rt->id,
                        $rt->order_id ?? 'غير موجود',
                        $rt->customer_id ?? 'غير موجود',
                        $rt->amount ?? 'غير موجود',
                        $rt->status
                    ];
                })->toArray()
            );

        } catch (\Exception $e) {
            $this->error('❌ خطأ في فحص الحقول المطلوبة: ' . $e->getMessage());
        }
    }

    /**
     * فحص العلاقات
     */
    protected function checkRelationships()
    {
        try {
            // البحث عن الحوالات العكسية بدون علاقات صحيحة
            $reverseTransfers = ReverseTransfer::whereDoesntHave('order')
                ->orWhereDoesntHave('customer')
                ->get();

            if ($reverseTransfers->isEmpty()) {
                $this->info('✅ جميع الحوالات العكسية تحتوي على علاقات صحيحة');
                return;
            }

            $this->warn("⚠️ يوجد {$reverseTransfers->count()} حوالة عكسية تحتوي على علاقات مفقودة");

            $this->table(
                ['ID', 'Order ID', 'Customer ID', 'Status', 'المشكلة'],
                $reverseTransfers->map(function($rt) {
                    $problems = [];
                    if (!$rt->order) $problems[] = 'الطلب مفقود';
                    if (!$rt->customer) $problems[] = 'العميل مفقود';
                    
                    return [
                        $rt->id,
                        $rt->order_id ?? 'غير موجود',
                        $rt->customer_id ?? 'غير موجود',
                        $rt->status,
                        implode(', ', $problems)
                    ];
                })->toArray()
            );

        } catch (\Exception $e) {
            $this->error('❌ خطأ في فحص العلاقات: ' . $e->getMessage());
        }
    }

    /**
     * فحص بيانات العميل
     */
    protected function checkCustomerData($reverseTransferService)
    {
        try {
            // البحث عن الحوالات العكسية بدون بيانات عميل كاملة
            $reverseTransfers = ReverseTransfer::whereHas('customer', function($query) {
                $query->whereNull('phone')
                    ->orWhere('phone', '')
                    ->orWhereNull('email')
                    ->orWhere('email', '');
            })->get();

            if ($reverseTransfers->isEmpty()) {
                $this->info('✅ جميع الحوالات العكسية تحتوي على بيانات عميل كاملة');
                return;
            }

            $this->warn("⚠️ يوجد {$reverseTransfers->count()} حوالة عكسية تحتوي على بيانات عميل غير مكتملة");

            $this->table(
                ['ID', 'Order ID', 'Customer', 'Phone', 'Email', 'Status'],
                $reverseTransfers->map(function($rt) {
                    $customer = $rt->customer;
                    return [
                        $rt->id,
                        $rt->order_id,
                        $customer ? ($customer->f_name . ' ' . $customer->l_name) : 'غير موجود',
                        $customer->phone ?? 'غير موجود',
                        $customer->email ?? 'غير موجود',
                        $rt->status
                    ];
                })->toArray()
            );

            if ($this->confirm('هل تريد محاولة إصلاح بيانات العميل؟')) {
                $this->fixCustomerData($reverseTransfers);
            }

        } catch (\Exception $e) {
            $this->error('❌ خطأ في فحص بيانات العميل: ' . $e->getMessage());
        }
    }

    /**
     * إصلاح بيانات العميل
     */
    protected function fixCustomerData($reverseTransfers)
    {
        $fixedCount = 0;
        $errors = [];

        foreach ($reverseTransfers as $reverseTransfer) {
            try {
                $customer = $reverseTransfer->customer;
                if (!$customer) {
                    $errors[] = "الحوالة العكسية #{$reverseTransfer->id}: العميل غير موجود";
                    continue;
                }

                $updated = false;

                // إصلاح رقم الهاتف
                if (empty($customer->phone)) {
                    $customer->phone = '966500000000';
                    $updated = true;
                }

                // إصلاح البريد الإلكتروني
                if (empty($customer->email)) {
                    $customer->email = 'customer_' . $customer->id . '@example.com';
                    $updated = true;
                }

                // إصلاح الاسم
                if (empty($customer->f_name)) {
                    $customer->f_name = 'مستخدم';
                    $updated = true;
                }

                if (empty($customer->l_name)) {
                    $customer->l_name = 'جديد';
                    $updated = true;
                }

                if ($updated) {
                    $customer->save();
                    $fixedCount++;
                    
                    Log::info('تم إصلاح بيانات العميل', [
                        'reverse_transfer_id' => $reverseTransfer->id,
                        'customer_id' => $customer->id,
                        'phone' => $customer->phone,
                        'email' => $customer->email,
                        'name' => $customer->f_name . ' ' . $customer->l_name
                    ]);
                }

            } catch (\Exception $e) {
                $errors[] = "الحوالة العكسية #{$reverseTransfer->id}: " . $e->getMessage();
            }
        }

        if ($fixedCount > 0) {
            $this->info("✅ تم إصلاح بيانات {$fixedCount} عميل");
        }

        if (!empty($errors)) {
            $this->warn("⚠️ أخطاء في إصلاح بيانات العميل:");
            foreach ($errors as $error) {
                $this->warn("   - {$error}");
            }
        }
    }

    /**
     * فحص خاص بـ payments.id كمرجع العميل
     */
    protected function checkPaymentsIdAsCustomerReference($reverseTransferService)
    {
        $this->info('🔍 فحص payments.id كمرجع العميل...');

        try {
            // البحث عن جميع الحوالات العكسية
            $reverseTransfers = ReverseTransfer::all();
            
            if ($reverseTransfers->isEmpty()) {
                $this->info('✅ لا توجد حوالات عكسية للفحص');
                return;
            }

            $paymentsIdCount = 0;
            $transactionReferenceCount = 0;
            $paymentRequestCount = 0;
            $tempIdCount = 0;
            $missingCount = 0;

            $details = [];

            foreach ($reverseTransfers as $reverseTransfer) {
                $order = $reverseTransfer->order;
                if (!$order) {
                    $missingCount++;
                    $details[] = [
                        'ID' => $reverseTransfer->id,
                        'Order ID' => $reverseTransfer->order_id ?? 'غير موجود',
                        'المصدر' => 'الطلب مفقود',
                        'معرف المعاملة' => $reverseTransfer->original_transaction_id ?? 'غير موجود'
                    ];
                    continue;
                }

                $transactionId = $reverseTransfer->original_transaction_id;
                
                if (str_starts_with($transactionId, 'PAYMENT_')) {
                    $paymentsIdCount++;
                    $details[] = [
                        'ID' => $reverseTransfer->id,
                        'Order ID' => $order->id,
                        'المصدر' => 'payments.id (مرجع العميل)',
                        'معرف المعاملة' => $transactionId
                    ];
                } elseif (str_starts_with($transactionId, 'PAYMENT_REQUEST_')) {
                    $paymentRequestCount++;
                    $details[] = [
                        'ID' => $reverseTransfer->id,
                        'Order ID' => $order->id,
                        'المصدر' => 'payment_requests.id',
                        'معرف المعاملة' => $transactionId
                    ];
                } elseif (str_starts_with($transactionId, 'ORDER_')) {
                    $tempIdCount++;
                    $details[] = [
                        'ID' => $reverseTransfer->id,
                        'Order ID' => $order->id,
                        'المصدر' => 'معرف مؤقت',
                        'معرف المعاملة' => $transactionId
                    ];
                } elseif (!empty($transactionId)) {
                    $transactionReferenceCount++;
                    $details[] = [
                        'ID' => $reverseTransfer->id,
                        'Order ID' => $order->id,
                        'المصدر' => 'transaction_reference',
                        'معرف المعاملة' => $transactionId
                    ];
                } else {
                    $missingCount++;
                    $details[] = [
                        'ID' => $reverseTransfer->id,
                        'Order ID' => $order->id,
                        'المصدر' => 'غير محدد',
                        'معرف المعاملة' => 'غير موجود'
                    ];
                }
            }

            // عرض الإحصائيات
            $this->info('📊 إحصائيات معرفات المعاملات:');
            $this->table(
                ['المصدر', 'العدد', 'النسبة'],
                [
                    ['payments.id (مرجع العميل)', $paymentsIdCount, round(($paymentsIdCount / $reverseTransfers->count()) * 100, 2) . '%'],
                    ['transaction_reference', $transactionReferenceCount, round(($transactionReferenceCount / $reverseTransfers->count()) * 100, 2) . '%'],
                    ['payment_requests.id', $paymentRequestCount, round(($paymentRequestCount / $reverseTransfers->count()) * 100, 2) . '%'],
                    ['معرف مؤقت', $tempIdCount, round(($tempIdCount / $reverseTransfers->count()) * 100, 2) . '%'],
                    ['غير محدد', $missingCount, round(($missingCount / $reverseTransfers->count()) * 100, 2) . '%']
                ]
            );

            // عرض التفاصيل
            if (!empty($details)) {
                $this->info('📋 تفاصيل معرفات المعاملات:');
                $this->table(
                    ['ID', 'Order ID', 'المصدر', 'معرف المعاملة'],
                    $details
                );
            }

            // توصيات
            if ($paymentsIdCount == 0) {
                $this->warn('⚠️ لا توجد حوالات عكسية تستخدم payments.id كمرجع العميل');
                $this->info('💡 يوصى بتشغيل الأمر: php artisan reverse-transfer:fix-transaction-ids');
            } elseif ($missingCount > 0) {
                $this->warn("⚠️ يوجد {$missingCount} حوالة عكسية بدون معرف معاملة");
                $this->info('💡 يوصى بتشغيل الأمر: php artisan reverse-transfer:fix-transaction-ids');
            } else {
                $this->info('✅ جميع الحوالات العكسية تحتوي على معرفات معاملات صحيحة');
            }

        } catch (\Exception $e) {
            $this->error('❌ خطأ في فحص payments.id: ' . $e->getMessage());
        }
    }
}
