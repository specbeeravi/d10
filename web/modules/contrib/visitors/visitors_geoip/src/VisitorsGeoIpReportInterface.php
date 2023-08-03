<?php

namespace Drupal\visitors_geoip;

/**
 * Visitors GeoLocation reports.
 *
 * @package visitors_geoip
 */
interface VisitorsGeoIpReportInterface {

  /**
   * Returns a table content.
   *
   * @param array $header
   *   Table header configuration.
   *
   * @return array
   *   Array representing the table content.
   */
  public function countries(array $header);

  /**
   * Returns a table content.
   *
   * @param array $header
   *   Table header configuration.
   * @param string $country
   *   The name of the country.
   *
   * @return array
   *   Array representing the table content.
   */
  public function cities(array $header, string $country);

  /**
   * Returns a table content.
   *
   * @param array $header
   *   Table header configuration.
   * @param string $country
   *   The name of the country.
   * @param string $city
   *   The name of the city.
   *
   * @return array
   *   Array representing the table content.
   */
  public function cityHits(array $header, string $country, string $city);

}
