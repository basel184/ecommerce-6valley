<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use Brian2694\Toastr\Facades\Toastr;

class PaymentFallbackController extends Controller
{
    /**
     * Handle payment fallback for failed payment gateways
     */
    public function handleFallback(Request $request)
    {
        $gateway = $request->get('gateway');
        $orderId = $request->get('order_id');
        $checkoutId = $request->get('checkout_id');
        
        Log::info('Payment Fallback Triggered', [
            'gateway' => $gateway,
            'order_id' => $orderId,
            'checkout_id' => $checkoutId
        ]);
        
        // Find the order
        $order = Order::where('id', $orderId)->first();
        
        if (!$order) {
            Toastr::error(translate('Order not found'));
            return redirect()->route('checkout-details');
        }
        
        // Handle different gateways
        switch ($gateway) {
            case 'tamara':
                return $this->handleTamaraFallback($request, $order);
            case 'tabby':
                return $this->handleTabbyFallback($request, $order);
            default:
                return $this->handleGenericFallback($request, $order);
        }
    }
    
    /**
     * Handle Tamara fallback
     */
    private function handleTamaraFallback(Request $request, $order)
    {
        // Redirect to cash on delivery or other payment method
        Toastr::warning(translate('Tamara payment is temporarily unavailable. Please choose another payment method.'));
        
        return redirect()->route('checkout-payment')->with([
            'payment_error' => 'tamara_unavailable',
            'message' => translate('Tamara payment service is currently experiencing issues. Please try another payment method.')
        ]);
    }
    
    /**
     * Handle Tabby fallback
     */
    private function handleTabbyFallback(Request $request, $order)
    {
        Toastr::warning(translate('Tabby payment is temporarily unavailable. Please choose another payment method.'));
        
        return redirect()->route('checkout-payment')->with([
            'payment_error' => 'tabby_unavailable',
            'message' => translate('Tabby payment service is currently experiencing issues. Please try another payment method.')
        ]);
    }
    
    /**
     * Handle generic payment fallback
     */
    private function handleGenericFallback(Request $request, $order)
    {
        Toastr::error(translate('Payment service is temporarily unavailable. Please try again later.'));
        
        return redirect()->route('checkout-payment')->with([
            'payment_error' => 'service_unavailable',
            'message' => translate('Payment service is currently experiencing technical difficulties. Please try again later or contact support.')
        ]);
    }
}
