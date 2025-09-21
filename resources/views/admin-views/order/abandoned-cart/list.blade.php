@extends('layouts.admin.app')
@section('title', translate('Abandoned_Carts'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">
                        <i class="tio-shopping-cart"></i>
                        {{ translate('Abandoned_Carts') }}
                    </h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Statistics Cards -->
        <div class="row g-2 mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-sm avatar-soft-info">
                                <i class="tio-shopping-cart"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">{{ translate('Total_Abandoned_Carts') }}</h6>
                            <h4 class="mb-0">{{ $statistics['total'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-sm avatar-soft-warning">
                                <i class="tio-dollar"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">{{ translate('Total_Value') }}</h6>
                            <h4 class="mb-0">{{ \App\Utils\Helpers::currency_converter($statistics['total_value']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-sm avatar-soft-success">
                                <i class="tio-email"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">{{ translate('With_Reminders') }}</h6>
                            <h4 class="mb-0">{{ $statistics['with_reminders'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-sm avatar-soft-danger">
                                <i class="tio-time"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">{{ translate('Recent_7_Days') }}</h6>
                            <h4 class="mb-0">{{ $statistics['recent'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Statistics Row -->
        <div class="row g-2 mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-sm avatar-soft-warning">
                                <i class="tio-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">{{ translate('Inactive_Products') }}</h6>
                            <h4 class="mb-0">{{ $statistics['with_inactive_products'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-sm avatar-soft-danger">
                                <i class="tio-user-delete"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">{{ translate('Deleted_Sellers') }}</h6>
                            <h4 class="mb-0">{{ $statistics['with_deleted_sellers'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.abandoned-carts.index') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-3">
                            <input type="text" name="searchValue" class="form-control" 
                                   placeholder="{{ translate('Search_by_customer_or_product') }}" 
                                   value="{{ $searchValue }}">
                        </div>
                        <div class="col-sm-6 col-md-2">
                            <select name="filter" class="form-control">
                                <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                                <option value="with_reminders" {{ $filter == 'with_reminders' ? 'selected' : '' }}>{{ translate('With_Reminders') }}</option>
                                <option value="without_reminders" {{ $filter == 'without_reminders' ? 'selected' : '' }}>{{ translate('Without_Reminders') }}</option>
                                <option value="recent" {{ $filter == 'recent' ? 'selected' : '' }}>{{ translate('Recent_7_Days') }}</option>
                                <option value="old" {{ $filter == 'old' ? 'selected' : '' }}>{{ translate('Older_7_Days') }}</option>
                            </select>
                        </div>
                        <div class="col-sm-6 col-md-2">
                            <input type="date" name="from" class="form-control" 
                                   placeholder="{{ translate('From_Date') }}" 
                                   value="{{ $from }}">
                        </div>
                        <div class="col-sm-6 col-md-2">
                            <input type="date" name="to" class="form-control" 
                                   placeholder="{{ translate('To_Date') }}" 
                                   value="{{ $to }}">
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="tio-search"></i> {{ translate('Search') }}
                            </button>
                            <a href="{{ route('admin.abandoned-carts.index') }}" class="btn btn-secondary">
                                <i class="tio-refresh"></i> {{ translate('Reset') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Abandoned Carts Table -->
        <div class="card">
            <div class="card-header">
                <div class="row justify-content-between align-items-center flex-grow-1">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-2 mb-sm-0">
                        <h5 class="card-header-title">{{ translate('Abandoned_Carts_List') }}</h5>
                    </div>
                    <div class="col-12 col-sm-6 col-md-8 col-lg-9">
                        <div class="d-flex flex-wrap justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-primary" id="bulk-reminder-btn">
                                <i class="tio-email"></i> {{ translate('Send_Bulk_Reminders') }}
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="bulk-delete-btn">
                                <i class="tio-delete"></i> {{ translate('Bulk_Delete') }}
                            </button>
                            <a href="{{ route('admin.abandoned-carts.export') }}?{{ http_build_query(request()->all()) }}" 
                               class="btn btn-outline-info">
                                <i class="tio-download"></i> {{ translate('Export') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="select-all">
                                    <label class="custom-control-label" for="select-all"></label>
                                </div>
                            </th>
                            <th>{{ translate('Customer') }}</th>
                            <th>{{ translate('Product') }}</th>
                            <th>{{ translate('Product_Status') }}</th>
                            <th>{{ translate('Quantity') }}</th>
                            <th>{{ translate('Price') }}</th>
                            <th>{{ translate('Total_Value') }}</th>
                            <th>{{ translate('Abandoned_Date') }}</th>
                            <th>{{ translate('Reminders') }}</th>
                            <th>{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($abandonedCarts as $cart)
                            <tr>
                                <td>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input cart-checkbox" 
                                               id="cart-{{ $cart->id }}" value="{{ $cart->id }}">
                                        <label class="custom-control-label" for="cart-{{ $cart->id }}"></label>
                                    </div>
                                </td>
                                <td>
                                    @if($cart->customer)
                                        <div>
                                            <strong>{{ $cart->customer->f_name }} {{ $cart->customer->l_name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $cart->customer->email }}</small>
                                            @if($cart->customer->phone)
                                                <br>
                                                <small class="text-muted">{{ $cart->customer->phone }}</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge badge-soft-warning">{{ translate('Guest_User') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($cart->productWithDetails)
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('storage/app/public/product/' . $cart->productWithDetails->thumbnail) }}" 
                                                 alt="{{ $cart->productWithDetails->name }}" 
                                                 class="rounded" style="width: 40px; height: 40px;">
                                            <div class="ms-2">
                                                <strong>{{ Str::limit($cart->productWithDetails->name, 30) }}</strong>
                                                @if($cart->sellerWithDetails)
                                                    <br>
                                                    <small class="text-muted">{{ translate('Seller') }}: {{ $cart->sellerWithDetails->f_name }} {{ $cart->sellerWithDetails->l_name }}</small>
                                                @else
                                                    <br>
                                                    <small class="text-danger">{{ translate('Seller_Not_Found') }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-danger">{{ translate('Product_Not_Found') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($cart->productWithDetails)
                                        @if($cart->productWithDetails->status == 1)
                                            <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                                        @else
                                            <span class="badge badge-soft-warning">{{ translate('Inactive') }}</span>
                                        @endif
                                    @else
                                        <span class="badge badge-soft-danger">{{ translate('Not_Found') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-soft-info">{{ $cart->quantity }}</span>
                                </td>
                                <td>
                                    <strong>{{ \App\Utils\Helpers::currency_converter($cart->price) }}</strong>
                                </td>
                                <td>
                                    <strong>{{ \App\Utils\Helpers::currency_converter($cart->price * $cart->quantity) }}</strong>
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $cart->abandoned_at->format('M d, Y') }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $cart->abandoned_at->format('H:i') }}</small>
                                    </div>
                                </td>
                                <td>
                                    @if($cart->reminder_sent > 0)
                                        <span class="badge badge-soft-success">{{ $cart->reminder_sent }} {{ translate('sent') }}</span>
                                        @if($cart->last_reminder_sent_at)
                                            <br>
                                            <small class="text-muted">{{ $cart->last_reminder_sent_at->format('M d, H:i') }}</small>
                                        @endif
                                    @else
                                        <span class="badge badge-soft-warning">{{ translate('No_reminders') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary send-reminder-btn" 
                                                data-cart-id="{{ $cart->id }}" 
                                                {{ $cart->reminder_sent >= 3 ? 'disabled' : '' }}>
                                            <i class="tio-email"></i>
                                        </button>
                                        <a href="{{ route('admin.abandoned-carts.show', $cart->id) }}" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="tio-visible"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-cart-btn" 
                                                data-cart-id="{{ $cart->id }}">
                                            <i class="tio-delete"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">
                                    <div class="empty-state">
                                        <img src="{{ asset('public/assets/admin/img/empty-state.png') }}" alt="Empty State" style="width: 100px;">
                                        <h5 class="mt-3">{{ translate('No_Abandoned_Carts_Found') }}</h5>
                                        <p class="text-muted">{{ translate('No_carts_have_been_abandoned_yet') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($abandonedCarts->hasPages())
                <div class="card-footer">
                    {{ $abandonedCarts->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="delete-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Delete_Abandoned_Cart') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ translate('Are_you_sure_you_want_to_delete_this_abandoned_cart') }}?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <form id="delete-form" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">{{ translate('Delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
$(document).ready(function() {
    // Select all checkbox
    $('#select-all').change(function() {
        $('.cart-checkbox').prop('checked', $(this).is(':checked'));
    });

    // Send reminder
    $('.send-reminder-btn').click(function() {
        const cartId = $(this).data('cart-id');
        const btn = $(this);
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.abandoned-carts.send-reminder") }}',
            method: 'POST',
            data: {
                cart_id: cartId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('{{ translate("Failed_to_send_reminder") }}');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Bulk send reminders
    $('#bulk-reminder-btn').click(function() {
        const selectedCarts = $('.cart-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedCarts.length === 0) {
            toastr.warning('{{ translate("Please_select_carts_to_send_reminders") }}');
            return;
        }

        if (confirm('{{ translate("Are_you_sure_you_want_to_send_reminders_to_selected_carts") }}?')) {
            $.ajax({
                url: '{{ route("admin.abandoned-carts.bulk-reminders") }}',
                method: 'POST',
                data: {
                    cart_ids: selectedCarts,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        location.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('{{ translate("Failed_to_send_reminders") }}');
                }
            });
        }
    });

    // Delete cart
    $('.delete-cart-btn').click(function() {
        const cartId = $(this).data('cart-id');
        $('#delete-form').attr('action', '{{ route("admin.abandoned-carts.destroy", "") }}/' + cartId);
        $('#delete-modal').modal('show');
    });

    // Bulk delete
    $('#bulk-delete-btn').click(function() {
        const selectedCarts = $('.cart-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedCarts.length === 0) {
            toastr.warning('{{ translate("Please_select_carts_to_delete") }}');
            return;
        }

        if (confirm('{{ translate("Are_you_sure_you_want_to_delete_selected_carts") }}?')) {
            $.ajax({
                url: '{{ route("admin.abandoned-carts.bulk-destroy") }}',
                method: 'POST',
                data: {
                    cart_ids: selectedCarts,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        location.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('{{ translate("Failed_to_delete_carts") }}');
                }
            });
        }
    });
});
</script>
@endpush 