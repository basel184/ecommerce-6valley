<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MyFatoorahService;
use App\Traits\HelperTrait;

class MyFatoorahController extends Controller
{
    use HelperTrait;
    
    protected $myFatoorahService;

    public function __construct(MyFatoorahService $myFatoorahService)
    {
        $this->myFatoorahService = $myFatoorahService;
    }

    /**
     * Redirect to MyFatoorah payment page
     */
    public function processPayment(Request $request)
    {
        // Validate the request
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'order_id' => 'required',
        ]);

        // Generate callback URLs
        $callbackUrl = route('myfatoorah.callback');
        $errorUrl = route('myfatoorah.error');

        // Process payment with MyFatoorah
        $paymentData = $this->myFatoorahService->initiatePayment(
            $validated['amount'],
            $validated['customer_name'],
            $validated['customer_email'],
            $validated['customer_phone'],
            $callbackUrl,
            $errorUrl,
            $validated['order_id']
        );

        if (!$paymentData['success']) {
            \Log::error('MyFatoorah Payment Error in ProcessPayment', [
                'error' => $paymentData['error'] ?? 'Unknown error',
                'details' => $paymentData['details'] ?? []
            ]);
            
            return back()->with('error', 'Payment could not be initiated: ' . ($paymentData['error'] ?? 'Unknown error'));
        }

        // Store invoice ID in session or database for later verification
        session(['myfatoorah_invoice_id' => $paymentData['invoice_id']]);
        session(['myfatoorah_order_id' => $validated['order_id']]);

        // Redirect to MyFatoorah payment page
        return redirect($paymentData['payment_url']);
    }

    /**
     * Handle successful payment callback
     */
    public function handleCallback(Request $request)
    {
        // Get invoice ID from request or session
        $invoiceId = $request->input('invoiceId') ?? session('myfatoorah_invoice_id');
        $orderId = session('myfatoorah_order_id');

        if (!$invoiceId) {
            return redirect()->route('payment.error')->with('error', 'Invoice ID not found');
        }

        // Verify payment status
        $paymentStatus = $this->myFatoorahService->getPaymentStatus($invoiceId);

        if (!$paymentStatus['success']) {
            return redirect()->route('payment.error')->with('error', 'Could not verify payment status');
        }

        // Check if payment was successful
        $paymentData = $paymentStatus['data'];
        $isSuccess = $paymentData['InvoiceStatus'] === 'Paid';

        if ($isSuccess) {
            // Payment was successful
            // Clear session data
            session()->forget(['myfatoorah_invoice_id', 'myfatoorah_order_id']);

            // إضافة: إرسال إشعارات الطلب وإطلاق Events
            $this->processSuccessfulPayment($orderId, $paymentData);

            // Redirect to success page with transaction info
            return redirect()->route('payment.success')->with([
                'success' => 'Payment completed successfully!',
                'transaction_id' => $paymentData['InvoiceTransactions'][0]['TransactionId'] ?? null,
                'order_id' => $orderId,
            ]);
        } else {
            // Payment failed or is still pending
            return redirect()->route('payment.error')->with('error', 'Payment was not completed. Status: ' . $paymentData['InvoiceStatus']);
        }
    }

    /**
     * Handle payment errors
     */
    public function handleError(Request $request)
    {
        // Clear session data
        session()->forget(['myfatoorah_invoice_id', 'myfatoorah_order_id']);

        return redirect()->route('payment.error')->with('error', 'Payment was not completed successfully');
    }

    /**
     * Handle webhook notifications from MyFatoorah
     */
    public function webhook(Request $request)
    {
        // Get the request body
        $requestBody = $request->getContent();
        
        // Verify webhook signature if configured
        $signature = $request->header('mf-signature');
        if ($signature && !$this->myFatoorahService->verifyWebhook($requestBody, $signature)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        // Process webhook data
        $data = json_decode($requestBody, true);
        
        if (isset($data['InvoiceId']) && isset($data['InvoiceStatus'])) {
            // Process based on status
            if ($data['InvoiceStatus'] === 'Paid') {
                // Handle successful payment
                // Update order status, notify user, etc.
                
                // You can use $data['CustomerReference'] to identify your order
            }
            
            // Log webhook for debugging
            \Log::info('MyFatoorah Webhook', ['data' => $data]);
        }

        // Always respond with success to MyFatoorah
        return response()->json(['status' => 'success'], 200);
    }

    /**
     * معالجة الدفع الناجح - إرسال إشعارات وإطلاق Events
     */
    private function processSuccessfulPayment($orderId, $paymentData)
    {
        try {
            \Log::info('MyFatoorah: Processing successful payment', [
                'order_id' => $orderId,
                'invoice_status' => $paymentData['InvoiceStatus'] ?? 'unknown',
                'transaction_id' => $paymentData['InvoiceTransactions'][0]['TransactionId'] ?? null
            ]);

            if ($orderId) {
                // البحث عن الطلب في قاعدة البيانات
                $order = \App\Models\Order::find($orderId);
                
                if ($order) {
                    // تحديث حالة الدفع للطلب
                    $order->payment_status = 'paid';
                    $order->save();

                    \Log::info('MyFatoorah: Order payment status updated', [
                        'order_id' => $order->id,
                        'payment_status' => 'paid'
                    ]);

                    // إطلاق Event OrderPlacedEvent
                    event(new \App\Events\OrderPlacedEvent((object)['order' => $order]));

                    \Log::info('MyFatoorah: OrderPlacedEvent dispatched', [
                        'order_id' => $order->id
                    ]);

                    // إرسال إشعارات للعميل والإدارة
                    $notificationService = app(\App\Services\OrderNotificationService::class);
                    $notificationService->sendOrderNotifications($orderId, [
                        'transaction_reference' => $paymentData['InvoiceTransactions'][0]['TransactionId'] ?? null,
                        'payment_amount' => $paymentData['InvoiceValue'] ?? null,
                        'payment_method' => 'MyFatoorah'
                    ]);

                    \Log::info('MyFatoorah: Order notifications sent', [
                        'order_id' => $orderId,
                        'notification_service' => 'OrderNotificationService'
                    ]);
                } else {
                    \Log::warning('MyFatoorah: Order not found for notifications', [
                        'order_id' => $orderId
                    ]);
                }
            } else {
                \Log::warning('MyFatoorah: No order ID provided for payment processing');
            }
        } catch (\Exception $e) {
            \Log::error('MyFatoorah: Error processing successful payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $orderId ?? null
            ]);
        }
    }
}
