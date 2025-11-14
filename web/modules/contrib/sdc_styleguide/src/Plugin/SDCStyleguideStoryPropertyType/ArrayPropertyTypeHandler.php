<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide\Plugin\SDCStyleguideStoryPropertyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sdc_styleguide\Attribute\SDCStyleguideStoryPropertyType;
use Drupal\sdc_styleguide\SDCStyleguideStoryPropertyTypePluginBase;

/**
 * Plugin implementation of the sdc_styleguide.
 */
#[SDCStyleguideStoryPropertyType(
  id: "sdc_styleguide_array",
  handleType: "array",
  label: new TranslatableMarkup("SDC Styleguide Story Array property type handler"),
  description: new TranslatableMarkup("Handles properties with a type of `array`."),
)]
final class ArrayPropertyTypeHandler extends SDCStyleguideStoryPropertyTypePluginBase {

  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public function buildFormElement(array &$formElement, bool $required, array $propertyDefinition) : void {
    $formElement = [
      '#default_value' => $propertyDefinition['default'] ?? '',
      '#description' => ($propertyDefinition['description'] ?? NULL)  . $this->t('One item per line'),
      '#required' => $required,
      '#title' => $propertyDefinition['title'],
      '#type' => 'textarea',
    ];

    // If it is an enum, changes to select.
    if (isset($propertyDefinition['items']['enum'])) {
      $enum = $propertyDefinition['items']['enum'];
      $formElement['#type'] = 'checkboxes';
      $formElement['#options'] = array_combine($enum, $enum);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function convertSubmittedValueToType(&$convertedValue, $submittedValue) : void {
    if (empty($submittedValue)) {
      $submittedValue = [];
    }

    if (!is_array($submittedValue)) {
      $submittedValue = explode(PHP_EOL, trim($submittedValue));
      $submittedValue = array_map(fn ($x) => trim($x), $submittedValue);
    }

    // Removes any number 0 values (FAPI for checkboxes sends us 0 when an
    // option is not checked.
    $submittedValue = array_filter($submittedValue, fn ($x) => $x !== 0);
    $convertedValue = array_values($submittedValue);
  }

  /**
   * {@inheritDoc}
   */
  public function convertTypeToValueForStorage(&$convertedValue, $value) : void {
    $convertedValue = $value;
  }

  /**
   * {@inheritDoc}
   */
  public function convertStoredValueToType(&$convertedValue, $storedValue, array $propertyDefinition) : void {
    $convertedValue = $storedValue;
  }
}
