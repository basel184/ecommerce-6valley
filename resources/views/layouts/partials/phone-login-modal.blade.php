{{-- Phone Login Modal --}}
<div class="modal fade" id="phoneLoginModal" tabindex="-1" aria-labelledby="phoneLoginModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close btn-close-white bg-light rounded-circle p-2" 
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-5 pb-5">
                <!-- Logo Section -->
                <div class="text-center mb-4">
                    <div class="bg-gradient-primary rounded-circle d-inline-flex p-3 mb-3">
                        <img loading="lazy" alt="{{ translate('logo') }}" class="h-50px"
                             src="{{ getStorageImages(path: $web_config['web_logo'], type:'logo') }}">
                    </div>
                    <h3 class="fw-bold text-dark mb-2">{{ translate('welcome_back') }}</h3>
                    <p class="text-muted mb-0">{{ translate('secure_phone_verification') }}</p>
                </div>

                <!-- Phone Login Form (Step 1: Phone Entry) -->
                <div id="phone-login-step-1">
                    <div class="card border-0 bg-light rounded-3 p-4 mb-4">
                        <div class="text-center mb-3">
                            <div class="d-inline-flex align-items-center justify-content-center bg-success rounded-circle" 
                                 style="width: 50px; height: 50px;">
                                <i class="fas fa-mobile-alt text-white fs-5"></i>
                            </div>
                        </div>
                        <h4 class="text-center fw-bold mb-2">{{ translate('phone_verification') }}</h4>
                        <p class="text-center text-muted small">
                            {{ translate('enter_your_phone_number_to_login_or_register') }}
                        </p>
                    </div>

                    <form id="phoneRequestOtpForm" class="phone-login-form">
                        <div class="form-group mb-4">
                            <label for="saudi-phone" class="form-label fw-semibold text-dark mb-3">
                                <i class="fas fa-phone me-2 text-primary"></i>
                                {{ translate('phone_number') }}
                            </label>
                            <div class="input-group input-group-lg border rounded-3 overflow-hidden shadow-sm">
                                <span class="input-group-text bg-primary text-white border-0 px-4">
                                    <img src="{{ asset('public/assets/frontend/img/flags/sa.png') }}" 
                                         width="24" class="me-2 rounded" alt="Saudi Flag">
                                    <span class="fw-bold">+966</span>
                                </span>
                                <input type="text" name="phone" id="saudi-phone" 
                                       class="form-control border-0 px-4 fs-5" 
                                       placeholder="{{ translate('5xxxxxxxx') }}" 
                                       minlength="9" maxlength="10" pattern="[0-9]*"
                                       style="letter-spacing: 2px;">
                            </div>
                            <div class="invalid-feedback d-none mt-2" id="phone-error">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                <span></span>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mb-4">
                            <button type="submit" id="requestOtpBtn" 
                                    class="btn btn-primary btn-lg rounded-3 py-3 fw-bold position-relative overflow-hidden">
                                <span class="btn-text">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    {{ translate('send_verification_code') }}
                                </span>
                                <div class="spinner-border spinner-border-sm d-none" role="status">
                                    <span class="visually-hidden">{{ translate('loading') }}</span>
                                </div>
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1 text-success"></i>
                                {{ translate('your_information_is_secure') }}
                            </small>
                        </div>
                    </form>
                </div>

                <!-- Phone Login Form (Step 2: OTP Verification) -->
                <div id="phone-login-step-2" class="d-none">
                    <div class="card border-0 bg-light rounded-3 p-4 mb-4">
                        <div class="text-center mb-3">
                            <div class="d-inline-flex align-items-center justify-content-center bg-warning rounded-circle" 
                                 style="width: 50px; height: 50px;">
                                <i class="fas fa-key text-white fs-5"></i>
                            </div>
                        </div>
                        <h4 class="text-center fw-bold mb-2">{{ translate('verify_your_phone') }}</h4>
                        <p class="text-center text-muted small">
                            {{ translate('enter_the_verification_code_sent_to') }} 
                            <strong class="text-primary" id="display-phone"></strong>
                        </p>
                    </div>

                    <form id="phoneVerifyOtpForm" class="phone-login-form">
                        <input type="hidden" id="verify-phone-number" name="phone">
                        
                        <div class="form-group mb-4">
                            <label for="otp-code" class="form-label fw-semibold text-dark mb-3">
                                <i class="fas fa-lock me-2 text-warning"></i>
                                {{ translate('verification_code') }}
                            </label>
                            <div class="d-flex justify-content-center">
                                <input type="text" name="otp" maxlength="4" pattern="[0-9]*" id="otp-code" 
                                       class="form-control form-control-lg text-center border-2 rounded-3 shadow-sm" 
                                       placeholder="0000"
                                       style="font-size: 2rem; letter-spacing: 0.5rem; width: 200px; font-weight: bold;">
                            </div>
                            <div class="invalid-feedback d-none mt-2 text-center" id="otp-error">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                <span></span>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mb-4">
                            <button type="submit" id="verifyOtpBtn" 
                                    class="btn btn-success btn-lg rounded-3 py-3 fw-bold">
                                <i class="fas fa-check-circle me-2"></i>
                                {{ translate('verify_and_continue') }}
                            </button>
                        </div>
                        
                        <!-- Resend Section -->
                        <div class="text-center mb-4">
                            <div id="resend-counter" class="mb-3">
                                <div class="d-inline-flex align-items-center bg-light rounded-pill px-3 py-2">
                                    <i class="fas fa-clock text-primary me-2"></i>
                                    <span class="text-muted small">
                                        {{ translate('resend_code_in') }} 
                                        <strong class="text-primary" id="countdown">60</strong> 
                                        {{ translate('seconds') }}
                                    </span>
                                </div>
                            </div>
                            <button type="button" id="resendOtpBtn" 
                                    class="btn btn-outline-primary rounded-pill px-4" disabled>
                                <i class="fas fa-redo me-2"></i>
                                {{ translate('resend_code') }}
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <button type="button" id="change-phone-btn" 
                                    class="btn btn-link text-decoration-none">
                                <i class="fas fa-edit me-1"></i>
                                {{ translate('change_phone_number') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Variables
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
            $('#countdown').text(seconds);
            $('#resendOtpBtn').prop('disabled', true);
            $('#resend-counter').removeClass('d-none');
            
            clearInterval(otpTimer);
            otpTimer = setInterval(function() {
                seconds--;
                $('#countdown').text(seconds);
                
                if (seconds <= 0) {
                    clearInterval(otpTimer);
                    $('#resendOtpBtn').prop('disabled', false);
                    $('#resend-counter').addClass('d-none');
                }
            }, 1000);
        }
        
        // Request OTP form submit
        $('#phoneRequestOtpForm').on('submit', function(e) {
            e.preventDefault();
            
            const phone = formatSaudiPhone($('#saudi-phone').val());
            
            if (!phone || phone.length < 9) {
                $('#phone-error').removeClass('d-none').find('span').text('{{ translate("please_enter_a_valid_saudi_phone_number") }}');
                $('#saudi-phone').addClass('is-invalid');
                return false;
            }
            
            // Improved button animation
            const $btn = $('#requestOtpBtn');
            $btn.prop('disabled', true);
            $btn.find('.btn-text').addClass('d-none');
            $btn.find('.spinner-border').removeClass('d-none');
            $('#phone-error').addClass('d-none');
            $('#saudi-phone').removeClass('is-invalid');
            
            $.ajax({
                url: '{{ route("phone-login.request-otp") }}',
                type: 'POST',
                data: {
                    phone: phone,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        // Smooth transition with animation
                        $('#phone-login-step-1').fadeOut(300, function() {
                            $('#phone-login-step-2').fadeIn(300);
                        });
                        
                        // Set phone number for verification
                        $('#verify-phone-number').val(phone);
                        $('#display-phone').text('+966 ' + phone);
                        
                        // Start countdown
                        startCountdown();
                        
                        // Clear OTP field and focus
                        $('#otp-code').val('').focus();
                        
                        // Success notification
                        toastr.success('{{ translate("verification_code_sent_successfully") }}');
                    } else {
                        $('#phone-error').removeClass('d-none').find('span').text(response.message);
                        $('#saudi-phone').addClass('is-invalid');
                    }
                },
                error: function(xhr) {
                    let errorMessage = '{{ translate("an_error_occurred_please_try_again") }}';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    $('#phone-error').removeClass('d-none').find('span').text(errorMessage);
                    $('#saudi-phone').addClass('is-invalid');
                },
                complete: function() {
                    const $btn = $('#requestOtpBtn');
                    $btn.prop('disabled', false);
                    $btn.find('.spinner-border').addClass('d-none');
                    $btn.find('.btn-text').removeClass('d-none');
                }
            });
        });
        
        // Verify OTP form submit
        $('#phoneVerifyOtpForm').on('submit', function(e) {
            e.preventDefault();
            
            const phone = $('#verify-phone-number').val();
            const otp = $('#otp-code').val();
            
            if (!otp || otp.length !== 4) {
                $('#otp-error').text('{{ translate("please_enter_the_4_digit_verification_code") }}').removeClass('d-none');
                return false;
            }
            
            $('#verifyOtpBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ translate("verifying") }}');
            $('#otp-error').addClass('d-none');
            
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
                        $('#otp-error').text(response.message).removeClass('d-none');
                    }
                },
                error: function(xhr) {
                    let errorMessage = '{{ translate("invalid_or_expired_otp_code") }}';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    $('#otp-error').text(errorMessage).removeClass('d-none');
                },
                complete: function() {
                    $('#verifyOtpBtn').prop('disabled', false).text('{{ translate("verify_&_login") }}');
                }
            });
        });
        
        // Resend OTP button click
        $('#resendOtpBtn').on('click', function() {
            const phone = $('#verify-phone-number').val();
            
            $('#resendOtpBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ translate("sending") }}');
            
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
                    $('#resendOtpBtn').prop('disabled', false).text('{{ translate("resend_verification_code") }}');
                }
            });
        });
        
        // Change phone number button
        $('#change-phone-btn').on('click', function() {
            $('#phone-login-step-2').addClass('d-none');
            $('#phone-login-step-1').removeClass('d-none');
            clearInterval(otpTimer);
        });
        
        // Reset the form when modal is closed
        $('#phoneLoginModal').on('hidden.bs.modal', function () {
            $('#phone-login-step-2').addClass('d-none');
            $('#phone-login-step-1').removeClass('d-none');
            $('#saudi-phone').val('');
            $('#otp-code').val('');
            $('#phone-error').addClass('d-none');
            $('#otp-error').addClass('d-none');
            clearInterval(otpTimer);
        });
        
        // Saudi phone number input formatting and validation
        $('#saudi-phone').on('input', function() {
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
    });
</script>
