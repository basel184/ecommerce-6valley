"use strict";

$(document).ready(function () {
    getVariantPrice(".add-to-cart-details-form");
    actionRequestForProductRestockFunctionality();
});

$('.add-to-cart-details-form input').on('change', function () {
    getVariantPrice(".add-to-cart-details-form");
});

$('.add-to-cart-details-form').on('submit', function (e) {
    e.preventDefault();
});


$('.addCompareList_quick_view').on('click', function () {
    let id = $(this).data('id');
    addCompareList(id);
});

$('.addWishlist_function_btn').on('click', function () {
    let productId = $('#quick-view-product-id').data('product-id');
    addWishlist_function(productId);
});

$('.product-add-to-cart-button').on('click', function () {
    let parentElement = $(this).closest('.product-cart-option-container');
    let productCartForm = parentElement.find('.addToCartDynamicForm')
    addToCart(productCartForm);
});

$('.product-buy-now-button').on('click', function () {
    let url = $(this).data("route");
    let redirectStatus = $(this).data("auth").toString();
    let parentElement = $(this).closest('.product-cart-option-container');
    let productCartForm = parentElement.find('.addToCartDynamicForm')
    addToCart(productCartForm, redirectStatus, url);
    if(redirectStatus === "false") {
        $("#quickViewModal").modal("hide");
        customerLoginRegisterModalCall()
        toastr.warning($('.login-warning').data('login-warning-message'));
    }
});
