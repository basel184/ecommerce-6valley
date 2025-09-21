<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Services\AbandonedCartService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAbandonedCartReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'abandoned-cart:send-reminders {--hours=24 : Hours after abandonment to send reminder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for abandoned carts';

    /**
     * Execute the console command.
     */
    public function handle(AbandonedCartService $abandonedCartService)
    {
        $hours = $this->option('hours');
        
        $this->info("Looking for abandoned carts older than {$hours} hours...");
        
        // Get abandoned carts that need reminders
        $abandonedCarts = Cart::abandoned()->where('abandoned_at', '<=', Carbon::now()->subHours($hours))
            ->where('reminder_sent', '<', 3)
            ->where('is_guest', 0) // Only send reminders to registered customers
            ->with(['customer', 'product'])
            ->get();

        if ($abandonedCarts->isEmpty()) {
            $this->info('No abandoned carts found that need reminders.');
            return 0;
        }

        $this->info("Found {$abandonedCarts->count()} abandoned carts to send reminders for.");

        $successCount = 0;
        $failedCount = 0;

        $progressBar = $this->output->createProgressBar($abandonedCarts->count());
        $progressBar->start();

        foreach ($abandonedCarts as $abandonedCart) {
            try {
                if ($abandonedCartService->sendReminder($abandonedCart)) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("Failed to send reminder for cart ID {$abandonedCart->id}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Reminders sent successfully: {$successCount}");
        $this->info("Failed to send reminders: {$failedCount}");

        return 0;
    }
}
