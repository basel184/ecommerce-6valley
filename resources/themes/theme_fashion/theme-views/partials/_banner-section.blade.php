@php
    // Use only real banner data from database
    $bannersToShow = $bannerTypeMainBanner ?? collect([]);
@endphp

@if ($bannersToShow->count() > 0)
<section class="hero-slider-container {{ session()->get('direction') == 'rtl' ? 'rtl-hero' : '' }}">
    <div id="main-slider" class="hero-carousel owl-carousel">
        @foreach($bannersToShow as $banner)
        <div class="hero-slide-wrapper" data-bg-color="{{ $banner->background_color ?? '#007bff' }}">
            <a href="{{ $banner->url ?? 'javascript:' }}" class="hero-clickable-background">
                <div class="hero-image-container" style="background-image: url('{{ getStorageImages(path: $banner->photo_full_url, type:'product') }}');">
                </div>
            </a>

            <div class="hero-decoration-shape {{ session()->get('direction') == 'rtl' ? 'rtl-shape' : '' }}">
                <svg width="16" height="44" viewBox="0 0 16 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g filter="url(#heroShapeFilter)">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M0.987292 43.5471C2.37783 38.4513 6.40927 34.0997 10.2104 29.9969C10.7306 29.4354 11.2464 28.8785 11.7506 28.3251C12.3698 27.6454 12.9261 26.9375 13.4285 26.2154C15.7758 22.8419 15.7065 18.2693 13.2818 14.9509C12.1188 13.3593 10.7689 11.9386 9.18884 10.7511C5.58277 8.04099 1.99367 4.63569 0.853516 0.455078L0.987292 43.5471Z" fill="currentColor"/>
                    </g>
                    <defs>
                        <filter id="heroShapeFilter" x="-46.9791" y="-47.3775" width="109.958" height="138.757" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                    <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                    <feGaussianBlur in="BackgroundImageFix" stdDeviation="23.9163"/>
                        <feComposite in2="SourceAlpha" operator="in" result="effect1_backgroundBlur"/>
                        <feBlend mode="normal" in="SourceGraphic" in2="effect1_backgroundBlur" result="shape"/>
                    </filter>
                    </defs>
                </svg>
            </div>
        </div>
        @endforeach
    </div>
</section>
@else
    <section class="empty-banner-section">
        <div class="empty-banner-placeholder"></div>
    </section>
@endif

<style>
     /* Hero Slider Container */
     .hero-slider-container {
         position: relative;
         width: 100%;
         height: 100vh;
         min-height: 600px;
         overflow: hidden;
     }
     
     @media (max-width: 768px) {
         /* Hero Slider Container */
         .hero-image-container[style*="background-image"] {
             background-size: cover !important;
             height: 200px;
             min-height: auto;
         }
         .hero-slider-container {
             background-size: cover !important;
         }
         .hero-slider-container {
             height: 200px !important;
             min-height: auto !important;
         }
     }

    /* Hero Carousel */
    .hero-carousel {
        height: 100%;
    }
    /* Smooth transitions */
    .owl-carousel .owl-item {
        transition: all 0.5s ease;
    }
    
    /* Ensure autoplay works */
    .hero-carousel.owl-carousel {
        position: relative;
    }
      
    .hero-carousel .owl-stage-outer,
    .hero-carousel .owl-stage,
    .hero-carousel .owl-item {
        height: 100%;
    }
    
    /* Hero Slide Wrapper */
    .hero-slide-wrapper {
        position: relative;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    /* Hero Clickable Background */
    .hero-clickable-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        text-decoration: none;
        display: block;
        cursor: pointer;
    }
    
    .hero-clickable-background:hover {
        text-decoration: none;
    }
    
    /* Hero Image Container */
    .hero-image-container {
        position: relative;
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        min-height: 100%;
    }
    
    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(
            135deg,
            rgba(0, 0, 0, 0.4) 0%,
            rgba(0, 0, 0, 0.2) 50%,
            rgba(0, 0, 0, 0.6) 100%
        );
        z-index: 2;
        pointer-events: none;
    }
    
    /* Dynamic banner images from database */
    .hero-image-container {
        background-size: cover !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
    }
    
    /* Ensure images load properly */
    .hero-image-container[style*="background-image"] {
        background-size: cover !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
    }
    
    /* Ensure proper image display */
    .hero-image-container {
        position: relative;
        overflow: hidden;
    }
    
    /* Force image display */
    .hero-image-container[style*="background-image"] {
        background-attachment: scroll !important;
        background-clip: border-box !important;
        background-origin: padding-box !important;
    }
    
    /* Debug image loading */
    .hero-image-container {
        min-height: 100vh;
        min-height: 100dvh;
    }
    
    /* Ensure images are visible */
    .hero-image-container[style*="background-image"] {
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Mobile responsive for images */
    @media screen and (max-width: 768px) {
        .hero-image-container {
            min-height: 70vh;
        }
    }
    
    @media screen and (max-width: 480px) {
        .hero-image-container {
            min-height: 60vh;
        }
    }
    
    /* Hero Content */
    .hero-content-wrapper {
        position: relative;
        z-index: 4;
        width: 100%;
        max-width: 1200px;
        padding: 0 2rem;
        text-align: center;
        pointer-events: none;
    }
    
    .hero-content-wrapper a {
        pointer-events: auto;
    }
    
    .hero-text-container {
        animation: slideInUp 1s ease-out;
    }
    
    .hero-main-title {
        font-size: 4rem;
        font-weight: 800;
        color: white;
        margin-bottom: 1.5rem;
        text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.7);
        line-height: 1.2;
        letter-spacing: -0.02em;
    }
    
    .hero-subtitle {
        display: block;
        font-size: 2.5rem;
        font-weight: 300;
        color: #f8f9fa;
        margin-top: 0.5rem;
        text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.5);
    }
    
    .hero-action-container {
        margin-top: 2.5rem;
        animation: slideInUp 1s ease-out 0.3s both;
    }
    
    .hero-cta-button {
        display: inline-block;
        padding: 18px 40px;
        font-size: 1.2rem;
        font-weight: 600;
        color: white;
        background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        border: none;
        border-radius: 50px;
        text-decoration: none;
        box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
        pointer-events: auto;
    }
    
    .hero-cta-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.6s;
    }
    
    .hero-cta-button:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 12px 35px rgba(255, 107, 107, 0.6);
        color: white;
        text-decoration: none;
        pointer-events: auto;
    }
    
    .hero-cta-button:hover::before {
        left: 100%;
    }
    
    /* Hero Decoration Shape */
    .hero-decoration-shape {
        position: absolute;
        bottom: 0;
        right: 0;
        z-index: 2;
        color: rgba(255, 255, 255, 0.1);
        display: none;
    }
    
    @media (min-width: 768px) {
        .hero-decoration-shape {
            display: block;
        }
    }
    
    /* Owl Carousel Navigation */
    .hero-carousel .owl-nav {
        position: absolute;
        top: 50%;
        width: 100%;
        z-index: 4;
        transform: translateY(-50%);
    }
    
    .hero-carousel .owl-prev,
    .hero-carousel .owl-next {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.15) !important;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        backdrop-filter: blur(10px);
    }
    
    .hero-carousel .owl-prev {
        left: 30px;
    }
    
    .hero-carousel .owl-next {
        right: 30px;
    }
    
    .hero-carousel .owl-prev:hover,
    .hero-carousel .owl-next:hover {
        background: rgba(255, 255, 255, 0.25) !important;
        border-color: rgba(255, 255, 255, 0.6);
        transform: translateY(-50%) scale(1.1);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    
    /* Owl Carousel Dots */
    .hero-carousel .owl-dots {
        position: absolute;
        bottom: 40px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 4;
        display: flex;
        gap: 12px;
    }
    
    .hero-carousel .owl-dot {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.4);
        border: 2px solid rgba(255, 255, 255, 0.6);
        transition: all 0.4s ease;
        cursor: pointer;
    }
    
    .hero-carousel .owl-dot.active {
        background: white;
        border-color: white;
        transform: scale(1.3);
        box-shadow: 0 4px 15px rgba(255, 255, 255, 0.4);
    }
    
    /* Empty Banner Section */
    .empty-banner-section {
        height: 400px;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .empty-banner-placeholder {
        width: 100%;
        height: 100%;
        background: repeating-linear-gradient(
            45deg,
            #f8f9fa,
            #f8f9fa 10px,
            #e9ecef 10px,
            #e9ecef 20px
        );
    }
    
    /* Animations */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    /* Mobile Responsive */
    @media screen and (max-width: 1200px) {
        .hero-slider-container {
            height: 80vh;
            min-height: 500px;
        }
        
        .hero-main-title {
            font-size: 3.5rem;
        }
        
        .hero-subtitle {
            font-size: 2rem;
        }
    }
    
    @media screen and (max-width: 768px) {
        .hero-slider-container {
            height: 70vh;
            min-height: 450px;
        }
        
        .hero-content-wrapper {
            padding: 0 1rem;
        }
        
        .hero-main-title {
            font-size: 2.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
        }
        
        .hero-cta-button {
            padding: 15px 30px;
            font-size: 1rem;
        }
        
        .hero-carousel .owl-prev,
        .hero-carousel .owl-next {
            width: 50px;
            height: 50px;
            font-size: 20px;
        }
        
        .hero-carousel .owl-prev {
            left: 15px;
        }
        
        .hero-carousel .owl-next {
            right: 15px;
        }
        
        .hero-carousel .owl-dots {
            bottom: 20px;
        }
    }
    
    @media screen and (max-width: 480px) {
        .hero-slider-container {
            height: 60vh;
            min-height: 400px;
        }
        
        .hero-main-title {
            font-size: 2rem;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
        }
        
        .hero-cta-button {
            padding: 12px 25px;
            font-size: 0.9rem;
        }
    }
    
    /* RTL Support */
    .rtl-hero .hero-content-wrapper {
        text-align: center;
        direction: rtl;
    }
    
    .rtl-content {
        text-align: center;
        direction: rtl;
    }
    
    .rtl-content .hero-main-title {
        font-family: 'GE SS Two', 'Arial', sans-serif;
        line-height: 1.4;
        letter-spacing: 0;
    }
    
    .rtl-content .hero-subtitle {
        font-family: 'GE SS Two', 'Arial', sans-serif;
        display: block;
        margin-top: 0.5rem;
    }
    
    .rtl-content .hero-cta-button {
        font-family: 'GE SS Two', 'Arial', sans-serif;
        font-weight: 500;
    }
    
    .rtl-shape {
        right: auto;
        left: 0;
        transform: scaleX(-1);
    }
    
    .rtl-hero .hero-carousel .owl-prev {
        left: auto;
        right: 30px;
    }
    
    .rtl-hero .hero-carousel .owl-next {
        right: auto;
        left: 30px;
    }
    
    .rtl-hero .hero-carousel .owl-dots {
        left: 50%;
        transform: translateX(-50%);
    }
    
    /* RTL Mobile Support */
    @media screen and (max-width: 768px) {
        .rtl-hero .hero-carousel .owl-prev {
            left: auto;
            right: 15px;
        }
        
        .rtl-hero .hero-carousel .owl-next {
            right: auto;
            left: 15px;
        }
        
        .rtl-content .hero-main-title {
            font-size: 2.5rem;
            line-height: 1.3;
        }
        
        .rtl-content .hero-subtitle {
            font-size: 1.5rem;
        }
    }
    
    @media screen and (max-width: 480px) {
        .rtl-content .hero-main-title {
            font-size: 2rem;
            line-height: 1.2;
        }
        
        .rtl-content .hero-subtitle {
            font-size: 1.2rem;
        }
        
        .rtl-content .hero-cta-button {
            font-size: 0.9rem;
            padding: 12px 25px;
        }
    }
    
    /* Loading Animation */
    .hero-carousel .owl-item {
        animation: fadeInScale 0.8s ease-out;
    }
    
    /* RTL Text Direction */
    .rtl-hero {
        direction: rtl;
    }
    
    .rtl-hero .hero-main-title,
    .rtl-hero .hero-subtitle,
    .rtl-hero .hero-cta-button {
        direction: rtl;
        text-align: center;
    }
    
    /* RTL Font Support */
    .rtl-hero .hero-main-title {
        font-family: 'GE SS Two', 'Cairo', 'Tajawal', 'Arial', sans-serif;
        font-weight: 700;
    }
    
    .rtl-hero .hero-subtitle {
        font-family: 'GE SS Two', 'Cairo', 'Tajawal', 'Arial', sans-serif;
        font-weight: 300;
    }
    
    .rtl-hero .hero-cta-button {
        font-family: 'GE SS Two', 'Cairo', 'Tajawal', 'Arial', sans-serif;
        font-weight: 500;
    }
    
    /* RTL Shape Positioning */
    .rtl-hero .hero-decoration-shape {
        right: auto;
        left: 0;
        transform: scaleX(-1);
    }
    
    /* RTL Navigation Buttons */
    .rtl-hero .hero-carousel .owl-nav {
        direction: rtl;
    }
    
    /* RTL Content Alignment */
    .rtl-hero .hero-content-wrapper {
        direction: rtl;
        text-align: center;
    }
    
    /* RTL Button Hover Effects */
    .rtl-hero .hero-cta-button:hover {
        transform: translateY(-3px) scale(1.05);
    }
    
    /* RTL Mobile Responsive */
    @media screen and (max-width: 1200px) {
        .rtl-hero .hero-main-title {
            font-size: 3.5rem;
        }
        
        .rtl-hero .hero-subtitle {
            font-size: 2rem;
        }
    }
    
    @media screen and (max-width: 768px) {
        .rtl-hero .hero-main-title {
            font-size: 2.5rem;
            line-height: 1.3;
        }
        
        .rtl-hero .hero-subtitle {
            font-size: 1.5rem;
        }
        
        .rtl-hero .hero-cta-button {
            font-size: 1rem;
            padding: 15px 30px;
        }
    }
    
    @media screen and (max-width: 480px) {
        .rtl-hero .hero-main-title {
            font-size: 2rem;
            line-height: 1.2;
        }
        
        .rtl-hero .hero-subtitle {
            font-size: 1.2rem;
        }
        
        .rtl-hero .hero-cta-button {
            font-size: 0.9rem;
            padding: 12px 25px;
        }
    }
</style>

<script>
$(document).ready(function() {
    // Check if RTL direction
    var isRTL = $('html').attr('dir') === 'rtl' || $('body').hasClass('rtl') || $('.hero-slider-container').hasClass('rtl-hero');
    
    $("#main-slider").owlCarousel({
        navigation : true,
        slideSpeed : 300,
        paginationSpeed : 400,
        items : 1, 
        itemsDesktop : false,
        itemsDesktopSmall : false,
        itemsTablet: false,
        itemsMobile : false,
        autoplay: true,
        autoplayTimeout: 4000, // 4 seconds between slides
        autoplayHoverPause: true,
        autoplaySpeed: 800, // Speed of transition
        loop: true,
        dots: true,
        animateOut: 'fadeOut',
        animateIn: 'fadeIn',
        smartSpeed: 1000,
        rtl: isRTL,
        onInitialized: function() {
            console.log('Hero slider initialized successfully');
            console.log('RTL Mode:', isRTL);
            console.log('Autoplay enabled:', true);
        },
        onChanged: function(event) {
            $('.hero-slide-wrapper').removeClass('active');
            $('.owl-item.active .hero-slide-wrapper').addClass('active');
        },
        onTranslate: function(event) {
            console.log('Slider is moving automatically');
        }
    });
    
    // Add active class to first slide
    $('.owl-item.active .hero-slide-wrapper').addClass('active');
    
    // RTL Navigation Button Text
    if (isRTL) {
        $('.hero-carousel .owl-prev').html('&#8250;');
        $('.hero-carousel .owl-next').html('&#8249;');
    } else {
        $('.hero-carousel .owl-prev').html('&#8249;');
        $('.hero-carousel .owl-next').html('&#8250;');
    }
    
    // Force autoplay to start
    setTimeout(function() {
        $("#main-slider").trigger('play.owl.autoplay');
        console.log('Forced autoplay to start');
    }, 1000);
    
    // Ensure autoplay continues
    setInterval(function() {
        if (!$("#main-slider").hasClass('owl-autoplay')) {
            $("#main-slider").trigger('play.owl.autoplay');
            console.log('Restarted autoplay');
        }
    }, 5000);
});
</script>

