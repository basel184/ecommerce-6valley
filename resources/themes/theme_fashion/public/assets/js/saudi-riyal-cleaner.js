// Saudi Riyal ULTIMATE PRECISION Solution - Zero Text Interference
document.addEventListener('DOMContentLoaded', function() {
    
    // Function to safely clean duplicate riyal symbols ONLY in pure numeric prices
    function cleanDuplicateRiyalSymbols() {
        console.log('Saudi Riyal Ultra-Precise Cleaner: Starting...');
        
        // ULTRA-SAFE function to check if text is PURELY numeric price
        function isPureNumericPrice(text) {
            if (!text || typeof text !== 'string') return false;
            
            // Remove whitespace for testing
            const trimmed = text.trim();
            
            // Must contain riyal symbol
            if (!trimmed.includes('ريال')) return false;
            
            // Must NOT contain any Arabic letters (except riyal symbol)
            const withoutRiyal = trimmed.replace(/ريال/g, '');
            if (/[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/.test(withoutRiyal)) {
                return false; // Contains Arabic text
            }
            
            // Must match pattern: optional spaces + digits/commas/dots + optional spaces + riyal symbols + optional spaces
            return /^\s*[\d,]+\.?\d*\s*ريال+\s*$/.test(trimmed);
        }
        
        // Handle total-cost-wrapper with MAXIMUM PRECISION
        const totalCostWrappers = document.querySelectorAll('.total-cost-wrapper');
        totalCostWrappers.forEach(wrapper => {
            console.log('Processing total-cost-wrapper...');
            
            // Find all spans inside wrapper
            const spans = wrapper.querySelectorAll('span');
            spans.forEach((span, index) => {
                const originalText = span.textContent || '';
                console.log(`Checking span ${index}: "${originalText}"`);
                
                if (isPureNumericPrice(originalText)) {
                    const riyalCount = (originalText.match(/ريال/g) || []).length;
                    console.log(`✓ Pure price detected, riyal count: ${riyalCount}`);
                    
                    if (riyalCount > 1) {
                        const numberMatch = originalText.match(/([\d,]+\.?\d*)/);
                        if (numberMatch) {
                            const cleanText = numberMatch[0] + ' ريال';
                            span.textContent = cleanText;
                            console.log(`✓ Fixed: "${originalText}" → "${cleanText}"`);
                        }
                    }
                } else {
                    console.log(`✗ Skipped (not pure price): "${originalText}"`);
                }
            });
        });
        
        // Handle specific price element selectors
        const specificPriceSelectors = [
            '.product-details-chosen-price-amount',
            '.product-details-tax-amount', 
            '#product-details-delivery-cost'
        ];
        
        specificPriceSelectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                const originalText = element.textContent || '';
                
                if (isPureNumericPrice(originalText)) {
                    const riyalCount = (originalText.match(/ريال/g) || []).length;
                    
                    if (riyalCount > 1) {
                        const numberMatch = originalText.match(/([\d,]+\.?\d*)/);
                        if (numberMatch) {
                            const cleanText = numberMatch[0] + ' ريال';
                            element.textContent = cleanText;
                            console.log(`Fixed ${selector}: "${originalText}" → "${cleanText}"`);
                        }
                    }
                }
            });
        });
        
        // Handle proceed button - VERY CAREFUL with mixed content
        const proceedBtns = document.querySelectorAll('.proceed-cart-btn');
        proceedBtns.forEach(btn => {
            const buttonText = btn.textContent || '';
            if (buttonText.includes('ريال')) {
                const riyalCount = (buttonText.match(/ريال/g) || []).length;
                if (riyalCount > 1) {
                    // Find the price pattern and replace ONLY the price part
                    const updatedText = buttonText.replace(/([\d,]+\.?\d*)\s*ريال{2,}/g, '$1 ريال');
                    if (updatedText !== buttonText) {
                        btn.textContent = updatedText;
                        console.log(`Fixed button: "${buttonText}" → "${updatedText}"`);
                    }
                }
            }
        });
        
        console.log('Saudi Riyal Ultra-Precise Cleaner: Completed successfully.');
    }
    
    // Clean order summary currency display
    function cleanOrderSummaryPrices() {
        const orderSummary = document.querySelector('.total-cost-wrapper');
        if (!orderSummary) return;
        
        const priceElements = orderSummary.querySelectorAll('.total-cost-info li span:last-child, .proceed-cart-btn h6 span:last-child');
        
        priceElements.forEach(element => {
            let text = element.textContent || element.innerText;
            
            // Remove duplicate currency symbols
            text = text.replace(/ر\.س/g, '');
            text = text.replace(/SAR/g, '');
            text = text.replace(/SR/g, '');
            text = text.replace(/ريال/g, '');
            
            // Clean up extra spaces
            text = text.trim();
            
            // Add single Saudi Riyal symbol
            if (text && !text.includes('ريال')) {
                element.textContent = text + ' ريال';
            }
        });
        
        console.log('Order summary prices cleaned');
    }
    
    // Execute cleaning
    cleanDuplicateRiyalSymbols();
    cleanOrderSummaryPrices();
    
    // Execute after delays for dynamic content
    setTimeout(() => {
        cleanDuplicateRiyalSymbols();
        cleanOrderSummaryPrices();
    }, 500);
    setTimeout(() => {
        cleanDuplicateRiyalSymbols();
        cleanOrderSummaryPrices();
    }, 1500);
    setTimeout(() => {
        cleanDuplicateRiyalSymbols();
        cleanOrderSummaryPrices();
    }, 3000);
    
    // Monitor form changes that might affect prices
    document.addEventListener('change', function(e) {
        const relevantElements = ['quantity', 'color', 'size', 'variant'];
        if (relevantElements.some(name => e.target.name === name || e.target.matches(`[name="${name}"]`))) {
            setTimeout(() => {
                cleanDuplicateRiyalSymbols();
                cleanOrderSummaryPrices();
            }, 300);
        }
    });
    
    // Monitor DOM changes specifically in price-related areas
    const priceObserver = new MutationObserver(function(mutations) {
        let shouldClean = false;
        mutations.forEach(function(mutation) {
            if (mutation.target.closest('.total-cost-wrapper') || 
                mutation.target.closest('.proceed-cart-btn') ||
                mutation.target.closest('[class*="price"]') ||
                mutation.target.closest('[id*="price"]')) {
                shouldClean = true;
            }
        });
        if (shouldClean) {
            setTimeout(() => {
                cleanDuplicateRiyalSymbols();
                cleanOrderSummaryPrices();
            }, 100);
        }
    });
    
    // Start observing
    priceObserver.observe(document.body, { 
        childList: true, 
        subtree: true, 
        characterData: true 
    });
    
    console.log('Saudi Riyal Ultra-Precise Cleaner: Initialized successfully.');
});

// Export for manual use
window.cleanOrderSummaryPrices = cleanOrderSummaryPrices;
