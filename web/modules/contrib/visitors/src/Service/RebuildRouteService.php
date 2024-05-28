<?php

namespace Drupal\visitors\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\visitors\VisitorsRebuildRouteInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Service for rebuilding routes.
 */
class RebuildRouteService implements VisitorsRebuildRouteInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The router.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $routerMatcher;

  /**
   * Constructs a new Rebuild Route Service.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router_matcher
   *   The router matcher.
   */
  public function __construct(Connection $connection, RequestMatcherInterface $router_matcher) {
    $this->database = $connection;
    $this->routerMatcher = $router_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuild(string $path): int {
    $count = 0;
    try {
      $request = Request::create($path);
      $result = $this->routerMatcher->matchRequest($request);
      if (empty($result['_route'])) {
        return $count;
      }
      $route = $result['_route'];
    }
    catch (ParamNotConvertedException $e) {
      $route = $e->getRouteName();
    }
    catch (ResourceNotFoundException $e) {
      // Do nothing.
    }
    catch (\Exception $e) {
      watchdog_exception('visitors', $e);
    }

    if (empty($route)) {
      return $count;
    }

    try {
      $count = $this->database->update('visitors')
        ->fields(['route' => $route])
        ->condition('visitors_path', $path)
        ->execute();
    }
    catch (\Exception $e) {
      watchdog_exception('visitors', $e);
    }

    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaths(): array {
    $records = [];
    try {
      $records = $this->database->select('visitors', 'v')
        ->fields('v', ['visitors_path'])
        ->condition('route', '')
        ->distinct()
        ->execute()
        ->fetchAll();
    }
    catch (\Exception $e) {
      watchdog_exception('visitors', $e);
    }

    return $records;
  }

}
