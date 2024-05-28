<?php

namespace Drupal\visitors\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\visitors\VisitorsTrackerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Visitors tracking controller.
 */
class Visitors extends ControllerBase {


  /**
   * The tracker service.
   *
   * @var \Drupal\visitors\VisitorsTrackerInterface
   */
  protected $tracker;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $stack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): Visitors {
    return new static(
      $container->get('visitors.tracker'),
      $container->get('request_stack')
    );
  }

  /**
   * Visitor tracker.
   *
   * @param \Drupal\visitors\VisitorsTrackerInterface $tracker
   *   The date service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $stack
   *   The request stack.
   */
  public function __construct(VisitorsTrackerInterface $tracker, RequestStack $stack) {
    $this->tracker = $tracker;
    $this->stack = $stack;

  }

  /**
   * Tracks visits.
   */
  public function track(): Response {
    $query = $this->stack->getCurrentRequest()->query->all();
    $this->tracker->log($query);

    $response = new Response();
    $response->setContent('');

    return $response;
  }

}
