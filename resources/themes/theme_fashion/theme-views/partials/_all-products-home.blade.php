<section class="all-products-section scroll_to_form_top mt-4">
    <div class="container">
        <div class="section-title">
            <div class="d-flex flex-wrap justify-content-between row-gap-2 column-gap-4 align-items-center">
                <h2 class="title mb-0 me-auto text-capitalize">{{ translate('all_products') }}</h2>
                <div class="ms-auto ms-md-0 d-flex align-items-center column-gap-3">
                    <button type="button" class="btn btn-base filter-toggle d-lg-none">
                        <i class="bi bi-funnel"></i>
                    </button>
                    <a href="{{route('products')}}" id="data_form_id" class="see-all text-capitalize">
                        {{ translate('see_all') }}
                    </a>
                </div>
            </div>
        </div>
        <form action="{{ route('ajax-filter-products') }}" method="POST" id="fashion_products_list_form">
            @csrf
            <main class="main-wrapper">

                <aside class="sidebar">
                    @include('theme-views.product.partials._products-list-aside', ['categories' => $categories, 'colors' => $allProductsColorList])
                </aside>

                <article class="article">
                    <input type="hidden" name="per_page_product" value="{{ $singlePageProductCount }}">
                    <div id="ajax_products_section">
                        @include('theme-views.product._ajax-products', [
                            'products' => $allProductsList,
                            'page' => 1,
                            'singlePageProductCount' => $singlePageProductCount
                        ])
                    </div>

                </article>
            </main>
        </form>
    </div>
</section>
