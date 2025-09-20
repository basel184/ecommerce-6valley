@extends('layouts.admin.app')

@section('title', translate('add_homepage_section'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize">{{ translate('add_homepage_section') }}</h2>
        </div>
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.pages-and-media.homepage.sections.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ translate('title') }}</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ translate('slug') }}</label>
                            <input type="text" name="slug" class="form-control" placeholder="optional">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ translate('section_type') }}</label>
                            <select name="type" class="form-select" id="section-type-select">
                                <option value="products">{{ translate('products') }}</option>
                                <option value="banners">{{ translate('banners') }}</option>
                                <option value="categories">{{ translate('categories') }}</option>
                                <option value="brands">{{ translate('brands') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="product-feed-row">
                            <label class="form-label">{{ translate('products_source') }}</label>
                            <select name="feed_type" class="form-select" id="feed-type-select">
                                <option value="manual">{{ translate('manual_selection') }}</option>
                                <option value="category">{{ translate('by_category') }}</option>
                                <option value="best_selling">{{ translate('best_selling') }}</option>
                                <option value="latest">{{ translate('latest_products') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="feed-category-row">
                            <label class="form-label">{{ translate('category') }}</label>
                            <select name="feed_category_id" class="form-select">
                                <option value="">-- {{ translate('select') }} --</option>
                                @foreach(($categories ?? []) as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ translate('show_limit') }}</label>
                            <input type="number" name="show_limit" class="form-control" value="8" min="1" max="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ translate('sort_order') }}</label>
                            <input type="number" name="sort_order" class="form-control" value="0" min="0" max="1000">
                        </div>
                        <div class="col-md-4" id="banner-layout-row">
                            <label class="form-label">{{ translate('banner_layout') }}</label>
                            <select name="banner_layout" class="form-select">
                                <option value="slider">{{ translate('slider') }}</option>
                                <option value="grid_1">{{ translate('1_column') }}</option>
                                <option value="grid_2">{{ translate('2_columns') }}</option>
                                <option value="grid_3">{{ translate('3_columns') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="status" id="status" checked>
                                <label class="form-check-label" for="status">{{ translate('active') }}</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Categories Selection -->
                    <div class="row g-3 mt-3" id="categories-selection-row" style="display: none;">
                        <div class="col-12">
                            <label class="form-label">{{ translate('select_categories') }}</label>
                            <select name="category_ids[]" class="form-select js-example-responsive" multiple size="8">
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ translate('hold_ctrl_or_cmd_to_select_multiple') }}</div>
                        </div>
                    </div>
                    
                    <!-- Brands Selection -->
                    <div class="row g-3 mt-3" id="brands-selection-row" style="display: none;">
                        <div class="col-12">
                            <label class="form-label">{{ translate('select_brands') }}</label>
                            <select name="brand_ids[]" class="form-select js-example-responsive" multiple size="8">
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ translate('hold_ctrl_or_cmd_to_select_multiple') }}</div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button class="btn btn-primary" type="submit">{{ translate('save') }}</button>
                        <a href="{{ route('admin.pages-and-media.homepage.sections.index') }}" class="btn btn-secondary">{{ translate('cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    (function(){
        const typeSelect = document.getElementById('section-type-select');
        const feedRow = document.getElementById('product-feed-row');
        const feedTypeSelect = document.getElementById('feed-type-select');
        const feedCategoryRow = document.getElementById('feed-category-row');
        const bannerLayoutRow = document.getElementById('banner-layout-row');
        const categoriesSelectionRow = document.getElementById('categories-selection-row');
        const brandsSelectionRow = document.getElementById('brands-selection-row');

        function onTypeChange(){
            const isBanners = typeSelect.value === 'banners';
            const isCategories = typeSelect.value === 'categories';
            const isBrands = typeSelect.value === 'brands';
            
            feedRow.style.display = (isBanners || isCategories || isBrands) ? 'none' : '';
            feedCategoryRow.style.display = (!isBanners && !isCategories && !isBrands && feedTypeSelect.value === 'category') ? '' : 'none';
            bannerLayoutRow.style.display = isBanners ? '' : 'none';
            categoriesSelectionRow.style.display = isCategories ? '' : 'none';
            brandsSelectionRow.style.display = isBrands ? '' : 'none';
        }
        function onFeedChange(){
            const isBanners = typeSelect.value === 'banners';
            const isCategories = typeSelect.value === 'categories';
            const isBrands = typeSelect.value === 'brands';
            const showCat = feedTypeSelect.value === 'category' && !isBanners && !isCategories && !isBrands;
            feedCategoryRow.style.display = showCat ? '' : 'none';
        }
        typeSelect.addEventListener('change', onTypeChange);
        feedTypeSelect.addEventListener('change', onFeedChange);
        onTypeChange();
        onFeedChange();
    })();
</script>
<style>
    .select2-container--default {
        width: 137px;
    }
</style>
@endpush
