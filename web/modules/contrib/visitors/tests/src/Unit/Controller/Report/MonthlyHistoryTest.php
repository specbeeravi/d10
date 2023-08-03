<?php

namespace Drupal\Tests\visitors\Unit\Controller\Report;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\visitors\Controller\Report\MonthlyHistory;
use Drupal\visitors\VisitorsReportInterface;
use Drupal\Core\Form\FormInterface;

/**
 * Unit tests for the MonthlyHistory controller.
 *
 * @group visitors
 */
class MonthlyHistoryTest extends UnitTestCase {

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
   * The mocked string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The MonthlyHistory controller under test.
   *
   * @var \Drupal\visitors\Controller\Report\MonthlyHistory
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
    $this->stringTranslation = $this->createMock(TranslationInterface::class);

    $this->controller = new MonthlyHistory(
      $this->dateFormatter,
      $this->formBuilder,
      $this->report,
      $this->stringTranslation
    );
    if (!defined('RESPONSIVE_PRIORITY_LOW')) {
      define('RESPONSIVE_PRIORITY_LOW', 'priority-low');
    }
  }

  /**
   * Tests the display() method of the MonthlyHistory controller.
   */
  public function testDisplay(): void {
    // Mock the necessary objects and their methods.
    $form = $this->createMock(FormInterface::class);
    $results = [
      ['example', 1, 2],
      ['example', 2, 3],
    ];

    $this->formBuilder->expects($this->once())
      ->method('getForm')
      ->with('Drupal\visitors\Form\DateFilter')
      ->willReturn($form);
    $this->report->expects($this->exactly(2))
      ->method('monthly')
      ->willReturnOnConsecutiveCalls([], $results);
    $this->report->expects($this->once())
      ->method('width')
      ->willReturn(800);
    $this->report->expects($this->once())
      ->method('height')
      ->willReturn(600);

    // Execute the display() method.
    $renderArray = $this->controller->display();

    // Assertions for the returned render array.
    $this->assertArrayHasKey('visitors_date_filter_form', $renderArray);
    $this->assertArrayHasKey('visitors_jqplot', $renderArray);
    $this->assertArrayHasKey('visitors_table', $renderArray);
  }

}
