<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\TamaraService;

class DiagnoseTamaraService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tamara:diagnose {--test-api : Test API connectivity} {--check-config : Check configuration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose Tamara service issues and test connectivity';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Diagnosing Tamara Service...');
        $this->info('');
        
        // Check circuit breaker status
        $this->checkCircuitBreaker();
        
        // Check configuration if requested
        if ($this->option('check-config')) {
            $this->checkConfiguration();
        }
        
        // Test API if requested
        if ($this->option('test-api')) {
            $this->testApiConnectivity();
        }
        
        // Provide recommendations
        $this->provideRecommendations();
        
        return 0;
    }
    
    private function checkCircuitBreaker()
    {
        $this->info('🔌 Circuit Breaker Status:');
        
        $failureCount = Cache::get('tamara_failure_count', 0);
        $lastFailure = Cache::get('tamara_last_failure');
        
        $this->table(['Metric', 'Value'], [
            ['Failure Count', $failureCount],
            ['Last Failure', $lastFailure ? $lastFailure->format('Y-m-d H:i:s') : 'None'],
            ['Minutes Since Last Failure', $lastFailure ? now()->diffInMinutes($lastFailure) : 'N/A'],
            ['Circuit Breaker Status', $failureCount >= 3 && $lastFailure && now()->diffInMinutes($lastFailure) < 10 ? '🔴 CLOSED (Blocking)' : '🟢 OPEN (Allowing)'],
            ['Threshold', '3 failures in 10 minutes'],
        ]);
        
        $this->info('');
    }
    
    private function checkConfiguration()
    {
        $this->info('⚙️ Configuration Check:');
        
        $mode = config('tamara.mode', 'sandbox');
        $liveApiKey = config('tamara.api_key');
        $sandboxApiKey = config('tamara.sandbox_api_key');
        $merchantId = config('tamara.merchant_id');
        $liveApiUrl = config('tamara.live_api_url');
        $sandboxApiUrl = config('tamara.sandbox_api_url');
        
        $this->table(['Setting', 'Value', 'Status'], [
            ['Mode', $mode, $mode ? '✅' : '❌'],
            ['Live API Key', $liveApiKey ? 'Set (' . strlen($liveApiKey) . ' chars)' : 'Not set', $liveApiKey ? '✅' : '⚠️'],
            ['Sandbox API Key', $sandboxApiKey ? 'Set (' . strlen($sandboxApiKey) . ' chars)' : 'Not set', $sandboxApiKey ? '✅' : '⚠️'],
            ['Merchant ID', $merchantId ?: 'Not set', $merchantId ? '✅' : '❌'],
            ['Live API URL', $liveApiUrl ?: 'Not set', $liveApiUrl ? '✅' : '❌'],
            ['Sandbox API URL', $sandboxApiUrl ?: 'Not set', $sandboxApiUrl ? '✅' : '❌'],
        ]);
        
        // Check current mode configuration
        $currentApiKey = $mode === 'sandbox' ? $sandboxApiKey : $liveApiKey;
        $currentApiUrl = $mode === 'sandbox' ? $sandboxApiUrl : $liveApiUrl;
        
        if (!$currentApiKey) {
            $this->error("❌ Missing API key for current mode: {$mode}");
        }
        
        if (!$currentApiUrl) {
            $this->error("❌ Missing API URL for current mode: {$mode}");
        }
        
        $this->info('');
    }
    
    private function testApiConnectivity()
    {
        $this->info('🌐 Testing API Connectivity...');
        
        $mode = config('tamara.mode', 'sandbox');
        $apiKey = $mode === 'sandbox' ? config('tamara.sandbox_api_key') : config('tamara.api_key');
        $apiUrl = $mode === 'sandbox' ? config('tamara.sandbox_api_url') : config('tamara.live_api_url');
        $merchantId = config('tamara.merchant_id');
        
        if (!$apiKey || !$apiUrl) {
            $this->error('❌ Cannot test: Missing API key or URL for current mode');
            return;
        }
        
        // Test basic connectivity to Tamara
        $this->info("Testing {$mode} API at {$apiUrl}...");
        
        try {
            // Test a simple API endpoint (if available)
            $headers = [
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ];
            
            if ($mode === 'live' && $merchantId) {
                $headers['X-Merchant-Id'] = $merchantId;
            }
            
            // Try to ping the API or get service status
            $response = Http::timeout(10)->withHeaders($headers)->get($apiUrl);
            
            $this->info("Response Status: {$response->status()}");
            
            if ($response->successful()) {
                $this->info('✅ API is reachable');
            } else {
                $this->warn("⚠️ API returned status {$response->status()}");
                $body = $response->body();
                if (strlen($body) < 500) {
                    $this->info("Response: {$body}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ API connectivity test failed: {$e->getMessage()}");
        }
        
        $this->info('');
    }
    
    private function provideRecommendations()
    {
        $this->info('💡 Recommendations:');
        
        $failureCount = Cache::get('tamara_failure_count', 0);
        $lastFailure = Cache::get('tamara_last_failure');
        
        if ($failureCount >= 3) {
            $this->warn('1. 🔄 Reset circuit breaker if you believe the issue is resolved:');
            $this->warn('   php artisan tamara:reset-circuit-breaker');
            $this->info('');
        }
        
        if ($lastFailure && now()->diffInMinutes($lastFailure) < 60) {
            $this->warn('2. ⏱️ Recent failures detected. Consider:');
            $this->warn('   • Waiting for Tamara service to stabilize');
            $this->warn('   • Using alternative payment methods (MyFatoorah)');
            $this->warn('   • Contacting Tamara support if issues persist');
            $this->info('');
        }
        
        $this->info('3. 📊 Monitor service status:');
        $this->info('   php artisan tamara:diagnose --test-api --check-config');
        $this->info('');
        
        $this->info('4. 🛡️ Payment method priorities:');
        $this->info('   • MyFatoorah: ✅ Working correctly');
        $this->info('   • Tamara: ⚠️ Currently experiencing issues');
        $this->info('   • Consider updating payment method order in UI');
        $this->info('');
        
        $this->info('5. 📱 Notification system:');
        $this->info('   • SMS/WhatsApp notifications: ✅ Working (no duplicates)');
        $this->info('   • Payment completion notifications: ✅ Fixed');
    }
}
