@extends('themes.default.layouts.front-end.app')

@section('title', $section->title)

@section('content')
    <section class="container pt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0 text-capitalize">{{ $section->title }}</h2>
        </div>
        <div class="row g-3">
            @foreach($products as $product)
                <div class="col-6 col-md-4 col-lg-3">
                    @include('web-views.partials._product-card-1',['product'=>$product, 'decimal_point_settings'=> getWebConfig('decimal_point_settings')])
                </div>
            @endforeach
        </div>
        <div class="mt-4">
            {{ $products->links() }}
        </div>
    </section>
@endsection
