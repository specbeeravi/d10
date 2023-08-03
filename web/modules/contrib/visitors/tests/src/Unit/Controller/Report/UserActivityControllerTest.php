<?php

namespace Drupal\Tests\visitors\Unit\Controller\Report;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\visitors\Controller\Report\UserActivity;
use Drupal\visitors\VisitorsReportInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Unit tests for the UserActivity controller.
 *
 * @group visitors
 */
class UserActivityControllerTest extends UnitTestCase {

  /**
   * The mocked Form Builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formBuilder;

  /**
   * The mocked Module Handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

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
   * The UserActivity controller under test.
   *
   * @var \Drupal\visitors\Controller\Report\UserActivity
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->formBuilder = $this->createMock(FormBuilderInterface::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->visitorsReport = $this->createMock(VisitorsReportInterface::class);
    $this->stringTranslation = $this->createMock(TranslationInterface::class);

    $this->controller = new UserActivity(
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
   * Tests the display() method when the node module is enabled.
   */
  public function testDisplayWithNodeModuleEnabled(): void {
    $form = $this->createMock(FormInterface::class);
    $rows = [
      // Add sample rows.
    ];

    $this->formBuilder->expects($this->once())
      ->method('getForm')
      ->with('Drupal\visitors\Form\DateFilter')
      ->willReturn($form);

    $this->moduleHandler->expects($this->exactly(2))
      ->method('moduleExists')
      ->willReturnMap([
        ['node', TRUE],
        ['comment', FALSE],
      ]);

    $this->visitorsReport->expects($this->once())
      ->method('activity')
      ->willReturn($rows);

    $renderArray = $this->controller->display();

    // Assert the expected render array.
    $this->assertArrayHasKey('visitors_date_filter_form', $renderArray);
    $this->assertArrayHasKey('visitors_table', $renderArray);
    $this->assertArrayHasKey('visitors_pager', $renderArray);
  }

  /**
   * Tests the display() method when the node module is not enabled.
   */
  public function testDisplayWithNodeModuleDisabled(): void {

    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('node')
      ->willReturn(FALSE);

    $this->expectException(NotFoundHttpException::class);

    $this->controller->display();
  }

}
