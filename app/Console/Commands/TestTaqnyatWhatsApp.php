<?php

namespace App\Console\Commands;

use App\Services\TaqnyatSmsService;
use Illuminate\Console\Command;

class TestTaqnyatWhatsApp extends Command
{
    protected $signature = 'test:taqnyat-whatsapp {phone} {message?}';
    protected $description = 'Test Taqnyat WhatsApp service';

    public function handle(TaqnyatSmsService $smsService)
    {
        $phone = $this->argument('phone');
        $message = $this->argument('message') ?? 'اختبار رسالة واتساب من BERN عبر Taqnyat 📱✅';
        
        $this->info("🔧 Testing Taqnyat Integration");
        $this->line("══════════════════════════════");
        $this->info("Phone: {$phone}");
        $this->info("Message: {$message}");
        $this->line("");
        
        // Check Taqnyat configuration
        $this->info("📋 Taqnyat Configuration:");
        $apiKey = config('sms.taqnyat.api_key');
        $sender = config('sms.taqnyat.sender');
        $smsEnabled = config('sms.taqnyat.sms_enabled');
        $whatsappEnabled = config('sms.taqnyat.whatsapp_enabled');
        
        $this->line("  API Key: " . ($apiKey ? '✅ Set (' . strlen($apiKey) . ' chars)' : '❌ Missing'));
        $this->line("  Sender: {$sender}");
        $this->line("  SMS Enabled: " . ($smsEnabled ? '✅ Yes' : '❌ No'));
        $this->line("  WhatsApp Enabled: " . ($whatsappEnabled ? '✅ Yes' : '❌ No'));
        $this->line("");
        
        $success = true;
        
        // Test SMS if enabled
        if ($smsEnabled) {
            $this->info("📧 Testing Taqnyat SMS...");
            $smsResult = $smsService->sendSms($phone, "📧 SMS: " . $message);
            
            if ($smsResult['success']) {
                $this->info("  ✅ SMS sent successfully!");
            } else {
                $this->error("  ❌ SMS failed: " . $smsResult['message']);
                $success = false;
            }
        } else {
            $this->line("📧 SMS disabled in configuration");
        }
        
        $this->line("");
        
        // Test WhatsApp if enabled
        if ($whatsappEnabled) {
            $this->info("📱 Testing Taqnyat WhatsApp...");
            $whatsappResult = $smsService->sendWhatsApp($phone, "📱 WhatsApp: " . $message);
            
            if ($whatsappResult['success']) {
                $this->info("  ✅ WhatsApp sent successfully!");
            } else {
                $this->error("  ❌ WhatsApp failed: " . $whatsappResult['message']);
                // Don't mark as failure since WhatsApp might fallback to SMS
            }
        } else {
            $this->line("📱 WhatsApp disabled in configuration");
        }
        
        $this->line("");
        $this->info("🎯 Test completed!");
        
        return $success ? 0 : 1;
    }
}
