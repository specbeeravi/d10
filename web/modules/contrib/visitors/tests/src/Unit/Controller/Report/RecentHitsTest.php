<?php

namespace Drupal\Tests\visitors\Unit\Controller\Report;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\visitors\Controller\Report\RecentHits;
use Drupal\visitors\VisitorsReportInterface;

/**
 * Unit tests for the RecentHits controller.
 *
 * @group visitors
 */
class RecentHitsTest extends UnitTestCase {

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
   * The RecentHits controller under test.
   *
   * @var \Drupal\visitors\Controller\Report\RecentHits
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

    $this->controller = new RecentHits(
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
   * Tests the display() method of the RecentHits controller.
   */
  public function testDisplay(): void {
    // Mock the necessary objects and their methods.
    $form = $this->createMock(FormInterface::class);
    $rows = [
      [
        '#1',
        '123',
        '2023-06-19 10:30:00',
        'https://example.com',
        'John Doe',
        '',
      ],
    ];

    // Mock the behavior of the form builder.
    $this->formBuilder->expects($this->once())
      ->method('getForm')
      ->with('Drupal\visitors\Form\DateFilter')
      ->willReturn($form);

    // Mock the behavior of the visitors report.
    $this->report->expects($this->once())
      ->method('recent')
      // ->with($this->any())
      ->willReturn($rows);

    // Call the display() method.
    $renderArray = $this->controller->display();

    // Assert the expected render array.
    $this->assertArrayHasKey('visitors_date_filter_form', $renderArray);
    $this->assertArrayHasKey('visitors_table', $renderArray);
    $this->assertArrayHasKey('visitors_pager', $renderArray);
  }

}
