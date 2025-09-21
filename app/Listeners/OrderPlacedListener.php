<?php

namespace App\Listeners;

use App\Events\OrderPlacedEvent;
use App\Traits\EmailTemplateTrait;
use App\Traits\PushNotificationTrait;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class OrderPlacedListener
{
    use PushNotificationTrait, EmailTemplateTrait;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderPlacedEvent $event): void
    {
        if ($event->email) {
            $this->sendMail($event);
        }
        if ($event->notification) {
            $this->sendNotification($event);
        }

        // إضافة: إرسال إشعارات SMS للعميل والإدارة
        $this->sendOrderNotifications($event);
    }

    private function sendMail(OrderPlacedEvent $event): void
    {
        $email = $event->email;
        $data = $event->data;
        try {
            $this->sendingMail(sendMailTo: $email, userType: $data['userType'], templateName: $data['templateName'], data: $data);
        } catch (\Exception $exception) {

        }
    }

    private function sendNotification(OrderPlacedEvent $event): void
    {
        if (isset($event->notification->key) && isset($event->notification->type) && isset($event->notification->order)) {
            $key = $event->notification->key;
            $type = $event->notification->type;
            $order = $event->notification->order;
            $this->sendOrderNotification(key: $key, type: $type, order: $order);
        } else {
            \Log::warning('OrderPlacedListener: Missing notification properties', [
                'has_key' => isset($event->notification->key),
                'has_type' => isset($event->notification->type),
                'has_order' => isset($event->notification->order)
            ]);
        }
    }

    /**
     * إرسال إشعارات SMS للعميل والإدارة
     */
    private function sendOrderNotifications(OrderPlacedEvent $event): void
    {
        try {
            $orderId = null;
            
            // استخراج معرف الطلب من الحدث
            if (isset($event->notification) && isset($event->notification->order)) {
                $orderId = $event->notification->order->id;
            } elseif (isset($event->data) && isset($event->data['orderId'])) {
                $orderId = $event->data['orderId'];
            }

            if ($orderId) {
                // إرسال إشعارات SMS
                $notificationService = app(\App\Services\OrderNotificationService::class);
                $notificationService->sendOrderNotifications($orderId, [
                    'payment_method' => 'Tabby',
                    'source' => 'OrderPlacedListener'
                ]);

                \Log::info('OrderPlacedListener: SMS notifications sent', [
                    'order_id' => $orderId,
                    'listener' => 'OrderPlacedListener'
                ]);
            } else {
                \Log::warning('OrderPlacedListener: No order ID found in event', [
                    'event_structure' => [
                        'has_notification' => isset($event->notification),
                        'has_data' => isset($event->data),
                        'notification_order_id' => $event->notification->order->id ?? 'null',
                        'data_order_id' => $event->data['orderId'] ?? 'null'
                    ]
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('OrderPlacedListener: Error sending SMS notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
