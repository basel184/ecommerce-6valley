@if(count($products) > 0)
    @php($decimal_point_settings = getWebConfig(name: 'decimal_point_settings'))
    @foreach($products as $product)
        @if(!empty($product['product_id']))
            @php($product=$product->product)
        @endif

        <div class="col-lg-3 col-md-3 col-sm-4 col-6 p-2">
            @if(!empty($product))
                @include('web-views.partials._filter-single-product',['product'=>$product, 'decimal_point_settings'=>$decimal_point_settings])
            @endif
        </div>
    @endforeach

    <div class="col-12">
        <nav class="d-flex justify-content-between pt-2" aria-label="Page navigation"
             id="paginator-ajax">
            {!! $products->links() !!}
        </nav>
    </div>
    {{-- Expose scroll state and ajax endpoints for infinite loading --}}
    <div class="product-scroll-state d-none"
         data-current-page="{{ (int) $products->currentPage() }}"
         data-total-pages="{{ (int) $products->lastPage() }}"
         data-ajax-url="{{ route('ajax-filter-products', request('offer_type') ? ['offer_type' => request('offer_type')] : []) }}"
         data-load-more-url="{{ route('ajax-load-more-products', request('offer_type') ? ['offer_type' => request('offer_type')] : []) }}"
    ></div>

    {{-- Inline infinite-scroll (safe: defines only if missing, then initializes) --}}
    <script>
        (function(){
            'use strict';
            if (!window.initProductsInfiniteScrollDefault || !window.productsScrollForceCheckDefault) {
                (function(){
                    var isLoading = false;
                    function $stateEl(){ return $('.product-scroll-state').first(); }
                    function readInt(v, d){ v=parseInt((v||'').toString(),10); return isNaN(v)?d:v; }
                    function getState(){
                        var $s = $stateEl();
                        return {
                            page: readInt($s.attr('data-current-page'), 1),
                            total: readInt($s.attr('data-total-pages'), 1),
                            ajax: ($s.attr('data-ajax-url')||'').trim(),
                            loadMore: ($s.attr('data-load-more-url')||'').trim()
                        };
                    }
                    function setPage(p){ var $s=$stateEl(); if($s.length){ $s.attr('data-current-page', p).data('current-page', p); $('#current-page-indicator').text(p); } }
                    // Default theme: prefer ajax-filter-products for consistent HTML, fallback to load-more
                    function pickUrl(st){ return st.ajax || st.loadMore || window.location.href; }
                    function buildPayload(nextPage){
                        var payload = {};
                        // Prefer global if available (external script), else build from backup node
                        if (window.productListPageData) {
                            try { payload = $.extend({}, window.productListPageData); } catch(e){}
                        } else {
                            var $b = $('#products-search-data-backup');
                            if ($b.length) {
                                payload = {
                                    id: $b.data('id'),
                                    name: $b.data('name'),
                                    product_name: $b.data('name'),
                                    brand_id: $b.data('brand'),
                                    category_id: $b.data('category'),
                                    data_from: $b.data('from'),
                                    offer_type: $b.data('offer'),
                                    product_check: $b.data('product-check'),
                                    min_price: $b.data('min-price'),
                                    max_price: $b.data('max-price'),
                                    sort_by: $b.data('sort'),
                                    product_type: $b.data('product-type'),
                                    vendor_id: $b.data('vendor-id'),
                                    author_id: $b.data('author-id'),
                                    publishing_house_id: $b.data('publishing-house-id'),
                                    flash_deals_id: $b.data('flash-deals-id')
                                };
                            }
                        }
                        payload.page = nextPage;
                        return payload;
                    }
                    function appendHtml(resp){
                        var html = (resp && (resp.products_html || resp.html_products)) || '';
                        if (!html) return 0;
                        var $root = $('#ajax-products-view');
                        if (!$root.length) { $root = $('#ajax_products_section'); }
                        if (!$root.length) { $root = $('.product-wrapper'); }
                        try {
                            var $tmp = $('<div/>').html(html);
                            var $cols = $tmp.find('.col-lg-3.col-md-3.col-sm-4.col-6.p-2');
                            if ($cols.length){ $root.append($cols); return $cols.length; }
                        } catch(e){}
                        $root.append(html);
                        try { return $tmp ? ($tmp.children().length||0) : 0; } catch(e){ return 0; }
                    }
                    function loadNext(){
                        if (isLoading) return; var st = getState(); if (st.page >= st.total) return; isLoading = true;
                        var next = st.page + 1; var payload = buildPayload(next);
                        var csrf = $('meta[name="_token"]').attr('content') || $('meta[name="csrf-token"]').attr('content');
                        var url = pickUrl(st);
                        if (window.location.search.indexOf('debug=1')!==-1 || window.PRODUCTS_DEBUG===true) {
                            try { console.log('[Products][Default] Loading next page', {url:url, page:next, totalPages: st.total}); } catch(e){}
                        }
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: payload,
                            headers: csrf ? {'X-CSRF-TOKEN': csrf} : {},
                            dataType: 'json'
                        }).done(function(resp){
                            var count = appendHtml(resp);
                            if (window.location.search.indexOf('debug=1')!==-1 || window.PRODUCTS_DEBUG===true) {
                                try { console.log('[Products][Default] Append count:', count); } catch(e){}
                            }
                            if (count > 0) setPage(next); else setPage(st.total);
                        }).always(function(){ isLoading = false; });
                    }
                    function onScroll(){ var st=getState(); if (isLoading || st.page>=st.total) return; if($(window).scrollTop()+$(window).height()+300 >= $(document).height()) loadNext(); }
                    window.initProductsInfiniteScrollDefault = function(){
                        $(window).off('scroll.productInfiniteDefault').on('scroll.productInfiniteDefault', onScroll);
                        setTimeout(function(){ if ($(document).height() <= $(window).height()+10) loadNext(); else onScroll(); }, 0);
                    };
                    window.productsScrollForceCheckDefault = function(force){ if (force===true) loadNext(); else onScroll(); };
                })();
            }
            try { if (window.initProductsInfiniteScrollDefault) window.initProductsInfiniteScrollDefault(); } catch(e){}
            try { if (window.productsScrollForceCheckDefault) window.productsScrollForceCheckDefault(true); } catch(e){}
        })();
    </script>
@else
    <div class="d-flex justify-content-center align-items-center w-100 py-5">
        <div>
            <img src="{{ theme_asset(path: 'public/assets/front-end/img/media/product.svg') }}" class="img-fluid" alt="">
            <h6 class="text-muted">{{ translate('no_product_found') }}</h6>
        </div>
    </div>
@endif
