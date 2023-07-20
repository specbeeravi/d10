/**
 * @file
 * Customization of navigation.
 */

((Drupal, once, tabbable) => {
  /**
   * Checks if navWrapper contains "is-active" class.
   *
   * @param {Element} navWrapper
   *   Header navigation.
   *
   * @return {boolean}
   *   True if navWrapper contains "is-active" class, false if not.
   */
  function isNavOpen(navWrapper) {
    return navWrapper.classList.contains('is-active');
  }

  /**
   * Opens or closes the header navigation.
   *
   * @param {object} props
   *   Navigation props.
   * @param {boolean} state
   *   State which to transition the header navigation menu into.
   */
  function toggleNav(props, state) {
    const value = !!state;
    props.navButton.setAttribute('aria-expanded', value);

    if (value) {
      props.body.classList.add('is-overlay-active');
      props.body.classList.add('is-fixed');
      props.navWrapper.classList.add('is-active');
    } else {
      props.body.classList.remove('is-overlay-active');
      props.body.classList.remove('is-fixed');
      props.navWrapper.classList.remove('is-active');
    }
  }

  /**
   * Initialize the header navigation.
   *
   * @param {object} props
   *   Navigation props.
   */
  function init(props) {
    props.navButton.setAttribute('aria-controls', props.navWrapperId);
    props.navButton.setAttribute('aria-expanded', 'false');

    props.navButton.addEventListener('click', () => {
      toggleNav(props, !isNavOpen(props.navWrapper));
    });

    // Close any open sub-navigation first, then close the header navigation.
    document.addEventListener('keyup', (e) => {
      if (e.key === 'Escape' || e.key === 'Esc') {
        if (props.january.areAnySubNavsOpen()) {
          props.january.closeAllSubNav();
        } else {
          toggleNav(props, FALSE);
        }
      }
    });

    props.overlay.addEventListener('click', () => {
      toggleNav(props, FALSE);
    });

    props.overlay.addEventListener('touchstart', () => {
      toggleNav(props, FALSE);
    });

    // Focus trap. This is added to the header element because the navButton
    // element is not a child element of the navWrapper element, and the keydown
    // event would not fire if focus is on the navButton element.
    props.header.addEventListener('keydown', (e) => {
      if (e.key === 'Tab' && isNavOpen(props.navWrapper)) {
        const tabbableNavElements = tabbable.tabbable(props.navWrapper);
        tabbableNavElements.unshift(props.navButton);
        const firstTabbableEl = tabbableNavElements[0];
        const lastTabbableEl =
          tabbableNavElements[tabbableNavElements.length - 1];

        if (e.shiftKey) {
          if (
            document.activeElement === firstTabbableEl &&
            !props.january.isDesktopNav()
          ) {
            lastTabbableEl.focus();
            e.preventDefault();
          }
        } else if (
          document.activeElement === lastTabbableEl &&
          !props.january.isDesktopNav()
        ) {
          firstTabbableEl.focus();
          e.preventDefault();
        }
      }
    });

    // Remove overlays when browser is resized and desktop nav appears.
    window.addEventListener('resize', () => {
      if (props.january.isDesktopNav()) {
        toggleNav(props, FALSE);
        props.body.classList.remove('is-overlay-active');
        props.body.classList.remove('is-fixed');
      }

      // Ensure that all sub-navigation menus close when the browser is resized.
      Drupal.january.closeAllSubNav();
    });

    // If hyperlink links to an anchor in the current page, close the
    // mobile menu after the click.
    props.navWrapper.addEventListener('click', (e) => {
      if (
        e.target.matches(
          `[href *= "${window.location.pathname}#"], [href *= "${window.location.pathname}#"] * , [href^ = "#"], [href^ = "#"] * `,
        )
      ) {
        toggleNav(props, FALSE);
      }
    });
  }

  /**
   * Initialize the navigation.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach context and settings for navigation.
   */
  Drupal.behaviors.januaryNavigation = {
    attach(context) {
      const headerId = 'header';
      const header = once('navigation', `#${headerId}`, context).shift();
      const navWrapperId = 'header-nav';

      if (header) {
        const navWrapper = header.querySelector(`#${navWrapperId}`);
        const { january } = Drupal;
        const navButton = context.querySelector(
          '[data-drupal-selector="mobile-nav-button"]',
        );
        const body = context.querySelector('body');
        const overlay = context.querySelector(
          '[data-drupal-selector="header-nav-overlay"]',
        );

        init({
          january,
          header,
          navWrapperId,
          navWrapper,
          navButton,
          body,
          overlay,
        });
      }
    },
  };
})(Drupal, once, tabbable);
