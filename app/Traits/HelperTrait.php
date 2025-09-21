<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Brian2694\Toastr\Facades\Toastr;

trait HelperTrait
{
    /**
     * Payment failed redirect function
     * 
     * @param string|null $payment_id
     * @return RedirectResponse
     */
    public function payment_failed($payment_id): RedirectResponse
    {
        if (isset($payment_id)) {
            $payment_data = \App\Models\PaymentRequest::where(['id' => $payment_id])->first();
            if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
                $payment_data->payment_status = 'failed';
                $payment_data->save();
                return call_user_func($payment_data->failure_hook, $payment_data);
            }
        }
        
        Toastr::error(translate('payment_failed'));
        return redirect('/');
    }

    /**
     * Return JSON response for API requests
     * 
     * @param bool $status
     * @param string $message
     * @param array $data
     * @param int $code
     * @return JsonResponse
     */
    public function apiResponse(bool $status, string $message, array $data = [], int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => $status,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Format amount to standard format
     * 
     * @param float $amount
     * @return float
     */
    public function formatAmount($amount): float
    {
        return (float) number_format((float)$amount, 2, '.', '');
    }

    /**
     * Get customer information from request
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getCustomerInfo($request): array
    {
        if (auth('customer')->check()) {
            $customer = auth('customer')->user();
            return [
                'name' => $customer->f_name . ' ' . $customer->l_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address ?? ''
            ];
        }
        
        return [
            'name' => $request->input('customer_name') ?? 'Guest Customer',
            'email' => $request->input('customer_email') ?? 'guest@example.com',
            'phone' => $request->input('customer_phone') ?? '',
            'address' => $request->input('customer_address') ?? ''
        ];
    }
}
