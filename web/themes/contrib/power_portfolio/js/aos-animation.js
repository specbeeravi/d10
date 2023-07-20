(function ($, Drupal) {
  Drupal.behaviors.bannerAnimation = {
    attach: function (context, settings) {
      "use strict";

      /**
       * Easy event listener function
       */
      const on = (type, el, listener) => {
        $(el, context).once().on(type, listener);
      };

      /**
       * AOS initialization
       */
      AOS.init({
        duration: 1000,
        easing: "ease-in-out",
        once: true,
        mirror: false
      });

    }
  };
})(jQuery, Drupal);
