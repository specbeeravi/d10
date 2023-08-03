<?php

namespace Drupal\visitors\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Visitors Settings Form.
 */
class Settings extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'visitors.config';

  /**
  * Shows this block on every page except the listed pages.
  */
  const PATH_NOT_LISTED = 0;

  /**
   * Shows this block on only the listed pages.
   */
  const PATH_LISTED = 1;


  /**
   * An extension discovery instance.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeList;

  /**
   * An extension discovery instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * No customization allowed to the users.
   */
  public const VISIBILITY_USER_ACCOUNT_MODE_NO_PERSONALIZATION = 0;

  /**
   * Customization allowed, tracking enabled by default.
   */
  public const VISIBILITY_USER_ACCOUNT_MODE_OPT_OUT = 1;

  /**
   * Customization allowed, tracking disabled by default.
   */
  public const VISIBILITY_USER_ACCOUNT_MODE_OPT_IN = 2;

  /**
   * When visibility on pages is conditioned by PHP code.
   */
  public const VISIBILITY_REQUEST_PATH_MODE_PHP = 2;

  /**
   * If cookie domain has more than this number of parts, adapt form example.
   */
  public const MULTI_DOMAIN_TRIGGER = 2;

  /**
   * Number of supported custom variables.
   *
   * @todo see if in the latest version of Matomo it is still the case.
   */
  public const MAX_CUSTOM_VARIABLES = 5;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfig;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Using constructor less class instantiation inspired by the Webform
    // module.
    // @see https://www.drupal.org/node/3076421
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->httpClient = $container->get('http_client');
    $instance->sessionConfig = $container->get('session_configuration');
    $instance->themeList = $container->get('extension.list.theme');
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'visitors_admin_settings';
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
    $config = $this->config('visitors.config');
    $system_config = $this->config('system.theme');
    $form = parent::buildForm($form, $form_state);

    $roles = [];
    foreach ($this->entityTypeManager->getStorage('user_role')->loadMultiple() as $name => $role) {
      $roles[$name] = $role->label();
    }

    $all_themes = $this->themeList->getList();
    $default_theme = $system_config->get('default');
    $admin_theme = $system_config->get('admin');

    $default_name = $all_themes[$default_theme]->info['name'];
    $themes_installed = [
      'default' => $this->t('Default (@default)', ['@default' => $default_name]),
    ];
    if ($admin_theme) {
      $admin_name = $all_themes[$admin_theme]->info['name'];
      $themes_installed['admin'] = $this->t('Admin (@admin)', ['@admin' => $admin_name]);
    }

    $list_themes = array_filter($all_themes, function ($obj) {
      return $obj->status;
    });
    $themes_installed += array_map(function ($value) {
      return $value->info['name'];
    }, $list_themes);
    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Set a theme for reports'),
      '#options' => $themes_installed,
      '#default_value' => $config->get('theme') ?: 'admin',
      '#description' => $this->t('Select a theme for the Visitors reports.'),
    ];

    // Visibility settings.
    $form['tracking_scope'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Tracking scope'),
      '#attached' => [
        'library' => [
          'visitors/visitors.admin',
        ],
      ],
    ];

    $form['tracking']['domain_tracking'] = [
      '#type' => 'details',
      '#title' => $this->t('Domains'),
      '#group' => 'tracking_scope',
    ];

    $session_options = $this->sessionConfig->getOptions($this->getRequest());
    $cookie_domain = $session_options['cookie_domain'];
    $multiple_sub_domains = [];
    foreach (['www', 'app', 'shop'] as $subdomain) {
      if (\count(\explode('.', $cookie_domain)) > self::MULTI_DOMAIN_TRIGGER && !\is_numeric(\str_replace('.', '', $cookie_domain))) {
        $multiple_sub_domains[] = $subdomain . $cookie_domain;
      }
      // IP addresses or localhost.
      else {
        $multiple_sub_domains[] = $subdomain . '.example.com';
      }
    }

    $form['tracking']['domain_tracking']['visitors_domain_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('What are you tracking?'),
      '#options' => [
        0 => $this->t('A single domain (default)'),
        1 => $this->t('One domain with multiple subdomains'),
      ],
      0 => [
        '#description' => $this->t('Domain: @domain', ['@domain' => $this->getRequest()->getHost()]),
      ],
      1 => [
        '#description' => $this->t('Examples: @domains', ['@domains' => \implode(', ', $multiple_sub_domains)]),
      ],
      '#default_value' => $config->get('domain_mode'),
    ];

    // Page specific visibility configurations.
    $visibility_request_path_pages = $config->get('visibility.request_path_pages');

    $form['tracking']['page_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Pages'),
      '#group' => 'tracking_scope',
    ];

    if ($config->get('visibility.request_path_mode') == self::VISIBILITY_REQUEST_PATH_MODE_PHP) {
      // No permission to change PHP snippets, but keep existing settings.
      $form['tracking']['page_visibility_settings'] = [];
      $form['tracking']['page_visibility_settings']['visitors_visibility_request_path_mode'] = [
        '#type' => 'value',
        '#value' => self::VISIBILITY_REQUEST_PATH_MODE_PHP,
      ];
      $form['tracking']['page_visibility_settings']['visitors_visibility_request_path_pages'] = [
        '#type' => 'value',
        '#value' => $visibility_request_path_pages,
      ];
    }
    else {
      $options = [
        $this->t('All pages except those listed'),
        $this->t('Only the listed pages'),
      ];
      $description = $this->t(
        "Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.",
        [
          '%blog' => '/blog',
          '%blog-wildcard' => '/blog/*',
          '%front' => '<front>',
        ]
      );

      $form['tracking']['page_visibility_settings']['visitors_visibility_request_path_pages'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Pages'),
        '#title_display' => 'invisible',
        '#default_value' => !empty($visibility_request_path_pages) ? $visibility_request_path_pages : '',
        '#description' => $description,
        '#rows' => (int) 10,
      ];
      $form['tracking']['page_visibility_settings']['visitors_visibility_request_path_mode'] = [
        '#type' => 'radios',
        '#title' => $this->t('Add tracking to specific pages'),
        '#title_display' => 'invisible',
        '#options' => $options,
        '#default_value' => $config->get('visibility.request_path_mode'),
      ];
    }

    // Render the role overview.
    $visibility_user_role_roles = $config->get('visibility.user_role_roles');

    $form['tracking']['role_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#group' => 'tracking_scope',
    ];

    $form['tracking']['role_visibility_settings']['visitors_visibility_user_role_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add tracking for specific roles'),
      '#options' => [
        $this->t('Add to the selected roles only'),
        $this->t('Add to every role except the selected ones'),
      ],
      '#default_value' => $config->get('visibility.user_role_mode'),
    ];
    $form['tracking']['role_visibility_settings']['visitors_visibility_user_role_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => !empty($visibility_user_role_roles) ? $visibility_user_role_roles : [],
      '#options' => \array_map('\Drupal\Component\Utility\Html::escape', \user_role_names()),
      '#description' => $this->t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
    ];

    // Standard tracking configurations.
    $visibility_user_account_mode = $config->get('visibility.user_account_mode');

    $form['tracking']['user_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Users'),
      '#group' => 'tracking_scope',
    ];
    $t_permission = ['%permission' => $this->t('opt-out of visitors tracking')];
    $form['tracking']['user_visibility_settings']['visitors_visibility_user_account_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow users to customize tracking on their account page'),
      '#options' => [
        self::VISIBILITY_USER_ACCOUNT_MODE_NO_PERSONALIZATION => $this->t('No customization allowed'),
        self::VISIBILITY_USER_ACCOUNT_MODE_OPT_OUT => $this->t('Tracking on by default, users with %permission permission can opt out', $t_permission),
        self::VISIBILITY_USER_ACCOUNT_MODE_OPT_IN => $this->t('Tracking off by default, users with %permission permission can opt in', $t_permission),
      ],
      '#default_value' => !empty($visibility_user_account_mode) ? $visibility_user_account_mode : 0,
    ];
    $form['tracking']['user_visibility_settings']['visitors_trackuserid'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track User ID'),
      '#default_value' => $config->get('track.userid'),
      '#description' => $this->t('User ID enables the analysis of groups of sessions, across devices, using a unique, persistent, and representing a user. <a href=":url">Learn more about the benefits of using User ID</a>.', [':url' => 'https://matomo.org/docs/user-id/']),
    ];

    $form['tracking']['user_visibility_settings']['visibility_exclude_user1'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude user1 from statistics'),
      '#default_value' => $config->get('visibility.exclude_user1'),
      '#description' => $this->t('Exclude hits of user1 from statistics.'),
    ];

    // Status Code configurations.
    $form['tracking']['status_codes'] = [
      '#type' => 'details',
      '#title' => $this->t('Status Codes'),
      '#group' => 'tracking_scope',
    ];

    $form['tracking']['status_codes']['status_codes_disabled'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Prevent tracking of pages with given HTTP Status code:'),
      '#options' => [
        '404' => $this->t('404 - Not found'),
        '403' => $this->t('403 - Access denied'),
      ],
      '#default_value' => $config->get('status_codes_disabled'),
    ];

    // Privacy specific configurations.
    $form['tracking']['privacy'] = [
      '#type' => 'details',
      '#title' => $this->t('Privacy'),
      '#group' => 'tracking_scope',
    ];
    $form['tracking']['privacy']['visitors_privacy_disablecookies'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable cookies'),
      '#description' => $this->t('Matomo uses <a href=":cookie">cookies</a> to store some information about visitors between visits. Enable to disable all Matomo tracking cookies. When cookies are disabled, some data in Matomo will be <a href=":disablecookies">less accurate</a>.', [
        ':cookie' => Url::fromUri('https://en.wikipedia.org/wiki/HTTP_cookie')->toString(),
        ':disablecookies' => Url::fromUri('https://matomo.org/faq/general/faq_156/')->toString(),
      ]),
      '#default_value' => $config->get('privacy.disablecookies'),
    ];

    $form['block'] = [
      '#type' => 'details',
      '#title' => $this->t('Default Block'),
      '#description' => $this->t('Default block settings'),
      '#group' => 'tracking_scope',
    ];

    $form['block']['show_total_visitors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Total Visitors'),
      '#default_value' => $config->get('show_total_visitors'),
      '#description' => $this->t('Show Total Visitors.'),
    ];

    $form['block']['show_unique_visitor'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Unique Visitors'),
      '#default_value' => $config->get('show_unique_visitor'),
      '#description' => $this->t('Show Unique Visitors based on their IP.'),
    ];

    $form['block']['show_registered_users_count'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Registered Users Count'),
      '#default_value' => $config->get('show_registered_users_count'),
      '#description' => $this->t('Show Registered Users.'),
    ];

    $form['block']['show_last_registered_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Last Registered User'),
      '#default_value' => $config->get('show_last_registered_user'),
      '#description' => $this->t('Show Last Registered User.'),
    ];

    $form['block']['show_published_nodes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Published Nodes'),
      '#default_value' => $config->get('show_published_nodes'),
      '#description' => $this->t('Show Published Nodes.'),
    ];

    $form['block']['show_user_ip'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show User IP'),
      '#default_value' => $config->get('show_user_ip'),
      '#description' => $this->t('Show User IP.'),
    ];

    $form['block']['show_since_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Since Date'),
      '#default_value' => $config->get('show_since_date'),
      '#description' => $this->t('Show Since Date.'),
    ];

    $form['block']['start_count_total_visitors'] = [
      '#type' => 'number',
      '#title' => $this->t('Total visitors start count'),
      '#default_value' => $config->get('start_count_total_visitors') ?? 0,
      '#description' => $this->t('Start the count of the total visitors at this number. Useful for including the known number of visitors in the past.'),
    ];

    $form['charts'] = [
      '#type' => 'details',
      '#title' => $this->t('Charts'),
      '#description' => $this->t('Visitors chart settings'),
      '#group' => 'tracking_scope',
    ];

    $form['charts']['chart_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $config->get('chart_width') ?? 700,
      '#description' => $this->t('Chart width.'),
    ];

    $form['charts']['chart_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $config->get('chart_height') ?? 430,
      '#description' => $this->t('Chart height.'),
    ];

    // Advanced feature configurations.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['advanced']['visitors_disable_tracking'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable tracking'),
      '#description' => $this->t('If checked, the tracking code is disabled generally.'),
      '#default_value' => $config->get('disable_tracking'),
    ];

    $form['advanced']['items_per_page'] = [
      '#type' => 'select',
      '#title' => 'Items per page',
      '#default_value' => $config->get('items_per_page'),
      '#options' => [
        5 => 5,
        10 => 10,
        25 => 25,
        50 => 50,
        100 => 100,
        200 => 200,
        250 => 250,
        500 => 500,
        1000 => 1000,
      ],
      '#description' =>
      $this->t('The default maximum number of items to display per page.'),
    ];

    $form['advanced']['flush_log_timer'] = [
      '#type' => 'select',
      '#title' => $this->t('Discard visitors logs older than'),
      '#default_value'   => $config->get('flush_log_timer'),
      '#options' => [
        0 => $this->t('Never'),
        3600 => $this->t('1 hour'),
        10800 => $this->t('3 hours'),
        21600 => $this->t('6 hours'),
        32400 => $this->t('9 hours'),
        43200 => $this->t('12 hours'),
        86400 => $this->t('1 day'),
        172800 => $this->t('2 days'),
        259200 => $this->t('3 days'),
        604800 => $this->t('1 week'),
        1209600 => $this->t('2 weeks'),
        4838400 => $this->t('1 month 3 weeks'),
        9676800 => $this->t('3 months 3 weeks'),
        31536000 => $this->t('1 year'),
      ],
      '#description' =>
      $this->t('Older visitors log entries (including referrer statistics) will be automatically discarded. (Requires a correctly configured <a href="@cron">cron maintenance task</a>.)',
          ['@cron' => Url::fromRoute('system.status')->toString()]
      ),
    ];
    // Allow for tracking of the originating node when viewing translation sets.
    if ($this->moduleHandler->moduleExists('content_translation')) {
      $form['advanced']['visitors_translation_set'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Track translation sets as one unit'),
        '#description' => $this->t('When a node is part of a translation set, record statistics for the originating node instead. This allows for a translation set to be treated as a single unit.'),
        '#default_value' => $config->get('translation_set'),
      ];
    }

    $form['advanced']['codesnippet'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom JavaScript code'),
      '#open' => TRUE,
      '#description' => $this->t('You can add custom Matomo <a href=":snippets">code snippets</a> here. These will be added to every page that Matomo appears on. <strong>Do not include the &lt;script&gt; tags</strong>, and always end your code with a semicolon (;).', [':snippets' => 'https://matomo.org/docs/javascript-tracking/']),
    ];
    $form['advanced']['codesnippet']['visitors_codesnippet_before'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Code snippet (before)'),
      '#default_value' => $config->get('codesnippet.before'),
      '#rows' => 5,
      '#description' => $this->t('Code in this textarea will be added <strong>before</strong> _paq.push(["trackPageView"]).'),
    ];
    $form['advanced']['codesnippet']['visitors_codesnippet_after'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Code snippet (after)'),
      '#default_value' => $config->get('codesnippet.after'),
      '#rows' => 5,
      '#description' => $this->t('Code in this textarea will be added <strong>after</strong> _paq.push(["trackPageView"]). This is useful if you\'d like to track a site in two accounts.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable(static::SETTINGS);
    $values = $form_state->getValues();
    $config
      ->set('show_total_visitors', $values['show_total_visitors'])
      ->set('start_count_total_visitors', $values['start_count_total_visitors'])
      ->set('show_unique_visitor', $values['show_unique_visitor'])
      ->set('show_registered_users_count', $values['show_registered_users_count'])
      ->set('show_last_registered_user', $values['show_last_registered_user'])
      ->set('show_published_nodes', $values['show_published_nodes'])
      ->set('show_user_ip', $values['show_user_ip'])
      ->set('show_since_date', $values['show_since_date'])
      ->set('theme', $values['theme'])
      ->set('items_per_page', $values['items_per_page'])
      ->set('flush_log_timer', $values['flush_log_timer'])
      ->set('chart_width', $values['chart_width'])
      ->set('chart_height', $values['chart_height'])
      ->set('codesnippet.before', $values['visitors_codesnippet_before'])
      ->set('codesnippet.after', $values['visitors_codesnippet_after'])
      ->set('domain_mode', $values['visitors_domain_mode'])
      ->set('track.userid', $values['visitors_trackuserid'])
      ->set('privacy.disablecookies', $values['visitors_privacy_disablecookies'])
      ->set('disable_tracking', $values['visitors_disable_tracking'])
      ->set('visibility.request_path_mode', $values['visitors_visibility_request_path_mode'])
      ->set('visibility.request_path_pages', $values['visitors_visibility_request_path_pages'])
      ->set('visibility.user_account_mode', $values['visitors_visibility_user_account_mode'])
      ->set('visibility.user_role_mode', $values['visitors_visibility_user_role_mode'])
      ->set('visibility.user_role_roles', $values['visitors_visibility_user_role_roles'])
      ->set('visibility.exclude_user1', $values['visibility_exclude_user1'])
      ->set('status_codes_disabled', array_values(array_filter($values['status_codes_disabled'])))
      ->save();

    if ($form_state->hasValue('visitors_translation_set')) {
      $config->set('translation_set', $form_state->getValue('visitors_translation_set'))->save();
    }

    parent::submitForm($form, $form_state);
  }

}
