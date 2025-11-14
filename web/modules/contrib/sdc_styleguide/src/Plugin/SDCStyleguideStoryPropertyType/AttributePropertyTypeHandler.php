<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide\Plugin\SDCStyleguideStoryPropertyType;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\sdc_styleguide\Attribute\SDCStyleguideStoryPropertyType;
use Drupal\sdc_styleguide\SDCStyleguideStoryPropertyTypePluginBase;

/**
 * Plugin implementation of the sdc_styleguide.
 */
#[SDCStyleguideStoryPropertyType(
  id: "sdc_styleguide_attribute",
  handleType: "Drupal\Core\Template\Attribute",
  label: new TranslatableMarkup("SDC Styleguide Story String Property Type"),
  description: new TranslatableMarkup("Handles properties with a type of `Drupal\Core\Template\Attribute`."),
)]
final class AttributePropertyTypeHandler extends SDCStyleguideStoryPropertyTypePluginBase {

  private const VALID_ATTRIBUTE_REGEX = '@^([a-z](-?\w+)*)(="([^"]+)")?$@';

  /**
   * {@inheritDoc}
   */
  public function buildFormElement(array &$formElement, bool $required, array $propertyDefinition) : void {
    $format_description = t('Drupal attributes need to be written in the text area in the format @format, one attribute per line. @examples', [
      '@format' => new FormattableMarkup(' <strong>@format</strong>', [
        '@format' => 'attribute-name="attribute-value"'
      ]),
      '@examples' => new FormattableMarkup('<code><pre>class="@classes"' . PHP_EOL . 'id="@id"</pre></code>', [
        '@classes' => 'my classes go here',
        '@id' => 'my-id-here',
      ]),
    ]);

    $formElement = [
      '#description' => ($sdc_property['description'] ?? '') . PHP_EOL . $format_description,
      '#element_validate' => [[$this, 'validateSubmittedFormElementValue']],
      '#required' => $required,
      '#title' => $propertyDefinition['title'],
      '#type' => 'textarea',
    ];
  }

  /**
   * Validation handler for our form field.
   */
  public function validateSubmittedFormElementValue(array &$element, FormStateInterface $formState) : void {
    $value = trim($element['#value']);
    if (empty($value)) {
      return;
    }

    $values = explode(PHP_EOL, $value);
    foreach ($values as $val) {
      if (!preg_match(self::VALID_ATTRIBUTE_REGEX, trim($val))) {
        $formState->setError($element, t('Invalid value for field @field on value @value.', [
          '@field' => new FormattableMarkup('<strong>@field</strong>', ['@field' => $element['#title']]),
          '@value' => new FormattableMarkup('<strong>@value</strong>', ['@value' => $val]),
        ]));
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function convertSubmittedValueToType(&$convertedValue, $submittedValue) : void {
    $attribute = new Attribute();
    $matches = [];
    if (preg_match_all('/([a-zA-Z0-9-_]+)(="([^"]+)")?/', $submittedValue, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        if (isset($match[3])) {
          if ($match[1] == 'class') {
            $match[3] = explode(' ', $match[3]);
          }
        }
        $attribute->setAttribute($match[1], $match[3] ?? '');
      }
    }
    $convertedValue = $attribute;
  }

  /**
   * {@inheritDoc}
   */
  public function convertTypeToValueForStorage(&$convertedValue, $value) : void {
    $convertedValue = $value->toArray();
  }

  /**
   * {@inheritDoc}
   */
  public function convertStoredValueToType(&$convertedValue, $storedValue, array $propertyDefinition) : void {
    $convertedValue = new Attribute();
    if (!is_array($storedValue)) {
      throw new \Exception('Stored value is not an array for the Attribute type. Please ensure the value is properly set.');
    }
    foreach ($storedValue as $attrName => $attrVal) {
      $convertedValue->setAttribute($attrName, $attrVal);
    }
  }
}
