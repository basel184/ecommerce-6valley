<div class="total-cost-wrapper">

    @php($shippingMethod = getWebConfig(name: 'shipping_method'))
    @php($product_price_total=0)
    @php($total_tax=0)
    @php($total_shipping_cost=0)
    @php($order_wise_shipping_discount=\App\Utils\CartManager::order_wise_shipping_discount())
    @php($total_discount_on_product=0)
    @php($cart=\App\Utils\CartManager::getCartListQuery(type: 'checked'))
    @php($cartAll=\App\Utils\CartManager::getCartListQuery())
    @php($cart_group_ids=\App\Utils\CartManager::get_cart_group_ids())
    @php($shipping_cost=\App\Utils\CartManager::get_shipping_cost(type: 'checked'))
    @php($get_shipping_cost_saved_for_free_delivery=\App\Utils\CartManager::getShippingCostSavedForFreeDelivery(type: 'checked'))
    @php($coupon_dis=0)
    @if($cart->count() > 0)
        @foreach($cart as $key => $cartItem)
            @php($product_price_total+=$cartItem['price']*$cartItem['quantity'])
            @php($total_tax+=$cartItem['tax_model']=='exclude' ? ($cartItem['tax']*$cartItem['quantity']):0)
            @php($total_discount_on_product+=$cartItem['discount']*$cartItem['quantity'])
        @endforeach

        @if(session()->missing('coupon_type') || session('coupon_type') !='free_delivery')
            @php($total_shipping_cost=$shipping_cost - $get_shipping_cost_saved_for_free_delivery)
        @else
            @php($total_shipping_cost=$shipping_cost)
        @endif
        
        {{-- إضافة تكلفة شحن افتراضية إذا كانت القيمة صفر --}}
        @if($total_shipping_cost <= 0 && $cart->count() > 0)
            @php($defaultShippingCost = getWebConfig(name: 'shipping_cost') ?? 25)
            @php($total_shipping_cost = $defaultShippingCost)
        @endif
        
        {{-- تحقق من الشحن المجاني للطلبات فوق المبلغ المحدد --}}
        @if($cart->count() > 0)
            @php($first_cart = $cart->first())
            @if($first_cart)
                @php($free_delivery_status = \App\Utils\OrderManager::getFreeDeliveryOrderAmountArray($first_cart->cart_group_id))
                @if ($free_delivery_status['status'] && $free_delivery_status['amount_need'] <= 0)
                    @php($total_shipping_cost = 0)
                @endif
            @endif
        @endif
    @endif


    @if($cartAll->count() > 0 && $cart->count() == 0)
        <p class="mb-2 text-center">{{ translate('Please_checked_items_before_proceeding_to_checkout') }}</p>
    @elseif($cartAll->count() == 0)
        <p class="mb-2 text-center">{{ translate('empty_cart') }}</p>
    @endif

    <h6 class="text-center title font-medium letter-spacing-0 mb-20px text-capitalize">{{ translate('totals_cost') }}</h6>

    <div class="total-cost-area">
        @if(auth('customer')->check() && !session()->has('coupon_discount'))
            @php($coupon_discount = 0)
            <form action="javascript:" method="post" novalidate id="coupon-code-ajax">
                <div class="apply-coupon-form">
                    <input type="text" class="form-control" name="code" id="promo-code"
                           placeholder="{{ translate('apply_coupon_code') }}" required autocomplete="off">
                    <button class="btn badge-soft-base" id="coupon_code_theme_fashion">{{ translate('apply') }}</button>
                </div>
                <span id="coupon-apply" data-url="{{ route('coupon.apply') }}"></span>
            </form>
            @php($coupon_dis=0)
        @endif


        <ul class="total-cost-info border-bottom-0 border-bottom-sm mt-20px mb-30px text-capitalize">
            <li>
                <span>{{ translate('product_discount') }}</span>
                <span class="price-red">{!! webCurrencyConverterWithImage($total_discount_on_product) !!}</span>
            </li>
            <li>
                <span>{{ translate('shipping') }}</span>
                <span class="price-red">{!! webCurrencyConverterWithImage($total_shipping_cost) !!}</span>
            </li>
            <li>
                <span>{{ translate('tax') }}</span>
                <span class="price-red">{!! webCurrencyConverterWithImage($total_tax) !!}</span>
            </li>
            @if(auth('customer')->check() && session()->has('coupon_discount'))
                @php($coupon_discount = session()->has('coupon_discount')?session('coupon_discount'):0)
                <li>
                    <span>{{ translate('coupon_discount') }} </span>
                    <span class="price-red">{!! webCurrencyConverterWithImage($coupon_discount+$order_wise_shipping_discount) !!}</span>
                </li>
                @php($coupon_dis=session('coupon_discount'))
            @endif
        </ul>
        
        {{-- Free Delivery Status --}}
        @if($cart->count() > 0)
            @php($first_cart = $cart->first())
            @if($first_cart)
                @php($free_delivery_status = \App\Utils\OrderManager::getFreeDeliveryOrderAmountArray($first_cart->cart_group_id))
                @if ($free_delivery_status['status'] && (session()->missing('coupon_type') || session('coupon_type') !='free_delivery'))
                    <div class="free-delivery-area px-3 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <img loading="lazy"
                                 src="{{ theme_asset('assets/img/free-shipping.svg') }}"
                                 alt="{{ translate('free_shipping') }}" width="40">
                            @if ($free_delivery_status['amount_need'] <= 0)
                                <span class="text-success fs-14">
                                    {{ translate('you_Get_Free_Delivery_Bonus') }}!
                                </span>
                            @else
                                <div class="d-flex flex-column">
                                    <span class="text-muted fs-12">
                                        {{ translate('add_more_for_free_delivery') }}
                                    </span>
                                    <span class="need-for-free-delivery font-bold text-primary">{!! webCurrencyConverterWithImage($free_delivery_status['amount_need']) !!}</span>
                                </div>
                            @endif
                        </div>
                        <div class="progress free-delivery-progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar"
                                 style="width: {{ $free_delivery_status['percentage'] }}%"
                                 aria-valuenow="{{ $free_delivery_status['percentage'] }}" aria-valuemin="0"
                                 aria-valuemax="100"></div>
                        </div>
                    </div>
                @endif
            @endif
        @endif
        
        <hr/>
        <div class="d-block d-md-none">
            <h6 class="d-flex justify-content-center gap-2 mb-2 justify-content-sm-between letter-spacing-0 font-semibold text-normal">
                <span>{{ translate('total') }}</span>
                <span class="price-red">{!! webCurrencyConverterWithImage($product_price_total+$total_tax+$total_shipping_cost-$coupon_dis-$total_discount_on_product-$order_wise_shipping_discount) !!}</span>
            </h6>
        </div>
        <div class="proceed-cart-btn">
            <h6 class="d-flex justify-content-center gap-2 mb-2 justify-content-sm-between letter-spacing-0 font-semibold text-normal">
                <span>{{ translate('total') }}</span>
                <span class="price-red">{!! webCurrencyConverterWithImage($product_price_total+$total_tax+$total_shipping_cost-$coupon_dis-$total_discount_on_product-$order_wise_shipping_discount) !!}</span>
            </h6>
            <button class="btn btn-base w-100 justify-content-center mt-1 form-control h-42px text-capitalize checkout_action {{$cart->count() <= 0 ? 'custom-disabled' : ''}}"
                    {{ (isset($isProductNullStatus) && $isProductNullStatus == 1) ? 'custom-disabled':''}}
                    type="button">{{ translate('proceed_to_checkout') }}</button>
        </div>
    </div>
</div>