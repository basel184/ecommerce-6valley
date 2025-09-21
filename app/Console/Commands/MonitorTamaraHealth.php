<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\TamaraService;
use App\Services\TaqnyatSmsService;

class MonitorTamaraHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tamara:monitor {--notify : Send SMS notification if issues detected} {--auto-reset : Auto reset circuit breaker if conditions are met}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Tamara service health and notify on issues';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Monitoring Tamara Service Health...');
        
        $healthStatus = $this->checkHealth();
        
        if ($this->option('notify') && !$healthStatus['healthy']) {
            $this->sendHealthAlert($healthStatus);
        }
        
        if ($this->option('auto-reset') && $healthStatus['can_auto_reset']) {
            $this->autoResetCircuitBreaker();
        }
        
        $this->displayHealthReport($healthStatus);
        
        return $healthStatus['healthy'] ? 0 : 1;
    }
    
    private function checkHealth()
    {
        $failureCount = Cache::get('tamara_failure_count', 0);
        $lastFailure = Cache::get('tamara_last_failure');
        $isCircuitBreakerClosed = $failureCount >= 3 && $lastFailure && now()->diffInMinutes($lastFailure) < 10;
        
        // Check if we can auto-reset (15+ minutes since last failure)
        $canAutoReset = $lastFailure && now()->diffInMinutes($lastFailure) > 15;
        
        // Check recent error patterns in logs
        $recentErrors = $this->checkRecentErrorPatterns();
        
        return [
            'healthy' => !$isCircuitBreakerClosed && $recentErrors['server_errors'] < 3,
            'failure_count' => $failureCount,
            'last_failure' => $lastFailure,
            'circuit_breaker_closed' => $isCircuitBreakerClosed,
            'can_auto_reset' => $canAutoReset,
            'recent_errors' => $recentErrors,
            'minutes_since_failure' => $lastFailure ? now()->diffInMinutes($lastFailure) : null,
        ];
    }
    
    private function checkRecentErrorPatterns()
    {
        // This is a simplified version - in production you'd parse actual logs
        return [
            'server_errors' => Cache::get('tamara_recent_500_errors', 0),
            'auth_errors' => Cache::get('tamara_recent_auth_errors', 0),
            'total_requests' => Cache::get('tamara_recent_requests', 0),
        ];
    }
    
    private function sendHealthAlert($healthStatus)
    {
        $this->info('📱 Sending health alert...');
        
        $message = "🚨 Tamara Service Alert - BERN\n\n";
        $message .= "الحالة: خدمة تمارا معطلة\n";
        $message .= "عدد الأخطاء: {$healthStatus['failure_count']}\n";
        
        if ($healthStatus['last_failure']) {
            $message .= "آخر خطأ: " . $healthStatus['last_failure']->format('H:i') . "\n";
        }
        
        $message .= "\nالإجراءات:\n";
        $message .= "• استخدام MyFatoorah كبديل\n";
        $message .= "• مراقبة تلقائية كل 15 دقيقة\n";
        
        if ($healthStatus['can_auto_reset']) {
            $message .= "• سيتم إعادة التفعيل تلقائياً\n";
        }
        
        try {
            $smsService = app(TaqnyatSmsService::class);
            $adminPhone = config('sms.taqnyat.admin_phone');
            
            if ($adminPhone) {
                $smsService->sendSms($adminPhone, $message);
                $this->info('✅ Health alert sent to admin');
                
                Log::info('Tamara health alert sent', [
                    'failure_count' => $healthStatus['failure_count'],
                    'circuit_breaker_status' => $healthStatus['circuit_breaker_closed'] ? 'closed' : 'open'
                ]);
            }
        } catch (\Exception $e) {
            $this->error("❌ Failed to send health alert: {$e->getMessage()}");
        }
    }
    
    private function autoResetCircuitBreaker()
    {
        $this->info('🔄 Auto-resetting circuit breaker...');
        
        Cache::forget('tamara_failure_count');
        Cache::forget('tamara_last_failure');
        
        Log::info('Tamara Circuit Breaker auto-reset after health monitoring');
        
        $this->info('✅ Circuit breaker auto-reset completed');
    }
    
    private function displayHealthReport($healthStatus)
    {
        $this->info('');
        $this->info('📊 Tamara Health Report:');
        
        $status = $healthStatus['healthy'] ? '🟢 HEALTHY' : '🔴 UNHEALTHY';
        $this->info("Overall Status: {$status}");
        
        $this->table(['Metric', 'Value', 'Status'], [
            [
                'Failure Count', 
                $healthStatus['failure_count'], 
                $healthStatus['failure_count'] < 3 ? '✅' : '❌'
            ],
            [
                'Circuit Breaker', 
                $healthStatus['circuit_breaker_closed'] ? 'CLOSED (Blocking)' : 'OPEN (Allowing)', 
                $healthStatus['circuit_breaker_closed'] ? '❌' : '✅'
            ],
            [
                'Last Failure', 
                $healthStatus['last_failure'] ? $healthStatus['last_failure']->format('Y-m-d H:i:s') : 'None', 
                $healthStatus['last_failure'] ? '⚠️' : '✅'
            ],
            [
                'Time Since Failure', 
                $healthStatus['minutes_since_failure'] ? $healthStatus['minutes_since_failure'] . ' minutes' : 'N/A', 
                $healthStatus['minutes_since_failure'] > 10 ? '✅' : '⚠️'
            ],
            [
                'Auto-Reset Available', 
                $healthStatus['can_auto_reset'] ? 'Yes' : 'No', 
                $healthStatus['can_auto_reset'] ? '✅' : '⏳'
            ]
        ]);
        
        $this->info('');
        
        if (!$healthStatus['healthy']) {
            $this->warn('🔧 Recommendations:');
            
            if ($healthStatus['circuit_breaker_closed']) {
                if ($healthStatus['can_auto_reset']) {
                    $this->warn('• Run with --auto-reset to automatically reset circuit breaker');
                } else {
                    $timeLeft = 10 - ($healthStatus['minutes_since_failure'] ?? 0);
                    $this->warn("• Wait {$timeLeft} more minutes or use --force to reset circuit breaker");
                }
            }
            
            $this->warn('• Monitor MyFatoorah as primary payment method');
            $this->warn('• Consider disabling Tamara temporarily in UI');
            $this->warn('• Contact Tamara support for prolonged issues');
        } else {
            $this->info('✅ Tamara service is healthy and available');
        }
    }
}
