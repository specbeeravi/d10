<?php

namespace Drupal\webform_booking_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for Webform Booking Calendar.
 */
class WebformBookingCalendarController extends ControllerBase {

  /**
   * Provide booking data as events for the calendar.
   */
  public function getEvents($webform_ids, $element_names) {
    // Check if the user has the required permission.
    if (!$this->currentUser()->hasPermission('view webform booking calendar')) {
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    $events = [];
    $processed = []; // Array to track unique events by submission ID.

    // Split comma-separated values.
    $webform_ids = explode(',', $webform_ids);
    $element_names = explode(',', $element_names);

    // Fetch webform labels for all IDs.
    $webform_labels = $this->getWebformLabels($webform_ids);

    foreach ($webform_ids as $webform_id) {
      foreach ($element_names as $element_name) {
        // Fetch data for the specified Webform and element.
        $query = \Drupal::database()->select('webform_submission_data', 'wsd')
          ->fields('wsd', ['sid', 'webform_id', 'name', 'value'])
          ->condition('wsd.webform_id', $webform_id)
          ->condition('wsd.name', $element_name);
        $results = $query->execute()->fetchAll();

        foreach ($results as $result) {
          // Create a unique key based on webform ID and submission ID.
          $unique_key = $result->webform_id . ':' . $result->sid;

          // Ensure each event is processed only once.
          if (!isset($processed[$unique_key]) && strpos($result->value, '|') !== false) {
            [$datetime, $seats] = explode('|', $result->value);
            $start_time = strtotime($datetime);
            $end_time = $start_time + (1 * 60 * 60) + (45 * 60); // 1 hour 45 mins

            // Generate the submission URL using \Drupal\Core\Url.
            $submission_url = Url::fromRoute('entity.webform_submission.canonical', [
              'webform' => $result->webform_id,
              'webform_submission' => $result->sid,
            ], ['absolute' => TRUE])->toString();

            // Fetch additional details for the tooltip.
            $details = $this->fetchSubmissionDetails($result->sid);

            // Add webform label.
            $webform_name = $webform_labels[$result->webform_id] ?? 'Unknown Webform';

            $events[] = [
              'title' => 'Booking (' . $seats . ' seats)',
              'start' => date('Y-m-d\TH:i:s', $start_time),
              'end' => date('Y-m-d\TH:i:s', $end_time),
              'url' => $submission_url,
              'webform_id' => $webform_id, // Add webform_id to be used as a className.
              'details' => "Webform: $webform_name\n" . $details, // Add additional details for tooltip.
            ];

            // Mark the event as processed.
            $processed[$unique_key] = TRUE;
          }
        }
      }
    }

    return new JsonResponse($events);
  }

  /**
   * Fetch additional details for a webform submission.
   */
  private function fetchSubmissionDetails($submission_id) {
    // Fetch additional submission data for the tooltip.
    $query = \Drupal::database()->select('webform_submission_data', 'wsd')
      ->fields('wsd', ['name', 'value'])
      ->condition('wsd.sid', $submission_id);
    $results = $query->execute()->fetchAllKeyed();

    // Format the details as a string.
    $details = [];
    $total_price = 'N/A';

    foreach ($results as $name => $value) {
      if ($name === 'paypal_transaction') {
        // Decode the PayPal transaction JSON and extract the price.
        $decoded = json_decode($value, TRUE);
        $total_price = $decoded['purchase_units'][0]['amount']['value'] ?? 'N/A';
      } else {
        $details[] = ucfirst(str_replace('_', ' ', $name)) . ': ' . $value;
      }
    }

    // Add the total price to the tooltip details.
    $details[] = 'Total Price Paid: ' . $total_price;

    return implode("\n", $details); // Return the details as a newline-separated string.
  }

  /**
   * Get webform labels for given webform IDs.
   */
  private function getWebformLabels(array $webform_ids) {
    $labels = [];
    $webforms = \Drupal::entityTypeManager()
      ->getStorage('webform')
      ->loadMultiple($webform_ids);

    foreach ($webforms as $webform) {
      $labels[$webform->id()] = $webform->label();
    }

    return $labels;
  }
}
