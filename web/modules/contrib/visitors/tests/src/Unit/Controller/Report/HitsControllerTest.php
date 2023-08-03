<?php

namespace Drupal\Tests\visitors\Unit\Controller\Report;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\visitors\Controller\Report\Hits;
use Drupal\visitors\VisitorsReportInterface;

/**
 * Unit tests for the Hits controller.
 *
 * @group visitors
 */
class HitsControllerTest extends UnitTestCase {

  /**
   * The mocked Date Formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $dateFormatter;

  /**
   * The mocked Form Builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formBuilder;

  /**
   * The mocked Visitors Report service.
   *
   * @var \Drupal\visitors\VisitorsReportInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $visitorsReport;

  /**
   * The mocked String Translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The Hits controller under test.
   *
   * @var \Drupal\visitors\Controller\Report\Hits
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->dateFormatter = $this->createMock(DateFormatterInterface::class);
    $this->formBuilder = $this->createMock(FormBuilderInterface::class);
    $this->visitorsReport = $this->createMock(VisitorsReportInterface::class);
    $this->stringTranslation = $this->createMock(TranslationInterface::class);

    $this->controller = new Hits(
      $this->dateFormatter,
      $this->formBuilder,
      $this->visitorsReport,
      $this->stringTranslation
    );

    if (!defined('RESPONSIVE_PRIORITY_LOW')) {
      define('RESPONSIVE_PRIORITY_LOW', 'priority-low');
    }

  }

  /**
   * Tests the display() method.
   */
  public function testDisplay(): void {
    $host = 'example.com';
    $form = $this->createMock(FormInterface::class);

    $rows = [
      // Add sample rows.
    ];

    $this->formBuilder->expects($this->once())
      ->method('getForm')
      ->with('Drupal\visitors\Form\DateFilter')
      ->willReturn($form);

    $this->visitorsReport->expects($this->once())
      ->method('recentHost')
      ->willReturn($rows);

    $renderArray = $this->controller->display($host);

    $this->assertArrayHasKey('visitors_date_filter_form', $renderArray);
    $this->assertArrayHasKey('visitors_table', $renderArray);
    $this->assertArrayHasKey('visitors_pager', $renderArray);

  }

}
