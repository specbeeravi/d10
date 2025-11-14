<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide;

/**
 * Interface for SDC Styleguide plugins.
 */
interface SDCStyleguidePluginInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * Returns the plugin type.
   *
   * @return string
   *   A value either `explorer` or `tool`.
   */
  public function type(): string;

  /**
   * Returns the render array representing the plugin.
   *
   * @return mixed[]
   *   Plugin render array.
   */
  public function build();

}
