<?php

namespace Drupal\Tests\visitors\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests if tacker.js is loaded when content is not printed.
 *
 * @group visitors
 */
class StatisticsAttachedTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'visitors'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page']);

    // Install "visitors_test_attached" and set it as the default theme.
    $theme = 'visitors_test_attached';
    \Drupal::service('theme_installer')->install([$theme]);
    $this->config('system.theme')
      ->set('default', $theme)
      ->save();
    // Installing a theme will cause the kernel terminate event to rebuild the
    // router. Simulate that here.
    \Drupal::service('router.builder')->rebuildIfNeeded();
  }

  /**
   * Tests if statistics.js is loaded when content is not printed.
   */
  public function testAttached() {

    $node = Node::create([
      'type' => 'page',
      'title' => 'Page node',
      'body' => 'body text',
    ]);
    $node->save();
    $this->drupalGet('node/' . $node->id());

    $this->assertSession()->responseContains('modules/contrib/visitors/js/tracker.js');
  }

}
