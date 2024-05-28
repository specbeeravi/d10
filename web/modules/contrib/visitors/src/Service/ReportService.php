<?php

namespace Drupal\visitors\Service;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\visitors\VisitorsReportInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Report data.
 *
 * @package visitors
 */
class ReportService implements VisitorsReportInterface {
  use StringTranslationTrait;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Items per page.
   *
   * @var int
   */
  protected $itemsPerPage;

  /**
   * The page number.
   *
   * @var int
   */
  protected $page;

  /**
   * The first day of week.
   *
   * @var int
   */
  protected $firstDay;

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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The width of chart.
   *
   * @var int
   */
  protected $width;

  /**
   * The height of chart.
   *
   * @var int
   */
  protected $height;

  /**
   * Database Service Object.
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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(
    Connection $database,
    ConfigFactoryInterface $config_factory,
    RequestStack $stack,
    RendererInterface $renderer,
    DateFormatterInterface $date,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler) {

    $this->database = $database;
    $this->firstDay = $config_factory->get('system.date')->get('first_day') ?? 0;
    $this->itemsPerPage = $config_factory->get('visitors.config')->get('items_per_page') ?? 10;
    $this->width = $config_factory->get('visitors.config')->get('width') ?? 600;
    $this->height = $config_factory->get('visitors.config')->get('height') ?? 400;
    $this->page = $stack->getCurrentRequest()->query->get('page') ?? 0;
    $this->renderer = $renderer;
    $this->date = $date;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function width() {
    return $this->width;
  }

  /**
   * {@inheritdoc}
   */
  public function height() {
    return $this->height;
  }

  /**
   * {@inheritdoc}
   */
  public function daysOfMonth(array $header = NULL) {
    $query = $this->database->select('visitors', 'v');
    $query->addExpression('COUNT(*)', 'count');
    $query->addExpression(
      visitors_date_format_sql('visitors_date_time', '%d'), 'day'
    );
    $query->groupBy('day');
    visitors_date_filter_sql_condition($query);

    if (!is_null($header)) {
      $query
        ->extend('Drupal\Core\Database\Query\TableSortExtender')
        ->orderByHeader($header);
    }

    $results = $query->execute();
    $rows = [];
    $i = 0;

    foreach ($results as $data) {
      $i += 1;
      $rows[] = [
        $i,
        (int) $data->day,
        $data->count,
      ];
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function daysOfWeek() {
    $query = $this->database->select('visitors', 'v');
    $query->addExpression('COUNT(*)', 'count');
    $query->addExpression(
      visitors_date_format_sql('visitors_date_time', '%a'), 'd'
    );
    $query->addExpression(
      visitors_date_format_sql('MIN(visitors_date_time)', '%w'), 'n'
    );
    visitors_date_filter_sql_condition($query);
    $query->groupBy('d');
    $query->orderBy('n');
    $results = $query->execute();

    $rows = [];
    $tmp_rows = [];

    foreach ($results as $data) {
      $tmp_rows[$data->n] = [
        $data->d,
        $data->count,
        $data->n,
      ];
    }
    $sort_days = $this->getDaysOfWeek();
    $trans_days = $this->getTranslatedDays();
    foreach ($sort_days as $day => $value) {
      $rows[$value] = [$value, $trans_days[$day]->render(), 0];
    }

    foreach ($tmp_rows as $tmp_item) {
      $day_of_week = Unicode::ucfirst(mb_strtolower($tmp_item[0]));
      $rows[$sort_days[$day_of_week]][2] = $tmp_item[1];
    }

    return $rows;
  }

  /**
   * Create days of week array.
   *
   * Using first_day parameter, using keys as day of week.
   *
   * @return array
   *   An array of sorted days.
   */
  protected function getDaysOfWeek(): array {
    $days      = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    $sort_days = [];
    $n         = 1;

    for ($i = $this->firstDay; $i < 7; $i++) {
      $sort_days[$days[$i]] = $n++;
    }

    for ($i = 0; $i < $this->firstDay; $i++) {
      $sort_days[$days[$i]] = $n++;
    }

    return $sort_days;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatedDays(): array {
    return [
      'Sun' => $this->t('Sunday'),
      'Mon' => $this->t('Monday'),
      'Tue' => $this->t('Tuesday'),
      'Wed' => $this->t('Wednesday'),
      'Thu' => $this->t('Thursday'),
      'Fri' => $this->t('Friday'),
      'Sat' => $this->t('Saturday'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function hosts(array $header) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('visitors', 'v')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');

    $query->addExpression('COUNT(*)', 'count');
    $query->fields('v', ['visitors_ip']);
    visitors_date_filter_sql_condition($query);
    $query->groupBy('visitors_ip');
    $query->orderByHeader($header);
    $query->limit($this->itemsPerPage);

    $count_query = $this->database->select('visitors', 'v');
    $count_query->addExpression('COUNT(DISTINCT visitors_ip)');
    visitors_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = [];

    $i = $this->page * $this->itemsPerPage;
    foreach ($results as $data) {
      $ip = $data->visitors_ip;
      $visitors_host_url = Url::fromRoute('visitors.host_hits', [
        "host" => $ip,
      ]);
      $visitors_host_link = Link::fromTextAndUrl($ip, $visitors_host_url);
      $visitors_host_link = $visitors_host_link->toRenderable();

      $i += 1;
      $rows[] = [
        $i,
        $ip,
        $data->count,
        $this->renderer->render($visitors_host_link),
      ];
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function hours($header = NULL) {
    $query = $this->database->select('visitors', 'v');
    $query->addExpression('COUNT(*)', 'count');
    $query->addExpression(
      visitors_date_format_sql('visitors_date_time', '%H'), 'hour'
    );
    visitors_date_filter_sql_condition($query);
    $query->groupBy('hour');

    if (!is_null($header)) {
      $query
        ->extend('Drupal\Core\Database\Query\TableSortExtender')
        ->orderByHeader($header);
    }

    $results = $query->execute();
    $rows    = [];
    $i       = 0;

    foreach ($results as $data) {
      $i += 1;
      $rows[] = [
        $i,
        $data->hour,
        $data->count,
      ];
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function monthly(array $header = NULL) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('visitors', 'v')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');

    $query->addExpression('COUNT(*)', 'count');
    $query->addExpression(
      visitors_date_format_sql('visitors_date_time', '%Y%m'), 'm'
    );
    $query->addExpression(
      visitors_date_format_sql('MIN(visitors_date_time)', '%Y %M'), 's'
    );
    visitors_date_filter_sql_condition($query);
    $query->groupBy('m');
    if (!is_null($header)) {
      $query
        ->extend('Drupal\Core\Database\Query\TableSortExtender')
        ->orderByHeader($header);
    }
    $query->limit($this->itemsPerPage);

    $count_query = $this->database->select('visitors', 'v');
    $count_query->addExpression(
      sprintf('COUNT(DISTINCT %s)',
      visitors_date_format_sql('visitors_date_time', '%Y %M'))
    );

    visitors_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = [];
    $i = $this->page * $this->itemsPerPage;

    foreach ($results as $data) {
      $i += 1;
      $rows[] = [
        $i,
        $data->s,
        $data->count,
      ];
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function recentHost(array $header, string $host) {

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('visitors', 'v')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');

    $query->leftJoin('users_field_data', 'u', 'u.uid=v.visitors_uid');
    $query->fields(
      'v',
      [
        'visitors_id',
        'visitors_ip',
        'visitors_uid',
        'visitors_date_time',
        'visitors_title',
        'visitors_path',
        'visitors_url',
      ]
    );
    $query->fields('u', ['name', 'uid']);
    $query->condition('v.visitors_ip', $host, '=');
    visitors_date_filter_sql_condition($query);
    $query->orderByHeader($header);
    $query->limit($this->itemsPerPage);

    $count_query = $this->database->select('visitors', 'v');
    $count_query->addExpression('COUNT(*)');
    $count_query->condition('visitors_ip', $host);
    visitors_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $count = $count_query->execute()->fetchField();
    if ($count == 0) {
      return NULL;
    }
    $rows = [];

    $i = $this->page * $this->itemsPerPage;
    foreach ($results as $data) {
      $user = $this->entityTypeManager
        ->getStorage('user')
        ->load($data->visitors_uid);

      $visitors_host_url = Url::fromRoute('visitors.hit_details', [
        'hit_id' => $data->visitors_id,
      ]);
      $visitors_host_link = Link::fromTextAndUrl($this->t('Details'), $visitors_host_url);
      $visitors_host_link = $visitors_host_link->toRenderable();

      $user_profile_url = Url::fromRoute('entity.user.canonical', [
        'user' => $user->id(),
      ]);
      $user_profile_link = Link::fromTextAndUrl($user->getAccountName(), $user_profile_url);
      $user_profile_link = $user_profile_link->toRenderable();
      $i += 1;
      $rows[] = [
        $i,
        $data->visitors_id,
        $this->date->format($data->visitors_date_time, 'short'),
        $data->visitors_title . " - " . $data->visitors_url,
        $this->renderer->render($user_profile_link),
        $this->renderer->render($visitors_host_link),
      ];
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function referer(array $header) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('visitors', 'v')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');

    $query->addExpression('COUNT(*)', 'count');
    $query->fields('v', ['visitors_referer']);
    visitors_date_filter_sql_condition($query);
    $query = $this->setReferersCondition($query);
    $query->condition('visitors_referer', '', '<>');
    $query->groupBy('visitors_referer');
    $query->orderByHeader($header);
    $query->limit($this->itemsPerPage);

    $count_query = $this->database->select('visitors', 'v');
    $count_query->condition('visitors_referer', '', '<>');
    $count_query->addExpression('COUNT(DISTINCT visitors_referer)');
    visitors_date_filter_sql_condition($count_query);
    $count_query = $this->setReferersCondition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = [];
    $i = $this->page * $this->itemsPerPage;
    foreach ($results as $data) {
      $i += 1;
      $rows[] = [
        $i,
        empty($data->visitors_referer) ? $this->t('No Referer') : $data->visitors_referer,
        $data->count,
      ];
    }
    return $rows;
  }

  /**
   * Build sql query from referer type value.
   */
  protected function setReferersCondition($query) {
    switch ($_SESSION['referer_type']) {
      case VisitorsReportInterface::REFERER_TYPE_INTERNAL_PAGES:
        $query->condition(
          'visitors_referer',
          sprintf('%%%s%%', $_SERVER['HTTP_HOST']),
          'LIKE'
        );
        $query->condition('visitors_referer', '', '<>');
        break;

      case VisitorsReportInterface::REFERER_TYPE_EXTERNAL_PAGES:
        $query->condition(
          'visitors_referer',
          sprintf('%%%s%%', $_SERVER['HTTP_HOST']),
          'NOT LIKE'
        );
        break;

      default:
        break;
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function top(array $header) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('visitors', 'v')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');

    $query->addExpression('COUNT(visitors_id)', 'count');
    // $query->addExpression('MIN(visitors_title)', 'visitors_title');
    // $query->addExpression('MAX(visitors_url)', 'visitors_url');
    $query->fields('v', ['visitors_path']);
    visitors_date_filter_sql_condition($query);
    $query->groupBy('visitors_path');
    $query->orderByHeader($header);
    $query->limit($this->itemsPerPage);

    $count_query = $this->database->select('visitors', 'v');
    $count_query->addExpression('COUNT(DISTINCT visitors_path)');
    visitors_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = [];
    $i = $this->page * $this->itemsPerPage;
    foreach ($results as $data) {
      $i = $i + 1;
      $rows[] = [
        $i,
        $data->visitors_path,
        $data->count,
      ];
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function activity(array $header) {
    $is_comment_module_exist = $this->moduleHandler->moduleExists('comment');
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('users_field_data', 'u')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');

    $query->leftJoin('visitors', 'v', 'u.uid=v.visitors_uid');
    $query->leftJoin('node_field_data', 'nfd', 'nfd.uid=v.visitors_uid');
    $query->leftJoin('node', 'n', 'nfd.nid=n.nid');
    if ($is_comment_module_exist) {
      $query->leftJoin('comment_field_data', 'c', 'u.uid=c.uid');
    }
    $query->fields('u', ['name', 'uid']);
    $query->addExpression('COUNT(DISTINCT v.visitors_id)', 'hits');
    $query->addExpression('COUNT(DISTINCT n.nid)', 'nodes');
    if ($is_comment_module_exist) {
      $query->addExpression('COUNT(DISTINCT c.cid)', 'comments');
    }
    visitors_date_filter_sql_condition($query);
    $query->groupBy('u.name');
    $query->groupBy('u.uid');
    $query->groupBy('v.visitors_uid');
    $query->groupBy('nfd.uid');
    if ($is_comment_module_exist) {
      $query->groupBy('c.uid');
    }
    $query->orderByHeader($header);
    $query->limit($this->itemsPerPage);

    $count_query = $this->database->select('users_field_data', 'u');
    $count_query->leftJoin('visitors', 'v', 'u.uid=v.visitors_uid');
    $count_query->addExpression('COUNT(DISTINCT u.uid)');
    visitors_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = [];
    $i = $this->page * $this->itemsPerPage;
    foreach ($results as $data) {
      $user = $this->entityTypeManager->getStorage('user')->load($data->uid);
      if ($is_comment_module_exist) {
        $i += 1;
        $rows[] = [
          $i,
          ($user->id() == 0) ? 'Anonymous User' : $user->getAccountName(),
          $data->hits,
          $data->nodes,
          $data->comments,
        ];
      }
      else {
        $i += 1;
        $rows[] = [
          $i,
          ($user->id() == 0) ? 'Anonymous User' : $user->getAccountName(),
          $data->hits,
          $data->nodes,
        ];
      }
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function recent(array $header) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
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
    visitors_date_filter_sql_condition($query);
    $query->orderByHeader($header);
    $query->limit($this->itemsPerPage);

    $count_query = $this->database->select('visitors', 'v');
    $count_query->addExpression('COUNT(*)');
    visitors_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = [];
    $i = $this->page * $this->itemsPerPage;
    foreach ($results as $data) {
      $user = $this->entityTypeManager
        ->getStorage('user')
        ->load($data->visitors_uid);
      $i += 1;
      $rows[] = [
        $i,
        $data->visitors_id,
        $this->date->format($data->visitors_date_time, 'short'),
        $data->visitors_path,
        $user->getAccountName(),
        Link::fromTextAndUrl($this->t('details'), Url::fromRoute('visitors.hit_details', ["hit_id" => $data->visitors_id])),
      ];
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function hitDetails($hit_id) {
    $query = $this->database->select('visitors', 'v');
    $query->leftJoin('users_field_data', 'u', 'u.uid=v.visitors_uid');
    $query->fields('v');
    $query->fields('u', ['name', 'uid']);
    $query->condition('v.visitors_id', (int) $hit_id);
    $hit_details = $query->execute()->fetch();

    $rows = [];

    if ($hit_details) {
      $url          = urldecode($hit_details->visitors_url);
      $referer      = $hit_details->visitors_referer;
      $date         = $this->date->format($hit_details->visitors_date_time, 'large');
      $whois_enable = $this->moduleHandler->moduleExists('whois');

      $attr = [
        'attributes' => [
          'target' => '_blank',
          'title'  => $this->t('Whois lookup'),
        ],
      ];
      $ip   = $hit_details->visitors_ip;
      $user = $this->entityTypeManager->getStorage('user')->load($hit_details->visitors_uid);
      // @todo make url, referer and username as link
      $array = [
        $this->t('URL')->render()        => $url,
        $this->t('Title')->render()      => ($hit_details->visitors_title ?? ''),
        $this->t('Referer')->render()    => $referer,
        $this->t('Date')->render()       => $date,
        $this->t('User')->render()       => $user->getAccountName(),
        $this->t('IP')->render()         => $whois_enable ? Link::fromTextAndUrl($ip, Url::fromUri('whois/' . $ip, $attr)) : $ip,
        $this->t('User Agent')->render() => ($hit_details->visitors_user_agent ?? ''),
      ];

      if ($this->moduleHandler->moduleExists('visitors_geoip')) {
        $geoip_data_array = [
          $this->t('Country')->render()         => ($hit_details->location_country_name ?? ''),
          $this->t('Region')->render()          => ($hit_details->location_region ?? ''),
          $this->t('City')->render()            => ($hit_details->location_city ?? ''),
          $this->t('Postal Code')->render()     => ($hit_details->location_postal ?? ''),
          $this->t('Latitude')->render()        => ($hit_details->location_latitude ?? ''),
          $this->t('Longitude')->render()       => ($hit_details->location_longitude ?? ''),
          $this->t('PSTN Area Code')->render()  => ($hit_details->location_area_code ?? ''),
        ];
        $array = array_merge($array, $geoip_data_array);
      }

      foreach ($array as $key => $value) {
        $rows[] = [['data' => $key, 'header' => TRUE], $value];
      }
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function node(array $header, int $nid) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('visitors', 'v')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->leftJoin('users_field_data', 'u', 'u.uid=v.visitors_id');
    $query->fields(
    'v',
    [
      'visitors_uid',
      'visitors_id',
      'visitors_date_time',
      'visitors_referer',
    ]
      );

    $query->fields('u', ['name', 'uid']);
    $db_or = Database::getConnection()->condition('or');
    $db_or->condition('v.visitors_path', '/node/' . $nid, '=');
    // @todo removed placeholder is this right?
    $db_or->condition(
      'v.visitors_path', '%/node/' . $nid . "%", 'LIKE'
      );
    $query->condition($db_or);

    visitors_date_filter_sql_condition($query);
    $query->orderByHeader($header);
    $query->limit($this->itemsPerPage);

    $count_query = $this->database->select('visitors', 'v');
    $count_query->addExpression('COUNT(*)');
    $count_query->condition($db_or);
    visitors_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();
    $rows = [];

    $i = $this->page * $this->itemsPerPage;

    foreach ($results as $data) {
      $user = $this->entityTypeManager->getStorage('user')->load($data->visitors_uid);
      $i += 1;
      $rows[] = [
        $i,
        $data->visitors_id,
        $this->date->format($data->visitors_date_time, 'short'),
        !empty($data->visitors_referer) ? $data->visitors_referer : 'none',
        $user->getAccountName(),
        Link::fromTextAndUrl($this->t('details'), Url::fromRoute('visitors.hit_details', ["hit_id" => $data->visitors_id])),
      ];
    }

    return $rows;
  }

}
