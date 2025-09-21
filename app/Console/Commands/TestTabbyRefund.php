<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TabbyService;
use App\Services\ReverseTransferService;
use Illuminate\Support\Facades\Log;

class TestTabbyRefund extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:tabby-refund {--payment-id=} {--amount=} {--reason=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'اختبار معالجة الاسترداد عبر Tabby';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 بدء اختبار معالجة الاسترداد عبر Tabby...');
        
        try {
            // إنشاء خدمة Tabby
            $tabbyService = new TabbyService();
            
            // بيانات اختبار الاسترداد
            $refundData = [
                'amount' => $this->option('amount') ?? 31.0,
                'original_transaction_id' => $this->option('payment-id') ?? '01986099-9a40-76b2-8d52-4ac85fcf8712',
                'reason' => $this->option('reason') ?? 'اختبار معالجة الاسترداد',
                'reference' => 'REF_TEST_' . time(),
                'customer_email' => 'test@example.com',
                'customer_phone' => '966566724846',
                'customer_name' => 'عميل اختبار'
            ];
            
            $this->info('📋 بيانات الاسترداد:');
            $this->table(['الحقل', 'القيمة'], [
                ['المبلغ', $refundData['amount']],
                ['معرف المعاملة الأصلية', $refundData['original_transaction_id']],
                ['السبب', $refundData['reason']],
                ['المرجع', $refundData['reference']]
            ]);
            
            $this->info('🔧 اختبار خدمة Tabby...');
            
            // اختبار معالجة الاسترداد
            $result = $tabbyService->processRefund($refundData);
            
            if ($result['success']) {
                $this->info('✅ نجح الاسترداد عبر Tabby!');
                $this->table(['الحقل', 'القيمة'], [
                    ['معرف المعاملة', $result['gateway_transaction_id']],
                    ['الرسالة', $result['message']],
                    ['الاستجابة', json_encode($result['response'], JSON_UNESCAPED_UNICODE)]
                ]);
                
                // عرض رابط إلغاء الطلب في Tabby إذا كان متوفراً
                if (isset($result['response']['tabby_cancel_url']) && $result['response']['tabby_cancel_url'] !== 'N/A') {
                    $this->info('🔗 رابط إلغاء الطلب في Tabby:');
                    $this->line($result['response']['tabby_cancel_url']);
                }
                
                // عرض نتيجة محاولة الإلغاء
                if (isset($result['response']['cancellation_result'])) {
                    $this->info('🔄 نتيجة محاولة إلغاء الطلب:');
                    $cancellation = $result['response']['cancellation_result'];
                    
                    if ($cancellation['success']) {
                        $this->info('✅ تم إلغاء الطلب في Tabby بنجاح!');
                    } else {
                        $this->warn('⚠️ فشل في إلغاء الطلب عبر API');
                        $this->line('الرسالة: ' . $cancellation['message']);
                        
                        if (isset($cancellation['manual_cancel_url'])) {
                            $this->info('🔗 رابط الإلغاء اليدوي:');
                            $this->line($cancellation['manual_cancel_url']);
                        }
                        
                        if (isset($cancellation['note'])) {
                            $this->line('ملاحظة: ' . $cancellation['note']);
                        }
                    }
                }
                
                // اختبار التحقق من حالة الاسترداد
                $this->info('🔍 اختبار التحقق من حالة الاسترداد...');
                $statusResult = $tabbyService->getRefundStatus($result['gateway_transaction_id']);
                
                if ($statusResult['success']) {
                    $this->info('✅ نجح التحقق من حالة الاسترداد!');
                    $this->table(['الحقل', 'القيمة'], [
                        ['الحالة', $statusResult['status']],
                        ['البيانات', json_encode($statusResult['data'], JSON_UNESCAPED_UNICODE)]
                    ]);
                } else {
                    $this->warn('⚠️ فشل في التحقق من حالة الاسترداد: ' . $statusResult['error']);
                }
                
            } else {
                $this->error('❌ فشل الاسترداد عبر Tabby: ' . $result['error']);
                
                // عرض تفاصيل الخطأ
                if (isset($result['response']['error'])) {
                    $this->error('تفاصيل الخطأ: ' . $result['response']['error']);
                }
            }
            
            // اختبار ReverseTransferService
            $this->info('🔧 اختبار ReverseTransferService...');
            
            $reverseTransferService = new ReverseTransferService(
                app(\App\Services\MyFatoorahService::class),
                $tabbyService,
                app(\App\Services\TamaraService::class)
            );
            
            // اختبار معالجة الاسترداد عبر البوابة
            try {
                $reflection = new \ReflectionClass($reverseTransferService);
                $method = $reflection->getMethod('processRefundByGateway');
                $method->setAccessible(true);
                $gatewayResult = $method->invoke($reverseTransferService, 'tabby', $tabbyService, $refundData);
            } catch (\Exception $e) {
                $this->warn('⚠️ فشل في اختبار ReverseTransferService: ' . $e->getMessage());
                $gatewayResult = ['success' => false, 'error' => $e->getMessage()];
            }
            
            if ($gatewayResult['success']) {
                $this->info('✅ نجح الاسترداد عبر ReverseTransferService!');
                $this->table(['الحقل', 'القيمة'], [
                    ['معرف المعاملة', $gatewayResult['gateway_transaction_id']],
                    ['الرسالة', $gatewayResult['message']]
                ]);
            } else {
                $this->warn('⚠️ فشل في ReverseTransferService: ' . $gatewayResult['error']);
            }
            
        } catch (\Exception $e) {
            $this->error('❌ خطأ في الاختبار: ' . $e->getMessage());
            Log::error('Tabby Refund Test Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        $this->info('🏁 انتهى اختبار معالجة الاسترداد عبر Tabby');
        
        return 0;
    }
}
