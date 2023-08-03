<?php

namespace Drupal\visitors_geoip\Service;

use Drupal\visitors_geoip\VisitorsGeoIpInterface;
use GeoIp2\Database\Reader;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * GeoIp lookup Service.
 *
 * @package visitors
 */
class GeoIpService implements VisitorsGeoIpInterface {

  /**
   * The GeoIP reader.
   *
   * @var \GeoIp2\Database\Reader
   */
  protected $reader;

  /**
   * Constructs a new GeoIpService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $settings = $config_factory->get('visitors_geoip.settings');
    $path = $settings->get('geoip_path');
    $exists = file_exists($path);
    if ($exists) {
      $this->reader = new Reader($path);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function metadata() {
    if (is_null($this->reader)) {
      return NULL;
    }
    $metadata = $this->reader->metadata();
    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function city($ip_address) {
    if (is_null($this->reader)) {
      return NULL;
    }
    $record = $this->reader->city($ip_address);
    return $record;
  }

  /**
   * {@inheritdoc}
   */
  public function getReader() {
    return $this->reader;
  }

  /**
   * {@inheritdoc}
   */
  public function setReader($reader) {
    $this->reader = $reader;
  }

}
