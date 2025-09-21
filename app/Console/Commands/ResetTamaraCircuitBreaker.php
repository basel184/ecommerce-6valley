<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\TamaraService;

class ResetTamaraCircuitBreaker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tamara:reset-circuit-breaker {--force : Force reset even if within time window}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset Tamara circuit breaker to allow new API attempts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔄 Resetting Tamara Circuit Breaker...');
        
        // Get current state
        $failureCount = Cache::get('tamara_failure_count', 0);
        $lastFailure = Cache::get('tamara_last_failure');
        
        $this->info("📊 Current State:");
        $this->info("   • Failure Count: {$failureCount}");
        $this->info("   • Last Failure: " . ($lastFailure ? $lastFailure->format('Y-m-d H:i:s') : 'None'));
        
        if ($lastFailure) {
            $minutesSince = now()->diffInMinutes($lastFailure);
            $this->info("   • Minutes Since: {$minutesSince}");
        }
        
        // Check if force is needed
        if ($failureCount >= 3 && $lastFailure && now()->diffInMinutes($lastFailure) < 10 && !$this->option('force')) {
            $this->warn('⚠️  Circuit breaker is still within the 10-minute window.');
            $this->warn('   Use --force to reset anyway, or wait a few more minutes.');
            
            $timeLeft = 10 - now()->diffInMinutes($lastFailure);
            $this->info("   ⏱️  Time remaining: {$timeLeft} minutes");
            
            return 1;
        }
        
        // Reset circuit breaker
        Cache::forget('tamara_failure_count');
        Cache::forget('tamara_last_failure');
        
        Log::info('Tamara Circuit Breaker manually reset via console command');
        
        $this->info('✅ Circuit breaker reset successfully!');
        
        // Test service availability
        $this->info('🧪 Testing service availability...');
        
        try {
            $isAvailable = TamaraService::isServiceAvailable();
            
            if ($isAvailable) {
                $this->info('✅ Tamara service is now available for requests');
            } else {
                $this->warn('⚠️  Service might still be blocked by other factors');
            }
        } catch (\Exception $e) {
            $this->error("❌ Error testing service: {$e->getMessage()}");
        }
        
        $this->info('');
        $this->info('📝 Next steps:');
        $this->info('   1. Monitor logs for new Tamara requests');
        $this->info('   2. Check if the 500 errors persist');
        $this->info('   3. Consider using fallback payment methods if issues continue');
        
        return 0;
    }
}
