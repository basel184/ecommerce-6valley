"use strict";

$(document).ready(function () {
    let total = $(".checkout_details .click-if-alone").length;
    if (Number.parseInt(total) < 2) {
        $(".click-if-alone").click();
        $(".checkout_details").html(
            `<h1>` +
                $("#text-redirecting-to-the-payment").data("text") +
                `......</h1>`
        );
    }
});

setTimeout(function () {
    $(".stripe-button-el").hide();
    $(".razorpay-payment-button").hide();
}, 10);

$(".digital_payment_btn").on("click", function () {
    $("#digital_payment").slideToggle("slow");
});

$("#pay_offline_method").on("change", function () {
    pay_offline_method_field(this.value);
});

function pay_offline_method_field(method_id) {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });
    $.ajax({
        url:
            $("#route-pay-offline-method-list").data("route") +
            "?method_id=" +
            method_id,
        data: {},
        processData: false,
        contentType: false,
        type: "get",
        success: function (response) {
            $("#method-filed__div").html(response.methodHtml);
        },
        error: function () {},
    });
}

$(".checkout-wallet-payment-form").on("submit", function (event) {
    setTimeout(() => {
        $(".update_wallet_cart_button")
            .attr("type", "button")
            .addClass("disabled");
    }, 100);
});

$(".checkout-payment-form").on("submit", function (event) {
    event.preventDefault();

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });
    $.ajax({
        url: $(this).attr("action"),
        method: "GET",
        data: $(this).serialize(),
        beforeSend: function () {
            $("#loading").addClass("d-grid");
        },
        success: function (response) {
            if (response?.status == 0 && response?.message) {
                toastr.error(response.message);
            }
            // تحسين التعامل مع إعادة التوجيه لـ Tabby
            if (response?.redirect_url) {
                console.log("Redirecting to payment URL:", response.redirect_url);
                window.location.href = response.redirect_url;
                return;
            }
            if (response?.redirect && response?.redirect != "") {
                console.log("Standard redirect:", response.redirect);
                location.href = response?.redirect;
            }
        },
        complete: function () {
            $("#loading").removeClass("d-grid");
        },
        error: function (xhr, status, error) {
            console.error("Payment request error:", error);
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                let errorMessage = '';
                $.each(xhr.responseJSON.errors, function(key, value) {
                    errorMessage += value + '<br>';
                });
                toastr.error(errorMessage || 'Payment processing error');
            } else {
                toastr.error('An error occurred while processing your payment');
            }
        },
    });
});

$("#bring_change_amount").on("shown.bs.collapse", function () {
    $("#bring_change_amount_btn").text($(this).data("less"));
});

$("#bring_change_amount").on("hidden.bs.collapse", function () {
    $("#bring_change_amount_btn").text($(this).data("more"));
});

$("#bring_change_amount_input").on("keyup keypress change", function () {
    $("#bring_change_amount_value").val($(this).val());
});

$("#proceed-to-payment-action").on("click", function () {
    let getType = $(this).data("type");
    if (getType && getType.toString() === "checkout-payment") {
        let formId = $(".payment-method-list-page")
            .find('input[type="radio"]:checked')
            .data("form");
        if (formId !== "") {
            $(this).attr("disabled", true).addClass("disabled");
            $(formId).submit();
        }
    }
});

$(document).ready(function(){

    $(".payment-method_parent").on("click", function (e) {
        e.preventDefault();

        $('.payment-area').find("input[type='radio']").prop("checked", false);
        $(this).closest('.payment-method-form').find("input[name='payment_method']").prop("checked", true);

        $(".payment-method_parent").removeClass("border-selected");
        $(this).addClass("border-selected");
        $("#proceed-to-payment-action").removeClass("custom-disabled").removeAttr('disabled');

        if ($(this).hasClass("next-btn-enable")) {
            $("#proceed_to_next_action")
                .removeClass("custom-disabled")
                .removeAttr("disabled");
        } else {
            $("#proceed_to_next_action")
                .addClass("custom-disabled")
                .attr("disabled", true);
        }

        if ($(this).find(".digital_payment_btn").length === 0) {
            $('#digital_payment').slideUp("slow");
        }

        if ($(this).hasClass('cash_on_delivery')) {
            $(".bring-change-amount-container").slideDown("slow");
        } else {
            $(".bring-change-amount-container").slideUp("slow");
        }


    });

    $(".digital-payment-card_btn").on("click", function (e) {
        e.preventDefault();

        setTimeout(() => {
            $('.payment-area').find("input[type='radio']").prop("checked", false);
            $(this).closest('.payment-method-form').find("input[name='payment_method']").prop("checked", true);
            if ($(this).hasClass("next-btn-enable")) {
                $("#proceed_to_next_action")
                    .removeClass("custom-disabled")
                    .removeAttr("disabled");
            } else {
                $("#proceed_to_next_action")
                    .addClass("custom-disabled")
                    .attr("disabled", true);
            }
        }, 0);

    });

    $("#bring_change_amount").on("shown.bs.collapse", function () {
        $(document).on("click.outsideCollapse", function (e) {
            if (
                !$(e.target).closest("#bring_change_amount").length &&
                !$(e.target).closest("#bring_change_amount_btn").length
            ) {
                $("#bring_change_amount").collapse("hide");
            }
        });
    });

    $("#bring_change_amount").on("hidden.bs.collapse", function () {
        $(document).off("click.outsideCollapse");
    });

})
