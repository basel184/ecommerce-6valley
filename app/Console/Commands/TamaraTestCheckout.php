<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Library\Payer;
use App\Library\Receiver;
use App\Library\Payment as PaymentInfo;
use App\Traits\Payment as PaymentTrait;

class TamaraTestCheckout extends Command
{
    use PaymentTrait;

    /**
     * The name and signature of the console command.
     *
     * Usage: php artisan tamara:test-checkout --phone="+966548060989" --amount=100 --name="Test User" --email="test@example.com"
     *
     * @var string
     */
    protected $signature = 'tamara:test-checkout
        {--phone= : Customer phone number (e.g., +966548060989)}
        {--amount=100 : Amount in SAR}
        {--name=Test Customer : Customer full name}
        {--email=test-tamara@bernsa.com : Customer email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a Tamara checkout link for quick live testing with a specific phone number and amount.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $phone = trim((string) $this->option('phone'));
        if ($phone === '') {
            $this->error('Missing required --phone option.');
            return Command::FAILURE;
        }

        $amount = (float) $this->option('amount');
        if ($amount <= 0) {
            $this->error('Amount must be greater than 0.');
            return Command::FAILURE;
        }

        $name = (string) $this->option('name');
        $email = (string) $this->option('email');

        // Build Payer and Receiver
        $payer = new Payer($name, $email, $phone, [
            'line1' => 'N/A',
            'city' => 'Riyadh',
            'country_code' => 'SA',
        ]);
        $receiver = new Receiver('Bernsa', '');

        // Build payment info
        $currency = config('tamara.currency', 'SAR');
        $paymentInfo = new PaymentInfo(
            success_hook: 'digital_payment_success',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency,
            payment_method: 'tamara',
            payment_platform: 'web',
            payer_id: auth('customer')->id() ?? null,
            receiver_id: null,
            additional_data: [
                'purpose' => 'tamara_quick_test',
            ],
            payment_amount: $amount,
            external_redirect_link: null,
            attribute: null,
            attribute_id: null
        );

        try {
            $link = self::generate_link($payer, $paymentInfo, $receiver);
        } catch (\Throwable $e) {
            $this->error('Failed to create payment link: ' . $e->getMessage());
            return Command::FAILURE;
        }

        if (!$link) {
            $this->error('Could not generate payment link.');
            return Command::FAILURE;
        }

        $this->info('Tamara checkout link generated:');
        $this->line($link);
        return Command::SUCCESS;
    }
}
