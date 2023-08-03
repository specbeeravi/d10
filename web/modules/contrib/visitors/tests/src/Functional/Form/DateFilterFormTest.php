<?php

namespace Drupal\Tests\visitors\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the DateFilter form.
 *
 * @group visitors
 */
class DateFilterFormTest extends BrowserTestBase {

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
   * Tests the DateFilter form.
   */
  public function testDateFilterForm() {
    // Create a user with the necessary permissions.
    $adminUser = $this->drupalCreateUser([
      'access visitors',
      'access content',
    ]);

    // Log in as the created user.
    $this->drupalLogin($adminUser);

    // Navigate to the DateFilter form.
    $this->drupalGet('/visitors/pages');

    // Assert that the form is displayed.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('from[month]');
    $this->assertSession()->fieldExists('from[day]');
    $this->assertSession()->fieldExists('from[year]');
    $this->assertSession()->fieldExists('to[month]');
    $this->assertSession()->fieldExists('to[day]');
    $this->assertSession()->fieldExists('to[year]');

    // Fill out the form with valid values.
    $edit = [
      'from[month]' => '1',
      'from[day]' => '1',
      'from[year]' => '2023',
      'to[month]' => '12',
      'to[day]' => '31',
      'to[year]' => '2023',
    ];
    $this->submitForm($edit, 'Save');

    // Assert that the form submission is successful.
    $this->assertSession()->optionExists('from[month]', '1');
    $this->assertSession()->optionExists('from[day]', '1');
    $this->assertSession()->optionExists('from[year]', '2023');
    $this->assertSession()->optionExists('to[month]', '12');
    $this->assertSession()->optionExists('to[day]', '31');
    $this->assertSession()->optionExists('to[year]', '2023');

    // Additional assertions or verifications after form submission, if needed.
  }

}
