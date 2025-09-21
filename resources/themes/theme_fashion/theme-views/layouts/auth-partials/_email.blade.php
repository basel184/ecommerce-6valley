<div class="form-group mb-2">
    <label class="form-label form--label" for="user_identity">
        {{ translate('email') }} / {{ translate('phone') }}
    </label>
    <input type="text" class="form-control auth-email-input"
           name="user_identity" id="user_identity" value="{{old('user_id')}}"
           placeholder="{{translate('enter_email_or_phone_number')}}" required>
</div>
