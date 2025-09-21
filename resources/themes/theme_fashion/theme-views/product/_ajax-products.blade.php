<div class="product-wrapper">
    <input class="search_input_store" type="text" name="search_category_value" value="{{request('search_category_value')}}" hidden>
    <input class="search_input_store search_input_name" type="text" name="search" value="{{request('search')}}" hidden>
    <input class="search_input_store search_input_data_from" type="text" name="data_from" value="{{ isset($request_data) ? $request_data['data_from'] : request('data_from')}}" hidden>

    @if (count($products) > 0)
        @foreach($products as $product)
            @include('theme-views.partials._products-list-single-card', ['product'=>$product])
        @endforeach
    @else
        <div class="text-center pt-5 w-100">
            <div class="text-center mb-5">
                <img loading="lazy" src="{{ theme_asset('assets/img/icons/product.svg') }}" alt="{{ translate('product') }}">
                <h5 class="my-3 text-muted">{{translate('products_Not_Found')}}!</h5>
                <p class="text-center text-muted">{{ translate('sorry_no_data_found_related_to_your_search') }}</p>
            </div>
        </div>
    @endif

</div>

<!-- Expose pagination state for global initializer -->
<div class="product-scroll-state d-none"
    data-current-page="{{ $page }}"
    data-total-pages="{{ ceil($products->total() / $singlePageProductCount) }}"
    data-ajax-url="{{ route('ajax-filter-products', request('offer_type') ? ['offer_type' => request('offer_type')] : []) }}"
    data-load-more-url="{{ route('ajax-load-more-products', request('offer_type') ? ['offer_type' => request('offer_type')] : []) }}">
</div>

@if(count($products) > 0 && ceil(($products->total() / $singlePageProductCount)) != 1)
    <!-- Infinite Scroll Loading Indicator -->
    <div class="text-center mt-4 mb-4 infinite-scroll-loader" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">{{ translate('loading') ?? 'جاري التحميل...' }}</span>
        </div>
        <p class="mt-2">{{ translate('loading_more_products') ?? 'جاري تحميل المزيد من المنتجات...' }}</p>
    </div>

    <!-- Page Progress Indicator -->
    <div class="text-center mt-3 mb-3 page-progress-indicator">
        <small class="text-muted">
            {{ translate('page') ?? 'صفحة' }} <span id="current-page-indicator">{{ $page }}</span> 
            {{ translate('of') ?? 'من' }} <span id="total-pages-indicator">{{ ceil($products->total() / $singlePageProductCount) }}</span>
            | {{ translate('showing') ?? 'عرض' }} <span id="products-loaded-count">{{ $products->count() }}</span> 
            {{ translate('of') ?? 'من' }} {{ $products->total() }} {{ translate('products') ?? 'منتج' }}
        </small>
    </div>

    <!-- End of Products Indicator -->
    <div class="text-center mt-4 mb-4 infinite-scroll-end" style="display: none;">
        <p class="text-muted">{{ translate('no_more_products') ?? 'لا توجد منتجات أخرى' }}</p>
        <small class="text-muted">{{ translate('total_products_loaded') ?? 'إجمالي المنتجات المحملة' }}: <span id="final-products-count">{{ $products->count() }}</span></small>
    </div>

    <!-- Legacy Pagination (Hidden by default, can be toggled) -->
    <div id="legacy-pagination" style="display: none;">
        <ul class="search-pagination justify-content-end">
            <li>
                <label for="paginate_{{($page-1)}}" class="paginate_{{($page-1)}}">
                    <i class="bi bi-chevron-left"></i>
                </label>
            </li>

            @php
                $totalPages = ceil($products->total() / $singlePageProductCount);
                $windowSize = 1;
            @endphp

            @for ($i = 1; $i <= min(2, $totalPages); $i++)
                <li>
                    <label class="paginate_{{ $i }} {{ $page == $i ? 'active' : '' }}" for="paginate_{{ $i }}">{{ $i }}</label>
                    <input class="paginate_btn paginate_btn_id{{ $i }} d-none" type="radio" name="page" id="paginate_{{ $i }}" value="{{ $i }}" {{ $page == $i ? 'checked' : '' }}>
                </li>
            @endfor

            @if ($totalPages > 3 && $page > 2)
                <li class="d-flex align-items-center disabled user-select-none">
                    <span class="px-2">...</span>
                </li>
            @endif

            @for ($i = max(3, $page - $windowSize); $i <= min($totalPages - 2, $page + $windowSize); $i++)
                <li>
                    <label class="paginate_{{ $i }} {{ $page == $i ? 'active' : '' }}" for="paginate_{{ $i }}">{{ $i }}</label>
                    <input class="paginate_btn paginate_btn_id{{ $i }} d-none" type="radio" name="page" id="paginate_{{ $i }}" value="{{ $i }}" {{ $page == $i ? 'checked' : '' }}>
                </li>
            @endfor

            @if ($page < $totalPages - 2)
                <li class="d-flex align-items-center disabled user-select-none">
                    <span class="px-2">...</span>
                </li>
            @endif

            @for ($i = max($totalPages - 1, 1); $i <= $totalPages; $i++)
                @if ($i > 2)
                <li>
                    <label class="paginate_{{ $i }} {{ $page == $i ? 'active' : '' }}" for="paginate_{{ $i }}">{{ $i }}</label>
                    <input class="paginate_btn paginate_btn_id{{ $i }} d-none" type="radio" name="page" id="paginate_{{ $i }}" value="{{ $i }}" {{ $page == $i ? 'checked' : '' }}>
                </li>
                @endif
            @endfor

            <li>
                <label for="paginate_{{($page+1)}}" class="paginate_{{($page+1)}}">
                    <i class="bi bi-chevron-right"></i>
                </label>
            </li>
        </ul>
    </div>

    <!-- Pagination Toggle Button -->
    <!--<div class="text-center mt-3">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="toggle-pagination-mode">
            <i class="bi bi-arrow-repeat"></i> {{ translate('switch_to_pagination') ?? 'تبديل إلى الصفحات' }}
        </button>
    </div>-->

    <style>
    /* Infinite Scroll Styles - Optimized */
    .infinite-scroll-loader {
        animation: fadeIn 0.2s ease-in;
    }

    .infinite-scroll-end {
        border-top: 1px solid #e0e0e0;
        padding-top: 1rem;
        margin-top: 2rem;
    }

    .infinite-scroll-loader .spinner-border {
        width: 1.5rem;
        height: 1.5rem;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* Page Progress Indicator Styles */
    .page-progress-indicator {
        background: rgba(0,0,0,0.05);
        border-radius: 15px;
        padding: 6px 12px;
        margin: 10px auto;
        display: inline-block;
        font-size: 0.85rem;
    }

    /* Pagination toggle button */
    #toggle-pagination-mode {
        transition: all 0.2s ease;
        font-size: 0.85rem;
    }

    /* Legacy pagination when hidden */
    #legacy-pagination {
        transition: opacity 0.2s ease;
    }
    </style>

    <script>
        "use strict";
        // Robust inline infinite scroll: define safe fallback if globals not ready yet
        (function(){
            // Prevent duplicate execution
            if (window.productsInfiniteScrollInitialized) {
                return;
            }
            window.productsInfiniteScrollInitialized = true;
            
            var DEBUG = window.location.search.indexOf('debug=1') !== -1 || window.PRODUCTS_DEBUG === true;
            function log(){ if (DEBUG) { try { console.log.apply(console, arguments); } catch(e){} } }

            // If globals missing, define lightweight versions here
            if (typeof window.initProductsInfiniteScroll !== 'function' || typeof window.productsScrollForceCheck !== 'function') {
                (function(){
                    var isLoading = false;
                    function $state(){ return $('.product-scroll-state').first(); }
                    function readInt(v, d){ v=parseInt((v||'').toString(),10); return isNaN(v)?d:v; }
                    function state(){ var $s=$state(); return {
                        page: readInt($s.attr('data-current-page'), 1),
                        total: readInt($s.attr('data-total-pages'), 1),
                        ajax: ($s.attr('data-ajax-url')||'').trim(),
                        loadMore: ($s.attr('data-load-more-url')||'').trim()
                    }; }
                    function setPage(p){ var $s=$state(); if($s.length){ $s.attr('data-current-page', p).data('current-page', p); $('#current-page-indicator').text(p); } }
                    function pickUrl(st){ return st.loadMore || st.ajax || window.location.href; }
                    function buildPayload(next){
                        // Prefer full form if exists
                        var payload = {};
                        var $form = $('#fashion_products_list_form');
                        if ($form.length) {
                            try { $($form.serializeArray()).each(function(_, it){ payload[it.name] = it.value; }); } catch(e){}
                        } else {
                            // Fallback: read known hidden inputs
                            $(".search_input_store").each(function(){ var n=$(this).attr('name'); if(n){ payload[n]= $(this).val(); }});
                        }
                        payload.page = next;
                        return payload;
                    }
                    function appendHtml(resp){
                        var html = (resp && (resp.products_html || resp.html_products)) || '';
                        if (!html) return 0;
                        var $root = $('.product-wrapper');
                        if (!$root.length) { $root = $('#ajax_products_section'); }
                        if (!$root.length) { $root = $('#ajax-products-view'); }
                        try {
                            var $tmp = $('<div/>').html(html);
                            var $items = $tmp.find('.product-wrapper > *').length ? $tmp.find('.product-wrapper > *') : $tmp.children();
                            // Remove any script tags to prevent duplicate execution
                            $items.find('script').remove();
                            if ($items.length){ $root.append($items); return $items.length; }
                        } catch(e){}
                        // Also remove script tags from the main html before appending
                        var cleanHtml = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
                        $root.append(cleanHtml); return 1;
                    }
                    function toggleLoader(show){ $('.infinite-scroll-loader').toggle(!!show); }
                    function showEnd(){ $('.infinite-scroll-end').show(); }

                    function loadNext(){
                        if (isLoading) return; var st = state(); if (st.page >= st.total) { showEnd(); return; }
                        isLoading = true; toggleLoader(true);
                        var next = st.page + 1; var url = pickUrl(st); var payload = buildPayload(next);
                        var csrf = $('meta[name="_token"]').attr('content') || $('meta[name="csrf-token"]').attr('content');
                        log('[Products][Fashion] Loading next page', { url:url, page:next, totalPages: st.total });
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: payload,
                            headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {},
                            dataType: 'json'
                        }).done(function(resp){
                            var count = appendHtml(resp);
                            log('[Products][Fashion] Append count:', count);
                            if (count > 0) {
                                setPage(next);
                                var $loaded = $('#products-loaded-count'); if ($loaded.length) { try { $loaded.text(parseInt($loaded.text(),10)+count); } catch(e){} }
                            } else {
                                setPage(st.total); showEnd();
                            }
                        }).always(function(){ isLoading=false; toggleLoader(false); });
                    }

                    function onScroll(){ var st = state(); if (isLoading || st.page>=st.total) return; if($(window).scrollTop()+$(window).height()+300 >= $(document).height()) loadNext(); }

                    window.initProductsInfiniteScroll = function(){
                        $(window).off('scroll.productFashionInline').on('scroll.productFashionInline', onScroll);
                        setTimeout(function(){ if ($(document).height() <= $(window).height()+10) loadNext(); else onScroll(); }, 0);
                    };
                    window.productsScrollForceCheck = function(force){ if (force===true) loadNext(); else onScroll(); };
                })();
            }

            // Initialize now (works for both global and inline fallback)
            try { if (typeof window.initProductsInfiniteScroll === 'function') window.initProductsInfiniteScroll(); } catch(e){}
            try { if (typeof window.productsScrollForceCheck === 'function') window.productsScrollForceCheck(true); } catch(e){}
            log('[Products] inline init executed');
        })();
    </script>
@endif

