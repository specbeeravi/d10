<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for SDC Styleguide Story Property Type Handlers.
 */
interface SDCStyleguideStoryPropertyTypePluginInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * Returns the type of element that the class will handle.
   *
   * @return string
   *   The property type supported by SDC.
   */
  public function getHandledType(): string;

  /**
   * A FAPI array that the SDC Styleguide Demo Form can use to get data.
   *
   * @param array $element
   *   The FAPI representation of the input fields.
   *
   * @param bool $required
   *   A value indicating if the field is required or not.
   *
   * @param array $propertyDefinition
   *   The SDC property definition.
   *
   * @return void
   */
  public function buildFormElement(array &$formElement, bool $required, array $propertyDefinition) : void;

  /**
   * Converts the submitted value from the FAPI into something the SDC can use.
   *
   * @param &$convertedValue
   *   The converted value with the right that that can be used by the SDC.
   *
   * @param $submittedValue
   *   The value as received from the FAPI.
   *
   * @return void
   */
  public function convertSubmittedValueToType(&$convertedValue, $submittedValue) : void;

  /**
   * Converts the value of a specific type to something that can be stored.
   *
   * @param &$convertedValue
   *   The converted value to a storable format.
   *
   * @param $value
   *   The value of type that SDC has on the property definition.
   *
   * @return void
   */
  public function convertTypeToValueForStorage(&$convertedValue, $value) : void;

  /**
   * Converts the stored value to the value needed by the SDC for rendering.
   *
   * @param $convertedValue
   *   The converted value as the SDC definition requires.
   *
   * @param $storedValue
   *   The value obtained from storage. This is usually the resulting value from
   *   the @see ::convertTypeToValueForStorage.
   *
   * @param array $propertyDefinition
   *   The property definition from the SDC.
   *
   * @return void
   */
  public function convertStoredValueToType(&$convertedValue, $storedValue, array $propertyDefinition) : void;

}
