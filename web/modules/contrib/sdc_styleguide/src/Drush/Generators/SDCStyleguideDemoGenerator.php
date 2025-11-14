<?php

namespace Drupal\sdc_styleguide\Drush\Generators;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\ComponentPluginManager;
use DrupalCodeGenerator\Asset\AssetCollection;
use DrupalCodeGenerator\Attribute\Generator;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\GeneratorType;
use DrupalCodeGenerator\InputOutput\Interviewer;
use Drush\Commands\AutowireTrait;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Generates a custom demo for one of the available SDCs on site.
 */
#[Generator(
  name: 'sdc_styleguide:demo',
  description: 'Generates a SDC Styleguide demo',
  aliases: ['sdcs-demo'],
  templatePath: __DIR__,
  type: GeneratorType::MODULE_COMPONENT,
)]
class SDCStyleguideDemoGenerator extends BaseGenerator {

  use AutowireTrait;
  use StringTranslationTrait;

  private array $groupedComponents;
  private array $components;

  private Interviewer $interviewer;

  /**
   * Constructs a new SDCStyleguideDemoGenerator object.
   */
  public function __construct(
    #[Autowire(service: 'plugin.manager.sdc')]
    protected readonly ComponentPluginManager $componentPluginManager,
    protected readonly FileSystemInterface $fileSystem,
  ) {
    parent::__construct();
  }

  /**
   * Initializes the components maps.
   */
  private function initializeComponentsMaps() : void {
    $default_group = 'Ungrouped';
    $this->components = [];
    $this->groupedComponents = [$default_group => []];

    $definitions = $this->componentPluginManager->getAllComponents();
    foreach ($definitions as $componentDefinition) {
      $definition = $componentDefinition->getPluginDefinition();

      // A group less component goes into the default group.
      $group = $definition['group'] ?? $default_group;
      if (!isset($components[$group])) {
        $this->groupedComponents[$group] = [];
      }

      $component = [
        'id' => $componentDefinition->getPluginId(),
        'name' => $definition['name'],
        'machineName' => $definition['machineName'],
        'path' => $definition['path'],
        'properties' => $definition['props']['properties'] ?? [],
        'required' => $definition['props']['required'] ?? NULL,
        'slots' => $definition['slots'] ?? NULL,
        'type' => $definition['extension_type'],
      ];

      $option = "{$component['name']} ({$component['id']})";
      $this->groupedComponents[$group][$option] = $component;
      $this->components[$component['id']] = $component;
    }
  }

  /**
   * Gets the component id from the user by providing an autocomplete feature.
   *
   * @return string
   *  The identifier of the component providedby the user.
   */
  private function getComponentByAskingId() : string {
    $question = new Question($this->t('Please type the SDC id'), NULL);
    $question->setAutocompleterValues(array_keys($this->components));
    $found = FALSE;
    do {
      $component = $this->io()->askQuestion($question);
      if (!($found = isset($this->components[$component]))) {
        $this->io()->error($this->t('Could not find SDC id: @id', ['@id' => $component]));
      }
    }
    while(!$found);
    return $component;
  }

  /**
   * Gets a component id from the user by providing options for finding it.
   *
   * @return string
   *  The user provided component identifier.
   */
  private function getComponentByAskingGrouping() : string {
    // Displays available groups.
    $options = [];
    foreach ($this->groupedComponents as $group => $items) {
      $options[] = $group . PHP_EOL . "\t" . $this->formatPlural(count($items), '1 item', '@count items');
    }
    sort($options);

    // Gets the group where the component is located.
    $choice = $this->interviewer->choice($this->t('Under which group is the SDC located?'), $options);
    $group = explode(PHP_EOL, $options[$choice]);
    $group = reset($group);

    // Gets the component from the components in the group.
    $options = array_keys($this->groupedComponents[$group]);
    sort($options);
    $choice = $this->interviewer->choice($this->t('For which SDC do you want to create a demo for?'), $options);
    $component = $options[$choice];
    $component = $this->groupedComponents[$group][$component];

    return $component['id'];
  }

  function getScalarValueByAsking($property, $required) {
    $name = $property['title'];
    $value = NULL;
    $default = $property['default'] ?? '';
    $tVars = [
      '@default' => $default,
      '@description' => $property['description'] ?? $this->t('Field description unavailable'),
      '@property' => $name,
    ];

    // For enumerations, just displays the options the user can pick from.
    if (isset($property['enum'])) {
      $message = (string)$this->t('Please select the value for @property', $tVars);
      $chosen = $this->interviewer->choice($message, $property['enum']);
      return $property['enum'][$chosen];
    }

    do {
      $message = $this->t('Please set the value for @property (@description, default value of `@default`).', $tVars);
      $value = $this->interviewer->ask($message, $default, function ($value) use ($name, $required) {
        if ($required && !$value) {
          throw new \Exception('This property is required. Please set a value.');
        }

        // @todo Check for type validation. (bool, number, string).
        return $value ?? '';
      });
    }
    while (empty($value) && $required);

    return $value;
  }

  /**
   * Gets the attribute definition from user input.
   *
   * @param array $property
   *  The property definition.
   *
   * @return array
   *  A map whose keys are the HTML tag attributes and the values are their
   *  corresponding value.
   */
  function getAttributeByAsking(array $property, $required = FALSE) : array {
    $attributes = [];
    $tVars = [
      '@property' => $property['title'],
    ];
    if (!$required) {
      if (!$this->interviewer->confirm($this->t('Would you like to add values for property @property', $tVars), FALSE)) {
        return $attributes;
      }
    }

    // An attribute object and have more than one HTML attributes, so this loops
    // unless the user decides to not continue.
    $alreadyRun = FALSE;
    $continue = TRUE;
    do {
      if ($required && !empty($attributes)) {
        $required = FALSE;
      }
      if ($alreadyRun) {
        $message = $this->t('Would you like to add another attribute for property @property?', $tVars);
        if (!($continue = $this->interviewer->confirm($message, FALSE))) {
          continue;
        }
      }

      $message = $this->t('What is the new attribute name for property @property?',$tVars);
      $name = $this->interviewer->ask($message, NULL, function ($name) use ($attributes) {
        if (!$name) {
          throw new \Exception('Please set an attribute name.');
        }
        if (!preg_match('@^(\w+(\-\w+)?)+$@', $name)) {
          throw new \Exception('Invalid attribute name.');
        }
        if (isset($attributes[$name])) {
          throw new \Exception('Attribute is already defined. Please use another name.');
        }

        return $name;
      });
      $attributes[$name] = $this->interviewer->ask($this->t('What is the attribute value for @attribute?', ['@attribute' => $name]));
      $alreadyRun = TRUE;

      // Shows the current set of values.
      $this->io()->title($this->t('Current values.'));
      $this->io()->write(Yaml::encode($attributes));
    }
    while ($continue || $required);

    return $attributes;
  }

  /**
   * Gets input from the user regarding the component whose demo is going to be
   * generated.
   *
   * @return string
   *  The selected component identifier.
   */
  private function getComponentToBuild() : array {
    $component = $this->interviewer->confirm($this->t('Do you know the id of your SDC? (y/n)'), FALSE) ?
      $this->getComponentByAskingId() :
      $this->getComponentByAskingGrouping();

    return $this->components[$component];
  }

  private function getPropertiesForComponent(array $component) : array {
    $props = [];
    if (!isset($component['properties'])) {
      return $props;
    }

    // Fills property values.
    foreach ($component['properties'] as $name => $property) {
      $required = $component['required'] ?? FALSE;
      $property['description'] = $property['description'] ?? $this->t('Missing field description in component definition');

      // Personally, I don't like SDCs to know about the specific Drupal class
      // for attributes, but the Olivero theme has it, and it is highly likely
      // that because of that people will use it.
      $props[$name] = $property['type'] == Attribute::class ?
        $this->getAttributeByAsking($property, $required) :
        $this->getScalarValueByAsking($property, $required);
    }
    return $props;
  }

  /**
   * @param array $component
   *  The SDC definition.
   *
   * @return array
   *  The demo definition ready to be used.
   */
  private function buildComponentDemo(array $component) : array {
    // Forces a demo name.
    $demoFilename = NULL;
    $demoMachineName = NULL;
    $readName = NULL;
    $this->interviewer->ask($this->t('How would you like to name your demo?'), NULL, function ($name) use ($component, &$demoMachineName, &$demoFilename, &$readName) {
      if (!$name) {
        throw new \Exception('Please set a value.');
      }

      // Confirms a demo with the same name on component folder does not exist.
      $readName = $name;
      $demoMachineName = preg_replace('/[^a-z0-9]/', '_', strtolower($name));
      $demoFilename = "{$component['path']}/{$component['machineName']}.demo.{$demoMachineName}.yml";
      if (file_exists($demoFilename)) {
        throw new \Exception('A demo with that name already exists. Please use a different name.');
      }
    });

    // Prepares demo general structure.
    $demo = [
      'name' => $readName,
      'description' => $this->interviewer->ask($this->t('Please add a description for your demo. (Optional)')) ?? '',
      'props' => $this->getPropertiesForComponent($component),
      'slots' => [],
    ];

    // Fills demos.
    if (isset($component['slots'])) {
      foreach ($component['slots'] as $name => $slot) {
        // @todo Ask the user if they want to use Drupal stuff (Nodes, Media, Views, Another SDC Demo) or if they want
        // to use a free form string.
        $demo['slots'][$name] = $this->interviewer->ask("Please set the {$slot['title']} value. ({$slot['description']})") ?? '';
      }
    }

    return [
      'component' => $component['id'],
      'filename' => $demoFilename,
      'demo' => $demo,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function generate(array &$vars, AssetCollection $assets): void {
    // Initialization. For some reason the interviewer cannot be created at the
    // constructor.
    $this->initializeComponentsMaps();
    $this->interviewer = $this->createInterviewer($vars);

    // Gets the component.
    $component = $this->getComponentToBuild();
    $demo = $this->buildComponentDemo($component);

    // Encode the component and show it to the user.
    $encoded = Yaml::encode($demo['demo']);
    $tVars = [
      '@component' => $demo['component'],
      '@filename' => $demo['filename'],
      '@name' => $demo['demo']['name'],
    ];
    $this->io()->success($this->t('SDC demo for @component created.', $tVars));
    $this->io()->title($this->t('Demo @name for @component.', $tVars));
    $this->io()->write($encoded);

    // Writes file.
    $this->fileSystem->saveData($encoded, $demo['filename'], FileExists::Error);
    $this->io()->success($this->t('SDC demo for @component stored at @filename.', $tVars));
  }

}
