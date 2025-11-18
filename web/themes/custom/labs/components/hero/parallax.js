/**
 * @file
 * parallax.js
 *
 * Add parallax effect to img elements.
 * The img element needs to be higher than the container around it, and have a class parallax__image.
 *
 */

// Version 2
((Drupal, once) => {
    /**
     * Custom object for this modules functions.
     */
    Drupal.blockHero = {
        /**
         * Function to render a Google Chart from given data.
         */
        getDynamicFixedOffset: function () {
            const selectors = [
                'aside.top-bar',
                'nav.site__header-top',
                '.dialog-off-canvas-main-canvas > header'
            ];

            return selectors.reduce((total, selector) => {
                const el = document.querySelector(selector);
                if (el) {
                    const rect = el.getBoundingClientRect();
                    return total + rect.height;
                }
                return total;
            }, 0);
        },
    },

        Drupal.behaviors.parallax = {
            attach(context) {
                const images = once('parallax', '.parallax__image', context);

                if (images.length === 0) return;

                // Dynamically calculate the offset from fixed elements
                const fixedOffset = Drupal.blockHero.getDynamicFixedOffset();

                const updateParallax = () => {
                    images.forEach(image => {
                        const imageContainer = image.closest('.hero__bg');
                        if (!imageContainer) return;

                        const rect = imageContainer.getBoundingClientRect();
                        const viewportHeight = window.innerHeight;
                        const containerHeight = rect.height;

                        // Adjusted viewport height to account for the fixed element
                        const adjustedViewportHeight = viewportHeight - fixedOffset;

                        // Total scroll range: from bottom of adjusted viewport to top of viewport
                        const scrollRange = containerHeight + adjustedViewportHeight;

                        // Progress: how far the top of the container has moved into the adjusted viewport
                        const progress = (adjustedViewportHeight - rect.top) / scrollRange;

                        // Clamp between 0 and 1
                        const clampedProgress = Math.min(Math.max(progress, 0), 1);

                        // Convert to percentage
                        const percentage = clampedProgress * 100;

                        if (image) {
                            image.style.setProperty('--image-offset-percentage', `${percentage}%`);
                        }
                    });
                };

                window.addEventListener('scroll', updateParallax);
                window.addEventListener('resize', updateParallax);
                window.addEventListener('load', updateParallax);
                updateParallax();
            }
        };
})(Drupal, once);
