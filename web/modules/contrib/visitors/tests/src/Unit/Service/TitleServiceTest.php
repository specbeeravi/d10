<?php

namespace Drupal\Tests\visitors\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Tests\UnitTestCase;
use Drupal\visitors\Service\TitleService;

/**
 * Tests the TitleService class.
 *
 * @group visitors
 */
class TitleServiceTest extends UnitTestCase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $menuLinkTree;

  /**
   * The site config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $siteConfig;

  /**
   * The visitors config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $visitorsConfig;

  /**
   * The main menu config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $mainMenuConfig;

  /**
   * The current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentPath;

  /**
   * The TitleService instance.
   *
   * @var \Drupal\visitors\Service\TitleService
   */
  protected $titleService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->menuLinkTree = $this->createMock(MenuLinkTreeInterface::class);
    $this->siteConfig = $this->createMock('\Drupal\Core\Config\ImmutableConfig');
    $this->visitorsConfig = $this->createMock('\Drupal\Core\Config\ImmutableConfig');
    $this->mainMenuConfig = $this->createMock('\Drupal\Core\Config\ImmutableConfig');
    $this->currentPath = $this->createMock(CurrentPathStack::class);

    $this->configFactory->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['system.site', $this->siteConfig],
        ['visitors.config', $this->visitorsConfig],
        ['system.menu.main', $this->mainMenuConfig],
      ]);

    $this->titleService = new TitleService($this->configFactory, $this->menuLinkTree, $this->currentPath);
  }

  /**
   * Tests the title() method.
   */
  public function testTitle() {
    // Set up mock objects and expectations.
    $menuTreParameters = $this->createMock('Drupal\Core\Menu\MenuTreeParameters');

    $this->siteConfig->expects($this->once())
      ->method('get')
      ->with('page.front')
      ->willReturn('/front-page');

    $this->visitorsConfig->expects($this->any())
      ->method('get')
      ->with('page_title_hierarchy_exclude_home')
      ->willReturn(TRUE);

    $this->currentPath->expects($this->any())
      ->method('getPath')
      ->willReturn('/current-page');

    $m1Link = $this->createMock('\Drupal\Core\Menu\MenuLinkInterface');
    $m1Link->expects($this->any())
      ->method('getRouteName')
      ->willReturn('entity.node.canonical');
    $m1Link->expects($this->any())
      ->method('getTitle')
      ->willReturn('About');
    $menuLink1 = (object) [
      'inActiveTrail' => FALSE,
      'link' => $m1Link,
    ];

    $m2Link = $this->createMock('\Drupal\Core\Menu\MenuLinkInterface');
    $m2Link->expects($this->any())
      ->method('getRouteName')
      ->willReturn('entity.node.canonical');
    $m2Link->expects($this->any())
      ->method('getTitle')
      ->willReturn('About');
    $menuLink2 = (object) [
      'inActiveTrail' => TRUE,
      'link' => $m2Link,
    ];

    $m3Link = $this->createMock('\Drupal\Core\Menu\MenuLinkInterface');
    $m3Link->expects($this->any())
      ->method('getRouteName')
      ->willReturn('/front-page');
    $m3Link->expects($this->any())
      ->method('getTitle')
      ->willReturn('Contact');

    $menuLink3 = (object) [
      'inActiveTrail' => FALSE,
      'link' => $m3Link,
    ];

    $menuLinks = [$menuLink1, $menuLink2, $menuLink3];

    $this->mainMenuConfig->expects($this->once())
      ->method('get')
      ->with('id')
      ->willReturn('main');

    $this->menuLinkTree->expects($this->once())
      ->method('getCurrentRouteMenuTreeParameters')
      ->with('main')
      ->willReturn($menuTreParameters);

    $this->menuLinkTree->expects($this->once())
      ->method('load')
      ->with('main', $menuTreParameters)
      ->willReturn($menuLinks);

    $this->menuLinkTree->expects($this->once())
      ->method('transform')
      ->with($menuLinks, $this->anything())
      ->willReturn($menuLinks);

    // Execute the title() method.
    $result = $this->titleService->title();

    // Verify the result.
    $expectedResult = ['About'];
    $this->assertEquals($expectedResult, $result);
  }

}
