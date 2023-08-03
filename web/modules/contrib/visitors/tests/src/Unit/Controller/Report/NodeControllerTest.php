<?php

namespace Drupal\Tests\visitors\Unit\Controller\Report;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\visitors\Controller\Report\Node;
use Drupal\visitors\VisitorsReportInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Unit tests for the Node controller.
 *
 * @group visitors
 */
class NodeControllerTest extends TestCase {

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
   * The mocked Database Connection service.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * The mocked Module Handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked Request Stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $requestStack;

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
   * The Node controller under test.
   *
   * @var \Drupal\visitors\Controller\Report\Node
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->dateFormatter = $this->createMock(DateFormatterInterface::class);
    $this->formBuilder = $this->createMock(FormBuilderInterface::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->visitorsReport = $this->createMock(VisitorsReportInterface::class);
    $this->stringTranslation = $this->createMock(TranslationInterface::class);

    $this->controller = new Node(
      $this->dateFormatter,
      $this->formBuilder,
      $this->moduleHandler,
      $this->visitorsReport,
      $this->stringTranslation
    );

    if (!defined('RESPONSIVE_PRIORITY_LOW')) {
      define('RESPONSIVE_PRIORITY_LOW', 'priority-low');
    }
  }

  /**
   * Tests the display() method with a valid node.
   */
  public function testDisplayWithValidNode(): void {
    $node = $this->createMock('Drupal\Core\Entity\EntityInterface');
    $node->expects($this->once())
      ->method('id')
      ->willReturn(123);

    $this->moduleHandler
      ->expects($this->once())
      ->method('moduleExists')
      ->with('node')
      ->willReturn(TRUE);

    $rows = [
      [
        '#markup' => 'Row 1',
      ],
      [
        '#markup' => 'Row 2',
      ],
    ];

    $this->visitorsReport->expects($this->once())
      ->method('node')
      ->willReturn($rows);

    // Invoke the display method and assert the expected render array.
    $renderArray = $this->controller->display($node);

    $this->assertArrayHasKey('visitors_date_filter_form', $renderArray);
    $this->assertArrayHasKey('visitors_table', $renderArray);
    $this->assertArrayHasKey('visitors_pager', $renderArray);
  }

  /**
   * Tests the display() method with an invalid node.
   */
  public function testDisplayWithInvalidNode(): void {
    $node = $this->createMock('Drupal\Core\Entity\EntityInterface');
    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('node')
      ->willReturn(FALSE);

    $this->expectException(NotFoundHttpException::class);
    $this->controller->display($node);
  }

}
