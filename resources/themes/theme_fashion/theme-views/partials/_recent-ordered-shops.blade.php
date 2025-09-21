<section class="recent-shop-section section-gap pb-0">
    <div class="container">
        <div class="section-title mb-0">
            <div class="mb-32px">
                <div class="d-flex justify-content-between text-capitalize row-gap-2 column-gap-4 align-items-center">
                    <h2 class="title mb-0 me-auto">{{ translate('shop_again_from_your_recent_store') }}</h2>
                    <div class="d-flex align-items-center column-gap-4 justify-content-end ms-auto ms-md-0 text-nowrap">
                        <div class="owl-prev recent-shop-prev">
                            <i class="bi bi-chevron-left"></i>
                        </div>
                        <div class="owl-next recent-shop-next">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                        <a href="{{route('vendors')}}" class="see-all">{{ translate('see_all') }}</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="overflow-hidden">
            <div class="recent-shop-slider-wrapper">
                <div class="recent-shop-slider owl-theme owl-carousel">
                    @foreach ($recentOrderShopList as $product)
                        @if(isset($product) && $product)
                            <div class="recent-shop-card">
                                <div class="img">
                                    <a href="{{route('product',$product->slug)}}" title="{{$product->name}}">
                                        <img loading="lazy" alt="{{ translate('shop') }}"
                                             src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}">
                                    </a>
                                    <span class="badge badge-soft-base">
                                        {{webCurrencyConverter($product->unit_price-\App\Utils\Helpers::getProductDiscount($product,$product->unit_price))}}
                                    </span>
                                </div>
                                <div class="cont">
                                    @if($product?->added_by == 'admin')
                                        <a href="{{ route('shopView', ['id' => 0]) }}"
                                           class="recent-shop-card-author">
                                            <img loading="lazy" alt="{{ translate('shop') }}"
                                                 src="{{ getStorageImages(path: $web_config['fav_icon'], type:'shop') }}">
                                            <h5 class="subtitle" title="{{ $web_config['company_name']  }}">
                                                {{ $web_config['company_name'] }}
                                            </h5>
                                        </a>
                                        <div class="text-center">
                                            <a href="{{ route('shopView',['id' => 0]) }}"
                                               class="btn __btn-outline text-capitalize">{{ translate('visit_again') }}</a>
                                        </div>
                                    @else
                                        <a href="{{route('shopView', ['id' => $product->seller['id']])}}"
                                           class="recent-shop-card-author">
                                            <img loading="lazy" alt="{{ translate('shop') }}"
                                                 src="{{ getStorageImages(path: $product?->seller?->shop->image_full_url, type:'shop') }}">
                                            <h5 class="subtitle" title="{{ $product->seller->shop->name  }}">{{ $product->seller->shop->name  }}</h5>
                                        </a>
                                        <div class="text-center">
                                            <a href="{{route('shopView',['id' => $product->seller['id']])}}"
                                               class="btn __btn-outline text-capitalize">{{ translate('visit_again') }}</a>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</section>
