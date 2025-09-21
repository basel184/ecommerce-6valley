<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestMyFatoorahRefund extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:myfatoorah-refund {transaction_id} {amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'اختبار الاسترداد مع MyFatoorah';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('transaction_id');
        $amount = $this->argument('amount');

        $this->info("🔍 اختبار الاسترداد مع MyFatoorah");
        $this->info("معرف المعاملة: {$transactionId}");
        $this->info("المبلغ: {$amount}");

        try {
            $this->info("📡 إرسال طلب الاسترداد...");
            
            $refundPayload = [
                'Key' => $transactionId,
                'KeyType' => 'InvoiceId',
                'Amount' => $amount,
                'Reason' => 'Customer request',
                'Comment' => 'Refund processed via system'
            ];

            $this->info("📊 بيانات الاسترداد:");
            $this->table(['المجال', 'القيمة'], [
                ['Key', $refundPayload['Key']],
                ['KeyType', $refundPayload['KeyType']],
                ['Amount', $refundPayload['Amount']],
                ['Reason', $refundPayload['Reason']],
                ['Comment', $refundPayload['Comment']]
            ]);

            $this->info("🌐 إرسال الطلب إلى: " . config('myfatoorah.live_api_url') . '/v2/MakeRefund');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('myfatoorah.api_key'),
                'Content-Type' => 'application/json',
            ])->post(config('myfatoorah.live_api_url') . '/v2/MakeRefund', $refundPayload);

            $this->info("📊 استجابة MyFatoorah:");
            $this->info("Status Code: " . $response->status());
            $this->info("Headers: " . json_encode($response->headers(), JSON_PRETTY_PRINT));
            $this->info("Body: " . $response->body());

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['IsSuccess']) && $responseData['IsSuccess']) {
                    $this->info("✅ نجح الاسترداد!");
                    $this->info("Refund ID: " . ($responseData['Data']['RefundId'] ?? 'غير محدد'));
                    $this->info("الاستجابة: " . json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                } else {
                    $this->error("❌ فشل الاسترداد من MyFatoorah");
                    $this->error("الرسالة: " . ($responseData['Message'] ?? 'رسالة خطأ غير محددة'));
                    $this->error("الاستجابة: " . json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            } else {
                $this->error("❌ خطأ في الاتصال مع MyFatoorah");
                $this->error("Status Code: " . $response->status());
                $this->error("Response Body: " . $response->body());
            }

        } catch (\Exception $e) {
            $this->error("❌ فشل الاسترداد: " . $e->getMessage());
            $this->error("Stack Trace: " . $e->getTraceAsString());
        }
    }
}
