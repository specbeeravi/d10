<?php

namespace Drupal\visitors_geoip\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Visitors Settings Form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'visitors_geoip.settings';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    MessengerInterface $messenger) {

    parent::__construct($config_factory);
    $this->messenger = $messenger;

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'visitors_geoip_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->configFactory->get('visitors_geoip.settings');
    $form = parent::buildForm($form, $form_state);

    $form['geoip_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GeoIP Database path'),
      '#description' => $this->t('Enter the path to the MindMax Cities database. Compatible with GeoLite2 and GeoIP2 databases.'),
      '#default_value' => $settings->get('geoip_path'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $path = $form_state->getValue('geoip_path');
    if (!file_exists($path)) {
      $form_state->setErrorByName('geoip_path', $this->t('File does not exists, or is not accessible.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable(static::SETTINGS);
    $values = $form_state->getValues();
    $config
      ->set('geoip_path', $values['geoip_path'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
