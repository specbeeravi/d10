<?php

namespace Drupal\Tests\visitors\Unit\Controller\Report;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\visitors\Controller\Report\DaysOfWeek;
use Drupal\visitors\VisitorsReportInterface;

/**
 * Unit tests for the DaysOfWeek controller.
 *
 * @group visitors
 */
class DaysOfWeekTest extends UnitTestCase {

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
   * The DaysOfWeek controller under test.
   *
   * @var \Drupal\visitors\Controller\Report\DaysOfWeek
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

    $this->controller = new DaysOfWeek(
      $this->dateFormatter,
      $this->formBuilder,
      $this->report,
      $this->string_translation
    );
  }

  /**
   * Tests the display() method of the DaysOfWeek controller.
   */
  public function testDisplay(): void {
    // Mock the necessary objects and their methods.
    $form = $this->createMock(FormInterface::class);

    $results = [
      [1, 'Monday', 10],
      [2, 'Tuesday', 15],
      [3, 'Wednesday', 8],
    ];
    $days = [
      $this->createMock(TranslatableMarkup::class),
      $this->createMock(TranslatableMarkup::class),
      $this->createMock(TranslatableMarkup::class),
    ];
    $this->report->expects($this->exactly(2))
      ->method('daysOfWeek')
      ->willReturn($results);

    $this->report->expects($this->once())
      ->method('getTranslatedDays')
      ->willReturn($days);
    $this->report->expects($this->once())
      ->method('width')
      ->willReturn(800);
    $this->report->expects($this->once())
      ->method('height')
      ->willReturn(400);
    $this->formBuilder->expects($this->once())
      ->method('getForm')
      ->with('Drupal\visitors\Form\DateFilter')
      ->willReturn($form);

    // Execute the display() method.
    $renderArray = $this->controller->display();

    // Assertions for the returned render array.
    $this->assertArrayHasKey('visitors_date_filter_form', $renderArray);
    $this->assertArrayHasKey('visitors_jqplot', $renderArray);
    $this->assertArrayHasKey('visitors_table', $renderArray);

  }

}
