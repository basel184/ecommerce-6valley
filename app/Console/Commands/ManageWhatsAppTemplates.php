<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TaqnyatSmsService;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ManageWhatsAppTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:manage {action} {--phone=} {--template=} {--all-customers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage WhatsApp templates and customer opt-ins according to Taqnyat documentation';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        $smsService = new TaqnyatSmsService();
        
        switch ($action) {
            case 'opt-in':
                return $this->handleOptIn($smsService);
            
            case 'opt-out':
                return $this->handleOptOut($smsService);
            
            case 'test-template':
                return $this->testTemplate($smsService);
            
            case 'bulk-opt-in':
                return $this->bulkOptIn($smsService);
            
            case 'show-templates':
                return $this->showTemplates();
            
            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: opt-in, opt-out, test-template, bulk-opt-in, show-templates");
                return Command::FAILURE;
        }
    }
    
    /**
     * Handle single customer opt-in
     */
    protected function handleOptIn($smsService)
    {
        $phone = $this->option('phone');
        
        if (!$phone) {
            $phone = $this->ask('Enter phone number (Saudi format):');
        }
        
        if (!$phone) {
            $this->error('Phone number is required');
            return Command::FAILURE;
        }
        
        $this->info("📱 Registering WhatsApp opt-in for: {$phone}");
        
        $result = $smsService->registerWhatsAppOptIn($phone);
        
        if ($result['success']) {
            $this->info("✅ Opt-in registered successfully");
            
            // Send welcome template message
            $welcomeMessage = "مرحباً بك في BERN! 🛍️\nسنرسل لك إشعارات مهمة عن طلباتك عبر WhatsApp.\nيمكنك إلغاء الاشتراك في أي وقت بإرسال 'STOP'.";
            
            $this->info("📩 Sending welcome template message...");
            $welcomeResult = $smsService->sendWhatsApp($phone, $welcomeMessage, 'welcome_message', false);
            
            if ($welcomeResult['success']) {
                $this->info("✅ Welcome message sent successfully");
            } else {
                $this->warn("⚠️  Welcome message failed: " . $welcomeResult['message']);
            }
        } else {
            $this->error("❌ Opt-in failed: " . $result['message']);
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Handle single customer opt-out
     */
    protected function handleOptOut($smsService)
    {
        $phone = $this->option('phone');
        
        if (!$phone) {
            $phone = $this->ask('Enter phone number (Saudi format):');
        }
        
        if (!$phone) {
            $this->error('Phone number is required');
            return Command::FAILURE;
        }
        
        $this->info("📱 Registering WhatsApp opt-out for: {$phone}");
        
        $result = $smsService->registerWhatsAppOptOut($phone);
        
        if ($result['success']) {
            $this->info("✅ Opt-out registered successfully");
        } else {
            $this->error("❌ Opt-out failed: " . $result['message']);
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Test template message
     */
    protected function testTemplate($smsService)
    {
        $phone = $this->option('phone');
        $template = $this->option('template');
        
        if (!$phone) {
            $phone = $this->ask('Enter phone number (Saudi format):');
        }
        
        if (!$template) {
            $template = $this->choice(
                'Select template to test:',
                array_keys(config('sms.taqnyat.whatsapp_templates')),
                0
            );
        }
        
        $this->info("📨 Testing template '{$template}' for: {$phone}");
        
        $testMessage = "هذه رسالة اختبار لقالب {$template} من BERN. الوقت: " . now()->format('Y-m-d H:i:s');
        
        $result = $smsService->sendWhatsApp($phone, $testMessage, $template, false);
        
        if ($result['success']) {
            $this->info("✅ Template message sent successfully");
            if (isset($result['message_id'])) {
                $this->info("📋 Message ID: " . $result['message_id']);
            }
        } else {
            $this->error("❌ Template message failed: " . $result['message']);
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Bulk opt-in for all customers
     */
    protected function bulkOptIn($smsService)
    {
        if (!$this->option('all-customers')) {
            if (!$this->confirm('This will register WhatsApp opt-in for ALL customers. Continue?')) {
                $this->info('Operation cancelled');
                return Command::SUCCESS;
            }
        }
        
        $this->info("📋 Getting all customers with phone numbers...");
        
        $customers = User::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get(['id', 'f_name', 'l_name', 'phone']);
        
        $this->info("Found {$customers->count()} customers");
        
        $successCount = 0;
        $failCount = 0;
        
        $bar = $this->output->createProgressBar($customers->count());
        $bar->start();
        
        foreach ($customers as $customer) {
            try {
                $result = $smsService->registerWhatsAppOptIn($customer->phone);
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failCount++;
                    Log::warning('Bulk opt-in failed for customer', [
                        'customer_id' => $customer->id,
                        'phone' => $customer->phone,
                        'error' => $result['message']
                    ]);
                }
                
                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 second
                
            } catch (\Exception $e) {
                $failCount++;
                Log::error('Bulk opt-in exception for customer', [
                    'customer_id' => $customer->id,
                    'phone' => $customer->phone,
                    'error' => $e->getMessage()
                ]);
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Successfully registered: {$successCount}");
        $this->info("❌ Failed: {$failCount}");
        $this->info("📊 Total processed: {$customers->count()}");
        
        return Command::SUCCESS;
    }
    
    /**
     * Show available templates
     */
    protected function showTemplates()
    {
        $this->info("📋 Available WhatsApp Templates");
        $this->info("===============================");
        
        $templates = config('sms.taqnyat.whatsapp_templates');
        $defaultTemplate = config('sms.taqnyat.whatsapp_default_template');
        
        foreach ($templates as $key => $template) {
            $isDefault = ($template === $defaultTemplate) ? ' (default)' : '';
            $this->info("• {$key}: {$template}{$isDefault}");
        }
        
        $this->info("\n🔧 Configuration:");
        $this->info("Default Template: {$defaultTemplate}");
        $this->info("WhatsApp Enabled: " . (config('sms.taqnyat.whatsapp_enabled') ? 'Yes' : 'No'));
        $this->info("SMS Enabled: " . (config('sms.taqnyat.sms_enabled') ? 'Yes' : 'No'));
        
        $this->info("\n📖 Usage Examples:");
        $this->info("# Register opt-in:");
        $this->info("php artisan whatsapp:manage opt-in --phone=966548060989");
        $this->info("");
        $this->info("# Test template:");
        $this->info("php artisan whatsapp:manage test-template --phone=966548060989 --template=order_notification");
        $this->info("");
        $this->info("# Bulk opt-in all customers:");
        $this->info("php artisan whatsapp:manage bulk-opt-in --all-customers");
        
        return Command::SUCCESS;
    }
}
