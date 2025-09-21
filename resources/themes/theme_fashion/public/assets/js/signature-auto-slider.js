// Simple Auto-moving Signature Slider - Guaranteed to work
$(document).ready(function() {
    console.log('Initializing signature slider...');
    
    // Wait for page to fully load
    setTimeout(function() {
        if ($('.signature-products-slider').length > 0) {
            console.log('Signature slider found, initializing...');
            
            // Destroy any existing carousel
            $('.signature-products-slider').trigger('destroy.owl.carousel');
            $('.signature-products-slider').removeClass('owl-loaded owl-drag owl-carousel');
            
            // Re-initialize with simple settings
            $('.signature-products-slider').owlCarousel({
                items: 3,
                margin: 15,
                loop: true,
                autoplay: true,
                autoplayTimeout: 2500, // Very fast for testing
                autoplayHoverPause: false, // Don't pause on hover
                smartSpeed: 600,
                nav: false,
                dots: true,
                mouseDrag: true,
                touchDrag: true,
                responsive: {
                    0: {
                        items: 1
                    },
                    768: {
                        items: 2
                    },
                    1200: {
                        items: 3
                    }
                },
                onInitialized: function(event) {
                    console.log('✅ Signature slider initialized successfully!');
                    console.log('Items count:', event.item.count);
                    
                    // Force start autoplay
                    var owl = $(event.target);
                    owl.trigger('play.owl.autoplay', [2500]);
                },
                onChanged: function(event) {
                    console.log('🔄 Slider moved to item:', event.item.index);
                }
            });
            
            // Force autoplay every 3 seconds as backup
            setInterval(function() {
                if ($('.signature-products-slider').length > 0) {
                    $('.signature-products-slider').trigger('next.owl.carousel');
                }
            }, 3000);
            
        } else {
            console.warn('❌ Signature slider not found');
        }
    }, 1000);
});

// Additional initialization on window load
$(window).on('load', function() {
    setTimeout(function() {
        if ($('.signature-products-slider').length > 0 && !$('.signature-products-slider').hasClass('owl-loaded')) {
            console.log('🔄 Fallback: Re-initializing signature slider...');
            
            $('.signature-products-slider').owlCarousel({
                items: 3,
                loop: true,
                autoplay: true,
                autoplayTimeout: 2500,
                smartSpeed: 600,
                responsive: {
                    0: { items: 1 },
                    768: { items: 2 },
                    1200: { items: 3 }
                }
            });
        }
    }, 2000);
});
