@if($dealOfTheDay)
    @if ((isset($dealOfTheDay->product) && $dealOfTheDay->product->discount > 0) || isset($recommendedProduct->discount_type))
        <section class="deal-of-the-day section-gap pb-0">
            <div class="container">
                <div class="deal-of-the-day-wrapper">
                    <img loading="lazy" src="{{theme_asset('assets/img/deal/dd-1.png') }}"
                        alt="{{ translate('deal_of_the_day') }}" class="d-shape-1">
                    <img loading="lazy" src="{{theme_asset('assets/img/deal/dd-2.png')}}"
                        alt="{{ translate('deal_of_the_day') }}" class="d-shape-2">
                    <img loading="lazy" src="{{theme_asset('assets/img/deal/dd-3.png')}}"
                        alt="{{ translate('deal_of_the_day') }}" class="d-shape-3">
                    <div class="deal-left">
                        <h6 class="subtitle text-capitalize">{{translate("do_not_miss_todays_deal")}}!</h6>
                        <h3 class="title">{{ translate('todays_best_deal') }}</h3>
                        @if (isset($dealOfTheDay->product) && $dealOfTheDay->product->discount > 0)
                            @if (getProductPriceByType(product: $dealOfTheDay?->product, type: 'discount', result: 'value') > 0)
                                <span class="deal-badge bg-base secondary-color">
                                    {{ getProductPriceByType(product: $dealOfTheDay?->product, type: 'discount', result: 'string') }}
                                    {{('off')}}
                                </span>
                            @endif
                        @elseif (isset($recommendedProduct->discount_type))
                            @if (getProductPriceByType(product: $recommendedProduct, type: 'discount', result: 'value') > 0)
                                <span class="deal-badge bg-base secondary-color">
                                    {{ getProductPriceByType(product: $recommendedProduct, type: 'discount', result: 'string') }}
                                    {{('off')}}
                                </span>
                            @endif
                        @endif
                    </div>

                    @if (isset($dealOfTheDay->product) && $dealOfTheDay->product->discount > 0)
                        <div class="deal-right">
                            <div class="deal-img">
                                <img loading="lazy" alt="{{ translate('product') }}"
                                    src="{{ getStorageImages(path: $dealOfTheDay?->product?->thumbnail_full_url, type: 'product') }}">
                            </div>
                            <div class="deal-content">
                                <div class="product-single-content">
                                    <div class="d-flex flex-wrap align-items-center column-gap-4 mb-3">
                                        <div class=" review position-relative">
                                            <div class="stars">
                                                @php($overall_rating = getOverallRating($dealOfTheDay->product->reviews))
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if ($i <= $overall_rating[0])
                                                        <i class="bi bi-star-fill"></i>
                                                    @elseif ($overall_rating[0] != 0 && $i <= $overall_rating[0] + 1.1)
                                                        <i class="bi bi-star-half"></i>
                                                    @else
                                                        <i class="bi bi-star"></i>
                                                    @endif
                                                @endfor
                                            </div>
                                        </div>
                                        <span
                                                class="badge badge-soft-{{$dealOfTheDay->product->current_stock>0 ? 'success':'danger'}}">
                                            {{translate($dealOfTheDay->product->current_stock>0 ? 'stock_available':'out_of_stock')}}

                                        </span>
                                    </div>
                                    <h3 class="title">{{ \Illuminate\Support\Str::limit($dealOfTheDay->product->name,60) }}</h3>
                                    <div class="categories">
                                        <span class="text-base"><i class="bi bi-shop"></i></span> <span class="text-base">
                                            @if ($dealOfTheDay->product->added_by == 'admin')
                                                {{$web_config['company_name']}}
                                            @else
                                                {{isset($dealOfTheDay->product->seller->shop) ? $dealOfTheDay->product->seller->shop->name : ''}}
                                            @endif

                                        </span>
                                    </div>
                                    <br>
                                    <div class="price">
                                        <h4>
                                            <span>{{ getProductPriceByType(product: $dealOfTheDay?->product, type: 'discounted_unit_price', result: 'string') }}</span>
                                            @if(getProductPriceByType(product: $dealOfTheDay?->product, type: 'discount', result: 'value') > 0)
                                                <del>{{ webCurrencyConverter($dealOfTheDay?->product?->unit_price) }}</del>
                                            @endif

                                            @if (getProductPriceByType(product: $dealOfTheDay?->product, type: 'discount', result: 'value') > 0)
                                                <span class="badge bg-base secondary-color">
                                                    {{ getProductPriceByType(product: $dealOfTheDay?->product, type: 'discount', result: 'string') }}
                                                    {{('off')}}
                                                </span>
                                            @endif
                                        </h4>

                                    </div>
                                    <div class="btn-grp">
                                        <a href="{{route('product',$dealOfTheDay->product->slug)}}"
                                        class="btn btn-base text-capitalize hover">
                                            {{translate('shop_now')}}<i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif (isset($recommendedProduct->discount_type))
                        <div class="deal-right">
                            <div class="deal-img">
                                <img loading="lazy" alt="{{ translate('product') }}"
                                    src="{{ getStorageImages(path: $recommendedProduct['thumbnail_full_url'], type: 'product') }}">
                            </div>
                            <div class="deal-content">
                                <div class="product-single-content">
                                    <div class="d-flex flex-wrap align-items-center column-gap-4 mb-3">
                                        <div class=" review position-relative">
                                            <div class="stars">
                                                @php($overall_rating = getOverallRating($recommendedProduct->reviews))
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if ($i <= $overall_rating[0])
                                                        <i class="bi bi-star-fill"></i>
                                                    @elseif ($overall_rating[0] != 0 && $i <= $overall_rating[0] + 1.1)
                                                        <i class="bi bi-star-half"></i>
                                                    @else
                                                        <i class="bi bi-star"></i>
                                                    @endif
                                                @endfor
                                            </div>
                                        </div>
                                        <span class="badge badge-soft-{{$recommendedProduct->current_stock>0 ? 'success':'danger'}}">
                                            {{translate($recommendedProduct->current_stock>0 ? 'stock_available':'out_of_stock')}}
                                        </span>
                                    </div>
                                    <h3 class="title">{{ \Illuminate\Support\Str::limit($recommendedProduct->name, 60) }}</h3>
                                    <div class="categories">
                                        <span class="text-base"><i class="bi bi-shop"></i></span> <span class="text-base">
                                            @if ($recommendedProduct->added_by == 'admin')
                                                {{$web_config['company_name']}}
                                            @else
                                                {{isset($recommendedProduct->seller->shop) ? $recommendedProduct->seller->shop->name : ''}}
                                            @endif
                                        </span>
                                    </div>
                                    <br>
                                    <div class="price">
                                        <h4>
                                            <span>{{ getProductPriceByType(product: $recommendedProduct, type: 'discounted_unit_price', result: 'string') }}</span>
                                            @if(getProductPriceByType(product: $recommendedProduct, type: 'discount', result: 'value') > 0)
                                                <del>{{ webCurrencyConverter($recommendedProduct?->unit_price) }}</del>
                                            @endif

                                            @if (getProductPriceByType(product: $recommendedProduct, type: 'discount', result: 'value') > 0)
                                                <span class="badge bg-base secondary-color">
                                                    {{ getProductPriceByType(product: $recommendedProduct, type: 'discount', result: 'string') }}
                                                    {{('off')}}
                                                </span>
                                            @endif
                                        </h4>
                                    </div>
                                    <div class="btn-grp">
                                        <a href="{{route('product', $recommendedProduct->slug)}}"
                                        class="btn btn-base text-capitalize hover">{{translate('shop_now')}}<i
                                                    class="bi bi-arrow-right"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </section>
    @endif
@endif
