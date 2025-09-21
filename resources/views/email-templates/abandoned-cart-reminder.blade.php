<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('Complete_Your_Purchase') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .cart-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .product-details {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .product-info {
            flex: 1;
        }
        .product-name {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .product-price {
            color: #28a745;
            font-weight: bold;
            font-size: 18px;
        }
        .quantity {
            background: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        .total-section {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6c757d;
            font-size: 14px;
        }
        .social-links {
            margin-top: 20px;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #6c757d;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ translate('Complete_Your_Purchase') }}</h1>
        <p>{{ translate('Your_cart_is_waiting_for_you') }}</p>
    </div>

    <div class="content">
        <p>مرحباً {{ $customer_name }}،</p>
        
        <p>{{ translate('We_noticed_you_left_some_items_in_your_cart') }}. {{ translate('Dont_miss_out_on_these_great_products') }}!</p>

        <h3>{{ translate('Your_Cart_Items') }}:</h3>

        @foreach($cart_items as $item)
            <div class="cart-item">
                <div class="product-details">
                    @if($item->product && $item->product->thumbnail)
                        <img src="{{ asset('storage/app/public/product/' . $item->product->thumbnail) }}" 
                             alt="{{ $item->product->name }}" class="product-image">
                    @else
                        <div style="width: 80px; height: 80px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <span style="color: #6c757d;">{{ translate('No_Image') }}</span>
                        </div>
                    @endif
                    <div class="product-info">
                        <div class="product-name">
                            @if($item->product)
                                {{ $item->product->name }}
                            @else
                                {{ translate('Product_Not_Found') }}
                            @endif
                        </div>
                        <div class="product-price">
                            {{ \App\Utils\Helpers::currency_converter($item->price) }}
                            <span class="quantity">× {{ $item->quantity }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="total-section">
            <div class="total-row">
                <span>{{ translate('Cart_Total') }}:</span>
                <span class="total-amount">{{ \App\Utils\Helpers::currency_converter($total_value) }}</span>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ url('/cart') }}" class="cta-button">
                {{ translate('Complete_Your_Purchase') }}
            </a>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 8px;">
            <h4 style="margin-top: 0; color: #1976d2;">{{ translate('Why_Complete_Your_Purchase') }}?</h4>
            <ul style="margin: 0; padding-left: 20px;">
                <li>{{ translate('Secure_payment_options') }}</li>
                <li>{{ translate('Fast_delivery') }}</li>
                <li>{{ translate('Easy_returns') }}</li>
                <li>{{ translate('24_7_customer_support') }}</li>
            </ul>
        </div>

        <div class="footer">
            <p>{{ translate('This_email_was_sent_to_you_because_you_have_items_in_your_cart') }}.</p>
            <p>{{ translate('If_you_have_any_questions_please_contact_our_support_team') }}.</p>
            
            <div class="social-links">
                <a href="#">{{ translate('Facebook') }}</a>
                <a href="#">{{ translate('Twitter') }}</a>
                <a href="#">{{ translate('Instagram') }}</a>
            </div>
            
            <p style="margin-top: 20px;">
                {{ translate('Cart_abandoned_on') }}: {{ $abandoned_at }}
            </p>
        </div>
    </div>
</body>
</html> 