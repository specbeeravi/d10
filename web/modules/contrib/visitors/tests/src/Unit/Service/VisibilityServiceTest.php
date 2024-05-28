<?php

namespace Drupal\Tests\visitors\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserDataInterface;
use Drupal\visitors\Service\VisibilityService;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the VisibilityService class.
 *
 * @coversDefaultClass \Drupal\visitors\Service\VisibilityService
 *
 * @group visitors
 */
class VisibilityServiceTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The mocked current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentPathStack;

  /**
   * The mocked alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $aliasManager;

  /**
   * The mocked path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcher|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pathMatcher;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked user data service.
   *
   * @var \Drupal\user\UserDataInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $userData;

  /**
   * The visibility service under test.
   *
   * @var \Drupal\visitors\Service\VisibilityService
   */
  protected $visibilityService;

  /**
   * The mocked request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stack;

  /**
   * The mocked account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $accountProxy;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->currentPathStack = $this->createMock(CurrentPathStack::class);
    $this->aliasManager = $this->createMock(AliasManagerInterface::class);
    $this->pathMatcher = $this->createMock(PathMatcher::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->userData = $this->createMock(UserDataInterface::class);
    $this->stack = $this->createMock(RequestStack::class);
    $this->accountProxy = $this->createMock(AccountProxyInterface::class);

  }

  /**
   * Tests the user() method.
   *
   * @dataProvider userDataProvider
   */
  public function testUser($accountRoles, $visibilityConfig, $userData, $expectedResult) {
    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->any())
      ->method('getRoles')
      ->willReturn($accountRoles);

    $config = $this->createMock('\Drupal\Core\Config\ImmutableConfig');
    $config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['visibility.exclude_user1', TRUE],
        ['visibility.user_role_mode', $visibilityConfig],
        ['visibility.user_role_roles', $accountRoles],
        ['visibility.user_account_mode', ''],
      ]);

    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('visitors.config')
      ->willReturn($config);

    $this->userData->expects($this->any())
      ->method('get')
      ->with('visitors', $account->id())
      ->willReturn($userData);

    $this->visibilityService = new VisibilityService(
      $this->configFactory,
      $this->currentPathStack,
      $this->aliasManager,
      $this->pathMatcher,
      $this->moduleHandler,
      $this->userData,
      $this->stack,
      $this->accountProxy
    );

    $result = $this->visibilityService->user($account);
    $this->assertEquals($expectedResult, $result);
  }

  /**
   * Provides test data for the testUser() method.
   */
  public function userDataProvider() {
    return [
      // User is a member of a tracked role and user account mode is 0.
      [
        ['anonymous', 'authenticated', 'editor'],
        0,
        [],
        FALSE,
      ],
      // User is a member of a tracked role and user account mode is 1.
      [
        ['anonymous', 'authenticated', 'editor'],
        1,
        [],
        FALSE,
      ],
      // User is a member of a tracked role and user account mode is 2.
      [
        ['anonymous', 'authenticated', 'editor'],
        2,
        [],
        FALSE,
      ],
      // User is not a member of a tracked role and user account mode is 0.
      [
        ['anonymous', 'authenticated'],
        0,
        [],
        FALSE,
      ],
      // User is not a member of a tracked role and user account mode is 1.
      [
        ['anonymous', 'authenticated'],
        1,
        [],
        FALSE,
      ],
      // User is not a member of a tracked role and user account mode is 2.
      [
        ['anonymous', 'authenticated'],
        2,
        [],
        FALSE,
      ],
      // User is a member of a tracked role and user account mode is 1, and user
      // data has 'user_account_users' set to TRUE.
      [
        ['anonymous', 'authenticated', 'editor'],
        1,
        ['user_account_users' => TRUE],
        FALSE,
      ],
      // User is a member of a tracked role and user account mode is 1, and user
      // data has 'user_account_users' set to FALSE.
      [
        ['anonymous', 'authenticated', 'editor'],
        1,
        ['user_account_users' => FALSE],
        FALSE,
      ],
    ];
  }

  /**
   * Tests the page() method.
   *
   * @dataProvider pageDataProvider
   */
  public function testPage($visibilityConfig, $path, $aliasPath, $pathMatcherResult, $moduleExists, $expectedResult) {
    $config = $this->createMock('\Drupal\Core\Config\ImmutableConfig');
    $config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['visibility.request_path_mode', $visibilityConfig],
        ['visibility.request_path_pages', ''],
      ]);

    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('visitors.config')
      ->willReturn($config);

    $this->currentPathStack->expects($this->any())
      ->method('getPath')
      ->willReturn($path);

    $this->aliasManager->expects($this->any())
      ->method('getAliasByPath')
      ->with($path)
      ->willReturn($aliasPath);

    $this->pathMatcher->expects($this->any())
      ->method('matchPath')
      ->with($aliasPath, $pathMatcherResult)
      ->willReturn($pathMatcherResult);

    $this->moduleHandler->expects($this->any())
      ->method('moduleExists')
      ->with('php')
      ->willReturn($moduleExists);

    $this->visibilityService = new VisibilityService(
      $this->configFactory,
      $this->currentPathStack,
      $this->aliasManager,
      $this->pathMatcher,
      $this->moduleHandler,
      $this->userData,
      $this->stack,
      $this->accountProxy
    );

    $result = $this->visibilityService->page();
    $this->assertEquals($expectedResult, $result);
  }

  /**
   * Provides test data for the testPage() method.
   */
  public function pageDataProvider() {
    return [
      // Visibility request path mode is 0, page match is TRUE.
      [
        0,
        '/page1',
        '/alias1',
        TRUE,
        FALSE,
        TRUE,
      ],
      // Visibility request path mode is 0, page match is FALSE.
      [
        0,
        '/page1',
        '/alias1',
        FALSE,
        FALSE,
        TRUE,
      ],
      // Visibility request path mode is 1, page match is TRUE.
      [
        1,
        '/page1',
        '/alias1',
        TRUE,
        FALSE,
        TRUE,
      ],
      // Visibility request path mode is 1, page match is FALSE.
      [
        1,
        '/page1',
        '/alias1',
        FALSE,
        FALSE,
        TRUE,
      ],
      // Visibility request path mode is 2, page match is TRUE.
      [
        2,
        '/page1',
        '/alias1',
        TRUE,
        FALSE,
        TRUE,
      ],
      // Visibility request path mode is 2, page match is FALSE.
      [
        2,
        '/page1',
        '/alias1',
        FALSE,
        FALSE,
        TRUE,
      ],
      // Visibility request path mode is 0, page match is TRUE,
      // PHP module exists.
      [
        0,
        '/page1',
        '/alias1',
        TRUE,
        TRUE,
        TRUE,
      ],
      // Visibility request path mode is 1, page match is TRUE,
      // PHP module exists.
      [
        1,
        '/page1',
        '/alias1',
        TRUE,
        TRUE,
        TRUE,
      ],
      // Visibility request path mode is 2, page match is TRUE,
      // PHP module exists.
      [
        2,
        '/page1',
        '/alias1',
        TRUE,
        TRUE,
        TRUE,
      ],
    ];
  }

  /**
   * Tests the roles() method.
   *
   * @dataProvider rolesDataProvider
   */
  public function testRoles($accountRoles, $visibilityConfig, $expectedResult) {
    $this->accountProxy->expects($this->any())
      ->method('getRoles')
      ->willReturn($accountRoles);

    $config = $this->createMock('\Drupal\Core\Config\ImmutableConfig');
    $config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['visibility.user_role_mode', $visibilityConfig],
        ['visibility.user_account_mode', $visibilityConfig],
      ]);
    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('visitors.config')
      ->willReturn($config);

    $this->visibilityService = new VisibilityService(
      $this->configFactory,
      $this->currentPathStack,
      $this->aliasManager,
      $this->pathMatcher,
      $this->moduleHandler,
      $this->userData,
      $this->stack,
      $this->accountProxy
    );

    $result = $this->visibilityService->roles($this->accountProxy);
    $this->assertEquals($expectedResult, $result);
  }

  /**
   * Provides test data for the testRoles() method.
   */
  public function rolesDataProvider() {
    return [
      // User is a member of a tracked role and user role mode is 0.
      [
        ['anonymous', 'authenticated', 'editor'],
        0,
        TRUE,
      ],
      // User is a member of a tracked role and user role mode is 1.
      [
        ['anonymous', 'authenticated', 'editor'],
        1,
        TRUE,
      ],
      // User is a member of a tracked role and user role mode is 2.
      [
        ['anonymous', 'authenticated', 'editor'],
        2,
        TRUE,
      ],
      // User is not a member of a tracked role and user role mode is 0.
      [
        ['anonymous', 'authenticated'],
        0,
        TRUE,
      ],
      // User is not a member of a tracked role and user role mode is 1.
      [
        ['anonymous', 'authenticated'],
        1,
        TRUE,
      ],
      // User is not a member of a tracked role and user role mode is 2.
      [
        ['anonymous', 'authenticated'],
        2,
        TRUE,
      ],
    ];
  }

}
