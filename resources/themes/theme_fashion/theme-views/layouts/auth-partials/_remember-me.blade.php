@php($rememberId = rand(111, 999))
<div class="d-flex justify-content-between gap-1 mb-2">
    <label class="form-check m-0">
        <input type="checkbox" class="form-check-input" name="remember" id="remember{{ $rememberId }}" {{ old('remember') ? 'checked' : '' }}>
        <span class="form-check-label">{{ translate('remember_me') }}</span>
    </label>

    @if(isset($forgotPassword) && $forgotPassword)
        <a href="{{route('customer.auth.recover-password')}}" class="text-base text-capitalize">
            {{ translate('forgot_password') }} ?
        </a>
    @endif
</div>
