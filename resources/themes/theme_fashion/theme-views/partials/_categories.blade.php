<section class="most-visited-category section-gap pb-0 text-center">
    <div class="container">
        <div class="section-title-3 mb-0">
            <div class="mb-32px">
                <h2 class="title mx-auto mb-0 text-capitalize">{{ translate('most_visited_categories') }}</h2>
            </div>
        </div>
        <div>
            <div class="most-visited-category-wrapper align-items-center d-none d-sm-flex">

                @if ($mostVisitedCategories[0])
                    <a href="{{route('products',['category_id'=> $mostVisitedCategories[0]->id,'data_from'=>'category','page'=>1])}}"
                    class="most-visited-item">
                        <img loading="lazy" alt="{{ translate('category') }}"
                            src="{{ getStorageImages(path: $mostVisitedCategories[0]->icon_full_url, type:'category') }}">
                        <h4 class="title"><span>{{ $mostVisitedCategories[0]->name }}</span></h4>
                        <div class="cont">
                            <h6 class="text-white font-semibold text-uppercase">{{ $mostVisitedCategories[0]->name }}</h6>
                            <span>{{ $mostVisitedCategories[0]->product_count }} {{ translate('product') }}</span>
                            <i class="bi bi-eye-fill"></i>
                        </div>
                    </a>
                @endif

                <div class="most-visited-area">
                    @foreach ($mostVisitedCategories as $key => $item)
                        @if ($key != 0 && $key < 8)
                            <a href="{{route('products',['category_id'=> $item->id,'data_from'=>'category','page'=>1])}}"
                            class="most-visited-item">
                                <img loading="lazy" alt="{{ translate('category') }}" src="{{ getStorageImages(path: $item->icon_full_url, type:'category') }}">
                                <h4 class="title">
                                    <span>{{ $item->name }}</span>
                                </h4>
                                <div class="cont">
                                    <h6 class="text-white font-semibold text-uppercase">{{ $item->name }}</h6>
                                    <span>{{ $item->product_count }} {{ translate('product') }}</span>
                                    <i class="bi bi-eye-fill"></i>
                                </div>
                            </a>
                        @endif

                    @endforeach
                </div>

                @if (isset($mostVisitedCategories[8]) && $mostVisitedCategories[8])
                    <a href="{{route('products',['category_id'=> $mostVisitedCategories[8]->id,'data_from'=>'category','page'=>1])}}"
                    class="most-visited-item">
                        <img loading="lazy" alt="{{ translate('category') }}"
                            src="{{ getStorageImages(path: $mostVisitedCategories[8]->icon_full_url, type:'category') }}">
                        <h4 class="title"><span>{{ $mostVisitedCategories[8]->name }}</span></h4>
                        <div class="cont">
                            <h6 class="text-white font-semibold text-uppercase">{{ $mostVisitedCategories[8]->name }}</h6>
                            <span>{{ $mostVisitedCategories[8]->product_count }} {{ translate('product') }}</span>
                            <i class="bi bi-eye-fill"></i>
                        </div>
                    </a>
                @endif
            </div>

            <div class="most-visited-category-wrapper align-items-center d-sm-none">
                <div class="categories-slider owl-theme owl-carousel">
                    @foreach ($mostVisitedCategories as $key => $item)

                        <a href="{{route('products',['category_id'=> $item->id,'data_from'=>'category','page'=>1])}}"
                            class="most-visited-item">
                            <img loading="lazy" alt="{{ translate('category') }}" src="{{ getStorageImages(path: $item->icon_full_url, type:'category') }}">
                            <h4 class="title"><span>{{ $item->name }}</span></h4>
                            <div class="cont">
                                <h6 class="text-white font-semibold text-uppercase">{{ $item->name }}</h6>
                                <span>{{ $item->product_count }} {{ translate('product') }}</span>
                                <i class="bi bi-eye-fill"></i>
                            </div>
                        </a>

                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
