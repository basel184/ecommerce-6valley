"use strict";

let loadReviewFunctionButton = $("#load_review_function");
let seeMoreDetailsReview = $(".see-more-details-review");

$(".currency_change_function").on("click", function () {
    let currency_code = $(this).data("currencycode");

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });
    $.ajax({
        type: "POST",
        url: $("#currency-route").data("currency-route"),
        data: {
            currency_code: currency_code,
        },
        success: function (data) {
            toastr.success(data.message);
            location.reload();
        },
    });
});

$(".change-language").on("click", function () {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });
    $.ajax({
        type: "POST",
        url: $(this).data("action"),
        data: {
            language_code: $(this).data("language-code"),
        },
        success: function (data) {
            toastr.success(data.message);
            location.reload();
        },
    });
});

function renderFocusPreviewImageByColor() {
    $(".focus_preview_image_by_color").on("click", function () {
        let id = $(this).data("colorid");
        $(`.color_variants_${id}`).click();
    });
}

renderFocusPreviewImageByColor();

function checkAddToCartValidity(formElement) {
    var names = {};
    var radioInputs = $(formElement).find("input[type='radio']");

    // Return true if no radio inputs are found
    if (radioInputs.length === 0) {
        return true;
    }

    radioInputs.each(function () {
        names[$(this).attr("name")] = true;
    });

    var count = 0;
    $.each(names, function () {
        count++;
    });

    if ($(formElement).find("input[type='radio']:checked").length == count) {
        return true;
    }
    return false;
}

function checkAddToCartFormValidity(formElement) {
    var names = {};
    var radioInputs = formElement.find("input[type='radio']");

    // Return true if no radio inputs are found
    if (radioInputs.length === 0) {
        return true;
    }

    radioInputs.each(function () {
        names[$(this).attr("name")] = true;
    });

    var count = 0;
    $.each(names, function () {
        count++;
    });

    if (formElement.find("input[type='radio']:checked").length == count) {
        return true;
    }
    return false;
}


function quickView(product_id, url = null) {
    let action_url = $("#quick_view_url").data("url");
    $.get({
        url: action_url,
        dataType: "json",
        data: { product_id: product_id },
        beforeSend: function () { $("#loading").addClass("d-grid"); },
        success: function (data) {
            $("#quickViewModal_content").empty().html(data.view);
            owl_carousel_quick_view();
            inc_dec_btn_quick_view();
            $("#quickViewModal").modal("show");
            $("#quickViewModal .modal-dialog .modal-content").css("opacity", "1");
        },
        complete: function(){ $("#loading").removeClass("d-grid"); }
    });
}

function updateNavCart() {
    let url = $("#update_nav_cart_url").data("url");
    $.post(
        url,
        {
            _token: $('meta[name="_token"]').attr("content"),
        },
        function (response) {
            $("#cart_items").html(response.data);
            $("#mobile_app_bar").html(response.mobile_nav);
            update_floating_nav_cart();
            updateCartQuantity_cart_data();
            addWishlist_function_view_page();
        }
    );
}

function updateCartQuantity_cart_data() {
    $(".updateCartQuantity_cart_data").on("click", function () {
        let cart = $(this).data("cart");
        let product = $(this).data("product");
        let value = $(this).data("value");
        let action = $(this).data("action");
        updateCartQuantity(cart, product, value, action);
    });
}

function update_floating_nav_cart() {
    let url = $("#update_floating_nav_cart_url").data("url");
    $.post(
        url,
        {
            _token: $('meta[name="_token"]').attr("content"),
        },
        function (response) {
            $("#floating_cart_items").html(response.floating_nav);
        }
    );
}

function updateCartQuantity(cart_id, product_id, action, event) {
    let remove_url = $("#remove_from_cart_url").data("url");
    let update_quantity_url = $("#update_quantity_url").data("url");
    let token = $('meta[name="_token"]').attr("content");
    let product_qyt =
        parseInt($(`.cartQuantity${cart_id}`).val()) + parseInt(action);
    let cart_quantity_of = $(`.cartQuantity${cart_id}`);
    let segment_array = window.location.pathname.split("/");
    let segment = segment_array[segment_array.length - 1];

    if (cart_quantity_of.val() > cart_quantity_of.data("current-stock")) {
        cartItemRemoveFunction(remove_url, token, cart_id, segment);
        return false;
    }

    if (cart_quantity_of.val() == 0) {
        toastr.info($(".cannot_use_zero").data("text"), {
            CloseButton: true,
            ProgressBar: true,
        });
        cart_quantity_of.val(cart_quantity_of.data("min"));
    } else if (
        cart_quantity_of.val() == cart_quantity_of.data("min") &&
        event == "minus"
    ) {
        cartItemRemoveFunction(remove_url, token, cart_id, segment);
    } else {
        if (cart_quantity_of.val() < cart_quantity_of.data("min")) {
            let min_value = cart_quantity_of.data("min");
            toastr.error(
                "Minimum order quantity cannot be less than " + min_value
            );
            cart_quantity_of.val(min_value);
            updateCartQuantity(cart_id, product_id, action, event);
        } else {
            $(`.cartQuantity${cart_id}`).html(product_qyt);
            $.post(
                update_quantity_url,
                {
                    _token: token,
                    key: cart_id,
                    product_id: product_id,
                    quantity: product_qyt,
                },
                function (response) {
                    update_floating_nav_cart();
                    if (response["status"] == 0) {
                        toastr.error(response["message"]);
                    } else {
                        toastr.success(response["message"]);
                    }
                    response["qty"] <= 1
                        ? $(`.quantity__minus${cart_id}`).html(
                            '<i class="bi bi-trash3-fill text-danger fs-10"></i>'
                        )
                        : $(`.quantity__minus${cart_id}`).html(
                            '<i class="bi bi-dash"></i>'
                        );

                    $(`.cartQuantity${cart_id}`).val(response["qty"]);
                    $(`.cartQuantity${cart_id}`).html(response["qty"]);
                    $(".cart_total_amount").html(response.total_price);
                    $(`.discount_price_of_${cart_id}`).html(
                        response["discount_price"]
                    );
                    $(`.quantity_price_of_${cart_id}`).html(
                        response["quantity_price"]
                    );

                    if (response["qty"] == cart_quantity_of.data("min")) {
                        cart_quantity_of
                            .parent()
                            .find(".quantity__minus")
                            .html(
                                '<i class="bi bi-trash3-fill text-danger fs-10"></i>'
                            );
                    } else {
                        cart_quantity_of
                            .parent()
                            .find(".quantity__minus")
                            .html('<i class="bi bi-dash"></i>');
                    }
                    if (
                        segment === "shop-cart" ||
                        segment === "checkout-payment" ||
                        segment === "checkout-details"
                    ) {
                        location.reload();
                    }
                }
            );
        }
    }
}

function cartItemRemoveFunction(remove_url, token, cart_id, segment) {
    $.post(
        remove_url,
        {
            _token: token,
            key: cart_id,
        },
        function (response) {
            updateNavCart();
            toastr.info(response.message, {
                CloseButton: true,
                ProgressBar: true,
            });
            getUpdateProductAddUpdateCartBtn(response)
            if (
                segment === "shop-cart" ||
                segment === "checkout-payment" ||
                segment === "checkout-details"
            ) {
                location.reload();
            }
        }
    );
}

function getUpdateProductAddUpdateCartBtn(response) {
    try {
        let productInfo = $('.product-generated-variation-code');
        let productVariantExist = false;

        response?.cartList?.map(function (item, index) {
            if (productInfo.data('product-id') == item?.id && productInfo.val() == item?.variant) {
                productVariantExist = true;
            }
        })

        if (!productVariantExist) {
            let actionAddToCartBtn = $('.product-add-to-cart-button');
            actionAddToCartBtn.children("span").html(actionAddToCartBtn.data("add"));
            $('.product-exist-in-cart-list[name="key"]').val('');
        }
    } catch (e) {
    }
}

function checkValidityForVariantPrice(formSelector) {
    return $(formSelector).find("input[name=quantity]").val() > 0 && checkAddToCartValidity(formSelector);
}

function checkFromValidityForVariantPrice(formSelector) {
    return formSelector.find("input[name=quantity]").val() > 0 && checkAddToCartFormValidity(formSelector);
}

function getStockCheckOnVariantPrice(formSelector = ".add-to-cart-details-form") {
    const productQty = $(formSelector).find(".product_quantity__qty");
    const minValue = parseInt(productQty.attr("min"));
    const maxValue = parseInt(productQty.attr("max"));
    const valueCurrent = parseInt(productQty.val());

    if (minValue >= valueCurrent) {
        productQty.val(minValue);
        try {
            if (productQty.data("details-page")) {
                productQty.parent().find(".quantity__minus").html('<i class="bi bi-dash"></i>');
            } else {
                productQty.parent().find(".quantity__minus").html('<i class="bi bi-trash3-fill text-danger fs-10"></i>');
            }
        } catch (e) {
            productQty.parent().find(".quantity__minus").html('<i class="bi bi-trash3-fill text-danger fs-10"></i>');
        }
    } else {
        productQty.parent().find(".quantity__minus").html('<i class="bi bi-dash"></i>');
    }
}

$(".add-to-cart-details-form").on("submit", function (e) {
    e.preventDefault();
});

$(".add-to-cart-details-form input").on("change", function () {
    getVariantPrice(".add-to-cart-details-form");
});

$(".add-to-cart-sticky-form input").on("change", function () {
    getVariantPrice(".add-to-cart-sticky-form");
});

let checkFirstTimeVariant = true;

function getVariantPrice(formSelector = ".add-to-cart-details-form") {
    // getStockCheckOnVariantPrice(formSelector);

    if (checkValidityForVariantPrice(formSelector)) {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
            },
        });

        $.ajax({
            type: "POST",
            url: $("#route-cart-variant-price").data("url"),
            data: $(formSelector).serializeArray(),
            success: function (response) {
                updateProductDetailsTopSection(formSelector, response);

                if (formSelector === '.add-to-cart-sticky-form' || checkFirstTimeVariant) {
                    checkFirstTimeVariant = false;
                    updateProductDetailsBottomSection(formSelector, response);
                }

                if (response.quantity > 0) {
                    if (response.quantity <= response.stock_limit) {
                        $(formSelector).find(".product-details-stock-status").addClass("d-none");
                        $(formSelector).find(".product-details-stock-out").addClass("d-none");
                        $(formSelector).find(".product-details-stock-limited").removeClass("d-none");
                        $(formSelector).find(".product-details-stock-qty").html(response.quantity);
                    } else {
                        $(formSelector).find(".product-details-stock-status").removeClass("d-none");
                        $(formSelector).find(".product-details-stock-out").addClass("d-none");
                        $(formSelector).find(".product-details-stock-limited").addClass("d-none");
                        $(formSelector).find(".product-details-stock-qty").html(response.quantity);
                    }
                }

                if (response.quantity <= 0 || response.quantity < $(formSelector).find("input[name=quantity]").val()) {
                    $(formSelector).find(".product-details-stock-status").addClass("d-none");
                    $(formSelector).find(".product-details-stock-out").removeClass("d-none");
                    $(formSelector).find(".product-details-stock-limited").addClass("d-none");
                }

                // end stock status
                if (response.quantity !== 0 && response.quantity > $(formSelector).find(".product_qty").attr("max")) {
                    $(formSelector).find(".product_qty").attr("max", response.quantity);
                } else {
                    if (response.quantity <= 0) {
                        $(formSelector).find(".product_qty").val(parseInt($(formSelector).find(".product_qty").attr("min")));
                        $(formSelector).find(".product_qty").attr("max", response.quantity);
                    } else {
                        $(formSelector).find(".product_qty").attr("max", response.quantity);
                    }
                }

                if (response?.discount_amount > 0) {
                    if (response?.discount_type === 'flat') {
                        $(formSelector).find(".discounted_badge").html(`${response?.discount}`);
                    } else {
                        $(formSelector).find(".discounted_badge").html(`- ${response?.discount}%`);
                    }
                    $(formSelector).find(".discounted-badge-element").removeClass('d-none');
                } else {
                    $(formSelector).find(".discounted-badge-element").addClass('d-none');
                }

            },
        });
    }
}

function updateProductDetailsBottomSection(formSelector, response) {
    let productDetailsStickySection = $('.product-details-sticky-section');

    productDetailsStickySection.find(".product-details-chosen-price-amount").html(response?.price);
    productDetailsStickySection.find(".discounted-unit-price").html(response?.discounted_unit_price);
    productDetailsStickySection.find(".product-total-unit-price").html(response?.discount_amount > 0 ? response?.total_unit_price : '');
    productDetailsStickySection.find(".product-details-sticky-color-name").html(response?.color_name ? `(${response?.color_name})` : '');

    if (response?.discount_amount > 0) {
        productDetailsStickySection.find(".discounted_badge").html(`- ${response?.discount}`);
        productDetailsStickySection.find(".discounted-badge-element").removeClass('d-none');
    } else {
        productDetailsStickySection.find(".discounted-badge-element").addClass('d-none');
    }
}

function updateProductDetailsTopSection(formSelector, response) {
    $(formSelector).find(".product-details-chosen-price-section").removeClass("d-none");
    
    // Update main price section (right side)
    $(formSelector).find(".product-details-chosen-price-amount").html(response?.price);
    $(formSelector).find(".product-details-original-price-amount").html(response?.total_unit_price);
    
    // Update sidebar price section (left side)
    $(formSelector).find(".product-details-chosen-price-amount-sidebar").html(response?.price);
    $(formSelector).find(".product-details-original-price-amount-sidebar").html(response?.total_unit_price);
    
    $(formSelector).find(".product-details-tax-amount").html(response?.update_tax);
    $(".product-details-delivery-cost").html(response?.delivery_cost);

    $(formSelector).find(".product-details-stock-qty").html(response?.quantity);

    // Update discount badges for both sections
    if (response?.discount_amount > 0) {
        let discountText = '';
        let mainDiscountText = '';
        
        if (response?.discount_type === 'flat') {
            discountText = `وفر ${response?.discount}`;
            mainDiscountText = response?.discount;
        } else {
            discountText = `خصم ${response?.discount}%`;
            mainDiscountText = `- ${response?.discount}%`;
        }
        
        // Main section discount badge
        $(formSelector).find(".discounted_badge").html(mainDiscountText);
        $(formSelector).find(".discounted-badge-element").removeClass('d-none').show();
        
        // Sidebar discount badge
        $(formSelector).find(".discounted_badge_sidebar").html(discountText);
        $(formSelector).find(".discounted-badge-element-sidebar").removeClass('d-none').show();
        
        // Show original prices
        $(formSelector).find(".product-details-original-price-amount").parent().show();
        $(formSelector).find(".product-details-original-price-amount-sidebar").parent().show();
    } else {
        $(formSelector).find(".discounted-badge-element").addClass('d-none').hide();
        $(formSelector).find(".discounted-badge-element-sidebar").addClass('d-none').hide();
        
        // Hide original prices
        $(formSelector).find(".product-details-original-price-amount").parent().hide();
        $(formSelector).find(".product-details-original-price-amount-sidebar").parent().hide();
    }

    if (response?.product_type?.toString() === 'physical') {
        let productRestockRequestButton = $(formSelector).find(".product-restock-request-button");
        if (response?.quantity <= 0) {
            $(formSelector).find(".product-restock-request-section").show();
            productRestockRequestButton.removeAttr('disabled');
            $(formSelector).find(".product-add-and-buy-section").hide().removeClass('d-flex');
        } else {
            $(formSelector).find(".product-restock-request-section").hide();
            $(formSelector).find(".product-add-and-buy-section").show().addClass('d-flex');
        }
        if (response?.restock_request_status) {
            productRestockRequestButton.html(productRestockRequestButton.data('requested'));
            productRestockRequestButton.attr('disabled', true);
        } else {
            productRestockRequestButton.html(productRestockRequestButton.data('default'));
        }
    }

    $(formSelector).find(".product-details-cart-qty").val(response?.in_cart_quantity);
    $(formSelector).find(".product-generated-variation-code").val(response?.variation_code);
    $(formSelector).find(".product-generated-variation-text").text(response?.variation_code);

    if ($(formSelector).find(".product-details-cart-qty").attr("min") < parseFloat($(formSelector).find(".product-details-cart-qty").val())) {
        $(formSelector).find(".btn-number[data-type='minus'][data-field='quantity']").removeAttr("disabled");
    } else {
        $(formSelector).find(".btn-number[data-type='minus'][data-field='quantity']").attr("disabled", "disabled");
    }

    if (response?.discount_amount > 0) {
        if (response?.discount_type === 'flat') {
            $(formSelector).find(".discounted_badge").html(`${response?.discount}`);
        } else {
            $(formSelector).find(".discounted_badge").html(`- ${response?.discount}%`);
        }
        $(formSelector).find(".discounted-badge-element").removeClass('d-none');
    } else {
        $(formSelector).find(".discounted-badge-element").addClass('d-none');
    }
}

//compare list search 0 Index
function global_search_for_compare_list0() {
    global_search_for_compare_list_common(0);
}

//compare list search 1
function global_search_for_compare_list1() {
    global_search_for_compare_list_common(1);
}

//compare list search 2
function global_search_for_compare_list2() {
    global_search_for_compare_list_common(2);
}

function global_search_for_compare_list_common(key) {
    $(".search-card").css("display", "block");
    let name = $("#search_bar_input" + key).val();
    let compare_id = $("#compare_id" + key).val();
    let base_url = $('meta[name="base-url"]').attr("content");
    if (name.length > 0) {
        $.get({
            url: base_url + "/searched-products-for-compare",
            dataType: "json",
            data: {
                name,
                compare_id,
            },
            beforeSend: function () {
                $("#loading").addClass("d-grid");
            },
            success: function (data) {
                $(".search-result-box-compare-list" + key)
                    .empty()
                    .html(data.result);
            },
            complete: function () {
                $("#loading").removeClass("d-grid");
            },
        });
    } else {
        $(".search-result-box-compare-list" + key).empty();
    }
}

// End of product Compare List

// Chat with Seller Modal JS || Start
$("#contact_with_seller_form").on("submit", function (e) {
    e.preventDefault();
    let messages_form = $(this);
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });

    $.ajax({
        type: "post",
        url: messages_form.attr("action"),
        data: messages_form.serialize(),
        success: function (respons) {
            toastr.success(
                $("#contact_with_seller_form").data("success-message"),
                {
                    CloseButton: true,
                    ProgressBar: true,
                }
            );
            $("#contact_with_seller_form").trigger("reset");
            $("#contact_sellerModal").modal("hide");
        },
    });
});
// Chat with Seller Modal JS || End

$(".lightbox_custom").on("click", function (e) {
    e.preventDefault();
    new lightbox(this);
});

// ShopView Review - View more button action
let load_review_for_shop_count = 1;
let loadShopReviewShowStatus = 1;

$("#load_review_for_shop").on("click", function () {
    let shop_id = $(this).data("shopid");

    let url_load_review = seeMoreDetailsReview.data("routename");
    let onerror = seeMoreDetailsReview.data("onerror");
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });
    $.ajax({
        type: "post",
        url: url_load_review,
        data: {
            shop_id: shop_id,
            offset: load_review_for_shop_count,
        },
        success: function (data) {
            $("#shop-review-list").append(data.productReview);
            readMoreCurrentReview();
            renderCustomImagePopup();

            if (loadShopReviewShowStatus === 1) {
                if (data.checkReviews <= 1) {
                    seeMoreDetailsReview
                        .closest(".product-information")
                        .addClass("active");
                    seeMoreDetailsReview.html(
                        seeMoreDetailsReview.data("afterextend")
                    );
                }
            } else {
                seeMoreActions(seeMoreDetailsReview);
            }
            if (data.checkReviews <= 1 && loadShopReviewShowStatus === 1) {
                loadShopReviewShowStatus = 0;
            }
        },
        complete: function () {
            $(".lightbox_custom")
                .off("click")
                .on("click", function (e) {
                    e.preventDefault();
                    new lightbox(this);
                });
        },
    });
    load_review_for_shop_count++;
});

// Product Review - View more button action
let load_review_count = 1;
let loadReviewShowStatus = 1;

loadReviewFunctionButton.on("click", function () {
    let productId = $(this).data("productid");

    let url_load_review = seeMoreDetailsReview.data("routename");
    let onerror = seeMoreDetailsReview.data("onerror");
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });
    $.ajax({
        type: "post",
        url: url_load_review,
        data: {
            product_id: productId,
            offset: load_review_count,
        },
        success: function (data) {
            $("#product-review-list").append(data.productReview);
            readMoreCurrentReview();
            renderCustomImagePopup();

            if (loadReviewShowStatus === 1) {
                if (data.checkReviews <= 1) {
                    seeMoreDetailsReview
                        .closest(".product-information")
                        .addClass("active");
                    seeMoreDetailsReview.html(
                        seeMoreDetailsReview.data("afterextend")
                    );
                }
            } else {
                seeMoreActions(seeMoreDetailsReview);
            }
            if (data.checkReviews <= 1 && loadReviewShowStatus === 1) {
                loadReviewShowStatus = 0;
            }
        },
        complete: function () {
            $(".lightbox_custom")
                .off("click")
                .on("click", function (e) {
                    e.preventDefault();
                    new lightbox(this);
                });
        },
    });
    load_review_count++;
});

function seeMoreActions(element) {
    let reviewSeeMore = element.data("seemore");
    let reviewSeeLess = element.data("afterextend");

    if (element.closest(".product-information").hasClass("active")) {
        element.closest(".product-information").removeClass("active");
        loadReviewFunctionButton.html(reviewSeeMore);
        console.log(reviewSeeMore);
        console.log("In step one");
    } else {
        element.closest(".product-information").addClass("active");
        loadReviewFunctionButton.html(reviewSeeLess);
        console.log(reviewSeeLess);
        console.log("In step two");
    }
}

$(".single_section_dual_tabs .single_section_dual_btn li").on(
    "click",
    function () {
        let tabTarget = $(this).data("targetbtn");
        $(this)
            .parent()
            .parent()
            .find(".single_section_dual_target a")
            .addClass("d-none");
        $(this)
            .parent()
            .parent()
            .find(`.single_section_dual_target a:eq(${tabTarget})`)
            .removeClass("d-none");
    }
);

// Shop Details Page JS || Start
$(".shop_follow_action").on("click", function () {
    let shop_id = $(this).data("shopid");

    let status = $(this).data("status");
    if (status == 1) {
        Swal.fire({
            title: $(this).data("titletext"),
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: $(this).data("titletext2"),
            cancelButtonText: $(this).data("titlecancel"),
        }).then((result) => {
            if (result.isConfirmed) {
                shopFollow(shop_id);
            }
        });
    } else {
        shopFollow(shop_id);
    }
});

function shopFollow(shop_id) {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });
    $.ajax({
        url: $("#shop_follow_url").data("url"),
        method: "POST",
        data: {
            shop_id: shop_id,
        },
        beforeSend: function () {
            $("#loading").addClass("d-grid");
        },
        success: function (data) {
            if (data.value == 1) {
                toastr.success(data.message);
                $(".follower_count").html(data.followers);
                $(".follow_button").html(data.text);
                $(".follow_button").data("status", "1");
            } else if (data.value == 2) {
                toastr.success(data.message);
                $(".follower_count").html(data.followers);
                $(".follow_button").html(data.text);
                $(".follow_button").data("status", "0");
            } else {
                toastr.error(data.message);
                $("#loginModal").modal("show");
            }
        },
        complete: function () {
            $("#loading").removeClass("d-grid");
        },
    });
}

// Shop Details Page JS || End

/*========================
Background Image Use by data-bg-img (Attr)
==========================*/
var $bgImg = $("[data-bg-img]");
$bgImg
    .css("background-image", function () {
        return 'url("' + $(this).data("bg-img") + '")';
    })
    .removeAttr("data-bg-img");

function password_keyup() {
    if (
        $("#seller_password").val() != "" &&
        $("#seller_repeat_password").val() != ""
    ) {
        $(".password_message").removeClass("d-none");
        password_validation(
            $("#seller_password").val(),
            $("#seller_repeat_password").val()
        );
    } else {
        $(".password_message").addClass("d-none");
    }
}

function password_validation(password_one, password_two) {
    let password_characters_limit = $(".text-custom-storage").data(
        "password-characters-limit"
    );
    let password_not_match = $(".text-custom-storage").data(
        "password-not-match"
    );

    let message = "";
    if (password_one.length < 8 || password_two.length < 8) {
        message = password_characters_limit;
    } else if (password_one !== password_two) {
        message = password_not_match;
    }
    $(".password_message").html(message);
}

// Seller Registration Page JS || End

// Fashion Products List Form JS || Start
$("#fashion_products_list_form input").on("change", function () {
    if ($(this).hasClass("block-from-submit")) {
        return false;
    }
    inputTypeNumberClick(1);
    fashion_products_list_form_common();
});

$("#fashion_products_list_form select").on("change", function () {
    inputTypeNumberClick(1);
    fashion_products_list_form_common();
});

function fashion_products_list_form_common() {
    $(".products_navs_list li input").removeAttr("checked");
    $("#filter_by_all").attr("checked", true);
    $(".products_navs_list li label").removeClass("active");
    $(".filter_by_all").addClass("active");
}

$("#fashion_products_list_form input").on("keyup", function () {
    if ($(this).hasClass("block-from-submit")) {
        return false;
    }
    $("#fashion_products_list_form").submit();
});

$(".filter_by_product_list_web").on("change", function () {
    let value = $(this).val();
    let option = '<option value=" ' + value + ' " selected></option>';
    $(".filter_by_product_list_mobile").append(option);
    inputTypeNumberClick(1);
    fashion_products_list_form_common();
    productCommonActionForViewEvents();
});

$(".filter_by_product_list_mobile").on("change", function () {
    let value = $(this).val();
    let option = '<option value=" ' + value + ' " selected></option>';
    $(".filter_by_product_list_web").append(option);
    inputTypeNumberClick(1);
    fashion_products_list_form_common();
});

function inputTypeNumberClick(key, slider = null) {
    if (slider != null) {
        setTimeout(function () {
            $("#fashion_products_list_form").submit();
        }, 500);
    } else {
        $(".paginate_btn").removeAttr("checked", true);
        $(".paginate_btn_id" + key).attr("checked", true);
        $("#fashion_products_list_form").submit();
    }
}

$(".inputTypeNumberClick").on("click", function () {
    inputTypeNumberClick($(this).data("page"));
});

function set_shipping_id_function() {
    $(".set_shipping_id_function").on("click", function () {
        let id = $(this).data("id");
        let cart_group_id = $(this).data("cart-group");
        set_shipping_id(id, cart_group_id);
    });

    $(".set_shipping_onchange").on("change", function () {
        let id = $(this).val();
        set_shipping_id(id, "all_cart_group");
    });

    function set_shipping_id(id, cart_group_id) {
        $.get({
            url: $("#set_shipping_url").data("url"),
            dataType: "json",
            data: {
                id: id,
                cart_group_id: cart_group_id,
            },
            beforeSend: function () {
                $("#loading").addClass("d-grid");
            },
            success: function (data) {
                location.reload();
            },
            complete: function () {
                $("#loading").removeClass("d-grid");
            },
        });
    }
}

set_shipping_id_function();

// Add to Cart Function
function addToCart(formElement, redirectToCheckout = "false", url = null) {
    if (checkFromValidityForVariantPrice(formElement)) {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
            },
        });
        let redirectCheckout = redirectToCheckout?.toString();

        let existCartItem = $('.product-exist-in-cart-list[name="key"]').val();
        let formActionUrl = formElement.attr("action");
        if (existCartItem !== "" && redirectCheckout === "false") {
            formActionUrl = $("#update_quantity_url").data("url");
        }

        $.post({
            url: formActionUrl,
            data: formElement.serializeArray()
                .concat({
                    name: "buy_now",
                    value: redirectCheckout === "true" ? 1 : 0,
                }),
            beforeSend: function () {
            },
            success: function (response) {
                if (response.status === 2) {
                    hideProductDetailsStickySection();
                    $("#buyNowModal-body").html(
                        response.shippingMethodHtmlView
                    );
                    $("#quickViewModal").modal("hide");
                    $("#buyNowModal").modal("show");
                }

                if (response.status == 1) {
                    updateNavCart();

                    let actionAddToCartBtn = formElement.find(".product-add-to-cart-button");
                    actionAddToCartBtn.children("span").html(actionAddToCartBtn.data("update"));

                    if (response?.product_variant_type === 'single_variant') {
                        $('.add-to-cart-details-form').find(".product-add-to-cart-button").children('.text').html(actionAddToCartBtn.data("update"));
                        $('.add-to-cart-sticky-form').find(".product-add-to-cart-button").children('.text').html(actionAddToCartBtn.data("update"));
                    }

                    toastr.success(response.message, {
                        CloseButton: true,
                        ProgressBar: true,
                        timeOut: 2000, // duration
                    });
                    if (
                        redirectCheckout === "true" &&
                        response.redirect_to_url
                    ) {
                        setTimeout(function () {
                            location.href = response.redirect_to_url;
                        }, 100);
                    } else if (redirectCheckout === "true") {
                        setTimeout(function () {
                            location.href = url;
                        }, 100);
                    }

                    $("#quickViewModal").modal("hide");
                } else if (response.status == 0) {
                    toastr.warning(response.message, {
                        CloseButton: true,
                        ProgressBar: true,
                        timeOut: 2000, // duration
                    });
                }
            },
            complete: function () {
            },
        });
    } else if (formElement.find("input[name=quantity]") == 0) {
        toastr.warning(formElement.data("outofstock"), {
            CloseButton: true,
            ProgressBar: true,
            timeOut: 2000, // duration
        });
    } else {
        toastr.info(formElement.data("errormessage"), {
            CloseButton: true,
            ProgressBar: true,
            timeOut: 2000, // duration
        });
    }
}

function hideProductDetailsStickySection() {
    $('html, body').animate({ scrollTop: 0 }, 'slow');
    setTimeout(() => {
        $('.product-details-sticky-section').removeClass('active');
    });
}

// Product Buy Now Button Action || Start
$(document).ready(function() {
    $(".product-buy-now-button").on("click", function () {
        let url = $(this).data("route");
        let redirectStatus = $(this).data("auth").toString();
        let parentElement = $(this).closest('.product-cart-option-container');
        let productCartForm = parentElement.find('.addToCartDynamicForm');
        
        if (typeof addToCart === 'function') {
            addToCart(productCartForm, redirectStatus, url);
        } else {
            console.error('addToCart function is not defined');
        }
        
        if (redirectStatus === "false") {
            $("#quickViewModal").modal("hide");
            if (typeof customerLoginRegisterModalCall === 'function') {
                customerLoginRegisterModalCall();
            }
            toastr.warning($('.login-warning').data('login-warning-message'));
        }
    });
});

function addCompareList(product_id) {
    let action_url = $("#store_compare_list_url").data("url");
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });

    $.ajax({
        url: action_url,
        method: "POST",
        data: {
            product_id,
        },
        beforeSend: function () {
            $("#loading").addClass("d-grid");
        },
        success: function (data) {
            if (data.value == 1) {
                toastr.success(data.success);
                $(`.text-base`).removeClass("text-base").focusout();
                $(`.compare_list_icon_active`)
                    .removeClass("compare_list_icon_active")
                    .focusout();

                $.each(data.compare_product_ids, function (key, id) {
                    $(`.compare_list-${id}`)
                        .addClass("compare_list_icon_active")
                        .focusout();
                    $(`.compare_list_icon-${id}`)
                        .addClass("text-base")
                        .focusout();
                });
            } else if (data.value == 2) {
                $(`.text-base`).removeClass("text-base").focusout();
                $(`.compare_list_icon_active`)
                    .removeClass("compare_list_icon_active")
                    .focusout();
                $.each(data.compare_product_ids, function (key, id) {
                    $(`.compare_list-${id}`)
                        .addClass("compare_list_icon_active")
                        .focusout();
                    $(`.compare_list_icon-${id}`)
                        .addClass("text-base")
                        .focusout();
                });
            } else {
                toastr.warning(data.error);
                $("#quickViewModal").modal("hide");
                customerLoginRegisterModalCall();
            }
        },
        complete: function () {
            $("#loading").removeClass("d-grid");
        },
    });
}

// Product Add to Cart Event Handlers
$(document).ready(function() {
    $(".product-add-to-cart-button").on("click", function () {
        let parentElement = $(this).closest('.product-cart-option-container');
        let productCartForm = parentElement.find('.addToCartDynamicForm');
        
        if (typeof addToCart === 'function') {
            addToCart(productCartForm.length ? productCartForm : $(".add-to-cart-details-form"));
        } else {
            console.error('addToCart function is not defined');
        }
    });

    $(".add_to_cart_mobile").on("click", function () {
        let productID = $(this).data('id');
        let parentElement = $(this).closest('.product-cart-option-container');
        let productCartForm = parentElement.find('.addToCartDynamicForm');
        
        if (typeof addToCart === 'function') {
            addToCart(productCartForm.length ? productCartForm : $(".add-to-cart-details-form"));
        } else {
            console.error('addToCart function is not defined');
        }
    });
});

// ==== Product Share Link Generator JS || Start ====
function social_share_function() {
    $(".social_share_function").on("click", function () {
        let url = $(this).data("url");
        let social = $(this).data("social");

        var width = 600,
            height = 400,
            left = (screen.width - width) / 2,
            top = (screen.height - height) / 2;
        window.open(
            "https://" + social + encodeURIComponent(url),
            "Popup",
            "toolbar=0,status=0,width=" +
            width +
            ",height=" +
            height +
            ",left=" +
            left +
            ",top=" +
            top
        );
    });
}

social_share_function();
// ==== Product Share Link Generator JS || End ====

$("#fashion_products_list_form").on("submit", function (event) {
    event.preventDefault();

    resetCategoryWiseSearchInput();

    $(".product_view_title").text($(".product_view_title").data("allproduct"));
    let form = $(this);
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });

    $.ajax({
        url: $(this).attr("action"),
        method: "POST",
        data: form.serialize(),
        beforeSend: function () {
            $("#loading").addClass("d-grid");
        },
        success: function (response) {
            if (response?.status?.toString() === '0' && response?.message) {
                toastr.error(response?.message)
            } else {
                var tabId = ".scroll_to_form_top";
                // Using scrollTop() method
                var tabTopPosition = $(tabId).offset().top - 80;
                $("html, body").scrollTop(tabTopPosition);

                $("#ajax_products_section").empty().html(response?.html_products);
                $("#selected_filter_area").empty().html(response.html_tags);
                productCommonActionForViewEvents();
                resetAllInProductList();

                // If no products found, ensure no infinite-scroll keeps appending empty-state
                try {
                    var total = Number(response?.products_count || 0);
                    var pageCount = Number(response?.page_products_count || 0);
                    if (total === 0 || pageCount === 0) {
                        $(window).off('scroll.productInfinite');
                        $('.infinite-scroll-loader, .infinite-scroll-end, .page-progress-indicator').hide();
                    }
                    // Re-init infinite scroll via global initializer
                    if (total > 0) {
                        if (typeof window.initProductsInfiniteScroll === 'function') {
                            window.initProductsInfiniteScroll();
                        }
                    }
                } catch (e) {}
            }
        },
        complete: function () {
            $("#loading").removeClass("d-grid");
        },
    });
});

function resetCategoryWiseSearchInput() {
    $(".search_input_store").val("");
    try {
        $('.category-search-form').find('input').val('');
        $('#search_category_value, #search_category_value_web').val('all').trigger('change');
    } catch (e) {

    }
}

// --------------------
// Infinite Scroll (Global)
// --------------------
(function(){
    let isLoadingNext = false;

    function readStateFromDom() {
        const $state = $('.product-scroll-state');
        // Read from attributes to avoid jQuery .data() caching old values
        const pageAttr = $state.attr('data-current-page');
        const totalAttr = $state.attr('data-total-pages');
        const page = Number(pageAttr || $state.data('current-page')) || 1;
        const totalPages = Number(totalAttr || $state.data('total-pages')) || 1;
        return { page, totalPages };
    }

    function updateIndicators(page, totalPages, appendedCount) {
        $('#current-page-indicator').text(page);
        let currentCount = parseInt($('#products-loaded-count').text()) || 0;
        if (appendedCount) {
            $('#products-loaded-count').text(currentCount + appendedCount);
            $('#final-products-count').text(currentCount + appendedCount);
        }
        if (page >= totalPages) {
            $('.infinite-scroll-end').show();
            $('.page-progress-indicator').hide();
        }
    }

    function loadNextPage() {
        if (isLoadingNext) return;
        const state = readStateFromDom();
        if (state.page >= state.totalPages) return;
        isLoadingNext = true;
        $('.infinite-scroll-loader').show();

        const $form = $('#fashion_products_list_form');
        // Prefer URL from DOM state if provided
    const $stateEl = $('.product-scroll-state');
    const domLoadMoreUrl = ($stateEl.data('load-more-url') || '').toString();
    const domAjaxUrl = ($stateEl.data('ajax-url') || '').toString();
    // Prefer lightweight load-more URL if available; fallback to filter endpoint
    const action = domLoadMoreUrl || domAjaxUrl || ($form.attr('action') || '');
        const formData = new FormData($form[0]);
        formData.set('page', state.page + 1);

        // Debug: log chosen URL & page when debug flag is on
        try {
            if (window.location.search.indexOf('debug=1') !== -1 || window.PRODUCTS_DEBUG === true) {
                console.log('[Products][Fashion] Loading next page', {
                    url: action,
                    page: state.page + 1,
                    totalPages: state.totalPages
                });
            }
        } catch(e){}

        $.ajax({
            url: action,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                let appendedCount = 0;
                // 1) If server returned only product cards (loadMore endpoint style)
                if (response && response.products_html) {
                    const html = response.products_html;
                    const $tmp = $('<div>').html(html);
                    appendedCount = $tmp.find('.product-card').length || $tmp.children().length;
                    $('.product-wrapper').append(html);
                } else {
                    // 2) Parse full partial HTML and extract cards
                    const $tempDiv = $('<div>').html((response && response.html_products) ? response.html_products : '');
                    let $cards = $tempDiv.find('.product-card.product-cart-option-container');
                    if ($cards.length === 0) {
                        // Relax selector just in case
                        $cards = $tempDiv.find('.product-card');
                    }
                    if ($cards.length > 0) {
                        let html = '';
                        $cards.each(function(){ html += this.outerHTML; });
                        $('.product-wrapper').append(html);
                        appendedCount = $cards.length;
                    }
                }

                if (window.location.search.indexOf('debug=1') !== -1 || window.PRODUCTS_DEBUG === true) {
                    try { console.log('[Products][Fashion] Append count:', appendedCount); } catch(e){}
                }

                if (appendedCount > 0) {
                    // Update state in DOM
                    const newPage = state.page + 1;
                    $('.product-scroll-state')
                        .attr('data-current-page', newPage)
                        .data('current-page', newPage);
                    updateIndicators(newPage, state.totalPages, appendedCount);
                } else {
                    // No real cards; end
                    $('.product-scroll-state')
                        .attr('data-current-page', state.totalPages)
                        .data('current-page', state.totalPages);
                    $('.infinite-scroll-end').show();
                    $('.page-progress-indicator').hide();
                }
            },
            complete: function(){
                isLoadingNext = false;
                $('.infinite-scroll-loader').hide();
            },
            error: function(){ isLoadingNext = false; $('.infinite-scroll-loader').hide(); }
        });
    }

    function onScroll() {
        const state = readStateFromDom();
        if (isLoadingNext || state.page >= state.totalPages) return;
        const threshold = 500; // px from bottom
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - threshold) {
            loadNextPage();
        }
    }

    // Expose a safe, public trigger for inline callers
    window.productsScrollForceCheck = function(forceLoad){
        try {
            if (forceLoad === true) {
                loadNextPage();
            } else {
                onScroll();
            }
        } catch (e) { /* noop */ }
    }

    window.initProductsInfiniteScroll = function() {
        // Reset and bind once
        $(window).off('scroll.productInfinite');
    const state = readStateFromDom();
    // If there's no more pages or no form, don't bind
    if (!$('#fashion_products_list_form').length) return;
        if (state.page < state.totalPages) {
            $('.infinite-scroll-loader, .page-progress-indicator').show();
            $('.infinite-scroll-end').hide();
            $(window).on('scroll.productInfinite', onScroll);
            // Trigger an immediate check to load if content is short or user already at bottom
            setTimeout(function(){
                if ($(document).height() <= $(window).height() + 10) {
                    loadNextPage();
                } else {
                    onScroll();
                }
            }, 0);
        } else {
            $('.infinite-scroll-loader, .page-progress-indicator').hide();
            $('.infinite-scroll-end').show();
        }
    }

    // Auto-init on ready (initial page load)
    $(function(){
    if ($('.product-scroll-state').length) {
            window.initProductsInfiniteScroll();
        }
    });
})();


// Product Add To Wishlist || Start
function addWishlist_function(product_id) {
    let action_url = $("#store_wishlist_url").data("url");
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });

    $.ajax({
        url: action_url,
        method: "POST",
        data: {
            product_id,
        },
        success: function (data) {
            if (data.value == 1) {
                toastr.success(data.success);
                $(`.wishlist_${product_id}`)
                    .removeClass("bi-heart")
                    .addClass("bi-heart-fill text-danger");
                $(".wishlist_count_status").html(
                    parseInt($(".wishlist_count_status").html()) + 1
                );
                $(".product_wishlist_count_status").html(
                    parseInt($(".product_wishlist_count_status").html()) + 1
                );
            } else if (data.value == 2) {
                $(`.wishlist_${product_id}`)
                    .removeClass("bi-heart-fill text-danger")
                    .addClass("bi-heart");
                $(".wishlist_count_status").html(
                    parseInt($(".wishlist_count_status").html()) - 1
                );
                $(".product_wishlist_count_status").html(
                    parseInt($(".product_wishlist_count_status").html()) - 1
                );
                toastr.success(data.error);
            } else {
                toastr.warning(data.error);
                $("#quickViewModal").modal("hide");
                customerLoginRegisterModalCall();
            }
        },
    });
}

function quickViewActionRender() {
    $(".quickView_action").on("click", function () {
        let id = $(this).data("id");
        quickView(id);
    });
}

function addWishlist_function_view_page() {
    $(".addWishlist_function_view_page").on("click", function () {
        let id = $(this).data("id");
        addWishlist_function(id);
    });
}

$(".addCompareList_view_page").on("click", function () {
    let id = $(this).data("id");
    addCompareList(id);
});

function productCommonActionForViewEvents() {
    $(".remove_tags_Category").on("click", function () {
        let id = $(this).data("id");
        $(".category_class_for_tag_" + id).click();
    });

    $(".remove_tags_Brand").on("click", function () {
        let id = $(this).data("id");
        $(".brand_class_for_tag_" + id).click();
    });

    $(".remove_tags_publishing_house").on("click", function () {
        let id = $(this).data("id");
        $(".publishing_house_class_for_tag_" + id).click();
    });

    $(".remove_tags_author_id").on("click", function () {
        let id = $(this).data("id");
        $(".authors_id_class_for_tag_" + id).click();
    });

    $(".remove_tags_review").on("click", function () {
        let id = $(this).data("id");
        $(".review_class_for_tag_" + id).click();
    });

    $(".remove_tags_sortBy").on("click", function () {
        $(".filter_by_product_list_web").val(["latest"]).trigger("change");
    });

    $(".store_vacation_check_function").on("click", function () {
        let id = $(this).data("id");
        let added_by = $(this).data("added_by");
        let user_id = $(this).data("user_id");
        let action_url = $(this).data("action_url");
        let product_cart_id = $(this).data("product_cart_id");
        store_vacation_check(
            id,
            added_by,
            user_id,
            action_url,
            product_cart_id
        );
    });

    quickViewActionRender();
    addWishlist_function_view_page();
}

$(window).on("load", function () {
    productCommonActionForViewEvents();
});

function resetAllInProductList() {
    $(".fashion_products_list_form_reset").on("click", function () {
        location.reload();
    });
}

resetAllInProductList();

$(".remove_wishlist_theme_fashion").on("click", function () {
    let url = $("#delete_wishlist_url").data("url");
    let product_id = $(this).data("productid");

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });
    $.ajax({
        url: url,
        method: "POST",
        data: {
            id: product_id,
        },
        beforeSend: function () {
            $("#loading").addClass("d-grid");
        },
        success: function (data) {
            Swal.fire({
                type: "success",
                title: $(".text-wishList").data("text"),
                text: data.success,
            });
            $(".row_id" + product_id).hide();
            $(".wishlist_count_status").html(
                parseInt($(".wishlist_count_status").html()) - 1
            );
            let currentRoute = $("#get-current-route-name").data("route");
            if (
                data.count <= 0 &&
                currentRoute &&
                currentRoute.toString() === "wishlists"
            ) {
                location.reload();
            }
        },
        complete: function () {
            $("#loading").removeClass("d-grid");
        },
    });
});

$(".action-global-search-mobile").on("keyup", function () {
    let value = $("#input-value-mobile").val();
    let id = $("#search_category_value_mobile").val();
    let class_name = "search-result-box-mobile";
    global_search(value, id, class_name);
});

$("#hide_search_toggle").on("click", function () {
    let value = 0;
    let id = null;
    let class_name = "search-result-box-mobile";
    global_search(value, id, class_name);
});

$("#input-value-web").on("keyup", function () {
    let value = $(this).val();
    let id = $("#search_category_value_web").val();
    let class_name = "search-result-box-web";
    global_search(value, id, class_name);
    $(".search_input_name").val(value);
});

// Search Field Popup Actions || Start
function global_search(value, id, class_name) {
    $(".search-card").removeClass("d-none").addClass("d-block");
    let name = value;
    let category_id = id;
    let class__name = class_name;
    let base_url = $('meta[name="base-url"]').attr("content");
    if (name.length > 0) {
        $.get({
            url: base_url + "/searched-products",
            dataType: "json",
            data: {
                name,
                category_id,
            },
            beforeSend: function () {
                $("#loading").addClass("d-grid");
            },
            success: function (data) {
                let html = (data && data.result) ? data.result : '';
                if (data && data.fuzzy) {
                    html += data.fuzzy;
                }
                $("." + class__name)
                    .empty()
                    .html(html);
            },
            complete: function () {
                $("#loading").removeClass("d-grid");
            },
        });
    } else {
        $("." + class__name)
            .empty()
            .removeClass("d-block")
            .addClass("d-none");
    }
}

// Search Field Popup Actions || End
$(".activeFilterNav").on("click", function () {
    let key = $(this).data("key");
    $("#fashion_products_list_form").trigger("reset");
    $('.search_input_data_from').val($(this).data('value'));
    // inputTypeNumberClick(1);
    $(".products_navs_list").find('input').removeAttr("checked");
    $(".products_navs_list li label").removeClass("active");
    $("#" + key).attr("checked", true);
    $("." + key).addClass("active");
    $("#fashion_products_list_form").submit();
    $(".form-check-subgroup").css("display", "none");
});

function applyCouponThemeFashion() {
    $("#coupon_code_theme_fashion").on("click", function () {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
            },
        });
        $.ajax({
            type: "POST",
            url: $("#coupon-apply").data("url"),
            data: $("#coupon-code-ajax").serializeArray(),
            success: function (data) {
                if (data.status == 1) {
                    let ms = data.messages;
                    ms.forEach(function (m, index) {
                        toastr.success(m, index, {
                            CloseButton: true,
                            ProgressBar: true,
                        });
                    });
                } else {
                    let ms = data.messages;
                    ms.forEach(function (m, index) {
                        toastr.error(m, index, {
                            CloseButton: true,
                            ProgressBar: true,
                        });
                    });
                }
                setTimeout(function () {
                    location.reload();
                }, 2000);
            },
        });
    });
}

applyCouponThemeFashion();

function goToPageBasedSelectValue(link) {
    location.href = link;
}

function formResetByClassOrID(ClassOrIDName) {
    $(ClassOrIDName).trigger("reset");
    $(ClassOrIDName + " input").val("");
    $(".search_input_store").val("");
}

$('.form--check-inner input[type="checkbox"]').change(function () {
    var isChecked = $(this).prop("checked");
    var $subgroup = $(this)
        .closest(".form--check-inner")
        .siblings(".form-check-subgroup");
    if (!$(this).prop("checked")) {
        $subgroup.find('input[type="checkbox"]').prop("checked", false);
    }
});

function from_reset_by_className(classname, redirect_url = null) {
    $(`.${classname} input`).val("");
    redirect_url != null ? (window.location.href = redirect_url) : "";
}

// ==== start owl carousel for images ====
function owl_carousel_quick_view() {
    var sync1 = $("#sync1");
    var sync2 = $("#sync2");
    var thumbnailItemClass = ".owl-item";
    var slides = sync1
        .owlCarousel({
            startPosition: 12,
            items: 1,
            loop: false,
            margin: 0,
            mouseDrag: true,
            touchDrag: true,
            pullDrag: false,
            scrollPerPage: true,
            autoplayHoverPause: false,
            nav: false,
            dots: false,
        })
        .on("changed.owl.carousel", syncPosition);

    function syncPosition(el) {
        var owl_slider = $(this).data("owl.carousel");
        var loop = owl_slider.options.loop;

        if (loop) {
            var count = el.item.count - 1;
            var current = Math.round(el.item.index - el.item.count / 2 - 0.5);
            if (current < 0) {
                current = count;
            }
            if (current > count) {
                current = 0;
            }
        } else {
            var current = el.item.index;
        }

        var owl_thumbnail = sync2.data("owl.carousel");
        var itemClass = "." + owl_thumbnail.options.itemClass;

        var thumbnailCurrentItem = sync2
            .find(itemClass)
            .removeClass("synced")
            .eq(current);
        thumbnailCurrentItem.addClass("synced");

        if (!thumbnailCurrentItem.hasClass("active")) {
            var duration = 500;
            sync2.trigger("to.owl.carousel", [current, duration, true]);
        }
    }

    var thumbs = sync2
        .owlCarousel({
            startPosition: 12,
            items: 6,
            loop: false,
            margin: 10,
            autoplay: false,
            nav: false,
            dots: false,
            // rtl: true,
            responsive: {
                576: {
                    items: 4,
                },
                768: {
                    items: 5,
                },
                992: {
                    items: 5,
                },
                1200: {
                    items: 6,
                },
                1400: {
                    items: 7,
                },
            },
            onInitialized: function (e) {
                var thumbnailCurrentItem = $(e.target)
                    .find(thumbnailItemClass)
                    .eq(this._current);
                thumbnailCurrentItem.addClass("synced");
            },
        })
        .on("click", thumbnailItemClass, function (e) {
            e.preventDefault();
            var duration = 500;
            var itemIndex = $(e.target).parents(thumbnailItemClass).index();
            sync1.trigger("to.owl.carousel", [itemIndex, duration, true]);
        })
        .on("changed.owl.carousel", function (el) {
            var number = el.item.index;
            var owl_slider = sync1.data("owl.carousel");
            owl_slider.to(number, 500, true);
        });
    sync1.owlCarousel();
}

// ==== end owl carousel for images ====

// ==== start increment decrement btn ====
function inc_dec_btn_quick_view() {
    var CartPlusMinus = $(".inc-inputs");
    CartPlusMinus.prepend(
        '<div class="dec qtyBtn text-base"><i class="bi bi-dash-lg"></i></div>'
    );
    CartPlusMinus.append(
        '<div class="inc qtyBtn text-base"><i class="bi bi-plus-lg"></i></div>'
    );
    $(".qtyBtn").on("click", function () {
        var $button = $(this);
        var oldValue = parseFloat($button.parent().find("input").val());
        var oldMaxValue = parseInt($button.parent().find("input").attr("max"));
        var oldMinValue = parseInt($button.parent().find("input").attr("min"));
        var outofstock = $(".add_to_cart_form").data("outofstock");
        var newVal = oldValue;
        if ($(this).hasClass("inc")) {
            if (oldValue < oldMaxValue) {
                newVal = oldValue + 1;
                $(".qtyBtn").removeClass("disabled");
                $(".qtyBtn").addClass("text-base");
            } else {
                $(".qtyBtn").addClass("disabled");
                $(".qtyBtn").removeClass("text-base");
                toastr.warning(outofstock);
            }
        } else {
            if (oldValue > oldMinValue) {
                $(".qtyBtn").removeClass("disabled");
                $(".qtyBtn").addClass("text-base");
                newVal = oldValue - 1;
            } else {
                newVal = oldMinValue;
                let minimumOrderQuantityMessage = $(".minimum_order_quantity_msg").data("text");
                toastr.error(minimumOrderQuantityMessage + " " + oldMinValue);
            }
        }
        $button.parent().find("input").val(newVal);
    });
}

// ==== end increment decrement btn ====

// ==== Product Share Link Generator JS || Start ====
function shareOnFacebook(url, social) {
    var width = 600,
        height = 400,
        left = (screen.width - width) / 2,
        top = (screen.height - height) / 2;
    window.open(
        "https://" + social + encodeURIComponent(url),
        "Popup",
        "toolbar=0,status=0,width=" +
        width +
        ",height=" +
        height +
        ",left=" +
        left +
        ",top=" +
        top
    );
}

// ==== Product Share Link Generator JS || End ====

// ==== Start Otp Verification Js ====
$(document).ready(function () {
    $(".otp-form button[type=submit]").attr("disabled", true);
    $(".otp-form *:input[type!=hidden]:first").focus();
    let otp_fields = $(".otp-form .otp-field"),
        otp_value_field = $(".otp-form .otp-value");
    otp_fields
        .on("input", function (e) {
            $(this).val(
                $(this)
                    .val()
                    .replace(/[^0-9]/g, "")
            );
            let otp_value = "";
            otp_fields.each(function () {
                let field_value = $(this).val();
                if (field_value != "") otp_value += field_value;
            });
            otp_value_field.val(otp_value);
            // Check if all input fields are filled
            if (otp_value.length === 6) {
                $(".otp-form button[type=submit]").attr("disabled", false);
            } else {
                $(".otp-form button[type=submit]").attr("disabled", true);
            }
        })
        .on("keyup", function (e) {
            let key = e.keyCode || e.charCode;
            if (key == 8 || key == 46 || key == 37 || key == 40) {
                // Backspace or Delete or Left Arrow or Down Arrow
                $(this).prev().focus();
            } else if (key == 38 || key == 39 || $(this).val() != "") {
                // Right Arrow or Top Arrow or Value not empty
                $(this).next().focus();
            }
        })
        .on("paste", function (e) {
            let paste_data = e.originalEvent.clipboardData.getData("text");
            let paste_data_splitted = paste_data.split("");
            $.each(paste_data_splitted, function (index, value) {
                otp_fields.eq(index).val(value);
            });
        });
});
// ==== End Otp Verification Js ====

// ====Start Count Down Js====
function countdown() {
    var counter = $(".verifyCounter");
    var seconds = counter.data("second");

    function tick() {
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        seconds--;
        counter.html(m + ":" + (s < 10 ? "0" : "") + String(s));
        if (seconds > 0) {
            setTimeout(tick, 1000);
            $(".resend-otp-button").attr("disabled", true);
            $(".resend_otp_custom").slideDown();
        } else {
            $(".resend-otp-button").removeAttr("disabled");
            $(".verifyCounter").html("0:00");
            $(".resend_otp_custom").slideUp();
        }
    }

    tick();
}

function store_vacation_check(
    id,
    added_by,
    user_id,
    action_url,
    product_cart_id
) {
    $.get(
        {url: action_url},
        {id: id, added_by: added_by, user_id: user_id},
        (response) => {
        }
    ).then((response) => {
        if (response.status === "active") {
        } else if (response.status == 1 || response.status == 0) {
            response.status == 1
                ? toastr.success(response.message)
                : toastr.error(response.message);
            updateNavCart();
        } else {
            toastr.error(
                $(".text-custom-storage").data("textshoptemporaryclose")
            );
        }
    });
}

$(".order_again_function").on("click", function () {
    let order_id = $(this).data("orderid");

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });
    $.ajax({
        type: "POST",
        url: $("#order_again_url").data("url"),
        data: {
            order_id,
        },
        beforeSend: function () {
            $("#loading").addClass("d-grid");
        },
        success: function (response) {
            if (response.status === 1) {
                updateNavCart();
                toastr.success(response.message, {
                    CloseButton: true,
                    ProgressBar: true,
                    timeOut: 2000, // duration
                });
                $("#quickViewModal").modal("hide");
                // location.href = response.redirect_url;
                // return false;
            } else if (response.status === 0) {
                toastr.warning(response.message, {
                    CloseButton: true,
                    ProgressBar: true,
                    timeOut: 2000, // duration
                });
                return false;
            }
        },
        complete: function () {
            $("#loading").removeClass("d-grid");
        },
    });
});

toastr.options = {
    positionClass: "toast-top-right",
};

var errorMessages = {
    valueMissing: $(".please_fill_out_this_field").data("text"),
};

$("input").each(function () {
    var $el = $(this);

    $el.on("invalid", function (event) {
        var target = event.target,
            validity = target.validity;
        target.setCustomValidity("");
        if (!validity.valid) {
            if (validity.valueMissing) {
                target.setCustomValidity(
                    $el.data("errorRequired") || errorMessages.valueMissing
                );
            }
        }
    });
});

$("textarea").each(function () {
    var $el = $(this);

    $el.on("invalid", function (event) {
        var target = event.target,
            validity = target.validity;
        target.setCustomValidity("");
        if (!validity.valid) {
            if (validity.valueMissing) {
                target.setCustomValidity(
                    $el.data("errorRequired") || errorMessages.valueMissing
                );
            }
        }
    });
});

$(document).on("click", "#cookie-accept", function () {
    document.cookie =
        "6valley_cookie_consent=accepted; max-age=" + 60 * 60 * 24 * 30;
    $("#cookie-section").hide();
});
$(document).on("click", "#cookie-reject", function () {
    document.cookie = "6valley_cookie_consent=reject; max-age=" + 60 * 60 * 24;
    $("#cookie-section").hide();
});

$(document).ready(function () {
    if (document.cookie.indexOf("6valley_cookie_consent=accepted") !== -1) {
        $("#cookie-section").hide();
    } else {
        $("#cookie-section").html(cookie_content).show();
    }

    try {
        // Check if Firebase function exists before calling it
        if (typeof initializeFirebaseGoogleRecaptcha === 'function') {
            initializeFirebaseGoogleRecaptcha('recaptcha-container-verify-token', 'Token Verification');
        } else {
            // Silently skip if not available to avoid console noise
        }
    } catch (e) {
        // Silently skip errors from optional Recaptcha init
    }
});

function route_alert(route, message, type = null) {
    if (type == "order-cancel") {
        $("#reset_btn").empty().html($(".text-custom-storage").data("textno"));
        $("#delete_button")
            .empty()
            .html($(".text-custom-storage").data("textyes"));
    }
    $("#alert_message").empty().append(message);
    $("#delete_button").attr("href", route);
    $("#status-warning-modal").modal("show");
}

$(".route_alert_function").on("click", function () {
    let route_name = $(this).data("routename");
    let message = $(this).data("message");
    let type = $(this).data("typename");
    route_alert(route_name, message, type);
});

$(".select2-init2").select2({
    dropdownParent: $("#offcanvasRight"),
});

$(".select2-init-js").select2({
    dropdownParent: $(".sidebar"),
});



function renderOfferBarFunction(sectionSelector) {
    let hideUntil = localStorage.getItem("offerBarHideUntil");
    if (hideUntil) {
        let currentTime = Date.now();
        if (currentTime < hideUntil) {
            $(sectionSelector).slideUp("fast");
        } else {
            $(sectionSelector).slideDown("fast");
            localStorage.removeItem("offerBarHideUntil");
        }
    } else {
        $(sectionSelector).slideDown("fast");
    }
}

$("#add-fund-amount-input").on("keyup", function () {
    if ($(this).val() == "") {
        $("#add-fund-list-area").slideUp();
    } else {
        $("#add-fund-list-area").slideDown();
    }
});
const themeDirection = $("html").attr("dir");

$(".add-fund-slider").owlCarousel({
    loop: true,
    autoplay: true,
    autoplayTimeout: 3000,
    autoplayHoverPause: true,
    smartSpeed: 800,
    items: 1,
    rtl: themeDirection && themeDirection.toString() === "rtl",
});

$(".click_to_copy_coupon_function").on("click", function () {
    let copyCode = $(this).data("copycode");
    navigator.clipboard
        .writeText(copyCode)
        .then(function () {
            toastr.success($(".text-custom-storage").data("textsuccessfullycopied"));
        })
        .catch(function (error) {
            toastr.error($("#message-copied-failed").data("text"));
        });
});

$(".click_to_copy_function").on("click", function () {
    let copied_text = $(this).data("copycode");
    let tempTextarea = $("<textarea>");
    $("body").append(tempTextarea);
    tempTextarea.val(copied_text).select();
    document.execCommand("copy");
    tempTextarea.remove();
    toastr.success($(".text-custom-storage").data("textsuccessfullycopied"));
});

$(".thisIsALinkElement").on("click", function () {
    if ($(this).data("linkpath")) {
        location.href = $(this).data("linkpath");
    }
});

$(".offer-bar-close").on("click", function (e) {
    $(this).parents(".offer-bar").slideUp("slow");
    let hideUntil = Date.now() + 300000;
    localStorage.setItem("offerBarHideUntil", hideUntil);
});

$(".minimum_Order_Amount_message").on("click", function () {
    let message = $(this).data("bs-title");
    toastr.warning(message, {
        CloseButton: true,
        ProgressBar: true,
    });
});

$("#add_fund_to_wallet_form_btn").on("click", function () {
    if (!$("input[name='payment_method']:checked").val()) {
        toastr.error(
            $(".text-custom-storage").data("textpleaseselectpaymentmethods")
        );
    }
});

$("#exchange-amount-input").on("keyup", function () {
    let convertedAmount = $(this).val();
    if (convertedAmount > 0) {
        $(".converted_amount_text").removeClass("d-none");
    }
    $.get($(this).data("route"), {amount: convertedAmount}, (response) => {
        $(".converted_amount").empty().html(response);
    });
});

$("#chat-form").on("submit", function (event) {
    event.preventDefault();
    let message = $(this).data("message");
    $.ajax({
        type: "post",
        url: $(this).attr("action"),
        data: $(this).serialize(),
        success: function (response) {
            $(this).trigger("reset");
            $('#chat-form [name="message"]').val("");
            $("#chatModal").modal("hide");
            toastr.success(message);
        },
    });
});

function review_message() {
    let message = $(".text-custom-storage").data("reviewmessage");
    toastr.info(message, {
        CloseButton: true,
        ProgressBar: true,
    });
}

function refund_message() {
    let message = $(".text-custom-storage").data("refundmessage");
    toastr.info(message, {
        CloseButton: true,
        ProgressBar: true,
    });
}

function renderCheckoutAction() {
    $(".checkout_action").on("click", function () {
        let order_note = $("#order_note").val();
        $.post({
            url: $("#order_note_url").data("url"),
            data: {
                _token: $('meta[name="_token"]').attr("content"),
                order_note: order_note,
            },
            beforeSend: function () {
                $("#loading").addClass("d-grid");
                $(this).attr("disabled", true);
            },
            success: function (response) {
                if (response.status === 0) {
                    response.message.map(function (message) {
                        toastr.error(message);
                    });
                } else {
                    location.href = response.redirect
                        ? response.redirect
                        : $("#route-checkout-details").data("url");
                }
            },
            complete: function () {
                $("#loading").removeClass("d-grid");
                $(this).removeAttr("disabled");
            },
        });
    });
}

renderCheckoutAction();

$("#customer_auth_resend_otp").on("click", function () {
    $("input.otp-field").val("");
    let user_id = $("#user_id").val();
    let form_url = $(this).data("url");
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });
    $.ajax({
        url: form_url,
        method: "POST",
        dataType: "json",
        data: {
            user_id: user_id,
        },
        beforeSend: function () {
            $("#loading").addClass("d-grid");
        },
        success: function (data) {
            if (data.status == 1) {
                let newCounter = $(".verifyCounter");
                let new_seconds = data.new_time;

                function new_tick() {
                    let m = Math.floor(new_seconds / 60);
                    let s = new_seconds % 60;
                    new_seconds--;
                    newCounter.html(m + ":" + (s < 10 ? "0" : "") + String(s));
                    if (new_seconds > 0) {
                        setTimeout(new_tick, 1000);
                        $(".resend-otp-button").attr("disabled", true);
                        $(".resend_otp_custom").slideDown();
                    } else {
                        $(".resend-otp-button").removeAttr("disabled");
                        newCounter.html("0:00");
                        $(".resend_otp_custom").slideUp();
                    }
                }

                new_tick();

                toastr.success($(".text-otp-related").data("otpsendagain"));
            } else {
                toastr.error($(".text-otp-related").data("otpnewcode"));
            }
        },
        complete: function () {
            $("#loading").removeClass("d-grid");
        },
    });
});

function initTooltip() {
    var tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    $(".minimum_Order_Amount_message").on("click", function () {
        let message = $(this).data("bs-title");
        toastr.warning(message, {
            CloseButton: true,
            ProgressBar: true,
        });
    });
}

$(".goToPageBasedSelectValue").on("change", function () {
    let value = $(this).val();
    goToPageBasedSelectValue(value);
});

function getPlaceHolderImages() {
    $(".onerror-placeholder-image").on("error", function () {
        let image = $("#onerror-placeholder-image").data("image-path");
        $(this).attr("src", image);
    });
}

getPlaceHolderImages();

function passwordToTextType() {
    $(".js-password-toggle").each(function () {
        $(this).siblings("input:password").css("padding-inline-end", "40px");

        if ($(this).hasClass("type-password")) {
            $(this).html('<i class="bi fill"></i>');
        } else {
            $(this).html('<i class="bi bi-eye-slash-fill"></i>');
        }

        $(this).on("click", function () {
            const sibling = $(this).siblings("input");
            if (sibling.hasClass("type-password")) {
                sibling.removeClass("type-password");
                               sibling.attr("type", "password");
                $(this).html('<i class="bi bi-eye-slash-fill"></i>');
            } else {
                sibling.addClass("type-password");
                sibling.attr("type", "text");
                $(this).html('<i class="bi bi-eye-fill"></i>');
            }
        });
    });
}

passwordToTextType();

function customerLoginRegisterModalCall() {
    $.ajax({
        url: $("#get-login-modal-data").data("route"),
        method: "GET",
        beforeSend: function () {
            $("#loading").addClass("d-grid");
        },
        success: function (data) {
            $("#login-and-register-modal-section").html(data.login_modal);
            $("#login-and-register-modal-section").append(
                data.register_modal
            );
            $("#SignInModal").modal("show");

            try {
                initializePhoneInput(
                    ".phone-input-with-country-picker",
                    ".country-picker-phone-number"
                );
            } catch (e) {

            }

            passwordToTextType();
        },
        complete: function () {
            $("#loading").removeClass("d-grid");
        },
    });
}

function customerSignUpModalCall() {
    let requestURL = $("#get-login-modal-data").data("route");
    const currentUrl = new URL(window.location.href);
    const referralCodeParameter = new URLSearchParams(currentUrl.search).get("referral_code");

    if (referralCodeParameter) {
        requestURL = $("#get-login-modal-data").data("route") + '?referral_code=' + referralCodeParameter;
    }

    $.ajax({
        url: requestURL,
        method: "GET",
        beforeSend: function () {
            $("#loading").addClass("d-grid");
        },
        success: function (data) {
            $("#login-and-register-modal-section").html(data.login_modal);
            $("#login-and-register-modal-section").append(
                data.register_modal
            );
            $("#SignUpModal").modal("show");
            initializePhoneInput(
                ".phone-input-with-country-picker",
                ".country-picker-phone-number"
            );
            passwordToTextType();
        },
        complete: function () {
            $("#loading").removeClass("d-grid");
        },
    });
}

document.addEventListener("click", function (event) {
    $(".search-result-box-web").addClass("d-none");
});

function readMoreCurrentReview() {
    $(".read-more-current-review").on("click", function () {
        let element = $(this).data("element");
        $(element).removeClass("max-height-fixed");
        $(element + "-primary").addClass("d--none");
        $(element + "-hidden").removeClass("d--none");
    });
}

readMoreCurrentReview();

function renderCustomImagePopup() {
    $(".custom-image-popup-init").each(function () {
        $(this)
            .find(".custom-image-popup")
            .magnificPopup({
                type: "image",
                closeOnContentClick: false,
                closeBtnInside: false,
                mainClass: "mfp-with-zoom mfp-img-mobile",
                image: {
                    verticalFit: true,
                    titleSrc: function (item) {
                        return (
                            item.el.attr("title") +
                            ' &middot; <a class="image-source-link" href="' +
                            item.el.attr("data-source") +
                            '" target="_blank">image source</a>'
                        );
                    },
                },
                gallery: {
                    enabled: true,
                },
                zoom: {
                    enabled: true,
                    duration: 300,
                    opener: function (element) {
                        return element.find("img");
                    },
                },
            });
    });
}

renderCustomImagePopup();

$(".close-element-onclick-by-data").on("click", function () {
    $($(this).data("selector")).slideUp().fadeOut();
});

function playAudio() {
    document.getElementById("myAudio").play();
}

function multipleCheckBoxFunctionsInit() {
    $(document).ready(function () {
        $(".cart_information").each(function () {
            let allShopItemsInChecked = true;
            $(this)
                .find(".shop-item-check")
                .each(function () {
                    if (!$(this).prop("checked")) {
                        allShopItemsInChecked = false;
                        return false;
                    }
                });
            $(this)
                .find(".shop-head-check")
                .prop("checked", allShopItemsInChecked);
        });
    });

    $(".shop-head-check").on("change", function () {
        $(this)
            .parents(".cart_information")
            .find(".shop-item-check")
            .prop("checked", this.checked);
    });

    $(".shop-item-check").on("change", function () {
        var allChecked = true;

        $(this)
            .parents(".cart_information")
            .find(".shop-item-check")
            .each(function () {
                if (!$(this).prop("checked")) {
                    allChecked = false;
                    return false;
                }
            });

        $(this)
            .parents(".cart_information")
            .find(".shop-head-check")
            .prop("checked", allChecked);
    });

    $(".shop-head-check-desktop").on("change", function () {
        getCartSelectCartItemsCheckedValues(
            '.cart_information input[type="checkbox"].shop-item-check-desktop'
        );
    });

    $(".shop-head-check-mobile").on("change", function () {
        getCartSelectCartItemsCheckedValues(
            '.cart_information input[type="checkbox"].shop-item-check-mobile'
        );
    });

    $(".shop-item-check-desktop").on("change", function () {
        getCartSelectCartItemsCheckedValues(
            '.cart_information input[type="checkbox"].shop-item-check-desktop'
        );
    });

    $(".shop-item-check-mobile").on("change", function () {
        getCartSelectCartItemsCheckedValues(
            '.cart_information input[type="checkbox"].shop-item-check-mobile'
        );
    });

    function getCartSelectCartItemsCheckedValues(elementSelector) {
        let checkedValues = [];
        $(elementSelector).each(function () {
            if ($(this).prop("checked")) {
                checkedValues.push($(this).val());
            }
        });
        getCartSelectCartItemsRequest(checkedValues);
    }

    function getCartSelectCartItemsRequest(checkedValues) {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
            },
        });
        $.ajax({
            url: $("#get-cart-select-cart-items").data("route"),
            type: "POST",
            data: {
                ids: checkedValues,
            },
            beforeSend: function () {
                $("#loading").addClass("d-grid");
            },
            success: function (response) {
                $("#cart-summary").empty().html(response.htmlView);
                toastr.success(response.message);
                initTooltip();
                set_shipping_id_function();
                updateCartQuantityList_cart_data();
                updateCartQuantityListMobile_cart_data();
                updateAddToCartByVariationWebCommon();
                applyCouponThemeFashion();
                updateAddToCartByVariationWebCommon();
                renderCheckoutAction();
                multipleCheckBoxFunctionsInit();
                getInitRouteAlertFunction();
            },
            complete: function () {
                $("#loading").removeClass("d-grid");
            },
        });
    }
}

multipleCheckBoxFunctionsInit();

function getInitRouteAlertFunction() {
    $(".route_alert_function").on("click", function () {
        let route_name = $(this).data("routename");
        let message = $(this).data("message");
        let type = $(this).data("typename");
        route_alert(route_name, message, type);
    });
}

$(".remove-img-row-by-key").on("click", function () {
    let reviewId = $(this).data("review-id");
    let getPhoto = $(this).data("photo");
    let key = $(this).data("key");

    $.ajaxSetup({
        headers: {"X-CSRF-TOKEN": $('meta[name="_token"]').attr("content")},
    });
    $.ajax({
        type: "POST",
        url: $(this).data("route"),
        data: {
            id: reviewId,
            name: getPhoto,
        },
        success: function (response) {
            if (response.message) {
                toastr.success(response.message);
            }
            $(".img-container-" + key).remove();
        },
    });
});

$(".getDownloadFileUsingFileUrl").on("click", function () {
    let getLink = $(this).data("file-path");
    downloadFileUsingFileUrl(getLink);
});

function downloadFileUsingFileUrl(url) {
    fetch(url)
        .then((response) => response.blob())
        .then((blob) => {
            const filename = url.substring(url.lastIndexOf("/") + 1);
            const blobUrl = window.URL.createObjectURL(new Blob([blob]));
            const link = document.createElement("a");
            link.href = blobUrl;
            link.setAttribute("download", filename);
            document.body.appendChild(link);
            link.click();
            link.parentNode.removeChild(link);
        })
        .catch((error) => console.error("Error downloading file:", error));
}

function getViewByOnclick() {
    $('.get-view-by-onclick').on('click', function () {
        location.href = $(this).data('link');
    });
}

getViewByOnclick();


function responseManager(response) {
    if (response.status === 'success') {
        if (response.message) {
            toastr.success(response.message);
        }
        if (response?.redirectRoute) {
            location.href = response.redirectRoute;
        } else if (response?.redirect_url) {
            location.href = response?.redirect_url;
        }
    } else if (response.status === 'error') {
        if (response.message) {
            toastr.error(response.message);
        }
    } else if (response.status === 'warning') {
        if (response.message) {
            toastr.warning(response.message);
        }
    }

    if (response.errors) {
        for (
            let index = 0;
            index < response.errors.length;
            index++
        ) {
            toastr.error(response.errors[index].message, {
                CloseButton: true,
                ProgressBar: true,
            });
        }
    } else if (response.error) {
        toastr.error(response.error, {
            CloseButton: true,
            ProgressBar: true,
        });
    }

    if (response?.reload) {
        location.reload();
    }
}

$('.clean-phone-input-value').on("input", function () {
    $(this).val($(this).val().replace(/\s/g, ""));
});

$(".submitVerifyForm").on('click', function () {
    let formElement = $(this).closest('form');
    formElement.attr('action', formElement.data('verify'));
    $(this).closest('form').submit();
});

$(".resendVerifyForm").on('click', function () {
    let formElement = $(this).closest('form');
    formElement.attr('action', formElement.data('resend'));
    $(this).closest('form').submit();
});

function actionRequestForProductRestockFunctionality() {
    $(".product-restock-request-button").on("click", function () {
        let isLoggedIn = $(this).data('auth')?.toString();
        if (isLoggedIn === 'true' || isLoggedIn === '1') {
            let parentElement = $(this).closest('.product-cart-option-container');
            let productCartForm = parentElement.find('.addToCartDynamicForm');
            let getFrom = $(this).data('form')?.toString();

            if (productCartForm?.length <= 0 && getFrom && getFrom !== '') {
                productCartForm = $(getFrom);
            }
            getRequestForProductRestock(productCartForm);
        } else {
            $("#quickViewModal").modal("hide");
            toastr.warning($('.login-warning').data('login-warning-message'));
            customerLoginRegisterModalCall();
        }
    });
}

actionRequestForProductRestockFunctionality();

function getRequestForProductRestock(formElement) {
    let button = $(".product-restock-request-button");
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });
    $.ajax({
        url: $("#route-product-restock-request").data("url"),
        type: "POST",
        data: formElement.serializeArray(),
        beforeSend: function () {
            $("#loading").addClass("d-grid");
        },
        success: function (response) {
            responseManager(response)
            button.attr('disabled', true);
            button.text(button.data('requested'));

            try {
                startFCM([response?.fcm_topic]);
            } catch (e) {
                console.log(e)
            }
        },
        complete: function () {
            $("#loading").removeClass("d-grid");
        },
    });
}

function productRestockStockLimitStatus(response) {
    let mainElement = $('.product-restock-stock-alert');
    mainElement.find('.title').html(response?.title);
    mainElement.find('.image').attr("width", 50).attr('src', response?.image);
    mainElement.find('.message').html(response?.body);
    mainElement.find('.product-link').attr('data-link', response?.route);
    mainElement.addClass("active");
    setTimeout(() => {
        mainElement.removeClass("active");
    }, 100000)
}

$(".product-restock-stock-close").on("click", function () {
    $(".product-restock-stock-alert").removeClass("active");
});

$(".call-route-alert").on("click", function () {
    let route = $(this).data("route");
    let message = $(this).data("message");
    route_alert(route, message);
});

$(".product-details-sticky-collapse-btn").on("click", function () {
    $(this).toggleClass('rotate-180');
    $('.product-details-sticky-top').slideToggle('slow');
})

$('.only-integer-input-field').on("keypress keyup change", function (event) {
    let inputValue = $(this).val();
    if (event.type === "keypress" && (event.which < 48 || event.which > 57) && event.which !== 46) {
        event.preventDefault();
    }
    if ((inputValue.match(/\./g) || []).length > 1) {
        $(this).val(inputValue.slice(0, inputValue.lastIndexOf('.')));
    }
    $(this).val(inputValue.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1'));
});

$('.form-loading-button-form').on('submit', function () {
    $(this).find('[type="submit"]').addClass('active-loading').attr('disabled', true);
    $(this).find('.form-cancel-button').attr('disabled', true);
});
