<?php

namespace Drupal\Tests\visitors\Unit\Controller\Report;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\visitors\Controller\Report\DaysOfMonth;
use Drupal\visitors\VisitorsReportInterface;

/**
 * Unit tests for the DaysOfMonth controller.
 *
 * @group visitors
 */
class DaysOfMonthTest extends UnitTestCase {

  /**
   * The mocked date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $dateFormatter;

  /**
   * The mocked form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formBuilder;

  /**
   * The mocked visitors report service.
   *
   * @var \Drupal\visitors\VisitorsReportInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $report;

  /**
   * The DaysOfMonth controller under test.
   *
   * @var \Drupal\visitors\Controller\Report\DaysOfMonth
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->dateFormatter = $this->createMock(DateFormatterInterface::class);
    $this->formBuilder = $this->createMock(FormBuilderInterface::class);
    $this->report = $this->createMock(VisitorsReportInterface::class);
    $this->string_translation = $this->createMock(TranslationInterface::class);

    $this->controller = new DaysOfMonth(
      $this->dateFormatter,
      $this->formBuilder,
      $this->report,
      $this->string_translation
    );

    if (!defined('RESPONSIVE_PRIORITY_LOW')) {
      define('RESPONSIVE_PRIORITY_LOW', 'priority-low');
    }
  }

  /**
   * Tests the display() method of the DaysOfMonth controller.
   */
  public function testDisplay(): void {
    // Mock the necessary objects and their methods.
    $form = $this->createMock(FormInterface::class);

    $this->report->expects($this->once())
      ->method('width')
      ->willReturn(600);

    $this->report->expects($this->once())
      ->method('height')
      ->willReturn(800);

    $this->formBuilder->expects($this->once())
      ->method('getForm')
      ->with($this->equalTo('Drupal\visitors\Form\DateFilter'))
      ->willReturn($form);

    $results = [
      [1, 1, 10],
      [2, 2, 15],
      [3, 3, 8],
    ];

    $this->report->expects($this->exactly(2))
      ->method('daysOfMonth')
      ->willReturn($results);

    // Invoke the display method and assert the expected render array.
    $renderArray = $this->controller->display();

    $this->assertArrayHasKey('visitors_date_filter_form', $renderArray);
    $this->assertArrayHasKey('visitors_jqplot', $renderArray);
    $this->assertArrayHasKey('visitors_table', $renderArray);

  }

}
