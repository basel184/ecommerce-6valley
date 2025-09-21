@if (getFeaturedDealsProductList()->count() > 0)
    <section class="signature-product-section pb-0">
        <div class="overflow-hidden">
            <div class="section-title-2">
                <span class="shapetitle text-capitalize">{{ translate('Feature_Deal') }}</span>
                <h2 class="title text-capitalize">{{ translate('Feature_Deal_for_this_season') }}</h2>
            </div>
        </div>
        <div class="signature-product-section-inner">
            <div class="container">
                <div class="signature-wrapper">
                    <div class="signature-products-slider-wrapper">
                        {{-- Custom slider controls --}}
                        <div class="signature-slider-controls d-none d-md-block">
                            <button class="signature-slider-prev" type="button" aria-label="Previous">
                                <i class="fa fa-chevron-left"></i>
                            </button>
                            <button class="signature-slider-next" type="button" aria-label="Next">
                                <i class="fa fa-chevron-right"></i>
                            </button>
                            <button class="signature-slider-play-pause" type="button" aria-label="Play/Pause">⏸️</button>
                        </div>
                        
                        <div class="owl-theme owl-carousel signature-products-slider">
                            @foreach (getFeaturedDealsProductList() as $key => $product)
                                <div class="signature-product @if($key % 2 == 1) even-item @endif">
                                    @include('theme-views.partials._signature-product-card', ['product'=>$product])
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="signature-title text-md-end">
                        <div class="pb-4">
                            <h2 class="title text-base text-capitalize mb-2">{{ translate('find_your_best_featured_deal_product') }}</h2>
                            <a href="{{route('products',['offer_type'=>'featured_deal','page'=>1])}}"
                               class="text-base text-underline">
                                {{translate('see_all_products')}}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
