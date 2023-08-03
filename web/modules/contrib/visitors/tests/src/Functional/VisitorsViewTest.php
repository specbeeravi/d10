<?php

namespace Drupal\Tests\visitors\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the visitors/{report} page.
 *
 * @group visitors
 * @see \Drupal\visitors\Controller\Report\*
 */
class VisitorsViewTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['visitors'];

  /**
   * Tests the html head links.
   */
  public function testHasReportAccess() {
    $user = $this->drupalCreateUser([
      'access visitors',
    ]);
    $this->drupalLogin($user);

    $this->visitReports(200);
  }

  /**
   * Tests the Link header.
   */
  public function testNoReportAccess() {
    $user = $this->drupalCreateUser([]);
    $this->drupalLogin($user);

    $this->visitReports(403);
  }

  /**
   * Tests Visitors Settings form access.
   */
  public function testVisitorsSettingsForm() {
    $user = $this->drupalCreateUser([]);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/system/visitors');
    $this->assertSession()->statusCodeEquals(403);

    $user = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/system/visitors');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that we store and retrieve multi-byte UTF-8 characters correctly.
   */
  protected function visitReports(int $status) {
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

    $activity_status = $status == 200 ? 404 : $status;
    $this->drupalGet('/visitors/user_activity');
    $this->assertSession()->statusCodeEquals($activity_status);

    // $this->drupalGet('/node/1/visitors');
    // $this->assertSession()->statusCodeEquals($activity_status);
  }

}
