<?php

namespace Drupal\Tests\visitors\Unit\Controller\Report;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\visitors\Controller\Report\Referer;
use Drupal\visitors\VisitorsReportInterface;
use Drupal\Core\Form\FormInterface;

/**
 * Tests the Referer controller.
 *
 * @group visitors
 */
class RefererTest extends UnitTestCase {

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
   * The Referer controller instance.
   *
   * @var \Drupal\visitors\Controller\Report\Referer
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

    $this->controller = new Referer(
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
   * Tests the display method.
   */
  public function testDisplay(): void {
    $date_form = $this->createMock(FormInterface::class);
    $referer_form = $this->createMock(FormInterface::class);

    // Mock the behavior of the form builder.
    $this->formBuilder->expects($this->once())
      ->method('getForm')
      ->willReturnMap([
        ['Drupal\visitors\Form\Referer', $referer_form],
        ['Drupal\visitors\Form\DateFilter', $date_form],
      ]);

    $this->report->expects($this->once())
      ->method('referer')
      ->willReturn([]);

    // Call the display() method.
    $renderArray = $this->controller->display();

    // Assert the expected render array.
    $this->assertArrayHasKey('visitors_date_filter_form', $renderArray);
    $this->assertArrayHasKey('visitors_table', $renderArray);
    $this->assertArrayHasKey('visitors_pager', $renderArray);

  }

}
