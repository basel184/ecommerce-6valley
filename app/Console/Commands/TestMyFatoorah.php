<?php

namespace App\Console\Commands;

use App\Services\MyFatoorahService;
use Illuminate\Console\Command;

class TestMyFatoorah extends Command
{
    protected $signature = 'test:myfatoorah {amount=100} {--email=test@example.com} {--phone=966501234567}';
    protected $description = 'Test MyFatoorah payment service integration';

    public function handle(MyFatoorahService $myFatoorahService)
    {
        $amount = floatval($this->argument('amount'));
        $email = $this->option('email');
        $phone = $this->option('phone');
        
        $this->info("🔧 Testing MyFatoorah Integration");
        $this->line("─────────────────────────────");
        
        // Check configuration
        $this->info("📋 Configuration Check:");
        $this->checkConfiguration();
        
        $this->line("");
        $this->info("💳 Testing Payment Initiation:");
        $this->line("Amount: {$amount} SAR");
        $this->line("Email: {$email}");
        $this->line("Phone: {$phone}");
        
        $callbackUrl = url('/payment/fatoorah/callback') . '?payment_id=test123';
        $errorUrl = url('/payment/fatoorah/error') . '?payment_id=test123';
        
        try {
            $result = $myFatoorahService->initiatePayment(
                $amount,
                'Test Customer',
                $email,
                $phone,
                $callbackUrl,
                $errorUrl,
                'TEST_' . time()
            );
            
            if ($result['success']) {
                $this->info("✅ Payment initiation successful!");
                $this->line("Payment URL: " . $result['payment_url']);
                $this->line("Invoice ID: " . $result['invoice_id']);
                
                // Test payment status check
                $this->line("");
                $this->info("🔍 Testing Payment Status Check:");
                $statusResult = $myFatoorahService->getPaymentStatus($result['invoice_id']);
                
                if ($statusResult['success']) {
                    $this->info("✅ Payment status check successful!");
                    $this->line("Status: " . $statusResult['data']['InvoiceStatus']);
                } else {
                    $this->error("❌ Payment status check failed: " . $statusResult['error']);
                }
                
            } else {
                $this->error("❌ Payment initiation failed: " . $result['error']);
                if (isset($result['details'])) {
                    $this->line("Details: " . json_encode($result['details'], JSON_PRETTY_PRINT));
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception occurred: " . $e->getMessage());
        }
        
        return 0;
    }
    
    private function checkConfiguration()
    {
        $config = [
            'API Key' => config('myfatoorah.api_key') ? '✅ Set (' . strlen(config('myfatoorah.api_key')) . ' chars)' : '❌ Missing',
            'Mode' => config('myfatoorah.mode'),
            'Country Code' => config('myfatoorah.account_country_code'),
            'API URL' => config('myfatoorah.mode') === 'live' 
                ? config('myfatoorah.live_api_url') 
                : config('myfatoorah.test_api_url'),
        ];
        
        foreach ($config as $key => $value) {
            $this->line("{$key}: {$value}");
        }
    }
}
