<?php

namespace Drupal\visitors_geoip\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\visitors_geoip\VisitorsGeoIpReportInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Geo location report service.
 *
 * @package visitors_geoip
 */
class GeoReportService implements VisitorsGeoIpReportInterface {
  use StringTranslationTrait;

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The number of items per page.
   *
   * @var int
   */
  protected $itemsPerPage;

  /**
   * The current page.
   *
   * @var int
   */
  protected $page;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $date;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * String translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Report service.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $stack
   *   The request stack service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date
   *   The date service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    Connection $database,
    ConfigFactoryInterface $config_factory,
    RequestStack $stack,
    RendererInterface $renderer,
    DateFormatterInterface $date,
    EntityTypeManagerInterface $entity_type_manager,
    TranslationInterface $string_translation) {

    $this->database = $database;
    $this->itemsPerPage = $config_factory->get('visitors.config')->get('items_per_page') ?? 10;
    $this->page = $stack->getCurrentRequest()->query->get('page') ?? 0;
    $this->renderer = $renderer;
    $this->date = $date;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function countries(array $header) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('visitors', 'v')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');

    $query->addExpression('COUNT(location_country_name)', 'count');
    $query->fields('v', ['location_country_name']);
    visitors_date_filter_sql_condition($query);
    $query->groupBy('location_country_name');
    $query->orderByHeader($header);
    $query->limit($this->itemsPerPage);

    $count_query = $this->database->select('visitors', 'v');
    $count_query->addExpression('COUNT(DISTINCT location_country_name)');
    visitors_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = [];
    $i = $this->page * $this->itemsPerPage;
    foreach ($results as $data) {
      if ($data->location_country_name == '') {
        $data->location_country_name = '(none)';
      }
      $visitors_country_url = Url::fromRoute('visitors.cities', [
        'country' => $data->location_country_name,
      ]);
      $visitors_country_link = Link::fromTextAndUrl($data->location_country_name, $visitors_country_url);
      $visitors_country_link = $visitors_country_link->toRenderable();
      $i += 1;
      $rows[] = [
        $i,
        $this->renderer->render($visitors_country_link),
        $data->count,
      ];
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function cities(array $header, string $country) {
    $original_country = ($country == '(none)') ? '' : $country;

    $query = $this->database->select('visitors', 'v')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');

    $query->addExpression('COUNT(location_city)', 'count');
    $query->fields('v', ['location_city']);
    $query->condition('v.location_country_name', $original_country);
    visitors_date_filter_sql_condition($query);
    $query->groupBy('location_city');
    $query->orderByHeader($header);
    $query->limit($this->itemsPerPage);

    $count_query = $this->database->select('visitors', 'v');
    $count_query->addExpression('COUNT(DISTINCT location_city)');
    $count_query->condition('v.location_country_name', $original_country);
    visitors_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = [];
    $i = $this->page * $this->itemsPerPage;
    foreach ($results as $data) {
      if ($data->location_city == '') {
        $data->location_city = '(none)';
      }
      $location_city_url = Url::fromRoute('visitors.city_hits', [
        "country" => $country,
        "city" => $data->location_city,
      ]);
      $location_city_link = Link::fromTextAndUrl($data->location_city, $location_city_url);
      $location_city_link = $location_city_link->toRenderable();
      $i += 1;
      $rows[] = [
        $i,
        $this->renderer->render($location_city_link),
        $data->location_city,
        $data->count,
      ];
    }
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function cityHits(array $header, string $country, string $city) {
    $original_country = ($country == '(none)') ? '' : $country;
    $original_city = ($city == '(none)') ? '' : $city;

    $query = $this->database->select('visitors', 'v')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');

    $query->leftJoin('users_field_data', 'u', 'u.uid=v.visitors_id');
    $query->fields(
      'v',
      [
        'visitors_id',
        'visitors_uid',
        'visitors_date_time',
        'visitors_title',
        'visitors_path',
        'visitors_url',
      ]
    );
    $query->fields('u', ['name', 'uid']);
    $query->condition('v.location_country_name', $original_country);
    $query->condition('v.location_city', $original_city);
    visitors_date_filter_sql_condition($query);
    $query->orderByHeader($header);
    $query->limit($this->itemsPerPage);

    $count_query = $this->database->select('visitors', 'v');
    $count_query->addExpression('COUNT(*)');
    $count_query->condition('v.location_country_name', $original_country);
    $count_query->condition('v.location_city', $original_city);
    visitors_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = [];
    $i = $this->page * $this->itemsPerPage;
    foreach ($results as $data) {
      $user = $this->entityTypeManager->getStorage('user')->load($data->visitors_uid);
      $username = ['#type' => 'username', '#account' => $user];
      $i += 1;
      $rows[] = [
        $i,
        $data->visitors_id,
        $this->date->format($data->visitors_date_time, 'short'),
        $data->visitors_url,
        $this->renderer->render($username),
        Link::fromTextAndUrl($this->t('details'), Url::fromRoute('visitors.hit_details', ["hit_id" => $data->visitors_id])),
      ];
    }

    return $rows;
  }

}
