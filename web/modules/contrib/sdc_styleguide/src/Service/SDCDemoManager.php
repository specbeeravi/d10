<?php

namespace Drupal\sdc_styleguide\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\sdc_styleguide\SDCStyleguideStoryPropertyTypePluginManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class to manage demos.
 */
class SDCDemoManager {
  use StringTranslationTrait;
  use MessengerTrait;

  private static $supportedTypes = [
    'boolean',
    'number',
    'string',
    'array',
    'object',
  ];

  /**
   * The list of demos.
   *
   * The array contains two indexes:
   *   - index: All demos accessible by name.
   *   - groups: All demos accessible by SDC group.
   *
   * @var array
   */
  private $demos = [];

  /**
   * Constructs a new SDCDemoManager object.
   */
  public function __construct(
    private readonly string $appRoot,
    private readonly ComponentPluginManager $pluginManagerSdc,
    private readonly SDCStyleguideStoryPropertyTypePluginManager $propertyTypeManager,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly ThemeManagerInterface $themeManager,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Gets a demo by id.
   *
   * @param string $id
   *   The id of the demo.
   *
   * @return mixed|null
   *   The demo with the matching id or NULl if not found.
   */
  public function getDemoById($id) {
    if (empty($this->demos)) {
      $this->getDemos();
    }
    return $this->demos['index'][$id] ?? NULL;
  }

  /**
   * Checks if a component should be excluded from the explorer.
   *
   * @param array $definition
   *   The component plugin definition.
   *
   * @return bool
   *   TRUE if the component should be excluded, FALSE otherwise.
   */
  private function isComponentExcluded(array $definition): bool {
    static $excludeModules = NULL;
    static $excludeThemes = NULL;
    static $excludeOthers = NULL;

    if ($excludeModules === NULL) {
      $config = $this->configFactory->get('sdc_styleguide.settings');
      $componentExplorerConfig = $config->get('component_explorer') ?? [];
      $excludeModules = $componentExplorerConfig['exclude_modules'] ?? [];
      $excludeThemes = $componentExplorerConfig['exclude_themes'] ?? [];
      $excludeOthers = $componentExplorerConfig['exclude_others'] ?? [];
    }

    $extensionType = $definition['extension_type']->value ?? NULL;
    $provider = $definition['provider'];

    // Check if the provider is excluded based on extension type.
    switch ($extensionType) {
      case 'module':
        return in_array($provider, $excludeModules);

      case 'theme':
        return in_array($provider, $excludeThemes);

      default:
        return in_array($provider, $excludeOthers);
    }
  }

  private function prepareDemoFromFile(string $componentId, array $componentDefinition, ActiveTheme $activeTheme, SplFileInfo $file) : array {
    $activeThemePath = $activeTheme->getPath();
    $contents = $file->getContents();
    $demoData = Yaml::decode($contents);
    $demoData['component_id'] = $componentId;

    // With the demo loaded, we look for an override on the active theme
    // folder as long as it is not the theme that is providing it. If the
    // override is found, we used that for the demo.
    if ($componentDefinition['provider'] != $activeTheme->getName()) {
      $overrideFileName = str_replace('.yml', '.override.yml', $file->getFilename());
      $componentFolder = explode('/components/', $file->getPath())[1];
      $demoOverridePath = "{$this->appRoot}/{$activeThemePath}/components/{$componentFolder}/{$overrideFileName}";
      if (file_exists($demoOverridePath)) {
        $overrideFile = new SplFileInfo($demoOverridePath, '', $file->getFilename());
        $contents = $overrideFile->getContents();
        $overrideData = Yaml::decode($contents);
        foreach (['props', 'slots'] as $key) {
          if (!isset($overrideData[$key])) {
            continue;
          }
          $demoData[$key] = array_merge($demoData[$key], $overrideData[$key]);
        }
      }
    }

    // Attribute value validation. Required for when we have
    if (!empty($componentDefinition['props']['properties'])) {
      foreach ($componentDefinition['props']['properties'] as $propName => $propertySettings) {
        $convertedValue = NULL;
        $storedValue = $demoData['props'][$propName] ?? NULL;
        $this->propertyTypeManager->convertStoredValueToType($convertedValue, $storedValue, $propertySettings);

        $originalValue = &$demoData['props'][$propName];

        if (!in_array($propertySettings['type'], self::$supportedTypes)) {
          $newValue = NULL;
          $this->moduleHandler->alter('styleguide_demo_convert_stored_value_to_complex_type',$newValue, $originalValue, $propertySettings);
          $this->themeManager->alter('styleguide_demo_convert_stored_value_to_complex_type',$newValue, $originalValue);
          if (empty($newValue) & !empty($originalValue)) {
            $this->messenger()->addWarning($this->t('Cannot read value from demo file for property @prop type @type.', [
              '@prop' => $propertySettings['title'],
              '@type' => $propertySettings['type'],
            ]));
          }

          $demoData['props'][$propName] = $newValue;
          continue;
        }

        if ($propertySettings['type'] == 'string') {
          if (trim($originalValue ?? '') != trim(strip_tags($originalValue ?? ''))) {
            $originalValue = [
              '#type' => '#markup',
              '#markup' => $originalValue,
            ];
          }

          continue;
        }
        else if ($propertySettings['type'] == 'number') {
          $int = filter_var($originalValue, FILTER_VALIDATE_INT);
          $originalValue = $int != FALSE ? $int : (double)$originalValue;
        }
        else if ($propertySettings['type'] == 'array') {
          if (empty($originalValue)) {
            $originalValue = [];
          }
        }

        $demoData['props'][$propName] = $originalValue;
      }
    }

    return $demoData;
  }

  /**
   * Gets a list of all available SDC and their demos if available.
   *
   * @return array
   *   The list of demos grouped by Group and Component.
   */
  public function getDemos() {
    if (!empty($this->demos)) {
      return $this->demos['groups'];
    }

    // Avoids having to regenerate all demos in the same request.
    // @todo Maybe add cache?
    $this->demos = [
      'groups' => [],
      'index' => [],
    ];

    $activeTheme = $this->themeManager->getActiveTheme();
    $activeThemePath = $activeTheme->getPath();

    // Builds demos.
    $componentDemos = &$this->demos['groups'];
    $ungroupedIndex = $this->t('Ungrouped')->render();
    foreach ($this->pluginManagerSdc->getAllComponents() as $component) {
      $definition = $component->getPluginDefinition();

      // Skip excluded components early.
      if ($this->isComponentExcluded($definition)) {
        continue;
      }

      $group = $definition['group'] ?? $ungroupedIndex;
      $componentId = $component->getPluginId();

      if (!isset($componentDemos[$group])) {
        $componentDemos[$group] = [];
      }

      // Adds component.
      $componentDemos[$group][$componentId] = [
        'name' => $definition['name'],
        'demos' => [],
      ];
      $demos = &$componentDemos[$group][$componentId]['demos'];

      // Finds demos for the current component and adds them to the explorer.
      $finder = new Finder();
      $component_name = $definition['machineName'];
      $finder->in($definition['path'])->files()->name("{$component_name}.demo.*.yml");
      foreach ($finder as $file) {
        $key = str_replace(['.yml', "{$component_name}.demo."], '', $file->getFilename());
        $demoData = $this->prepareDemoFromFile($componentId, $definition, $activeTheme, $file);
        $demos[$key] = $demoData;
        $this->demos['index']["{$componentId}.demo.{$key}"] = $demoData;
      }

      // Sorts by component readable name.
      uasort($demos, fn ($a, $b) => strcmp($a['name'], $b['name']));
    }

    foreach ($componentDemos as &$group) {
      uasort($group, fn($a, $b) => strcmp($a['name'], $b['name']));
    }
    ksort($componentDemos);
    return $componentDemos;
  }

}
