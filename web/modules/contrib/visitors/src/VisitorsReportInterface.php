<?php

namespace Drupal\visitors;

/**
 * Visitors report data.
 */
interface VisitorsReportInterface {

  /**
   * The number of hits for each day of the month.
   */
  const REFERER_TYPE_INTERNAL_PAGES = 0;

  /**
   * The number of hits for each day of the month.
   */
  const REFERER_TYPE_EXTERNAL_PAGES = 1;

  /**
   * Get the number of hits for each day of the month.
   *
   * @param array|null $header
   *   Table header configuration.
   *
   * @return array
   *   The number of hits for each day of the month.
   */
  public function daysOfMonth(array $header = NULL);

  /**
   * Get the number of hits for each day of the week.
   *
   * @return array
   *   The number of hits for each day of the week.
   */
  public function daysOfWeek();

  /**
   * Get the number of hits for each host (ip address).
   *
   * @param array|null $header
   *   Table header configuration.
   *
   * @return array
   *   The number of hits for each host (ip address).
   */
  public function hosts(array $header);

  /**
   * Get the number of hits for each hour of the day.
   *
   * @param array $header
   *   Table header configuration.
   *
   * @return array
   *   The number of hits for each hour of the day.
   */
  public function hours(array $header = NULL);

  /**
   * Get the number of hits for each month of the year.
   *
   * @param array $header
   *   Table header configuration.
   *
   * @return array
   *   The number of hits for each month of the year.
   */
  public function monthly(array $header = NULL);

  /**
   * Get recent visits.
   *
   * @param array $header
   *   Table header configuration.
   * @param string $host
   *   The hostname.
   *
   * @return array
   *   The number of recent.
   */
  public function recentHost(array $header, string $host);

  /**
   * Get the number of hits for each referer.
   *
   * @param array $header
   *   Table header configuration.
   *
   * @return array
   *   The number of hits for each referer.
   */
  public function referer(array $header);

  /**
   * Get the number of hits for most visited pages.
   *
   * @param array $header
   *   Table header configuration.
   *
   * @return array
   *   The number of hits for most visited pages.
   */
  public function top(array $header);

  /**
   * Get the number of hits for each user agent.
   *
   * @param array $header
   *   Table header configuration.
   *
   * @return array
   *   The number of hits for each user agent.
   */
  public function activity(array $header);

  /**
   * Returns chart width.
   */
  public function width();

  /**
   * Returns chart height.
   */
  public function height();

  /**
   * Returns a translated days of week array.
   *
   * @return array
   *   An array of translated days.
   */
  public function getTranslatedDays();

  /**
   * Returns most recent visits.
   *
   * @return array
   *   The most recent visits.
   */
  public function recent(array $header);

  /**
   * Details about the visit.
   *
   * @param int $hit_id
   *   The hit id.
   *
   * @return array
   *   The details about the visit.
   */
  public function hitDetails($hit_id);

  /**
   * Visits related to the node.
   *
   * @param array $header
   *   Table header configuration.
   * @param int $nid
   *   The node id.
   *
   * @return array
   *   The visits related to a node.
   */
  public function node(array $header, int $nid);

}
