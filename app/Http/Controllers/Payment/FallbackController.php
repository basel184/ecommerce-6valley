<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Brian2694\Toastr\Facades\Toastr;

class FallbackController extends Controller
{
    /**
     * Handle payment fallbacks when a gateway is experiencing issues
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleFallback(Request $request)
    {
        $gateway = $request->query('gateway');
        $orderId = $request->query('order_id');
        $checkoutId = $request->query('checkout_id');
        
        Log::info('Payment Fallback Triggered', [
            'gateway' => $gateway,
            'order_id' => $orderId,
            'checkout_id' => $checkoutId
        ]);
        
        // Log the fallback
        if (!$orderId) {
            Toastr::error(translate('payment_information_not_found'));
            return redirect('/');
        }
        
        // Get payment data
        $payment_data = PaymentRequest::where(['id' => $orderId])->first();
        if (!$payment_data) {
            Toastr::error(translate('payment_information_not_found'));
            return redirect('/');
        }
        
        // Notify the user about the fallback with better message
        Toastr::warning(translate('We apologize, but the payment gateway you selected is temporarily unavailable. Please select an alternative payment method to complete your purchase.'));
        
        // Redirect to an alternative payment method selection page with more context
        return redirect()->route('payment-selection', [
            'payment_id' => $orderId,
            'failed_gateway' => $gateway
        ]);
    }
    
    /**
     * Display a custom page with alternative payment options
     * 
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function showAlternatives(Request $request)
    {
        $paymentId = $request->query('payment_id');
        $payment_data = PaymentRequest::where(['id' => $paymentId])->first();
        
        if (!$payment_data) {
            Toastr::error(translate('payment_information_not_found'));
            return redirect('/');
        }
        
        // Available payment methods (excluding the one that failed)
        $failedGateway = $request->query('failed_gateway') ?? 'tamara';
        
        // Return view with alternative payment methods
        return view('payment.alternatives', [
            'payment_data' => $payment_data,
            'failed_gateway' => $failedGateway
        ]);
    }
}
