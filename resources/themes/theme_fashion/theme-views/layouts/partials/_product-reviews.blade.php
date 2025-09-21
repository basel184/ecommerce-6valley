@foreach ($productReviews as $item)
<li class="d-block mx-3">
    <div class="d-flex flex-wrap align-items-start gap-3 {{ $item->reply ? 'before-content-border' : '' }}">
        <div class="author-area">
            <img loading="lazy" alt="{{ translate('profile') }}" class="mx-1"
                src="{{ getStorageImages(path:$item?->user?->image_full_url ?? '', type: 'avatar') }}">
            <div class="cont">
                <h6>
                    @if($item->user)
                        <div href="javascript:" class="text-capitalize">{{$item->user->f_name}} {{$item->user->l_name}}</div>
                    @else
                        <a href="javascript:" class="text-capitalize">{{translate('user_not_exist')}}</a>
                    @endif
                </h6>
                <span>
                    <i class="bi bi-star-fill text-star"></i>
                    {{$item->rating}}/{{ '5' }}
                </span>
            </div>
        </div>

        <div class="content-area mx-3 max-height-fixed review-comment-id{{ $item['id'] }}">
            <div class="d-flex justify-content-between">
                <div>
                    <p class="mb-3 review-comment-id{{ $item['id'] }}-primary">
                        @if(mb_strlen(strip_tags(str_replace('&nbsp;', ' ', $item->comment))) > 450)
                            {{ Str::limit((strip_tags(str_replace('&nbsp;', ' ', $item->comment))), 450) }}
                            <span class="read-more-current-review cursor-pointer text-base" data-element=".review-comment-id{{ $item['id'] }}">{{ translate('read_more') }}</span>
                        @else
                            {!! $item->comment !!}
                        @endif
                    </p>

                    <p class="mb-3 review-comment-id{{ $item['id'] }}-hidden d--none">
                        {!! $item->comment !!}
                    </p>
                </div>
                <div class="text-nowrap ps-1">
                    {{ isset($item->created_at) ? $item->created_at->format('M-d-Y') : '' }}
                </div>
            </div>
            @if(count($item->attachment_full_url)>0)
            <div class="products-comments-img d-flex flex-wrap gap-2 custom-image-popup-init">
                @foreach ($item->attachment_full_url as $img)
                    <a href="{{ getStorageImages(path:$img, type: 'product') }}" class="custom-image-popup">
                        <img loading="lazy" src="{{ getStorageImages(path: $img, type: 'product') }}" alt="{{$img['key']}}">
                    </a>
                @endforeach
            </div>
            @endif

        </div>
    </div>

    @if($item->reply && $item?->reply?->reply_text)
        <div class="ps-md-4 mt-3 me-1">
            <div class="review-reply rounded bg-E9F3FF80 p-3 ms-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <img src="{{dynamicAsset('/public/assets/front-end/img/seller-reply-icon.png')}}" alt="">
                        <h6 class="font-bold text-normal">{{ translate('Reply_by_Seller') }}</h6>
                    </div>
                    <span class="opacity-50">
                        {{ isset($item->reply->created_at) ? $item->reply->created_at->format('M-d-Y') : '' }}
                    </span>
                </div>
                <p class="text-sm">
                    {!! $item->reply->reply_text !!}
                </p>
            </div>
        </div>
    @endif
</li>
@endforeach
