<?php

namespace Drupal\visitors\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Visitors' block.
 *
 * @Block(
 *   id = "visitors_block",
 *   admin_label = @Translation("Visitors"),
 *   category = @Translation("Visitors")
 * )
 */
class VisitorsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service object.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $database,
    RendererInterface $renderer,
    DateFormatterInterface $date_formatter,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
    $this->configFactory = $config_factory;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): VisitorsBlock {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('renderer'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('request_stack'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $items = [];
    $config = $this->getConfiguration();

    if ($config['show_total_visitors']) {
      $items[] = $this->showTotalVisitors();
    }
    if ($config['show_unique_visitor']) {
      $items[] = $this->showUniqueVisitors();
    }
    if ($config['show_registered_users_count']) {
      $items[] = $this->showRegisteredUsersCount();
    }
    if ($config['show_last_registered_user']) {
      $items[] = $this->showLastRegisteredUser();
    }
    if ($config['show_published_nodes']) {
      $items[] = $this->showPublishedNodes();
    }
    if ($config['show_user_ip']) {
      $items[] = $this->showUserIp();
    }
    if ($config['show_since_date']) {
      $items[] = $this->showSinceDate();
    }

    $build = [
      'visitors_info' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $default = $this->configFactory->get('visitors.config');
    $default_config = [
      'show_total_visitors' => $default->get('show_total_visitors'),
      'show_unique_visitor' => $default->get('show_unique_visitor'),
      'show_registered_users_count' => $default->get('show_registered_users_count'),
      'show_last_registered_user' => $default->get('show_last_registered_user'),
      'show_published_nodes' => $default->get('show_published_nodes'),
      'show_user_ip' => $default->get('show_user_ip'),
      'show_since_date' => $default->get('show_since_date'),
    ];

    $block_config = $this->getConfiguration();

    $config = array_merge($default_config, $block_config);
    $form['show_total_visitors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Total Visitors'),
      '#default_value' => $config['show_total_visitors'],
      '#description' => $this->t('Show Total Visitors.'),
    ];

    $form['show_unique_visitor'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Unique Visitors'),
      '#default_value' => $config['show_unique_visitor'],
      '#description' => $this->t('Show Unique Visitors based on their IP.'),
    ];

    $form['show_registered_users_count'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Registered Users Count'),
      '#default_value' => $config['show_registered_users_count'],
      '#description' => $this->t('Show Registered Users.'),
    ];

    $form['show_last_registered_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Last Registered User'),
      '#default_value' => $config['show_last_registered_user'],
      '#description' => $this->t('Show Last Registered User.'),
    ];

    $form['show_published_nodes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Published Nodes'),
      '#default_value' => $config['show_published_nodes'],
      '#description' => $this->t('Show Published Nodes.'),
    ];

    $form['show_user_ip'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show User IP'),
      '#default_value' => $config['show_user_ip'],
      '#description' => $this->t('Show User IP.'),
    ];

    $form['show_since_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Since Date'),
      '#default_value' => $config['show_since_date'],
      '#description' => $this->t('Show Since Date.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('show_total_visitors', $form_state->getValue('show_total_visitors'));
    $this->setConfigurationValue('show_unique_visitor', $form_state->getValue('show_unique_visitor'));
    $this->setConfigurationValue('show_registered_users_count', $form_state->getValue('show_registered_users_count'));
    $this->setConfigurationValue('show_last_registered_user', $form_state->getValue('show_last_registered_user'));
    $this->setConfigurationValue('show_published_nodes', $form_state->getValue('show_published_nodes'));
    $this->setConfigurationValue('show_user_ip', $form_state->getValue('show_user_ip'));
    $this->setConfigurationValue('show_since_date', $form_state->getValue('show_since_date'));
  }

  /**
   * Display total visitors count to visitors block.
   */
  protected function showTotalVisitors(): string {
    $config = $this->configFactory->get('visitors.config');

    $query = $this->database->select('visitors');
    $query->addExpression('COUNT(*)');

    $start_count_total_visitors = $config->get('start_count_total_visitors') ?? 0;
    $count = $query->execute()->fetchField() + $start_count_total_visitors;

    $item = $this->t('Total Visitors: %visitors', ['%visitors' => $count]);

    return $item;
  }

  /**
   * Display unique visitors count to visitors block.
   */
  protected function showUniqueVisitors(): string {
    $query = $this->database->select('visitors');
    $query->addExpression('COUNT(DISTINCT visitors_ip)');

    $unique_visitors = $query->execute()->fetchField();

    $item = $this->t('Unique Visitors: %unique_visitors', ['%unique_visitors' => $unique_visitors]);

    return $item;
  }

  /**
   * Display registered users count to visitors block.
   */
  protected function showRegisteredUsersCount(): string {

    $query = $this->database->select('users');
    $query->addExpression('COUNT(*)');
    $query->condition('uid', '0', '>');

    $registered_users_count = $query->execute()->fetchField();

    $item = $this->t('Registered Users: %registered_users_count', ['%registered_users_count' => $registered_users_count]);

    return $item;
  }

  /**
   * Display last registered user to visitors block.
   */
  protected function showLastRegisteredUser(): string {

    $last_user_uid = $this->database->select('users', 'u')
      ->fields('u', ['uid'])
      ->orderBy('uid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    $user = $this->entityTypeManager->getStorage('user')->load($last_user_uid);
    $username = [
      '#theme' => 'username',
      '#account' => $user,
    ];

    $item = $this->t('Last Registered User: @last_user',
      ['@last_user' => $this->renderer->render($username)]);

    return $item;
  }

  /**
   * Display published nodes count to visitors block.
   */
  protected function showPublishedNodes(): string {

    $query = $this->database->select('node', 'n');
    $query->innerJoin('node_field_data', 'nfd', 'n.nid = nfd.nid');
    $query->addExpression('COUNT(*)');
    $query->condition('nfd.status', '1', '=');

    $nodes = $query->execute()->fetchField();

    $item = $this->t('Published Nodes: %nodes', ['%nodes' => $nodes]);

    return $item;
  }

  /**
   * Display user ip to visitors block.
   */
  protected function showUserIp(): string {
    $item = $this->t('Your IP: %user_ip', [
      '%user_ip' => $this->request->getClientIp(),
    ]);

    return $item;
  }

  /**
   * Display the start date statistics to visitors block.
   */
  protected function showSinceDate(): string {
    $query = $this->database->select('visitors');
    $query->addExpression('MIN(visitors_date_time)');

    $since_date = $query->execute()->fetchField();

    $item = $this->t('Since: %since_date', [
      '%since_date' => $this->dateFormatter->format($since_date, 'short'),
    ]);

    return $item;
  }

}
