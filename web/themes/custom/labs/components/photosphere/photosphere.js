/**
 * @file
 * photosphere.js
 *
 * Load 360 images with photosphere effect.
 */

((Drupal, once) => {
  Drupal.behaviors.photosphere = {
    attach(context) {
      const elements = once('image360', '.photosphere', context);
      elements.forEach((element) => {
        const src = element.dataset.src;
        if (src) {
          new PhotoSphereViewer.Viewer({
            container: element,
            panorama: src,
            size: {
              width: '100%',
              height: '685px'
            },
            navbar: false,
          });
        }
      });
    }
  };
})(Drupal, once);
