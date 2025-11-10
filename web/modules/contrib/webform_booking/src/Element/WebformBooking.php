<?php

namespace Drupal\webform_booking\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElementBase;

/**
 * Provides a custom form element for booking.
 *
 * @FormElement("webform_booking")
 */
class WebformBooking extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#size' => 60,
      '#element_validate' => [
        [$class, 'validateWebformBooking'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#attributes' => [
        'type' => 'text',
        'class' => ['form-text', 'webform-booking'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformBookingElement'],
      ],
      '#process' => [
        [$class, 'processWebformBooking'],
        [$class, 'processAjaxForm'],
      ],
      '#theme' => 'input__webform_booking',
      '#tree' => TRUE,
    ];
  }

  /**
   * Process callback for the webform booking element.
   */
  public static function processWebformBooking(&$element, FormStateInterface $form_state, &$complete_form) {
    // Ensure proper structure for the element.
    $element['#tree'] = TRUE;

    // Set the element ID for the template.
    $element['#element_id'] = $element['#webform_key'];

    // Set up the seats input.
    $element['seats'] = [
      '#type' => 'hidden',
      '#default_value' => 1,
    ];

    return $element;
  }

  /**
   * Pre-render callback for the webform booking element.
   */
  public static function preRenderWebformBookingElement($element) {
    // Pass necessary variables to template.
    $element['#element_id'] = $element['#webform_key'];
    return $element;
  }

  /**
   * Validation for the element.
   */
  public static function validateWebformBooking(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['slot']['#value'] ?? '';
    if (!empty($value)) {
      $dateTime = \DateTime::createFromFormat('Y-m-d H:i', $value);
      if ($dateTime === FALSE || $dateTime->format('Y-m-d H:i') !== $value) {
        $form_state->setError($element, t('The provided value must be in the format YYYY-MM-DD HH:MM, for example, 2024-03-12 14:30.'));
      }
    }
  }

}
