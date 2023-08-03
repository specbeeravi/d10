<?php

namespace Drupal\visitors\Controller\Report;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\visitors\VisitorsReportInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Day of Week Report controller.
 */
class DaysOfWeek extends ControllerBase {
  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $date;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The report service.
   *
   * @var \Drupal\visitors\VisitorsReportInterface
   */
  protected $report;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): DaysOfWeek {
    return new static(
      $container->get('date.formatter'),
      $container->get('form_builder'),
      $container->get('visitors.report'),
      $container->get('string_translation')
    );
  }

  /**
   * Constructs a DaysOfWeek object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\visitors\VisitorsReportInterface $report_service
   *   The report service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    DateFormatterInterface $date_formatter,
    FormBuilderInterface $form_builder,
    VisitorsReportInterface $report_service,
    TranslationInterface $string_translation) {

    $this->date              = $date_formatter;
    $this->formBuilder       = $form_builder;
    $this->report            = $report_service;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Returns a days of week page page.
   *
   * @return array
   *   A render array representing the days of week page content.
   */
  public function display(): array {
    $form    = $this->formBuilder->getForm('Drupal\visitors\Form\DateFilter');
    $header  = $this->getHeader();
    $results = $this->report->daysOfWeek();
    $days    = $this->report->getTranslatedDays();
    $x       = [];
    $y       = [];

    foreach ($days as $translated) {
      $key = $translated->render();
      $x[] = '"' . $key . '"';
      $y[$key] = 0;
    }

    foreach ($results as $data) {
      $y[$data[1]] = $data[2];
    }

    return [
      'visitors_date_filter_form' => $form,
      'visitors_jqplot' => [
        '#theme'  => 'visitors_jqplot',
        '#x'      => implode(', ', $x),
        '#y'      => implode(', ', $y),
        '#width'  => $this->report->width(),
        '#height' => $this->report->height(),
      ],
      'visitors_table' => [
        '#type'  => 'table',
        '#header' => $header,
        '#rows'   => $this->report->daysOfWeek(),
      ],
    ];
  }

  /**
   * Returns a table header configuration.
   *
   * @return array
   *   A render array representing the table header info.
   */
  protected function getHeader(): array {
    return [
      '#' => [
        'data' => $this->t('#'),
      ],
      'day' => [
        'data' => $this->t('Day'),
      ],
      'count' => [
        'data' => $this->t('Pages'),
      ],
    ];
  }

}
