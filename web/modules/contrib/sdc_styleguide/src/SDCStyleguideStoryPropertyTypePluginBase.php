<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for sdc_styleguide plugins.
 */
abstract class SDCStyleguideStoryPropertyTypePluginBase extends PluginBase implements SDCStyleguideStoryPropertyTypePluginInterface {

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
  public function getHandledType(): string {
    return $this->pluginDefinition['handleType'];
  }
}
