<?php
    $customerManualLogin = $web_config['customer_login_options']['manual_login'] ?? 0;
    $customerOTPLogin = $web_config['customer_login_options']['otp_login'] ?? 0;
    $customerSocialLogin = $web_config['customer_login_options']['social_login'] ?? 0;

    if (!$customerOTPLogin && $customerManualLogin && $customerSocialLogin) {
        $multiColumn = 1;
    } elseif ($customerOTPLogin && !$customerManualLogin && $customerSocialLogin) {
        $multiColumn = 1;
    } elseif ($customerOTPLogin && $customerManualLogin && !$customerSocialLogin) {
        $multiColumn = 1;
    } elseif ($customerOTPLogin && $customerManualLogin && $customerSocialLogin) {
        $multiColumn = 1;
    } else {
        $multiColumn = 0;
    }
?>


<div class="modal fade" id="SignInModal" tabindex="-1" aria-labelledby="SignInModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered {{ $multiColumn ? 'modal-lg' : '' }}">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 px-sm-5 py-5">
                <!-- Header Section with Gradient -->
                <div class="mb-5 text-center">
                    <h2 class="mb-3 fw-bold text-gradient-primary">{{ translate('sign_in') }}</h2>
                    <p class="text-muted-custom mb-0 fs-6">
                        {{ translate('sign_in_to_your_account.') }}
                    </p>
                </div>

                <div class="{{ $multiColumn ? 'row align-items-center or-sign-in-with-row' : ''}}">
                    <div class="col-12">
                        <!-- Modern Saudi Phone Login Form -->
                        <div class="login-form-container">
                            <form id="themePhoneRequestOtpForm" class="phone-login-form modern-form">
                                <div class="form-floating mb-4 position-relative">
                                    <div class="input-group input-group-modern" dir="rtl">
                                        <input type="text" name="phone" id="theme-saudi-phone" 
                                               class="form-control form-control-modern" 
                                               placeholder="{{ translate('5xxxxxxxx') }}" 
                                               minlength="9" maxlength="10" pattern="[0-9]*"
                                               style="text-align: right;">
                                        <span class="input-group-text input-group-modern-text">
                                            <div class="country-code-container">
                                                <img src="{{ asset('public/assets/front-end/img/flags/sa.png') }}" 
                                                     width="24" class="country-flag me-2" alt="Saudi Flag">
                                                <span class="country-code">+966</span>
                                            </div>
                                        </span>
                                    </div>
                                    <label for="theme-saudi-phone" class="form-label-modern">
                                        <i class="bi bi-phone me-2"></i>{{ translate('phone_number') }}
                                    </label>
                                    <div class="input-focus-effect"></div>
                                    <div class="invalid-feedback modern-feedback d-none" id="theme-phone-error"></div>
                                </div>

                                <div class="d-grid mb-4">
                                    <button type="submit" id="themeRequestOtpBtn" class="btn btn-modern-primary btn-lg">
                                        <span class="btn-content">
                                            <i class="fa fa-check-circle text-success fs-12"></i>
                                            <span class="btn-text">{{ translate('get_otp') }}</span>
                                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Modern OTP Verification Form -->
                        <div id="theme-phone-login-step-2" class="d-none otp-verification-container">
                            <div class="text-center mb-5">
                                <div class="otp-icon-container mb-3">
                                    <i class="fa fa-check text-success fs-1"></i>
                                </div>
                                <h2 class="mb-3 fw-bold text-gradient-success">{{ translate('verify_otp') }}</h2>
                                <p class="text-muted-custom">
                                    {{ translate('enter_the_verification_code_sent_to') }}
                                </p>
                                <p class="fw-bold text-primary" id="theme-display-phone"></p>
                            </div>

                            <form id="themePhoneVerifyOtpForm" class="phone-login-form modern-form">
                                <input type="hidden" id="theme-verify-phone-number" name="phone">
                                
                                <div class="mb-4">
                                    <label for="theme-otp-code" class="form-label-modern mb-3">
                                        <i class="bi bi-key me-2"></i>{{ translate('verification_code') }}
                                    </label>
                                    <div class="otp-input-container">
                                        <input type="text" name="otp" maxlength="4" pattern="[0-9]*" id="theme-otp-code" 
                                               class="form-control form-control-otp text-center" 
                                               placeholder="0000" autocomplete="one-time-code">
                                    </div>
                                    <div class="invalid-feedback modern-feedback d-none" id="theme-otp-error"></div>
                                </div>

                                <div class="d-grid mb-4">
                                    <button type="submit" id="themeVerifyOtpBtn" class="btn btn-modern-success btn-lg">
                                        <span class="btn-content">
                                            <i class="bi bi-check-circle me-2"></i>
                                            <span class="btn-text">{{ translate('verify_&_login') }}</span>
                                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                        </span>
                                    </button>
                                </div>
                                
                                <!-- Resend Timer -->
                                <div class="text-center mb-4">
                                    <div id="theme-resend-counter" class="resend-timer-container">
                                        <i class="bi bi-clock me-2"></i>
                                        <span class="timer-text">{{ translate('resend_code_in') }}</span>
                                        <span id="theme-countdown" class="countdown-number">60</span>
                                        <span class="timer-text">{{ translate('seconds') }}</span>
                                    </div>
                                    <button type="button" id="themeResendOtpBtn" class="btn btn-outline-primary btn-resend" disabled>
                                        <i class="bi bi-arrow-clockwise me-2"></i>
                                        {{ translate('resend_verification_code') }}
                                    </button>
                                </div>
                                
                                <!-- Back Button -->
                                <div class="text-center">
                                    <button type="button" id="theme-change-phone-btn" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-arrow-left me-2"></i>
                                        {{ translate('change_phone_number') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($multiColumn)
    <script>
        "use strict";
        function resizeFunc() {
            $('.or-sign-in-with').css('width', $('.or-sign-in-with-row').height())
        }
        $('#SignInModal').on('show.bs.modal', function () {
            resizeFunc();
            const resizeObserver = new ResizeObserver(resizeFunc);
            resizeObserver.observe(document.querySelector('.or-sign-in-with-row'));
        });
    </script>
@endif

@if($web_config['firebase_otp_verification'] && $web_config['firebase_otp_verification']['status'])
    <script type="text/javascript">
        "use strict";
        try {
            initializeFirebaseGoogleRecaptcha('recaptcha-container-otp', 'OTP Verification');
            initializeFirebaseGoogleRecaptcha('recaptcha-container-manual-login', 'Manual Login');
            initializeFirebaseGoogleRecaptcha('recaptcha-container-verify-token', 'Token Verification');
        } catch (e) {
            console.log(e)
        }
    </script>
@elseif($web_config['recaptcha']['status'] == 1)
    <script type="text/javascript">
        "use strict";
        var onloadCallbackCustomerLogin = function () {
            var login_id = grecaptcha.render('recaptcha_element_customer_login', {
                'sitekey': '{{ getWebConfig(name: 'recaptcha')['site_key'] }}'
            });
            $('#recaptcha_element_customer_login').attr('data-login-id', login_id);
        };
    </script>
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallbackCustomerLogin&render=explicit" async
            defer></script>

@else
    <script type="text/javascript">
        "use strict";
        $('#re_captcha_customer_login').on('click', function(){
            var re_captcha = "{{ URL('/customer/auth/code/captcha') }}";
            re_captcha = re_captcha + "/" + Math.random()+'?captcha_session_id=default_recaptcha_id_customer_login';
            document.getElementById('customer_login_recaptcha_id').src = re_captcha;
        })
    </script>
@endif

<script>
    "use strict";

    try {
        initializePhoneInput(".phone-input-with-country-picker-login", ".country-picker-phone-number-login");
    } catch (e) {

    }

    $('.customer-centralize-login-form').on('submit', async function (event) {
        event.preventDefault();

        var recaptchaContainer = document.getElementById('recaptcha_element_customer_login');
        if (recaptchaContainer && recaptchaContainer.innerHTML.trim()?.toString() !== "") {
            var response = grecaptcha.getResponse($('#recaptcha_element_customer_login').attr('data-login-id'));
            if (response.length === 0) {
                toastr.error($("#message-please-check-recaptcha").data("text"));
                return false;
            }
        }

        $.ajax({
            url: $(this).attr('action'),
            method: $(this).attr('method'),
            data: $(this).serialize(),
            beforeSend: function () {
                $("#loading").addClass("d-grid");
            },
            success: function (response) {
                responseManager(response)
            },
            complete: function () {
                $("#loading").removeClass("d-grid");
                const widgetId = $('#recaptcha_element_customer_login').attr('data-login-id');
                if (widgetId) {
                    grecaptcha.reset(widgetId);
                }
            },
        });
    });

    $('.otp-login-btn').on('click', function () {
        $(this).addClass('d-none');
        $('.manual-login-btn').removeClass('d-none');
        $('.manual-login-items').addClass('d-none');
        $('.otp-login-items').removeClass('d-none');
        $('.phone-input-with-country-picker-login').attr('required', true);
        $('.auth-email-input').attr('required', false);
        $('.auth-password-input').attr('required', false);
        $('.auth-login-type-input').val('otp-login');
    })

    $('.manual-login-btn').on('click', function () {
        $(this).addClass('d-none');
        $('.otp-login-btn').removeClass('d-none');
        $('.otp-login-items').addClass('d-none');
        $('.manual-login-items').removeClass('d-none');
        $('.phone-input-with-country-picker-login').attr('required', false);
        $('.auth-email-input').attr('required', true);
        $('.auth-password-input').attr('required', true);
        $('.auth-login-type-input').val('manual-login');
    })

    // Saudi Phone Login Functions
    let otpTimer;
    const countdownTime = 60;
    
    // Format phone number as Saudi format (remove leading zeros, spaces)
    function formatSaudiPhone(phone) {
        phone = phone.replace(/\D/g, ''); // Remove non-digits
        
        // Remove leading zero if present
        if (phone.startsWith('0')) {
            phone = phone.substring(1);
        }
        
        return phone;
    }
    
    // Start countdown timer for OTP resend
    function startCountdown() {
        let seconds = countdownTime;
        $('#theme-countdown').text(seconds);
        $('#themeResendOtpBtn').prop('disabled', true);
        $('#theme-resend-counter').removeClass('d-none');
        
        clearInterval(otpTimer);
        otpTimer = setInterval(function() {
            seconds--;
            $('#theme-countdown').text(seconds);
            
            if (seconds <= 0) {
                clearInterval(otpTimer);
                $('#themeResendOtpBtn').prop('disabled', false);
                $('#theme-resend-counter').addClass('d-none');
            }
        }, 1000);
    }
    
    // Request OTP form submit
    $('#themePhoneRequestOtpForm').on('submit', function(e) {
        e.preventDefault();
        
        const phone = formatSaudiPhone($('#theme-saudi-phone').val());
        
        if (!phone || phone.length < 9) {
            $('#theme-phone-error').text('{{ translate("please_enter_a_valid_saudi_phone_number") }}').removeClass('d-none');
            return false;
        }
        
        $('#themeRequestOtpBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ translate("please_wait") }}');
        $('#theme-phone-error').addClass('d-none');
        
        $.ajax({
            url: '{{ route("phone-login.request-otp") }}',
            type: 'POST',
            data: {
                phone: phone,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Hide phone input form
                    $('#themePhoneRequestOtpForm').addClass('d-none');
                    // Show OTP verification screen
                    $('#theme-phone-login-step-2').removeClass('d-none');
                    
                    // Set phone number for verification
                    $('#theme-verify-phone-number').val(phone);
                    $('#theme-display-phone').text('966'+phone);
                    
                    // Start countdown
                    startCountdown();
                    
                    // Clear OTP field
                    $('#theme-otp-code').val('');
                } else {
                    $('#theme-phone-error').text(response.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                let errorMessage = '{{ translate("an_error_occurred_please_try_again") }}';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                $('#theme-phone-error').text(errorMessage).removeClass('d-none');
            },
            complete: function() {
                $('#themeRequestOtpBtn').prop('disabled', false).text('{{ translate("get_otp") }}');
            }
        });
    });
    
    // Verify OTP form submit
    $('#themePhoneVerifyOtpForm').on('submit', function(e) {
        e.preventDefault();
        
        const phone = $('#theme-verify-phone-number').val();
        const otp = $('#theme-otp-code').val();
        
                    if (!otp || otp.length !== 4) {
                            $('#theme-otp-error').text('{{ translate("please_enter_the_4_digit_verification_code") }}').removeClass('d-none');
            return false;
        }
        
        $('#themeVerifyOtpBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ translate("verifying") }}');
        $('#theme-otp-error').addClass('d-none');
        
        $.ajax({
            url: '{{ route("phone-login.verify-otp") }}',
            type: 'POST',
            data: {
                phone: phone,
                otp: otp,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    toastr.success('{{ translate("login_successful") }}');
                    
                    // Redirect user - force page reload to update session
                    setTimeout(function() {
                        window.location.href = response.redirect || '/';
                    }, 1000);
                } else {
                    $('#theme-otp-error').text(response.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                let errorMessage = '{{ translate("invalid_or_expired_otp_code") }}';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                $('#theme-otp-error').text(errorMessage).removeClass('d-none');
            },
            complete: function() {
                $('#themeVerifyOtpBtn').prop('disabled', false).text('{{ translate("verify_&_login") }}');
            }
        });
    });
    
    // Resend OTP button click
    $('#themeResendOtpBtn').on('click', function() {
        const phone = $('#theme-verify-phone-number').val();
        
        $('#themeResendOtpBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ translate("sending") }}');
        
        $.ajax({
            url: '{{ route("phone-login.resend-otp") }}',
            type: 'POST',
            data: {
                phone: phone,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Start countdown again
                    startCountdown();
                    
                    // Show success message
                    toastr.success('{{ translate("verification_code_resent_successfully") }}');
                } else {
                    if (response.wait_seconds) {
                        toastr.warning('{{ translate("please_wait_before_requesting_another_code") }}');
                    } else {
                        toastr.error(response.message);
                    }
                }
            },
            error: function() {
                toastr.error('{{ translate("failed_to_resend_verification_code") }}');
            },
            complete: function() {
                $('#themeResendOtpBtn').prop('disabled', false).text('{{ translate("resend_verification_code") }}');
            }
        });
    });
    
    // Change phone number button
    $('#theme-change-phone-btn').on('click', function() {
        $('#theme-phone-login-step-2').addClass('d-none');
        $('#themePhoneRequestOtpForm').removeClass('d-none');
        clearInterval(otpTimer);
    });
    
    // Saudi phone number input formatting and validation
    $('#theme-saudi-phone').on('input', function() {
        let input = $(this).val().replace(/\D/g, '');
        
        // Remove leading zero if entered
        if (input.startsWith('0')) {
            input = input.substring(1);
        }
        
        // Limit to 9 digits (Saudi mobile without country code)
        if (input.length > 9) {
            input = input.slice(0, 9);
        }
        
        $(this).val(input);
    });

</script>

<style>
    /* Very Simple Modal Design */
    .modal-content {
        border: none;
        border-radius: 8px;
        background: linear-gradient(70deg, rgba(234,220,210,1) 0%, rgba(179,148,129,1) 100%);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .modal-header {
        background: transparent;
        border: none;
    }

    
    /* Simple Logo */
    .logo-container {
        padding: 10px;
        background: rgba(255,255,255,0.2);
    }
    
    /* Text Colors */
    .text-gradient-primary {
        color: #8B4513;
        font-weight: bold;
    }
    
    .text-gradient-success {
        color: #228B22;
        font-weight: bold;
    }
    
    .text-muted-custom {
        color: rgba(139,69,19,0.7) !important;
    }
    
    /* Simple Form Container */
    .login-form-container,
    .otp-verification-container {
        background: rgba(255,255,255,0.4);
        padding: 20px;
        border: 1px solid rgba(255,255,255,0.5);
    }
    
    /* Simple Input Fields */
    .input-group-modern {
        background: white;
        border: 1px solid rgba(179,148,129,0.4);
        overflow: hidden;
    }
    
    .input-group-modern:focus-within {
        border-color: #8B4513;
    }
    
    .form-control-modern {
        border: none;
        background: transparent;
        font-size: 16px;
        color: #8B4513;
        padding: 12px 15px;
    }
    
    .form-control-modern:focus {
        border: none;
        box-shadow: none;
    }
    
    .input-group-modern-text {
        background: rgba(179,148,129,0.9);
        border: none;
        padding: 12px 15px;
        color: white;
        font-weight: 600;
    }
    
    .country-code-container {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Simple Form Labels */
    .form-label-modern {
        position: absolute;
        top: -8px;
        right: 15px;
        background: rgba(234,220,210,0.9);
        color: #8B4513;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 600;
        z-index: -1;
    }
    
    /* Simple OTP Input */
    .form-control-otp {
        background: white;
        border: 1px solid rgba(179,148,129,0.4);
        font-size: 22px;
        font-weight: bold;
        letter-spacing: 4px;
        padding: 12px;
        color: #8B4513;
    }
    
    .form-control-otp:focus {
        border-color: #8B4513;
        box-shadow: 0 0 0 0.2rem rgba(139,69,19,0.25);
    }
    
    /* Simple Buttons */
    .btn-modern-primary {
        background: linear-gradient(45deg, rgba(179,148,129,1), rgba(160,82,45,1));
        border: none;
        padding: 12px 20px;
        font-size: 16px;
        font-weight: 600;
        color: white;
    }
    
    .btn-modern-primary:hover {
        background: linear-gradient(45deg, rgba(160,82,45,1), rgba(139,69,19,1));
        color: white;
    }
    
    .btn-modern-success {
        background: linear-gradient(45deg, #228B22, #32CD32);
        border: none;
        padding: 12px 20px;
        font-size: 16px;
        font-weight: 600;
        color: white;
    }
    
    .btn-modern-success:hover {
        background: linear-gradient(45deg, #32CD32, #228B22);
        color: white;
    }
    
    /* Simple OTP Icon */
    .otp-icon-container {
        width: 60px;
        height: 60px;
        margin: 0 auto;
        background: rgba(255,255,255,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Simple Timer */
    .resend-timer-container {
        background: rgba(255,255,255,0.3);
        padding: 8px 12px;
        margin-bottom: 15px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #8B4513;
        font-weight: 500;
    }
    
    .countdown-number {
        background: rgba(179,148,129,1);
        color: white;
        padding: 2px 8px;
        font-weight: bold;
        font-size: 12px;
    }
    
    .btn-resend {
        padding: 8px 16px;
        background: rgba(255,255,255,0.3);
        border: 1px solid rgba(179,148,129,0.5);
        color: #8B4513;
    }
    
    .btn-resend:hover:not(:disabled) {
        background: rgba(179,148,129,0.2);
        color: #8B4513;
    }
    
    /* Simple Error Messages */
    .modern-feedback {
        background: rgba(220,53,69,0.1);
        border: 1px solid rgba(220,53,69,0.3);
        padding: 8px 12px;
        margin-top: 8px;
        color: #dc3545;
    }
    
    /* RTL Support */
    [dir="rtl"] .form-label-modern {
        right: auto;
        left: 15px;
    }
    
    /* Mobile */
    @media (max-width: 768px) {
        .login-form-container,
        .otp-verification-container {
            padding: 15px;
        }
        
        .form-control-modern {
            font-size: 14px;
            padding: 10px 12px;
        }
        
        .form-control-otp {
            font-size: 18px;
            letter-spacing: 3px;
        }
    }
</style>
