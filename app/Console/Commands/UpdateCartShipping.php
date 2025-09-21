<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cart;
use App\Models\CartShipping;
use App\Models\ShippingMethod;

class UpdateCartShipping extends Command
{
    protected $signature = 'cart:update-shipping';
    protected $description = 'Update cart items with shipping costs';

    public function handle()
    {
        $this->info('Updating cart shipping costs...');
        
        // Update physical products in cart with shipping cost
        $updatedCarts = Cart::where('product_type', 'physical')
            ->where('shipping_cost', 0)
            ->update(['shipping_cost' => 25]);
        
        $this->info("Updated {$updatedCarts} cart items with shipping cost");
        
        // Create cart shipping records for order-wise shipping
        $cartGroups = Cart::where('product_type', 'physical')
            ->distinct()
            ->pluck('cart_group_id');
        
        $created = 0;
        $defaultShippingMethod = ShippingMethod::where('status', 1)->first();
        
        foreach ($cartGroups as $groupId) {
            $exists = CartShipping::where('cart_group_id', $groupId)->exists();
            if (!$exists) {
                CartShipping::create([
                    'cart_group_id' => $groupId,
                    'shipping_method_id' => $defaultShippingMethod ? $defaultShippingMethod->id : 2,
                    'shipping_cost' => 25
                ]);
                $created++;
            }
        }
        
        $this->info("Created {$created} cart shipping records");
        $this->info('Cart shipping update completed successfully!');
    }
}
