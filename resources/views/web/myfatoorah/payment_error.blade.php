@extends('theme-views.layouts.app')

@section('title', translate('payment_failed'))

@section('content')
<style>
.payment-error-container {
    min-height: 80vh;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    position: relative;
    overflow: hidden;
}

.payment-error-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    pointer-events: none;
}

.error-card {
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

.error-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
}

.error-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ff6b6b, #ee5a24, #ff4757);
    border-radius: 24px 24px 0 0;
}

.error-icon {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    position: relative;
    animation: errorShake 0.5s ease-in-out;
}

.error-icon::before {
    content: '';
    position: absolute;
    width: 140px;
    height: 140px;
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    border-radius: 50%;
    opacity: 0.3;
    animation: errorPulse 2s ease-in-out infinite;
}

@keyframes errorShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

@keyframes errorPulse {
    0%, 100% { transform: scale(1); opacity: 0.3; }
    50% { transform: scale(1.1); opacity: 0.1; }
}

.error-icon i {
    font-size: 60px;
    color: white;
    z-index: 2;
    position: relative;
}

.error-title {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1rem;
    text-align: center;
}

.error-message {
    font-size: 1.2rem;
    color: #666;
    text-align: center;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.error-details {
    background: linear-gradient(135deg, #fff5f5, #ffe8e8);
    border-radius: 16px;
    padding: 1.5rem;
    margin: 2rem 0;
    border: 1px solid rgba(255, 107, 107, 0.1);
}

.error-detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 107, 107, 0.1);
}

.error-detail-item:last-child {
    border-bottom: none;
}

.error-detail-label {
    font-weight: 600;
    color: #495057;
}

.error-detail-value {
    font-weight: 700;
    color: #ff6b6b;
    font-family: 'Courier New', monospace;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 2rem;
}

.btn-error-primary {
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    border: none;
    color: white;
    padding: 1rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    position: relative;
    overflow: hidden;
}

.btn-error-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.btn-error-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
    color: white;
    text-decoration: none;
}

.btn-error-primary:hover::before {
    left: 100%;
}

.btn-error-secondary {
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

.btn-error-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
    color: white;
    text-decoration: none;
}

.troubleshoot-section {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 16px;
    padding: 1.5rem;
    margin: 2rem 0;
    border-left: 4px solid #ff6b6b;
}

.troubleshoot-title {
    font-weight: 700;
    color: #495057;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.troubleshoot-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.troubleshoot-list li {
    padding: 0.5rem 0;
    color: #666;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.troubleshoot-list li::before {
    content: '•';
    color: #ff6b6b;
    font-weight: bold;
    font-size: 1.2rem;
}

@media (max-width: 768px) {
    .error-title {
        font-size: 2rem;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-error-primary,
    .btn-error-secondary {
        width: 100%;
        max-width: 300px;
    }
}

.rtl .error-detail-item {
    flex-direction: row-reverse;
}

.rtl .action-buttons {
    flex-direction: row-reverse;
}

.rtl .troubleshoot-list li {
    flex-direction: row-reverse;
}
</style>

<div class="payment-error-container">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="error-card p-5">
                    <div class="error-icon">
                        <i class="fa fa-times"></i>
                    </div>
                    
                    <h1 class="error-title">
                        ❌ {{ translate('payment_failed') }}
                    </h1>
                    
                    <p class="error-message">
                        {{ translate('payment_was_not_completed_successfully') }}
                        <br>
                        <span class="text-muted">لا تقلق، يمكنك المحاولة مرة أخرى! 🔄</span>
                    </p>
                    
                    @if(session('error'))
                    <div class="error-details">
                        <div class="error-detail-item">
                            <span class="error-detail-label">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                {{ translate('error_message') }}:
                            </span>
                            <span class="error-detail-value">{{ session('error') }}</span>
                        </div>
                    </div>
                    @endif
                    
                    <div class="troubleshoot-section">
                        <h4 class="troubleshoot-title">
                            <i class="fa fa-lightbulb"></i>
                            نصائح لحل المشكلة:
                        </h4>
                        <ul class="troubleshoot-list">
                            <li>تأكد من صحة بيانات البطاقة</li>
                            <li>تحقق من توفر الرصيد الكافي</li>
                            <li>تأكد من تفعيل الدفع عبر الإنترنت</li>
                            <li>جرب بطاقة أخرى أو طريقة دفع مختلفة</li>
                            <li>تواصل مع البنك إذا استمرت المشكلة</li>
                        </ul>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="{{ route('home') }}" class="btn-error-primary">
                            <i class="fa fa-home me-2"></i>
                            العودة للصفحة الرئيسية
                        </a>
                        <a href="{{ url()->previous() }}" class="btn-error-secondary">
                            <i class="fa fa-arrow-left me-2"></i>
                            المحاولة مرة أخرى
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
    const buttons = document.querySelectorAll('.btn-error-primary, .btn-error-secondary');
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
