@extends('layouts.admin.app')
@section('title', translate('Abandoned_Cart_Details'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">
                        <i class="tio-shopping-cart"></i>
                        {{ translate('Abandoned_Cart_Details') }}
                    </h1>
                </div>
                <div class="col-sm-auto">
                    <a href="{{ route('admin.abandoned-carts.index') }}" class="btn btn-primary">
                        <i class="tio-back"></i> {{ translate('Back_to_List') }}
                    </a>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row">
            <!-- Customer Information -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-header-title">
                            <i class="tio-user"></i> {{ translate('Customer_Information') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($abandonedCart->customer)
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-lg">
                                    @php
                                        $avatarFile = $abandonedCart->customer->image ? ('profile/' . ltrim($abandonedCart->customer->image, '/')) : null;
                                        $hasAvatar = $avatarFile && \Illuminate\Support\Facades\Storage::disk('public')->exists($avatarFile);
                                    @endphp
                                    @if($hasAvatar)
                                        <img src="{{ route('storage.public', ['path' => $avatarFile]) }}"
                                             alt="{{ $abandonedCart->customer->f_name }}"
                                             class="rounded-circle" width="64" height="64" loading="lazy">
                                    @else
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                                            <i class="tio-user" style="font-size: 1.75rem; color: #999;"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-1">{{ $abandonedCart->customer->f_name }} {{ $abandonedCart->customer->l_name }}</h5>
                                    <span class="badge badge-soft-info">{{ translate('Registered_Customer') }}</span>
                                </div>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">{{ translate('Email') }}</label>
                                    <p class="form-control-static">{{ $abandonedCart->customer->email }}</p>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ translate('Phone') }}</label>
                                    <p class="form-control-static">{{ $abandonedCart->customer->phone ?? translate('Not_Provided') }}</p>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ translate('Member_Since') }}</label>
                                    <p class="form-control-static">{{ $abandonedCart->customer->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                        @else
                            <div class="text-center">
                                <div class="avatar avatar-lg mx-auto mb-3">
                                    <i class="tio-user" style="font-size: 3rem; color: #ccc;"></i>
                                </div>
                                <h5 class="mb-1">{{ translate('Guest_User') }}</h5>
                                <p class="text-muted">{{ translate('This_cart_was_created_by_a_guest_user') }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Cart Information -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-header-title">
                            <i class="tio-info"></i> {{ translate('Cart_Information') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">{{ translate('Cart_Group_ID') }}</label>
                                <p class="form-control-static">{{ $abandonedCart->cart_group_id }}</p>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ translate('Abandoned_Date') }}</label>
                                <p class="form-control-static">{{ $abandonedCart->abandoned_at->format('M d, Y H:i:s') }}</p>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ translate('Reminders_Sent') }}</label>
                                <p class="form-control-static">
                                    <span class="badge badge-soft-{{ $abandonedCart->reminder_sent > 0 ? 'success' : 'warning' }}">
                                        {{ $abandonedCart->reminder_sent }} {{ translate('times') }}
                                    </span>
                                </p>
                            </div>
                            @if($abandonedCart->last_reminder_sent_at)
                                <div class="col-12">
                                    <label class="form-label">{{ translate('Last_Reminder_Sent') }}</label>
                                    <p class="form-control-static">{{ $abandonedCart->last_reminder_sent_at->format('M d, Y H:i:s') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-header-title">
                            <i class="tio-shopping-bag"></i> {{ translate('Cart_Items') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        @forelse($relatedCarts as $cart)
                            <div class="card border mb-3">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            @if($cart->productWithDetails)
                                                @php
                                                    $thumb = $cart->productWithDetails->thumbnail;
                                                @endphp
                                                @php
                                                    $productPath = 'product/' . ltrim($thumb, '/');
                                                @endphp
                                                @if(!empty($thumb) && \Illuminate\Support\Facades\Storage::disk('public')->exists($productPath))
                                                    <img src="{{ route('storage.public', ['path' => $productPath]) }}"
                                                         alt="{{ $cart->productWithDetails->name }}"
                                                         class="img-fluid rounded"
                                                         loading="lazy">
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                         style="width: 80px; height: 80px;">
                                                        <i class="tio-image" style="font-size: 2rem; color: #ccc;"></i>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                     style="width: 80px; height: 80px;">
                                                    <i class="tio-image" style="font-size: 2rem; color: #ccc;"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="mb-1">
                                                @if($cart->productWithDetails)
                                                    {{ $cart->productWithDetails->name }}
                                                    @if($cart->productWithDetails->status == 0)
                                                        <span class="badge badge-soft-warning ms-2">{{ translate('Inactive') }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-danger">{{ translate('Product_Not_Found') }}</span>
                                                @endif
                                            </h6>
                                            @if($cart->productWithDetails)
                                                <p class="text-muted mb-1">{{ \Illuminate\Support\Str::limit($cart->productWithDetails->description, 100) }}</p>
                                            @endif
                                            @if($cart->sellerWithDetails)
                                                <small class="text-muted">
                                                    {{ translate('Seller') }}: {{ $cart->sellerWithDetails->f_name }} {{ $cart->sellerWithDetails->l_name }}
                                                </small>
                                            @else
                                                <small class="text-danger">
                                                    {{ translate('Seller_Not_Found') }}
                                                </small>
                                            @endif
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <span class="badge badge-soft-info">{{ $cart->quantity }}</span>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <strong>{{ \App\Utils\Helpers::currency_converter($cart->price) }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                {{ translate('Total') }}: {{ \App\Utils\Helpers::currency_converter($cart->price * $cart->quantity) }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="tio-shopping-bag" style="font-size: 3rem; color: #ccc;"></i>
                                <h5 class="mt-3">{{ translate('No_Items_Found') }}</h5>
                                <p class="text-muted">{{ translate('No_items_in_this_abandoned_cart') }}</p>
                            </div>
                        @endforelse

                        @if($relatedCarts->count() > 0)
                            <div class="border-top pt-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>{{ translate('Cart_Summary') }}</h6>
                                        <p class="text-muted">{{ translate('Total_Items') }}: {{ $relatedCarts->count() }}</p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <h5>{{ translate('Total_Value') }}: 
                                            <strong>{{ \App\Utils\Helpers::currency_converter($relatedCarts->sum(function($cart) { return $cart->price * $cart->quantity; })) }}</strong>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-header-title">
                            <i class="tio-settings"></i> {{ translate('Actions') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @if($abandonedCart->customer)
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-primary w-100 send-reminder-btn" 
                                            data-cart-id="{{ $abandonedCart->id }}"
                                            >
                                        <i class="tio-email"></i> 
                                        {{ translate('Send_Reminder') }}
                                        @if($abandonedCart->reminder_sent > 0)
                                            ({{ $abandonedCart->reminder_sent }}/3)
                                        @endif
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    @php
                                        // Prefer controller-provided list if available
                                        $tpls = isset($twilioTemplates) ? $twilioTemplates : config('twilio.templates', []);
                                    @endphp
                                    <select id="wa-template-key" class="form-select">
                                        @foreach($tpls as $key => $tpl)
                                            <option value="{{ $key }}" data-content-sid="{{ $tpl['content_sid'] ?? '' }}">{{ $tpl['name'] ?? $key }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">{{ translate('Select_the_Twilio_WhatsApp_template') }}</small>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-success w-100 restore-cart-btn" 
                                            data-cart-group-id="{{ $abandonedCart->cart_group_id }}"
                                            data-customer-id="{{ $abandonedCart->customer_id }}">
                                        <i class="tio-refresh"></i> {{ translate('Restore_to_Cart') }}
                                    </button>
                                </div>
                            @else
                                <div class="col-12">
                                    <div class="alert alert-warning">
                                        <i class="tio-info"></i> 
                                        {{ translate('Cannot_send_reminders_or_restore_guest_cart') }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Reminder History -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-header-title">
                            <i class="tio-message"></i> {{ translate('Reminder_History') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            $hasLogTable = \Illuminate\Support\Facades\Schema::hasTable('abandoned_cart_reminders');
                            $logs = collect();
                            if ($hasLogTable) {
                                $logs = \App\Models\AbandonedCartReminder::where('cart_group_id', $abandonedCart->cart_group_id)
                                    ->orderByDesc('created_at')
                                    ->limit(50)
                                    ->get();
                            }
                        @endphp
                        @if(!$hasLogTable)
                            <div class="alert alert-info">
                                {{ translate('Reminder_history_will_appear_after_running_database_migrations') }}
                            </div>
                        @elseif($logs->isEmpty())
                            <div class="text-center text-muted">
                                {{ translate('No_reminders_sent_yet') }}
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <thead>
                                        <tr>
                                            <th>{{ translate('Date') }}</th>
                                            <th>{{ translate('Channel') }}</th>
                                            <th>{{ translate('Provider') }}</th>
                                            <th>{{ translate('Template') }}</th>
                                            <th>{{ translate('To') }}</th>
                                            <th>{{ translate('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($logs as $log)
                                        <tr>
                                            <td>{{ optional($log->sent_at ?? $log->created_at)->format('M d, Y H:i') }}</td>
                                            <td><span class="badge bg-secondary">{{ strtoupper($log->channel) }}</span></td>
                                            <td>{{ $log->provider }}</td>
                                            <td>{{ $log->template_key }}</td>
                                            <td>{{ $log->to_phone }}</td>
                                            <td>
                                                @if($log->status === 'sent')
                                                    <span class="badge bg-success">{{ translate('Sent') }}</span>
                                                @else
                                                    <span class="badge bg-danger" title="{{ $log->error }}">{{ translate('Failed') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
$(document).ready(function() {
    // Safe toast wrapper
    function showToast(type, message) {
        if (window.toastr && typeof toastr[type] === 'function') {
            toastr[type](message);
        } else {
            // Fallback to console if toastr not loaded
            console[type === 'success' ? 'log' : 'error'](message);
        }
    }
    // Send reminder
    $('.send-reminder-btn').click(function() {
        const cartId = $(this).data('cart-id');
        const btn = $(this);
        const selected = $('#wa-template-key option:selected');
        const selectedSid = selected.data('content-sid');
        const enforceContent = {{ config('twilio.enforce_content_templates', true) ? 'true' : 'false' }};
        
        if (btn.prop('disabled')) {
            return;
        }

        if (enforceContent && (!selectedSid || String(selectedSid).trim() === '')) {
            showToast('error', '{{ translate('Please_configure_Twilio_Content_SID_for_this_template_first') }}');
            return;
        }
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.abandoned-carts.send-reminder") }}',
            method: 'POST',
            data: (function(){
                var $sel = selected;
                return {
                    cart_id: cartId,
                    template_key: $sel.val(),
                    // pass content_sid to backend if present; service prefers it
                    content_sid: $sel.data('content-sid') || '',
                    _token: '{{ csrf_token() }}'
                };
            })(),
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message);
                    location.reload();
                } else {
                    showToast('error', response.message);
                }
            },
            error: function() {
                showToast('error', '{{ translate("Failed_to_send_reminder") }}');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Restore cart
    $('.restore-cart-btn').click(function() {
        const cartGroupId = $(this).data('cart-group-id');
        const customerId = $(this).data('customer-id');
        const btn = $(this);
        
        if (confirm('{{ translate("Are_you_sure_you_want_to_restore_this_cart") }}?')) {
            btn.prop('disabled', true);
            
            $.ajax({
                url: '{{ route("admin.abandoned-carts.restore") }}',
                method: 'POST',
                data: {
                    cart_group_id: cartGroupId,
                    customer_id: customerId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showToast('success', response.message);
                        window.location.href = '{{ route("admin.abandoned-carts.index") }}';
                    } else {
                        showToast('error', response.message);
                    }
                },
                error: function() {
                    showToast('error', '{{ translate("Failed_to_restore_cart") }}');
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        }
    });
});
</script>
@endpush 