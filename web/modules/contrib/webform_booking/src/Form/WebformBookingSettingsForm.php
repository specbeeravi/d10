<?php

namespace Drupal\webform_booking\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Webform booking settings form.
 */
class WebformBookingSettingsForm extends ConfigFormBase {

  /**
   * Get editable config names.
   */
  protected function getEditableConfigNames() {
    return ['webform_booking.settings'];
  }

  /**
   * Get form id.
   */
  public function getFormId() {
    return 'webform_booking_settings_form';
  }

  /**
   * Build form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform_booking.settings');

    $form['paypal_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PayPal Client ID'),
      '#default_value' => $config->get('paypal_client_id'),
    ];

    $form['paypal_environment'] = [
      '#type' => 'select',
      '#title' => $this->t('PayPal Environment'),
      '#options' => [
        'sandbox' => $this->t('Sandbox (Testing)'),
        'live' => $this->t('Live (Production)'),
      ],
      '#default_value' => $config->get('paypal_environment'),
    ];

    $form['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#options' => [
        'AUD' => $this->t('Australian dollar (AUD)'),
        'BRL' => $this->t('Brazilian real (BRL)'),
        'CAD' => $this->t('Canadian dollar (CAD)'),
        'CNY' => $this->t('Chinese Renminbi (CNY)'),
        'CZK' => $this->t('Czech koruna (CZK)'),
        'DKK' => $this->t('Danish krone (DKK)'),
        'EUR' => $this->t('Euro (EUR)'),
        'HKD' => $this->t('Hong Kong dollar (HKD)'),
        'HUF' => $this->t('Hungarian forint (HUF)'),
        'ILS' => $this->t('Israeli new shekel (ILS)'),
        'JPY' => $this->t('Japanese yen (JPY)'),
        'MYR' => $this->t('Malaysian ringgit (MYR)'),
        'MXN' => $this->t('Mexican peso (MXN)'),
        'TWD' => $this->t('New Taiwan dollar (TWD)'),
        'NZD' => $this->t('New Zealand dollar (NZD)'),
        'NOK' => $this->t('Norwegian krone (NOK)'),
        'PHP' => $this->t('Philippine peso (PHP)'),
        'PLN' => $this->t('Polish złoty (PLN)'),
        'GBP' => $this->t('Pound sterling (GBP)'),
        'SGD' => $this->t('Singapore dollar (SGD)'),
        'SEK' => $this->t('Swedish krona (SEK)'),
        'CHF' => $this->t('Swiss franc (CHF)'),
        'THB' => $this->t('Thai baht (THB)'),
        'USD' => $this->t('United States dollar (USD)'),
      ],
      '#default_value' => $config->get('currency') ?? 'USD',
      '#description' => $this->t('Select the currency for PayPal transactions.'),
    ];

    $form['paypal_default_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Country'),
      '#options' => [
        'AL' => $this->t('Albania'),
        'DZ' => $this->t('Algeria'),
        'AD' => $this->t('Andorra'),
        'AO' => $this->t('Angola'),
        'AI' => $this->t('Anguilla'),
        'AG' => $this->t('Antigua & Barbuda'),
        'AR' => $this->t('Argentina'),
        'AM' => $this->t('Armenia'),
        'AW' => $this->t('Aruba'),
        'AU' => $this->t('Australia'),
        'AT' => $this->t('Austria'),
        'AZ' => $this->t('Azerbaijan'),
        'BS' => $this->t('Bahamas'),
        'BH' => $this->t('Bahrain'),
        'BB' => $this->t('Barbados'),
        'BY' => $this->t('Belarus'),
        'BE' => $this->t('Belgium'),
        'BZ' => $this->t('Belize'),
        'BJ' => $this->t('Benin'),
        'BM' => $this->t('Bermuda'),
        'BT' => $this->t('Bhutan'),
        'BO' => $this->t('Bolivia'),
        'BA' => $this->t('Bosnia & Herzegovina'),
        'BW' => $this->t('Botswana'),
        'BR' => $this->t('Brazil'),
        'VG' => $this->t('British Virgin Islands'),
        'BN' => $this->t('Brunei'),
        'BG' => $this->t('Bulgaria'),
        'BF' => $this->t('Burkina Faso'),
        'BI' => $this->t('Burundi'),
        'KH' => $this->t('Cambodia'),
        'CM' => $this->t('Cameroon'),
        'CA' => $this->t('Canada'),
        'CV' => $this->t('Cape Verde'),
        'KY' => $this->t('Cayman Islands'),
        'TD' => $this->t('Chad'),
        'CL' => $this->t('Chile'),
        'CN' => $this->t('China'),
        'XC' => $this->t('China Worldwide'),
        'CO' => $this->t('Colombia'),
        'KM' => $this->t('Comoros'),
        'CG' => $this->t('Congo - Brazzaville'),
        'CD' => $this->t('Congo - Kinshasa'),
        'CK' => $this->t('Cook Islands'),
        'CR' => $this->t('Costa Rica'),
        'CI' => $this->t('Côte d’Ivoire'),
        'HR' => $this->t('Croatia'),
        'CY' => $this->t('Cyprus'),
        'CZ' => $this->t('Czech Republic'),
        'DK' => $this->t('Denmark'),
        'DJ' => $this->t('Djibouti'),
        'DM' => $this->t('Dominica'),
        'DO' => $this->t('Dominican Republic'),
        'EC' => $this->t('Ecuador'),
        'EG' => $this->t('Egypt'),
        'SV' => $this->t('El Salvador'),
        'ER' => $this->t('Eritrea'),
        'EE' => $this->t('Estonia'),
        'ET' => $this->t('Ethiopia'),
        'FK' => $this->t('Falkland Islands'),
        'FO' => $this->t('Faroe Islands'),
        'FJ' => $this->t('Fiji'),
        'FI' => $this->t('Finland'),
        'FR' => $this->t('France'),
        'GF' => $this->t('French Guiana'),
        'PF' => $this->t('French Polynesia'),
        'GA' => $this->t('Gabon'),
        'GM' => $this->t('Gambia'),
        'GE' => $this->t('Georgia'),
        'DE' => $this->t('Germany'),
        'GI' => $this->t('Gibraltar'),
        'GR' => $this->t('Greece'),
        'GL' => $this->t('Greenland'),
        'GD' => $this->t('Grenada'),
        'GP' => $this->t('Guadeloupe'),
        'GT' => $this->t('Guatemala'),
        'GN' => $this->t('Guinea'),
        'GW' => $this->t('Guinea-Bissau'),
        'GY' => $this->t('Guyana'),
        'HN' => $this->t('Honduras'),
        'HK' => $this->t('Hong Kong SAR China'),
        'HU' => $this->t('Hungary'),
        'IS' => $this->t('Iceland'),
        'IN' => $this->t('India'),
        'ID' => $this->t('Indonesia'),
        'IE' => $this->t('Ireland'),
        'IL' => $this->t('Israel'),
        'IT' => $this->t('Italy'),
        'JM' => $this->t('Jamaica'),
        'JP' => $this->t('Japan'),
        'JO' => $this->t('Jordan'),
        'KZ' => $this->t('Kazakhstan'),
        'KE' => $this->t('Kenya'),
        'KI' => $this->t('Kiribati'),
        'KW' => $this->t('Kuwait'),
        'KG' => $this->t('Kyrgyzstan'),
        'LA' => $this->t('Laos'),
        'LV' => $this->t('Latvia'),
        'LS' => $this->t('Lesotho'),
        'LI' => $this->t('Liechtenstein'),
        'LT' => $this->t('Lithuania'),
        'LU' => $this->t('Luxembourg'),
        'MK' => $this->t('Macedonia'),
        'MG' => $this->t('Madagascar'),
        'MW' => $this->t('Malawi'),
        'MY' => $this->t('Malaysia'),
        'MV' => $this->t('Maldives'),
        'ML' => $this->t('Mali'),
        'MT' => $this->t('Malta'),
        'MH' => $this->t('Marshall Islands'),
        'MQ' => $this->t('Martinique'),
        'MR' => $this->t('Mauritania'),
        'MU' => $this->t('Mauritius'),
        'YT' => $this->t('Mayotte'),
        'MX' => $this->t('Mexico'),
        'FM' => $this->t('Micronesia'),
        'MD' => $this->t('Moldova'),
        'MC' => $this->t('Monaco'),
        'MN' => $this->t('Mongolia'),
        'ME' => $this->t('Montenegro'),
        'MS' => $this->t('Montserrat'),
        'MA' => $this->t('Morocco'),
        'MZ' => $this->t('Mozambique'),
        'NA' => $this->t('Namibia'),
        'NR' => $this->t('Nauru'),
        'NP' => $this->t('Nepal'),
        'NL' => $this->t('Netherlands'),
        'NC' => $this->t('New Caledonia'),
        'NZ' => $this->t('New Zealand'),
        'NI' => $this->t('Nicaragua'),
        'NE' => $this->t('Niger'),
        'NG' => $this->t('Nigeria'),
        'NU' => $this->t('Niue'),
        'NF' => $this->t('Norfolk Island'),
        'NO' => $this->t('Norway'),
        'OM' => $this->t('Oman'),
        'PW' => $this->t('Palau'),
        'PA' => $this->t('Panama'),
        'PG' => $this->t('Papua New Guinea'),
        'PY' => $this->t('Paraguay'),
        'PE' => $this->t('Peru'),
        'PH' => $this->t('Philippines'),
        'PN' => $this->t('Pitcairn Islands'),
        'PL' => $this->t('Poland'),
        'PT' => $this->t('Portugal'),
        'QA' => $this->t('Qatar'),
        'RE' => $this->t('Réunion'),
        'RO' => $this->t('Romania'),
        'RU' => $this->t('Russia'),
        'RW' => $this->t('Rwanda'),
        'WS' => $this->t('Samoa'),
        'SM' => $this->t('San Marino'),
        'ST' => $this->t('São Tomé & Príncipe'),
        'SA' => $this->t('Saudi Arabia'),
        'SN' => $this->t('Senegal'),
        'RS' => $this->t('Serbia'),
        'SC' => $this->t('Seychelles'),
        'SL' => $this->t('Sierra Leone'),
        'SG' => $this->t('Singapore'),
        'SK' => $this->t('Slovakia'),
        'SI' => $this->t('Slovenia'),
        'SB' => $this->t('Solomon Islands'),
        'SO' => $this->t('Somalia'),
        'ZA' => $this->t('South Africa'),
        'KR' => $this->t('South Korea'),
        'ES' => $this->t('Spain'),
        'LK' => $this->t('Sri Lanka'),
        'SH' => $this->t('St. Helena'),
        'KN' => $this->t('St. Kitts & Nevis'),
        'LC' => $this->t('St. Lucia'),
        'PM' => $this->t('St. Pierre & Miquelon'),
        'VC' => $this->t('St. Vincent & Grenadines'),
        'SR' => $this->t('Suriname'),
        'SJ' => $this->t('Svalbard & Jan Mayen'),
        'SZ' => $this->t('Swaziland'),
        'SE' => $this->t('Sweden'),
        'CH' => $this->t('Switzerland'),
        'TW' => $this->t('Taiwan'),
        'TJ' => $this->t('Tajikistan'),
        'TZ' => $this->t('Tanzania'),
        'TH' => $this->t('Thailand'),
        'TG' => $this->t('Togo'),
        'TO' => $this->t('Tonga'),
        'TT' => $this->t('Trinidad & Tobago'),
        'TN' => $this->t('Tunisia'),
        'TM' => $this->t('Turkmenistan'),
        'TC' => $this->t('Turks & Caicos Islands'),
        'TV' => $this->t('Tuvalu'),
        'UG' => $this->t('Uganda'),
        'UA' => $this->t('Ukraine'),
        'AE' => $this->t('United Arab Emirates'),
        'GB' => $this->t('United Kingdom'),
        'US' => $this->t('United States'),
        'UY' => $this->t('Uruguay'),
        'VU' => $this->t('Vanuatu'),
        'VA' => $this->t('Vatican City'),
        'VE' => $this->t('Venezuela'),
        'VN' => $this->t('Vietnam'),
        'WF' => $this->t('Wallis & Futuna'),
        'YE' => $this->t('Yemen'),
        'ZM' => $this->t('Zambia'),
        'ZW' => $this->t('Zimbabwe'),
      ],
      '#default_value' => $config->get('default_country') ?? 'GB',
      '#description' => $this->t('Select the default country.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('webform_booking.settings')
      ->set('paypal_client_id', $form_state->getValue('paypal_client_id'))
      ->set('paypal_environment', $form_state->getValue('paypal_environment'))
      ->set('currency', $form_state->getValue('currency'))
      ->set('default_country', $form_state->getValue('paypal_default_country'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
