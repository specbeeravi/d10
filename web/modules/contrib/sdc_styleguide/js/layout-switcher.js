(() => {

  'use strict';

  /**
   * Initializes the scrollbars to make the UI better.
   */
  Drupal.behaviors.initializeExplorerScroll = {
    attach: (context, settings) => {
      once('scrollbars-init', '.sdc-styleguide-page__components-list', context).forEach(list => {
        const { OverlayScrollbars, ClickScrollPlugin } = OverlayScrollbarsGlobal;
        OverlayScrollbars.plugin(ClickScrollPlugin);

        OverlayScrollbars(document.body, {
          scrollbars: {
            clickScroll: true,
          },
        });
        OverlayScrollbars(list, {});
      });
    }
  };

  /**
   * Initializes the layout switcher plugin.
   */
  Drupal.behaviors.sdcStyleguideLayoutSwitcherInit = {
    attach: (context, settings) => {
      const switcher = once('layout-switcher-init', '.layout-switcher__trigger', context);
      if (switcher.length == 0) {
        return;
      }

      const page = document.querySelector('.sdc-styleguide-page');
      page.classList.add('sdc-styleguide-page--alternate-layout');
      switcher[0].addEventListener('click', e => {
        page.classList.toggle('sdc-styleguide-page--alternate-layout');
        page.classList.toggle('sdc-styleguide-page--default-layout');
      });
    }
  };

})();
