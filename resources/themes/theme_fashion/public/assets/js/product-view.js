// "use strict";

let productListPageBackup = $('.products-search-data-backup');
let productListPageData = {
    page: productListPageBackup.data('page'),
    id: productListPageBackup.data('id'),
    name: productListPageBackup.data('name'),
    brand_id: productListPageBackup.data('brand'),
    category_id: productListPageBackup.data('category'),
    data_from: productListPageBackup.data('from'),
    min_price: productListPageBackup.data('min-price'),
    max_price: productListPageBackup.data('max-price'),
    sort_by: productListPageBackup.data('sort_by'),
    product_type: productListPageBackup.data('product-type'),
    vendor_id: productListPageBackup.data('vendor-id'),
    author_id: productListPageBackup.data('author-id'),
    publishing_house_id: productListPageBackup.data('publishing-house-id'),
    search_category_value: productListPageBackup.data('search-category-value'),
};

$('.filter-on-product-type-change').on('click', function () {
    productListPageData.product_type = $(this).data('value');
    $(".filter-on-product-type-button").html($(this).text());

    if ($(this).data('value')?.toString() === 'digital') {
        $('.product-type-physical-checkbox').prop('checked', false);
        $('#product_type_digital').prop('checked', true);
    } else if ($(this).data('value')?.toString() === 'physical') {
        $('.product-type-digital-checkbox').prop('checked', false);
        $('#product_type_physical').prop('checked', true);
    } else {
        $('#product_type_all').prop('checked', true);
    }

    listPageProductTypeCheck();

    try {
        inputTypeNumberClick(1);
        fashion_products_list_form_common();
    } catch (error) {

    }
});

function listPageProductTypeCheck() {
    if (productListPageData?.product_type?.toString() === 'digital') {
        $('.product-type-digital-section').show();
        $('.product-type-physical-section').hide();
    } else if (productListPageData?.product_type?.toString() === 'physical') {
        $('.product-type-digital-section').hide();
        $('.product-type-physical-section').show();
    } else {
        $('.product-type-physical-section').show();
        $('.product-type-digital-section').show();
    }
}
listPageProductTypeCheck();
