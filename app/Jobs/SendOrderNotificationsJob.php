<?php

namespace App\Jobs;

use App\Services\OrderNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;
    protected $paymentData;

    /**
     * Create a new job instance.
     */
    public function __construct($orderId, $paymentData = null)
    {
        $this->orderId = $orderId;
        $this->paymentData = $paymentData;
    }

    /**
     * Execute the job.
     */
    public function handle(OrderNotificationService $notificationService): void
    {
        try {
            Log::info('Starting order notifications job', [
                'order_id' => $this->orderId,
                'has_payment_data' => !empty($this->paymentData)
            ]);

            $result = $notificationService->sendOrderNotifications($this->orderId, $this->paymentData);
            
            if ($result) {
                Log::info('Order notifications sent successfully', ['order_id' => $this->orderId]);
            } else {
                Log::warning('Order notifications failed', ['order_id' => $this->orderId]);
            }
        } catch (\Exception $e) {
            Log::error('Order notifications job failed', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Order notifications job permanently failed', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage()
        ]);
    }
}
