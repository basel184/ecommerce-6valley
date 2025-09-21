@extends('theme-views.layouts.app')

@section('title', translate('discounted_products').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <h1 class="text-center mt-4 mb-4">المنتجات المخفضة</h1>
            <p class="text-center">يتم العمل على إصلاح هذه الصفحة. سيتم توجيهك قريباً إلى صفحة المنتجات المخفضة.</p>
            
            <div class="text-center mt-4">
                <a href="{{ route('home') }}" class="btn btn-primary">العودة للرئيسية</a>
            </div>
            
            <script>
                // Redirect to home page after 3 seconds
                setTimeout(function() {
                    window.location.href = "{{ route('home') }}";
                }, 3000);
            </script>
        </div>
    </div>
</div>
@endsection
