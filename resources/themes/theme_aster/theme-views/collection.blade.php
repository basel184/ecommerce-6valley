@extends('theme-views.layouts.app')

@section('title', $section->title)

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0 text-capitalize">{{ $section->title }}</h2>
            </div>
            <div class="row g-3">
                @foreach($products as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        @include('theme-views.partials._product-small-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </div>
    </main>
@endsection
