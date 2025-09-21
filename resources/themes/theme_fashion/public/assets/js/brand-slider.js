// Brand Slider JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const brandSlider = document.querySelector('.creams-sliders3');
    
    if (!brandSlider) return;
    
    // Add smooth scrolling behavior
    let isScrolling = false;
    let scrollTimer = null;
    
    // Auto-scroll functionality (optional)
    function autoScroll() {
        if (isScrolling) return;
        
        const scrollAmount = 200;
        const maxScroll = brandSlider.scrollWidth - brandSlider.clientWidth;
        
        if (brandSlider.scrollLeft >= maxScroll) {
            brandSlider.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
            brandSlider.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    }
    
    // Pause auto-scroll on hover
    brandSlider.addEventListener('mouseenter', function() {
        isScrolling = true;
        if (scrollTimer) {
            clearInterval(scrollTimer);
        }
    });
    
    brandSlider.addEventListener('mouseleave', function() {
        isScrolling = false;
        // Uncomment the line below if you want auto-scroll
        // scrollTimer = setInterval(autoScroll, 3000);
    });
    
    // Touch/swipe support for mobile
    let startX = 0;
    let scrollLeft = 0;
    let isDown = false;
    
    brandSlider.addEventListener('touchstart', function(e) {
        isDown = true;
        startX = e.touches[0].pageX - brandSlider.offsetLeft;
        scrollLeft = brandSlider.scrollLeft;
        brandSlider.style.cursor = 'grabbing';
    });
    
    brandSlider.addEventListener('touchmove', function(e) {
        if (!isDown) return;
        e.preventDefault();
        const x = e.touches[0].pageX - brandSlider.offsetLeft;
        const walk = (x - startX) * 2;
        brandSlider.scrollLeft = scrollLeft - walk;
    });
    
    brandSlider.addEventListener('touchend', function() {
        isDown = false;
        brandSlider.style.cursor = 'grab';
    });
    
    // Mouse drag support for desktop
    brandSlider.addEventListener('mousedown', function(e) {
        isDown = true;
        startX = e.pageX - brandSlider.offsetLeft;
        scrollLeft = brandSlider.scrollLeft;
        brandSlider.style.cursor = 'grabbing';
    });
    
    brandSlider.addEventListener('mousemove', function(e) {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - brandSlider.offsetLeft;
        const walk = (x - startX) * 2;
        brandSlider.scrollLeft = scrollLeft - walk;
    });
    
    brandSlider.addEventListener('mouseup', function() {
        isDown = false;
        brandSlider.style.cursor = 'grab';
    });
    
    brandSlider.addEventListener('mouseleave', function() {
        isDown = false;
        brandSlider.style.cursor = 'grab';
    });
    
    // Keyboard navigation
    brandSlider.addEventListener('keydown', function(e) {
        switch(e.key) {
            case 'ArrowLeft':
                brandSlider.scrollBy({ left: -200, behavior: 'smooth' });
                e.preventDefault();
                break;
            case 'ArrowRight':
                brandSlider.scrollBy({ left: 200, behavior: 'smooth' });
                e.preventDefault();
                break;
        }
    });
    
    // Make slider focusable for keyboard navigation
    brandSlider.setAttribute('tabindex', '0');
    
    // Lazy loading for images
    const brandImages = brandSlider.querySelectorAll('img');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                }
            });
        });
        
        brandImages.forEach(img => {
            if (img.dataset.src) {
                imageObserver.observe(img);
            }
        });
    }
    
    // Add loading state
    brandImages.forEach(img => {
        // Initialize all images as loaded since they're not lazy-loaded
        img.style.opacity = '0';
        img.classList.add('loaded');
        
        img.addEventListener('load', function() {
            this.style.opacity = '1';
        });
        
        img.addEventListener('error', function() {
            this.style.opacity = '0.5';
            this.classList.add('error');
        });
        
        // If image is already loaded
        if (img.complete) {
            img.style.opacity = '1';
        }
    });
    
    // Analytics tracking (optional)
    brandSlider.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (link) {
            const brandName = link.querySelector('img').alt;
            // You can add analytics tracking here
            console.log('Brand clicked:', brandName);
        }
    });
    
    // Initialize cursor style
    brandSlider.style.cursor = 'grab';
    
    // Optional: Start auto-scroll (uncomment if needed)
    // scrollTimer = setInterval(autoScroll, 4000);
});

// Utility function to detect RTL
function isRTL() {
    return document.documentElement.dir === 'rtl' || 
           document.body.dir === 'rtl' ||
           window.getComputedStyle(document.body).direction === 'rtl';
}

// Add scroll indicator
document.addEventListener('DOMContentLoaded', function() {
    const brandSlider = document.querySelector('.creams-sliders3');
    if (!brandSlider) return;
    
    function updateScrollIndicator() {
        const scrollPercentage = (brandSlider.scrollLeft / (brandSlider.scrollWidth - brandSlider.clientWidth)) * 100;
        
        // Create or update scroll indicator
        let indicator = document.querySelector('.brand-scroll-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'brand-scroll-indicator';
            indicator.style.cssText = `
                position: absolute;
                bottom: -10px;
                left: 50%;
                transform: translateX(-50%);
                width: 100px;
                height: 3px;
                background: rgba(0, 123, 255, 0.2);
                border-radius: 2px;
                overflow: hidden;
            `;
            
            const progress = document.createElement('div');
            progress.className = 'scroll-progress';
            progress.style.cssText = `
                height: 100%;
                background: #007bff;
                border-radius: 2px;
                transition: width 0.3s ease;
                width: 0%;
            `;
            
            indicator.appendChild(progress);
            brandSlider.parentNode.appendChild(indicator);
            brandSlider.parentNode.style.position = 'relative';
        }
        
        const progress = indicator.querySelector('.scroll-progress');
        progress.style.width = scrollPercentage + '%';
    }
    
    brandSlider.addEventListener('scroll', updateScrollIndicator);
    updateScrollIndicator(); // Initial call
});
