<?php

namespace Drupal\webform_booking\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Access\WebformAccessResult;

/**
 * Provides a custom access checker for cancel booking routes.
 */
class CancelBookingAccessCheck {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new RedirectDestination instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * Custom access check for the cancel booking route.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform object.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account performing the access check.
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkCancelBookingAccess(WebformInterface $webform, WebformSubmissionInterface $webform_submission, AccountInterface $account, Route $route): AccessResult {
    if ($account->hasPermission('cancel all webform bookings')) {
      return WebformAccessResult::allowed($webform_submission, TRUE);
    }

    // Allow (secure) token to bypass default access check.
    $token = $this->requestStack->getCurrentRequest()->query->get('token');
    if ($token && $webform->isOpen()) {
      if ($token === $webform_submission->getToken()) {
        return WebformAccessResult::allowed($webform_submission)->addCacheContexts(['url']);
      }
    }

    return WebformAccessResult::neutral($webform_submission)->addCacheContexts(['url']);
  }
}
