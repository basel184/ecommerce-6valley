<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AbandonedCartCleaner extends Command
{
    protected $signature = 'abandoned-cart:clean {--days=90 : Days after abandonment to delete} {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Clean old abandoned carts to free up database space';

    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("Searching for abandoned carts older than {$days} days...");
        
        $oldCarts = Cart::abandoned()
            ->where('abandoned_at', '<', Carbon::now()->subDays($days))
            ->with(['customer', 'productWithDetails'])
            ->get();

        if ($oldCarts->isEmpty()) {
            $this->info('No old abandoned carts found to clean.');
            return 0;
        }

        $this->info("Found {$oldCarts->count()} old abandoned carts to clean.");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No carts will be deleted');
            $this->table(
                ['ID', 'Customer', 'Product', 'Abandoned Date', 'Reminders Sent'],
                $oldCarts->take(10)->map(function ($cart) {
                    return [
                        $cart->id,
                        $cart->customer ? $cart->customer->f_name . ' ' . $cart->customer->l_name : 'Guest',
                        $cart->productWithDetails ? Str::limit($cart->productWithDetails->name, 30) : 'Product Not Found',
                        $cart->abandoned_at->format('Y-m-d H:i:s'),
                        $cart->reminder_sent
                    ];
                })
            );
            
            if ($oldCarts->count() > 10) {
                $remaining = $oldCarts->count() - 10;
                $this->info("... and {$remaining} more carts");
            }
            
            return 0;
        }

        if (!$this->confirm("Are you sure you want to delete {$oldCarts->count()} old abandoned carts?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info("Cleaning old abandoned carts...");
        
        $bar = $this->output->createProgressBar($oldCarts->count());
        $bar->start();

        $successCount = 0;
        $failedCount = 0;

        foreach ($oldCarts as $cart) {
            try {
                $cartId = $cart->id;
                $cart->delete();
                $successCount++;
                
                Log::info('Old abandoned cart deleted', [
                    'cart_id' => $cartId,
                    'customer_id' => $cart->customer_id,
                    'product_id' => $cart->product_id,
                    'abandoned_at' => $cart->abandoned_at,
                    'command' => 'abandoned-cart:clean'
                ]);
                
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Failed to delete old abandoned cart', [
                    'cart_id' => $cart->id,
                    'error' => $e->getMessage(),
                    'command' => 'abandoned-cart:clean'
                ]);
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($successCount > 0) {
            $this->info("Successfully deleted {$successCount} old abandoned carts!");
        }
        
        if ($failedCount > 0) {
            $this->warn("Failed to delete {$failedCount} old abandoned carts.");
        }

        $this->newLine();
        $this->info("Updated Statistics:");
        
        $totalAbandoned = Cart::abandoned()->count();
        $totalValue = Cart::abandoned()->sum(\DB::raw('price * quantity'));
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Abandoned Carts', $totalAbandoned],
                ['Total Value', number_format($totalValue, 2)],
            ]
        );

        $this->newLine();
        $this->comment("Recommendations:");
        $this->comment("   - Run this command monthly to keep database clean");
        $this->comment("   - Use --dry-run to test before applying");
        $this->comment("   - Monitor logs for any errors");

        return 0;
    }
}


