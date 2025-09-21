@if($web_config['firebase_otp_verification'] && $web_config['firebase_otp_verification']['status'])
    <div id="recaptcha-container-manual-login" class="my-2"></div>
@elseif($web_config['recaptcha']['status'] == 1)
    <div id="recaptcha_element_customer_login" class="w-100 my-2" data-type="image"></div>
@else
    <div class="row py-2 my-2">
        <div class="col-6 pr-2">
            <input type="text" class="form-control border __h-40" name="default_recaptcha_id_customer_login" value=""
                   placeholder="{{translate('enter_captcha_value')}}" autocomplete="off">
        </div>
        <div class="col-6 input-icons mb-2 rounded">
            <span id="re_captcha_customer_login" class="d-flex align-items-center align-items-center">
                <img loading="lazy" src="{{ URL('/customer/auth/code/captcha/1?captcha_session_id=default_recaptcha_id_customer_login') }}" class="input-field rounded __h-40" id="customer_login_recaptcha_id" alt="{{ translate('captcha') }}">
                <i class="bi bi-arrow-repeat icon cursor-pointer p-2"></i>
            </span>
        </div>
    </div>
@endif
