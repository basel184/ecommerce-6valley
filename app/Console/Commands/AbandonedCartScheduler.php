<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AbandonedCartScheduler extends Command
{
    protected $signature = 'abandoned-cart:schedule {--hours=24 : Hours after last update to mark as abandoned} {--dry-run : Show what would be marked without actually marking}';
    protected $description = 'Schedule abandoned cart marking - can be run manually or through admin panel';

    public function handle()
    {
        $hours = $this->option('hours');
        $dryRun = $this->option('dry-run');
        
        $this->info("Searching for carts not updated in the last {$hours} hours...");
        
        $oldCarts = Cart::active()
            ->where('updated_at', '<', Carbon::now()->subHours($hours))
            ->with(['customer', 'productWithDetails'])
            ->get();

        if ($oldCarts->isEmpty()) {
            $this->info('No carts found to mark as abandoned.');
            return 0;
        }

        $this->info("Found {$oldCarts->count()} carts to mark as abandoned.");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No carts will be marked as abandoned');
            $this->table(
                ['ID', 'Customer', 'Product', 'Last Updated', 'Cart Group ID'],
                $oldCarts->take(10)->map(function ($cart) {
                    return [
                        $cart->id,
                        $cart->customer ? $cart->customer->f_name . ' ' . $cart->customer->l_name : 'Guest',
                        $cart->productWithDetails ? Str::limit($cart->productWithDetails->name, 30) : 'Product Not Found',
                        $cart->updated_at->format('Y-m-d H:i:s'),
                        $cart->cart_group_id
                    ];
                })
            );
            
            if ($oldCarts->count() > 10) {
                $remaining = $oldCarts->count() - 10;
                $this->info("... and {$remaining} more carts");
            }
            
            return 0;
        }

        if (!$this->confirm("Are you sure you want to mark {$oldCarts->count()} carts as abandoned?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info("Marking carts as abandoned...");
        
        $bar = $this->output->createProgressBar($oldCarts->count());
        $bar->start();

        $successCount = 0;
        $failedCount = 0;

        foreach ($oldCarts as $cart) {
            try {
                $cart->markAsAbandoned();
                $successCount++;
                
                Log::info('Cart marked as abandoned', [
                    'cart_id' => $cart->id,
                    'customer_id' => $cart->customer_id,
                    'product_id' => $cart->product_id,
                    'abandoned_at' => $cart->abandoned_at,
                    'command' => 'abandoned-cart:schedule'
                ]);
                
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Failed to mark cart as abandoned', [
                    'cart_id' => $cart->id,
                    'error' => $e->getMessage(),
                    'command' => 'abandoned-cart:schedule'
                ]);
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($successCount > 0) {
            $this->info("Successfully marked {$successCount} carts as abandoned!");
        }
        
        if ($failedCount > 0) {
            $this->warn("Failed to mark {$failedCount} carts as abandoned.");
        }

        $this->newLine();
        $this->info("Updated Statistics:");
        
        $totalAbandoned = Cart::abandoned()->count();
        $totalValue = Cart::abandoned()->sum(\DB::raw('price * quantity'));
        $recentAbandoned = Cart::abandoned()->where('abandoned_at', '>=', Carbon::now()->subHours(1))->count();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Abandoned Carts', $totalAbandoned],
                ['Total Value', number_format($totalValue, 2)],
                ['Recently Abandoned (Last Hour)', $recentAbandoned],
            ]
        );

        $this->newLine();
        $this->comment("Recommendations:");
        $this->comment("   - Run this command every 6-12 hours");
        $this->comment("   - Use --dry-run to test before applying");
        $this->comment("   - Monitor logs for any errors");

        return 0;
    }
}
