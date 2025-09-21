// Saudi Riyal Symbol Handler
document.addEventListener('DOMContentLoaded', function() {
    
    // Function to create Saudi Riyal symbol element
    function createSaudiRiyalSymbol(size = 'normal') {
        const span = document.createElement('span');
        span.className = 'saudi-riyal-symbol';
        
        if (size === 'small') {
            span.className += ' saudi-riyal-symbol-small';
        } else if (size === 'large') {
            span.className += ' saudi-riyal-symbol-large';
        }
        
        span.title = 'ريال سعودي';
        return span;
    }
    
    // Function to format price with new Saudi symbol
    function formatPriceWithSaudiSymbol(amount, size = 'normal', position = 'right') {
        const formattedAmount = parseFloat(amount).toFixed(2);
        const symbol = createSaudiRiyalSymbol(size);
        
        if (position === 'left') {
            return symbol.outerHTML + ' ' + formattedAmount;
        } else {
            return formattedAmount + ' ' + symbol.outerHTML;
        }
    }
    
    // Update prices when cart is updated
    function updatePriceDisplays() {
        // Update main product price
        const priceElements = document.querySelectorAll('.product-details-chosen-price-amount');
        priceElements.forEach(element => {
            const amount = element.textContent.replace(/[^\d.]/g, '');
            if (amount && !element.querySelector('.saudi-riyal-symbol')) {
                element.innerHTML = formatPriceWithSaudiSymbol(amount);
            }
        });
        
        // Update delivery costs
        const deliveryElements = document.querySelectorAll('.product-details-delivery-cost');
        deliveryElements.forEach(element => {
            const amount = element.textContent.replace(/[^\d.]/g, '');
            if (amount && !element.querySelector('.saudi-riyal-symbol')) {
                element.innerHTML = formatPriceWithSaudiSymbol(amount, 'small');
            }
        });
    }
    
    // Initial update
    updatePriceDisplays();
    
    // Update when product variant changes
    document.addEventListener('change', function(e) {
        if (e.target.matches('[name="color"], [name="size"], select[name*="attribute"]')) {
            setTimeout(updatePriceDisplays, 500);
        }
    });
    
    // Update when quantity changes
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[name="quantity"]')) {
            setTimeout(updatePriceDisplays, 500);
        }
    });
});
