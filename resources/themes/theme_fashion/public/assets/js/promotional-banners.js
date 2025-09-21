// Promotional Banners JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize promotional banners
    const promotionItems = document.querySelectorAll('.promotion-item');
    
    if (promotionItems.length === 0) return;
    
    // Add loading animation for images
    promotionItems.forEach((item, index) => {
        const img = item.querySelector('.promotion-image img');
        const link = item.querySelector('.slide-content-link');
        
        if (img) {
            // Add loading state
            img.style.opacity = '0';
            
            img.addEventListener('load', function() {
                this.style.opacity = '1';
                this.classList.add('loaded');
            });
            
            img.addEventListener('error', function() {
                this.style.opacity = '0.5';
                this.classList.add('error');
                console.warn('Failed to load image:', this.src);
            });
            
            // If image is already loaded
            if (img.complete) {
                img.style.opacity = '1';
                img.classList.add('loaded');
            }
        }
        
        // Add click tracking
        if (link) {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                const altText = item.querySelector('img')?.alt || 'Unknown Banner';
                
                // Analytics tracking (optional)
                console.log('Promotional banner clicked:', {
                    banner: altText,
                    url: href,
                    index: index
                });
                
                // Add click effect
                const span = this.querySelector('span');
                if (span) {
                    span.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        span.style.transform = 'scale(1.1)';
                    }, 100);
                }
            });
        }
    });
    
    // Intersection Observer for animation
    if ('IntersectionObserver' in window) {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        promotionItems.forEach(item => {
            observer.observe(item);
        });
    }
    
    // Touch/swipe support for mobile
    promotionItems.forEach(item => {
        let startY = 0;
        let startTime = 0;
        
        item.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY;
            startTime = Date.now();
        });
        
        item.addEventListener('touchend', function(e) {
            const endY = e.changedTouches[0].clientY;
            const endTime = Date.now();
            const distance = Math.abs(endY - startY);
            const duration = endTime - startTime;
            
            // If it's a quick swipe up/down, don't trigger click
            if (distance > 50 && duration < 300) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Keyboard navigation support
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            const focusedLink = document.activeElement;
            if (focusedLink && focusedLink.classList.contains('slide-content-link')) {
                e.preventDefault();
                focusedLink.click();
            }
        }
    });
    
    // Lazy loading for promotional images
    const lazyImages = document.querySelectorAll('.promotion-image img[data-src]');
    
    if ('IntersectionObserver' in window && lazyImages.length > 0) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // Preload images on hover for better UX
    promotionItems.forEach(item => {
        const img = item.querySelector('.promotion-image img');
        if (img && !img.complete) {
            item.addEventListener('mouseenter', function() {
                const tempImg = new Image();
                tempImg.src = img.src;
            }, { once: true });
        }
    });
    
    // Add subtle parallax effect on scroll (optional)
    let ticking = false;
    
    function updateParallax() {
        const scrollY = window.pageYOffset;
        
        promotionItems.forEach((item, index) => {
            const rect = item.getBoundingClientRect();
            const speed = 0.5;
            
            if (rect.top < window.innerHeight && rect.bottom > 0) {
                const yPos = -(scrollY - rect.top) * speed;
                const img = item.querySelector('.promotion-image img');
                if (img) {
                    img.style.transform = `translateY(${yPos}px) scale(1)`;
                }
            }
        });
        
        ticking = false;
    }
    
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateParallax);
            ticking = true;
        }
    }
    
    // Uncomment the line below to enable parallax effect
    // window.addEventListener('scroll', requestTick);
});

// Utility functions
function isElementInViewport(el) {
    const rect = el.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Export for potential external use
window.PromotionalBanners = {
    init: function() {
        // Re-initialize if needed
        document.dispatchEvent(new Event('DOMContentLoaded'));
    }
};
