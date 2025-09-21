<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use App\Services\MyFatoorahService;
use App\Services\OrderNotificationService;
use App\Jobs\SendOrderNotificationsJob;
use App\Traits\HelperTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class FatoorahController extends Controller
{
    use HelperTrait;

    private $myFatoorahService;
    private $notificationService;

    public function __construct(MyFatoorahService $myFatoorahService, OrderNotificationService $notificationService)
    {
        $this->myFatoorahService = $myFatoorahService;
        $this->notificationService = $notificationService;
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
            
            $callbackUrl = url('/payment/fatoorah/callback') . '?payment_id=' . $request['payment_id'];
            $errorUrl = url('/payment/fatoorah/error') . '?payment_id=' . $request['payment_id'];

            // Process payment with MyFatoorah
            $paymentData = $this->myFatoorahService->initiatePayment(
                $payment_data->payment_amount,
                $payer->name,
                $payer->email,
                $payer->phone ?? '',
                $callbackUrl,
                $errorUrl,
                $payment_data->id
            );

            if (!$paymentData['success']) {
                if ($request->ajax()) {
                    return response()->json([
                        'errors' => [
                            'message' => 'MyFatoorah Error: ' . ($paymentData['error'] ?? 'Unknown error'),
                            'details' => $paymentData['details'] ?? [] 
                        ]
                    ], 403);
                }
                
                \Log::error('MyFatoorah Payment Error', [
                    'error' => $paymentData['error'] ?? 'Unknown error',
                    'details' => $paymentData['details'] ?? []
                ]);
                
                Toastr::error('MyFatoorah Error: ' . ($paymentData['error'] ?? 'Unknown error'));
                return back();
            }

            // Store invoice ID in session for later verification
            session(['myfatoorah_invoice_id' => $paymentData['invoice_id']]);
            session(['myfatoorah_payment_id' => $request['payment_id']]);

            // Redirect to MyFatoorah payment page
            return redirect($paymentData['payment_url']);
        } catch (Exception $exception) {
            if ($request->ajax()) {
                return response()->json(['errors' => ['message' => 'MyFatoorah Error: ' . $exception->getMessage()]], 403);
            }
            Toastr::error('MyFatoorah Error: ' . $exception->getMessage());
            return back();
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            // Get invoice ID from request or session
            $invoiceId = $request->input('invoiceId') ?? session('myfatoorah_invoice_id');
            $paymentId = $request->input('payment_id') ?? session('myfatoorah_payment_id');

            if (!$invoiceId || !$paymentId) {
                Toastr::error(translate('payment_information_not_found'));
                return redirect('/');
            }

            // Verify payment status
            $paymentStatus = $this->myFatoorahService->getPaymentStatus($invoiceId);

            if (!$paymentStatus['success']) {
                Toastr::error(translate('payment_failed'));
                return $this->payment_failed($paymentId);
            }

            // Check if payment was successful
            $paymentData = $paymentStatus['data'];
            $isSuccess = $paymentData['InvoiceStatus'] === 'Paid';

            if ($isSuccess) {
                // Payment was successful
                $payment_data = PaymentRequest::where(['id' => $paymentId])->first();
                
                if (isset($payment_data)) {
                    try {
                        $updateData = [
                            'payment_status' => 'success',
                            'is_paid' => 1
                        ];
                        
                        // ✅ حفظ رقم العملية الفعلي من My Fatoora
                        $myFatooraTransactionId = null;
                        if (isset($paymentData['InvoiceTransactions'][0]['TransactionId'])) {
                            $myFatooraTransactionId = $paymentData['InvoiceTransactions'][0]['TransactionId'];
                            $updateData['transaction_reference'] = $myFatooraTransactionId;
                        } elseif ($invoiceId) {
                            $myFatooraTransactionId = $invoiceId;
                            $updateData['transaction_reference'] = $invoiceId;
                        }
                        
                        // ✅ تحديث additional_data ليشمل معلومات My Fatoora
                        $additionalData = json_decode($payment_data->additional_data, true) ?: [];
                        $additionalData['myfatoora_transaction_id'] = $myFatooraTransactionId;
                        $additionalData['myfatoora_invoice_id'] = $invoiceId;
                        $additionalData['payment_confirmed_at'] = now()->toISOString();
                        $updateData['additional_data'] = json_encode($additionalData);
                        
                        $payment_data->update($updateData);
                        
                        // Clear session data
                        session()->forget(['myfatoorah_invoice_id', 'myfatoorah_payment_id']);
                        
                        // Call success hook if it exists
                        if (function_exists($payment_data->success_hook)) {
                            call_user_func($payment_data->success_hook, $payment_data);
                        }
                        
                        // ✅ إرسال إشعارات SMS و WhatsApp باستخدام Job مع معلومات إضافية
                        if (isset($payment_data->attribute_id)) {
                            SendOrderNotificationsJob::dispatch($payment_data->attribute_id, [
                                'transaction_reference' => $updateData['transaction_reference'] ?? null,
                                'payment_amount' => $payment_data->payment_amount,
                                'payment_method' => 'MyFatoorah',
                                'myfatoora_transaction_id' => $myFatooraTransactionId,
                                'myfatoora_invoice_id' => $invoiceId
                            ]);
                        }
                        
                        Toastr::success(translate('payment_successful'));
                        return redirect()->route('account-orders');
                    } catch (Exception $e) {
                        \Log::error('Payment update error: ' . $e->getMessage());
                        Toastr::success(translate('payment_successful'));
                        return redirect()->route('account-orders');
                    }
                }
                
                // If no payment data found, still redirect to orders page
                Toastr::success(translate('payment_successful'));
                return redirect()->route('account-orders');
            } else {
                // Payment failed or is still pending
                return $this->payment_failed($paymentId);
            }
        } catch (Exception $exception) {
            Toastr::error($exception->getMessage());
            return $this->payment_failed($paymentId ?? null);
        }
    }

    public function error(Request $request): RedirectResponse
    {
        $paymentId = $request->input('payment_id') ?? session('myfatoorah_payment_id');
        
        // Clear session data
        session()->forget(['myfatoorah_invoice_id', 'myfatoorah_payment_id']);
        
        return $this->payment_failed($paymentId);
    }

    private function payment_failed($payment_id): RedirectResponse
    {
        if (isset($payment_id)) {
            $payment_data = PaymentRequest::where(['id' => $payment_id])->first();
            if (isset($payment_data)) {
                try {
                    $payment_data->update([
                        'payment_status' => 'failed',
                        'is_paid' => 0
                    ]);
                    
                    if (function_exists($payment_data->failure_hook)) {
                        call_user_func($payment_data->failure_hook, $payment_data);
                    }
                    
                    Toastr::error(translate('payment_failed'));
                    return redirect()->route('account-orders')->with('payment_error', 'payment_failed');
                } catch (Exception $e) {
                    \Log::error('Payment failure update error: ' . $e->getMessage());
                    Toastr::error(translate('payment_failed'));
                    return redirect()->route('account-orders')->with('payment_error', 'payment_failed');
                }
            }
        }
        
        Toastr::error(translate('payment_failed'));
        return redirect()->route('shop-cart')->with('payment_error', 'payment_failed');
    }
}
