<?php declare(strict_types = 1);

namespace Drupal\webform_booking\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * @todo Add a description for the form.
 */
final class CancelBookingConfirmForm extends ConfirmFormBase {

  /**
   * The webform object.
   *
   * @var Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The webform submission object.
   *
   * @var Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;


  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'webform_booking_cancel_booking_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to cancel this booking?');
  }

   /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Cancel booking');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Do not cancel booking');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return $this->destinationUrl();
  }

    /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the webform and webform submission parameters.
    $request = $this->getRequest();
    $webform = $request->attributes->get('webform');
    $webform_submission = $request->attributes->get('webform_submission');
    if ($webform instanceof WebformInterface && $webform_submission instanceof WebformSubmissionInterface) {
      $this->webform = $webform;
      $this->webformSubmission = $webform_submission;
    }
    else {
      $this->messenger()->addError($this->t('Error! Can not cancel this booking.'));
      return [];
    }

    // Get date, time and slots for each booking found on the webform.
    $bookings = [];
    $submission_data = $this->webformSubmission->getData();
    foreach($this->getWebformBookingFields() as $field_name => $field_properties) {
      $element_value = $submission_data[$field_name];
      $slot_data = explode('|', $element_value);
      $slot_count = empty($slot_data[1]) ? 0 : $slot_data[1];
      $slot_datetime = new DrupalDateTime($slot_data[0]);
      // Display only bookings that are not cancelled and have a valid date.
      if ($slot_count > 0 && !$slot_datetime->hasErrors()) {
        $bookings[$field_name] = $this->t('@booked_slots slot(s) on @booked_date_time', [
          "@booked_date_time" => _webform_booking_format_date($slot_datetime, 'long'),
          "@booked_slots" => $slot_count,
        ]);
      }
    }

    $form = parent::buildForm($form, $form_state);

    if (!empty($bookings)) {
      $title = count($bookings) > 1
        ? 'Select bookings to cancel'
        : 'Select booking to cancel';

      $description = count($bookings) > 1
        ? 'Check all bookings you want to cancel.'
        : 'Check the booking you want to cancel.';

      // A checkbox for each booking available for cancellation.
      $form['bookings_to_cancel'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t($title),
        '#options' => $bookings,
        '#description' => $this->t($description),
      ];
    }
    else {
      $form['#title'] = $this->t('No bookings to cancel');
      unset($form['actions']['submit']);
      $form['no_bookings_message'] = [
        '#type' => 'markup',
        '#markup' => '<p>This submission does not contain any bookings that can be cancelled.</p>',
      ];

      $form['actions']['cancel']['#title'] = $this->t('Return to booking form');
    }

    return $form;
  }

  /**
   * Construct target url. Either the form or front page when form is closed.
   *
   * @return \Drupal\Core\Url
   *   Webform if it's available, otherwise front page.
   */
  private function destinationUrl() : Url {
    return $this->webform && $this->webform->isOpen()
      ? $this->webform->toUrl()
      : Url::fromRoute('<front>');
  }

  /**
   * Retrieve all webform booking fields on this webform.
   *
   * @return array
   *   Array of webform booking field properties.
   */
  private function getWebformBookingFields() {
    $fields = [];
    foreach($this->webform->getElementsInitialized() as $field_name => $field_properties) {
      if (isset($field_properties['#type']) && $field_properties['#type'] === 'webform_booking') {
        $fields[$field_name] = $field_properties;
      }
    }
    return $fields;
  }

  /**
   * Cancel all selected webform bookings.
   *
   * Submissions are marked as cancelled when they have a date and time but
   * 0 slots. E. g.: "2025-01-28 15:00|0".
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try {
      // Iterate bookings and cancel the ones selected in the form.
      $num_cancelled = 0;
      foreach ($form_state->getValue('bookings_to_cancel') as $element_key) {
        $element_value = $this->webformSubmission->getElementData($element_key);
        if ($element_value) {
          // Validate format of booking element value.
          $pattern = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}\|\d+$/';
          if (preg_match($pattern, $element_value) !== 1) {
            throw new \Exception("The booking information is invalid.");
          }
          // Set number of slots to 0 to mark as cancelled.
          $slot_datetime = explode('|', $element_value)[0];
          $element_value = $this->webformSubmission->setElementData(
            $element_key,
            $slot_datetime . "|0"
          );
          $num_cancelled++;
        }
      }
      if ($num_cancelled > 0) {
        $this->webformSubmission->save();
        $this->messenger()->addStatus($this->t('@num_cancelled booking(s) cancelled successfully.', [
          '@num_cancelled' => $num_cancelled,
        ]));
      }
      else {
        throw new \Exception("Select at least one booking you want to cancel.");
      }

      $form_state->setRedirectUrl($this->destinationUrl());
    }
    catch(\Exception $e) {
      $this->messenger()->addError($this->t(
        'Error: Could not cancel the booking(s). @error_message', [
          '@error_message' => $e->getMessage(),
        ]
      ));

      // On error redirect back to the cancel form.
      $destination = Url::fromUserInput($this->getRequest()->getPathInfo());
      $destination->setOption('query', [
        'token' => $this->getRequest()->query->get('token'),
      ]);
    }

  }

}
