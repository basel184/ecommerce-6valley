"use strict";

function getStockCheckForProduct(productID, productCartForm, fromID) {
    let minValue = parseInt($('.product_quantity__qty'+productID).attr('min'));
    let maxValue = parseInt($('.product_quantity__qty'+productID).attr('max'));
    let valueCurrent = parseInt($('.product_quantity__qty'+productID).val());
    let outOfStockMsg = productCartForm.data('outofstock');

    console.log(minValue);
    console.log(maxValue);
    console.log(valueCurrent);
    console.log(minValue);


    if (minValue >= valueCurrent) {
        productCartForm.find('.product_quantity__qty').val(minValue);
    }
    if (valueCurrent <= maxValue) {
        getProductVariantPrice(productID, productCartForm);
    } else {
        toastr.warning(outOfStockMsg);
    }
}

$('.stock_check_for_product_web').on('change', function () {
    let parentElement = $(this).closest('.product-cart-option-container');
    let productCartForm = parentElement.find('.addToCartDynamicForm');
    let productID = $(this).data('id');
    let fromID = '#add_to_cart_form_web' + productID;
    getStockCheckForProduct(productID, productCartForm, fromID);
})

$('.stock_check_for_product_mobile').on('change', function () {
    let productID = $(this).data('id');
    let fromID = '#stock_check_for_product_mobile' + productID;
    let parentElement = $(this).closest('.product-cart-option-container');
    let productCartForm = parentElement.find('.addToCartDynamicForm');
    getStockCheckForProduct(productID, productCartForm, fromID);
})


$('.add_to_cart_web').on('click', function () {
    let parentElement = $(this).closest('.product-cart-option-container');
    let productCartForm = parentElement.find('.addToCartDynamicForm');
    let productID = $(this).data('id');
    addToCartList(productID, productCartForm)
})

$('.add_to_cart_form').on("submit", function (e) {
    e.preventDefault();
});

function getProductVariantPrice(productID, productCartForm) {
    let qty = $('.product_quantity__qty' + productID).val();

    let no_discount = $('.text-custom-storage').data('text-no-discount');
    let stock_available = $('.text-custom-storage').data('stock-available');
    let stock_not_available = $('.text-custom-storage').data('stock-not-available');

    if (qty > 0 && checkAddToCartValidity(productCartForm)) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });
        $.ajax({
            type: "POST",
            url: productCartForm.data('varianturl'),
            data: productCartForm.serializeArray(),
            success: function (data) {
                $('.unit_price' + productID).html(data.total_unit_price);
                $('.total_price' + productID).html(data.unit_price);
                $('.tax' + productID).html(data.tax);
                if (data.discount != 0)
                    $('.discount' + productID).html('-' + data.discount);
                else {
                    $('.discount_status' + productID).empty().html(`<span class="badge text-capitalize badge-soft-secondary">${no_discount}</span>`)
                }
                if (data.quantity > 0) {
                    $('.stock_status' + productID).empty().html(`<span class="badge badge-soft-success text-capitalize">${stock_available}</span>`);
                } else {
                    $('.stock_status' + productID).empty().html(`<span class="badge badge-soft-danger text-capitalize">${stock_not_available}</span>`);
                }
            }
        });
    }
}

function addToCartList(id, formElement) {
    let qty = $('.product_quantity__qty' + id).val();
    if (qty > 0 && checkAddToCartValidity(formElement)) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });

        $.post({
            url: formElement.attr('action'),
            data: formElement.serializeArray(),
            beforeSend: function () {
            },
            success: function (response) {
                if (response.status == 1) {
                    updateNavCart();
                    toastr.success(response.message, {
                        CloseButton: true,
                        ProgressBar: true,
                        timeOut: 3000
                    });
                    return false;
                } else if (response.status == 0) {
                    toastr.warning(response.message, {
                        CloseButton: true,
                        ProgressBar: true,
                        timeOut: 2000
                    });
                    return false;
                }
            },
            complete: function () {
            }
        });
    } else if (qty == 0) {
        toastr.warning(formElement.data('outofstock'), {
            CloseButton: true,
            ProgressBar: true,
            timeOut: 2000
        });
    } else {
        toastr.info(formElement.data('errormessage'), {
            CloseButton: true,
            ProgressBar: true,
            timeOut: 2000
        });
    }
}
