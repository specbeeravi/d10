<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide\Plugin\SDCStyleguide;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sdc_styleguide\Attribute\SDCStyleguide;
use Drupal\sdc_styleguide\SDCStyleguidePluginBase;

/**
 * Plugin implementation of the sdc_styleguide.
 */
#[SDCStyleguide(
  id: "layout_switcher",
  type: "explorer",
  label: new TranslatableMarkup("Layout switcher"),
  description: new TranslatableMarkup("Allows to change the explorer layout."),
)]
final class LayoutSwitcherSDCStyleguidePlugin extends SDCStyleguidePluginBase {

  /**
   * {@inheritDoc}
   */
  public function build() {
    return [
      '#theme' => 'layout_switcher_sdc_styleguide_plugin',
    ];
  }

}
