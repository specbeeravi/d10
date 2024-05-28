<?php

namespace Drupal\visitors\Service;

use Drupal\Core\Database\Connection;
use Drupal\visitors\VisitorsRebuildIpAddressInterface;

/**
 * Convert legacy IP address to new format.
 */
class RebuildIpAddressService implements VisitorsRebuildIpAddressInterface {


  const ERROR = -1;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new Rebuild Route Service.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->database = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuild(string $ip_address): int {
    if (inet_pton($ip_address) !== FALSE) {
      return 0;
    }
    $new_address = NULL;
    if (ip2long($ip_address) !== FALSE) {
      $new_address = ip2long($ip_address);
    }
    elseif (inet_ntop($ip_address) !== FALSE) {
      $new_address = inet_ntop($ip_address);
    }
    else {
      return self::ERROR;
    }

    $count = self::ERROR;
    try {
      $count = $this->database->update('visitors')
        ->fields(['visitors_ip' => $new_address])
        ->condition('visitors_ip', $ip_address)
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
  public function getIpAddresses(): array {
    $ip_addresses = $this->database->select('visitors', 'v')
      ->fields('v', [
        'visitors_ip',
      ])
      ->distinct()
      ->orderBy('visitors_ip', 'ASC')
      ->execute()->fetchAll();

    return $ip_addresses;
  }

}
