<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\Core\Theme\ThemeManager;
use Drupal\sdc_styleguide\SDCStyleguideStoryPropertyTypePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Single Directory Components Styleguide form.
 */
final class SDCDemoForm extends FormBase {

  private static $supportedTypes = [
    'boolean',
    'number',
    'string',
    'array',
    'object',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'sdc_styleguide_demo_form';
  }

  /**
   * Constructs a new SDCDemoForm object.
   */
  public function __construct(
    protected ComponentPluginManager $componentPluginManager,
    protected SDCStyleguideStoryPropertyTypePluginManager $propertyTypeManager,
    protected Renderer $renderer,
    protected ModuleHandlerInterface $moduleHandler,
    protected ThemeManager $themeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.sdc'),
      $container->get('plugin.manager.sdc_styleguide_story_property_type'),
      $container->get('renderer'),
      $container->get('module_handler'),
      $container->get('theme.manager'),
    );
  }

  /**
   * Verifies if the given type is supported by the form generator.
   * @param string $type
   *   The type for to validate.
   *
   * @return bool
   *  A value indicating if the type is supported or not.
   */
  private function isASupportedType(string $type) : bool {
    return in_array($type, self::$supportedTypes);
  }

  private function prepareSubmittedComponent($definition, $submittedComponent) {
    $component = [
      '#component' => $submittedComponent['id'],
      '#type' => 'component',
      '#props' => [],
      '#slots' => [],
    ];

    // Converts slots to inline templates.
    foreach (array_keys($submittedComponent['slots'] ?? []) as $slotId) {
      if (empty($submittedComponent['slots'][$slotId])) {
        continue;
      }

      $slot = $submittedComponent['slots'][$slotId];
      $component['#slots'][$slotId] = [
        '#template' => $slot,
        '#type' => 'inline_template',
      ];
    }

    // In order to keep SDCs as agnostic as possible (in a way because we are in
    // the context of Drupal) this module won't directly support types that
    // match Drupal classes. However, if anyone wants to support them, they
    // can do so here.
    foreach ($submittedComponent['fields'] as $propName => $value) {
      $property = $definition['props']['properties'][$propName];
      $convertedValue = NULL;
      $this->propertyTypeManager->convertSubmittedValueToType($convertedValue, $value, $property);
      $component['#props'][$propName] = $convertedValue;
    }

    return $component;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $componentId = NULL): array {
    $found = FALSE;
    $definition = NULL;

    // Finds the definition for the current component.
    foreach ($this->componentPluginManager->getAllComponents() as $component) {
      $definition = $component->getPluginDefinition();
      if ($componentId == $definition['id']) {
        $found = TRUE;
        break;
      }
    }

    // Error message when not found.
    if (!$found) {
      return [
        '#markup' => $this->t('The component @component does not exist.', [
          '@component' => new FormattableMarkup(
            '<strong>@componentId</strong>',
            ['@componentId' => $componentId],
          ),
        ]),
      ];
    }

    // Stores the definition for later usage.
    $form['definition'] = [
      '#type' => 'value',
      '#value' => $definition,
    ];

    // Initial form setup.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['component'] = [
      '#attributes' => [
        'id' => 'component-wrapper',
      ],
      '#tree' => TRUE,
      '#type' => 'container',
      'id' => [
        '#type' => 'value',
        '#value' => $componentId,
      ],
      'fields' => [],
      'slots' => [],
    ];

    // Gets each field based on the property.
    foreach ($definition['props']['properties'] ?? [] as $field => $property) {
      $required = in_array($field, $definition['props']['required'] ?? []);
      $formElement = [];
      $this->propertyTypeManager->buildFormElement($formElement, $required, $property);
      if (empty($formElement)) {
        $this
          ->messenger()
          ->addWarning(t('Cannot generate form element for property @name because no there is no supported definition for type @type.', [
            '@name' => new FormattableMarkup('<strong>@name</strong>', ['@name' => $property['title']]),
            '@type' => new FormattableMarkup('<strong>@type</strong>', ['@type' => $property['type']]),
          ]));
      }
      $form['component']['fields'][$field] = $formElement;
    }

    // All available slots are set as text areas.
    foreach ($definition['slots'] ?? [] as $id => $slot) {
      $form['component']['slots'][$id] = [
        '#description' => $slot['description'],
        '#required' => $slot['required'] ?? FALSE,
        '#title' => $slot['title'],
        '#type' => 'textarea',
      ];
    }

    // Wraps them in fieldsets if they have elements.
    if (!empty($form['component']['fields'])) {
      $form['component']['fields']['#type'] = 'fieldset';
      $form['component']['fields']['#title'] = $this->t('Properties');
    }
    if (!empty($form['component']['slots'])) {
      $form['component']['slots']['#type'] = 'fieldset';
      $form['component']['slots']['#title'] = $this->t('Slots');
    }

    // Submit button with AJAX support.
    $form['submit'] = [
      '#ajax' => [
        'callback' => '::onComponentSubmit',
        'event' => 'click',
        'wrapper' => 'result',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Verifying entry...'),
        ],
      ],
      '#attributes' => [
        'type' => 'button',
      ],
      '#type' => 'button',
      '#value' => 'submit',
    ];

    // Rendered result..
    $form['rendered_result'] = [
      '#attributes' => [
        'id' => 'result',
      ],
      '#type' => 'container',
      '#weight' => -100,
    ];

    // Returns form if no values submitted yet.
    $submittedComponent = $form_state->getValue('component');
    if (empty($submittedComponent)) {
      return $form;
    }

    // Updates rendered result.
    $form['rendered_result']['component'] = $this->prepareSubmittedComponent($definition, $submittedComponent);
    $form['rendered_result']['export_action'] = [
      '#ajax' => [
        'callback' => '::onComponentExport',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Generating export...'),
        ],
      ],
      '#type' => 'submit',
      '#value' => $this->t('Export demo'),
    ];
    return $form;
  }

  /**
   * AJAX handler for when the component values are submitted.
   */
  public function onComponentSubmit(array &$form, FormStateInterface $form_state) {
    return $form['rendered_result'];
  }

  /**
   * AJAX handler to generate the component export.
   */
  public function onComponentExport(array &$form, FormStateInterface $form_state) {
    // Component id used in the file name format.
    $component = $form_state->getValue('component');
    $component_id = explode(':', $form_state->getValue('component')['id']);
    $component_id = end($component_id);

    // Builds demo structure.
    $demo = [
      'name' => (string)$this->t('Set the demo name here'),
      'description' => (string)$this->t('Set the demo description here'),
      'props' => $form['rendered_result']['component']['#props'] ?? [],
    ];
    $definition = $form_state->getValue('definition');

    // Converts each property so that it is exportable.
    foreach ($demo['props'] as $prop_name => &$value) {
      $property = $definition['props']['properties'][$prop_name];
      $convertedValue = NULL;
      $this->propertyTypeManager->convertTypeToValueForStorage($convertedValue, $value, $property);

      // Warns the user if an export is not defined.
      $required = in_array($prop_name, $definition['props']['required'] ?? []);
      if (empty($convertedValue) && $required) {
        $this->messenger()->addError($this->t('There is no export definition for field @field with type @type.', [
          '@field' => $property['title'],
          '@type' => $property['type'],
        ]));
        $convertedValue = NULL;
      }

      $value = $convertedValue;
    }

    // Builds the slots. From the for we only do inline templates, but from
    // definitions they can be more complex if needed.
    // @todo evaluate if we could do some advanced feature to build arrays.
    if (!empty($form['rendered_result']['component']['#slots'])) {
      $demo['slots'] = $form['rendered_result']['component']['#slots'];
      foreach ($demo['slots'] as $key => &$value) {
        if (is_array($value)) {
          if (isset($value['#template'])) {
            $value['#template'] = str_replace("\r\n", PHP_EOL, Html::escape($value['#template']));
          }
          continue;
        }
        $value = str_replace("\r\n", PHP_EOL, Html::escape($value));
      }
    }

    // Builds response content.
    $formatVars = [
      '@filename' => new FormattableMarkup('<strong>@component_name.demo.@demo_name.yml</strong>', [
        '@component_name' => $component_id,
        '@demo_name' => $this->t('YOUR_DEMO_NAME'),
      ])
    ];
    $content = [
      'code' => [
        '#children' => '<pre><code class="language-yaml">' . Yaml::encode($demo) . '</code></pre>',
      ],
      'description' => [
        '#markup' => $this->t('Copy this code in a yml file on the same folder as your component. The filename format is @filename', $formatVars),
        '#prefix' => '<div class="sdc-styleguide-export__description">',
        '#suffix' => '</div>',
      ],
      'highlight' => [
        '#children' => '<script>hljs.highlightAll();</script>',
      ],
    ];

    // Builds response.
    $response = new AjaxResponse();
    $dialog_options = [
      'dialogClass' => 'sdc-styleguide-demo-export',
      'minHeight' => 'min-content',
      'minWidth' => '600',
      'resizable' => TRUE
    ];
    $response->addCommand(new OpenModalDialogCommand('Demo export', $this->renderer->render($content), $dialog_options));
    $response->setAttachments(['library' => ['sdc_styleguide/highlightjs-yaml']]);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No op.
  }

}
