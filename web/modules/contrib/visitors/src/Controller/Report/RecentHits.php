<?php

namespace Drupal\visitors\Controller\Report;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\visitors\VisitorsReportInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Recent Hits Report controller.
 */
class RecentHits extends ControllerBase {
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
  public static function create(ContainerInterface $container): RecentHits {
    return new static(
      $container->get('date.formatter'),
      $container->get('form_builder'),
      $container->get('visitors.report'),
      $container->get('string_translation')
    );
  }

  /**
   * Constructs a RecentHits object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\visitors\VisitorsReportInterface $report
   *   The report service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    DateFormatterInterface $date_formatter,
    FormBuilderInterface $form_builder,
    VisitorsReportInterface $report,
    TranslationInterface $string_translation) {

    $this->date              = $date_formatter;
    $this->formBuilder       = $form_builder;
    $this->report            = $report;
    $this->stringTranslation = $string_translation;

  }

  /**
   * Returns a recent hits page.
   *
   * @return array
   *   A render array representing the recent hits page content.
   */
  public function display(): array {
    $form = $this->formBuilder->getForm('Drupal\visitors\Form\DateFilter');
    $header = $this->getHeader();

    return [
      'visitors_date_filter_form' => $form,
      'visitors_table' => [
        '#type'  => 'table',
        '#header' => $header,
        '#rows'   => $this->report->recent($header),
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
      'visitors_id' => [
        'data'      => $this->t('ID'),
        'field'     => 'visitors_id',
        'specifier' => 'visitors_id',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
        'sort'      => 'desc',
      ],
      'visitors_date_time' => [
        'data'      => $this->t('Date'),
        'field'     => 'visitors_date_time',
        'specifier' => 'visitors_date_time',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
      ],
      'visitors_url' => [
        'data'      => $this->t('URL'),
        'field'     => 'visitors_url',
        'specifier' => 'visitors_url',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
      ],
      'u.name' => [
        'data'      => $this->t('User'),
        'field'     => 'u.name',
        'specifier' => 'u.name',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
      ],
      '' => [
        'data'      => $this->t('Details'),
      ],
    ];
  }

}
