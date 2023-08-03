<?php

namespace Drupal\Tests\visitors\Unit\Controller\Report;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\visitors\Controller\Report\HitDetails;
use Drupal\visitors\VisitorsReportInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the HitDetails controller.
 *
 * @group visitors
 */
class HitDetailsControllerTest extends UnitTestCase {

  /**
   * The mocked Date Formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $dateFormatter;

  /**
   * The mocked Visitors Report service.
   *
   * @var \Drupal\visitors\VisitorsReportInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $visitorsReport;

  /**
   * The HitDetails controller under test.
   *
   * @var \Drupal\visitors\Controller\Report\HitDetails
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->dateFormatter = $this->createMock(DateFormatterInterface::class);
    $this->visitorsReport = $this->createMock(VisitorsReportInterface::class);

    $this->controller = new HitDetails(
      $this->dateFormatter,
      $this->visitorsReport,
    );
  }

  /**
   * Tests the display() method.
   */
  public function testDisplay(): void {
    $hitId = 123;
    $rows = [
      // Add sample rows.
    ];

    $this->visitorsReport->expects($this->once())
      ->method('hitDetails')
      ->with($hitId)
      ->willReturn($rows);

    $expectedOutput = [
      'visitors_table' => [
        '#type' => 'table',
        '#rows' => $rows,
      ],
    ];

    $output = $this->controller->display($hitId);
    $this->assertEquals($expectedOutput, $output);
  }

}
