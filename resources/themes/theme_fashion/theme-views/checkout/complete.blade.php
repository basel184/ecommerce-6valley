@extends('theme-views.layouts.app')

@section('title', translate('order_complete').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')
<main class="main-content d-flex flex-column gap-3 py-3 mb-3">
    <div class="container">
        <div class="py-5">
            <div class="bg-contain bg-center bg-no-repeat success-bg py-5"
                data-bg-img="{{theme_asset('assets/img/bg/success-bg.png')}}">
                <div class="row justify-content-center mb-5">
                    <div class="col-xl-6 col-md-10">
                        <div class="text-center d-flex flex-column align-items-center gap-3">
                            <img loading="lazy" width="46" src="{{ theme_asset('assets/img/icons/check.png') }}" class="dark-support"
                                alt="{{translate('order_placed_successfully')}}">
                            <h3 class="text-capitalize">
                                @if(isset($isNewCustomerInSession) && $isNewCustomerInSession)
                                    {{ translate('Order_Placed_&_Account_Created_Successfully') }}!
                                @else
                                    {{ translate('Your_order_has_been_successfully_placed') }}!
                                @endif
                            </h3>
                            <p class="text-muted">
                                {{ translate('Thank_you_for_your_purchase!') }}
                                <br>
                                @if (isset($order_ids) && count($order_ids) > 0)
                                    {{ translate('order') }}
                                    <strong>
                                        @foreach ($order_ids as $key => $order)
                                            #{{ $order }} {{ !$loop->last ? ',':'' }}
                                        @endforeach
                                    </strong>
                                    {{ translate('details_will_be_sent_to_your_email_shortly.') }}
                                @else
                                    {{ translate('order_details_will_be_sent_to_your_email_shortly.') }}
                                @endif
                            </p>

                            <div class="d-flex flex-column gap-3 align-items-center">
                                <a href="{{route('home')}}" class="btn-base w-50 justify-content-center form-control max-w-340">
                                    {{ translate('Continue_Shopping') }}
                                </a>
                                
                                @if (isset($order_ids) && count($order_ids) > 0)
                                    @php($first_order_id = $order_ids[0])
                                    <a href="{{ route('tryoto.track', ['order_id' => $first_order_id]) }}" 
                                       target="_blank" 
                                       class="btn btn-outline-primary w-50 justify-content-center form-control max-w-340">
                                        <i class="bi bi-truck me-2"></i>
                                        {{ translate('Track_Your_Shipment') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
