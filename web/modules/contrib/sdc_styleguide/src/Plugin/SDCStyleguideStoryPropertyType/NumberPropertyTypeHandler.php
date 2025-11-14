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
  id: "sdc_styleguide_number",
  handleType: "number",
  label: new TranslatableMarkup("SDC Styleguide Story Number property type handler"),
  description: new TranslatableMarkup("Handles properties with a type of `number`."),
)]
final class NumberPropertyTypeHandler extends SDCStyleguideStoryPropertyTypePluginBase {

  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public function buildFormElement(array &$formElement, bool $required, array $propertyDefinition) : void {
    $formElement = [
      '#default_value' => $propertyDefinition['default'] ?? NULL,
      '#description' => $propertyDefinition['description'] ?? NULL,
      '#element_validate' => [[$this, 'validateSubmittedFormElementValue']],
      '#required' => $required,
      '#title' => $propertyDefinition['title'],
      '#type' => 'number',
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
  public function validateSubmittedFormElementValue(array &$element, FormStateInterface $formState, $value) : void {
    $value = trim($element['#value']);
    if (empty($value)) {
      return;
    }

    $int = filter_var($value, FILTER_VALIDATE_INT);
    $numeric = is_numeric($value);
    if (!$int && !$numeric) {
      $formState->setError($element, $this->t('Value is not a number'));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function convertSubmittedValueToType(&$convertedValue, $submittedValue) : void {
    $convertedValue = filter_var($submittedValue, FILTER_VALIDATE_INT) ?: (double)$submittedValue;
  }

  /**
   * {@inheritDoc}
   */
  public function convertTypeToValueForStorage(&$convertedValue, $value) : void {
    $convertedValue = filter_var($value, FILTER_VALIDATE_INT) ?: (double)$value;
  }

  /**
   * {@inheritDoc}
   */
  public function convertStoredValueToType(&$convertedValue, $storedValue, array $propertyDefinition) : void {
    $int = filter_var($storedValue, FILTER_VALIDATE_INT);
    $convertedValue = $int != FALSE ? $int : (double)$storedValue;
  }
}
