<?php

namespace Drupal\webform_booking_price_element\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_booking_price_element' element.
 *
 * @WebformElement(
 *   id = "webform_booking_price_element",
 *   label = @Translation("Booking Extra Items"),
 *   description = @Translation("Provides a custom element with Title, Price, Quantity, and Quantity Label fields."),
 *   category = @Translation("Booking"),
 * )
 */
class WebformBookingPriceElement extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'title' => '',
      'price' => '',
      'quantity_label' => '',
      'max_units' => 0,
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['webform_booking_price_element'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Webform Booking Price Element settings'),
    ];

    $form['webform_booking_price_element']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
    ];

    $form['webform_booking_price_element']['price'] = [
      '#type' => 'number',
      '#title' => $this->t('Price'),
      '#step' => 0.01,
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['webform_booking_price_element']['quantity_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quantity Label'),
      '#description' => $this->t('Label for the quantity input (e.g., "Quantity").'),
    ];

    $form['webform_booking_price_element']['max_units'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Units'),
      '#description' => $this->t('Maximum number of units a user can purchase. Use 0 for unlimited.'),
      '#min' => 0,
      '#step' => 1,
      '#default_value' => 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $element['#theme'] = 'webform_booking_price_element';
    $element['#attached']['library'][] = 'webform_booking_price_element/webform_booking_price_element';

    // Get the currency from the webform_booking settings.
    $config = \Drupal::config('webform_booking.settings');
    $currency = $config->get('currency') ?? 'USD';
    $element['#currency'] = $currency;
    $element['#currency_symbol'] = $this->getCurrencySymbol($currency);

    // Ensure the quantity is submitted with the form.
    $element['#type'] = 'number';
    $element['#min'] = 0;
    $element['#step'] = 1;
    $element['#default_value'] = 0;

    // Set the maximum value if max_units is specified.
    if (!empty($element['#max_units']) && $element['#max_units'] > 0) {
      $element['#max'] = $element['#max_units'];
      $element['#attributes']['max-units'] = $element['#max_units'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    return [
      '#markup' => $this->t('@quantity', ['@quantity' => $value]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $webform_submission->getElementData($element['#webform_key']);
    return is_numeric($value) ? (int) $value : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return [rand(1, 10)];
  }

  /**
   * Get the currency symbol for a given currency code.
   */
  private function getCurrencySymbol($currency) {
    $symbols = [
      'USD' => '$',
      'EUR' => '€',
      'GBP' => '£',
      'AUD' => 'A$',
      'BRL' => 'R$',
      'CAD' => 'C$',
      'CNY' => '¥',
      'CZK' => 'Kč',
      'DKK' => 'kr',
      'HKD' => 'HK$',
      'HUF' => 'Ft',
      'ILS' => '₪',
      'JPY' => '¥',
      'MYR' => 'RM',
      'MXN' => 'Mex$',
      'TWD' => 'NT$',
      'NZD' => 'NZ$',
      'NOK' => 'kr',
      'PHP' => '₱',
      'PLN' => 'zł',
      'SGD' => 'S$',
      'SEK' => 'kr',
      'CHF' => 'CHF',
      'THB' => '฿',
    ];
    return $symbols[$currency] ?? $currency . ' ';
  }

}
