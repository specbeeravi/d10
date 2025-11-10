(function (Drupal) {
  'use strict';

  Drupal.behaviors.webformBookingPriceElement = {
    attach: function (context, settings) {
      context.querySelectorAll('.webform-booking-price-element').forEach(function(element) {
        if (element.dataset.processed) return;
        element.dataset.processed = true;

        const priceDisplay = element.querySelector('.webform-booking-price-element__price');
        const quantityInput = element.querySelector('.webform-booking-price-element__quantity');
        const subtotalDisplay = element.querySelector('.webform-booking-price-display');
        const maxUnits = parseInt(element.getAttribute('max-units'), 10);
        const price = parseFloat(priceDisplay.dataset.price);
        const currencySymbol = priceDisplay.textContent.trim().charAt(0);
        quantityInput.setAttribute('max', maxUnits);

        function updateSubtotal() {
          let quantity = parseInt(quantityInput.value, 10) || 0;

          // Ensure the quantity doesn't exceed maxUnits
          if (quantity > maxUnits) {
            quantity = maxUnits;
            quantityInput.value = maxUnits;
          }

          const subtotal = (price * quantity).toFixed(2);
          subtotalDisplay.setAttribute('data-price', subtotal);
          subtotalDisplay.textContent = `Subtotal: ${currencySymbol}${subtotal}`;

          // Update the form element value
          quantityInput.value = quantity;

          // Update total price
          if (typeof window.updateTotalPrice === 'function') {
            window.updateTotalPrice();
          }
        }

        quantityInput.addEventListener('input', updateSubtotal);
        quantityInput.addEventListener('change', updateSubtotal);

        // Initial update
        updateSubtotal();
      });
    }
  };

})(Drupal);
