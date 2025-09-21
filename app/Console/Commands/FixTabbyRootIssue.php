<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentRequest;
use App\Models\Cart;
use App\Utils\CartManager;
use App\Utils\OrderManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixTabbyRootIssue extends Command
{
    protected $signature = 'fix:tabby-root-issue {payment_id?} {--create-missing} {--force} {--dry-run}';
    protected $description = 'حل المشكلة الأساسية في Tabby - عدم إنشاء الدفع في قاعدة البيانات';

    public function handle()
    {
        $paymentId = $this->argument('payment_id');
        $createMissing = $this->option('create-missing');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info('🔧 حل المشكلة الأساسية في Tabby Payment');
        $this->newLine();

        if ($paymentId) {
            $this->fixSpecificPayment($paymentId, $createMissing, $force, $dryRun);
        } else {
            $this->fixAllMissingPayments($createMissing, $force, $dryRun);
        }
    }

    private function fixSpecificPayment($paymentId, $createMissing, $force, $dryRun)
    {
        $this->info("🔍 فحص الدفع المحدد: {$paymentId}");

        // البحث عن الدفع
        $payment = PaymentRequest::where('id', $paymentId)->first();
        
        if (!$payment) {
            $this->error("❌ الدفع غير موجود في قاعدة البيانات: {$paymentId}");
            
            if ($createMissing) {
                $this->info("🔧 محاولة إنشاء الدفع المفقود...");
                $this->createMissingPayment($paymentId, $dryRun);
            }
            return;
        }

        $this->displayPaymentInfo($payment);
        $this->fixPaymentIssues($payment, $force, $dryRun);
    }

    private function fixAllMissingPayments($createMissing, $force, $dryRun)
    {
        $this->info('🔍 البحث عن الدفعات المفقودة...');

        // البحث عن الدفعات التي تم إنشاؤها في السجلات ولكنها غير موجودة في قاعدة البيانات
        $missingPayments = $this->findMissingPayments();
        
        if (empty($missingPayments)) {
            $this->info('✅ لا توجد دفعات مفقودة');
            return;
        }

        $this->info("📊 تم العثور على " . count($missingPayments) . " دفعة مفقودة");

        foreach ($missingPayments as $missingPayment) {
            $this->newLine();
            $this->info("🔧 معالجة الدفع المفقود: {$missingPayment['payment_id']}");
            
            if ($createMissing) {
                $this->createMissingPayment($missingPayment['payment_id'], $dryRun, $missingPayment);
            } else {
                $this->warn("⚠️ استخدم --create-missing لإنشاء الدفع المفقود");
            }
        }
    }

    private function findMissingPayments()
    {
        $missingPayments = [];
        
        // البحث في السجلات عن payment_id التي لا توجد في قاعدة البيانات
        $logFile = storage_path('logs/laravel.log');
        
        if (!file_exists($logFile)) {
            return $missingPayments;
        }

        $logContent = file_get_contents($logFile);
        
        // البحث عن patterns لـ payment_id في السجلات
        preg_match_all('/"payment_id":"([a-f0-9\-]+)"/', $logContent, $matches);
        
        if (!empty($matches[1])) {
            $uniquePaymentIds = array_unique($matches[1]);
            
            foreach ($uniquePaymentIds as $paymentId) {
                // التحقق من وجود الدفع في قاعدة البيانات
                $payment = PaymentRequest::where('id', $paymentId)->first();
                
                if (!$payment) {
                    // البحث عن معلومات إضافية في السجلات
                    $paymentInfo = $this->extractPaymentInfoFromLogs($paymentId, $logContent);
                    
                    $missingPayments[] = [
                        'payment_id' => $paymentId,
                        'info' => $paymentInfo
                    ];
                }
            }
        }

        return $missingPayments;
    }

    private function extractPaymentInfoFromLogs($paymentId, $logContent)
    {
        $info = [
            'payment_id' => $paymentId,
            'session_id' => null,
            'amount' => null,
            'customer_id' => null,
            'created_at' => null
        ];

        // البحث عن session_id
        preg_match("/Tabby Payment Redirect.*payment_id.*{$paymentId}.*session_id.*?([a-zA-Z0-9\-_]+)/", $logContent, $sessionMatches);
        if (!empty($sessionMatches[1])) {
            $info['session_id'] = $sessionMatches[1];
        }

        // البحث عن المبلغ
        preg_match("/Tabby Payment Redirect.*payment_id.*{$paymentId}.*payment_amount.*?([0-9\.]+)/", $logContent, $amountMatches);
        if (!empty($amountMatches[1])) {
            $info['amount'] = $amountMatches[1];
        }

        // البحث عن customer_id
        preg_match("/customer_id.*?([0-9]+).*payment_id.*{$paymentId}/", $logContent, $customerMatches);
        if (!empty($customerMatches[1])) {
            $info['customer_id'] = $customerMatches[1];
        }

        return $info;
    }

    private function createMissingPayment($paymentId, $dryRun, $paymentInfo = null)
    {
        try {
            if (!$dryRun) {
                DB::beginTransaction();
            }

            $this->info("🔧 إنشاء الدفع المفقود: {$paymentId}");

            // إنشاء بيانات الدفع الأساسية
            $paymentData = [
                'id' => $paymentId,
                'payment_method' => 'tabby',
                'payment_status' => 'pending',
                'is_paid' => 0,
                'currency_code' => 'SAR',
                'created_at' => now(),
                'updated_at' => now()
            ];

            // إضافة المعلومات المستخرجة من السجلات
            if ($paymentInfo) {
                if ($paymentInfo['amount']) {
                    $paymentData['payment_amount'] = $paymentInfo['amount'];
                }
                if ($paymentInfo['session_id']) {
                    $paymentData['transaction_reference'] = $paymentInfo['session_id'];
                }
                if ($paymentInfo['customer_id']) {
                    $paymentData['payer_id'] = $paymentInfo['customer_id'];
                }
            }

            // إنشاء بيانات العميل الافتراضية
            $customerInfo = [
                'name' => 'Customer',
                'email' => 'customer@example.com',
                'phone' => '0500000000'
            ];

            $paymentData['payer_information'] = json_encode($customerInfo);
            $paymentData['receiver_information'] = json_encode(['name' => 'Store', 'image' => 'store.png']);
            $paymentData['additional_data'] = json_encode([]);
            $paymentData['success_hook'] = 'digital_payment_success';
            $paymentData['failure_hook'] = 'digital_payment_fail';

            if (!$dryRun) {
                $payment = PaymentRequest::create($paymentData);
                
                $this->info("✅ تم إنشاء الدفع بنجاح");
                $this->displayPaymentInfo($payment);
                
                DB::commit();
            } else {
                $this->info("🧪 في وضع الاختبار - سيتم إنشاء الدفع");
                $this->table(
                    ['الحقل', 'القيمة'],
                    [
                        ['Payment ID', $paymentData['id']],
                        ['Payment Method', $paymentData['payment_method']],
                        ['Amount', $paymentData['payment_amount'] ?? 'NULL'],
                        ['Session ID', $paymentData['transaction_reference'] ?? 'NULL'],
                        ['Customer ID', $paymentData['payer_id'] ?? 'NULL'],
                    ]
                );
            }

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            $this->error("❌ خطأ في إنشاء الدفع: " . $e->getMessage());
            Log::error('FixTabbyRootIssue: Error creating payment', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function fixPaymentIssues($payment, $force, $dryRun)
    {
        $this->info('🔧 إصلاح مشاكل الدفع...');

        $issues = $this->identifyPaymentIssues($payment);
        
        if (empty($issues)) {
            $this->info('✅ لا توجد مشاكل في هذا الدفع');
            return;
        }

        $this->warn('⚠️ تم العثور على مشاكل:');
        foreach ($issues as $issue) {
            $this->line("   - {$issue}");
        }

        if (!$force && !$this->confirm('هل تريد إصلاح هذه المشاكل؟')) {
            return;
        }

        try {
            if (!$dryRun) {
                DB::beginTransaction();
            }

            // إصلاح المشاكل
            foreach ($issues as $issue) {
                $this->fixSpecificIssue($payment, $issue, $dryRun);
            }

            if (!$dryRun) {
                $payment->save();
                DB::commit();
                $this->info('✅ تم إصلاح جميع المشاكل بنجاح');
            } else {
                $this->info('🧪 في وضع الاختبار - سيتم إصلاح المشاكل');
            }

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            $this->error("❌ خطأ في إصلاح المشاكل: " . $e->getMessage());
        }
    }

    private function identifyPaymentIssues($payment)
    {
        $issues = [];

        // التحقق من وجود session_id
        if (empty($payment->transaction_reference)) {
            $issues[] = 'Session ID غير موجود';
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

    private function fixSpecificIssue($payment, $issue, $dryRun)
    {
        switch ($issue) {
            case 'Session ID غير موجود':
                if (!$dryRun) {
                    $payment->transaction_reference = 'FIXED_' . time();
                }
                $this->info("🔧 إصلاح: إضافة Session ID");
                break;

            case 'حالة الدفع ليست ناجحة':
                if (!$dryRun) {
                    $payment->payment_status = 'success';
                }
                $this->info("🔧 إصلاح: تحديث حالة الدفع إلى نجاح");
                break;

            case 'الدفع غير محدد كمدفوع':
                if (!$dryRun) {
                    $payment->is_paid = 1;
                }
                $this->info("🔧 إصلاح: تحديد الدفع كمدفوع");
                break;

            case 'لا توجد طلبات مرتبطة':
                $this->info("🔧 إصلاح: إنشاء الطلبات المرتبطة");
                $this->createRelatedOrders($payment, $dryRun);
                break;
        }
    }

    private function createRelatedOrders($payment, $dryRun)
    {
        try {
            $additionalData = json_decode($payment->additional_data, true);
            $customerId = $additionalData['customer_id'] ?? $payment->payer_id;

            if (!$customerId) {
                $this->warn("⚠️ لا يمكن العثور على معرف العميل");
                return;
            }

            // البحث عن منتجات في السلة
            $cartItems = Cart::where('customer_id', $customerId)
                ->where('is_checked', 1)
                ->get();

            if ($cartItems->isEmpty()) {
                $this->warn("⚠️ لا توجد منتجات في السلة");
                return;
            }

            if (!$dryRun) {
                // إنشاء الطلب
                $orderData = [
                    'payment_method' => $payment->payment_method,
                    'order_status' => 'confirmed',
                    'payment_status' => 'paid',
                    'transaction_ref' => $payment->transaction_reference,
                    'customer_id' => $customerId,
                    'order_amount' => $payment->payment_amount,
                    'paid_amount' => $payment->payment_amount,
                ];

                $orderId = OrderManager::generate_order($orderData);
                
                // تحديث الدفع بـ order_ids
                $payment->order_ids = json_encode([$orderId]);
                
                $this->info("✅ تم إنشاء الطلب: {$orderId}");
            } else {
                $this->info("🧪 في وضع الاختبار - سيتم إنشاء الطلب");
            }

        } catch (\Exception $e) {
            $this->error("❌ خطأ في إنشاء الطلبات: " . $e->getMessage());
        }
    }

    private function displayPaymentInfo($payment)
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