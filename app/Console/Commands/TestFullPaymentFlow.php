<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Models\Cart;
use App\Models\ShippingAddress;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TestFullPaymentFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:full-payment {--customer-id=10 : Customer ID to use} {--reset : Reset and clean test data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the complete payment flow: Cart -> Payment -> Order Creation -> Notifications';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('reset')) {
            $this->resetTestData();
            return 0;
        }
        
        $this->info('🧪 Testing complete payment flow...');
        
        $customerId = $this->option('customer-id');
        
        // Step 1: Check customer
        $customer = User::find($customerId);
        if (!$customer) {
            $this->error("❌ Customer {$customerId} not found!");
            return 1;
        }
        
        $this->info("✅ Customer: {$customer->f_name} {$customer->l_name} ({$customer->phone})");
        
        // Step 2: Create test cart
        $cartGroupId = 'test_' . time();
        $this->createTestCart($customerId, $cartGroupId);
        
        // Step 3: Create test payment request
        $paymentRequestId = $this->createTestPaymentRequest($customerId, $cartGroupId);
        
        // Step 4: Simulate successful payment
        $this->simulateSuccessfulPayment($paymentRequestId);
        
        // Step 5: Check results
        $this->checkResults($paymentRequestId);
        
        return 0;
    }
    
    private function createTestCart($customerId, $cartGroupId)
    {
        $this->info('📦 Creating test cart...');
        
        // Create a simple cart item (assuming product ID 1 exists)
        Cart::create([
            'customer_id' => $customerId,
            'cart_group_id' => $cartGroupId,
            'product_id' => 1, // You may need to adjust this
            'product_type' => 'product',
            'color' => null,
            'choices' => '[]',
            'variations' => '[]',
            'variant' => null,
            'quantity' => 1,
            'price' => 1.00,
            'tax' => 0,
            'discount' => 0,
            'slug' => null,
            'name' => 'Test Product',
            'thumbnail' => null,
            'seller_id' => 1,
            'seller_is' => 'admin',
            'is_guest' => 0,
            'is_checked' => 1,
        ]);
        
        $this->info('✅ Test cart created');
    }
    
    private function createTestPaymentRequest($customerId, $cartGroupId)
    {
        $this->info('💳 Creating test payment request...');
        
        $customer = User::find($customerId);
        
        $payerInfo = [
            'name' => $customer->f_name . ' ' . $customer->l_name,
            'email' => $customer->email,
            'phone' => $customer->phone
        ];
        
        $additionalData = [
            'customer_id' => $customerId,
            'is_guest' => false,
            'is_guest_in_order' => false,
            'order_note' => 'Test order from payment flow test',
            'payment_request_from' => 'web',
            'payment_mode' => 'web'
        ];
        
        $paymentRequest = PaymentRequest::create([
            'payer_id' => $customerId,
            'payment_amount' => 1.00,
            'payment_method' => 'fatoorah',
            'success_hook' => 'digital_payment_success',
            'failure_hook' => 'digital_payment_fail',
            'currency_code' => 'SAR',
            'payment_platform' => 'web',
            'payer_information' => json_encode($payerInfo),
            'additional_data' => json_encode($additionalData),
            'payment_status' => 'pending',
            'is_paid' => 0,
            'order_ids' => null,
            'attribute_id' => time() . rand(1000, 9999),
        ]);
        
        $this->info("✅ Payment request created: {$paymentRequest->id}");
        $this->info("📋 Attribute ID: {$paymentRequest->attribute_id}");
        
        return $paymentRequest->id;
    }
    
    private function simulateSuccessfulPayment($paymentRequestId)
    {
        $this->info('⚡ Simulating successful payment...');
        
        $paymentRequest = PaymentRequest::find($paymentRequestId);
        
        // Update payment status
        $paymentRequest->update([
            'payment_status' => 'success',
            'is_paid' => 1,
            'transaction_reference' => 'TEST_FULL_FLOW_' . time()
        ]);
        
        // Call success hook (this creates the order)
        if (function_exists($paymentRequest->success_hook)) {
            Log::info('=== FULL PAYMENT FLOW TEST START ===');
            call_user_func($paymentRequest->success_hook, $paymentRequest);
            Log::info('=== SUCCESS HOOK COMPLETED ===');
        } else {
            $this->error("❌ Success hook '{$paymentRequest->success_hook}' not found!");
            return;
        }
        
        $this->info('✅ Payment processed successfully');
    }
    
    private function checkResults($paymentRequestId)
    {
        $this->info('🔍 Checking results...');
        
        $paymentRequest = PaymentRequest::find($paymentRequestId);
        
        $this->info("💳 Payment Status: {$paymentRequest->payment_status}");
        $this->info("✅ Is Paid: " . ($paymentRequest->is_paid ? 'Yes' : 'No'));
        $this->info("🔗 Transaction Ref: {$paymentRequest->transaction_reference}");
        
        if ($paymentRequest->order_ids) {
            $orderIds = json_decode($paymentRequest->order_ids, true);
            $this->info("📦 Orders Created: " . implode(', ', $orderIds));
            
            // Check if notifications were sent
            $attributeId = $paymentRequest->attribute_id;
            
            $this->info('📱 Sending notifications for attribute_id: ' . $attributeId);
            
            // Call notification service directly
            $notificationService = app(\App\Services\OrderNotificationService::class);
            $result = $notificationService->sendOrderNotifications($attributeId, [
                'transaction_reference' => $paymentRequest->transaction_reference,
                'payment_amount' => $paymentRequest->payment_amount,
                'payment_method' => 'Test Payment'
            ]);
            
            if ($result) {
                $this->info('✅ Notifications sent successfully!');
            } else {
                $this->error('❌ Failed to send notifications');
            }
            
            Log::info('=== FULL PAYMENT FLOW TEST END ===');
            
        } else {
            $this->error('❌ No orders were created!');
        }
        
        $this->info('');
        $this->info('📄 Check logs for detailed information');
        $this->info('');
        $this->info('💡 To clean test data: php artisan test:full-payment --reset');
    }
    
    private function resetTestData()
    {
        $this->info('🧹 Cleaning test data...');
        
        // Clean test carts
        Cart::where('cart_group_id', 'like', 'test_%')->delete();
        
        // Clean test payment requests (be careful!)
        PaymentRequest::where('additional_data', 'like', '%Test order from payment flow test%')->delete();
        
        $this->info('✅ Test data cleaned');
    }
}
