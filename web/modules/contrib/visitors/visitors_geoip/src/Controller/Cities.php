<?php

namespace Drupal\visitors_geoip\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\visitors_geoip\VisitorsGeoIpReportInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * City Report controller.
 */
class Cities extends ControllerBase {

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
   * Constructs a Cities object.
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
   * Returns a cities page.
   *
   * @param string $country
   *   The name of the country.
   *
   * @return array
   *   A render array representing the cities page content.
   */
  public function display($country) {

    $form = $this->formBuilder->getForm('Drupal\visitors\Form\DateFilter');
    $header = $this->getHeader();
    return [
      '#title' => $this->t('Visitors from @country', ['@country' => $country]),
      'visitors_date_filter_form' => $form,
      'visitors_table' => [
        '#type'  => 'table',
        '#header' => $header,
        '#rows'   => $this->report->cities($header, $country),
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
  protected function getHeader() {
    return [
      '#' => [
        'data'      => $this->t('#'),
      ],
      'location_city' => [
        'data'      => $this->t('City'),
        'field'     => 'location_city',
        'specifier' => 'location_city',
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
