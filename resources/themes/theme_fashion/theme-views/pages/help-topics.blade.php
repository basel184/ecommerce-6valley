@extends('theme-views.layouts.app')

@section('title', translate('FAQ').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')

    <main class="main-content d-flex flex-column gap-3 pb-3">

        <div class="page_title_overlay py-5">
            <img loading="lazy" class="bg--img" alt="{{ translate('faq') }}"
                 src="{{getStorageImages(path: imagePathProcessing(imageData: (isset($pageTitleBanner['value']) ?json_decode($pageTitleBanner['value'])?->image : null),path: 'banner'),source: theme_asset('assets/img/page-title-bg.png'))}} ">
            <div class="container">
                <h1 class="text-center">{{ translate('FAQ') }}</h1>
            </div>
        </div>

        <div class="container">
            <div class="card my-5">
                <div class="accordion accordion-flush card-body p-lg-5 text-dark" id="accordionFlushExample">
                    @foreach ($helps as $key => $item)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="flush-heading{{ $item['id'] }}">
                                <button
                                    class="accordion-button {{ $key==0 ?'':'collapsed'}} text-dark fw-semibold btn_focus_zero_shadow"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#flush-collapse{{ $item['id'] }}"
                                    aria-expanded="false" aria-controls="flush-collapse{{ $item['id'] }}">
                                    {{ $item['question'] }}
                                </button>
                            </h2>
                            <div id="flush-collapse{{ $item['id'] }}"
                                 class="accordion-collapse collapse {{ $key == 0 ? 'show' : '' }}"
                                 aria-labelledby="flush-heading{{ $item['id'] }}"
                                 data-bs-parent="#accordionFlushExample">
                                <div class="accordion-body">
                                    {{ $item['answer'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </main>

@endsection
