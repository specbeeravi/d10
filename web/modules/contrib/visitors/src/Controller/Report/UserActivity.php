<?php

namespace Drupal\visitors\Controller\Report;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\visitors\VisitorsReportInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * User Activity Report controller.
 */
class UserActivity extends ControllerBase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): UserActivity {
    return new static(
      $container->get('form_builder'),
      $container->get('module_handler'),
      $container->get('visitors.report'),
      $container->get('string_translation')
    );
  }

  /**
   * Constructs a UserActivity object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\visitors\VisitorsReportInterface $report_service
   *   The report service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
      FormBuilderInterface $form_builder,
      ModuleHandlerInterface $module_handler,
      VisitorsReportInterface $report_service,
      TranslationInterface $string_translation) {

    $this->formBuilder = $form_builder;
    $this->moduleHandler = $module_handler;
    $this->report = $report_service;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Returns a user activity page.
   *
   * @return array
   *   A render array representing the user activity page content.
   */
  public function display(): array {
    if (!$this->moduleHandler->moduleExists('node')) {
      throw new NotFoundHttpException();
    }

    $form = $this->formBuilder->getForm('Drupal\visitors\Form\DateFilter');
    $header = $this->getHeader();

    return [
      'visitors_date_filter_form' => $form,
      'visitors_table' => [
        '#type'  => 'table',
        '#header' => $header,
        '#rows'   => $this->report->activity($header),
      ],
      'visitors_pager' => ['#type' => 'pager'],
    ];
  }

  /**
   * Returns a table header configuration.
   *
   * @return array
   *   A render array representing the table header info.
   */
  protected function getHeader(): array {
    $headers = [
      '#' => [
        'data'      => $this->t('#'),
      ],
      'u.name' => [
        'data'      => $this->t('User'),
        'field'     => 'u.name',
        'specifier' => 'u.name',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
      ],
      'hits' => [
        'data'      => $this->t('Hits'),
        'field'     => 'hits',
        'specifier' => 'hits',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
        'sort'      => 'desc',
      ],
      'nodes' => [
        'data'      => $this->t('Nodes'),
        'field'     => 'nodes',
        'specifier' => 'nodes',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    if ($this->moduleHandler()->moduleExists('comment')) {
      $headers['comments'] = [
        'data'      => $this->t('Comments'),
        'field'     => 'comments',
        'specifier' => 'comments',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
      ];
    }

    return $headers;
  }

}
