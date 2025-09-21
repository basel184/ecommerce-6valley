"use strict";

$(document).ready(function () {
    $("#confirm_password2").on("keyup", function () {
        checkPasswordMatch();
    });

    initializePhoneInput('.profile-phone-with-country-picker', '.profile-phone-country-picker-hidden');
});

$(".profile-pic-upload").on('change', function () {
    $('.remove-img').removeClass('d-none')
});

$("#password2").on("keyup", function () {
    if ($( "#confirm_password2" ).val() != '') {
        checkPasswordMatch();
    }
});

function checkPasswordMatch() {
    let userProfileEditCheckPasswordMessage = $('#user-profile-edit-check-password-message');
    var password = $("#password2").val();
    var confirmPassword = $("#confirm_password2").val();
    $("#message").removeAttr("style");
    $("#message").html("");
    if (confirmPassword == "") {
        $("#message").attr("style", "color:black");
        $("#message").html(userProfileEditCheckPasswordMessage?.data('retype'));
    } else if (password == "") {
        $("#message").removeAttr("style");
        $("#message").html("");
    } else if (password != confirmPassword) {
        $("#message").html(userProfileEditCheckPasswordMessage?.data('password-not-matched'));
        $("#message").attr("style", "color:red");
    } else if (confirmPassword.length <= 7) {
        $("#message").html(userProfileEditCheckPasswordMessage?.data('password-length'));
        $("#message").attr("style", "color:red");
    } else {
        $("#message").html(userProfileEditCheckPasswordMessage?.data('password-matched'));
        $("#message").attr("style", "color:green");
    }
}

$(".reset_button").on('click', function () {
    let userProfileEditResetForm = $('#user-profile-edit-reset-form')
    $('.thumb').empty().html(`<img src="${userProfileEditResetForm.data('placeholder')}" alt="${userProfileEditResetForm.data('alt')}">`);
    $('.remove-img').addClass('d-none')
})
