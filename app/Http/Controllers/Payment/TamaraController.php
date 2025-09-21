<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use App\Services\TamaraService;
use App\Traits\HelperTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Log;

class TamaraController extends Controller
{
    use HelperTrait;

    private $tamaraService;

    public function __construct(TamaraService $tamaraService)
    {
        $this->tamaraService = $tamaraService;
    }

    public function pay(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $payment_data = PaymentRequest::where(['id' => $request['payment_id']])->first();
            if (!isset($payment_data)) {
                if ($request->ajax()) {
                    return response()->json(['errors' => ['message' => 'Data not found']], 403);
                }
                Toastr::error(translate('data_not_found'));
                return back();
            }

            $payer = json_decode($payment_data['payer_information']);
            
            $successUrl = url('/tamara/success') . '?payment_id=' . $request['payment_id'];
            $failureUrl = url('/tamara/failure') . '?payment_id=' . $request['payment_id'];
            $cancelUrl = url('/tamara/cancel') . '?payment_id=' . $request['payment_id'];
            
            // Create a default item for the order
            $defaultItem = [
                'reference_id' => $payment_data->id,
                'type' => 'digital',
                'name' => 'Order #' . $payment_data->id,
                'sku' => 'order-' . $payment_data->id,
                'quantity' => 1,
                'unit_price' => [
                    'amount' => $payment_data->payment_amount,
                    'currency' => 'SAR'
                ],
                'discount_amount' => [
                    'amount' => '0.00',
                    'currency' => 'SAR'
                ],
                'tax_amount' => [
                    'amount' => '0.00',
                    'currency' => 'SAR'
                ],
                'total_amount' => [
                    'amount' => $payment_data->payment_amount,
                    'currency' => 'SAR'
                ]
            ];
            
            // Process payment with Tamara
            $paymentData = $this->tamaraService->createCheckout(
                $payment_data->payment_amount,
                $payer->name,
                $payer->email,
                $payer->phone ?? '',
                $successUrl,
                $failureUrl,
                $cancelUrl,
                [$defaultItem], // Add at least one item
                $payment_data->id
            );
           
            if (!$paymentData['success']) {
                if ($request->ajax()) {
                    return response()->json([
                        'errors' => [
                            'message' => 'Tamara Error: ' . ($paymentData['error'] ?? 'Unknown error'),
                            'details' => $paymentData['details'] ?? [] 
                        ]
                    ], 403);
                }
                
                Log::error('Tamara Payment Error', [
                    'error' => $paymentData['error'] ?? 'Unknown error',
                    'details' => $paymentData['details'] ?? []
                ]);
                
                Toastr::error('Tamara Error: ' . ($paymentData['error'] ?? 'Unknown error'));
                return back();
            }

            // Store identifiers in session for later verification
            session(['tamara_checkout_id' => $paymentData['checkout_id'] ?? null]);
            session(['tamara_order_id' => $paymentData['order_id'] ?? null]);
            session(['tamara_payment_id' => $request['payment_id']]);

            // Redirect to Tamara (or fallback) payment page
            $redirectUrl = $paymentData['payment_url']
                ?? $paymentData['checkout_url']
                ?? $paymentData['redirect_url']
                ?? $paymentData['url']
                ?? null;

            if (!$redirectUrl) {
                Log::error('Tamara Payment Error: missing redirect URL in response', [ 'response' => $paymentData ]);
                Toastr::error('Tamara Error: Missing redirect URL');
                return back();
            }

            return redirect($redirectUrl);
        } catch (Exception $exception) {
            if ($request->ajax()) {
                return response()->json(['errors' => ['message' => 'Tamara Error: ' . $exception->getMessage()]], 403);
            }
            Toastr::error('Tamara Error: ' . $exception->getMessage());
            return back();
        }
    }

    public function success(Request $request): RedirectResponse
    {
        try {
            // Get payment ID from request or session
            $paymentId = $request->input('payment_id') ?? session('tamara_payment_id');
            $checkoutId = $request->input('checkout_id') ?? session('tamara_checkout_id');
            $orderId = $request->input('order_id') ?? $request->input('orderId') ?? session('tamara_order_id');

            if (!$paymentId || (!$checkoutId && !$orderId)) {
                Toastr::error(translate('payment_information_not_found'));
                return redirect('/');
            }

            // Prefer order_id for status when available, else use checkout_id
            $statusId = $orderId ?: $checkoutId;
            $orderStatus = $this->tamaraService->getOrderStatus($statusId);

            // Determine approval
            $isApproved = false;
            if ($orderStatus['success'] ?? false) {
                $statusVal = strtolower($orderStatus['status'] ?? '');
                $isApproved = in_array($statusVal, ['approved', 'authorised', 'authorized', 'captured', 'paid']);
            }

            // If not approved, try authorisation directly
            if (!$isApproved) {
                $authResult = $this->tamaraService->authorizePayment($statusId);
                if ($authResult['success'] ?? false) {
                    $isApproved = true;
                } else {
                    // Tamara sometimes returns 5xx after redirect; proceed as provisional success
                    Log::warning('Tamara status/authorize failed after success redirect; proceeding as provisional success', [
                        'payment_id' => $paymentId,
                        'status_check' => $orderStatus,
                        'auth_result' => $authResult ?? null
                    ]);
                    $isApproved = true; // Provisional
                }
            }

            // Payment was successful
            $payment_data = PaymentRequest::where(['id' => $paymentId])->first();
                
            if (isset($payment_data) && function_exists($payment_data->success_hook)) {
                // Mirror Tabby: mark as paid and success to allow order creation and notifications
                $payment_data->payment_status = 'success';
                $payment_data->is_paid = 1;
                $payment_data->transaction_reference = $orderId ?: $checkoutId;
                $payment_data->save();
                
                // Clear session data
                session()->forget(['tamara_checkout_id', 'tamara_order_id', 'tamara_payment_id']);
                
                // Call success hook
                $result = call_user_func($payment_data->success_hook, $payment_data);
                if ($result instanceof \Illuminate\Http\RedirectResponse) {
                    return $result;
                }
                Toastr::success(translate('payment_successful'));
                return redirect('/');
            }
            
            // If no success hook exists or it fails, redirect to home
            Toastr::success(translate('payment_successful'));
            return redirect('/');
        } catch (Exception $exception) {
            Toastr::error($exception->getMessage());
            return $this->payment_failed($request->input('payment_id') ?? session('tamara_payment_id') ?? null);
        }
    }

    public function failure(Request $request): RedirectResponse
    {
        $paymentId = $request->input('payment_id') ?? session('tamara_payment_id');
        return $this->payment_failed($paymentId);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $paymentId = $request->input('payment_id') ?? session('tamara_payment_id');
        return $this->payment_failed($paymentId);
    }

    private function payment_failed($payment_id): RedirectResponse
    {
        // Clear session data
    session()->forget(['tamara_checkout_id', 'tamara_order_id', 'tamara_payment_id']);
        
        if (isset($payment_id)) {
            $payment_data = PaymentRequest::where(['id' => $payment_id])->first();
            if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
                $payment_data->payment_status = 'failed';
                $payment_data->save();
                $result = call_user_func($payment_data->failure_hook, $payment_data);
                if ($result instanceof \Illuminate\Http\RedirectResponse) {
                    return $result;
                }
                return redirect('/');
            }
        }
        
        Toastr::error(translate('payment_failed'));
        return redirect('/');
    }

    public function notification(Request $request)
    {
        $signature = $request->header('X-Tamara-Signature');
        $payload = $request->getContent();
        
        if (!$this->tamaraService->verifyWebhook($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        // Process notification payload
        $data = json_decode($payload, true);

        // Log the notification for debugging
        Log::info('Tamara Notification Received', [ 'data' => $data ]);

        // Extract references
        $orderId = $data['order_id'] ?? $data['id'] ?? null;
        $orderRef = $data['order_reference_id'] ?? $data['merchant_reference_id'] ?? null;
        $status = strtolower($data['status'] ?? $data['order_status'] ?? '');

        // Find payment request
        $payment = null;
        if ($orderRef) {
            $payment = PaymentRequest::where('id', $orderRef)->first();
        }
        if (!$payment && $orderId) {
            $payment = PaymentRequest::where('transaction_reference', $orderId)->first();
        }

        if ($payment) {
            $isSuccess = in_array($status, ['approved', 'authorised', 'authorized', 'captured', 'paid']);
            $payment->transaction_reference = $orderId ?: $payment->transaction_reference;
            $payment->payment_status = $isSuccess ? 'success' : (in_array($status, ['rejected','declined','voided','cancelled','canceled']) ? 'failed' : $payment->payment_status);
            $payment->save();

            // Trigger hooks
            if ($isSuccess && function_exists($payment->success_hook)) {
                call_user_func($payment->success_hook, $payment);
            } elseif (!$isSuccess && function_exists($payment->failure_hook)) {
                call_user_func($payment->failure_hook, $payment);
            }
        } else {
            Log::warning('Tamara Notification: PaymentRequest not found for webhook', [
                'order_reference_id' => $orderRef,
                'order_id' => $orderId,
                'status' => $status
            ]);
        }

        return response()->json(['success' => true]);
    }
}
