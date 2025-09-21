@extends('theme-views.layouts.app')

@section('title', translate('order_Complete'))

@section('content')
<style>
.order-complete-container {
    min-height: 80vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    overflow: hidden;
}

.order-complete-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    pointer-events: none;
}

.complete-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    transform: translateY(0);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.complete-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
}

.complete-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #4CAF50, #8BC34A, #CDDC39);
    border-radius: 24px 24px 0 0;
}

.complete-icon {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #4CAF50, #8BC34A);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    position: relative;
    animation: completePulse 2s ease-in-out infinite;
}

.complete-icon::before {
    content: '';
    position: absolute;
    width: 140px;
    height: 140px;
    background: linear-gradient(135deg, #4CAF50, #8BC34A);
    border-radius: 50%;
    opacity: 0.3;
    animation: completePulse 2s ease-in-out infinite 0.5s;
}

.complete-icon::after {
    content: '';
    position: absolute;
    width: 160px;
    height: 160px;
    background: linear-gradient(135deg, #4CAF50, #8BC34A);
    border-radius: 50%;
    opacity: 0.1;
    animation: completePulse 2s ease-in-out infinite 1s;
}

@keyframes completePulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.7; }
}

.complete-icon i {
    font-size: 60px;
    color: white;
    z-index: 2;
    position: relative;
}

.complete-title {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #4CAF50, #8BC34A);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1rem;
    text-align: center;
}

.complete-message {
    font-size: 1.2rem;
    color: #666;
    text-align: center;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.order-details {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 16px;
    padding: 1.5rem;
    margin: 2rem 0;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.order-numbers {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
    margin: 1rem 0;
}

.order-number {
    background: linear-gradient(135deg, #4CAF50, #8BC34A);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1.1rem;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
    transition: all 0.3s ease;
}

.order-number:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 2rem;
}

.btn-complete-primary {
    background: linear-gradient(135deg, #4CAF50, #8BC34A);
    border: none;
    color: white;
    padding: 1rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
    position: relative;
    overflow: hidden;
}

.btn-complete-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.btn-complete-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
    color: white;
    text-decoration: none;
}

.btn-complete-primary:hover::before {
    left: 100%;
}

.btn-complete-secondary {
    background: linear-gradient(135deg, #6c757d, #495057);
    border: none;
    color: white;
    padding: 1rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.btn-complete-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
    color: white;
    text-decoration: none;
}

.confetti {
    position: absolute;
    width: 10px;
    height: 10px;
    background: #4CAF50;
    animation: confetti-fall 3s linear infinite;
}

.confetti:nth-child(2n) {
    background: #8BC34A;
    animation-delay: 0.5s;
}

.confetti:nth-child(3n) {
    background: #CDDC39;
    animation-delay: 1s;
}

.confetti:nth-child(4n) {
    background: #FFEB3B;
    animation-delay: 1.5s;
}

@keyframes confetti-fall {
    0% {
        transform: translateY(-100vh) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(100vh) rotate(360deg);
        opacity: 0;
    }
}

@media (max-width: 768px) {
    .complete-title {
        font-size: 2rem;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-complete-primary,
    .btn-complete-secondary {
        width: 100%;
        max-width: 300px;
    }
    
    .order-numbers {
        flex-direction: column;
        align-items: center;
    }
}

.rtl .action-buttons {
    flex-direction: row-reverse;
}
</style>

<div class="order-complete-container">
    <!-- Confetti Animation -->
    <div class="confetti" style="left: 10%"></div>
    <div class="confetti" style="left: 20%"></div>
    <div class="confetti" style="left: 30%"></div>
    <div class="confetti" style="left: 40%"></div>
    <div class="confetti" style="left: 50%"></div>
    <div class="confetti" style="left: 60%"></div>
    <div class="confetti" style="left: 70%"></div>
    <div class="confetti" style="left: 80%"></div>
    <div class="confetti" style="left: 90%"></div>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="complete-card p-5">
                    <div class="complete-icon">
                        <i class="fa fa-check"></i>
                    </div>
                    
                    <h1 class="complete-title">
                        🎉 
                        @if(isset($isNewCustomerInSession) && $isNewCustomerInSession)
                            {{ translate('Order_Placed_&_Account_Created_Successfully') }}!
                        @else
                            {{ translate('Order_Placed_Successfully') }}!
                        @endif
                    </h1>
                    
                    <p class="complete-message">
                        شكراً لك على طلبك! 🚀
                        <br>
                        <span class="text-muted">سيتم إرسال تفاصيل الطلب إلى بريدك الإلكتروني قريباً</span>
                    </p>
                    
                    @if (isset($order_ids) && count($order_ids) > 0)
                    <div class="order-details">
                        <h4 class="text-center mb-3">
                            <i class="fa fa-shopping-bag me-2"></i>
                            أرقام الطلبات:
                        </h4>
                        <div class="order-numbers">
                            @foreach ($order_ids as $key => $order)
                                <span class="order-number">#{{ $order }}</span>
                            @endforeach
                        </div>
                        <p class="text-center mt-3 text-muted">
                            <i class="fa fa-info-circle me-2"></i>
                            {{ translate('your_payment_has_been_successfully_processed_and_your_order') }}
                        </p>
                    </div>
                    @else
                    <div class="order-details">
                        <p class="text-center text-muted">
                            <i class="fa fa-clock me-2"></i>
                            {{ translate('your_order_is_being_processed_and_will_be_completed.') }}
                            <br>
                            {{ translate('You_will_receive_an_email_confirmation_when_your_order_is_placed.') }}
                        </p>
                    </div>
                    @endif
                    
                    <div class="action-buttons">
                        <a href="{{ route('track-order.index') }}" class="btn-complete-primary">
                            <i class="fa fa-truck me-2"></i>
                            {{ translate('track_Order')}}
                        </a>
                        <a href="{{route('home')}}" class="btn-complete-secondary">
                            <i class="fa fa-home me-2"></i>
                            {{ translate('Continue_Shopping') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click effect to buttons
    const buttons = document.querySelectorAll('.btn-complete-primary, .btn-complete-secondary');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
});
</script>
@endsection
