@extends('layouts.admin.app')

@section('title', translate('reverse_transfers'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{ translate('reverse_transfers') }}</h1>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.reverse-transfer.create') }}" class="btn btn-primary">
                    <i class="fi fi-sr-plus"></i> {{ translate('create_reverse_transfer') }}
                </a>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $statistics['total'] ?? 0 }}</h4>
                            <p class="mb-0">{{ translate('total_transfers') }}</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fi fi-sr-exchange fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $statistics['pending'] ?? 0 }}</h4>
                            <p class="mb-0">{{ translate('pending') }}</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fi fi-sr-clock fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $statistics['processed'] ?? 0 }}</h4>
                            <p class="mb-0">{{ translate('processed') }}</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fi fi-sr-settings fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $statistics['completed'] ?? 0 }}</h4>
                            <p class="mb-0">{{ translate('completed') }}</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fi fi-sr-check-double fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gateway Statistics -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ translate('gateway_statistics') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary">{{ $statistics['by_gateway']['myfatoorah'] ?? 0 }}</h4>
                                <p class="text-muted">MyFatoorah</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-info">{{ $statistics['by_gateway']['tabby'] ?? 0 }}</h4>
                                <p class="text-muted">Tabby</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-warning">{{ $statistics['by_gateway']['tamara'] ?? 0 }}</h4>
                                <p class="text-muted">Tamara</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-secondary">{{ $statistics['by_gateway']['manual'] ?? 0 }}</h4>
                                <p class="text-muted">{{ translate('manual') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reverse-transfer.index', ['status' => $status]) }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="searchValue">{{ translate('search') }}</label>
                            <input type="text" name="searchValue" id="searchValue" class="form-control" 
                                   value="{{ request('searchValue') }}" 
                                   placeholder="{{ translate('search_by_transfer_id_or_order_id') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="payment_gateway">{{ translate('payment_gateway') }}</label>
                            <select name="payment_gateway" id="payment_gateway" class="form-control">
                                <option value="">{{ translate('all_gateways') }}</option>
                                <option value="myfatoorah" {{ request('payment_gateway') === 'myfatoorah' ? 'selected' : '' }}>MyFatoorah</option>
                                <option value="tabby" {{ request('payment_gateway') === 'tabby' ? 'selected' : '' }}>Tabby</option>
                                <option value="tamara" {{ request('payment_gateway') === 'tamara' ? 'selected' : '' }}>Tamara</option>
                                <option value="bank_transfer" {{ request('payment_gateway') === 'bank_transfer' ? 'selected' : '' }}>{{ translate('bank_transfer') }}</option>
                                <option value="cash" {{ request('payment_gateway') === 'cash' ? 'selected' : '' }}>{{ translate('cash') }}</option>
                                <option value="check" {{ request('payment_gateway') === 'check' ? 'selected' : '' }}>{{ translate('check') }}</option>
                                <option value="other" {{ request('payment_gateway') === 'other' ? 'selected' : '' }}>{{ translate('other') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_from">{{ translate('date_from') }}</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" 
                                   value="{{ request('date_from') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_to">{{ translate('date_to') }}</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" 
                                   value="{{ request('date_to') }}">
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fi fi-sr-search"></i> {{ translate('search') }}
                        </button>
                        <a href="{{ route('admin.reverse-transfer.index') }}" class="btn btn-secondary">
                            <i class="fi fi-sr-refresh"></i> {{ translate('reset') }}
                        </a>
                        <a href="{{ route('admin.reverse-transfer.export', ['status' => $status]) }}?{{ http_build_query(request()->except('_token')) }}" 
                           class="btn btn-success">
                            <i class="fi fi-sr-download"></i> {{ translate('export') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link {{ $status === 'all' || !$status ? 'active' : '' }}" 
                       href="{{ route('admin.reverse-transfer.index', ['status' => 'all']) }}">
                        {{ translate('all') }} ({{ $statistics['total'] ?? 0 }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $status === 'pending' ? 'active' : '' }}" 
                       href="{{ route('admin.reverse-transfer.index', ['status' => 'pending']) }}">
                        {{ translate('pending') }} ({{ $statistics['pending'] ?? 0 }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $status === 'approved' ? 'active' : '' }}" 
                       href="{{ route('admin.reverse-transfer.index', ['status' => 'approved']) }}">
                        {{ translate('approved') }} ({{ $statistics['approved'] ?? 0 }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $status === 'processed' ? 'active' : '' }}" 
                       href="{{ route('admin.reverse-transfer.index', ['status' => 'processed']) }}">
                        {{ translate('processed') }} ({{ $statistics['processed'] ?? 0 }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $status === 'completed' ? 'active' : '' }}" 
                       href="{{ route('admin.reverse-transfer.index', ['status' => 'completed']) }}">
                        {{ translate('completed') }} ({{ $statistics['completed'] ?? 0 }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $status === 'rejected' ? 'active' : '' }}" 
                       href="{{ route('admin.reverse-transfer.index', ['status' => 'rejected']) }}">
                        {{ translate('rejected') }} ({{ $statistics['rejected'] ?? 0 }})
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            @if($reverseTransfers->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ translate('transfer_id') }}</th>
                                <th>{{ translate('order_id') }}</th>
                                <th>{{ translate('customer') }}</th>
                                <th>{{ translate('amount') }}</th>
                                <th>{{ translate('payment_gateway') }}</th>
                                <th>{{ translate('status') }}</th>
                                <th>{{ translate('created_date') }}</th>
                                <th>{{ translate('action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reverseTransfers as $transfer)
                            <tr>
                                <td>
                                    <strong>#{{ $transfer->id }}</strong>
                                    @if($transfer->gateway_transaction_id)
                                        <br><small class="text-muted">{{ translate('gateway_id') }}: {{ $transfer->gateway_transaction_id }}</small>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.details', ['id' => $transfer->order_id]) }}" 
                                       class="text-primary">#{{ $transfer->order_id }}</a>
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $transfer->customer_name }}</strong>
                                        <br><small class="text-muted">{{ $transfer->customer_phone }}</small>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $transfer->amount }} ريال</strong>
                                    <br><small class="text-muted">{{ translate('original') }}: {{ $transfer->order->order_amount ?? 'N/A' }} ريال</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($transfer->isGatewayPayment())
                                            <span class="badge badge-primary mr-2"  style="color: #000;">{{ $transfer->gateway_display_name }}</span>
                                        @else
                                            <span class="badge badge-secondary mr-2" style="color: #000;">{{ translate($transfer->payment_method) }}</span>
                                        @endif
                                        @if($transfer->gateway_status)
                                            <span class="badge badge-{{ $transfer->gateway_status === 'processing' ? 'info' : ($transfer->gateway_status === 'failed' ? 'danger' : 'success') }}">
                                                {{ $transfer->gateway_status }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($transfer->original_payment_method)
                                        <br><small class="text-muted">{{ translate('original') }}: {{ $transfer->original_payment_display_name }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $transfer->status_badge }}">
                                        {{ $transfer->status_text }}
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $transfer->created_at->format('Y-m-d') }}</strong>
                                        <br><small class="text-muted">{{ $transfer->created_at->format('H:i:s') }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.reverse-transfer.show', $transfer->id) }}" 
                                           class="btn btn-sm btn-info" title="{{ translate('view') }}">
                                            <i class="fi fi-sr-eye"></i>
                                        </a>
                                        @if($transfer->canBeDeleted())
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteTransfer({{ $transfer->id }})" 
                                                    title="{{ translate('delete') }}">
                                                <i class="fi fi-sr-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $reverseTransfers->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fi fi-sr-exchange fs-1 text-muted mb-3"></i>
                    <h5 class="text-muted">{{ translate('no_reverse_transfers_found') }}</h5>
                    <p class="text-muted">{{ translate('no_reverse_transfers_found_description') }}</p>
                    <a href="{{ route('admin.reverse-transfer.create') }}" class="btn btn-primary">
                        <i class="fi fi-sr-plus"></i> {{ translate('create_first_transfer') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@push('script')
<script>
function deleteTransfer(id) {
    if (confirm('{{ translate("are_you_sure_you_want_to_delete_this_transfer") }}')) {
        // إظهار مؤشر التحميل
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fi fi-sr-spinner fa-spin"></i>';
        
        $.ajax({
            url: '{{ url("admin/reverse-transfer/destroy") }}/' + id,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // إظهار رسالة النجاح
                showAlert('success', '{{ translate("reverse_transfer_deleted_successfully") }}');
                
                // إعادة تحميل الصفحة بعد ثانية
                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            error: function(xhr) {
                let errorMessage = '{{ translate("error_deleting_transfer") }}';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    errorMessage = '{{ translate("transfer_not_found") }}';
                } else if (xhr.status === 403) {
                    errorMessage = '{{ translate("cannot_delete_transfer") }}';
                }
                
                showAlert('error', errorMessage);
                
                // إعادة تفعيل الزر
                button.disabled = false;
                button.innerHTML = originalContent;
            }
        });
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

// Auto-submit form on gateway change
document.getElementById('payment_gateway').addEventListener('change', function() {
    this.form.submit();
});

// Auto-submit form on date changes
document.getElementById('date_from').addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('date_to').addEventListener('change', function() {
    this.form.submit();
});
</script>
@endpush

<style>
.nav-tabs .nav-link {
    color: #6c757d;
    border: none;
    border-bottom: 2px solid transparent;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    border-bottom-color: #007bff;
    background: none;
}

.badge {
    font-size: 0.75em;
}

.btn-group .btn {
    margin-right: 2px;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.table td {
    vertical-align: middle;
}
</style>
@endsection