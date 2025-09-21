// Enhanced Signature Products Slider
$(document).ready(function() {
    // Check if signature slider exists
    if ($('.signature-products-slider').length > 0) {
        
        // Destroy any existing owl carousel first
        $('.signature-products-slider').trigger('destroy.owl.carousel').removeClass('owl-loaded owl-drag');
        
        // Initialize the slider with enhanced settings
        var signatureSlider = $('.signature-products-slider').owlCarousel({
            items: 3,
            margin: 20,
            loop: true,
            autoplay: true,
            autoplayTimeout: 3000,
            autoplayHoverPause: true,
            smartSpeed: 800,
            nav: true,
            navText: [
                '<i class="fa fa-chevron-left" aria-hidden="true"></i>',
                '<i class="fa fa-chevron-right" aria-hidden="true"></i>'
            ],
            dots: true,
            mouseDrag: true,
            touchDrag: true,
            pullDrag: true,
            freeDrag: false,
            stagePadding: 0,
            merge: false,
            mergeFit: true,
            autoWidth: false,
            startPosition: 0,
            rtl: $('html').attr('dir') === 'rtl',
            center: false,
            animateOut: 'fadeOut',
            animateIn: 'fadeIn',
            responsive: {
                0: {
                    items: 1,
                    nav: false,
                    dots: true,
                    autoplayTimeout: 4000
                },
                480: {
                    items: 1,
                    nav: false,
                    dots: true,
                    autoplayTimeout: 4500
                },
                768: {
                    items: 2,
                    nav: true,
                    dots: true,
                    autoplayTimeout: 5000
                },
                992: {
                    items: 2,
                    nav: true,
                    dots: false,
                    autoplayTimeout: 5000
                },
                1200: {
                    items: 3,
                    nav: true,
                    dots: false,
                    autoplayTimeout: 5000
                }
            },
            onInitialized: function(event) {
                console.log('Signature Products Slider: Initialized successfully');
                $('.signature-products-slider-wrapper').removeClass('loading');
                
                // Add progress bar
                if ($('.signature-slider-progress').length === 0) {
                    $('.signature-products-slider').append('<div class="signature-slider-progress"></div>');
                }
            },
            onTranslate: function(event) {
                // Reset progress bar
                $('.signature-slider-progress').css('width', '0%');
            },
            onTranslated: function(event) {
                // Start progress bar
                $('.signature-slider-progress').css('width', '100%');
            },
            onDragged: function(event) {
                console.log('Signature Products Slider: Item dragged');
            }
        });
        
        // Custom controls
        $('.signature-slider-prev').on('click', function() {
            signatureSlider.trigger('prev.owl.carousel');
        });
        
        $('.signature-slider-next').on('click', function() {
            signatureSlider.trigger('next.owl.carousel');
        });
        
        // Play/Pause functionality
        $('.signature-slider-play-pause').on('click', function() {
            if ($(this).hasClass('paused')) {
                signatureSlider.trigger('play.owl.autoplay', [5000]);
                $(this).removeClass('paused').text('⏸️');
                $('.signature-products-slider').removeClass('paused');
            } else {
                signatureSlider.trigger('stop.owl.autoplay');
                $(this).addClass('paused').text('▶️');
                $('.signature-products-slider').addClass('paused');
            }
        });
        
        // Enhanced hover functionality
        $('.signature-products-slider').hover(
            function() {
                // Pause on hover
                $(this).trigger('stop.owl.autoplay');
                $('.signature-slider-progress').css('animation-play-state', 'paused');
            },
            function() {
                // Resume on leave
                $(this).trigger('play.owl.autoplay', [5000]);
                $('.signature-slider-progress').css('animation-play-state', 'running');
            }
        );
        
        // Keyboard navigation
        $(document).keydown(function(e) {
            if ($('.signature-products-slider:hover').length > 0) {
                if (e.keyCode == 37) { // Left arrow
                    signatureSlider.trigger('prev.owl.carousel');
                } else if (e.keyCode == 39) { // Right arrow
                    signatureSlider.trigger('next.owl.carousel');
                }
            }
        });
        
        // Touch gestures enhancement
        var touchStartX = 0;
        var touchEndX = 0;
        
        $('.signature-products-slider').on('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        $('.signature-products-slider').on('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
        
        function handleSwipe() {
            var swipeThreshold = 50;
            var diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Swipe left - next slide
                    signatureSlider.trigger('next.owl.carousel');
                } else {
                    // Swipe right - previous slide
                    signatureSlider.trigger('prev.owl.carousel');
                }
            }
        }
        
        // Auto-resize on window resize
        $(window).resize(function() {
            setTimeout(function() {
                signatureSlider.trigger('refresh.owl.carousel');
            }, 100);
        });
        
        // Progress bar animation sync
        var autoplayTimeout = 5000;
        var progressBar = $('.signature-slider-progress');
        
        signatureSlider.on('changed.owl.carousel', function(event) {
            progressBar.css({
                'width': '0%',
                'transition': 'none'
            });
            
            setTimeout(function() {
                progressBar.css({
                    'width': '100%',
                    'transition': 'width ' + autoplayTimeout + 'ms linear'
                });
            }, 10);
        });
        
        // Analytics tracking (optional)
        $('.signature-products-slider .owl-item').on('click', function() {
            var slideIndex = $(this).index();
            console.log('Signature Product Clicked: Slide ' + slideIndex);
            // Add your analytics code here
        });
        
        // Error handling
        signatureSlider.on('error.owl.carousel', function(error) {
            console.error('Signature Products Slider Error:', error);
        });
        
    } else {
        console.warn('Signature Products Slider: Element not found');
    }
});

// Fallback initialization if document ready doesn't work
$(window).on('load', function() {
    if ($('.signature-products-slider').length > 0 && !$('.signature-products-slider').hasClass('owl-loaded')) {
        console.log('Signature Products Slider: Fallback initialization');
        // Re-run initialization
        setTimeout(function() {
            $('.signature-products-slider').trigger('destroy.owl.carousel');
            // Re-initialize with basic settings
            $('.signature-products-slider').owlCarousel({
                items: 3,
                loop: true,
                autoplay: true,
                autoplayTimeout: 5000,
                nav: true,
                dots: true,
                responsive: {
                    0: { items: 1 },
                    768: { items: 2 },
                    1200: { items: 3 }
                }
            });
        }, 1000);
    }
});
