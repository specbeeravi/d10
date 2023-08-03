<?php

namespace Drupal\visitors\Controller\Report;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\visitors\VisitorsReportInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hour Report controller.
 */
class Hours extends ControllerBase {
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
  public static function create(ContainerInterface $container): Hours {
    return new static(
      $container->get('date.formatter'),
      $container->get('form_builder'),
      $container->get('visitors.report'),
      $container->get('string_translation')
    );
  }

  /**
   * Constructs a Hours object.
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
   * Returns a hours page.
   *
   * @return array
   *   A render array representing the hours page content.
   */
  public function display(): array {

    $form    = $this->formBuilder->getForm('Drupal\visitors\Form\DateFilter');
    $header  = $this->getHeader();
    $results = $this->report->hours();

    $y = [];
    for ($i = 0; $i < 24; $i++) {
      $y[$i] = 0;
    }

    foreach ($results as $data) {
      $y[(int) $data[1]] = $data[2];
    }

    return [
      'visitors_date_filter_form' => $form,
      'visitors_jqplot' => [
        '#theme'  => 'visitors_jqplot',
        '#x'      => implode(', ', range(0, 23)),
        '#y'      => implode(', ', $y),
        '#width'  => $this->report->width(),
        '#height' => $this->report->height(),
      ],
      'visitors_table' => [
        '#type'  => 'table',
        '#header' => $header,
        '#rows'   => $this->report->hours($header),
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
        'data'      => $this->t('#'),
      ],
      'hour' => [
        'data'      => $this->t('Hour'),
        'field'     => 'hour',
        'specifier' => 'hour',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
        'sort'      => 'asc',
      ],
      'count' => [
        'data'      => $this->t('Pages'),
        'field'     => 'count',
        'specifier' => 'count',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
  }

}
