<?php

/**
 * @file
 * Builds placeholder replacement tokens for node visitor statistics.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function visitors_token_info() {
  $entity['total-count'] = [
    'name' => t("Number of views"),
    'description' => t("The number of visitors who have read the node."),
  ];
  $entity['day-count'] = [
    'name' => t("Views today"),
    'description' => t("The number of visitors who have read the node today."),
  ];
  $entity['last-view'] = [
    'name' => t("Last view"),
    'description' => t("The date on which a visitor last read the node."),
    'type' => 'date',
  ];

  $token = [
    'tokens' => [],
  ];
  $entity_types = \Drupal::config('visitors.config')
    ->get('counter.entity_types') ?? [];
  foreach ($entity_types as $entity_type) {
    $token['tokens'][$entity_type] = $entity;
  }

  return $token;
}

/**
 * Implements hook_tokens().
 */
function visitors_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();
  $entity_types = \Drupal::config('visitors.config')
    ->get('counter.entity_types') ?? [];
  $replacements = [];

  if (!in_array($type, $entity_types) || empty($data[$type])) {
    return $replacements;
  }
  $entity = $data[$type];

  /** @var \Drupal\visitors\VisitorsCounterInterface $counter_storage */
  $counter_storage = \Drupal::service('visitors.counter');
  $entity_view = NULL;

  foreach ($tokens as $name => $original) {
    if ($name == 'total-count') {
      $entity_view = $entity_view ?? $counter_storage->fetchView($type, $entity->id());
      $replacements[$original] = $entity_view ? $entity_view->getTotalCount() : 0;
    }
    elseif ($name == 'day-count') {
      $entity_view = $entity_view ?? $counter_storage->fetchView($type, $entity->id());
      $replacements[$original] = $entity_view ? $entity_view->getDayCount() : 0;
    }
    elseif ($name == 'last-view') {
      $entity_view = $entity_view ?? $counter_storage->fetchView($type, $entity->id());
      $replacements[$original] = $entity_view ? \Drupal::service('date.formatter')->format($entity_view->getTimestamp()) : t('never');
    }
  }

  if ($created_tokens = $token_service->findWithPrefix($tokens, 'last-view')) {
    $entity_view = $entity_view ?? $counter_storage->fetchView($type, $entity->id());
    $replacements += $token_service->generate('date', $created_tokens, ['date' => $entity_view ? $entity_view->getTimestamp() : 0], $options, $bubbleable_metadata);
  }

  return $replacements;
}
