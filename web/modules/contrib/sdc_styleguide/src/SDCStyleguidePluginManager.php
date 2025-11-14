<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\sdc_styleguide\Attribute\SDCStyleguide;

/**
 * SDCStyleguide plugin manager.
 */
final class SDCStyleguidePluginManager extends DefaultPluginManager {

  /**
   * Constructs a new SDCStyleguidePluginManager object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/SDCStyleguide', $namespaces, $module_handler, SDCStyleguidePluginInterface::class, SDCStyleguide::class);
    $this->alterInfo('sdc_styleguide_info');
    $this->setCacheBackend($cache_backend, 'sdc_styleguide_plugins');
  }

}
