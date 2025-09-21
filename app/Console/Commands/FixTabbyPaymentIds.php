<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixTabbyPaymentIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:tabby-payment-ids {--dry-run : عرض التغييرات بدون تطبيقها}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إصلاح payment_id في الطلبات الموجودة لـ Tabby';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 بدء إصلاح payment_id في طلبات Tabby...');

        try {
            // البحث عن الطلبات التي تحتاج إصلاح
            $paymentsToFix = DB::table('payment_requests')
                ->where('payment_method', 'tabby')
                ->where('payment_status', 'success')
                ->where(function($query) {
                    $query->whereNull('additional_data')
                          ->orWhere('additional_data', '{}')
                          ->orWhere('additional_data', '[]')
                          ->orWhereRaw("JSON_EXTRACT(additional_data, '$.tabby_payment_id') IS NULL");
                })
                ->get();

            if ($paymentsToFix->isEmpty()) {
                $this->info('✅ جميع الطلبات محدثة بالفعل!');
                return 0;
            }

            $this->info("📋 تم العثور على {$paymentsToFix->count()} طلب يحتاج إصلاح");

            foreach ($paymentsToFix as $payment) {
                $this->processPayment($payment);
            }

            if ($this->option('dry-run')) {
                $this->warn('🔍 تم عرض التغييرات فقط (dry-run)');
            } else {
                $this->info('✅ تم إصلاح جميع الطلبات بنجاح!');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ خطأ في إصلاح الطلبات: ' . $e->getMessage());
            Log::error('FixTabbyPaymentIds Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * معالجة طلب واحد
     */
    protected function processPayment($payment)
    {
        try {
            $this->line("🔍 معالجة الطلب: {$payment->id}");

            // محاولة استخراج payment_id من transaction_reference
            $tabbyPaymentId = $this->extractTabbyPaymentId($payment);

            if (!$tabbyPaymentId) {
                $this->warn("⚠️ لم يتم العثور على payment_id للطلب {$payment->id}");
                return;
            }

            $this->info("✅ تم العثور على payment_id: {$tabbyPaymentId}");

            // تحديث additional_data
            $additionalData = $this->updateAdditionalData($payment, $tabbyPaymentId);

            if ($this->option('dry-run')) {
                $this->line("🔍 سيكون التحديث (dry-run):");
                $this->line("   - tabby_payment_id: {$tabbyPaymentId}");
                $this->line("   - additional_data: " . json_encode($additionalData, JSON_UNESCAPED_UNICODE));
            } else {
                // تطبيق التحديث
                DB::table('payment_requests')
                    ->where('id', $payment->id)
                    ->update([
                        'additional_data' => json_encode($additionalData),
                        'updated_at' => now()
                    ]);

                $this->info("✅ تم تحديث الطلب {$payment->id}");
            }

        } catch (\Exception $e) {
            $this->error("❌ خطأ في معالجة الطلب {$payment->id}: " . $e->getMessage());
            Log::error('FixTabbyPaymentIds Payment Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * استخراج payment_id من transaction_reference
     */
    protected function extractTabbyPaymentId($payment)
    {
        // إذا كان transaction_reference يبدو كـ payment_id من Tabby
        if ($this->isValidTabbyPaymentId($payment->transaction_reference)) {
            return $payment->transaction_reference;
        }

        // البحث في additional_data
        if (!empty($payment->additional_data)) {
            $additionalData = json_decode($payment->additional_data, true);
            
            if (isset($additionalData['tabby_payment_id'])) {
                return $additionalData['tabby_payment_id'];
            }
            
            if (isset($additionalData['payment_id'])) {
                return $additionalData['payment_id'];
            }
        }

        return null;
    }

    /**
     * التحقق من صحة payment_id من Tabby
     */
    protected function isValidTabbyPaymentId($id)
    {
        // Tabby payment_id format: UUID v4
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $id) === 1;
    }

    /**
     * تحديث additional_data
     */
    protected function updateAdditionalData($payment, $tabbyPaymentId)
    {
        $additionalData = [];

        // الحفاظ على البيانات الموجودة
        if (!empty($payment->additional_data)) {
            $existingData = json_decode($payment->additional_data, true);
            if (is_array($existingData)) {
                $additionalData = $existingData;
            }
        }

        // إضافة payment_id الجديد
        $additionalData['tabby_payment_id'] = $tabbyPaymentId;
        $additionalData['payment_id'] = $tabbyPaymentId;
        $additionalData['fixed_at'] = now()->toISOString();
        $additionalData['fix_source'] = 'FixTabbyPaymentIds Command';

        return $additionalData;
    }
}
