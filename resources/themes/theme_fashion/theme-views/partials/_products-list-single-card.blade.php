<div class="product-card product-cart-option-container">
    <div class="product-card-inner">
        <div class="img">
            <a href="{{route('product',$product->slug)}}" class="d-block h-100">
                <img loading="lazy" class="w-100" alt="{{ translate('product') }}"
                     src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}">
            </a>
            @if (isset($product->created_at) && $product->created_at->diffInMonths(\Carbon\Carbon::now()) < 1)
                <span class="badge badge-title z-2">{{translate('new')}}</span>
            @endif
            @if(getProductPriceByType(product: $product, type: 'discount', result: 'value') > 0)
                @php($discountPercentage = round((getProductPriceByType(product: $product, type: 'discount', result: 'value') / $product->unit_price) * 100))
                <div class="discount-badge-on-image">
                    <span class="badge bg-danger text-white">
                        {{$discountPercentage}}%
                    </span>
                </div>
            @endif
            <div class="hover-content d-flex justify-content-center">
                <div class="d-flex flex-wrap justify-content-between align-items-center column-gap-3">
                    <a href="{{route('product',$product->slug)}}" class="d-inline-flex">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="javascript:" class="d-inline-flex wish-icon addWishlist_function_view_page"
                       data-id="{{$product->id}}">
                        <i class="wishlist_{{$product->id}} bi {{ isProductInWishList($product->id) ?'bi-heart-fill text-danger':'bi-heart' }}"></i>
                    </a>
                    <div class="rating">
                        <i class="bi bi-star-fill text-star"></i>
                        <span>{{round($product->reviews->avg('rating') ?? 0,1)}}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="cont">
            <h6 class="title">
                <a href="{{route('product',$product->slug)}}"
                   title="{{ $product['name'] }}">{{ $product['name'] }}</a>
            </h6>
            <div class="d-flex align-items-center justify-content-between column-gap-2">
                <h4 class="price flex-wrap">
                    <span>{!! getProductPriceByType(product: $product, type: 'discounted_unit_price', result: 'string_with_image') !!}</span>
                    @if(getProductPriceByType(product: $product, type: 'discount', result: 'value') > 0)
                        <del>{{  webCurrencyConverter($product->unit_price) }}</del>
                    @endif
                </h4>
                @if (json_decode($product->variation) != null)
                    @php($variations = json_decode($product->variation, true))
                    @php($first_variation = $variations ? reset($variations) : null)
                    <span class="btn add-to-cart-btn">
                        <a href="javascript:" 
                           class="store_vacation_check_function w-100 h-100 d-flex align-items-center justify-content-center"
                           data-id="{{ $product['id'] }}"
                           data-added_by="{{ $product['added_by'] }}"
                           data-user_id="{{ $product['user_id'] }}"
                           data-action_url="{{ route('ajax-shop-vacation-check') }}"
                           onclick="addToCartWithVariation(this, {{ $product['id'] }}, '{{ $first_variation['color'] ?? '' }}', {{ json_encode($first_variation) }})">
                            <i class="bi bi-plus"></i>
                        </a>
                    </span>
                @else
                    <span class="btn add-to-cart-btn">
                        <a href="javascript:" 
                           class="store_vacation_check_function w-100 h-100 d-flex align-items-center justify-content-center"
                           data-id="{{ $product['id'] }}"
                           data-added_by="{{ $product['added_by'] }}"
                           data-user_id="{{ $product['user_id'] }}"
                           data-action_url="{{ route('ajax-shop-vacation-check') }}"
                           onclick="addToCartSimple(this, {{ $product['id'] }})">
                            <i class="bi bi-plus"></i>
                        </a>
                    </span>
                @endif
            </div>
            @php($overallRating = getOverallRating($product->reviews))
        </div>
        
    </div>
</div>
