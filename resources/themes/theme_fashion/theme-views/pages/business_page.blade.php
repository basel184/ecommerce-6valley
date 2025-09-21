@extends('theme-views.layouts.app')

@section('title', $businessPage?->title.' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-4">
        <div class="container">
            <div class="page_title_overlay py-5 rounded-10 overflow-hidden">
                <img loading="lazy" class="bg--img" alt="{{ $businessPage?->title }}"
                     src="{{ getStorageImages(path: $businessPage?->banner_full_url, type: 'business-page') }}">
                <div class="container">
                    <h1 class="text-center text-capitalize">{{ $businessPage?->title }}</h1>
                </div>
            </div>
            <div class="card my-4">
                <div class="card-body p-lg-5 text-dark page-paragraph">
                    {!! $businessPage?->description !!}
                </div>
            </div>
        </div>
    </main>
@endsection
