<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Utils\PaymentMethodManager;

class TestPaymentMethods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:payment-methods {--amount=500 : Test with specific amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test payment methods availability';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $amount = $this->option('amount');
        $this->info("🧪 Testing payment methods for amount: {$amount} SAR");
        $this->info('');
        
        $methods = PaymentMethodManager::getAvailablePaymentMethods($amount);
        
        $this->table(['Method', 'Display Name', 'Available', 'Status', 'Priority'], 
            array_map(function($key, $method) {
                return [
                    $key,
                    $method['display_name'],
                    $method['available'] ? '✅ Yes' : '❌ No',
                    $method['status'],
                    $method['priority']
                ];
            }, array_keys($methods), $methods)
        );
        
        $recommended = PaymentMethodManager::getRecommendedPaymentMethod($amount);
        
        if ($recommended) {
            $this->info("🎯 Recommended method: {$recommended['method']} ({$recommended['data']['display_name']})");
        } else {
            $this->warn("⚠️ No payment methods available!");
        }
        
        return 0;
    }
}
