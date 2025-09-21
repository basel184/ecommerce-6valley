@extends('layouts.front-end.app')

@section('title', 'MyFatoorah Payment')

@section('content')
    <div class="container pb-5 mb-2 mb-md-4 rtl" style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
        <div class="row">
            <div class="col-md-12 mb-5 pt-5">
                <div class="feature_header" style="background: #dcdcdc;line-height: 1px">
                    <span>{{ translate('MyFatoorah_payment')}}</span>
                </div>
            </div>
            <section class="col-lg-8">
                <div class="checkout_details">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mb-4">{{ translate('payment_via_MyFatoorah')}}</h4>
                            
                            <form action="{{ route('myfatoorah.process') }}" method="POST" class="needs-validation">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="customerName">{{ translate('name') }}</label>
                                        <input type="text" class="form-control" name="customer_name" id="customerName" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="customerEmail">{{ translate('email') }}</label>
                                        <input type="email" class="form-control" name="customer_email" id="customerEmail" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="customerPhone">{{ translate('phone') }}</label>
                                        <input type="text" class="form-control" name="customer_phone" id="customerPhone" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="amount">{{ translate('amount') }}</label>
                                        <input type="number" class="form-control" name="amount" id="amount" step="0.01" min="1" required>
                                    </div>
                                </div>

                                <input type="hidden" name="order_id" value="{{ session('cart_group_id') ?? rand(100000, 999999) }}">

                                <button class="btn btn--primary btn-block" type="submit">{{ translate('pay_now') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
