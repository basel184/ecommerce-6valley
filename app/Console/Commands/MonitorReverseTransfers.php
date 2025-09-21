<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReverseTransferService;
use Illuminate\Support\Facades\Log;

class MonitorReverseTransfers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverse-transfers:monitor {--gateway= : Monitor specific gateway only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'مراقبة حالة الحوالات العكسية عبر بوابات الدفع';

    /**
     * Execute the console command.
     */
    public function handle(ReverseTransferService $reverseTransferService)
    {
        $this->info('بدء مراقبة الحوالات العكسية...');

        try {
            $results = $reverseTransferService->monitorGatewayReverseTransfers();
            
            if (empty($results)) {
                $this->info('لا توجد حوالات عكسية تحتاج إلى مراقبة.');
                return 0;
            }

            $this->info('تم فحص ' . count($results) . ' حوالة عكسية.');

            foreach ($results as $result) {
                $this->processResult($result);
            }

            $this->info('تم إكمال مراقبة الحوالات العكسية بنجاح.');
            return 0;

        } catch (\Exception $e) {
            $this->error('خطأ في مراقبة الحوالات العكسية: ' . $e->getMessage());
            Log::error('خطأ في أمر مراقبة الحوالات العكسية: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * معالجة نتيجة فحص الحوالة العكسية
     */
    protected function processResult($result)
    {
        $gateway = $result['gateway'];
        $reverseTransferId = $result['reverse_transfer_id'];
        $gatewayResult = $result['result'];

        if ($gatewayResult['success']) {
            if ($gatewayResult['status'] === 'completed') {
                $this->info("✅ الحوالة العكسية #{$reverseTransferId} عبر {$gateway}: " . $gatewayResult['message']);
            } elseif ($gatewayResult['status'] === 'processing') {
                $this->line("⏳ الحوالة العكسية #{$reverseTransferId} عبر {$gateway}: " . $gatewayResult['message']);
            }
        } else {
            $this->error("❌ الحوالة العكسية #{$reverseTransferId} عبر {$gateway}: " . ($gatewayResult['error'] ?? $gatewayResult['message']));
        }
    }
}


