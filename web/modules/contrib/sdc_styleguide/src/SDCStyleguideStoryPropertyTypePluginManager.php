<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\sdc_styleguide\Attribute\SDCStyleguideStoryPropertyType;
use Drupal\sdc_styleguide\SDCStyleguideStoryPropertyTypePluginInterface;

/**
 * SDCStyleguideStoryPropertyType plugin manager.
 */
final class SDCStyleguideStoryPropertyTypePluginManager extends DefaultPluginManager {

  private $instancesByType = [];

  /**
   * Constructs a new SDCStyleguideStoryPropertyTypePluginManager object.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/SDCStyleguideStoryPropertyType',
      $namespaces,
      $module_handler,
      SDCStyleguideStoryPropertyTypePluginInterface::class,
      SDCStyleguideStoryPropertyType::class
    );
    $this->alterInfo('sdc_styleguide_story_property_type_info');
    $this->setCacheBackend($cache_backend, 'sdc_styleguide_story_property_type_plugins');

    foreach ($this->getDefinitions() as $definition) {
      $type = $definition['handleType'];
      if (!isset($this->instancesByType[$type])) {
        $this->instancesByType[$type] = [];
      }
      $this->instancesByType[$type][] = $this->createInstance($definition['id']);
    }
  }

  /**
   * Gets the plugins that handle a specific type.
   *
   * @param string $type
   *   The identifier of the handled type to filter plugins by.
   *
   * @return SDCStyleguideStoryPropertyTypePluginInterface[]|null
   *   An array of plugins that handle the type or null if none found.
   */
  private function getPluginsByHandledType(string $type) {
    return $this->instancesByType[$type] ?? NULL;
  }

  /**
   * Calls a plugin method in all the plugins that handle the specific type.
   *
   * @param string $type
   *   The property type the plugins need to handle.
   *
   * @param string $method
   *   The method name to call in the plugin.
   *
   * @param $arguments
   *   The arguments to pass to the method being called.
   *
   * @return void
   */
  private function callPluginMethodByHandledType(string $type, string $method, $arguments) {
    if (empty($plugins = $this->getPluginsByHandledType($type))) {
      return;
    }
    foreach ($plugins as $plugin) {
      $plugin->$method(...$arguments);
    }
  }

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
  public function buildFormElement(array &$formElement, bool $required, array $propertyDefinition) {
    $this->callPluginMethodByHandledType(
      $propertyDefinition['type'],
      'buildFormElement',
      [&$formElement, $required, $propertyDefinition]
    );
  }

  /**
   * Converts the submitted value from the FAPI into something the SDC can use.
   *
   * @param &$convertedValue
   *   The converted value with the right that that can be used by the SDC.
   *
   * @param $submittedValue
   *   The value as received from the FAPI.
   *
   * @param array $propertyDefinition
   *   The property definition from the SDC.
   *
   * @return void
   */
  public function convertSubmittedValueToType(&$convertedValue, $submittedValue, array $propertyDefinition) {
    $this->callPluginMethodByHandledType(
      $propertyDefinition['type'],
      'convertSubmittedValueToType',
      [&$convertedValue, $submittedValue]
    );
  }

  /**
   * Converts the value of a specific type to something that can be stored.
   *
   * @param &$convertedValue
   *   The converted value to a storable format.
   *
   * @param $value
   *   The value of type that SDC has on the property definition.
   *
   * @param array $propertyDefinition
   *   The property definition from the SDC.
   *
   * @return void
   */
  public function convertTypeToValueForStorage(&$convertedValue, $value, array $propertyDefinition) : void {
    $this->callPluginMethodByHandledType(
      $propertyDefinition['type'],
      'convertTypeToValueForStorage',
      [&$convertedValue, $value]
    );
  }

  /**
   * Converts the stored value to the value needed by the SDC for rendering.
   *
   * @param &$convertedValue
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
  public function convertStoredValueToType(&$convertedValue, $storedValue, array $propertyDefinition) : void {
    $this->callPluginMethodByHandledType(
      $propertyDefinition['type'],
      'convertStoredValueToType',
      [&$convertedValue, $storedValue, $propertyDefinition]
    );
  }

}
