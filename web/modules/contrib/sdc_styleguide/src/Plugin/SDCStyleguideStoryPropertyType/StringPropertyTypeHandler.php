<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide\Plugin\SDCStyleguideStoryPropertyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sdc_styleguide\Attribute\SDCStyleguideStoryPropertyType;
use Drupal\sdc_styleguide\SDCStyleguideStoryPropertyTypePluginBase;

/**
 * Plugin implementation of the sdc_styleguide.
 */
#[SDCStyleguideStoryPropertyType(
  id: "sdc_styleguide_string",
  handleType: "string",
  label: new TranslatableMarkup("SDC Styleguide Story String property type handler"),
  description: new TranslatableMarkup("Handles properties with a type of `string`."),
)]
final class StringPropertyTypeHandler extends SDCStyleguideStoryPropertyTypePluginBase {

  /**
   * {@inheritDoc}
   */
  public function buildFormElement(array &$formElement, bool $required, array $propertyDefinition) : void {
    $formElement = [
      '#default_value' => $propertyDefinition['default'] ?? NULL,
      '#description' => $propertyDefinition['description'] ?? NULL,
      '#required' => $required,
      '#title' => $propertyDefinition['title'],
      '#type' => 'textfield',
    ];

    // If it is an enum, changes to select.
    if (isset($propertyDefinition['enum'])) {
      $formElement['#type'] = 'select';
      $formElement['#options'] = ['' => '- Select -'] + array_combine($propertyDefinition['enum'], $propertyDefinition['enum']);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function convertSubmittedValueToType(&$convertedValue, $submittedValue) : void {
    $convertedValue = (string)$submittedValue;
  }

  /**
   * {@inheritDoc}
   */
  public function convertTypeToValueForStorage(&$convertedValue, $value) : void {
    $convertedValue = (string)$value;
  }

  /**
   * {@inheritDoc}
   */
  public function convertStoredValueToType(&$convertedValue, $storedValue, array $propertyDefinition) : void {
    $convertedValue = (string)$storedValue;
  }
}
