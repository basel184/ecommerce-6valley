<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestPaymentGatewayRefunds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:payment-gateway-refunds 
                            {identifier : رقم الطلب أو رقم العملية}
                            {--gateway=tabby : بوابة الدفع (tabby أو myfatoora)}
                            {--amount=100.00 : مبلغ الاسترداد}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'اختبار التحديثات الجديدة في بوابات الدفع لحفظ أرقام العمليات الفعلية';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $identifier = $this->argument('identifier');
        $gateway = $this->option('gateway');
        $amount = $this->option('amount');

        $this->info('🔧 اختبار التحديثات الجديدة في بوابات الدفع...');
        $this->info("📋 المعرف: {$identifier}");
        $this->info("💳 البوابة: {$gateway}");
        $this->info("💰 المبلغ: {$amount}");

        try {
            // البحث عن الدفع
            $payment = $this->findPayment($identifier, $gateway);

            if (!$payment) {
                $this->error("❌ لم يتم العثور على دفع لـ {$gateway} بالمعرف: {$identifier}");
                return 1;
            }

            $this->displayPaymentInfo($payment, $gateway);

            // اختبار البحث عن رقم العملية
            $this->testPaymentIdLookup($payment, $gateway);

            // اختبار محاكاة الاسترداد
            $this->testRefundSimulation($payment, $gateway, $amount);

            $this->info('✅ تم اختبار جميع الوظائف بنجاح!');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ خطأ في الاختبار: ' . $e->getMessage());
            Log::error('TestPaymentGatewayRefunds Error', [
                'identifier' => $identifier,
                'gateway' => $gateway,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }

    /**
     * البحث عن الدفع
     */
    protected function findPayment($identifier, $gateway)
    {
        $this->line("🔍 البحث عن الدفع في {$gateway}...");

        // البحث بـ transaction_reference
        $payment = DB::table('payment_requests')
            ->where('transaction_reference', $identifier)
            ->where('payment_method', $gateway)
            ->where('payment_status', 'success')
            ->first();

        if ($payment) {
            $this->line("   ✅ تم العثور عليه بـ transaction_reference");
            return $payment;
        }

        // البحث بـ attribute_id (رقم الطلب)
        $payment = DB::table('payment_requests')
            ->where('attribute_id', $identifier)
            ->where('payment_method', $gateway)
            ->where('payment_status', 'success')
            ->first();

        if ($payment) {
            $this->line("   ✅ تم العثور عليه بـ attribute_id (رقم الطلب)");
            return $payment;
        }

        // البحث بـ order_ids
        $payment = DB::table('payment_requests')
            ->where('payment_method', $gateway)
            ->where('payment_status', 'success')
            ->where('order_ids', 'like', "%{$identifier}%")
            ->first();

        if ($payment) {
            $this->line("   ✅ تم العثور عليه بـ order_ids");
            return $payment;
        }

        return null;
    }

    /**
     * عرض معلومات الدفع
     */
    protected function displayPaymentInfo($payment, $gateway)
    {
        $this->line("\n📋 معلومات الدفع:");
        $this->line("   - Payment ID: {$payment->id}");
        $this->line("   - Payment Method: {$payment->payment_method}");
        $this->line("   - Payment Status: {$payment->payment_status}");
        $this->line("   - Amount: {$payment->payment_amount}");
        $this->line("   - Transaction Reference: " . ($payment->transaction_reference ?? 'NULL'));
        $this->line("   - Attribute ID: " . ($payment->attribute_id ?? 'NULL'));
        $this->line("   - Order IDs: " . ($payment->order_ids ?? 'NULL'));

        if (!empty($payment->additional_data)) {
            $additionalData = json_decode($payment->additional_data, true);
            $this->line("   - Additional Data:");
            
            if ($gateway === 'tabby') {
                $this->line("     * tabby_payment_id: " . ($additionalData['tabby_payment_id'] ?? 'NULL'));
                $this->line("     * payment_id: " . ($additionalData['payment_id'] ?? 'NULL'));
            } elseif ($gateway === 'myfatoora') {
                $this->line("     * myfatoora_transaction_id: " . ($additionalData['myfatoora_transaction_id'] ?? 'NULL'));
                $this->line("     * myfatoora_invoice_id: " . ($additionalData['myfatoora_invoice_id'] ?? 'NULL'));
            }
            
            $this->line("     * payment_confirmed_at: " . ($additionalData['payment_confirmed_at'] ?? 'NULL'));
        }
    }

    /**
     * اختبار البحث عن رقم العملية
     */
    protected function testPaymentIdLookup($payment, $gateway)
    {
        $this->line("\n🔍 اختبار البحث عن رقم العملية...");

        if ($gateway === 'tabby') {
            $this->testTabbyPaymentIdLookup($payment);
        } elseif ($gateway === 'myfatoora') {
            $this->testMyFatooraPaymentIdLookup($payment);
        }
    }

    /**
     * اختبار البحث عن رقم العملية في Tabby
     */
    protected function testTabbyPaymentIdLookup($payment)
    {
        $this->line("   💳 اختبار Tabby...");

        // البحث عن tabby_payment_id في additional_data
        if (!empty($payment->additional_data)) {
            $additionalData = json_decode($payment->additional_data, true);
            
            if (isset($additionalData['tabby_payment_id'])) {
                $this->line("     ✅ tabby_payment_id: {$additionalData['tabby_payment_id']}");
                
                // التحقق من أن transaction_reference يحتوي على نفس القيمة
                if ($payment->transaction_reference === $additionalData['tabby_payment_id']) {
                    $this->line("     ✅ transaction_reference متطابق مع tabby_payment_id");
                } else {
                    $this->warn("     ⚠️ transaction_reference غير متطابق مع tabby_payment_id");
                }
            } else {
                $this->warn("     ⚠️ tabby_payment_id غير موجود في additional_data");
            }
        } else {
            $this->warn("     ⚠️ additional_data فارغ");
        }
    }

    /**
     * اختبار البحث عن رقم العملية في My Fatoora
     */
    protected function testMyFatooraPaymentIdLookup($payment)
    {
        $this->line("   💳 اختبار My Fatoora...");

        // البحث عن myfatoora_transaction_id في additional_data
        if (!empty($payment->additional_data)) {
            $additionalData = json_decode($payment->additional_data, true);
            
            if (isset($additionalData['myfatoora_transaction_id'])) {
                $this->line("     ✅ myfatoora_transaction_id: {$additionalData['myfatoora_transaction_id']}");
                
                // التحقق من أن transaction_reference يحتوي على نفس القيمة
                if ($payment->transaction_reference === $additionalData['myfatoora_transaction_id']) {
                    $this->line("     ✅ transaction_reference متطابق مع myfatoora_transaction_id");
                } else {
                    $this->warn("     ⚠️ transaction_reference غير متطابق مع myfatoora_transaction_id");
                }
            } else {
                $this->warn("     ⚠️ myfatoora_transaction_id غير موجود في additional_data");
            }
        } else {
            $this->warn("     ⚠️ additional_data فارغ");
        }
    }

    /**
     * اختبار محاكاة الاسترداد
     */
    protected function testRefundSimulation($payment, $gateway, $amount)
    {
        $this->line("\n💰 اختبار محاكاة الاسترداد...");

        if ($gateway === 'tabby') {
            $this->testTabbyRefundSimulation($payment, $amount);
        } elseif ($gateway === 'myfatoora') {
            $this->testMyFatooraRefundSimulation($payment, $amount);
        }
    }

    /**
     * اختبار محاكاة الاسترداد في Tabby
     */
    protected function testTabbyRefundSimulation($payment, $amount)
    {
        $this->line("   💳 محاكاة استرداد Tabby...");

        // محاكاة بيانات الاسترداد
        $refundData = [
            'original_transaction_id' => $payment->attribute_id ?? $payment->transaction_reference,
            'amount' => $amount,
            'reason' => 'Test refund from command'
        ];

        $this->line("     📝 بيانات الاسترداد:");
        $this->line("       - original_transaction_id: {$refundData['original_transaction_id']}");
        $this->line("       - amount: {$refundData['amount']}");
        $this->line("       - reason: {$refundData['reason']}");

        // محاكاة البحث عن payment_id
        $tabbyPaymentId = $this->simulateTabbyPaymentIdLookup($payment);
        
        if ($tabbyPaymentId) {
            $this->line("     ✅ تم العثور على Tabby payment_id: {$tabbyPaymentId}");
            $this->line("     🔗 رابط الاسترداد: https://api.tabby.ai/api/v2/payments/{$tabbyPaymentId}/refunds");
        } else {
            $this->warn("     ⚠️ لم يتم العثور على Tabby payment_id");
        }
    }

    /**
     * اختبار محاكاة الاسترداد في My Fatoora
     */
    protected function testMyFatooraRefundSimulation($payment, $amount)
    {
        $this->line("   💳 محاكاة استرداد My Fatoora...");

        // محاكاة بيانات الاسترداد
        $refundData = [
            'original_transaction_id' => $payment->attribute_id ?? $payment->transaction_reference,
            'amount' => $amount,
            'reason' => 'Test refund from command'
        ];

        $this->line("     📝 بيانات الاسترداد:");
        $this->line("       - original_transaction_id: {$refundData['original_transaction_id']}");
        $this->line("       - amount: {$refundData['amount']}");
        $this->line("       - reason: {$refundData['reason']}");

        // محاكاة البحث عن transaction_id
        $myFatooraTransactionId = $this->simulateMyFatooraTransactionIdLookup($payment);
        
        if ($myFatooraTransactionId) {
            $this->line("     ✅ تم العثور على My Fatoora transaction_id: {$myFatooraTransactionId}");
            $this->line("     📚 راجع دليل My Fatoora للاسترداد: https://docs.myfatoorah.com/docs/gateway-integration");
        } else {
            $this->warn("     ⚠️ لم يتم العثور على My Fatoora transaction_id");
        }
    }

    /**
     * محاكاة البحث عن Tabby payment_id
     */
    protected function simulateTabbyPaymentIdLookup($payment)
    {
        if (!empty($payment->additional_data)) {
            $additionalData = json_decode($payment->additional_data, true);
            return $additionalData['tabby_payment_id'] ?? $additionalData['payment_id'] ?? null;
        }
        return null;
    }

    /**
     * محاكاة البحث عن My Fatoora transaction_id
     */
    protected function simulateMyFatooraTransactionIdLookup($payment)
    {
        if (!empty($payment->additional_data)) {
            $additionalData = json_decode($payment->additional_data, true);
            return $additionalData['myfatoora_transaction_id'] ?? null;
        }
        return null;
    }
}





