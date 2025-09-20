<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Product;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Brand;
use App\Models\HomeSectionProduct;

class HomeSection extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'show_limit',
        'sort_order',
        'status',
        'type',
        'banner_layout',
        'feed_type',
        'feed_category_id',
    ];

    protected $casts = [
        'show_limit' => 'integer',
        'sort_order' => 'integer',
        'status' => 'boolean',
        'feed_category_id' => 'integer',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'home_section_products')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('home_section_products.sort_order');
    }

    public function productLinks(): HasMany
    {
        return $this->hasMany(HomeSectionProduct::class);
    }

    public function banners(): BelongsToMany
    {
        return $this->belongsToMany(Banner::class, 'home_section_banners')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('home_section_banners.sort_order');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'home_section_categories')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('home_section_categories.sort_order');
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'home_section_brands')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('home_section_brands.sort_order');
    }
}
