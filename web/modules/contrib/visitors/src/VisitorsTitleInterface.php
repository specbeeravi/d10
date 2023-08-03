<?php

namespace Drupal\visitors;

/**
 * Interface for page title hierarchy.
 */
interface VisitorsTitleInterface {

  /**
   * Returns the title for the current page.
   *
   * @return string[]
   *   The title for the current page.
   */
  public function title();

}
