<?php

namespace Drupal\visitors\Controller\Report;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\visitors\VisitorsReportInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Host Report controller.
 */
class Hosts extends ControllerBase {
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): Hosts {
    return new static(
      $container->get('date.formatter'),
      $container->get('form_builder'),
      $container->get('visitors.report'),
      $container->get('string_translation')
    );
  }

  /**
   * Constructs a Hosts object.
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
   * Returns a hosts page.
   *
   * @return array
   *   A render array representing the hosts page content.
   */
  public function display(): array {
    $form = $this->formBuilder->getForm('Drupal\visitors\Form\DateFilter');
    $header = $this->getHeader();

    return [
      'visitors_date_filter_form' => $form,
      'visitors_table' => [
        '#type'  => 'table',
        '#header' => $header,
        '#rows'   => $this->report->hosts($header),
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
    return [
      '#' => [
        'data'      => $this->t('#'),
      ],
      'visitors_ip' => [
        'data'      => $this->t('Host'),
        'field'     => 'visitors_ip',
        'specifier' => 'visitors_ip',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
      ],
      'count' => [
        'data'      => $this->t('Count'),
        'field'     => 'count',
        'specifier' => 'count',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
        'sort'      => 'desc',
      ],
      '' => [
        'data'      => $this->t('Operations'),
      ],
    ];
  }

}
