<?php

namespace Drupal\webform_booking_calendar\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Webform Booking Calendar block.
 *
 * @Block(
 *   id = "webform_booking_calendar_block",
 *   admin_label = @Translation("Webform Booking Calendar")
 * )
 */
class WebformBookingCalendarBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get configuration values.
    $config = $this->getConfiguration();
    $webform_ids = !empty($config['webform_ids']) ? $config['webform_ids'] : [];
    $element_names = !empty($config['element_names']) ? $config['element_names'] : [];

    // Ensure required values are set.
    if (empty($webform_ids) || empty($element_names)) {
      return [
        '#markup' => $this->t('Please configure this block to select Webforms and elements.'),
      ];
    }

    // Fetch submission data and attach calendar.
    $submissions = $this->fetchSubmissionData($webform_ids, $element_names);

    return [
      '#markup' => '<div id="calendar-' . $this->getPluginId() . '"></div>',
      '#attached' => [
        'library' => ['webform_booking_calendar/fullcalendar'],
        'drupalSettings' => [
          'webformBookingCalendar' => [
            $this->getPluginId() => [
              'webform_ids' => array_values($webform_ids),
              'element_names' => array_values($element_names),
              'submissions' => $submissions,
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Fetch submission data from the database.
   *
   * @param array $webform_ids
   *   The webform IDs to filter submissions.
   * @param array $element_names
   *   The element names to fetch.
   *
   * @return array
   *   An array of submission data.
   */
  protected function fetchSubmissionData(array $webform_ids, array $element_names) {
    $database = Database::getConnection();
    $query = $database->select('webform_submission_data', 'wsd')
      ->fields('wsd', ['sid', 'name', 'value'])
      ->condition('wsd.webform_id', $webform_ids, 'IN')
      ->condition('wsd.name', $element_names, 'IN');
    $results = $query->execute();

    $submissions = [];
    foreach ($results as $record) {
      if ($record->name === 'paypal_transaction') {
        $decoded = json_decode($record->value, TRUE);
        $total_price = $decoded['purchase_units'][0]['amount']['value'] ?? 'N/A';
        $submissions[$record->sid]['total_price'] = $total_price;
      } else {
        $submissions[$record->sid][$record->name] = $record->value;
      }
    }

    return $submissions;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Get existing configuration.
    $config = $this->getConfiguration();

    // Load all Webforms.
    $webforms = \Drupal::entityTypeManager()
      ->getStorage('webform')
      ->loadMultiple();

    $options = [];
    foreach ($webforms as $webform) {
      $options[$webform->id()] = $webform->label();
    }

    // Add form elements.
    $form['webform_ids'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Webforms'),
      '#options' => $options,
      '#default_value' => $config['webform_ids'] ?? [],
      '#required' => TRUE,
    ];

    $form['element_names'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Booking Element Keys'),
      '#default_value' => !empty($config['element_names']) ? implode("\n", $config['element_names']) : '',
      '#description' => $this->t('Enter one booking element key per line. Include "paypal_transaction" to fetch the total price.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('webform_ids', array_filter($form_state->getValue('webform_ids')));
    $this->setConfigurationValue(
      'element_names',
      array_filter(preg_split('/\r\n|\r|\n/', $form_state->getValue('element_names')))
    );
  }
}
