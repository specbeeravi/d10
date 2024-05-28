<?php

namespace Drupal\visitors\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\visitors\Event\VisitLogEvent;
use Drupal\visitors\VisitorsCounterInterface;
use Drupal\visitors\VisitorsTrackerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tracker for web analytics.
 */
class TrackerService implements VisitorsTrackerInterface {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The date service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The counter service.
   *
   * @var \Drupal\visitors\VisitorsCounterInterface
   */
  protected $counter;

  /**
   * Tracks visits and actions.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Component\Datetime\TimeInterface $time_service
   *   The date service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\visitors\VisitorsCounterInterface $counter
   *   The counter service.
   */
  public function __construct(
    RequestStack $request_stack,
    TimeInterface $time_service,
    ModuleHandlerInterface $module_handler,
    Connection $database,
    EventDispatcherInterface $event_dispatcher,
    VisitorsCounterInterface $counter
    ) {

    $this->request = $request_stack->getCurrentRequest();
    $this->time = $time_service;
    $this->moduleHandler = $module_handler;
    $this->database = $database;
    $this->eventDispatcher = $event_dispatcher;
    $this->counter = $counter;
  }

  /**
   * {@inheritdoc}
   */
  public function log(array $agent): void {
    $this->write($agent);
  }

  /**
   * Writes the events to the database.
   *
   * @param string[] $agent
   *   The agent array.
   */
  protected function write(array $agent): void {
    $path = '';
    $route = '';
    $viewed = NULL;
    $ip = $this->request->getClientIp();
    $custom = json_decode($agent['_cvar']);
    foreach ($custom as $c) {
      if ($c[0] == 'path') {
        $path = $c[1];
      }
      if ($c[0] == 'route') {
        $route = $c[1];
      }
      if ($c[0] == 'viewed') {
        $viewed = $c[1];
      }
    }

    $fields = [
      'visitors_uid'        => $agent['uid'],
      'visitors_ip'         => $ip,
      'visitors_date_time'  => $this->time->getRequestTime(),
      'visitors_url'        => $agent['url'],
      'visitors_referer'    => $agent['urlref'] ?? '',
      'visitors_path'       => $path,
      'visitors_title'      => $agent['action_name'] ?? '',
      'visitors_user_agent' => $this->getUserAgent(),
      'route'               => $route,
      'config_resolution'   => $agent['res'],
      'config_pdf'          => $agent['pdf'],
      'config_flash'        => $agent['fla'],
      'config_java'         => $agent['java'],
      'config_quicktime'    => $agent['qt'],
      'config_realplayer'   => $agent['realp'],
      'config_windowsmedia' => $agent['wma'],
      'config_silverlight'  => $agent['ag'],
      'config_cookie'       => $agent['cookie'],
    ];

    $event = new VisitLogEvent($fields);
    $this->eventDispatcher->dispatch($event);
    $fields = $event->getFields();
    try {
      $this->database->insert('visitors')
        ->fields($fields)
        ->execute();

      if (!is_null($viewed)) {
        [$type, $id] = explode(':', $viewed);
        $this->counter->recordView($type, $id);
      }
    }
    catch (\Exception $e) {
      watchdog_exception('visitors', $e);
    }

  }

  /**
   * Get visitor user agent.
   *
   * @return string
   *   string user agent, or empty string if user agent does not exist
   */
  protected function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
  }

}
