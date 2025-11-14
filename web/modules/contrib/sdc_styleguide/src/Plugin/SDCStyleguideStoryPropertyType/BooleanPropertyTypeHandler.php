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
  id: "sdc_styleguide_boolean",
  handleType: "boolean",
  label: new TranslatableMarkup("SDC Styleguide Story Booelan property type handler"),
  description: new TranslatableMarkup("Handles properties with a type of `boolean`."),
)]
final class BooleanPropertyTypeHandler extends SDCStyleguideStoryPropertyTypePluginBase {

  /**
   * {@inheritDoc}
   */
  public function buildFormElement(array &$formElement, bool $required, array $propertyDefinition) : void {
    $formElement = [
      '#default_value' => $propertyDefinition['default'] ?? NULL,
      '#description' => $propertyDefinition['description'] ?? NULL,
      '#required' => $required,
      '#title' => $propertyDefinition['title'],
      '#type' => 'checkbox',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function convertSubmittedValueToType(&$convertedValue, $submittedValue) : void {
    $convertedValue = (bool)$submittedValue;
  }

  /**
   * {@inheritDoc}
   */
  public function convertTypeToValueForStorage(&$convertedValue, $value) : void {
    $convertedValue = (bool)$value;
  }

  /**
   * {@inheritDoc}
   */
  public function convertStoredValueToType(&$convertedValue, $storedValue, array $propertyDefinition) : void {
    $convertedValue = (bool)$storedValue;
  }
}
