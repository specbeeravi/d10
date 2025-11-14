<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for sdc_styleguide plugins.
 */
abstract class SDCStyleguidePluginBase extends PluginBase implements SDCStyleguidePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function type(): string {
    return $this->pluginDefinition['type'];
  }

}
