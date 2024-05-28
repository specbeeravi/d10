<?php

namespace Drupal\visitors\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * A negotiator for custom visitors' theme.
 */
class VisitorThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ThemeNegotiator.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($route_match->getRouteObject()) {
      $route_name = $route_match->getRouteName();
      if (strpos($route_name, 'visitors.') === 0) {
        return TRUE;
      }
      $path = $route_match->getRouteObject()->getPath();
      if (strpos($path, '/visitors') === 0) {
        return TRUE;
      }

    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // Get the visitors config.
    $config = $this->configFactory->get('visitors.config');
    $theme = $config->get('theme') ?: 'admin';

    return $this->configFactory->get('system.theme')->get($theme) ?: $theme;
  }

}
