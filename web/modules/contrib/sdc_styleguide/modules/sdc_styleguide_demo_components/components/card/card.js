(($, Drupal) => {

  Drupal.behaviors.initializeCards = {
    attach: (context, settings) => {
      once('card-init', '.card', context).forEach(c => {
        console.log('jQuery available if needed.', $);
        c.querySelector('.card__media').addEventListener('click', e => {
          c.querySelector('.card__link').dispatchEvent(new MouseEvent('click'));
        });
      });
    }
  }

})(jQuery, Drupal);
