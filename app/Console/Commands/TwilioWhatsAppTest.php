<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AbandonedCartService;
use App\Services\TwilioWhatsAppService;
use App\Models\Cart;

class TwilioWhatsAppTest extends Command
{
    protected $signature = 'twilio:wa-test {--cart_id=} {--phone=} {--template=abandoned_cart_first} {--content_sid=}';

    protected $description = 'Send a WhatsApp template message via Twilio using a cart (preferred) or direct phone';

    public function handle(): int
    {
        $template = (string) $this->option('template');
        $contentSidOpt = $this->option('content_sid');
        $cartId = $this->option('cart_id');
        $phone = $this->option('phone');

        if (!$cartId && !$phone) {
            $this->error('Provide either --cart_id=ID to send via AbandonedCartService or --phone=9665xxxxxxx for direct send');
            return Command::FAILURE;
        }

        if ($cartId) {
            $cart = Cart::abandoned()->find($cartId);
            if (!$cart) {
                $this->error("Cart #{$cartId} not found or not marked abandoned");
                return Command::FAILURE;
            }

            // Inject content SID into runtime config if provided
            if ($contentSidOpt) {
                $tpl = config("twilio.templates.$template", []);
                $tpl['content_sid'] = $contentSidOpt;
                config(["twilio.templates.$template" => $tpl]);
            }

            $this->info("Sending WhatsApp template '{$template}' for cart_id={$cartId} ...");
            $service = app(AbandonedCartService::class);
            $ok = $service->sendReminder($cart, $template, $contentSidOpt ?: null);
            if ($ok) {
                $this->info('✅ Sent successfully');
                return Command::SUCCESS;
            }
            $this->error('❌ Send failed (see logs for details)');
            return Command::FAILURE;
        }

        // Direct mode by phone
        $to = preg_replace('/\s+/', '', (string)$phone);
        $this->info("Sending WhatsApp template '{$template}' to {$to} ...");
        if ($contentSidOpt) {
            $tpl = config("twilio.templates.$template", []);
            $tpl['content_sid'] = $contentSidOpt;
            config(["twilio.templates.$template" => $tpl]);
        }

        $vars = [
            'first_name' => 'مستخدم',
            'customer_name' => 'مستخدم',
            'store_name' => config('app.name', 'متجرك'),
            'cart_value' => '0.00',
            'cart_link' => url('/cart'),
        ];
        $twilio = app(TwilioWhatsAppService::class);
        $resp = $twilio->sendTemplate($to, $template, $vars);
        if (!empty($resp['success'])) {
            $this->info('✅ Sent successfully: SID=' . ($resp['sid'] ?? '')); 
            return Command::SUCCESS;
        }
        $this->error('❌ Send failed: ' . ($resp['message'] ?? 'unknown'));
        return Command::FAILURE;
    }
}
