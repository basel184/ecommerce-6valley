<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TaqnyatSmsService;
use Illuminate\Support\Facades\Log;

class TestTaqnyatWhatsAppCompliance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:taqnyat-whatsapp-compliance {phone?} {--template=} {--opt-in} {--opt-out} {--conversation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Taqnyat WhatsApp API compliance according to official documentation';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🧪 Testing Taqnyat WhatsApp API Compliance');
        $this->info('================================================');
        
        $phone = $this->argument('phone') ?: '966548060989';
        $smsService = new TaqnyatSmsService();
        
        // Test 1: Register Opt-in (required before sending any messages)
        if ($this->option('opt-in')) {
            $this->info("\n1️⃣ Testing WhatsApp Opt-in Registration");
            $result = $smsService->registerWhatsAppOptIn($phone);
            
            if ($result['success']) {
                $this->info("✅ Opt-in registration successful");
            } else {
                $this->error("❌ Opt-in registration failed: " . $result['message']);
            }
        }
        
        // Test 2: Send Template Message (required to start conversation)
        if (!$this->option('conversation')) {
            $this->info("\n2️⃣ Testing WhatsApp Template Message");
            $template = $this->option('template') ?: 'order_notification';
            $message = "مرحباً! هذه رسالة اختبار من BERN لتأكيد عمل نظام الإشعارات الجديد.";
            
            $result = $smsService->sendWhatsApp($phone, $message, $template, false);
            
            if ($result['success']) {
                $this->info("✅ Template message sent successfully");
                if (isset($result['message_id'])) {
                    $this->info("📋 Message ID: " . $result['message_id']);
                }
            } else {
                $this->error("❌ Template message failed: " . $result['message']);
            }
        }
        
        // Test 3: Send Conversation Message (only works if customer replied within 24h)
        if ($this->option('conversation')) {
            $this->info("\n3️⃣ Testing WhatsApp Conversation Message");
            $this->warn("⚠️  This only works if customer replied to a template within last 24 hours");
            
            $message = "هذه رسالة محادثة اختبارية. يجب أن تعمل فقط إذا كان هناك جلسة نشطة.";
            $result = $smsService->sendWhatsApp($phone, $message, null, true);
            
            if ($result['success']) {
                $this->info("✅ Conversation message sent successfully");
            } else {
                $this->error("❌ Conversation message failed: " . $result['message']);
            }
        }
        
        // Test 4: Register Opt-out
        if ($this->option('opt-out')) {
            $this->info("\n4️⃣ Testing WhatsApp Opt-out Registration");
            $result = $smsService->registerWhatsAppOptOut($phone);
            
            if ($result['success']) {
                $this->info("✅ Opt-out registration successful");
            } else {
                $this->error("❌ Opt-out registration failed: " . $result['message']);
            }
        }
        
        // Test 5: SMS Fallback Test
        $this->info("\n5️⃣ Testing SMS Fallback");
        $result = $smsService->sendSms($phone, "📱 هذه رسالة SMS احتياطية لاختبار النظام الموحد.");
        
        if ($result['success']) {
            $this->info("✅ SMS fallback working correctly");
        } else {
            $this->error("❌ SMS fallback failed: " . $result['message']);
        }
        
        // Summary
        $this->info("\n📋 Testing Summary");
        $this->info("==================");
        $this->info("📱 Phone Number: " . $phone);
        $this->info("🔧 WhatsApp Base URL: https://api.taqnyat.sa/wa/v2/");
        $this->info("📝 SMS Base URL: https://api.taqnyat.sa/v1");
        $this->info("📋 Default Template: " . config('sms.taqnyat.whatsapp_default_template'));
        
        $this->info("\n🔗 Important Notes:");
        $this->info("• All WhatsApp conversations must start with template messages");
        $this->info("• Customer opt-in is required before sending any WhatsApp messages");
        $this->info("• Conversation messages only work during active 24h sessions");
        $this->info("• SMS fallback activates automatically when WhatsApp fails");
        
        $this->info("\n📖 Usage Examples:");
        $this->info("# Register opt-in and send template:");
        $this->info("php artisan test:taqnyat-whatsapp-compliance {$phone} --opt-in --template=order_notification");
        $this->info("");
        $this->info("# Send conversation message (requires active session):");
        $this->info("php artisan test:taqnyat-whatsapp-compliance {$phone} --conversation");
        $this->info("");
        $this->info("# Register opt-out:");
        $this->info("php artisan test:taqnyat-whatsapp-compliance {$phone} --opt-out");
        
        return Command::SUCCESS;
    }
}
