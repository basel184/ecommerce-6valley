@if(!empty($products))
    <div class="border-top pt-2">
        <div class="px-3 pb-2 text-muted small">هل تقصد؟</div>
        <ul class="list-group list-group-flush border-0">
            @foreach($products as $product)
                <li class="list-group-item border-0">
                    <a href="{{ route('product', $product->slug) }}" class="text-base">
                        {{ $product['name'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
