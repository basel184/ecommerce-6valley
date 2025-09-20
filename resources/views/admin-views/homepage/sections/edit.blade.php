@extends('layouts.admin.app')

@section('title', translate('edit_homepage_section'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <h2 class="h1 mb-0 text-capitalize">{{ translate('edit_homepage_section') }}</h2>
            <a href="{{ route('admin.pages-and-media.homepage.sections.index') }}" class="btn btn-secondary">{{ translate('back') }}</a>
        </div>
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.pages-and-media.homepage.sections.update', $section->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">{{ translate('title') }}</label>
                                <input type="text" name="title" class="form-control" value="{{ $section->title }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ translate('slug') }}</label>
                                <input type="text" name="slug" class="form-control" value="{{ $section->slug }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ translate('section_type') }}</label>
                                <select name="type" class="form-select" id="section-type-select">
                                    <option value="products" {{ $section->type === 'products' ? 'selected' : '' }}>{{ translate('products') }}</option>
                                    <option value="banners" {{ $section->type === 'banners' ? 'selected' : '' }}>{{ translate('banners') }}</option>
                                    <option value="categories" {{ $section->type === 'categories' ? 'selected' : '' }}>{{ translate('categories') }}</option>
                                    <option value="brands" {{ $section->type === 'brands' ? 'selected' : '' }}>{{ translate('brands') }}</option>
                                </select>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ translate('show_limit') }}</label>
                                    <input type="number" name="show_limit" class="form-control" value="{{ $section->show_limit }}" min="1" max="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ translate('sort_order') }}</label>
                                    <input type="number" name="sort_order" class="form-control" value="{{ $section->sort_order }}" min="0" max="1000">
                                </div>
                            </div>
                            <div class="row g-3 mt-1" id="product-feed-row">
                                <div class="col-md-6">
                                    <label class="form-label">{{ translate('products_source') }}</label>
                                    <select name="feed_type" class="form-select" id="feed-type-select">
                                        <option value="manual" {{ ($section->feed_type ?? 'manual') === 'manual' ? 'selected' : '' }}>{{ translate('manual_selection') }}</option>
                                        <option value="category" {{ ($section->feed_type ?? '') === 'category' ? 'selected' : '' }}>{{ translate('by_category') }}</option>
                                        <option value="best_selling" {{ ($section->feed_type ?? '') === 'best_selling' ? 'selected' : '' }}>{{ translate('best_selling') }}</option>
                                        <option value="latest" {{ ($section->feed_type ?? '') === 'latest' ? 'selected' : '' }}>{{ translate('latest_products') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="feed-category-row">
                                    <label class="form-label">{{ translate('category') }}</label>
                                    <select name="feed_category_id" class="form-select">
                                        <option value="">-- {{ translate('select') }} --</option>
                                        @foreach(($categories ?? []) as $cat)
                                            <option value="{{ $cat->id }}" {{ (int)($section->feed_category_id ?? 0) === (int)$cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 mt-3" id="banner-layout-row">
                                <label class="form-label">{{ translate('banner_layout') }}</label>
                                <select name="banner_layout" class="form-select" id="banner-layout-select">
                                    <option value="slider" {{ $section->banner_layout === 'slider' ? 'selected' : '' }}>{{ translate('slider') }}</option>
                                    <option value="grid_1" {{ $section->banner_layout === 'grid_1' ? 'selected' : '' }}>{{ translate('1_column') }}</option>
                                    <option value="grid_2" {{ $section->banner_layout === 'grid_2' ? 'selected' : '' }}>{{ translate('2_columns') }}</option>
                                    <option value="grid_3" {{ $section->banner_layout === 'grid_3' ? 'selected' : '' }}>{{ translate('3_columns') }}</option>
                                </select>
                            </div>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="status" id="status" {{ $section->status ? 'checked' : '' }}>
                                <label class="form-check-label" for="status">{{ translate('active') }}</label>
                            </div>
                            <div class="mt-4">
                                <button class="btn btn-primary" type="submit">{{ translate('save_changes') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card" id="products-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ translate('assign_products') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.pages-and-media.homepage.sections.update', $section->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">{{ translate('selected_products') }}</label>
                                <select name="product_ids[]" class="form-select js-example-responsive" multiple size="12">
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ in_array($product->id, $assignedProductIds) ? 'selected' : '' }}>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">{{ translate('hold_ctrl_or_cmd_to_select_multiple') }}</div>
                            </div>
                            <button class="btn btn-outline-primary" type="submit">{{ translate('update_products') }}</button>
                        </form>
                        <hr>
                        <div class="mt-3">
                            <h6 class="mb-2">{{ translate('current_products') }}</h6>
                            <ol class="mb-0">
                                @foreach($section->products as $p)
                                    <li class="d-flex justify-content-between align-items-center py-1">
                                        <span>{{ $p->name }}</span>
                                        <form class="d-inline" action="{{ route('admin.pages-and-media.homepage.sections.remove-product', [$section->id, $p->id]) }}" method="POST" onsubmit="return confirm('{{ translate('are_you_sure') }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ translate('remove') }}</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="card" id="categories-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ translate('assign_categories') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.pages-and-media.homepage.sections.update', $section->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">{{ translate('selected_categories') }}</label>
                                <select name="category_ids[]" class="form-select js-example-responsive" multiple size="12">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ in_array($category->id, $assignedCategoryIds ?? []) ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">{{ translate('hold_ctrl_or_cmd_to_select_multiple') }}</div>
                            </div>
                            <button class="btn btn-outline-primary" type="submit">{{ translate('update_categories') }}</button>
                        </form>
                        <hr>
                        <div class="mt-3">
                            <h6 class="mb-2">{{ translate('current_categories') }}</h6>
                            <ol class="mb-0">
                                @foreach($section->categories ?? [] as $c)
                                    <li class="d-flex justify-content-between align-items-center py-1">
                                        <span>{{ $c->name }}</span>
                                        <form class="d-inline" action="{{ route('admin.pages-and-media.homepage.sections.remove-category', [$section->id, $c->id]) }}" method="POST" onsubmit="return confirm('{{ translate('are_you_sure') }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ translate('remove') }}</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="card" id="brands-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ translate('assign_brands') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.pages-and-media.homepage.sections.update', $section->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">{{ translate('selected_brands') }}</label>
                                <select name="brand_ids[]" class="form-select js-example-responsive" multiple size="12">
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ in_array($brand->id, $assignedBrandIds ?? []) ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">{{ translate('hold_ctrl_or_cmd_to_select_multiple') }}</div>
                            </div>
                            <button class="btn btn-outline-primary" type="submit">{{ translate('update_brands') }}</button>
                        </form>
                        <hr>
                        <div class="mt-3">
                            <h6 class="mb-2">{{ translate('current_brands') }}</h6>
                            <ol class="mb-0">
                                @foreach($section->brands ?? [] as $b)
                                    <li class="d-flex justify-content-between align-items-center py-1">
                                        <span>{{ $b->name }}</span>
                                        <form class="d-inline" action="{{ route('admin.pages-and-media.homepage.sections.remove-brand', [$section->id, $b->id]) }}" method="POST" onsubmit="return confirm('{{ translate('are_you_sure') }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ translate('remove') }}</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="card" id="banners-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ translate('banners') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="mb-2">{{ translate('current_banners') }}</h6>
                            <div class="row g-3">
                                @forelse($section->banners as $bn)
                                    <div class="col-6 col-md-4">
                                        <div class="border rounded p-2 h-100 d-flex flex-column">
                                            <img src="{{ getStorageImages(path: $bn->photo_full_url, type: 'banner') }}" class="img-fluid rounded mb-2" alt="banner">
                                            <div class="mb-2">
                                                <small class="text-muted">{{ translate('link') }}:</small>
                                                <div class="small">{{ $bn->url ?: translate('no_link') }}</div>
                                            </div>
                                            <form action="{{ route('admin.pages-and-media.homepage.sections.update', $section->id) }}" method="POST" onsubmit="return confirm('{{ translate('are_you_sure') }}?')">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="remove_banner_id" value="{{ $bn->id }}">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ translate('remove') }}</button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12"><em>{{ translate('no_banners_yet') }}</em></div>
                                @endforelse
                            </div>
                        </div>
                        <hr>
                        <div class="mt-3">
                            <h6 class="mb-2">{{ translate('upload_banners') }}</h6>
                            <form action="{{ route('admin.pages-and-media.homepage.sections.update', $section->id) }}" method="POST" enctype="multipart/form-data" id="banner-upload-form">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="use_banner_layout" id="use-banner-layout" value="{{ $section->banner_layout }}">
                                <div id="upload-slider" class="mb-3">
                                    <label class="form-label">{{ translate('slider_images') }}</label>
                                    <input type="file" name="banner_files[]" class="form-control" accept="image/*" multiple>
                                    <div class="form-text">{{ translate('you_can_add_multiple_images_for_slider') }}</div>
                                    <div class="mt-2">
                                        <label class="form-label">{{ translate('banner_links') }} ({{ translate('optional') }})</label>
                                        <input type="text" name="banner_links[]" class="form-control" placeholder="{{ translate('enter_links_separated_by_comma') }}">
                                        <div class="form-text">{{ translate('enter_links_in_same_order_as_images') }}</div>
                                    </div>
                                </div>
                                <div id="upload-grid-1" class="mb-3">
                                    <label class="form-label">{{ translate('image') }} (1)</label>
                                    <input type="file" name="banner_file_1" class="form-control" accept="image/*">
                                    <div class="mt-2">
                                        <label class="form-label">{{ translate('link') }} ({{ translate('optional') }})</label>
                                        <input type="url" name="banner_link_1" class="form-control" placeholder="{{ translate('enter_link') }}">
                                    </div>
                                </div>
                                <div id="upload-grid-2" class="mb-3">
                                    <label class="form-label">{{ translate('images') }} (2)</label>
                                    <input type="file" name="banner_file_1" class="form-control mb-2" accept="image/*">
                                    <input type="file" name="banner_file_2" class="form-control" accept="image/*">
                                    <div class="mt-2">
                                        <label class="form-label">{{ translate('links') }} ({{ translate('optional') }})</label>
                                        <input type="url" name="banner_link_1" class="form-control mb-2" placeholder="{{ translate('link_for_image_1') }}">
                                        <input type="url" name="banner_link_2" class="form-control" placeholder="{{ translate('link_for_image_2') }}">
                                    </div>
                                </div>
                                <div id="upload-grid-3" class="mb-3">
                                    <label class="form-label">{{ translate('images') }} (3)</label>
                                    <input type="file" name="banner_file_1" class="form-control mb-2" accept="image/*">
                                    <input type="file" name="banner_file_2" class="form-control mb-2" accept="image/*">
                                    <input type="file" name="banner_file_3" class="form-control" accept="image/*">
                                    <div class="mt-2">
                                        <label class="form-label">{{ translate('links') }} ({{ translate('optional') }})</label>
                                        <input type="url" name="banner_link_1" class="form-control mb-2" placeholder="{{ translate('link_for_image_1') }}">
                                        <input type="url" name="banner_link_2" class="form-control mb-2" placeholder="{{ translate('link_for_image_2') }}">
                                        <input type="url" name="banner_link_3" class="form-control" placeholder="{{ translate('link_for_image_3') }}">
                                    </div>
                                </div>
                                <button class="btn btn-outline-primary" type="submit">{{ translate('upload') }}</button>
                            </form>
                            <div class="small text-muted mt-2">{{ translate('grid_layouts_replace_existing_banners_while_slider_appends') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    (function() {
        const typeSelect = document.getElementById('section-type-select');
        const feedRow = document.getElementById('product-feed-row');
        const feedTypeSelect = document.getElementById('feed-type-select');
        const feedCategoryRow = document.getElementById('feed-category-row');
        const layoutSelect = document.getElementById('banner-layout-select');
        const productsCard = document.getElementById('products-card');
        const categoriesCard = document.getElementById('categories-card');
        const brandsCard = document.getElementById('brands-card');
        const bannersCard = document.getElementById('banners-card');
        const bannerLayoutRow = document.getElementById('banner-layout-row');
        const uploadSlider = document.getElementById('upload-slider');
        const uploadGrid1 = document.getElementById('upload-grid-1');
        const uploadGrid2 = document.getElementById('upload-grid-2');
        const uploadGrid3 = document.getElementById('upload-grid-3');
    const uploadForm = document.getElementById('banner-upload-form');

        function setGroupDisabled(groupEl, disabled) {
            if (!groupEl) return;
            const inputs = groupEl.querySelectorAll('input[type="file"]');
            inputs.forEach(inp => { inp.disabled = disabled; });
        }

        function onTypeChange() {
            const isBanners = typeSelect.value === 'banners';
            const isCategories = typeSelect.value === 'categories';
            const isBrands = typeSelect.value === 'brands';
            const showProducts = !isBanners && !isCategories && !isBrands && (!feedTypeSelect || feedTypeSelect.value === 'manual');
            
            feedRow.style.display = (isBanners || isCategories || isBrands) ? 'none' : '';
            productsCard.style.display = showProducts ? '' : 'none';
            categoriesCard.style.display = isCategories ? '' : 'none';
            brandsCard.style.display = isBrands ? '' : 'none';
            bannersCard.style.display = isBanners ? '' : 'none';
            bannerLayoutRow.style.display = isBanners ? '' : 'none';
            
            // Feed category only for products+category feed
            if (feedCategoryRow && feedTypeSelect) {
                feedCategoryRow.style.display = (!isBanners && !isCategories && !isBrands && feedTypeSelect.value === 'category') ? '' : 'none';
            }
        }

        function onFeedChange() {
            if (!feedTypeSelect) return;
            const isBanners = typeSelect.value === 'banners';
            const isCategories = typeSelect.value === 'categories';
            const isBrands = typeSelect.value === 'brands';
            const showProducts = !isBanners && !isCategories && !isBrands && feedTypeSelect.value === 'manual';
            productsCard.style.display = showProducts ? '' : 'none';
            if (feedCategoryRow) feedCategoryRow.style.display = (!isBanners && !isCategories && !isBrands && feedTypeSelect.value === 'category') ? '' : 'none';
        }

        function onLayoutChange() {
            const layout = layoutSelect.value;
            // Sync to upload form hidden input
            const layoutHidden = document.getElementById('use-banner-layout');
            if (layoutHidden) layoutHidden.value = layout;
            uploadSlider.style.display = layout === 'slider' ? '' : 'none';
            uploadGrid1.style.display = layout === 'grid_1' ? '' : 'none';
            uploadGrid2.style.display = layout === 'grid_2' ? '' : 'none';
            uploadGrid3.style.display = layout === 'grid_3' ? '' : 'none';

            // Enable only the visible group's inputs, disable others to avoid duplicate-name collisions
            setGroupDisabled(uploadSlider, layout !== 'slider');
            setGroupDisabled(uploadGrid1, layout !== 'grid_1');
            setGroupDisabled(uploadGrid2, layout !== 'grid_2');
            setGroupDisabled(uploadGrid3, layout !== 'grid_3');
        }

        typeSelect.addEventListener('change', onTypeChange);
    if (feedTypeSelect) feedTypeSelect.addEventListener('change', onFeedChange);
        layoutSelect.addEventListener('change', onLayoutChange);
        onTypeChange();
    onFeedChange && onFeedChange();
        onLayoutChange();

        // Client-side validation: ensure required file count for selected layout
        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                const layout = layoutSelect.value;
                let ok = true;
                if (layout === 'slider') {
                    const inp = uploadSlider ? uploadSlider.querySelector('input[name="banner_files[]"]') : null;
                    console.debug('slider files length', inp && inp.files ? inp.files.length : 0);
                    ok = !!(inp && inp.files && inp.files.length > 0);
                } else if (layout === 'grid_1') {
                    const f1 = uploadGrid1 ? uploadGrid1.querySelector('input[name="banner_file_1"]') : null;
                    console.debug('grid_1 f1 length', f1 && f1.files ? f1.files.length : 0);
                    ok = !!(f1 && f1.files && f1.files.length > 0);
                } else if (layout === 'grid_2') {
                    const f1 = uploadGrid2 ? uploadGrid2.querySelector('input[name="banner_file_1"]') : null;
                    const f2 = uploadGrid2 ? uploadGrid2.querySelector('input[name="banner_file_2"]') : null;
                    console.debug('grid_2 f1,f2 length', f1 && f1.files ? f1.files.length : 0, f2 && f2.files ? f2.files.length : 0);
                    ok = !!(f1 && f1.files && f1.files.length > 0 && f2 && f2.files && f2.files.length > 0);
                } else if (layout === 'grid_3') {
                    const f1 = uploadGrid3 ? uploadGrid3.querySelector('input[name="banner_file_1"]') : null;
                    const f2 = uploadGrid3 ? uploadGrid3.querySelector('input[name="banner_file_2"]') : null;
                    const f3 = uploadGrid3 ? uploadGrid3.querySelector('input[name="banner_file_3"]') : null;
                    console.debug('grid_3 f1,f2,f3 length', f1 && f1.files ? f1.files.length : 0, f2 && f2.files ? f2.files.length : 0, f3 && f3.files ? f3.files.length : 0);
                    ok = !!(f1 && f1.files && f1.files.length > 0 && f2 && f2.files && f2.files.length > 0 && f3 && f3.files && f3.files.length > 0);
                }
                if (!ok) {
                    e.preventDefault();
                    // Simple user feedback; server also shows a toast if it somehow reaches
                    alert('{{ translate('please_select_image_files_to_upload') }}');
                }
            });
        }
    })();
</script>
@endpush
