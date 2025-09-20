<?php

namespace App\Http\Controllers\Admin\Homepage;

use App\Http\Controllers\BaseController;
use App\Models\HomeSection;
use App\Models\HomeSectionProduct;
use App\Models\Product;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HomeSectionController extends BaseController
{
    public function index(?Request $request, string $type = null): View|\Illuminate\Database\Eloquent\Collection|\Illuminate\Pagination\LengthAwarePaginator|null|callable|RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $sections = HomeSection::query()
            ->orderBy('sort_order')
            ->paginate(20);
        return view('admin-views.homepage.sections.index', compact('sections'));
    }

    public function create(): View
    {
        $categories = \App\Models\Category::select('id','name')->orderBy('name')->get();
        $brands = \App\Models\Brand::select('id','name')->orderBy('name')->get();
        return view('admin-views.homepage.sections.create', compact('categories', 'brands'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'slug' => ['nullable','string','max:255'],
            'show_limit' => ['nullable','integer','min:1','max:100'],
            'sort_order' => ['nullable','integer','min:0','max:1000'],
            'type' => ['nullable','in:products,banners,categories,brands'],
            'banner_layout' => ['nullable','in:slider,grid_1,grid_2,grid_3'],
            'feed_type' => ['nullable','in:manual,category,best_selling,latest'],
            'feed_category_id' => ['nullable','integer','exists:categories,id'],
        ]);
        // Ensure slug uniqueness by generating a unique slug before insert
        $base = $request->string('slug')->toString() ?: $request->string('title')->toString();
        $data['slug'] = $this->makeUniqueSlug($base);
        // Coerce checkbox presence to boolean to avoid locale/value quirks
        $data['status'] = $request->has('status');
        // Defaults for optional fields
        $data['type'] = $data['type'] ?? 'products';
        $data['banner_layout'] = $data['banner_layout'] ?? 'slider';
        $data['feed_type'] = $data['feed_type'] ?? 'manual';
        if (($data['feed_type'] ?? 'manual') !== 'category') {
            $data['feed_category_id'] = null;
        }
        $section = HomeSection::create($data);
        
        // Handle assigned categories (optional)
        if ($request->has('category_ids')) {
            $ids = collect($request->input('category_ids', []))
                ->filter(fn($v) => is_numeric($v))
                ->map(fn($v) => (int)$v)
                ->values();
            if ($ids->isNotEmpty()) {
                $syncData = [];
                $sort = 0;
                foreach ($ids as $cid) {
                    $syncData[$cid] = ['sort_order' => $sort++];
                }
                $section->categories()->sync($syncData);
            }
        }

        // Handle assigned brands (optional)
        if ($request->has('brand_ids')) {
            $ids = collect($request->input('brand_ids', []))
                ->filter(fn($v) => is_numeric($v))
                ->map(fn($v) => (int)$v)
                ->values();
            if ($ids->isNotEmpty()) {
                $syncData = [];
                $sort = 0;
                foreach ($ids as $bid) {
                    $syncData[$bid] = ['sort_order' => $sort++];
                }
                $section->brands()->sync($syncData);
            }
        }
        
        ToastMagic::success(translate('created_successfully'));
    return redirect()->route('admin.pages-and-media.homepage.sections.edit', $section->id);
    }

    public function edit(int $id): View
    {
        $section = HomeSection::findOrFail($id);
        $categories = \App\Models\Category::select('id','name')->orderBy('name')->get();
        $brands = \App\Models\Brand::select('id','name')->orderBy('name')->get();
        $assignedProductIds = $section->products()->pluck('products.id')->toArray();
        $assignedCategoryIds = $section->categories()->pluck('categories.id')->toArray();
        $assignedBrandIds = $section->brands()->pluck('brands.id')->toArray();
        
        // Always include assigned products in the options so they don't get lost on sync
        $assignedProducts = collect([]);
        if (!empty($assignedProductIds)) {
            $assignedProducts = Product::query()->whereIn('id', $assignedProductIds)->get();
        }
        $recentProducts = Product::query()->active()->latest()->limit(100)->get();
        $products = $assignedProducts->concat($recentProducts)->unique('id')->values();
        return view('admin-views.homepage.sections.edit', compact('section','products','assignedProductIds','categories','brands','assignedCategoryIds','assignedBrandIds'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $section = HomeSection::findOrFail($id);
    $isProductsSyncOnly = $request->has('product_ids') && !$request->has('title');
    $isBannersSyncOnly = $request->has('banner_ids') && !$request->has('title');
    $isBannerUpload = $request->hasFile('banner_files') || $request->hasFile('banner_file_1') || $request->hasFile('banner_file_2') || $request->hasFile('banner_file_3');
    $hasUploadIntent = $request->filled('use_banner_layout') || $isBannerUpload;

        // Detach a single banner if requested
        if ($request->filled('remove_banner_id')) {
            $section->banners()->detach($request->integer('remove_banner_id'));
            ToastMagic::success(translate('removed_successfully'));
            return back();
        }

        // Only run full validation when it's a general edit (not a banners/products-only sync or banner upload intent)
        if (!($isProductsSyncOnly || $isBannersSyncOnly || $hasUploadIntent)) {
            $data = $request->validate([
                'title' => ['required','string','max:255'],
                'slug' => ["nullable","string","max:255"] ,
                'show_limit' => ['nullable','integer','min:1','max:100'],
                'sort_order' => ['nullable','integer','min:0','max:1000'],
                'type' => ['nullable','in:products,banners,categories,brands'],
                'banner_layout' => ['nullable','in:slider,grid_1,grid_2,grid_3'],
                'feed_type' => ['nullable','in:manual,category,best_selling,latest'],
                'feed_category_id' => ['nullable','integer','exists:categories,id'],
            ]);
            // Coerce checkbox presence to boolean to avoid locale/value quirks
            $data['status'] = $request->has('status');
            if (!isset($data['type'])) {
                $data['type'] = $section->type ?? 'products';
            }
            if (!isset($data['banner_layout'])) {
                $data['banner_layout'] = $section->banner_layout ?? 'slider';
            }
            // Normalize feed fields
            $data['feed_type'] = $data['feed_type'] ?? $section->feed_type ?? 'manual';
            if (($data['feed_type'] ?? 'manual') !== 'category') {
                $data['feed_category_id'] = null;
            }
            // Regenerate slug (from provided slug or title) and ensure uniqueness excluding current row
            $base = $request->string('slug')->toString() ?: $request->string('title')->toString();
            $data['slug'] = $this->makeUniqueSlug($base, $section->id);
            $section->update($data);
        }

        // Handle assigned products (optional)
        if ($request->has('product_ids')) {
            $ids = collect($request->input('product_ids', []))
                ->filter(fn($v) => is_numeric($v))
                ->map(fn($v) => (int)$v)
                ->values();
            // If no ids submitted (empty multi-select), keep current assignment to avoid accidental wipe
            if ($ids->isNotEmpty()) {
                $syncData = [];
                $sort = 0;
                foreach ($ids as $pid) {
                    $syncData[$pid] = ['sort_order' => $sort++];
                }
                $section->products()->sync($syncData);
            }
        }

        // Handle assigned categories (optional)
        if ($request->has('category_ids')) {
            $ids = collect($request->input('category_ids', []))
                ->filter(fn($v) => is_numeric($v))
                ->map(fn($v) => (int)$v)
                ->values();
            if ($ids->isNotEmpty()) {
                $syncData = [];
                $sort = 0;
                foreach ($ids as $cid) {
                    $syncData[$cid] = ['sort_order' => $sort++];
                }
                $section->categories()->sync($syncData);
            }
        }

        // Handle assigned brands (optional)
        if ($request->has('brand_ids')) {
            $ids = collect($request->input('brand_ids', []))
                ->filter(fn($v) => is_numeric($v))
                ->map(fn($v) => (int)$v)
                ->values();
            if ($ids->isNotEmpty()) {
                $syncData = [];
                $sort = 0;
                foreach ($ids as $bid) {
                    $syncData[$bid] = ['sort_order' => $sort++];
                }
                $section->brands()->sync($syncData);
            }
        }

        // Handle assigned banners (optional)
        if ($request->has('banner_ids')) {
            $bids = collect($request->input('banner_ids', []))
                ->filter(fn($v) => is_numeric($v))
                ->map(fn($v) => (int)$v)
                ->values();
            $syncBanners = [];
            $sortB = 0;
            foreach ($bids as $bid) {
                $syncBanners[$bid] = ['sort_order' => $sortB++];
            }
            $section->banners()->sync($syncBanners);
        }

    // Handle file uploads for banners
    $hasSliderFiles = $request->hasFile('banner_files');
    $hasGrid1 = $request->hasFile('banner_file_1') && !$request->hasFile('banner_file_2') && !$request->hasFile('banner_file_3');
    $hasGrid2 = $request->hasFile('banner_file_1') && $request->hasFile('banner_file_2') && !$request->hasFile('banner_file_3');
    $hasGrid3 = $request->hasFile('banner_file_1') && $request->hasFile('banner_file_2') && $request->hasFile('banner_file_3');

        if ($hasSliderFiles || $hasGrid1 || $hasGrid2 || $hasGrid3) {
            $layoutHint = $request->string('use_banner_layout')->toString() ?: $section->banner_layout;
            // Ensure section is marked as banners type and layout saved when uploading
            if ($layoutHint && $section->banner_layout !== $layoutHint) {
                $section->banner_layout = $layoutHint;
            }
            if ($section->type !== 'banners') {
                $section->type = 'banners';
            }
            if ($section->isDirty(['banner_layout','type'])) {
                $section->save();
            }
            $createdIds = [];

            // Slider: append all images
            if ($layoutHint === 'slider' && $hasSliderFiles) {
                $startSort = $section->banners()->count();
                $sort = $startSort;
                $bannerLinks = $request->input('banner_links', []);
                $linksArray = is_string($bannerLinks) ? array_filter(explode(',', $bannerLinks)) : $bannerLinks;
                
                foreach ($request->file('banner_files', []) as $index => $file) {
                    $path = $file->store('banner', 'public');
                    $filename = basename($path);
                    $url = isset($linksArray[$index]) ? trim($linksArray[$index]) : '#';
                    $bn = \App\Models\Banner::create([
                        'photo' => $filename,
                        'banner_type' => 'Section Banner',
                        'theme' => 'all',
                        'published' => 1,
                        'url' => $url,
                    ]);
                    $section->banners()->attach($bn->id, ['sort_order' => $sort++]);
                }
                ToastMagic::success(translate('uploaded_successfully'));
                return back();
            }

            // Grids: replace existing with up to N images
            if (in_array($layoutHint, ['grid_1','grid_2','grid_3'])) {
                $files = [];
                $links = [];
                if ($request->hasFile('banner_file_1')) {
                    $files[] = $request->file('banner_file_1');
                    $links[] = $request->input('banner_link_1', '#');
                }
                if ($request->hasFile('banner_file_2')) {
                    $files[] = $request->file('banner_file_2');
                    $links[] = $request->input('banner_link_2', '#');
                }
                if ($request->hasFile('banner_file_3')) {
                    $files[] = $request->file('banner_file_3');
                    $links[] = $request->input('banner_link_3', '#');
                }

                // Determine required count
                $required = $layoutHint === 'grid_1' ? 1 : ($layoutHint === 'grid_2' ? 2 : 3);
                // Detach old ones
                $section->banners()->detach();
                $sort = 0;
                foreach ($files as $idx => $file) {
                    if ($idx >= $required) break;
                    $path = $file->store('banner', 'public');
                    $filename = basename($path);
                    $url = isset($links[$idx]) ? trim($links[$idx]) : '#';
                    $bn = \App\Models\Banner::create([
                        'photo' => $filename,
                        'banner_type' => 'Section Banner',
                        'theme' => 'all',
                        'published' => 1,
                        'url' => $url,
                    ]);
                    $section->banners()->attach($bn->id, ['sort_order' => $sort++]);
                }
                ToastMagic::success(translate('updated_successfully'));
                return back();
            }
        } elseif ($hasUploadIntent) {
            // User clicked Upload without attaching files; avoid full validation and show a helpful message
            ToastMagic::error(translate('please_select_image_files_to_upload'));
            return back();
        }

        ToastMagic::success(translate('updated_successfully'));
        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        $section = HomeSection::findOrFail($id);
        $section->delete();
        ToastMagic::success(translate('deleted_successfully'));
    return redirect()->route('admin.pages-and-media.homepage.sections.index');
    }

    public function addProduct(Request $request, int $id): RedirectResponse
    {
        $section = HomeSection::findOrFail($id);
        $request->validate([
            'product_id' => ['required','integer','exists:products,id']
        ]);
        $maxOrder = HomeSectionProduct::where('home_section_id', $section->id)->max('sort_order') ?? 0;
        HomeSectionProduct::firstOrCreate([
            'home_section_id' => $section->id,
            'product_id' => $request->integer('product_id'),
        ], [
            'sort_order' => $maxOrder + 1,
        ]);
        ToastMagic::success(translate('added_successfully'));
        return back();
    }

    public function removeProduct(int $id, int $productId): RedirectResponse
    {
        HomeSectionProduct::where('home_section_id', $id)->where('product_id', $productId)->delete();
        ToastMagic::success(translate('removed_successfully'));
        return back();
    }

    public function removeCategory(int $id, int $categoryId): RedirectResponse
    {
        $section = HomeSection::findOrFail($id);
        $section->categories()->detach($categoryId);
        ToastMagic::success(translate('removed_successfully'));
        return back();
    }

    public function removeBrand(int $id, int $brandId): RedirectResponse
    {
        $section = HomeSection::findOrFail($id);
        $section->brands()->detach($brandId);
        ToastMagic::success(translate('removed_successfully'));
        return back();
    }

    /**
     * Build a unique slug from base string, optionally excluding a specific ID.
     */
    private function makeUniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base);
        if ($slug === '') {
            $slug = Str::random(8);
        }
        $original = $slug;
        $i = 2;
        while (HomeSection::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '<>', $ignoreId))
            ->exists()) {
            $slug = $original.'-'.$i;
            $i++;
            if ($i > 1000) break; // safety
        }
        return $slug;
    }
}
