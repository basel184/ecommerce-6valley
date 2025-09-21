<?php

namespace App\Jobs;

use App\Models\Order;
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
    public $tries = 3;
    public $backoff = [10, 30, 60]; // seconds

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
    public function handle(OrderNotificationService $notificationService)
    {
        Log::info('Starting order notifications job', [
            'order_id' => $this->orderId,
            'attempt' => $this->attempts(),
            'has_payment_data' => !is_null($this->paymentData)
        ]);

        try {
            // التحقق من وجود الطلب مع طرق متعددة
            $order = $this->findOrder($this->orderId);
            
            if (!$order) {
                Log::error('Order not found for notifications', [
                    'order_id' => $this->orderId,
                    'attempt' => $this->attempts()
                ]);
                
                // إذا كانت هذه المحاولة الأخيرة، لا نريد إعادة المحاولة
                if ($this->attempts() >= $this->tries) {
                    Log::warning('Order notifications failed after all attempts', [
                        'order_id' => $this->orderId
                    ]);
                    return;
                }
                
                // إعادة رمي الاستثناء لإعادة المحاولة
                throw new \Exception("Order {$this->orderId} not found");
            }

            // إرسال الإشعارات
            $result = $notificationService->sendOrderNotifications($this->orderId, $this->paymentData);
            
            if ($result) {
                Log::info('Order notifications sent successfully', [
                    'order_id' => $this->orderId,
                    'attempt' => $this->attempts()
                ]);
            } else {
                throw new \Exception('Notification service returned false');
            }
            
        } catch (\Exception $e) {
            Log::error('Order notifications job failed', [
                'order_id' => $this->orderId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // إعادة رمي الاستثناء لإعادة المحاولة
            throw $e;
        }
    }

    /**
     * البحث عن الطلب بطرق متعددة
     */
    protected function findOrder($orderId)
    {
        // Method 1: البحث المباشر
        $order = Order::with(['customer', 'orderDetails.product'])->find($orderId);
        if ($order) {
            return $order;
        }

        // Method 2: البحث برقم الطلب
        $order = Order::with(['customer', 'orderDetails.product'])
            ->where('order_number', $orderId)
            ->first();
        if ($order) {
            return $order;
        }

        // Method 3: البحث في payment_requests
        $paymentRequest = \DB::table('payment_requests')
            ->where('attribute_id', $orderId)
            ->orWhere('id', $orderId)
            ->first();
            
        if ($paymentRequest && isset($paymentRequest->attribute_id)) {
            $order = Order::with(['customer', 'orderDetails.product'])
                ->find($paymentRequest->attribute_id);
            if ($order) {
                return $order;
            }
        }

        // Method 4: البحث في الطلبات الحديثة (آخر ساعة)
        if ($this->paymentData && isset($this->paymentData['customer_id'])) {
            $order = Order::with(['customer', 'orderDetails.product'])
                ->where('customer_id', $this->paymentData['customer_id'])
                ->where('created_at', '>=', now()->subHour())
                ->latest()
                ->first();
            if ($order) {
                return $order;
            }
        }

        return null;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Order notifications job failed permanently', [
            'order_id' => $this->orderId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);
    }
}
