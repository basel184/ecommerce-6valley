<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReverseTransfer;
use App\Services\ReverseTransferService;
use App\Services\MyFatoorahService;
use App\Services\TabbyService;
use App\Services\TamaraService;

class ProcessReverseTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverse-transfer:process {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'معالجة حوالة عكسية محددة';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        
        try {
            // البحث عن الحوالة العكسية
            $reverseTransfer = ReverseTransfer::find($id);
            
            if (!$reverseTransfer) {
                $this->error("لم يتم العثور على الحوالة العكسية رقم {$id}");
                return 1;
            }
            
            $this->info("تم العثور على الحوالة العكسية رقم {$id}");
            $this->info("الطلب: {$reverseTransfer->order_id}");
            $this->info("المبلغ: {$reverseTransfer->amount}");
            $this->info("طريقة الدفع: {$reverseTransfer->payment_method}");
            $this->info("معرف المعاملة: {$reverseTransfer->original_transaction_id}");
            
            // إنشاء service
            $myFatoorahService = new MyFatoorahService();
            $tabbyService = new TabbyService();
            $tamaraService = new TamaraService();
            
            $service = new ReverseTransferService(
                $myFatoorahService,
                $tabbyService,
                $tamaraService
            );
            
            // معالجة الحوالة العكسية
            $this->info("بدء معالجة الحوالة العكسية...");
            $result = $service->processGatewayReverseTransfer($reverseTransfer);
            
            $this->info("نتيجة المعالجة:");
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            return 0;
            
        } catch (Exception $e) {
            $this->error("خطأ: " . $e->getMessage());
            return 1;
        }
    }
}
