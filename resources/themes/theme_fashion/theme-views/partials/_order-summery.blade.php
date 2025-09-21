@php($shippingMethod=getWebConfig(name: 'shipping_method'))
@php($product_price_total=0)
@php($total_tax=0)
@php($total_shipping_cost=0)
@php($order_wise_shipping_discount=\App\Utils\CartManager::order_wise_shipping_discount())
@php($total_discount_on_product=0)
@php($cart=\App\Utils\CartManager::getCartListQuery(type: 'checked'))
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

    @php($total_shipping_cost=$shipping_cost - $get_shipping_cost_saved_for_free_delivery)
    
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
@else
    <span>{{ translate('empty_cart') }}</span>
@endif
<div class="total-cost-wrapper">
    <div class="total-cost-area text-capitalize">
        <h5 class="mb-4">{{translate('order_summary')}} <small
                    class="text-base font-regular text-small">({{count(\App\Utils\CartManager::getCartListQuery(type: 'checked'))}} {{translate('items')}}
                )</small></h5>

        <div class="overflow-y-auto h--28rem">
            @if(auth('customer')->check())
                @php($cart_list = \App\Models\Cart::whereHas('product', function ($query) {
                    return $query->active();
                })->where(['customer_id' => auth('customer')->id(), 'is_guest' => 0,'is_checked'=>1])->get()->groupBy('cart_group_id'))
            @elseif(getWebConfig(name: 'guest_checkout') && session()->has('guest_id') && session('guest_id'))
                @php($cart_list = \App\Models\Cart::whereHas('product', function ($query) {
                    return $query->active();
                })->where(['customer_id' => session('guest_id'), 'is_guest' => 1,'is_checked'=>1])->get()->groupBy('cart_group_id'))
            @endif
            @foreach($cart_list as $group_key=>$group)
                @foreach($group as $cart_key=>$cartItem)
                    @if ($cart_key == 0)
                        @if($cartItem->seller_is=='admin')
                            <h6 class="font-bold letter-spacing-0">{{ getWebConfig(name: 'company_name') }}</h6>
                        @else
                            <h6 class="font-bold letter-spacing-0">{{ \App\Utils\get_shop_name($cartItem['seller_id']) }}</h6>
                        @endif
                    @endif
                @endforeach
                <ul class="total-cost-info mt-20px mb-30px mx-sm-4">
                    @php($isProductNullStatus = 0)

                    @foreach($group as $key=>$cartItem)
                        @php($product = $cartItem->product)
                        @if (!$product)
                            @php($isProductNullStatus = 1)
                        @endif
                        <li>
                            <span>{{ isset($product) ? Str::limit($product->name, 35) : translate('product_not_available') }}</span>
                            <span class="price-red price-display">{!! webCurrencyConverterWithImage($cartItem->price ?? 0) !!}</span>
                        </li>
                    @endforeach
                </ul>
            @endforeach
            <ul class="total-cost-info mt-20px mb-30px  mx-sm-4">
                <li>
                    <span>{{ translate('product_discount') }}</span>
                    <span class="price-red price-display">{!! webCurrencyConverterWithImage($total_discount_on_product ?? 0) !!}</span>
                </li>
                <li>
                    <span>{{ translate('sub_total') }}</span>
                    <span class="price-red  price-display">{!! webCurrencyConverterWithImage(($product_price_total - $total_discount_on_product) ?? 0) !!}</span>
                </li>
                <li>
                    <span>{{ translate('shipping') }}</span>
                    <span class="price-red price-display">{!! webCurrencyConverterWithImage($total_shipping_cost ?? 0) !!}</span>
                </li>
                @if(session()->has('coupon_discount'))
                    @php($coupon_discount = session()->has('coupon_discount')?session('coupon_discount'):0)
                    <li>
                        <span>{{ translate('coupon_discount') }} </span>
                        <span class="price-red price-display">{!! webCurrencyConverterWithImage(($coupon_discount+$order_wise_shipping_discount) ?? 0) !!}</span>
                    </li>
                    @php($coupon_dis=session('coupon_discount'))
                @endif
                <li>
                    <span>{{ translate('tax') }}</span>
                    <span class="price-red price-display">{!! webCurrencyConverterWithImage($total_tax ?? 0) !!}</span>
                </li>
            </ul>
            
            {{-- Free Delivery Status --}}
            @if(isset($cart_list) && $cart_list->count() > 0)
                @php($first_group = $cart_list->first())
                @if($first_group->count() > 0)
                    @php($free_delivery_status = \App\Utils\OrderManager::getFreeDeliveryOrderAmountArray($first_group->first()->cart_group_id))
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
        </div>
        <div class="ps-sm-4">
            <hr class="d-none d-sm-block"/>
        </div>
        <div class="d-block d-md-none">
            <h6 class="d-flex justify-content-center gap-2 mb-2 justify-content-sm-between letter-spacing-0 font-semibold text-normal">
                <span>{{translate('total')}}</span>
                <span class="price-red price-display total-price">{!! webCurrencyConverterWithImage(($product_price_total+$total_tax+$total_shipping_cost-$coupon_dis-$total_discount_on_product-$order_wise_shipping_discount) ?? 0) !!}</span>
            </h6>
        </div>

        <div class="proceed-cart-btn">
            <h6 class="d-flex justify-content-center gap-2 mb-2 justify-content-sm-between letter-spacing-0 font-semibold text-normal">
                <span>{{translate('total')}}</span>
                <span class="price-red price-display total-price">{!! webCurrencyConverterWithImage(($product_price_total+$total_tax+$total_shipping_cost-$coupon_dis-$total_discount_on_product-$order_wise_shipping_discount) ?? 0) !!}</span>
            </h6>
            <div class="ps-sm-4">
                <hr class="d-none d-sm-block"/>
            </div>

            @if (str_contains(request()->url(), 'checkout-payment'))
                <button class="btn btn-base w-100 justify-content-center form-control mt-1 mb-1 h-42px text-capitalize custom-disabled"
                    id="proceed-to-payment-action" data-gotocheckout="{{route('customer.choose-shipping-address-other')}}"
                    data-route="{{ route('checkout-payment') }}"
                    data-type="{{ 'checkout-payment' }}"
                    {{ (isset($isProductNullStatus) && $isProductNullStatus == 1) ? 'disabled':''}}
                    type="button">
                        {{ translate('proceed_to_payment') }}
                </button>
            @else
                <button class="btn btn-base w-100 justify-content-center form-control mt-1 mb-1 h-42px text-capitalize"
                    id="proceed_to_next_action" data-gotocheckout="{{route('customer.choose-shipping-address-other')}}"
                    data-checkoutpayment="{{ route('checkout-payment') }}"
                    {{ (isset($isProductNullStatus) && $isProductNullStatus == 1) ? 'disabled':''}}
                    type="button">
                        {{ translate('proceed_to_next') }}
                </button>
            @endif
        </div>
    </div>
</div>
