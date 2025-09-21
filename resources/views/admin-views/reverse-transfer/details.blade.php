@extends('layouts.admin.app')

@section('title', translate('reverse_transfer_details'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{ translate('reverse_transfer_details') }} #{{ $reverseTransfer->id }}</h1>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.reverse-transfer.index') }}" class="btn btn-secondary">
                    <i class="fi fi-sr-arrow-left"></i> {{ translate('back_to_list') }}
                </a>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <div class="row">
        <div class="col-lg-8">
            <!-- ملخص الحوالة -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">{{ translate('transfer_summary') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>{{ translate('transfer_id') }}:</strong></td>
                                    <td>#{{ $reverseTransfer->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('order_id') }}:</strong></td>
                                    <td>#{{ $reverseTransfer->order_id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('amount') }}:</strong></td>
                                    <td>{{ $reverseTransfer->amount }} ريال</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('status') }}:</strong></td>
                                    <td>
                                        <span class="badge {{ $reverseTransfer->status_badge }}">
                                            {{ $reverseTransfer->status_text }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>{{ translate('payment_method') }}:</strong></td>
                                    <td>{{ $reverseTransfer->gateway_display_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('original_payment') }}:</strong></td>
                                    <td>{{ $reverseTransfer->original_payment_display_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('created_date') }}:</strong></td>
                                    <td>{{ $reverseTransfer->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('created_by') }}:</strong></td>
                                    <td>{{ $reverseTransfer->admin_name }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- معلومات العميل -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">{{ translate('customer_information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>{{ translate('name') }}:</strong></td>
                                    <td>{{ $reverseTransfer->customer_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('phone') }}:</strong></td>
                                    <td>{{ $reverseTransfer->customer_phone }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>{{ translate('email') }}:</strong></td>
                                    <td>{{ $reverseTransfer->customer_email }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('customer_id') }}:</strong></td>
                                    <td>#{{ $reverseTransfer->customer_id }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- تفاصيل الحوالة -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">{{ translate('transfer_details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>{{ translate('reason') }}:</strong></td>
                                    <td>{{ $reverseTransfer->reason }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('refund_reason_code') }}:</strong></td>
                                    <td>{{ $reverseTransfer->refund_reason_code ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('notes') }}:</strong></td>
                                    <td>{{ $reverseTransfer->notes ?: 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>{{ translate('original_transaction_id') }}:</strong></td>
                                    <td>{{ $reverseTransfer->original_transaction_id ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('gateway_transaction_id') }}:</strong></td>
                                    <td>{{ $reverseTransfer->gateway_transaction_id ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('reference_number') }}:</strong></td>
                                    <td>{{ $reverseTransfer->reference_number ?: 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @if($reverseTransfer->payment_method === 'bank_transfer')
            <!-- معلومات البنك -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">{{ translate('bank_information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>{{ translate('bank_name') }}:</strong></td>
                                    <td>{{ $reverseTransfer->bank_name ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('account_number') }}:</strong></td>
                                    <td>{{ $reverseTransfer->account_number ?: 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>{{ translate('account_holder_name') }}:</strong></td>
                                    <td>{{ $reverseTransfer->account_holder_name ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('iban') }}:</strong></td>
                                    <td>{{ $reverseTransfer->iban ?: 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($reverseTransfer->isGatewayPayment())
            <!-- معلومات بوابة الدفع -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">{{ translate('gateway_information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>{{ translate('gateway') }}:</strong></td>
                                    <td>{{ $reverseTransfer->gateway_display_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('gateway_status') }}:</strong></td>
                                    <td>
                                        @if($reverseTransfer->gateway_status)
                                            <span class="badge badge-{{ $reverseTransfer->gateway_status === 'processing' ? 'info' : ($reverseTransfer->gateway_status === 'failed' ? 'danger' : 'success') }}">
                                                {{ $reverseTransfer->gateway_status }}
                                            </span>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>{{ translate('gateway_transaction_id') }}:</strong></td>
                                    <td>{{ $reverseTransfer->gateway_transaction_id ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('gateway_error') }}:</strong></td>
                                    <td>{{ $reverseTransfer->gateway_error_message ?: 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($reverseTransfer->gateway_response)
                    <div class="mt-3">
                        <h6>{{ translate('gateway_response') }}:</h6>
                        <pre class="bg-light p-3 rounded">{{ json_encode($reverseTransfer->gateway_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- معلومات المعالجة -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">{{ translate('processing_information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                @if($reverseTransfer->approved_by)
                                <tr>
                                    <td><strong>{{ translate('approved_by') }}:</strong></td>
                                    <td>{{ $reverseTransfer->approved_by_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('approved_at') }}:</strong></td>
                                    <td>{{ $reverseTransfer->approved_at ? $reverseTransfer->approved_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                </tr>
                                @endif
                                @if($reverseTransfer->rejected_by)
                                <tr>
                                    <td><strong>{{ translate('rejected_by') }}:</strong></td>
                                    <td>{{ $reverseTransfer->rejected_by_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('rejected_at') }}:</strong></td>
                                    <td>{{ $reverseTransfer->rejected_at ? $reverseTransfer->rejected_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('rejection_reason') }}:</strong></td>
                                    <td>{{ $reverseTransfer->rejection_reason ?: 'N/A' }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                @if($reverseTransfer->processed_by)
                                <tr>
                                    <td><strong>{{ translate('processed_by') }}:</strong></td>
                                    <td>{{ $reverseTransfer->processed_by_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ translate('processed_at') }}:</strong></td>
                                    <td>{{ $reverseTransfer->processed_at ? $reverseTransfer->processed_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                </tr>
                                @endif
                                @if($reverseTransfer->transfer_date)
                                <tr>
                                    <td><strong>{{ translate('transfer_date') }}:</strong></td>
                                    <td>{{ $reverseTransfer->transfer_date ? $reverseTransfer->transfer_date->format('Y-m-d') : 'N/A' }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td><strong>{{ translate('transaction_id') }}:</strong></td>
                                    <td>{{ $reverseTransfer->transaction_id ?: 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- إجراءات الحوالة -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">{{ translate('actions') }}</h5>
                </div>
                <div class="card-body">
                    @if($reverseTransfer->status === 'pending')
                        <button type="button" class="btn btn-success btn-block mb-2" onclick="updateStatus('approved')">
                            <i class="fi fi-sr-check"></i> {{ translate('approve') }}
                        </button>
                        <button type="button" class="btn btn-danger btn-block mb-2" onclick="updateStatus('rejected')">
                            <i class="fi fi-sr-cross"></i> {{ translate('reject') }}
                        </button>
                    @endif

                    @if($reverseTransfer->status === 'approved')
                        <button type="button" class="btn btn-primary btn-block mb-2" onclick="updateStatus('processed')">
                            <i class="fi fi-sr-settings"></i> {{ translate('process') }}
                        </button>
                        <button type="button" class="btn btn-danger btn-block mb-2" onclick="updateStatus('rejected')">
                            <i class="fi fi-sr-cross"></i> {{ translate('reject') }}
                        </button>
                    @endif

                    @if($reverseTransfer->status === 'processed')
                        <button type="button" class="btn btn-success btn-block mb-2" onclick="updateStatus('completed')">
                            <i class="fi fi-sr-check-double"></i> {{ translate('complete') }}
                        </button>
                    @endif

                    @if($reverseTransfer->status === 'rejected')
                        <button type="button" class="btn btn-success btn-block mb-2" onclick="updateStatus('approved')">
                            <i class="fi fi-sr-check"></i> {{ translate('approve') }}
                        </button>
                    @endif

                    @if($reverseTransfer->canBeDeleted())
                        <button type="button" class="btn btn-danger btn-block" onclick="deleteTransfer()">
                            <i class="fi fi-sr-trash"></i> {{ translate('delete') }}
                        </button>
                    @endif
                </div>
            </div>

            <!-- تاريخ الحالة -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ translate('status_history') }}</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach($reverseTransfer->statusHistory as $history)
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">{{ translate($history->status) }}</h6>
                                <p class="timeline-text">{{ $history->notes }}</p>
                                <small class="text-muted">
                                    {{ $history->changedBy ? $history->changedBy->f_name . ' ' . $history->changedBy->l_name : 'System' }} - 
                                    {{ $history->created_at->format('Y-m-d H:i:s') }}
                                </small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal لتحديث الحالة -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('update_status') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm">
                    <div class="form-group">
                        <label for="status">{{ translate('status') }}</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="approved">{{ translate('approved') }}</option>
                            <option value="rejected">{{ translate('rejected') }}</option>
                            <option value="processed">{{ translate('processed') }}</option>
                            <option value="completed">{{ translate('completed') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="notes">{{ translate('notes') }}</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="submitStatusUpdate()">{{ translate('update') }}</button>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
let currentStatus = '';

function updateStatus(status) {
    currentStatus = status;
    $('#status').val(status);
    $('#updateStatusModal').modal('show');
}

function submitStatusUpdate() {
    const status = $('#status').val();
    const notes = $('#notes').val();

    // إظهار مؤشر التحميل
    $('#updateStatusModal .btn-primary').prop('disabled', true).html('<i class="fi fi-sr-spinner fa-spin"></i> جاري التحديث...');

    $.ajax({
        url: '{{ route("admin.reverse-transfer.update-status", $reverseTransfer->id) }}',
        method: 'POST',
        data: {
            status: status,
            notes: notes,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                // إظهار رسالة النجاح
                showAlert('success', response.message);
                $('#updateStatusModal').modal('hide');
                
                // إعادة تحميل الصفحة بعد ثانية
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showAlert('error', response.message);
            }
        },
        error: function(xhr) {
            let errorMessage = 'حدث خطأ ما';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.status === 400) {
                errorMessage = 'بيانات غير صحيحة';
            } else if (xhr.status === 404) {
                errorMessage = 'الحوالة العكسية غير موجودة';
            } else if (xhr.status === 500) {
                errorMessage = 'خطأ في الخادم';
            }
            
            showAlert('error', errorMessage);
        },
        complete: function() {
            // إعادة تفعيل الزر
            $('#updateStatusModal .btn-primary').prop('disabled', false).html('{{ translate("update") }}');
        }
    });
}

function deleteTransfer() {
    if (confirm('{{ translate("are_you_sure_you_want_to_delete_this_transfer") }}')) {
        window.location.href = '{{ route("admin.reverse-transfer.destroy", $reverseTransfer->id) }}';
    }
}

// دالة لعرض التنبيهات
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const iconClass = type === 'success' ? 'fi fi-sr-check' : 'fi fi-sr-cross';
    
    // إزالة التنبيهات السابقة
    $('.custom-alert').remove();
    
    // إنشاء تنبيه جديد
    const alertHtml = `
        <div class="custom-alert alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="${iconClass}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // إضافة التنبيه في أعلى الصفحة
    $('.content.container-fluid').prepend(alertHtml);
    
    // إزالة التنبيه تلقائياً بعد 5 ثوان
    setTimeout(function() {
        $('.custom-alert').fadeOut();
    }, 5000);
}

// إضافة معالج الأحداث للنموذج
$(document).ready(function() {
    // منع إرسال النموذج بالضغط على Enter
    $('#updateStatusForm').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
        }
    });
    
    // إغلاق Modal عند الضغط على Escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#updateStatusModal').modal('hide');
        }
    });
    
    // إغلاق Modal عند النقر خارج النافذة
    $('#updateStatusModal').on('click', function(e) {
        if (e.target === this) {
            $(this).modal('hide');
        }
    });
    
    // إغلاق Modal عند النقر على زر الإغلاق
    $('[data-bs-dismiss="modal"]').on('click', function() {
        $('#updateStatusModal').modal('hide');
    });
    
    // إغلاق Modal عند النقر على زر الإلغاء
    $('.btn-secondary[data-bs-dismiss="modal"]').on('click', function() {
        $('#updateStatusModal').modal('hide');
    });
});
</script>
@endpush

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #007bff;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #007bff;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #007bff;
}

.timeline-title {
    margin: 0 0 5px 0;
    font-size: 14px;
    font-weight: bold;
}

.timeline-text {
    margin: 0 0 5px 0;
    font-size: 13px;
}
</style>
@endsection