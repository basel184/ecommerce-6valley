<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Services\AbandonedCartService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanOldAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'abandoned-cart:clean {--days=90 : Days after abandonment to delete} {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old abandoned carts';

    /**
     * Execute the console command.
     */
    public function handle(AbandonedCartService $abandonedCartService)
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("Looking for abandoned carts older than {$days} days...");
        
        // Get old abandoned carts
        $oldCarts = Cart::abandoned()->where('abandoned_at', '<', Carbon::now()->subDays($days))->get();

        if ($oldCarts->isEmpty()) {
            $this->info('No old abandoned carts found to clean.');
            return 0;
        }

        $this->info("Found {$oldCarts->count()} old abandoned carts to clean.");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No carts will be deleted');
            $this->table(
                ['ID', 'Customer', 'Product', 'Abandoned Date', 'Reminders Sent'],
                $oldCarts->map(function ($cart) {
                    return [
                        $cart->id,
                        $cart->customer ? $cart->customer->f_name . ' ' . $cart->customer->l_name : 'Guest',
                        $cart->product ? $cart->product->name : 'Product Not Found',
                        $cart->abandoned_at->format('Y-m-d H:i:s'),
                        $cart->reminder_sent
                    ];
                })
            );
            return 0;
        }

        if (!$this->confirm("Are you sure you want to delete {$oldCarts->count()} old abandoned carts?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $deletedCount = $abandonedCartService->cleanOldCarts();
        
        $this->info("Successfully deleted {$deletedCount} old abandoned carts.");

        return 0;
    }
}
