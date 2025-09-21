// Order Summary Price Display Fix - مُحسِّن عرض الأسعار في ملخص الطلب
document.addEventListener('DOMContentLoaded', function() {
    
    console.log('Order Summary Price Fix: Starting...');
    
    // Function to fix prices that appear without riyal symbol
    function fixPricesWithoutRiyal() {
        
        // Target the total-cost-wrapper specifically
        const totalCostWrappers = document.querySelectorAll('.total-cost-wrapper');
        
        totalCostWrappers.forEach(wrapper => {
            console.log('Processing total-cost-wrapper for price fix...');
            
            // Find all spans that could contain prices
            const spans = wrapper.querySelectorAll('span');
            
            spans.forEach((span, index) => {
                const text = span.textContent || '';
                console.log(`Checking span ${index}: "${text}"`);
                
                // Check if text is a pure number (potential price without symbol)
                const trimmedText = text.trim();
                
                // Pattern for numbers like "389.00", "81.00", "470.00", etc.
                if (/^\d+(\.\d{2})?$/.test(trimmedText) && parseFloat(trimmedText) > 0) {
                    console.log(`✓ Found price without symbol: "${trimmedText}"`);
                    
                    // Check if parent or next sibling already has riyal symbol
                    const parentText = span.parentElement ? span.parentElement.textContent : '';
                    const hasRiyalNearby = parentText.includes('ريال');
                    
                    if (!hasRiyalNearby) {
                        // Add riyal symbol to the price
                        span.textContent = trimmedText + ' ريال';
                        span.classList.add('price-display');
                        console.log(`✓ Fixed price: "${trimmedText}" → "${trimmedText} ريال"`);
                    }
                }
                
                // Also handle negative prices (discounts)
                if (/^-\d+(\.\d{2})?$/.test(trimmedText) && parseFloat(trimmedText) < 0) {
                    console.log(`✓ Found negative price without symbol: "${trimmedText}"`);
                    
                    const parentText = span.parentElement ? span.parentElement.textContent : '';
                    const hasRiyalNearby = parentText.includes('ريال');
                    
                    if (!hasRiyalNearby) {
                        span.textContent = trimmedText + ' ريال';
                        span.classList.add('price-display');
                        console.log(`✓ Fixed negative price: "${trimmedText}" → "${trimmedText} ريال"`);
                    }
                }
            });
            
            // Also check for stray riyal symbols at the end
            const allText = wrapper.textContent || '';
            const riyalMatches = allText.match(/ريال/g);
            if (riyalMatches && riyalMatches.length === 1 && allText.endsWith(' ريال')) {
                // There's only one riyal symbol at the very end - this might be the stray one
                console.log('Found potential stray riyal symbol at end');
                
                // Find text nodes that contain only the riyal symbol
                const walker = document.createTreeWalker(
                    wrapper,
                    NodeFilter.SHOW_TEXT,
                    null,
                    false
                );
                
                let node;
                while (node = walker.nextNode()) {
                    if (node.textContent.trim() === 'ريال') {
                        console.log('Removing stray riyal symbol');
                        node.textContent = '';
                    }
                }
            }
        });
    }
    
    // Function to specifically handle the checkout button text
    function fixCheckoutButtonText() {
        const checkoutButtons = document.querySelectorAll('button[type="button"]');
        
        checkoutButtons.forEach(button => {
            const buttonText = button.textContent || '';
            
            // If button text ends with just "ريال" and doesn't seem right
            if (buttonText.includes('المتابعة') && buttonText.endsWith('ريال') && !buttonText.match(/\d+\s*ريال/)) {
                console.log('Fixing checkout button text...');
                // Remove the stray riyal symbol from button
                button.textContent = buttonText.replace(/\s*ريال$/, '');
                console.log(`Fixed button text: "${buttonText}" → "${button.textContent}"`);
            }
        });
    }
    
    // Function to ensure all price-display elements have proper formatting
    function ensurePriceDisplay() {
        const priceElements = document.querySelectorAll('.price-display');
        
        priceElements.forEach(element => {
            const text = element.textContent || '';
            
            // If it doesn't contain riyal symbol, add it
            if (!text.includes('ريال') && /^\d+(\.\d{2})?$/.test(text.trim())) {
                element.textContent = text.trim() + ' ريال';
                console.log(`Added riyal to price-display: "${text}" → "${element.textContent}"`);
            }
            
            // If it has duplicate riyal symbols, fix it
            const riyalCount = (text.match(/ريال/g) || []).length;
            if (riyalCount > 1) {
                const numberMatch = text.match(/([\d,]+\.?\d*)/);
                if (numberMatch) {
                    element.textContent = numberMatch[0] + ' ريال';
                    console.log(`Fixed duplicate riyal in price-display: "${text}" → "${element.textContent}"`);
                }
            }
        });
    }
    
    // Run fixes immediately
    fixPricesWithoutRiyal();
    fixCheckoutButtonText();
    ensurePriceDisplay();
    
    // Run fixes again after a short delay to catch dynamic content
    setTimeout(() => {
        console.log('Running delayed price fixes...');
        fixPricesWithoutRiyal();
        fixCheckoutButtonText();
        ensurePriceDisplay();
    }, 500);
    
    // Set up observer for dynamic changes
    const observer = new MutationObserver(function(mutations) {
        let shouldFix = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
                const target = mutation.target;
                if (target.closest && target.closest('.total-cost-wrapper')) {
                    shouldFix = true;
                }
            }
        });
        
        if (shouldFix) {
            console.log('DOM changed, re-running price fixes...');
            setTimeout(() => {
                fixPricesWithoutRiyal();
                fixCheckoutButtonText();
                ensurePriceDisplay();
            }, 100);
        }
    });
    
    // Observe the document for changes
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        characterData: true
    });
    
    console.log('Order Summary Price Fix: Initialized');
});
