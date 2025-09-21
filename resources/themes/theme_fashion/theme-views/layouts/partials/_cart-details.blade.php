@php
    $shippingMethod = getWebConfig(name: 'shipping_method');
    $cart = \App\Models\Cart::whereHas('product', function ($query) {
                return $query->active();
            })->where(['customer_id' => (auth('customer')->check() ? auth('customer')->id() : session('guest_id'))])->with(['seller','allProducts.category'])->get()->groupBy('cart_group_id');
@endphp

@if( $cart->count() > 0)
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8 pe-lg-0">
                <div class="cart-title-area text-capitalize mb-2">
                    <h6 class="title">{{translate('all_cart_product_list')}}
                        <span class="btn-status">({{count(\App\Utils\CartManager::getCartListQuery())}})</span>
                    </h6>
                    <span type="button" class="text-text-2 route_alert_function"
                          data-routename="{{ route('cart.remove-all') }}"
                          data-message="{{ translate('want_to_clear_all_cart?') }}"
                          data-typename="">{{translate('remove_all')}}</span>
                </div>

                <div class="table-responsive d-none d-md-block overflow-hidden">
                    <table class="table __table vertical-middle cart-list-table-custom">
                        <thead class="word-nobreak">
                            <tr>
                                <th>
                                    <label class="form-check m-0">
                                        <span class="form-check-label">{{translate('product')}}</span>
                                    </label>
                                </th>
                                <th class="text-center">
                                    {{translate('discount')}}
                                </th>
                                <th class="text-center">
                                    {{translate('quantity')}}
                                </th>
                                <th class="text-center">
                                    {{translate('total')}}
                                </th>
                            </tr>
                        </thead>
                    </table>

                    @foreach($cart as $group_key => $group)
                        @php
                            $physical_product = false;
                            $total_shipping_cost = 0;
                            foreach ($group as $row) {
                                if ($row->product_type == 'physical' && $row->is_checked) {
                                    $physical_product = true;
                                }
                                if ($row->product_type == 'physical' && $row->shipping_type != "order_wise") {
                                    $total_shipping_cost += $row->shipping_cost;
                                }
                            }
                        @endphp

                        <?php
                            $physical_product = false;
                            foreach ($group as $row) {
                                if ($row->product_type == 'physical' && $row->is_checked) {
                                    $physical_product = true;
                                }
                            }
                            $productNullStatus = 0;
                            $total_amount = 0
                        ?>
                        <div class="cart_information">
                        @foreach($group as $cart_key => $cartItem)

                            <?php
                                if ($shippingMethod=='inhouse_shipping') {
                                    $admin_shipping = \App\Models\ShippingType::where('seller_id', 0)->first();
                                    $shipping_type = isset($admin_shipping) == true ? $admin_shipping->shipping_type : 'order_wise';
                                } else {
                                    if ($cartItem->seller_is == 'admin') {
                                        $admin_shipping = \App\Models\ShippingType::where('seller_id', 0)->first();
                                        $shipping_type = isset($admin_shipping) == true ? $admin_shipping->shipping_type : 'order_wise';
                                    } else {
                                        $seller_shipping = \App\Models\ShippingType::where('seller_id', $cartItem->seller_id)->first();
                                        $shipping_type = isset($seller_shipping) == true ? $seller_shipping->shipping_type : 'order_wise';
                                    }
                                }
                            ?>

                            @if($cart_key==0)
                                <div class="--bg-6 border-0 rounded py-2 px-2 px-sm-3 ">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between ">

                                        @php
                                            $verify_status = \App\Utils\OrderManager::verifyCartListMinimumOrderAmount($request, $group_key);
                                        @endphp

                                        <div class="min-w-180 d-flex">
                                            @if($cartItem->seller_is=='admin')
                                                <div class="d-flex gap-3 align-items-center">
                                                    <input type="checkbox" class="shop-head-check shop-head-check-desktop w-auto">
                                                    <a href="{{route('shopView',['id'=>0])}}" class="cart-shop">
                                                        <img loading="lazy" alt="{{ translate('logo') }}"
                                                             src="{{ getStorageImages(path: $web_config['fav_icon'], type: 'shop') }}">
                                                        <h6 class="text-base">{{$web_config['company_name']}}</h6>
                                                    </a>
                                                </div>
                                            @else
                                                <div class="d-flex gap-3 align-items-center">
                                                    <input type="checkbox" class="shop-head-check shop-head-check-desktop w-auto">
                                                    <a href="{{route('shopView',['id'=>$cartItem->seller_id])}}"
                                                       class="cart-shop">
                                                        <img loading="lazy" alt="{{ translate('shop') }}"
                                                             src="{{ getStorageImages(path: $cartItem->seller->shop->image_full_url, type: 'shop') }}">
                                                        <h6 class="text-base">
                                                            {{ $cartItem->seller?->shop?->name ?? translate('vendor_not_available') }}
                                                        </h6>
                                                    </a>
                                                </div>
                                            @endif

                                            @if ($verify_status['minimum_order_amount'] > $verify_status['amount'])
                                                <span class="ps-2 text-danger pulse-button minimum_Order_Amount_message"
                                                      data-bs-toggle="tooltip" data-bs-placement="right"
                                                      data-bs-custom-class="custom-tooltip"
                                                      data-bs-title="{{ translate('minimum_Order_Amount') }} {{ webCurrencyConverter($verify_status['minimum_order_amount']) }} {{ translate('for') }} @if($cartItem->seller_is=='admin') {{ getWebConfig(name: 'company_name') }} @else {{ \App\Utils\get_shop_name($cartItem['seller_id']) }} @endif">
                                                <i class="bi bi-info-circle"></i>
                                            </span>
                                            @endif
                                        </div>

                                        @if($physical_product && $shippingMethod=='sellerwise_shipping' && $shipping_type == 'order_wise')
                                            @php
                                                $choosen_shipping=\App\Models\CartShipping::where(['cart_group_id'=>$cartItem['cart_group_id']])->first()
                                            @endphp

                                            @if(isset($choosen_shipping)==false)
                                                @php $choosen_shipping['shipping_method_id']=0 @endphp
                                            @endif

                                            @php
                                                $shippings=\App\Utils\Helpers::getShippingMethods($cartItem['seller_id'],$cartItem['seller_is'])

                                            @endphp
                                            @if($physical_product && $shippingMethod=='sellerwise_shipping' && $shipping_type == 'order_wise')
                                                @if(count($shippings) > 0)
                                                    <div>
                                                        <div class="dropdown">
                                                            <a class="bg-white select-method-border rounded py-2 text-dark d-flex flex-wrap align-items-center" href="javascript:" data-bs-toggle="dropdown">
                                                                    <?php
                                                                    $shippings_title = translate('shipping_method');
                                                                    foreach ($shippings as $shipping) {
                                                                        if ($choosen_shipping['shipping_method_id'] == $shipping['id']) {
                                                                            $shippings_title = ucfirst($shipping['title']) . ' ( ' . $shipping['duration'] . ' ) ' . webCurrencyConverter($shipping['cost']);
                                                                        }
                                                                    }
                                                                    ?>
                                                                <div class="flex-middle flex-nowrap fw-semibold text-dark mx-3 text-capitalize">
                                                                    <i class="bi bi-truck"></i>
                                                                    {{ translate('shipping_method') }} :
                                                                </div>
                                                                {{ $shippings_title }}
                                                                <i class="ms-1 text-small bi bi-chevron-down"></i>
                                                            </a>
                                                            <div class="dropdown-menu __dropdown-menu text-nowrap w-100">
                                                                <ul class="">
                                                                    @foreach($shippings as $shipping)
                                                                        <li class="cursor-pointer text-dark px-3 py-1 set_shipping_id_function"
                                                                            data-id="{{$shipping['id']}}"
                                                                            data-cart-group="{{$cartItem['cart_group_id']}}"
                                                                        >
                                                                            {{ucfirst($shipping['title']).' ( '.$shipping['duration'].' ) '.webCurrencyConverter($shipping['cost'])}}
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>

                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="badge font-regular badge-soft-danger cursor-pointer border-danger border" data-bs-toggle="tooltip"
                                                          data-bs-placement="top"
                                                          title="{{ translate('No_shipping_options_available_at_this_shop') }}, {{ translate('please_remove_all_items_from_this_shop') }}">
                                                    {{ translate('shipping_Not_Available') }}
                                                </span>
                                                @endif

                                            @endif
                                        @else
                                            @if ($shipping_type != 'order_wise')
                                                <div class=" bg-white select-method-border rounded  py-2">
                                                    <div class="d-flex ">
                                                        <div
                                                            class="flex-middle flex-nowrap fw-semibold text-dark mx-3 text-capitalize">
                                                            <i class="bi bi-truck"></i>
                                                            {{ translate('shipping_cost') }} :
                                                        </div>
                                                        <div class="">
                                                            <span>{!! webCurrencyConverterWithImage($total_shipping_cost) !!}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @php($product = $cartItem->allProducts)

                            @if (!$product)
                                @php($productNullStatus = 1)
                            @endif

                            <?php
                            $checkProductStatus = $cartItem->allProducts?->status ?? 0;
                            if($cartItem->seller_is == 'admin') {
                                $inhouseTemporaryClose = getWebConfig(name: 'temporary_close') ? getWebConfig(name: 'temporary_close')['status'] : 0;
                                $inhouseVacation = getWebConfig(name: 'vacation_add');
                                $vacationStartDate = $inhouseVacation['vacation_start_date'] ? date('Y-m-d', strtotime($inhouseVacation['vacation_start_date'])) : null;
                                $vacationEndDate = $inhouseVacation['vacation_end_date'] ? date('Y-m-d', strtotime($inhouseVacation['vacation_end_date'])) : null;
                                $vacationStatus = $inhouseVacation['status'] ?? 0;
                                if ($inhouseTemporaryClose || ($vacationStatus && (date('Y-m-d') >= $vacationStartDate) && (date('Y-m-d') <= $vacationEndDate))) {
                                    $checkProductStatus = 0;
                                }
                            }else{
                                if (!isset($cartItem->allProducts->seller) || (isset($cartItem->allProducts->seller) && $cartItem->allProducts->seller->status != 'approved')) {
                                    $checkProductStatus = 0;
                                }
                                if (!isset($cartItem->allProducts->seller->shop) || $cartItem->allProducts->seller->shop->temporary_close) {
                                    $checkProductStatus = 0;
                                }
                                if(isset($cartItem->allProducts->seller->shop) && ($cartItem->allProducts->seller->shop->vacation_status && (date('Y-m-d') >= $cartItem->allProducts->seller->shop->vacation_start_date) && (date('Y-m-d') <= $cartItem->allProducts->seller->shop->vacation_end_date))) {
                                    $checkProductStatus = 0;
                                }
                            }
                            ?>

                            <form class="cart add_to_cart_form{{$cartItem['id']}}"
                                      id="add_to_cart_form_web{{$cartItem['id']}}"
                                      action="{{route('cart.update-variation')}}"
                                      data-errormessage="{{translate('please_choose_all_the_options')}}"
                                      data-outofstock="{{translate('sorry').', '.translate('out_of_stock')}}.">
                                    @csrf
                                    <table class="table __table vertical-middle cart-list-table-custom">
                                        <tbody>
                                        <tr>
                                            <td>
                                                <input type="text" name="id" value="{{ $cartItem->id }}" hidden>
                                                @if($product)
                                                    <input type="text" name="product_id" value="{{ $product->id }}" hidden>
                                                @endif
                                                <div class="d-flex gap-3 align-items-center">
                                                    <input type="checkbox" class="shop-item-check w-auto shop-item-check-desktop" value="{{ $cartItem['id'] }}" {{ $cartItem['is_checked'] ? 'checked' : '' }}>
                                                    <div class="cart-product  align-items-center">
                                                        <label class="form-check position-relative overflow-hidden">
                                                            @if ($product && $product->status == 1)
                                                                <img loading="lazy" alt="{{ translate('product') }}"
                                                                     src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}">
                                                            @elseif($product && $product->status == 0)
                                                                <img loading="lazy" alt="{{ translate('product') }}"
                                                                     src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}">
                                                                <span
                                                                    class="temporary-closed position-absolute text-center p-2">
                                                                        <span>{{translate('not_available')}}</span>
                                                                    </span>
                                                            @else
                                                                <img loading="lazy"
                                                                     src="{{ theme_asset('assets/img/image-place-holder.png') }}"
                                                                     alt="{{ translate('product') }}">
                                                                <span
                                                                    class="temporary-closed position-absolute text-center p-2">
                                                                        <span>{{translate('not_available')}}</span>
                                                                    </span>
                                                            @endif
                                                        </label>


                                                        <div class="cont {{ $checkProductStatus == 0 ? 'custom-cart-opacity-50':'' }}">
                                                            <a href="{{ $checkProductStatus ? route('product',$product['slug']) : 'javascript:' }}"
                                                               class="name text-title">
                                                                {{ $checkProductStatus ? $product->name : $cartItem->name}}
                                                            </a>
                                                            <div class="d-flex column-gap-1">
                                                                <span>{{ translate('price') }}</span> <span>:</span> <strong
                                                                    class="price-red product_price{{$cartItem['id']}}">{!! webCurrencyConverterWithImage($cartItem->price) !!}</strong>
                                                            </div>
                                                            <div class="d-flex column-gap-1">
                                                                @if (isset($product->category))
                                                                    <span>{{ translate('category') }} </span> <span>:</span>
                                                                    <strong>{{ isset($product->category) ? $product->category->name:'' }}</strong>
                                                                @endif
                                                            </div>
                                                            @if ($product)
                                                                    <?php
                                                                    $getProductCurrentStock = $product->current_stock;
                                                                    if(!empty($product->variation)) {
                                                                        foreach(json_decode($product->variation, true) as $productVariantSingle) {
                                                                            if($productVariantSingle['type'] == $cartItem->variant) {
                                                                                $getProductCurrentStock = $productVariantSingle['qty'];
                                                                            }
                                                                        }
                                                                    }
                                                                    ?>

                                                                @if ($product->product_type == "physical")
                                                                    <div class="d-flex flex-wrap column-gap-3">
                                                                        @if (!empty(json_decode($product->colors)))
                                                                            <div class="d-flex column-gap-1">
                                                                                <span>{{ translate('color') }} </span>
                                                                                <span>:</span>
                                                                                <select
                                                                                    class="no-border-select variants-class{{$cart_key}} update_add_to_cart_by_variation_web"
                                                                                    data-id="{{$cartItem['id']}}"
                                                                                    name="color" {{ $checkProductStatus == 0 ? 'disabled':'' }}>
                                                                                    @foreach (json_decode($product->colors) as $k=>$value)
                                                                                        <option
                                                                                            value="{{ $value }}" {{ $cartItem->color == $value ? 'selected':'' }}>{{getColorNameByCode(code: $value)}}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>
                                                                        @endif

                                                                        @php($variations = json_decode($cartItem->variations,true))
                                                                        @foreach (json_decode($product->choice_options) as $k => $choice)
                                                                            <div class="d-flex column-gap-1">
                                                                                <span> {{ translate( $choice->title )}} </span>
                                                                                <span>:</span>
                                                                                <select
                                                                                    class="no-border-select variants-class{{$cart_key}} update_add_to_cart_by_variation_web"
                                                                                    data-id="{{$cartItem['id']}}"
                                                                                    name="{{$choice->name}}" {{ $checkProductStatus == 0 ? 'disabled':'' }}>
                                                                                    @foreach ($choice->options as $value)
                                                                                        <option
                                                                                            value="{{ trim($value) }}" {{in_array(trim($value),$variations,true) ? 'selected' : ''}}>{{ ucwords($value) }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                    @if ( $shipping_type != 'order_wise')
                                                                        <div class="d-flex column-gap-1">
                                                                            <span>{{ translate('shipping_cost') }}</span> <span>:</span>
                                                                            <strong
                                                                                class="price-red">{!! webCurrencyConverterWithImage($cartItem['shipping_cost']) !!}</strong>
                                                                        </div>
                                                                    @endif

                                                                    @if($getProductCurrentStock < $cartItem['quantity'])
                                                                        <div class="d-flex text-danger font-bold">
                                                                            <span>{{ translate('Out_Of_Stock') }}</span>
                                                                        </div>
                                                                    @endif
                                                                @endif

                                                                @php($extensionIndex=0)
                                                                <input hidden name="current_variant_key" value="{{ $cartItem['variant'] }}">
                                                                @if($product['product_type'] == 'digital' && $product['digital_product_file_types'] && count($product['digital_product_file_types']) > 0 && $product['digital_product_extensions'])
                                                                    <div class="d-flex column-gap-1">
                                                                        <span> {{ translate('Select_an_extension')}} </span>
                                                                        <span>:</span>
                                                                        <select class="no-border-select variants-class{{$cart_key}} update_add_to_cart_by_variation_web"
                                                                            data-id="{{ $cartItem['id'] }}" name="variant_key" {{ $checkProductStatus == 0 ? 'disabled':'' }}
                                                                            {{ $extensionIndex == 0 ? 'checked' : ''}}>
                                                                        @foreach($product['digital_product_extensions'] as $extensionKey => $extensionGroup)
                                                                            @foreach($extensionGroup as $index => $extension)
                                                                                <option value="{{ $extensionKey.'-'. preg_replace('/\s+/', '-', $extension) }}"
                                                                                    {{ $cartItem['variant'] == $extensionKey.'-'. preg_replace('/\s+/', '-', $extension) ? 'selected' : '' }}
                                                                                >{{ ucwords($extension) }}</option>
                                                                                @php($extensionIndex++)
                                                                            @endforeach
                                                                        @endforeach
                                                                        </select>
                                                                    </div>
                                                                @endif

                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if ($cartItem['discount'] > 0)
                                                    <span class="badge badge-soft-base product_discount{{$cartItem['id']}}">-{!! webCurrencyConverterWithImage($cartItem['discount']*$cartItem['quantity']) !!}</span>
                                                @else
                                                    <span
                                                        class="badge text-capitalize badge-soft-secondary discount{{$cartItem['id']}}">{{translate('no_discount')}}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @php($minimum_order=\App\Utils\ProductManager::get_product($cartItem['product_id']))

                                                @if($minimum_order && $checkProductStatus)
                                                    <div class="quantity __quantity">
                                                        <input type="number"
                                                               class="quantity__qty cart-qty-input cart-quantity-web{{$cartItem['id']}} form-control cartQuantity{{$cartItem['id']}} updateCartQuantityList_cart_data"
                                                               value="{{$cartItem['quantity']}}" name="quantity"
                                                               id="cartQuantityWeb{{$cartItem['id']}}"
                                                               data-minorder="{{ $minimum_order->minimum_order_qty }}"
                                                               data-cart="{{ $cartItem['id'] }}" data-value="0"
                                                               data-action=""
                                                               data-current-stock="{{ $getProductCurrentStock }}"
                                                               data-min="{{ isset($cartItem->product->minimum_order_qty) ? $cartItem->product->minimum_order_qty : 1 }}">
                                                        <div>
                                                            <div
                                                                class="quantity__plus cart-qty-btn updateCartQuantityList_cart_data"
                                                                data-minorder="{{ $minimum_order->minimum_order_qty }}"
                                                                data-cart="{{ $cartItem['id'] }}" data-value="1"
                                                                data-action=""
                                                            >
                                                                <i class="bi bi-plus "></i>
                                                            </div>
                                                            <div
                                                                class="quantity__minus cart-qty-btn updateCartQuantityList_cart_data"
                                                                data-minorder="{{ $minimum_order->minimum_order_qty }}"
                                                                data-cart="{{ $cartItem['id'] }}" data-value="-1"
                                                                data-action="{{ $cartItem['quantity'] == $minimum_order->minimum_order_qty ? 'delete':'minus' }}"
                                                            >
                                                                @if( $getProductCurrentStock < $cartItem['quantity'] || $cartItem['quantity'] == (isset($cartItem->product->minimum_order_qty) ? $cartItem->product->minimum_order_qty : 1))
                                                                    <i class="bi bi-trash3-fill text-danger fs-10"></i>
                                                                @else
                                                                    <i class="bi bi-dash-lg"></i>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="quantity __quantity">
                                                        <input type="text"
                                                               class="quantity__qty cart-qty-input cart-quantity-web{{$cartItem['id']}} form-control cartQuantity{{$cartItem['id']}}"
                                                               name="quantity" id="cartQuantity{{$cartItem['id']}}"
                                                               data-min="{{$cartItem['quantity']}}"
                                                               value="{{$cartItem['quantity']}}" readonly>
                                                        <div>
                                                            <div class="cart-qty-btn disabled"
                                                                 title="{{ translate('product_not_available') }}">
                                                                <i class="bi bi-exclamation-circle text-danger"></i>
                                                            </div>
                                                            <div class="cart-qty-btn updateCartQuantityList_cart_data"
                                                                 data-minorder="{{$cartItem['quantity']+1}}"
                                                                 data-cart="{{ $cartItem['id'] }}"
                                                                 data-value="-{{$cartItem['quantity']}}"
                                                                 data-action="delete">
                                                                <i class="bi bi-trash3-fill text-danger fs-10}"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                            @php($total_amount = $total_amount + ($cartItem['price']-$cartItem['discount'])*$cartItem['quantity'])
                                            <td class="text-center price-red">{!! webCurrencyConverterWithImage(($cartItem['price']-$cartItem['discount'])*$cartItem['quantity']) !!}</td>

                                        </tr>
                                        </tbody>
                                    </table>
                                </form>
                        @endforeach
                        </div>

                        @php($free_delivery_status = \App\Utils\OrderManager::getFreeDeliveryOrderAmountArray($group[0]->cart_group_id))

                        @if ($free_delivery_status['status'] && (session()->missing('coupon_type') || session('coupon_type') !='free_delivery'))
                            <div class="free-delivery-area px-3 mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <img loading="lazy"
                                         src="{{ theme_asset('assets/img/free-shipping.svg') }}"
                                         alt="{{ translate('free_shipping') }}" width="40">
                                    @if ($free_delivery_status['amount_need'] <= 0)
                                        <span class="text-muted fs-16">
                                                                {{ translate('you_Get_Free_Delivery_Bonus') }}
                                                            </span>
                                    @else
                                        <span
                                            class="need-for-free-delivery font-bold">{!! webCurrencyConverterWithImage($free_delivery_status['amount_need']) !!}</span>
                                        <span class="text-muted fs-16">
                                                                {{ translate('add_more_for_free_delivery') }}
                                                            </span>
                                    @endif
                                </div>
                                <div class="progress free-delivery-progress">
                                    <div class="progress-bar" role="progressbar"
                                         style="width: {{ $free_delivery_status['percentage'] }}%"
                                         aria-valuenow="{{ $free_delivery_status['percentage'] }}" aria-valuemin="0"
                                         aria-valuemax="100"></div>
                                </div>
                            </div>
                        @endif

                        @endforeach

                </div>


                <div class="d-flex d-md-none flex-column mt-4 gap-3">
                    @foreach($cart as $group_key=>$group)
                        <?php
                            $physical_product = false;
                            $total_shipping_cost = 0;
                            foreach ($group as $row) {
                                if ($row->product_type == 'physical' && $row->is_checked) {
                                    $physical_product = true;
                                }
                                if ($row->product_type == 'physical' && $row->shipping_type != "order_wise") {
                                    $total_shipping_cost += $row->shipping_cost;
                                }
                            }

                            $physical_product = false;
                            foreach ($group as $row) {
                                if ($row->product_type == 'physical' && $row->is_checked) {
                                    $physical_product = true;
                                }
                            }
                        ?>

                        <div class="cart_information">
                        @foreach($group as $cartKeyMobile => $cartItem)
                            @if ($shippingMethod=='inhouse_shipping')
                                    <?php
                                    $admin_shipping = \App\Models\ShippingType::where('seller_id', 0)->first();
                                    $shipping_type = isset($admin_shipping) == true ? $admin_shipping->shipping_type : 'order_wise';
                                    ?>
                            @else
                                    <?php
                                    if ($cartItem->seller_is == 'admin') {
                                        $admin_shipping = \App\Models\ShippingType::where('seller_id', 0)->first();
                                        $shipping_type = isset($admin_shipping) == true ? $admin_shipping->shipping_type : 'order_wise';
                                    } else {
                                        $seller_shipping = \App\Models\ShippingType::where('seller_id', $cartItem->seller_id)->first();
                                        $shipping_type = isset($seller_shipping) == true ? $seller_shipping->shipping_type : 'order_wise';
                                    }
                                    ?>
                            @endif


                            @if($cartKeyMobile==0)
                                <div class="--bg-6 border-0 rounded py-2 px-2 px-sm-3 ">
                                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between ">
                                        <div class="flex-grow-1">
                                            <div class="d-flex gap-2">
                                                <input type="checkbox" class="shop-head-check shop-head-check-mobile w-auto">
                                                @if($cartItem->seller_is=='admin')
                                                    <a href="{{route('shopView',['id'=>0])}}" class="cart-shop">
                                                        <img loading="lazy" alt="{{ translate('shop') }}"
                                                             src="{{ getStorageImages(path: $web_config['fav_icon'], type: 'logo')}}">
                                                        <h6 class="name text-base text-nowrap w-100">{{$web_config['company_name']}}</h6>
                                                    </a>
                                                @else
                                                    <a href="{{route('shopView',['id'=>$cartItem->seller_id])}}"
                                                       class="cart-shop">
                                                        <img loading="lazy" alt="{{ translate('shop') }}"
                                                             src="{{ getStorageImages(path: $cartItem->seller->shop->image_full_url, type: 'shop') }}">
                                                        <h6 class="name text-base text-nowrap w-100">
                                                            {{ $cartItem->seller?->shop?->name ?? translate('vendor_not_available') }}
                                                        </h6>
                                                    </a>
                                                @endif

                                                    <?php
                                                    $verify_status = \App\Utils\OrderManager::verifyCartListMinimumOrderAmount($request, $group_key);
                                                    ?>

                                                @if ($verify_status['minimum_order_amount'] > $verify_status['amount'])
                                                    <span
                                                        class="ps-2 text-danger pulse-button minimum_Order_Amount_message"
                                                        data-bs-title="{{ translate('minimum_Order_Amount') }} {{ webCurrencyConverter($verify_status['minimum_order_amount']) }} {{ translate('for') }} @if($cartItem->seller_is=='admin') {{ getWebConfig(name: 'company_name') }} @else {{ \App\Utils\get_shop_name($cartItem['seller_id']) }} @endif">
                                                <i class="bi bi-info-circle"></i>
                                            </span>
                                                @endif
                                            </div>
                                        </div>
                                        @if($physical_product && $shippingMethod=='sellerwise_shipping' && $shipping_type == 'order_wise')
                                            @php($choosen_shipping=\App\Models\CartShipping::where(['cart_group_id'=>$cartItem['cart_group_id']])->first())
                                            @if(isset($choosen_shipping)==false)
                                                @php ( $choosen_shipping['shipping_method_id']= 0 )
                                            @endif

                                            @php($shippings=\App\Utils\Helpers::getShippingMethods($cartItem['seller_id'],$cartItem['seller_is']))

                                            @if($physical_product && $shippingMethod=='sellerwise_shipping' && $shipping_type == 'order_wise')
                                                @if(count($shippings) > 0)
                                                    <div class="max-sm-200px bg-white select-method-border rounded  py-2">
                                                        <div class="d-flex w-100">
                                                            <div class="flex-middle flex-nowrap fw-semibold text-dark mx-2">
                                                                <i class="bi bi-truck"></i>
                                                            </div>

                                                            <div class="dropdown">
                                                                <a class="text-dark" href="javascript:" data-bs-toggle="dropdown">
                                                                        <?php
                                                                        $shippings_title = translate('shipping_method');
                                                                        foreach ($shippings as $shipping) {
                                                                            if ($choosen_shipping['shipping_method_id'] == $shipping['id']) {
                                                                                $shippings_title = ucfirst($shipping['title']) . ' ( ' . $shipping['duration'] . ' ) ' . webCurrencyConverter($shipping['cost']);
                                                                            }
                                                                        }
                                                                        ?>
                                                                    {{ $shippings_title }}
                                                                    <i class="ms-1 text-small bi bi-chevron-down"></i>
                                                                </a>
                                                                <div class="dropdown-menu __dropdown-menu">
                                                                    <ul class="">
                                                                        @foreach($shippings as $shipping)
                                                                            <li class="cursor-pointer text-dark text-nowrap px-3 py-1 set_shipping_id_function"
                                                                                data-id="{{$shipping['id']}}"
                                                                                data-cart-group="{{$cartItem['cart_group_id']}}"
                                                                            >
                                                                                {{ucfirst($shipping['title']).' ( '.$shipping['duration'].' ) '.webCurrencyConverter($shipping['cost'])}}
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="badge font-regular badge-soft-danger cursor-pointer border-danger border" data-bs-toggle="tooltip"
                                                          data-bs-placement="top"
                                                          title="{{ translate('No_shipping_options_available_at_this_shop') }}, {{ translate('please_remove_all_items_from_this_shop') }}">
                                                    {{ translate('shipping_Not_Available') }}
                                                </span>
                                                @endif
                                            @endif
                                        @else
                                            <div class=" bg-white select-method-border rounded  py-2">
                                                <div class="d-flex ">
                                                    <div
                                                        class="flex-middle flex-nowrap fw-semibold text-dark mx-3 text-capitalize">
                                                        <i class="bi bi-truck"></i>
                                                        {{ translate('shipping_cost') }} :
                                                    </div>
                                                    <div class="">
                                                        <span>{!! webCurrencyConverterWithImage($total_shipping_cost) !!}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @php($product = $cartItem->product)

                                <?php
                                $checkProductStatus = $cartItem->allProducts?->status ?? 0;
                                if($cartItem->seller_is == 'admin') {
                                    $inhouseTemporaryClose = getWebConfig(name: 'temporary_close') ? getWebConfig(name: 'temporary_close')['status'] : 0;
                                    $inhouseVacation = getWebConfig(name: 'vacation_add');
                                    $vacationStartDate = $inhouseVacation['vacation_start_date'] ? date('Y-m-d', strtotime($inhouseVacation['vacation_start_date'])) : null;
                                    $vacationEndDate = $inhouseVacation['vacation_end_date'] ? date('Y-m-d', strtotime($inhouseVacation['vacation_end_date'])) : null;
                                    $vacationStatus = $inhouseVacation['status'] ?? 0;
                                    if ($inhouseTemporaryClose || ($vacationStatus && (date('Y-m-d') >= $vacationStartDate) && (date('Y-m-d') <= $vacationEndDate))) {
                                        $checkProductStatus = 0;
                                    }
                                }else{
                                    if (!isset($cartItem->allProducts->seller) || (isset($cartItem->allProducts->seller) && $cartItem->allProducts->seller->status != 'approved')) {
                                        $checkProductStatus = 0;
                                    }
                                    if (!isset($cartItem->allProducts->seller->shop) || $cartItem->allProducts->seller->shop->temporary_close) {
                                        $checkProductStatus = 0;
                                    }
                                    if(isset($cartItem->allProducts->seller->shop) && ($cartItem->allProducts->seller->shop->vacation_status && (date('Y-m-d') >= $cartItem->allProducts->seller->shop->vacation_start_date) && (date('Y-m-d') <= $cartItem->allProducts->seller->shop->vacation_end_date))) {
                                        $checkProductStatus = 0;
                                    }
                                }
                                ?>

                            <form class="cart add_to_cart_form{{$cartItem['id']}}"
                                  id="add_to_cart_form_mobile{{$cartItem['id']}}"
                                  action="{{route('cart.update-variation')}}"
                                  data-errormessage="{{translate('please_choose_all_the_options')}}"
                                  data-outofstock="{{translate('sorry').', '.translate('out_of_stock')}}.">
                                @csrf
                                <div class="d-flex gap-3 pb-3 border-bottom justify-content-between align-items-center">
                                    <input type="text" name="id" value="{{ $cartItem->id }}" hidden>
                                    <input type="text" name="product_id"
                                           value="{{ isset($product) ? $product->id : ''}}" hidden>

                                    <div class="d-flex align-item-center gap-2">
                                        <input type="checkbox" class="shop-item-check w-auto shop-item-check-mobile" value="{{ $cartItem['id'] }}" {{ $cartItem['is_checked'] ? 'checked' : '' }}>
                                        <div class="cart-product align-items-center">
                                            <label class="form-check">
                                                @if (isset($product))
                                                    <img loading="lazy" alt="{{ translate('product') }}"
                                                         src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}">
                                                @else
                                                    <img loading="lazy"
                                                         src="{{ theme_asset('assets/img/image-place-holder.png') }}"
                                                         alt="{{ translate('product') }}">

                                                @endif
                                            </label>
                                            <div class="cont d-flex flex-column gap-1 {{$checkProductStatus == 0 ? 'custom-cart-opacity-50' : '' }}">
                                                <a href="{{ isset($product) ? route('product',$product['slug']) : 'javascript:'}}"
                                                   class="name text-title">{{ isset($product) ? $product->name : $cartItem->name }}</a>
                                                <div class="d-flex">
                                                    <span>{{ translate('price') }}</span> <span>:</span> <strong
                                                        class="product_price{{$cartItem['id']}}">{!! webCurrencyConverterWithImage($cartItem->price) !!}</strong>
                                                </div>
                                                <div class="d-flex">
                                                    @if (isset($product->category))
                                                        <span>{{ translate('category') }} </span> <span>:</span>
                                                        <strong>{{ isset($product->category) ? $product->category->name:'' }}</strong>
                                                    @endif
                                                </div>
                                                @if (isset($product))
                                                    <div class="d-flex">
                                                        @if ($cartItem['discount'] > 0 )
                                                            <span>{{ translate('discount') }}</span> <span>:</span>
                                                            <strong
                                                                class="product_discount{{$cartItem['id']}}">{!! webCurrencyConverterWithImage($cartItem['discount']*$cartItem['quantity']) !!}</strong>
                                                        @else
                                                            <span>{{ translate('discount') }}</span> <span>:</span>
                                                            <span
                                                                class="badge text-capitalize badge-soft-secondary discount{{$cartItem['id']}}">{{translate('no_discount')}}</span>
                                                        @endif
                                                    </div>
                                                    <div class="d-flex">
                                                        <span>{{ translate('total_price') }}</span> <span>:</span>
                                                        <strong>{!! webCurrencyConverterWithImage(($cartItem['price']-$cartItem['discount'])*$cartItem['quantity']) !!}</strong>
                                                    </div>

                                                    @if ($product->product_type == "physical")
                                                        @if ( $shipping_type != 'order_wise' )
                                                            <div class="d-flex ">
                                                                <span>{{ translate('shipping_cost') }}</span> <span>:</span>
                                                                <strong>{!! webCurrencyConverterWithImage($cartItem['shipping_cost']) !!}</strong>
                                                            </div>
                                                        @endif
                                                        <div class="column-gap-3">
                                                            @if (!empty(json_decode($product->colors)))
                                                                <div class="d-flex">
                                                                    <span>{{ translate('color') }} </span> <span>:</span>
                                                                    <select
                                                                        class="no-border-select text-title variants-class{{$cartKeyMobile}} update_add_to_cart_by_variation_mobile"
                                                                        data-id="{{$cartItem['id']}}" name="color">
                                                                        @foreach (json_decode($product->colors) as $k=>$value)
                                                                            <option
                                                                                value="{{ $value }}"{{ $cartItem->color == $value ? 'selected':'' }}>{{getColorNameByCode(code: $value)}}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            @endif
                                                            @php($variations = json_decode($cartItem->variations,true))
                                                            @foreach (json_decode($product->choice_options) as $k => $choice)
                                                                <div class="d-flex">
                                                                    <span> {{ translate( $choice->title )}} </span>
                                                                    <span>:</span>
                                                                    <select
                                                                        class="no-border-select text-title variants-class{{$cartKeyMobile}} update_add_to_cart_by_variation_mobile"
                                                                        data-id="{{$cartItem['id']}}"
                                                                        name="{{$choice->name}}">
                                                                        @foreach ($choice->options as $value)
                                                                            <option
                                                                                value="{{ trim($value) }}" {{in_array(trim($value),$variations,true) ? 'selected' : ''}}>{{ ucwords($value) }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            @endforeach
                                                        </div>

                                                        @if($getProductCurrentStock < $cartItem['quantity'])
                                                            <div class="d-flex text-danger font-bold">
                                                                <span>{{ translate('Out_Of_Stock') }}</span>
                                                            </div>
                                                        @endif

                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>


                                    @php($minimum_order=\App\Utils\ProductManager::get_product($cartItem['product_id']))
                                    @if($minimum_order && $checkProductStatus)
                                        <div class="quantity quantity--style-two d-flex flex-column align-items-center">
                                            <div
                                                class="quantity__minus cart-qty-btn updateCartQuantityListMobile_cart_data"
                                                data-minorder="{{ $minimum_order->minimum_order_qty }}"
                                                data-cart="{{ $cartItem['id'] }}" data-value="-1"
                                                data-action="{{ $cartItem['quantity'] == $minimum_order->minimum_order_qty ? 'delete':'minus' }}"
                                            >
                                                @if($getProductCurrentStock < $cartItem['quantity'] || $cartItem['quantity'] == (isset($cartItem->product->minimum_order_qty) ? $cartItem->product->minimum_order_qty : 1))
                                                    <i class="bi bi-trash3-fill text-danger fs-10"></i>
                                                @else
                                                    <i class="bi bi-dash-lg"></i>
                                                @endif
                                            </div>
                                            <input type="text"
                                                   class="updateCartQuantityListMobile_cart_data quantity__qty cart-qty-input form-control cart-quantity-mobile{{$cartItem['id']}} cartQuantity{{$cartItem['id']}}"
                                                   value="{{$cartItem['quantity']}}" name="quantity"
                                                   id="cartQuantityMobile{{$cartItem['id']}}"
                                                   data-minorder="{{ $minimum_order->minimum_order_qty }}"
                                                   data-current-stock="{{ $getProductCurrentStock }}"
                                                   data-cart="{{ $cartItem['id'] }}" data-value="0" data-action=""
                                                   data-min="{{ isset($cartItem->product->minimum_order_qty) ? $cartItem->product->minimum_order_qty : 1 }}">

                                            <div
                                                class="quantity__plus cart-qty-btn updateCartQuantityListMobile_cart_data"
                                                data-minorder="{{ $minimum_order->minimum_order_qty }}"
                                                data-cart="{{ $cartItem['id'] }}" data-value="1" data-action="">
                                                <i class="bi bi-plus "></i>
                                            </div>
                                        </div>
                                    @else
                                        <div class="quantity quantity--style-two d-flex flex-column align-items-center">
                                            <div class="cart-qty-btn updateCartQuantityList_cart_data"
                                                 data-minorder="{{ $cartItem['quantity']+1 }}"
                                                 data-cart="{{ $cartItem['id'] }}"
                                                 data-value="-{{$cartItem['quantity']}}" data-action="delete">
                                                <i class="bi bi-trash3-fill text-danger fs-10"></i>
                                            </div>
                                            <input type="text"
                                                   class="quantity__qty cart-qty-input form-control cart-quantity-mobile{{$cartItem['id']}} cartQuantity{{$cartItem['id']}} updateCartQuantityList_cart_data"
                                                   data-minorder="{{ $minimum_order->minimum_order_qty ?? 1 }}"
                                                   data-cart="{{ $cartItem['id'] }}" data-value="0" data-action=""
                                                   value="{{$cartItem['quantity']}}" name="quantity"
                                                   id="cartQuantityMobile{{$cartItem['id']}}"
                                                   data-current-stock="{{ $getProductCurrentStock }}"
                                                   data-min="{{$cartItem['quantity']}}" disabled>
                                            <div class="cart-qty-btn" disabled
                                                 title="{{ translate('product_not_available') }}">
                                                <i class="bi bi-exclamation-circle text-danger"></i>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            </form>

                        @endforeach
                        </div>

                        @php($free_delivery_status = \App\Utils\OrderManager::getFreeDeliveryOrderAmountArray($group[0]->cart_group_id))
                        @if ($free_delivery_status['status'] && (session()->missing('coupon_type') || session('coupon_type') !='free_delivery'))
                            <div class="free-delivery-area px-3 mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <img loading="lazy"
                                         src="{{ theme_asset('assets/img/free-shipping.svg') }}"
                                         alt="{{ translate('free_shipping') }}" width="40">
                                    @if ($free_delivery_status['amount_need'] <= 0)
                                        <span class="text-muted fs-16">
                                                        {{ translate('you_Get_Free_Delivery_Bonus') }}
                                                    </span>
                                    @else
                                        <span
                                            class="need-for-free-delivery font-bold">{!! webCurrencyConverterWithImage($free_delivery_status['amount_need']) !!}</span>
                                        <span class="text-muted fs-16">
                                                        {{ translate('add_more_for_free_delivery') }}
                                                    </span>
                                    @endif
                                </div>
                                <div class="progress free-delivery-progress">
                                    <div class="progress-bar" role="progressbar"
                                         style="width: {{ $free_delivery_status['percentage'] }}%"
                                         aria-valuenow="{{ $free_delivery_status['percentage'] }}" aria-valuemin="0"
                                         aria-valuemax="100"></div>
                                </div>
                            </div>
                        @endif

                    @endforeach
                </div>


                <div>
                    @if($shippingMethod=='inhouse_shipping')
                            <?php
                            $physical_product = false;
                            foreach ($cart as $group_key => $group) {
                                foreach ($group as $row) {
                                    if ($row->product_type == 'physical' && $row->is_checked) {
                                        $physical_product = true;
                                    }
                                }
                            }

                            $admin_shipping = \App\Models\ShippingType::where('seller_id', 0)->first();
                            $shipping_type = isset($admin_shipping) == true ? $admin_shipping->shipping_type : 'order_wise';
                            ?>
                        @if ($shipping_type == 'order_wise' && $physical_product)
                            @php($shippings=\App\Utils\Helpers::getShippingMethods(1,'admin'))
                            @php($choosen_shipping=\App\Models\CartShipping::where(['cart_group_id'=>$cartItem['cart_group_id']])->first())

                            @if(isset($choosen_shipping)==false)
                                @php($choosen_shipping['shipping_method_id']=0)
                            @endif
                            <div class="row">
                                <div class="col-12">
                                    <!-- طريقة الشحن المحددة مسبقًا -->
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

            </div>
            <div class="col-lg-4 ps-lg-4 total-mobile ps-xl-5">
                @include('theme-views.partials._total-cost', ['isProductNullStatus'=>$productNullStatus])
            </div>

        </div>
    </div>
@else
    <div class="d-flex justify-content-center align-items-center w-100">
        <div class="d-flex flex-column justify-content-center align-items-center gap-2 py-5 w-100">
            <img width="80" class="mb-3" src="{{ theme_asset('assets/img/empty-state/empty-cart.svg') }}" alt="">
            <h5 class="text-center text-muted">
                {{ translate('You_have_not_added_anything_to_your_cart_yet') }}!
            </h5>
        </div>
    </div>
@endif

@push('script')
    <script src="{{ theme_asset('assets/js/cart-details.js') }}"></script>
@endpush

<style>
    .total-cost-wrapper {
        position: sticky;
        top: 0;
        z-index: 1;
    }
    @media screen and (max-width: 767px) {
        .total-mobile {
            width: 100% !important;
        }
        #mobile_app_bar {
            display: none !important;
        }
        .total-cost-area .d-block {
            display: none !important;
        }
        #mobile_app_bar {
            z-index: 0 !important;
        }
        
    }
</style>