<?php

namespace App\Listeners;

use App\Services\TryotoShippingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreateTryotoShipment implements ShouldQueue
{
    use InteractsWithQueue;

    private $tryotoService;

    public function __construct(TryotoShippingService $tryotoService)
    {
        $this->tryotoService = $tryotoService;
    }

    public function handle($event)
    {
        try {
            // Debug: Log configuration values
            $tryotoEnabled = config('services.tryoto.enabled');
            $envTryotoEnabled = env('TRYOTO_ENABLED');
            
            Log::info('Tryoto Debug - Config enabled: ' . ($tryotoEnabled ? 'true' : 'false'));
            Log::info('Tryoto Debug - Env enabled: ' . ($envTryotoEnabled ? 'true' : 'false'));
            
            // Check if Tryoto is enabled
            if (!$tryotoEnabled) {
                Log::info('Tryoto shipment creation is disabled');
                return;
            }

            $orderId = null;
            
            // Handle different event types for OrderPlacedEvent
            if (isset($event->notification) && isset($event->notification->order)) {
                $orderId = $event->notification->order->id;
                Log::info("Tryoto: Found order ID from notification: $orderId");
            } elseif (isset($event->data) && isset($event->data['orderId'])) {
                $orderId = $event->data['orderId'];
                Log::info("Tryoto: Found order ID from data: $orderId");
            } elseif (isset($event->order)) {
                $orderId = $event->order->id;
                Log::info("Tryoto: Found order ID from order: $orderId");
            } elseif (isset($event->order_id)) {
                $orderId = $event->order_id;
                Log::info("Tryoto: Found order ID from order_id: $orderId");
            } elseif (is_numeric($event)) {
                $orderId = $event;
                Log::info("Tryoto: Found order ID from numeric event: $orderId");
            }

            if (!$orderId) {
                Log::error('Tryoto: No order ID found in event. Event structure: ' . json_encode($event));
                return;
            }

            Log::info("Tryoto: Creating shipment for order ID: $orderId");
            
            // Check if order is paid before creating shipment
            $order = \App\Models\Order::find($orderId);
            if ($order && $order->payment_status !== 'paid') {
                Log::info("Tryoto: Order $orderId is not paid yet, skipping shipment creation", [
                    'payment_status' => $order->payment_status
                ]);
                return;
            }
            
            $result = $this->tryotoService->createOrder($orderId);
            
            if ($result && isset($result['success']) && $result['success']) {
                Log::info("Tryoto: Shipment created successfully for order ID: $orderId", [
                    'oto_id' => $result['oto_id'] ?? null,
                    'dashboard_url' => $result['dashboard_url'] ?? null
                ]);
            } else {
                Log::error("Tryoto: Failed to create shipment for order ID: $orderId", [
                    'result' => $result
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Tryoto Listener Error: ' . $e->getMessage());
        }
    }
}
