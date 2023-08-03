<?php

namespace Drupal\visitors\Service;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Drupal\visitors\VisitorsScriptInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Javascript tracking service.
 */
class ScriptService implements VisitorsScriptInterface {
  use StringTranslationTrait;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $path;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The session config.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfig;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The config object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\CurrentPathStack $path_current
   *   The current path.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_config
   *   The session config.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user,
    RequestStack $request_stack,
    CurrentPathStack $path_current,
    MessengerInterface $messenger,
    ModuleHandlerInterface $module_handler,
    Token $token,
    StateInterface $state,
    SessionConfigurationInterface $session_config,
    CurrentRouteMatch $current_route_match,
    EntityRepositoryInterface $entity_repository,
  ) {
    $this->config = $config_factory->get('visitors.config');
    $this->currentUser = $current_user;
    $this->request = $request_stack->getCurrentRequest();
    $this->path = $path_current->getPath();
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->token = $token;
    $this->state = $state;
    $this->sessionConfig = $session_config;
    $this->currentRouteMatch = $current_route_match;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function script(): string {
    // Get page http status code for visibility filtering.
    $status = NULL;
    $exception = $this->request->attributes->get('exception');
    if ($exception) {
      $status = $exception->getStatusCode();
    }
    // $this->config->get('site_id') ?? '1';
    $id = 1;

    $url_http = $site_url = $this->request->getScheme() . '://' . $this->request->getHost();
    // $this->config->get('url_http');
    $url_https = $this->config->get('url_https');

    $set_custom_url = '';
    $set_document_title = '';

    // Add link tracking.
    $link_settings = [];
    $link_settings['disableCookies'] = $this->config->get('privacy.disablecookies');
    $link_settings['trackMailto'] = $this->config->get('track.mailto');

    $page['#attached']['drupalSettings']['visitors'] = $link_settings;
    // $page['#attached']['library'][] = 'visitors/matomo';
    // Matomo can show a tree view of page titles that represents the site
    // structure if setDocumentTitle() provides the page titles as a "/"
    // delimited list. This may makes it easier to browse through the statistics
    // of page titles on larger sites.
    // Add messages tracking.
    $message_events = '';
    $message_types = $this->config->get('track.messages');
    if ($message_types) {
      $message_types = array_values(array_filter($message_types));
      $status_heading = [
        'status' => $this->t('Status message'),
        'warning' => $this->t('Warning message'),
        'error' => $this->t('Error message'),
      ];

      foreach ($this->messenger->all() as $type => $messages) {
        // Track only the selected message types.
        if (in_array($type, $message_types)) {
          foreach ($messages as $message) {
            if ($message instanceof MarkupInterface) {
              $message = $message->__toString();
            }

            $message_events .= '_paq.push(["trackEvent", ' . Json::encode($this->t('Messages')) . ', ' . Json::encode($status_heading[$type]) . ', ' . Json::encode(strip_tags($message)) . ']);';
          }
        }
      }
    }

    // If this node is a translation of another node, pass the original
    // node instead.
    if ($this->moduleHandler->moduleExists('content_translation') && $this->config->get('translation_set')) {
      // Check if we have a node object, it has translation enabled, and its
      // language code does not match its source language code.
      if ($this->request->attributes->has('node')) {
        $node = $this->request->attributes->get('node');
        if ($node instanceof NodeInterface && $this->entityRepository->getTranslationFromContext($node) !== $node->getUntranslated()) {
          $set_custom_url = Json::encode(Url::fromRoute('entity.node.canonical', [
            'node' => $node->id(),
          ], [
            'language' => $node->getUntranslated()->language(),
          ])->toString());
        }
      }
    }

    // Track access denied (403) and file not found (404) pages.
    if ($status == '403') {
      $set_document_title = '"403/URL = " + encodeURIComponent(document.location.pathname+document.location.search) + "/From = " + encodeURIComponent(document.referrer)';
    }
    elseif ($status == '404') {
      $set_document_title = '"404/URL = " + encodeURIComponent(document.location.pathname+document.location.search) + "/From = " + encodeURIComponent(document.referrer)';
    }

    // #2693595: User has entered an invalid login and clicked on forgot
    // password link. This link contains the username or email address and may
    // get send to Matomo if we do not override it. Override only if 'name'
    // query param exists. Last custom url condition, this need to win.
    //
    // URLs to protect are:
    // - user/password?name=username
    // - user/password?name=foo@example.com
    if ($this->currentRouteMatch->getRouteName() == 'user.pass' && $this->request->query->has('name')) {
      $set_custom_url = Json::encode(Url::fromRoute('user.pass')->toString());
    }

    // Add custom variables.
    $matomo_custom_vars = $this->config->get('custom.variable');
    $custom_variable = NULL;
    for ($i = 1; $i < 6; $i++) {
      $custom_var_name = !empty($matomo_custom_vars[$i]['name']) ? $matomo_custom_vars[$i]['name'] : '';
      if (!empty($custom_var_name)) {
        $custom_var_value = !empty($matomo_custom_vars[$i]['value']) ? $matomo_custom_vars[$i]['value'] : '';
        $custom_var_scope = !empty($matomo_custom_vars[$i]['scope']) ? $matomo_custom_vars[$i]['scope'] : 'visit';

        $types = [];
        if ($this->request->attributes->has('node')) {
          $node = $this->request->attributes->get('node');
          if ($node instanceof NodeInterface) {
            $types += ['node' => $node];
          }
        }
        $custom_var_name = $this->token->replace($custom_var_name, $types, ['clear' => TRUE]);
        $custom_var_value = $this->token->replace($custom_var_value, $types, ['clear' => TRUE]);

        // Suppress empty custom names and/or variables.
        if (!mb_strlen(trim($custom_var_name)) || !mb_strlen(trim($custom_var_value))) {
          continue;
        }

        // Custom variables names and values are limited to 200 characters in
        // length. It is recommended to store values that are as small as
        // possible to ensure that the Matomo Tracking request URL doesn't go
        // over the URL limit for the webserver or browser.
        $custom_var_name = rtrim(substr($custom_var_name, 0, 200));
        $custom_var_value = rtrim(substr($custom_var_value, 0, 200));

        // Add variables to tracker.
        $custom_variable .= '_paq.push(["setCustomVariable", ' . Json::encode($i) . ', ' . Json::encode($custom_var_name) . ', ' . Json::encode($custom_var_value) . ', ' . Json::encode($custom_var_scope) . ']);';
      }
    }
    $custom_variable .= '_paq.push(["setCustomVariable", ' . Json::encode(++$i) . ', ' . Json::encode('route') . ', ' . Json::encode($this->currentRouteMatch->getRouteName()) . ', ' . Json::encode('visit') . ']);';
    $custom_variable .= '_paq.push(["setCustomVariable", ' . Json::encode(++$i) . ', ' . Json::encode('path') . ', ' . Json::encode($this->path) . ', ' . Json::encode('visit') . ']);';

    // $current_path = \Drupal::service('path.current')->getPath();
    // Add any custom code snippets if specified.
    $codesnippet_before = $this->config->get('codesnippet.before');
    $codesnippet_after = $this->config->get('codesnippet.after');

    // Build tracker code.
    // @see https://matomo.org/docs/javascript-tracking/#toc-asynchronous-tracking
    $script = 'var _paq = _paq || [];';
    $script .= '(function(){';
    $script .= 'var u=(("https:" == document.location.protocol) ? "' . UrlHelper::filterBadProtocol($url_https) . '" : "' . UrlHelper::filterBadProtocol($url_http) . '");';
    $script .= '_paq.push(["setSiteId", ' . Json::encode($id) . ']);';
    $script .= '_paq.push(["setTrackerUrl", u+"/visitors/_track"]);';

    // Track logged in users across all devices.
    $user_id = 0;
    if ($this->config->get('track.userid')) {
      $user_id = $this->currentUser->id();
    }
    $script .= '_paq.push(["setUserId", ' . $user_id . ']);';
    // Set custom url.
    if (!empty($set_custom_url)) {
      $script .= '_paq.push(["setCustomUrl", ' . $set_custom_url . ']);';
    }
    // Set custom document title.
    if (!empty($set_document_title)) {
      $script .= '_paq.push(["setDocumentTitle", ' . $set_document_title . ']);';
    }

    // Custom file download extensions.
    if ($this->config->get('track.files') && !($this->config->get('track.files_extensions') == VisitorsScriptInterface::TRACKFILES_EXTENSIONS)) {
      $script .= '_paq.push(["setDownloadExtensions", ' . Json::encode($this->config->get('track.files_extensions')) . ']);';
    }

    // Disable tracking cookies.
    if ($this->config->get('privacy.disablecookies')) {
      $script .= '_paq.push(["disableCookies"]);';
    }

    // Domain tracking type.
    $cookie_domain = $this->sessionConfig->getOptions($this->request)['cookie_domain'] ?? '';
    $domain_mode = $this->config->get('domain_mode');

    // Per RFC 2109, cookie domains must contain at least one dot other than the
    // first. For hosts such as 'localhost' or IP Addresses we don't set a
    // cookie domain.
    if ($domain_mode == 1 && count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
      $script .= '_paq.push(["setCookieDomain", ' . Json::encode($cookie_domain) . ']);';
    }

    // Ordering $custom_variable before $codesnippet_before allows users to add
    // custom code snippets that may use deleteCustomVariable() and/or
    // getCustomVariable().
    $script .= $custom_variable;

    if (!empty($codesnippet_before)) {
      $script .= $codesnippet_before;
    }

    // Site search tracking support.
    // NOTE: It's recommended not to call trackPageView() on the Site Search
    // Result page.
    $keys = ($this->request->query->has('keys') ? trim($this->request->get('keys')) : '');
    if (
      $this->moduleHandler->moduleExists('search') &&
      $this->config->get('track.site_search') &&
      (strpos($this->currentRouteMatch->getRouteName(), 'search.view') === 0) &&
      $keys
      ) {
      // Parameters:
      // 1. Search keyword searched for. Example: "Banana"
      // 2. Search category selected in your search engine. If you do not need
      //    this, set to false. Example: "Organic Food"
      // 3. Number of results on the Search results page. Zero indicates a
      //    'No Result Search Keyword'. Set to false if you don't know.
      //
      // hook_preprocess_search_results() is not executed if search result is
      // empty. Make sure the counter is set to 0 if there are no results.
      $script .= '_paq.push(["trackSiteSearch", ' . Json::encode($keys) . ', false, (window.matomo_search_results) ? window.matomo_search_results : 0]);';
    }
    else {
      $script .= 'if (!window.matomo_search_results_active) {_paq.push(["trackPageView"]);}';
    }

    // Add link tracking.
    if ($this->config->get('track.files')) {
      // Disable tracking of links with ".no-tracking" and ".colorbox" classes.
      $ignore_classes = [
        'no-tracking',
        'colorbox',
      ];
      // Disable the download & outbound link tracking for specific CSS classes.
      // Custom code snippets with 'setIgnoreClasses' will override the value.
      // @see https://developer.matomo.org/api-reference/tracking-javascript#disable-the-download-amp-outlink-tracking-for-specific-css-classes
      $script .= '_paq.push(["setIgnoreClasses", ' . Json::encode($ignore_classes) . ']);';

      // Enable download & outlink link tracking.
      $script .= '_paq.push(["enableLinkTracking"]);';
    }

    if (!empty($message_events)) {
      $script .= $message_events;
    }
    if (!empty($codesnippet_after)) {
      $script .= $codesnippet_after;
    }

    $script .= 'var d=document,';
    $script .= 'g=d.createElement("script"),';
    $script .= 's=d.getElementsByTagName("script")[0];';
    $script .= 'g.type="text/javascript";';
    $script .= 'g.defer=true;';
    $script .= 'g.async=true;';

    // Should a local cached copy of the tracking code be used?
    if ($this->config->get('cache')) {
      $url = $url_http . '/modules/contrib/visitors/js/tracker.js';
      if ($url) {
        // A dummy query-string is added to filenames, to gain control over
        // browser-caching. The string changes on every update or full cache
        // flush, forcing browsers to load a new copy of the files, as the
        // URL changed.
        $query_string = '?' . ($this->state->get('system.css_js_query_string') ?: '0');

        $script .= 'g.src="' . $url . $query_string . '";';
      }
    }
    else {
      $script .= 'g.src=u+"/modules/contrib/visitors/js/tracker.js";';
    }

    $script .= 's.parentNode.insertBefore(g,s);';
    $script .= '})();';

    return $script;

  }

  /**
   * {@inheritdoc}
   */
  public function cacheTags(array $tags): array {
    $configTags = $this->config->getCacheTags();

    return Cache::mergeTags($tags, $configTags);
  }

}
