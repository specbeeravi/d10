<?php

namespace Drupal\visitors\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\visitors\VisitorsTrackerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\visitors\Event\VisitLogEvent;

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
   */
  public function __construct(
    RequestStack $request_stack,
    TimeInterface $time_service,
    ModuleHandlerInterface $module_handler,
    Connection $database,
    EventDispatcherInterface $event_dispatcher) {

    $this->request = $request_stack->getCurrentRequest();
    $this->time = $time_service;
    $this->moduleHandler = $module_handler;
    $this->database = $database;
    $this->eventDispatcher = $event_dispatcher;
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
    $ip = $this->request->getClientIp();
    $custom = json_decode($agent['_cvar']);
    foreach ($custom as $c) {
      if ($c[0] == 'path') {
        $path = $c[1];
      }
    }
    $fields = [
      'visitors_uid'        => $agent['uid'],
      'visitors_ip'         => $ip,
      'visitors_date_time'  => $this->time->getRequestTime(),
      'visitors_url'        => $agent['url'],
      'visitors_referer'    => $agent['urlref'] ?? '',
      'visitors_path'       => $path,
      'visitors_title'      => $agent['action_name'],
      'visitors_user_agent' => $this->getUserAgent(),
    ];

    $event = new VisitLogEvent($fields);
    $this->eventDispatcher->dispatch($event, VisitLogEvent::EVENT_NAME);
    $fields = $event->getFields();

    try {
      $this->database->insert('visitors')
        ->fields($fields)
        ->execute();
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
