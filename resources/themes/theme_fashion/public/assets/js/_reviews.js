"use strict";
$(function () {
    let orderDetailsReviewMessage = $('#order-details-review-message');
    $(".coba").spartanMultiImagePicker({
            fieldName: 'fileUpload[]',
            maxCount: 5,
            rowHeight: '150px',
            groupClassName: 'col-md-4',
            placeholderImage: {
                image: orderDetailsReviewMessage?.data('placeholder'),
            width: '100%'
        },
        dropFileLabel: orderDetailsReviewMessage?.data('drop-here'),
        onAddRow: function (index, file) {

    },
    onRenderedPreview: function (index) {

    },
    onRemoveRow: function (index) {

    },
    onExtensionErr: function (index, file) {
        toastr.error(orderDetailsReviewMessage?.data('input-type'), {
            CloseButton: true,
            ProgressBar: true
        });
    },
    onSizeErr: function (index, file) {
        toastr.error(orderDetailsReviewMessage?.data('on-big-size-file'), {
            CloseButton: true,
            ProgressBar: true
        });
    }
});
});

$(function () {
    $(".coba_refund").spartanMultiImagePicker({
            fieldName: 'images[]',
            maxCount: 5,
            rowHeight: '150px',
            groupClassName: 'col-md-4',
            maxFileSize: '',
            placeholderImage: {
                image: orderDetailsReviewMessage?.data('placeholder'),
            width: '100%'
        },
        dropFileLabel: orderDetailsReviewMessage?.data('drop-here'),
        onAddRow: function (index, file) {

    },
    onRenderedPreview: function (index) {

    },
    onRemoveRow: function (index) {

    },
    onExtensionErr: function (index, file) {
        toastr.error(orderDetailsReviewMessage?.data('input-type'), {
            CloseButton: true,
            ProgressBar: true
        });
    },
    onSizeErr: function (index, file) {
        toastr.error(orderDetailsReviewMessage?.data('on-big-size-file'), {
            CloseButton: true,
            ProgressBar: true
        });
    }
});
});
