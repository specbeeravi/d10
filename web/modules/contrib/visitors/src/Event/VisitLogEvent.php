<?php

namespace Drupal\visitors\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event that is fired when a user logs in.
 */
class VisitLogEvent extends Event {

  /**
   * The event name.
   */
  const EVENT_NAME = 'visit_log_event';

  /**
   * The fields.
   *
   * @var array
   */
  protected $fields;

  /**
   * Constructs the object.
   *
   * @param array $fields
   *   The fields.
   */
  public function __construct(array $fields) {
    $this->fields = $fields;
  }

  /**
   * Get the fields.
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * Set the fields.
   */
  public function setFields(array $fields) {
    $this->fields = $fields;
  }

}
