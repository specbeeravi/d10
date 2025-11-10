<?php

namespace Drupal\webform_booking\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'webform_booking' element.
 *
 * @WebformElement(
 *   id = "webform_booking",
 *   label = @Translation("Booking"),
 *   description = @Translation("Provides a webform element for scheduling appointments."),
 *   category = @Translation("Booking"),
 * )
 *
 * @FormElement("webform_webform_booking")
 *
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class WebformBooking extends WebformElementBase {
  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties() + [
      'title' => '',
      'start_date' => '',
      'end_date' => '',
      'exclusion_dates' => '',
      'days_advance' => '0',
      'days_visible' => '0',
      'excluded_weekdays' => [],
      'time_interval' => '9:00|16:30',
      'slot_duration' => 60,
      'seats_slot' => '1',
      'max_seats_per_booking' => 1,
      'no_slots' => 'No slots available.',
      'paypal_enabled' => FALSE,
      'default_price' => 0,
      'excluded_time_periods' => '',
      'date_label' => '',
      'slot_label' => '',
      'seats_label' => '',
    ];
    unset($properties['markup']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['#attached']['library'][] = 'webform_booking/webform_booking_element';

    unset($form['markup'], $form['default']);
    $form['form']['#access'] = FALSE;

    $form['appointment_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Webform Booking Settings'),
    ];

    $form['appointment_settings']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->configuration['title'] ?? '',
    ];

    $form['appointment_settings']['date_range'] = [
      '#type' => 'container',
    ];

    $form['appointment_settings']['date_range']['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date'),
      '#default_value' => $this->configuration['start_date'] ?? '',
      '#prefix' => '<div class="booking-inline-field">',
    ];

    $form['appointment_settings']['date_range']['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End Date'),
      '#default_value' => $this->configuration['end_date'] ?? '',
      '#suffix' => '</div>',
    ];

    $form['appointment_settings']['time_interval'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Time Intervals'),
      '#description' => $this->t('Enter time intervals in 24h format. One interval per line. Ex. 08:00|12:00. You can also add exceptions for specific days or weekdays (weekdays in English). Ex: 10:00|12:00(Friday) or 10:00|12:00(2024-10-04)'),
      '#default_value' => $this->configuration['time_interval'] ?? '9:00|16:30',
      '#required' => TRUE,
      '#element_validate' => [[get_class($this), 'validateTimeIntervals']],
      '#rows' => 2,
    ];

    $form['appointment_settings']['slot_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Slot Duration (min)'),
      '#description' => $this->t('In Minutes'),
      '#default_value' => $this->configuration['slot_duration'] ?? 60,
      '#min' => 1,
      '#required' => TRUE,
    ];

    $form['appointment_settings']['seats_slot'] = [
      '#type' => 'number',
      '#title' => $this->t('Seats Per Slot'),
      '#description' => $this->t('Number of bookings for the same slot'),
      '#default_value' => $this->configuration['seats_slot'] ?? 1,
      '#min' => 1,
      '#required' => TRUE,
      '#prefix' => '<div class="booking-inline-field">',
    ];

    $form['appointment_settings']['max_seats_per_booking'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Seats Per Booking'),
      '#description' => $this->t('Maximum number of seats that can be booked in a single reservation'),
      '#default_value' => $this->configuration['max_seats_per_booking'] ?? 1,
      '#min' => 1,
      '#required' => TRUE,
      '#suffix' => '</div>',
    ];

    $form['advanced_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced options'),
      '#open' => FALSE,
      '#weight' => 43,
    ];

    $form['advanced_options']['days_advance'] = [
      '#type' => 'number',
      '#title' => $this->t('Days in Advance'),
      '#default_value' => $this->configuration['days_advance'] ?? 0,
      '#description' => $this->t('Number of days in advance a booking can be made'),
      '#prefix' => '<div class="booking-inline-field">',
    ];

    $form['advanced_options']['days_visible'] = [
      '#type' => 'number',
      '#title' => $this->t('Days Visible'),
      '#description' => $this->t('Number of days from now a booking can be made, 0 for no limit'),
      '#suffix' => '</div>',
    ];

    $form['exclusions'] = [
      '#type' => 'details',
      '#title' => $this->t('Exclusions'),
      '#open' => FALSE,
      '#weight' => 44,
    ];

    $form['exclusions']['exclusion_dates'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclusion Dates'),
      '#description' => $this->t('Enter dates in format YYYY-MM-DD or intervals in format YYYY-MM-DD|YYYY-MM-DD, one per line.<br>Ex.<br>@example_date_single<br>@example_date_multi', [
        '@example_date_single' => $this->getExampleDates()['single'],
        '@example_date_multi' => $this->getExampleDates()['multi'],
      ]),
      '#default_value' => $this->configuration['exclusion_dates'] ?? '',
      '#rows' => 3,
      '#element_validate' => [[get_class($this), 'validateExclusionDates']],
    ];

    $form['exclusions']['excluded_weekdays'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Excluded Weekdays'),
      '#options' => [
        'Mon' => $this->t('Mon'),
        'Tue' => $this->t('Tue'),
        'Wed' => $this->t('Wed'),
        'Thu' => $this->t('Thu'),
        'Fri' => $this->t('Fri'),
        'Sat' => $this->t('Sat'),
        'Sun' => $this->t('Sun'),
      ],
      '#default_value' => $this->configuration['excluded_weekdays'] ?? [],
    ];

    // Add new option for time period exclusions.
    $form['exclusions']['excluded_time_periods'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded Time Periods'),
      '#description' => $this->t('Enter time periods in 24h format HH:MM|HH:MM, one per line. You can also add exceptions for specific days (weekdays in English) or dates (YYYY-MM-DD).<br>Ex.<br>12:00|13:00<br>15:30|16:30(Friday)<br>14:00|15:00(2024-10-04)'),
      '#default_value' => $this->configuration['excluded_time_periods'] ?? '',
      '#element_validate' => [[get_class($this), 'validateExcludedTimePeriods']],
    ];

    $form['labels'] = [
      '#type' => 'details',
      '#title' => $this->t('Labels/Messages'),
      '#open' => FALSE,
      '#weight' => 45,
    ];

    $form['labels']['date_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date Label'),
      '#description' => $this->t('Custom label for the date selection. Leave empty to use default.'),
      '#default_value' => $this->configuration['date_label'] ?? '',
    ];

    $form['labels']['slot_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slot Label'),
      '#description' => $this->t('Custom label for the time slot selection. Leave empty to use default.'),
      '#default_value' => $this->configuration['slot_label'] ?? '',
    ];

    $form['labels']['seats_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Seats Label'),
      '#description' => $this->t('Custom label for the seats selection. Leave empty to use default.'),
      '#default_value' => $this->configuration['seats_label'] ?? '',
    ];

    $form['labels']['no_slots'] = [
      '#type' => 'textarea',
      '#title' => $this->t('No slots available message'),
      '#description' => $this->t('Insert the text that will be displayed when there are no slots available.'),
      '#default_value' => $this->configuration['no_slots'] ?? '',
      '#rows' => 3,
    ];

    // Add PayPal integration settings.
    $form['payment_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Payment Settings'),
      '#weight' => 46,
    ];

    $form['payment_settings']['paypal_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable PayPal integration'),
      '#default_value' => $this->configuration['paypal_enabled'] ?? FALSE,
    ];

    $form['payment_settings']['default_price'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Price per Seat'),
      '#default_value' => $this->configuration['default_price'] ?? 0,
      '#min' => 0,
      '#step' => 0.01,
      '#states' => [
        'visible' => [
          ':input[name="properties[paypal_enabled]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="properties[paypal_enabled]"]' => ['checked' => TRUE],
        ],
      ],
      '#element_validate' => [[get_class($this), 'validateDefaultPrice']],
      '#description' => $this->t('Set <a href="@url" target="_blank">PayPal Client ID</a>', [
        '@url' => '/admin/config/services/webform-booking',
      ]),
    ];

    // Adjust weights of generic details.
    if (isset($form['element_description'])) {
      $form['element_description']['#weight'] = 47;
    }
    if (isset($form['validation'])) {
      $form['validation']['#weight'] = 48;
    }

    // Add validation for date range.
    $form['#element_validate'][] = [get_class($this), 'validateDateRange'];

    return $form;
  }

  /**
   * Generate example dates for exclusion dates.
   *
   * @return array
   *   An associative array with example single and multi dates.
   */
  protected static function getExampleDates() {
    $example_date_single = date('Y-m-d');
    $example_date_multi = date('Y-m-d', strtotime('first day of next month')) . '|' . date('Y-m-d', strtotime('last day of next month'));
    return [
      'single' => $example_date_single,
      'multi' => $example_date_multi,
    ];
  }

  /**
   * Validate time intervals.
   */
  public static function validateTimeIntervals(array &$element, FormStateInterface $form_state) {
    $time_intervals = $form_state->getValue($element['#parents']);

    if (is_array($time_intervals)) {
      $time_intervals = reset($time_intervals);
    }

    $time_interval_pattern = '/^(\d{1,2}:\d{2})\|(\d{1,2}:\d{2})(\((Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday|\d{4}-\d{2}-\d{2})\))?$/';
    $lines = preg_split('/\r\n|\r|\n/', $time_intervals);

    foreach ($lines as $line) {
      $line = trim($line);
      if (!empty($line) && !preg_match($time_interval_pattern, $line)) {
        // Check if the line matches the incomplete interval pattern.
        $incomplete_interval_pattern = '/^(\d{1,2}:\d{2})\|?$/';
        if (preg_match($incomplete_interval_pattern, $line)) {
          $error_message = t('Incomplete Time Interval. Both start and end times are required in the format HH:MM|HH:MM. Example: 08:00|12:00');
        }
        else {
          $error_message = t('Invalid Time Intervals. Enter time intervals in 24h format HH:MM|HH:MM, one interval per line. You can also add exceptions for specific days or weekdays (weekdays in English). Ex: 10:00|12:00(Friday) or 10:00|12:00(2024-10-04)');
        }
        $form_state->setError($element, $error_message);
        break;
      }
    }
  }

  /**
   * Validate exclusion dates.
   */
  public static function validateExclusionDates(array &$element, FormStateInterface $form_state) {
    $exclusion_dates = $form_state->getValue(['properties', 'exclusion_dates']);

    // Ensure $exclusion_dates is a string.
    if (is_array($exclusion_dates)) {
      $exclusion_dates = reset($exclusion_dates);
    }

    $date_pattern = '/^(\d{4}-\d{2}-\d{2})(\|\d{4}-\d{2}-\d{2})?$/';
    $lines = preg_split('/\r\n|\r|\n/', $exclusion_dates);
    foreach ($lines as $line) {
      if (!empty($line) && !preg_match($date_pattern, $line)) {
        $example_dates = self::getExampleDates();
        $error_message = t('Invalid Exclusion Dates. Enter dates in format YYYY-MM-DD or intervals in format YYYY-MM-DD|YYYY-MM-DD, one per line.<br>Ex.<br>@example_date_single<br>@example_date_multi', [
          '@example_date_single' => $example_dates['single'],
          '@example_date_multi' => $example_dates['multi'],
        ]);
        $form_state->setError($element, $error_message);
        break;
      }
    }
  }

  /**
   * Validate excluded time periods.
   */
  public static function validateExcludedTimePeriods(array &$element, FormStateInterface $form_state) {
    $excluded_time_periods = $form_state->getValue($element['#parents']);

    $time_period_pattern = '/^(\d{1,2}:\d{2})\|(\d{1,2}:\d{2})(\((Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday|\d{4}-\d{2}-\d{2})\))?$/';
    $lines = preg_split('/\r\n|\r|\n/', $excluded_time_periods);

    foreach ($lines as $line) {
      $line = trim($line);
      if (!empty($line) && !preg_match($time_period_pattern, $line)) {
        $form_state->setError($element, t('Invalid Excluded Time Periods. Enter time periods in 24h format HH:MM|HH:MM, one per line. You can also add exceptions for specific days (weekdays in English) or dates (YYYY-MM-DD).<br>Ex.<br>12:00|13:00<br>15:30|16:30(Friday)<br>14:00|15:00(2024-10-04)'));
        break;
      }
    }
  }

  /**
   * Validate default price when PayPal is enabled.
   */
  public static function validateDefaultPrice(array &$element, FormStateInterface $form_state) {
    $paypal_enabled = $form_state->getValue(['properties', 'paypal_enabled']);
    $default_price = $form_state->getValue(['properties', 'default_price']);

    if ($paypal_enabled && (empty($default_price) || !is_numeric($default_price))) {
      $form_state->setError($element, t('Default Price per Seat is required when PayPal integration is enabled.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL): array {
    // Ensure the library is attached only once.
    $element['#attached']['library'][] = 'webform_booking/webform_booking';
    $user = \Drupal::currentUser();

    $elementId = $element['#webform_key'];

    // Set the element name if not already set.
    if (!isset($element['#name'])) {
      $element['#name'] = $element['#webform_key'];
    }

    // Check if start date is after end date.
    $start_date = $element['#start_date'] ?? '';
    $end_date = $element['#end_date'] ?? '';

    if (!empty($start_date) && !empty($end_date)) {
      $start = strtotime($start_date);
      $end = strtotime($end_date);

      if ($start && $end && $start > $end) {
        $element['#description'] = '<div class="webform-booking-error messages messages--error">' .
          $this->t('Configuration error: Start date cannot be after end date.') . '</div>';
        return $element;
      }
    }

    if (!isset($element['#attached']['drupalSettings']['webform_booking']['elements'])) {
      $element['#attached']['drupalSettings']['webform_booking']['elements'] = [];
    }

    // Use element ID as a key to ensure unique settings for each element.
    $config = $this->configFactory->get('webform_booking.settings');
    $default_price = $element['#default_price'] ?? $this->configuration['default_price'] ?? 0;
    $default_country = $config->get('default_country') ?? 'GB';

    // Convert dates for US format if needed.
    $start_date = $element['#start_date'] ?? '';
    $end_date = $element['#end_date'] ?? '';

    if ($default_country === 'US' && !empty($start_date)) {
      $start_date = $this->convertDateFormat($start_date);
    }
    if ($default_country === 'US' && !empty($end_date)) {
      $end_date = $this->convertDateFormat($end_date);
    }
    $element['#attached']['drupalSettings']['webform_booking']['elements'][$elementId] = [
      'formId' => $element['#webform'],
      'elementId' => $elementId,
      'startDate' => $start_date,
      'endDate' => $end_date,
      'noSlots' => $element['#no_slots'] ?? NULL,
      'paypalEnabled' => $element['#paypal_enabled'] ?? FALSE,
      'defaultPrice' => is_numeric($default_price) ? (float) $default_price : 0,
      'currency' => $config->get('currency') ?? 'GBP',
      'maxSeatsPerBooking' => $element['#max_seats_per_booking'] ?? 1,
      'dateLabel' => !empty($element['#date_label']) ? $element['#date_label'] : '',
      'slotLabel' => !empty($element['#slot_label']) ? $element['#slot_label'] : '',
      'seatsLabel' => !empty($element['#seats_label']) ? $element['#seats_label'] : '',
    ];

    $element['#attached']['drupalSettings']['webform_booking']['paypalClientId'] = $config->get('paypal_client_id');
    $element['#attached']['drupalSettings']['webform_booking']['paypalEnvironment'] = $config->get('paypal_environment');
    $element['#attached']['drupalSettings']['webform_booking']['currency'] = $config->get('currency') ?? '';
    $element['#attached']['drupalSettings']['webform_booking']['defaultCountry'] = $default_country;

    $element['#tree'] = TRUE;
    if (!isset($element['#parents'])) {
      $element['#parents'] = [$element['#webform_key']];
    }

    // Get the default value from the submission if available.
    $default_value = $element['#default_value'] ?? '';
    $default_seats = '1';

    if ($webform_submission) {
      $value = $webform_submission->getElementData($element['#webform_key']);
      if (!empty($value) && is_string($value)) {
        $parts = explode('|', $value);
        if (count($parts) >= 2) {
          $default_seats = $parts[1];
        }
      }
    }

    $element['#attributes']['value'] = $default_value;

    $element['slot'] = [
      '#attributes' => ['id' => 'selected-slot-' . $elementId],
      '#type' => 'textfield',
    ];
    if (!$user->hasPermission('view webform booking input')) {
      $element['slot']['#type'] = 'hidden';
    }

    $element['seats'] = [
      '#type' => 'hidden',
      '#attributes' => ['id' => 'seats-' . $elementId],
      '#parents' => array_merge($element['#parents'], ['seats']),
      '#default_value' => $default_seats,
    ];

    $user = \Drupal::currentUser();
    if (!$user->hasPermission('edit any webform submission')) {
      $element['#attributes']['class'][] = 'hide';
    }
    // Add custom validation for empty slots.
    $element['#element_validate'][] = [get_class($this), 'validateEmptySlot'];

    return $element;
  }

  /**
   * Convert date from MM-DD-YYYY to DD-MM-YYYY format.
   *
   * @param string $date
   *   The date string to convert in MM-DD-YYYY format.
   *
   * @return string
   *   The converted date string in DD-MM-YYYY format.
   */
  protected function convertDateFormat($date) {
    if (empty($date)) {
      return '';
    }

    try {
      $parts = explode('-', $date);
      if (count($parts) !== 3) {
        return $date;
      }

      // US format is MM-DD-YYYY, so we rearrange to DD-MM-YYYY.
      $month = $parts[0];
      $day = $parts[1];
      $year = $parts[2];

      if (!checkdate((int) $month, (int) $day, (int) $year)) {
        return $date;
      }

      return sprintf('%02d-%02d-%04d', (int) $day, (int) $month, (int) $year);
    }
    catch (\Exception $e) {
      return $date;
    }
  }

  /**
   * Add a highlight class to the description if a required field is empty.
   *
   * @param array $element
   *   Element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateEmptySlot(array &$element, FormStateInterface $form_state) {
    if (!empty($element['#required'])) {
      if ($value = $form_state->getValue($element['#parents'])) {
        [$slot, $seats] = array_values($value);
      }

      if (empty($slot) || empty($seats)) {
        $elementId = $element['#webform_key'];
        $errorMessage = !empty($element['#required_error'])
          ? strval($element['#required_error'])
          : t('@field_title field is required.', [
            '@field_title' => $element['#title'],
          ]);
        $form_state->setErrorByName($element['#name'], $errorMessage);
        $element['#attached']['drupalSettings']['webform_booking']['elements'][$elementId]['hasError'] = $errorMessage;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission, $update = FALSE) {
    $key = $element['#webform_key'];
    $value = $webform_submission->getElementData($key);

    if (is_array($value)) {
      if (!empty($value['seats']) && !empty($value['slot'])) {
        $stringValue = $value['slot'];
        $seats = $value['seats'] ?? '1';
        $stringValue .= '|' . $seats;
        $webform_submission->setElementData($key, $stringValue);
      }
      else {
        $webform_submission->setElementData($key, '');
      }
    }
    elseif (is_string($value)) {
      $parts = explode('|', $value);
      if (count($parts) < 2) {
        $value .= '|1';
      }
      $webform_submission->setElementData($key, $value);
    }
    else {
      $webform_submission->setElementData($key, '');
    }
  }

  /**
   * Validate the date range.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateDateRange(array $element, FormStateInterface $form_state) {
    $start_date = $form_state->getValue(['properties', 'start_date']);
    $end_date = $form_state->getValue(['properties', 'end_date']);

    if (!empty($start_date) && !empty($end_date)) {
      $start = strtotime($start_date);
      $end = strtotime($end_date);

      if ($start && $end && $start > $end) {
        $form_state->setError($element['appointment_settings']['date_range']['start_date'], t('Start date cannot be after end date.'));
        $form_state->setError($element['appointment_settings']['date_range']['end_date'], t('End date cannot be before start date.'));
      }
    }
  }

}
