<?php

namespace Drupal\Tests\visitors\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the visitors/{report} page.
 *
 * @group visitors
 * @see \Drupal\visitors\Controller\Report\*
 */
class NodeViewTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'visitors'];

  /**
   * Tests the html head links.
   */
  public function testHasReportAccess() {
    $user = $this->drupalCreateUser([
      'access visitors',
      'access content',
    ]);
    $this->drupalLogin($user);

    $this->visitReports(200);
  }

  /**
   * Tests that we store and retrieve multi-byte UTF-8 characters correctly.
   */
  protected function visitReports(int $status) {
    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateNode();

    $this->drupalGet('/visitors');
    $this->assertSession()->statusCodeEquals($status);

    $this->drupalGet('/visitors/days_of_month');
    $this->assertSession()->statusCodeEquals($status);

    $this->drupalGet('/visitors/days_of_week');
    $this->assertSession()->statusCodeEquals($status);

    $this->drupalGet('/visitors/hosts');
    $this->assertSession()->statusCodeEquals($status);

    $this->drupalGet('/visitors/hours');
    $this->assertSession()->statusCodeEquals($status);

    $this->drupalGet('/visitors/monthly_history');
    $this->assertSession()->statusCodeEquals($status);

    $this->drupalGet('/visitors/hits');
    $this->assertSession()->statusCodeEquals($status);

    $this->drupalGet('/visitors/referers');
    $this->assertSession()->statusCodeEquals($status);

    $this->drupalGet('/visitors/pages');
    $this->assertSession()->statusCodeEquals($status);

    $this->drupalGet('/visitors/user_activity');
    $this->assertSession()->statusCodeEquals($status);

    $this->drupalGet('node/1/visitors');
    $this->assertSession()->statusCodeEquals($status);
  }

}
