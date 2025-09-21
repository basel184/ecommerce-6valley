@extends('layouts.admin.app')

@section('title', translate('create_reverse_transfer'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{ translate('create_reverse_transfer') }}</h1>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ translate('create_new_transfer') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.reverse-transfer.store') }}" method="POST">
                        @csrf
                        
                        <!-- اختيار الطلب -->
                        <div class="form-group">
                            <label for="order_id">{{ translate('select_order') }} <span class="text-danger">*</span></label>
                            <select name="order_id" id="order_id" class="form-control" required>
                                <option value="">{{ translate('select_order') }}</option>
                                @foreach($orders as $order)
                                    @if($order->customer)
                                        <option value="{{ $order->id }}" 
                                                data-customer="{{ $order->customer->f_name }} {{ $order->customer->l_name }}"
                                                data-amount="{{ $order->order_amount }}"
                                                data-payment-method="{{ $order->payment_method }}"
                                                data-transaction-id="{{ $order->transaction_reference }}">
                                            #{{ $order->id }} - {{ $order->customer->f_name }} {{ $order->customer->l_name }} 
                                            ({{ $order->order_amount }} ريال)
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <!-- معلومات العميل -->
                        <div class="form-group">
                            <label>{{ translate('customer_information') }}</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="customer_name" readonly 
                                           placeholder="{{ translate('customer_name') }}">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="order_amount" readonly 
                                           placeholder="{{ translate('order_amount') }}">
                                </div>
                            </div>
                        </div>

                        <!-- مبلغ الحوالة العكسية -->
                        <div class="form-group">
                            <label for="amount">{{ translate('amount') }} <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="amount" class="form-control" 
                                   step="0.01" min="0.01" required>
                        </div>

                        <!-- سبب الحوالة العكسية -->
                        <div class="form-group">
                            <label for="reason">{{ translate('reason') }} <span class="text-danger">*</span></label>
                            <textarea name="reason" id="reason" class="form-control" rows="3" required 
                                      placeholder="{{ translate('enter_reason_for_reverse_transfer') }}"></textarea>
                        </div>

                        <!-- رمز سبب الحوالة العكسية -->
                        <div class="form-group">
                            <label for="refund_reason_code">{{ translate('refund_reason_code') }}</label>
                            <select name="refund_reason_code" id="refund_reason_code" class="form-control">
                                <option value="">{{ translate('select_refund_reason') }}</option>
                                <option value="customer_request">طلب العميل</option>
                                <option value="product_defect">عيب في المنتج</option>
                                <option value="wrong_item">خطأ في الطلب</option>
                                <option value="delivery_issue">مشكلة في التوصيل</option>
                                <option value="duplicate_payment">دفع مكرر</option>
                                <option value="system_error">خطأ في النظام</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>

                        <!-- طريقة الدفع للحوالة العكسية -->
                        <div class="form-group">
                            <label for="payment_method">{{ translate('payment_method') }} <span class="text-danger">*</span></label>
                            <select name="payment_method" id="payment_method" class="form-control" required>
                                <option value="">{{ translate('select_payment_method') }}</option>
                                <option value="myfatoorah">MyFatoorah</option>
                                <option value="tabby">Tabby</option>
                                <option value="tamara">Tamara</option>
                                <option value="bank_transfer">{{ translate('bank_transfer') }}</option>
                                <option value="cash">{{ translate('cash') }}</option>
                                <option value="check">{{ translate('check') }}</option>
                                <option value="other">{{ translate('other') }}</option>
                            </select>
                        </div>

                        <!-- بوابة الدفع (للدفع الإلكتروني) -->
                        <div class="form-group" id="payment_gateway_group" style="display: none;">
                            <label for="payment_gateway">{{ translate('payment_gateway') }}</label>
                            <select name="payment_gateway" id="payment_gateway" class="form-control">
                                <option value="">{{ translate('select_payment_gateway') }}</option>
                                <option value="myfatoorah">MyFatoorah</option>
                                <option value="tabby">Tabby</option>
                                <option value="tamara">Tamara</option>
                            </select>
                        </div>

                        <!-- معلومات البنك (للتحويل البنكي) -->
                        <div id="bank_details" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bank_name">{{ translate('bank_name') }}</label>
                                        <input type="text" name="bank_name" id="bank_name" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="account_number">{{ translate('account_number') }}</label>
                                        <input type="text" name="account_number" id="account_number" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="account_holder_name">{{ translate('account_holder_name') }}</label>
                                        <input type="text" name="account_holder_name" id="account_holder_name" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="iban">{{ translate('iban') }}</label>
                                        <input type="text" name="iban" id="iban" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="swift_code">{{ translate('swift_code') }}</label>
                                <input type="text" name="swift_code" id="swift_code" class="form-control">
                            </div>
                        </div>

                        <!-- ملاحظات إضافية -->
                        <div class="form-group">
                            <label for="notes">{{ translate('additional_notes') }}</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3" 
                                      placeholder="{{ translate('enter_additional_notes') }}"></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">{{ translate('create_transfer') }}</button>
                            <a href="{{ route('admin.reverse-transfer.index') }}" class="btn btn-secondary">{{ translate('cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ translate('transfer_information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>{{ translate('important_notes') }}:</h6>
                        <ul class="mb-0">
                            <li>{{ translate('only_paid_orders_can_have_reverse_transfers') }}</li>
                            <li>{{ translate('gateway_transfers_are_processed_automatically') }}</li>
                            <li>{{ translate('manual_transfers_require_manual_processing') }}</li>
                            <li>{{ translate('all_transfers_are_tracked_with_status_history') }}</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <h6>{{ translate('supported_gateways') }}:</h6>
                        <ul class="mb-0">
                            <li><strong>MyFatoorah:</strong> {{ translate('myfatoorah_description') }}</li>
                            <li><strong>Tabby:</strong> {{ translate('tabby_description') }}</li>
                            <li><strong>Tamara:</strong> {{ translate('tamara_description') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
$(document).ready(function() {
    // تحديث معلومات العميل عند اختيار الطلب
    $('#order_id').change(function() {
        var selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            $('#customer_name').val(selectedOption.data('customer'));
            $('#order_amount').val(selectedOption.data('amount'));
            
            // تعيين المبلغ الأقصى للحوالة العكسية
            $('#amount').attr('max', selectedOption.data('amount'));
        } else {
            $('#customer_name').val('');
            $('#order_amount').val('');
            $('#amount').removeAttr('max');
        }
    });

    // إظهار/إخفاء حقول بوابة الدفع
    $('#payment_method').change(function() {
        var method = $(this).val();
        
        // إخفاء جميع الحقول
        $('#payment_gateway_group').hide();
        $('#bank_details').hide();
        
        // إظهار الحقول المناسبة
        if (['myfatoorah', 'tabby', 'tamara'].includes(method)) {
            $('#payment_gateway_group').show();
            $('#payment_gateway').val(method);
        } else if (method === 'bank_transfer') {
            $('#bank_details').show();
        }
    });

    // التحقق من صحة المبلغ
    $('#amount').on('input', function() {
        var amount = parseFloat($(this).val());
        var maxAmount = parseFloat($('#order_amount').val());
        
        if (amount > maxAmount) {
            $(this).addClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
            $(this).after('<div class="invalid-feedback">المبلغ لا يمكن أن يتجاوز مبلغ الطلب الأصلي</div>');
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // التحقق من صحة النموذج قبل الإرسال
    $('form').submit(function(e) {
        var amount = parseFloat($('#amount').val());
        var maxAmount = parseFloat($('#order_amount').val());
        
        if (amount > maxAmount) {
            e.preventDefault();
            alert('المبلغ لا يمكن أن يتجاوز مبلغ الطلب الأصلي');
            return false;
        }
    });
});
</script>
@endpush
@endsection