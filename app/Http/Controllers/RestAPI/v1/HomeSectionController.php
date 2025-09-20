<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\HomeSection;
use App\Utils\Helpers;
use Illuminate\Http\JsonResponse;

class HomeSectionController extends Controller
{
    public function list(): JsonResponse
    {
        $sections = HomeSection::with(['banners', 'categories', 'brands'])
            ->where('status', 1)
            ->orderBy('sort_order')
            ->get();

        $data = $sections->map(function ($s) {
            $item = [
                'id' => $s->id,
                'title' => $s->title,
                'slug' => $s->slug,
                'type' => $s->type,
                'banner_layout' => $s->banner_layout,
                'show_limit' => (int) $s->show_limit,
                'sort_order' => (int) $s->sort_order,
                'view_all_url' => 'collection/' . $s->slug,
            ];

            if ($s->type === 'products') {
                // Determine base query by feed_type
                $limit = (int)($s->show_limit ?: 8);
                $limit = max(1, min(100, $limit));
                $base = \App\Models\Product::query()->active();
                $feed = $s->feed_type ?? 'manual';
                // Expose feed info to client
                $item['feed_type'] = $feed;
                $item['feed_category_id'] = $s->feed_category_id ? (int)$s->feed_category_id : null;
                if ($feed === 'manual') {
                    $base = $s->products()->active();
                } elseif ($feed === 'category' && $s->feed_category_id) {
                    $catId = (int)$s->feed_category_id;
                    $base = $base->where(function($q) use ($catId){
                        $q->where('category_id', $catId)
                          ->orWhere('sub_category_id', $catId)
                          ->orWhere('sub_sub_category_id', $catId);
                    });
                    $base->orderByDesc('created_at');
                    // View all should navigate to shop by category
                    $item['view_all_url'] = 'shop?category=' . $catId;
                } elseif ($feed === 'best_selling') {
                    $base = $base->select('products.*')
                        ->selectRaw('(select sum(order_details.qty) from order_details where products.id = order_details.product_id) as order_qty_sum')
                        ->orderByDesc('order_qty_sum');
                } elseif ($feed === 'latest') {
                    $base = $base->orderByDesc('created_at');
                }
                $products = $base->take($limit)->get();
                $item['products'] = Helpers::product_data_formatting($products, true);
            } elseif ($s->type === 'categories') {
                $item['categories'] = $s->categories->take($s->show_limit)->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'name' => $c->name,
                        'slug' => $c->slug,
                        'icon_full_url' => $c->icon_full_url,
                    ];
                })->values();
            } elseif ($s->type === 'brands') {
                $item['brands'] = $s->brands->take($s->show_limit)->map(function ($b) {
                    return [
                        'id' => $b->id,
                        'name' => $b->name,
                        'image' => $b->image,
                    ];
                })->values();
            } else {
                $item['banners'] = $s->banners->map(function ($b) {
                    return [
                        'id' => $b->id,
                        'title' => $b->title,
                        'sub_title' => $b->sub_title,
                        'url' => $b->url,
                        'photo_full_url' => $b->photo_full_url,
                    ];
                })->values();
            }

            return $item;
        })->values();

        return response()->json($data, 200);
    }

    public function show(string $slug): JsonResponse
    {
        $s = HomeSection::with(['banners', 'categories', 'brands'])->where('slug', $slug)->where('status', 1)->first();
        if (!$s) return response()->json(['message' => 'Not found'], 404);

        $item = [
            'id' => $s->id,
            'title' => $s->title,
            'slug' => $s->slug,
            'type' => $s->type,
            'banner_layout' => $s->banner_layout,
            'show_limit' => (int) $s->show_limit,
            'sort_order' => (int) $s->sort_order,
        ];
        
        if ($s->type === 'categories') {
            $item['categories'] = $s->categories->take($s->show_limit)->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'icon_full_url' => $c->icon_full_url,
                ];
            })->values();
        } elseif ($s->type === 'brands') {
            $item['brands'] = $s->brands->take($s->show_limit)->map(function ($b) {
                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'image' => $b->image,
                ];
            })->values();
        } elseif ($s->type === 'banners') {
            $item['banners'] = $s->banners->map(function ($b) {
                return [
                    'id' => $b->id,
                    'title' => $b->title,
                    'sub_title' => $b->sub_title,
                    'url' => $b->url,
                    'photo_full_url' => $b->photo_full_url,
                ];
            })->values();
        }
        return response()->json($item, 200);
    }

    public function productsBySlug(\Illuminate\Http\Request $request, string $slug): JsonResponse
    {
        $limit = (int)($request->get('limit', 20));
        $offset = (int)($request->get('offset', 0));
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);

        $section = HomeSection::where('slug', $slug)->where('status', 1)->first();
        if (!$section) return response()->json(['message' => 'Not found'], 404);
        if ($section->type !== 'products') return response()->json(['message' => 'Section is not products type'], 400);

        $feed = $section->feed_type ?? 'manual';
        if ($feed === 'manual') {
            $query = $section->products()->active();
        } else {
            $query = \App\Models\Product::query()->active();
            if ($feed === 'category' && $section->feed_category_id) {
                $catId = (int)$section->feed_category_id;
                $query->where(function($q) use ($catId){
                    $q->where('category_id', $catId)
                      ->orWhere('sub_category_id', $catId)
                      ->orWhere('sub_sub_category_id', $catId);
                });
            } elseif ($feed === 'best_selling') {
                $query->select('products.*')
                    ->selectRaw('(select sum(order_details.qty) from order_details where products.id = order_details.product_id) as order_qty_sum');
            } elseif ($feed === 'latest') {
                // default sort set later
            }
        }

        // Search (supports translated names via morph relation 'translations')
        $search = trim((string)$request->get('q', ''));
        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('code', 'like', $like)
                  ->orWhereHas('translations', function ($t) use ($like) {
                      $t->where('key', 'name')->where('value', 'like', $like);
                  });
            });
        }

        // Brand filter (brand_id or brand_ids comma-separated)
        $brandIdsParam = $request->get('brand_id', $request->get('brand_ids'));
        if ($brandIdsParam !== null && $brandIdsParam !== '') {
            $brandIds = collect(explode(',', (string)$brandIdsParam))
                ->map(fn($v) => (int)trim((string)$v))
                ->filter()
                ->unique()
                ->values();
            if ($brandIds->count() > 0) {
                $query->whereIn('brand_id', $brandIds->all());
            }
        }

        // Category filter (category_id or categories comma-separated) matches any of category/sub_category/sub_sub_category
        $catParam = $request->get('category_id', $request->get('categories'));
        if ($catParam !== null && $catParam !== '') {
            $catIds = collect(explode(',', (string)$catParam))
                ->map(fn($v) => (int)trim((string)$v))
                ->filter()
                ->unique()
                ->values();
            if ($catIds->count() > 0) {
                $query->where(function($q) use ($catIds) {
                    $q->whereIn('category_id', $catIds->all())
                      ->orWhereIn('sub_category_id', $catIds->all())
                      ->orWhereIn('sub_sub_category_id', $catIds->all());
                });
            }
        }

        // Price range on effective (discounted) price
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $priceExpr = "(CASE WHEN discount_type = 'percent' THEN unit_price - (unit_price * discount/100) WHEN discount_type = 'amount' THEN unit_price - discount ELSE unit_price END)";
        if ($minPrice !== null && is_numeric($minPrice)) {
            $query->whereRaw("{$priceExpr} >= ?", [(float)$minPrice]);
        }
        if ($maxPrice !== null && is_numeric($maxPrice)) {
            $query->whereRaw("{$priceExpr} <= ?", [(float)$maxPrice]);
        }

        // Sort (override any default pivot sort_order using reorder)
        $sort = (string)$request->get('sort', $feed === 'manual' ? 'latest' : ($feed === 'best_selling' ? 'best_selling' : 'latest'));
        $query->reorder();
        switch ($sort) {
            case 'price_asc':
                $query->orderByRaw("{$priceExpr} asc");
                break;
            case 'price_desc':
                $query->orderByRaw("{$priceExpr} desc");
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'best_selling':
                $query->orderByDesc('order_qty_sum');
                break;
            case 'latest':
            default:
                $query->orderByDesc('created_at');
                break;
        }

        $total = (clone $query)->count();
        $products = $query->skip($offset)->take($limit)->get();
        $formatted = Helpers::product_data_formatting($products, true);

        return response()->json([
            'total_size' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'products' => $formatted,
        ], 200);
    }

    public function facetsBySlug(string $slug): JsonResponse
    {
        $section = HomeSection::where('slug', $slug)->where('status', 1)->first();
        if (!$section) return response()->json(['message' => 'Not found'], 404);
        if ($section->type !== 'products') return response()->json(['message' => 'Section is not products type'], 400);

        $feed = $section->feed_type ?? 'manual';
        if ($feed === 'manual') {
            $base = $section->products()->active();
        } else {
            $base = \App\Models\Product::query()->active();
            if ($feed === 'category' && $section->feed_category_id) {
                $catId = (int)$section->feed_category_id;
                $base->where(function($q) use ($catId){
                    $q->where('category_id', $catId)
                      ->orWhere('sub_category_id', $catId)
                      ->orWhere('sub_sub_category_id', $catId);
                });
            } elseif ($feed === 'best_selling') {
                // no special where; facets over all active products
            } elseif ($feed === 'latest') {
                // same
            }
        }

        // Brands present in section
        $brandIds = (clone $base)->whereNotNull('brand_id')->distinct()->pluck('brand_id')->filter()->values();
        $brands = \App\Models\Brand::whereIn('id', $brandIds)->select('id', 'name')->get()->map(function($b){
            return ['id' => $b->id, 'name' => $b->name];
        })->values();

        // Categories present in section (any level)
        $cat1 = (clone $base)->whereNotNull('category_id')->distinct()->pluck('category_id');
        $cat2 = (clone $base)->whereNotNull('sub_category_id')->distinct()->pluck('sub_category_id');
        $cat3 = (clone $base)->whereNotNull('sub_sub_category_id')->distinct()->pluck('sub_sub_category_id');
        $catIds = $cat1->merge($cat2)->merge($cat3)->filter()->unique()->values();
        $categories = \App\Models\Category::whereIn('id', $catIds)->select('id','name')->get()->map(function($c){
            return ['id' => $c->id, 'name' => $c->name];
        })->values();

        // Price range
        $minMax = (clone $base)->selectRaw('MIN(unit_price) as min_price, MAX(unit_price) as max_price')->first();
        $price = [
            'min' => (float)($minMax->min_price ?? 0),
            'max' => (float)($minMax->max_price ?? 0),
        ];

        return response()->json([
            'brands' => $brands,
            'categories' => $categories,
            'price' => $price,
        ], 200);
    }
}
