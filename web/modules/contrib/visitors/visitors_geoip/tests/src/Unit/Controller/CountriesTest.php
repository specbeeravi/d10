<?php

namespace Drupal\Tests\visitors_geoip\Unit\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\visitors_geoip\Controller\Countries;
use Drupal\visitors_geoip\VisitorsGeoIpReportInterface;

/**
 * Tests the Countries controller.
 *
 * @group visitors_geoip
 */
class CountriesTest extends UnitTestCase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formBuilder;

  /**
   * The report service.
   *
   * @var \Drupal\visitors_geoip\VisitorsGeoIpReportInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $report;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The Countries controller.
   *
   * @var \Drupal\visitors_geoip\Controller\Countries
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->formBuilder = $this->createMock(FormBuilderInterface::class);
    $this->report = $this->createMock(VisitorsGeoIpReportInterface::class);
    $this->stringTranslation = $this->createMock(TranslationInterface::class);

    $this->controller = new Countries(
      $this->formBuilder,
      $this->report,
      $this->stringTranslation
    );

    if (!defined('RESPONSIVE_PRIORITY_LOW')) {
      define('RESPONSIVE_PRIORITY_LOW', 'priority-low');
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->controller = NULL;
    $this->report = NULL;
    $this->formBuilder = NULL;
    $this->stringTranslation = NULL;

    parent::tearDown();
  }

  /**
   * Tests the display() method.
   */
  public function testDisplay() {
    // Mock the form builder service.
    $form = $this->createMock('Drupal\Core\Form\FormInterface');
    $this->formBuilder->expects($this->once())
      ->method('getForm')
      ->with('Drupal\visitors\Form\DateFilter')
      ->willReturn($form);

    // Mock the report service.
    $this->report->expects($this->once())
      ->method('countries')
      ->willReturn([]);

    // Call the display() method.
    $output = $this->controller->display();

    // Assert the output.
    $this->assertArrayHasKey('visitors_date_filter_form', $output);
    $this->assertArrayHasKey('visitors_table', $output);
    $this->assertArrayHasKey('visitors_pager', $output);
  }

}
