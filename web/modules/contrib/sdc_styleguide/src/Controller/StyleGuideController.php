<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\sdc_styleguide\Form\SDCDemoForm;
use Drupal\sdc_styleguide\SDCStyleguidePluginManager;
use Drupal\sdc_styleguide\Service\SDCDemoManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Single Directory Components Styleguide routes.
 */
final class StyleGuideController extends ControllerBase {

  /**
   * Constructs a new StyleGuideController object.
   */
  public function __construct(
    private readonly SDCDemoManager $demoManager,
    private readonly SDCStyleguidePluginManager $sdcPluginManager,
    private readonly BlockManagerInterface $blockManager,
    private readonly ThemeManagerInterface $themeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('sdc_styleguide.demo_manager'),
      $container->get('plugin.manager.sdc_styleguide'),
      $container->get('plugin.manager.block'),
      $container->get('theme.manager'),
    );
  }

  /**
   * Builds the welcome page.
   */
  public function welcome() {
    return [
      '#theme' => 'styleguide_welcome_message',
    ];
  }

  /**
   * Builds the response.
   */
  public function __invoke(): array {
    $componentDemos = $this->demoManager->getDemos();

    $build = [
      '#prefix' => '<div class="sdc-styleguide-explorer">',
      '#suffix' => '</div>',
    ];
    foreach ($componentDemos as $group => $componentsInGroup) {
      $build[$group] = [
        '#theme' => 'styleguide_explorer_group',
        '#title' => $group,
        '#components' => [],
      ];

      $items = &$build[$group]['#components'];
      foreach ($componentsInGroup as $componentId => $component) {
        $items[$componentId] = [
          '#prefix' => '<div class="sdc-styleguide-explorer__component">',
          '#suffix' => '</div>',
          'heading' => [
            '#prefix' => '<h3 class="sdc-styleguide-explorer__component-title">',
            '#suffix' => '</h3>',
            'link' => Link::createFromRoute($component['name'], 'sdc_styleguide.form', [
              'componentId' => $componentId,
            ], ['attributes' => ['class' => ['sdc-styleguide-explorer__demo-link']]])
              ->toRenderable(),
          ],
          'items' => [
            '#prefix' => '<div class="sdc-styleguide-explorer__component-demos">',
            '#suffix' => '</div>',
            '#theme' => 'item_list',
            '#items' => [],
          ],
        ];
        $demos = &$items[$componentId]['items']['#items'];

        // Builds the demos.
        foreach ($component['demos'] as $demoId => $data) {
          $demos[$demoId] = Link::createFromRoute($data['name'], 'sdc_styleguide.viewer', [
            'group' => $group,
            'component' => $componentId,
            'demo' => $demoId,
          ], [
            'attributes' => [
              'class' => ['sdc-styleguide-explorer__demo-link'],
              'title' => $data['description'] ?? $this->t('Description missing.'),
            ],
          ]);
        }
      }
    }

    $explorer_plugins = [];
    $tool_plugins = [];
    $plugins = $this->sdcPluginManager->getDefinitions();
    /** @var \Drupal\sdc_styleguide\SDCStyleguidePluginInterface $plugin */
    foreach ($plugins as $plugin) {
      $type = $plugin['type'];
      $plugin = $this->sdcPluginManager->createInstance($plugin['id']);
      switch ($type) {
        case 'explorer':
          $explorer_plugins[] = $plugin->build();
          break;

        case 'tool':
          $tool_plugins[] = $plugin->build();
          break;
      }
    }

    $build['Styleguide'] = [
      '#weight' => -100,
      '#theme' => 'styleguide_explorer_group',
      '#title' => $this->t('Styleguide'),
      '#components' => [
        'colors' => [
          '#prefix' => '<div class="sdc-styleguide-explorer__component">',
          '#suffix' => '</div>',
          'heading' => [
            '#prefix' => '<h3 class="sdc-styleguide-explorer__component-title">',
            '#suffix' => '</h3>',
            'link' => [
              '#type' => 'link',
              '#url' => Url::fromRoute('sdc_styleguide.section', ['section' => 'colors'], ['attributes' => ['class' => ['sdc-styleguide-explorer__demo-link']]]),
              '#title' => $this->t('Colors'),
            ],
          ],
          'items' => [],
        ],
        'text' => [
          '#prefix' => '<div class="sdc-styleguide-explorer__component">',
          '#suffix' => '</div>',
          'heading' => [
            '#prefix' => '<h3 class="sdc-styleguide-explorer__component-title">',
            '#suffix' => '</h3>',
            'link' => [
              '#type' => 'link',
              '#url' => Url::fromRoute('sdc_styleguide.section', ['section' => 'text'], ['attributes' => ['class' => ['sdc-styleguide-explorer__demo-link']]]),
              '#title' => $this->t('Text'),
            ],
          ],
          'items' => [],
        ],
      ],
    ];

    _sdc_styleguide_page_variables([
      'branding' => $this->getBrandingBlockContents(),
      'components' => $build,
      'explorer_plugins' => $explorer_plugins,
      'tool_plugins' => $tool_plugins,
      'content' => [
        '#theme' => 'styleguide_component_viewer',
        '#url' => Url::fromRoute('sdc_styleguide.welcome'),
      ],
    ]);
    return ['#markup' => ''];
  }

  /**
   * Gets the branding system block to get the current site's logo.
   *
   * @return array
   *   A render array representing the themed block.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function getBrandingBlockContents() {
    $configuration = [
      'label_display' => BlockPluginInterface::BLOCK_LABEL_VISIBLE,
      'use_site_name' => FALSE,
      'use_site_slogan' => FALSE,
    ];
    $block_plugin = $this->blockManager->createInstance('system_branding_block', $configuration);
    $build = [
      '#theme' => 'block',
      '#id' => $configuration['id'] ?? NULL,
      '#attributes' => [],
      '#contextual_links' => [],
      '#configuration' => $block_plugin->getConfiguration(),
      '#plugin_id' => $block_plugin->getPluginId(),
      '#base_plugin_id' => $block_plugin->getBaseId(),
      '#derivative_plugin_id' => $block_plugin->getDerivativeId(),
      'content' => $block_plugin->build(),
    ];
    return $build;
  }


  /**
   *  Converts the array defined in a demo to a valid render array.
   *
   * @param array $item
   *  The array to convert to a render array.
   *
   * @return array
   *   The render array to be used within the demo.
   *
   * @return array
   */
  private function convertToRenderArray(array $item) : array {
    $rendereable = [];
    foreach ($item as $key => $value) {
      if (!str_starts_with($key, '#')) {
        $key = "#{$key}";
      }
      $rendereable[$key] = $value;
    }

    // If we are attempting to render a demo integrating with UI patterns.
    if (isset($rendereable['#type']) && $rendereable['#type'] == 'component' && isset($rendereable['#story'])) {
      $rendereable['#demo'] = $rendereable['#component'] . ".demo.{$rendereable['#story']}";
      $rendereable['#theme'] = 'styleguide_demo';
      unset($rendereable['#type'], $rendereable['#story'], $rendereable['#component']);
    }
    return $rendereable;
  }

  /**
   * Displays a simple page with the demo.
   *
   * @param string $group
   *   The group the SDC belongs to.
   * @param string $component
   *   The SDC to render.
   * @param string $demo
   *   The demo name to use.
   *
   * @return array
   *   The render array representing the component output.
   */
  public function view(string $group, string $component, string $demo) {
    $componentDemos = $this->demoManager->getDemos();
    $definition = $componentDemos[$group][$component]['demos'][$demo];

    $slots = [];
    foreach ($definition['slots'] ?? [] as $slot_name => $slot_definition) {
      $rendereable = [];
      if (is_array($slot_definition)) {
        if (array_is_list($slot_definition)) {
          foreach ($slot_definition as $item) {
            $rendereable[] = $this->convertToRenderArray($item);
          }
        }
        else {
          $rendereable = $this->convertToRenderArray($slot_definition);
        }
      }
      else {
        $rendereable = ['#type' => 'inline_template', '#template' => $slot_definition];
      }
      $slots[$slot_name] = $rendereable;
    }

    // Component wrapper data.
    $horizontal = 'center';
    $vertical = 'center';
    $padding = TRUE;
    $fillerBefore = NULL;
    $fillerAfter = NULL;
    if ($settings = $definition['preview_settings'] ?? NULL) {
      $padding = $settings['padding'] ?? $padding;
      $fillerBefore = $settings['filler_before'] ?? $fillerBefore;
      $fillerAfter = $settings['filler_after'] ?? $fillerAfter;
      if ($position = $settings['position'] ?? NULL) {
        $horizontal = $position['horizontal'] ?? $horizontal;
        $vertical = $position['vertical'] ?? $vertical;
      }
    }

    // Container attributes.
    $attribute = new Attribute();
    $attribute->setAttribute('data-vertical', $vertical);
    $attribute->setAttribute('data-horizontal', $horizontal);
    $attribute->setAttribute('data-is-padded', $padding);
    $attribute->addClass('sdc-styleguide-demo');
    $sdcDemo = [
      '#attributes' => $attribute->toArray(),
      '#type' => 'container',
      'demo_component' => [
        '#type' => 'component',
        '#component' => $component,
        '#props' => $definition['properties'] ?? $definition['props'] ?? [],
        '#slots' => $slots,
      ],
      'a11y' => [
        '#theme' => 'styleguide_a11y_evaluation',
      ],
    ];

    if ($fillerBefore) {
      $sdcDemo['filler_before'] = [
        '#attributes' => ['class' => ['sdc-styleguide-demo__filler']],
        '#type' => 'container',
        '#weight' => -100,
        'content' => [
          '#children' => $fillerBefore,
        ],
      ];
    }
    if ($fillerAfter) {
      $sdcDemo['filler_after'] = [
        '#attributes' => ['class' => ['sdc-styleguide-demo__filler']],
        '#type' => 'container',
        'content' => [
          '#children' => $fillerAfter,
        ],
      ];
    }
    return $sdcDemo;
  }

  /**
   * Generates an SDC demo form.
   *
   * @param string $componentId
   *   The id of the component to generate the form for.
   *
   * @return array
   *   The form render array.
   */
  public function form(string $componentId) {
    return $this->formBuilder()->getForm(SDCDemoForm::class, $componentId);
  }

  public function generateStyleguideSection($section) : array {
    // Errors on invalid section.
    $sections = [];
    $this->themeManager->alter('styleguide_sections', $sections);
    if (empty($sections)) {
      $sections = _sdc_styleguide_get_default_sections();
    }

    if (!isset($sections[$section])) {
      throw new NotFoundHttpException((string)$this->t('Invalid section @section.', ['@section' => $section]));
    }

    return [
      '#theme' => 'styleguide_section',
      '#section' => $section,
      '#information' => $sections[$section] ?? NULL
    ];
  }
}
