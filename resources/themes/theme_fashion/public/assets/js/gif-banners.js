// GIF Banners Interactive Features
document.addEventListener('DOMContentLoaded', function() {
    
    // Add loading placeholder while GIFs load
    const gifBanners = document.querySelectorAll('.gif-banner');
    
    gifBanners.forEach(function(gif, index) {
        // Create loading overlay
        const wrapper = gif.closest('.gif-banner-wrapper');
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'gif-loading-overlay';
        loadingOverlay.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        `;
        loadingOverlay.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(248, 249, 250, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 8px;
        `;
        
        wrapper.style.position = 'relative';
        wrapper.appendChild(loadingOverlay);
        
        // Hide loading when image loads
        gif.addEventListener('load', function() {
            setTimeout(function() {
                loadingOverlay.style.opacity = '0';
                loadingOverlay.style.transition = 'opacity 0.3s ease';
                setTimeout(function() {
                    if (loadingOverlay.parentNode) {
                        loadingOverlay.parentNode.removeChild(loadingOverlay);
                    }
                }, 300);
            }, 100); // Small delay to ensure smooth loading
        });
        
        // Handle loading error
        gif.addEventListener('error', function() {
            loadingOverlay.innerHTML = `
                <div class="text-center text-muted">
                    <i class="bi bi-image-alt fs-1"></i>
                    <div class="mt-2">فشل في تحميل الصورة</div>
                </div>
            `;
        });
        
        // Add click tracking (optional)
        gif.addEventListener('click', function() {
            console.log('GIF Banner clicked:', index + 1);
            // You can add analytics tracking here
        });
    });
    
    // Intersection Observer for lazy loading animation
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('gif-banner-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });
        
        document.querySelectorAll('.gif-banner-wrapper').forEach(function(wrapper) {
            observer.observe(wrapper);
        });
    }
    
    // Add pause/play functionality on hover (for performance)
    let pauseTimer;
    gifBanners.forEach(function(gif) {
        const originalSrc = gif.src;
        
        gif.addEventListener('mouseenter', function() {
            clearTimeout(pauseTimer);
            // Resume GIF animation
            if (gif.style.animationPlayState === 'paused') {
                gif.style.animationPlayState = 'running';
            }
        });
        
        gif.addEventListener('mouseleave', function() {
            // Pause after a delay to save resources
            pauseTimer = setTimeout(function() {
                // This is a placeholder - actual GIF pause requires different approach
                // gif.style.animationPlayState = 'paused';
            }, 5000);
        });
    });
    
    console.log('🎬 GIF Banners initialized successfully!');
});
