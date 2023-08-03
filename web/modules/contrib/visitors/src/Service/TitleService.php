<?php

namespace Drupal\visitors\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\visitors\VisitorsTitleInterface;

/**
 * Service for page title hierarchy.
 */
class TitleService implements VisitorsTitleInterface {

  /**
   * Menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The site config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $siteConfig;

  /**
   * The visitors config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $visitorsConfig;

  /**
   * The main menu config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $mainMenuConfig;

  /**
   * The current path.
   *
   * @var string
   */
  private $path;

  /**
   * Constructs a new TitleService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    MenuLinkTreeInterface $menu_link_tree,
    CurrentPathStack $current_path) {

    $this->siteConfig = $config_factory->get('system.site');
    $this->visitorsConfig = $config_factory->get('visitors.config');
    $this->mainMenuConfig = $config_factory->get('system.menu.main');
    $this->menuLinkTree = $menu_link_tree;
    $this->path = $current_path->getPath();
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    $titles = [];
    // No breadcrumb for the front page.
    if ($this->path === $this->siteConfig->get('page.front')) {
      return $titles;
    }

    // Load up the menu tree.
    // @todo Check this is a sane approach.
    $menu_name = $this->mainMenuConfig->get('id');

    // Build the typical default set of menu tree parameters.
    $parameters = $this->menuLinkTree->getCurrentRouteMenuTreeParameters($menu_name);

    // Load the tree based on this set of parameters.
    $tree = $this->menuLinkTree->load($menu_name, $parameters);

    // Transform the tree using the manipulators.
    $manipulators = [
      // Only show links that are accessible for the current user.
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      // Use the default sorting of menu links.
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      // Fatten the menu.
      ['callable' => 'menu.default_tree_manipulators:flatten'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);

    if (!empty($tree)) {
      foreach ($tree as $menu_item) {
        // If the item is in the active trail and we don't have a front page
        // link when we have set to exclude home from the breadcrumbs then add
        // the title.
        $is_frontpage = $menu_item->link->getRouteName() == '<front>';
        $exclude_home = $this->visitorsConfig
          ->get('page_title_hierarchy_exclude_home');
        $is_excluded = $exclude_home && $is_frontpage;
        $include = $menu_item->inActiveTrail && !$is_excluded;
        if ($include) {
          $titles[] = $menu_item->link->getTitle();
        }
      }
    }

    return $titles;
  }

}
