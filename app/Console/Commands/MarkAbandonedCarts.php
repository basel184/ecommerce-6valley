<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carts:mark-abandoned {--hours=24 : Hours after last update to mark as abandoned} {--dry-run : Show what would be marked without actually marking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark old carts as abandoned';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $dryRun = $this->option('dry-run');
        
        $this->info("Looking for carts not updated in the last {$hours} hours...");
        
        $oldCarts = Cart::active()
            ->where('updated_at', '<', Carbon::now()->subHours($hours))
            ->with(['customer', 'product'])
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
                $oldCarts->map(function ($cart) {
                    return [
                        $cart->id,
                        $cart->customer ? $cart->customer->f_name . ' ' . $cart->customer->l_name : 'Guest',
                        $cart->product ? $cart->product->name : 'Product Not Found',
                        $cart->updated_at->format('Y-m-d H:i:s'),
                        $cart->cart_group_id
                    ];
                })
            );
            return 0;
        }

        if (!$this->confirm("Are you sure you want to mark {$oldCarts->count()} carts as abandoned?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $count = 0;
        $progressBar = $this->output->createProgressBar($oldCarts->count());
        $progressBar->start();

        foreach ($oldCarts as $cart) {
            $cart->markAsAbandoned();
            $count++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Successfully marked {$count} carts as abandoned.");
        
        // Log the operation
        \Log::info('Carts marked as abandoned', [
            'count' => $count,
            'hours_threshold' => $hours,
            'executed_at' => now()
        ]);

        return 0;
    }
}
