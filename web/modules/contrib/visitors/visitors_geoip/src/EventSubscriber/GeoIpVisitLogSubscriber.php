<?php

namespace Drupal\visitors_geoip\EventSubscriber;

use Drupal\visitors_geoip\VisitorsGeoIpInterface;
use Drupal\visitors\Event\VisitLogEvent;
use GeoIp2\Exception\AddressNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UserLoginSubscriber.
 *
 * @package Drupal\visitors\EventSubscriber
 */
class GeoIpVisitLogSubscriber implements EventSubscriberInterface {

  /**
   * The GeoIP service.
   *
   * @var \Drupal\visitors_geoip\VisitorsGeoIpInterface
   */
  protected $geoip;

  /**
   * The GeoIP service.
   *
   * @var \Drupal\visitors_geoip\VisitorsGeoIpInterface
   */
  public function __construct(VisitorsGeoIpInterface $geoip) {
    $this->geoip = $geoip;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // Static class constant => method on this class.
      VisitLogEvent::EVENT_NAME => 'onVisit',
    ];
  }

  /**
   * Subscribe to the user login event dispatched.
   *
   * @param \Drupal\visitors\Event\VisitLogEvent $event
   *   Our custom event object.
   */
  public function onVisit(VisitLogEvent $event) {

    try {
      $fields = $event->getFields();
      $geoip = $this->geoip->city($fields['visitors_ip']);
      if (is_null($geoip)) {
        return NULL;
      }
      $geoip_data['continent_code'] = $geoip->continent->code;
      $geoip_data['country_code']   = $geoip->country->isoCode;
      $geoip_data['region']         = $geoip->subdivisions[0]->isoCode;
      $geoip_data['country_name']   = $geoip->country->names['en'];
      $geoip_data['city']           = $geoip->city->names['en'];
      $geoip_data['postal_code']    = $geoip->postal->code;
      $geoip_data['latitude']       = $geoip->location->latitude;
      $geoip_data['longitude']      = $geoip->location->longitude;
      $geoip_data['area_code']      = $geoip->location->metroCode;

      $fields['location_continent_code'] = $geoip_data['continent_code'];
      $fields['location_country_code']   = $geoip_data['country_code'];
      $fields['location_country_name']   = $geoip_data['country_name'];
      $fields['location_region']         = $geoip_data['region'];
      $fields['location_city']           = $geoip_data['city'];
      $fields['location_postal_code']    = $geoip_data['postal_code'];
      $fields['location_latitude']       = $geoip_data['latitude'];
      $fields['location_longitude']      = $geoip_data['longitude'];
      $fields['location_area_code']      = $geoip_data['area_code'];

      $event->setFields($fields);
    }
    catch (AddressNotFoundException $e) {
      // Do nothing.
    }
    catch (\Exception $e) {
      watchdog_exception('visitors_geoip', $e);
    }

  }

}
