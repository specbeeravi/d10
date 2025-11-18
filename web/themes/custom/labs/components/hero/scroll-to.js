/**
 * Scroll to the bottom of an element when clicking on another element with data-scroll-to attribute.
 * Example:
 * <div data-scroll-to=".p--banner">
 */

((Drupal) => {
  Drupal.behaviors.scrollToElement = {
    attach(context) {
      const triggers = context.querySelectorAll('[data-scroll-to]');
      triggers.forEach(trigger => {
        trigger.addEventListener('click', () => {
          const selector = trigger.getAttribute('data-scroll-to');
          const target = document.querySelector(selector);
          if (target) {
            const targetOffset = target.offsetTop + target.offsetHeight;
            window.scrollTo({
              top: targetOffset,
              behavior: 'smooth'
            });
          }
        });
      });
    }
  };
})(Drupal);
