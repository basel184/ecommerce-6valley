<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReverseTransferService;
use App\Models\User;

class CreateReverseTransferByPhone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverse-transfer:create-by-phone 
                            {phone : رقم الهاتف}
                            {amount : المبلغ}
                            {reason : سبب الحوالة العكسية}
                            {--customer-id= : معرف العميل (اختياري)}
                            {--payment-method=customer_wallet : طريقة الدفع}
                            {--payment-gateway= : بوابة الدفع (اختياري)}
                            {--refund-reason-code=CUSTOMER_REQUEST : رمز سبب الاسترداد}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إنشاء حوالة عكسية برقم الهاتف والمبلغ';

    /**
     * Execute the console command.
     */
    public function handle(ReverseTransferService $reverseTransferService)
    {
        $phone = $this->argument('phone');
        $amount = (float) $this->argument('amount');
        $reason = $this->argument('reason');
        $customerId = $this->option('customer-id');
        $paymentMethod = $this->option('payment-method');
        $paymentGateway = $this->option('payment-gateway');
        $refundReasonCode = $this->option('refund-reason-code');

        $this->info("🔄 إنشاء حوالة عكسية جديدة");
        $this->info("📱 رقم الهاتف: {$phone}");
        $this->info("💰 المبلغ: {$amount} ريال");
        $this->info("📝 السبب: {$reason}");

        try {
            // البحث عن العميل إذا لم يتم تحديد معرف العميل
            if (!$customerId) {
                $customer = $this->findCustomerByPhone($phone);
                if (!$customer) {
                    $this->error("❌ لم يتم العثور على عميل بهذا الرقم الهاتف");
                    return 1;
                }
                $customerId = $customer->id;
                $this->info("👤 العميل: {$customer->f_name} {$customer->l_name} (ID: {$customerId})");
            }

            // إعداد بيانات الحوالة العكسية
            $data = [
                'customer_phone' => $phone,
                'amount' => $amount,
                'reason' => $reason,
                'customer_id' => $customerId,
                'payment_method' => $paymentMethod,
                'payment_gateway' => $paymentGateway ?: null,
                'refund_reason_code' => $refundReasonCode
            ];

            // التحقق من صحة البيانات
            $this->info("🔍 التحقق من صحة البيانات...");
            $validation = $reverseTransferService->validateReverseTransferData($data);

            if (!$validation['is_valid']) {
                $this->error("❌ بيانات غير صحيحة:");
                foreach ($validation['errors'] as $error) {
                    $this->error("   - {$error}");
                }
                return 1;
            }

            if (!empty($validation['warnings'])) {
                $this->warn("⚠️ تحذيرات:");
                foreach ($validation['warnings'] as $warning) {
                    $this->warn("   - {$warning}");
                }
                
                if (!$this->confirm('هل تريد المتابعة مع هذه التحذيرات؟')) {
                    $this->info("❌ تم إلغاء العملية");
                    return 0;
                }
            }

            // إنشاء الحوالة العكسية
            $this->info("✅ البيانات صحيحة، جاري إنشاء الحوالة العكسية...");
            $reverseTransfer = $reverseTransferService->createReverseTransfer($data);

            $this->info("🎉 تم إنشاء الحوالة العكسية بنجاح!");
            $this->table(
                ['المعرف', 'معرف الطلب', 'المبلغ', 'الحالة', 'تاريخ الإنشاء'],
                [[
                    $reverseTransfer->id,
                    $reverseTransfer->order_id,
                    $reverseTransfer->amount . ' ريال',
                    $reverseTransfer->status,
                    $reverseTransfer->created_at->format('Y-m-d H:i:s')
                ]]
            );

            // عرض خيارات المعالجة
            $this->displayProcessingOptions($reverseTransferService, $reverseTransfer);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ خطأ: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * البحث عن العميل برقم الهاتف
     */
    protected function findCustomerByPhone($phone)
    {
        // تنظيف رقم الهاتف
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // البحث عن العميل
        $customer = User::where('phone', 'LIKE', '%' . $cleanPhone . '%')
            ->orWhere('phone', 'LIKE', '%966' . $cleanPhone . '%')
            ->orWhere('phone', 'LIKE', '%+966' . $cleanPhone . '%')
            ->first();

        return $customer;
    }

    /**
     * عرض خيارات المعالجة
     */
    protected function displayProcessingOptions($reverseTransferService, $reverseTransfer)
    {
        $this->info("\n🔄 خيارات المعالجة:");
        
        if ($reverseTransfer->isGatewayPayment()) {
            $this->info("1️⃣ معالجة عبر بوابة الدفع ({$reverseTransfer->payment_gateway})");
            $this->info("2️⃣ معالجة محلية");
            
            $choice = $this->choice('اختر طريقة المعالجة:', [
                'gateway' => 'بوابة الدفع',
                'local' => 'محلية',
                'skip' => 'تخطي المعالجة'
            ], 'skip');

            switch ($choice) {
                case 'gateway':
                    $this->processGatewayReverseTransfer($reverseTransferService, $reverseTransfer);
                    break;
                case 'local':
                    $this->processLocalReverseTransfer($reverseTransferService, $reverseTransfer);
                    break;
                default:
                    $this->info("⏭️ تم تخطي المعالجة");
            }
        } else {
            $this->info("💳 معالجة محلية (طريقة الدفع: {$reverseTransfer->payment_method})");
            
            if ($this->confirm('هل تريد معالجة الحوالة العكسية الآن؟')) {
                $this->processLocalReverseTransfer($reverseTransferService, $reverseTransfer);
            }
        }
    }

    /**
     * معالجة الحوالة العكسية عبر بوابة الدفع
     */
    protected function processGatewayReverseTransfer($reverseTransferService, $reverseTransfer)
    {
        try {
            $this->info("🔄 معالجة الحوالة العكسية عبر بوابة الدفع...");
            $result = $reverseTransferService->processGatewayReverseTransfer($reverseTransfer);

            if ($result['success']) {
                $this->info("✅ " . $result['message']);
            } else {
                $this->error("❌ " . $result['error']);
            }

        } catch (\Exception $e) {
            $this->error("❌ خطأ في المعالجة: " . $e->getMessage());
        }
    }

    /**
     * معالجة الحوالة العكسية محلياً
     */
    protected function processLocalReverseTransfer($reverseTransferService, $reverseTransfer)
    {
        try {
            $this->info("🔄 معالجة الحوالة العكسية محلياً...");
            $result = $reverseTransferService->processLocalReverseTransfer($reverseTransfer);

            if ($result['success']) {
                $this->info("✅ " . $result['message']);
                $this->info("🆔 معرف المعاملة: " . $result['transaction_id']);
            } else {
                $this->error("❌ " . $result['error']);
            }

        } catch (\Exception $e) {
            $this->error("❌ خطأ في المعالجة: " . $e->getMessage());
        }
    }
}
