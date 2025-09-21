@extends('theme-views.layouts.app')

@section('title', translate('edit_my_profile').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')
    <section class="user-profile-section section-gap pt-0">
        <div class="container">
            @include('theme-views.partials._profile-aside')
            <div class="tab-content">

                <div class="tab-pane fade show active __chat-area">
                    <div class="personal-details mb-4">
                        <div
                            class="d-flex flex-wrap justify-content-between align-items-center column-gap-4 row-gap-2 mb-4 ">
                            <h4 class="subtitle m-0 text-capitalize">{{ translate('edit_personal_details') }}</h4>
                            <a href="{{route('user-profile')}}"
                               class="cmn-btn __btn-outline rounded-full align-content-center">
                                <i class="bi bi-chevron-left "></i>{{ translate('go_back') }}
                            </a>
                        </div>
                        <div>
                            <div class="">
                                <form action="{{route('user-update')}}" method="post" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row g-4 ">
                                        <div class="col-sm-6 text-capitalize">
                                            <label class="form--label mb-2"
                                                   for="f-name">{{ translate('first_name') }}</label>
                                            <input type="text" value="{{$customerDetail['f_name']}}" name="f_name"
                                                   class="form-control"
                                                   placeholder="{{translate('ex')}} : {{translate('Jhone')}}">
                                        </div>
                                        <div class="col-sm-6 text-capitalize">
                                            <label class="form--label mb-2"
                                                   for="l-name">{{ translate('last_name') }}</label>
                                            <input type="text" id="l-name" value="{{$customerDetail['l_name']}}"
                                                   name="l_name" class="form-control"
                                                   placeholder="{{translate('ex')}} : {{translate('Doe')}}">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form--label mb-2" for="email">{{ translate('phone') }}</label>
                                            <div class="position-relative d-flex align-items-center">
                                                <input type="tel" id="phone" class="form-control profile-phone-with-country-picker"
                                                       value="{{$customerDetail['phone']}}"
                                                       placeholder="{{translate('enter_phone_number')}}" required
                                                    {{ $customerDetail['is_phone_verified'] ? 'disabled' : '' }}>
                                                <input type="hidden" name="phone" value="{{$customerDetail['phone']}}" class="profile-phone-country-picker-hidden">

                                                @if($customerDetail['phone'] && getLoginConfig(key: 'phone_verification'))
                                                    @if($customerDetail['is_phone_verified'])
                                                        <span class="position-absolute inset-inline-end-10px cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ translate('Your_phone_is_verified') }}">
                                                                <img width="16"
                                                                     src="{{theme_asset('assets/img/icons/verified.svg')}}"
                                                                     class="dark-support" alt="">
                                                            </span>
                                                    @else
                                                        <span class="position-absolute inset-inline-end-10px cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                                                              title="{{ translate('Phone_not_verified.') }} {{ translate('Please_verify_through_the_user_app') }}">
                                                                <img width="16"
                                                                     src="{{ theme_asset('assets/img/icons/verified-error.svg') }}"
                                                                     class="dark-support" alt="">
                                                            </span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form--label mb-2" for="email">{{ translate('email') }}</label>
                                            <div class="position-relative d-flex align-items-center">
                                                <input type="text" id="email" class="form-control"
                                                       value="{{$customerDetail['email']}}"
                                                       placeholder="{{translate('enter_email_address')}}" name="email">
                                                @if($customerDetail['email'] && getLoginConfig(key: 'email_verification'))
                                                    @if($customerDetail['is_email_verified'])
                                                        <span class="position-absolute inset-inline-end-10px cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ translate('Your_email_is_verified') }}">
                                                            <img width="16"
                                                                 src="{{theme_asset('assets/img/icons/verified.svg')}}"
                                                                 class="dark-support" alt="">
                                                            </span>
                                                    @else
                                                        <span class="position-absolute inset-inline-end-10px cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                                                              title="{{ translate('Email_not_verified.') }} {{ translate('Please_verify_through_the_user_app.') }}">
                                                            <img width="16"
                                                                 src="{{theme_asset('assets/img/icons/verified-error.svg')}}"
                                                                 class="dark-support" alt="">
                                                            </span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form--label mb-2">{{translate('password')}}</label>
                                            <div class="position-relative">
                                                <input type="password" minlength="6" id="password2" class="form-control"
                                                       name="password" placeholder="{{translate('ex:_7+_characters')}}">
                                                <div class="js-password-toggle"><i class="bi bi-eye-fill"></i></div>
                                            </div>
                                            <span class="text-danger mx-1 password-error"></span>
                                        </div>
                                        <div class="col-sm-6 position-relative text-capitalize">
                                            <label class="form--label mb-2">{{translate('confirm_password')}}</label>
                                            <div class="position-relative">
                                                <input type="password" minlength="6" id="confirm_password2"
                                                       name="confirm_password" class="form-control"
                                                       placeholder="{{translate('ex:_7+_characters')}}">
                                                <div class="js-password-toggle"><i class="bi bi-eye-fill"></i></div>
                                            </div>
                                            <div id='message'></div>
                                        </div>
                                        <div class="d-flex flex-wrap align-items-center">
                                            <div class="upload-wrapper">
                                                <div class="thumb">
                                                    <img loading="lazy" src="{{theme_asset('assets/img/upload-img.png')}}" alt="{{ translate('upload') }}">
                                                </div>
                                                <div class="remove-img">
                                                    <i class="bi bi-x-lg"></i>
                                                </div>
                                                <label>
                                                    <input type="file" class="profile-pic-upload" name="image"
                                                           hidden="">
                                                </label>
                                            </div>
                                            <div class="ps-3 ps-sm-4 text-text-2 w-0 flex-grow-1">
                                                <small>{{translate('image_ration')}} {{ translate('1') }}
                                                    :{{ translate('1') }}</small>
                                                <small class="font-italic">
                                                    {{translate('NB')}}:{{translate('image_size_must_be_within_2MB')}}
                                                    <br>
                                                    {{translate('image_format_jpg_jpeg_png')}}
                                                </small>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div
                                                class="d-flex flex-column flex-sm-row jusitfy-content-between align-items-center gap-3 ">
                                                <button type="reset"
                                                        class="btn btn-base __btn-outline form-control reset_button min-w-180 ms-auto go-step-3">
                                                    {{translate('reset')}}
                                                </button>
                                                <button type="submit"
                                                        class="btn btn-base  form-control  min-w-180 ms-auto go-step-2 text-capitalize">
                                                    {{translate('update_profile')}}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
    <span id="user-profile-edit-check-password-message"
          data-retype="{{translate('please_retype_password')}}"
          data-password-not-matched="{{translate('password_does_not_match!')}}"
          data-password-length="{{translate('password_must_be_8_characters')}}"
          data-password-matched="{{translate('password_matched')}}"

    ></span>
    <span id="user-profile-edit-reset-form" data-placeholder="{{theme_asset('assets/img/upload-img.png')}}" data-alt="{{ translate('upload') }}"></span>
@endsection

@push('script')
    <script src="{{ theme_asset('assets/js/user-profile-edit.js') }}"></script>
    <script src="{{theme_asset('assets/js/password-strength.js')}}"></script>
@endpush

