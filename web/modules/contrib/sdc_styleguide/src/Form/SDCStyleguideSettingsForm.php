<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Theme\ComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Single Directory Components Styleguide settings for this site.
 */
final class SDCStyleguideSettingsForm extends ConfigFormBase {

  private ComponentPluginManager $sdcPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $form = new static($container->get('config.factory'));
    $form->sdcPluginManager = $container->get('plugin.manager.sdc');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'sdc_styleguide_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() : array {
    return ['sdc_styleguide.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) : array {
    $others = $themes = $modules = [];
    $components = $this->sdcPluginManager->getAllComponents();
    if (!empty($components)) {
      /** @var \Drupal\Core\Plugin\Component $component */
      foreach ($components as $component) {
        $definition = $component->getPluginDefinition();
        $list = NULL;
        switch ($definition['extension_type']->value) {
          case 'theme':
            $list = &$themes;
            break;

          case 'module':
            $list = &$modules;
            break;

          default:
            $list = &$others;
            break;
        }

        $provider = $definition['provider'];
        if (!in_array($provider, $list)) {
          $list[$provider] = $provider;
        }
        unset($list);
      }
    }

    $defaults = $this->config('sdc_styleguide.settings')->get('component_explorer');

    $form['component_explorer'] = [
      '#tree' => TRUE,
      'exclude_modules' => [
        '#access' => !empty($modules),
        '#description' => $this->t('Which modules to exclude from the component explorer only.'),
        '#default_value' => $defaults['exclude_modules'] ?? [],
        '#options' => $modules,
        '#type' => 'checkboxes',
        '#title' => $this->t('Exclude Modules'),
      ],
      'exclude_themes' => [
        '#access' => !empty($themes),
        '#description' => $this->t('Which themes to exclude from the component explorer only.'),
        '#default_value' => $defaults['exclude_themes'] ?? [],
        '#options' => $themes,
        '#type' => 'checkboxes',
        '#title' => $this->t('Exclude Themes'),
      ],
      'exclude_others' => [
        '#access' => !empty($others),
        '#description' => $this->t('Which other elements to exclude from the component explorer only.'),
        '#default_value' => $defaults['exclude_others'] ?? [],
        '#options' => $others,
        '#type' => 'checkboxes',
        '#title' => $this->t('Exclude Other'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $component_explorer = $form_state->getValue('component_explorer');
    foreach (array_keys($component_explorer) as $provider) {
      $component_explorer[$provider] = array_filter($component_explorer[$provider], fn ($value) => $value != 0);
      $component_explorer[$provider] = array_values($component_explorer[$provider]);
    }
    $form_state->setValue('component_explorer', $component_explorer);
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('sdc_styleguide.settings')
      ->set('component_explorer', $form_state->getValue('component_explorer'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
