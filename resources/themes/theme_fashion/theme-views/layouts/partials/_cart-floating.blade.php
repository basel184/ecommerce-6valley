@php($cart_mobile=\App\Utils\CartManager::getCartListQuery())
<span class="icon">
    <i class="bi bi-bag-check-fill"></i>
    <span class="status">{{$cart_mobile->count()}}</span>
</span>
<div>
    @if($cart_mobile->count() > 0)
        @php($sub_total=0)
        @php($total_tax=0)
        @foreach($cart_mobile as  $cartItem)
            @php($sub_total+=($cartItem['price']-$cartItem['discount'])*(int)$cartItem['quantity'])
            @php($total_tax+=$cartItem['tax']*(int)$cartItem['quantity'])
        @endforeach
        @if (webCurrencyConverterOnlyDigit($sub_total) > 1000000)
            {{webCurrencyConverter($sub_total / 1000000)}}M+
        @elseif(webCurrencyConverterOnlyDigit($sub_total) > 1000)
            {{webCurrencyConverter($sub_total / 1000)}}K+
        @else
            {{webCurrencyConverter($sub_total)}}
        @endif
    @endif
</div>
