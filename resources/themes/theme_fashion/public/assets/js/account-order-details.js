"use strict";
$(function () {
    let accountOrderDetailsReviewMessage = $('#account-order-details-review-message');
    $(".coba").spartanMultiImagePicker({
        fieldName: 'fileUpload[]',
        maxCount: 5,
        rowHeight: '150px',
        groupClassName: 'col-md-4',
        placeholderImage: {
            image: accountOrderDetailsReviewMessage?.data('placeholder'),
            width: '100%'
        },
        dropFileLabel: accountOrderDetailsReviewMessage?.data('drop-here'),
        onAddRow: function (index, file) {

        },
        onRenderedPreview: function (index) {

        },
        onRemoveRow: function (index) {

        },
        onExtensionErr: function (index, file) {
            toastr.error(accountOrderDetailsReviewMessage?.data('input-type'), {
                CloseButton: true,
                ProgressBar: true
            });
        },
        onSizeErr: function (index, file) {
            toastr.error(accountOrderDetailsReviewMessage?.data('on-big-size-file'), {
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
            image: accountOrderDetailsReviewMessage?.data('placeholder'),
            width: '100%'
        },
        dropFileLabel: accountOrderDetailsReviewMessage?.data('drop-here'),
        onAddRow: function (index, file) {

        },
        onRenderedPreview: function (index) {

        },
        onRemoveRow: function (index) {

        },
        onExtensionErr: function (index, file) {
            toastr.error(accountOrderDetailsReviewMessage?.data('input-type'), {
                CloseButton: true,
                ProgressBar: true
            });
        },
        onSizeErr: function (index, file) {
            toastr.error(accountOrderDetailsReviewMessage?.data('on-big-size-file'), {
                CloseButton: true,
                ProgressBar: true
            });
        }
    });
});
