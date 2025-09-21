<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\TryotoShippingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TryotoController extends Controller
{
    private $tryotoService;

    public function __construct(TryotoShippingService $tryotoService)
    {
        $this->tryotoService = $tryotoService;
    }

    public function createShipment(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id'
        ]);

        $orderId = $request->order_id;
        
        try {
            $result = $this->tryotoService->createOrder($orderId);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Shipment created successfully',
                    'data' => $result
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create shipment'
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function trackShipment($orderId)
    {
        // Redirect to Tryoto tracking page with order ID
        $trackingUrl = "https://apis.tryoto.com/?order_id=" . $orderId;
        return redirect()->away($trackingUrl);
    }
}
