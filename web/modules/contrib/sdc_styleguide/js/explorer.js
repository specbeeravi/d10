((Drupal) => {

  'use strict';

  // Updates to get a proper resolution based on device.
  window.addEventListener('resize', () => {
    document.documentElement.style.setProperty('--sdcs-explorer-viewport-height', document.documentElement.clientHeight + 'px');
    document.documentElement.style.setProperty('--sdcs-explorer-viewport-width', document.documentElement.clientWidth + 'px');
  });
  window.dispatchEvent(new Event('resize'));

  /**
   * Initializes the explorer interaction.
   */
  Drupal.behaviors.initializeExplorer = {
    attach: (context, settings) => {
      once('explorer-init', '.sdc-styleguide-explorer', context).forEach(ex => {
        // Initializes links to update the iframe src.
        ex.querySelectorAll('.sdc-styleguide-explorer__demo-link').forEach(a => {
          a.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            document
              .querySelector('.sdc-styleguide-viewer__iframe')
              .setAttribute('src', e.target.getAttribute('href'));
          });
        });
      });
    }
  };

  /**
   * Initializes the resizing functionality.
   */
  Drupal.behaviors.initializeResizer = {
    attach: (context, settings) => {
      once('resize-init', '.sdc-styleguide-page__viewer-wrapper', context).forEach(resizable => {
        // Control variable.
        let mouseX = 0;

        // Resize functionality as event handler. During mouse move calculates the
        // distance traveled and updates with accordingly.
        const resize = (e) => {
          const distance = mouseX - e.x;
          mouseX = e.x;
          resizable.style.width = (parseInt(getComputedStyle(resizable, '').width) - (2 * distance)) + "px";
        };

        // Finds the resizer and adds the event handlers to manage mouse events
        // The resizing class is needed in order to avoid the iframe triggering
        // events during resizing.
        const resizer = resizable.querySelector('.sdc-styleguide-page__viewer-resizer-wrapper');
        resizer.addEventListener('mousedown', e => {
          resizable.classList.add('sdc-styleguide-page__viewer-wrapper--resizing');
          mouseX = e.x;
          document.addEventListener('mousemove', resize, false, { passive: true });
        });
        document.addEventListener('mouseup', e => {
          resizable.classList.remove('sdc-styleguide-page__viewer-wrapper--resizing');
          document.removeEventListener('mousemove', resize, false);
        });
      });
    }
  };

})(Drupal);
