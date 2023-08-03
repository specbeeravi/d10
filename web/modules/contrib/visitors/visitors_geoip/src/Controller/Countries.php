<?php

namespace Drupal\visitors_geoip\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\visitors_geoip\VisitorsGeoIpReportInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Country Report controller.
 */
class Countries extends ControllerBase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The report service.
   *
   * @var \Drupal\visitors_geoip\VisitorsGeoIpReportInterface
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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('visitors_geoip.report'),
      $container->get('string_translation')
    );
  }

  /**
   * Constructs a Countries object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\visitors_geoip\VisitorsGeoIpReportInterface $report
   *   The report service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    FormBuilderInterface $form_builder,
    VisitorsGeoIpReportInterface $report,
    TranslationInterface $string_translation) {

    $this->formBuilder       = $form_builder;
    $this->report            = $report;
    $this->stringTranslation = $string_translation;

  }

  /**
   * Returns a countries page.
   *
   * @return array
   *   A render array representing the countries page content.
   */
  public function display(): array {

    $form = $this->formBuilder->getForm('Drupal\visitors\Form\DateFilter');
    $header = $this->getHeader();

    return [
      'visitors_date_filter_form' => $form,
      'visitors_table' => [
        '#type'  => 'table',
        '#header' => $header,
        '#rows'   => $this->report->countries($header),

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
      'location_country_name' => [
        'data'      => $this->t('Country'),
        'field'     => 'location_country_name',
        'specifier' => 'location_country_name',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
      ],
      'count' => [
        'data'      => $this->t('Count'),
        'field'     => 'count',
        'specifier' => 'count',
        'class'     => [RESPONSIVE_PRIORITY_LOW],
        'sort'      => 'desc',
      ],
    ];
  }

}
