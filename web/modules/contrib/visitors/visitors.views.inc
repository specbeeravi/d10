<?php

/**
 * @file
 * Provide views data for visitors.module.
 */

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Implements hook_views_data().
 */
function visitors_views_data() {
  $data = [];
  $data['visitors_counter']['table']['group'] = t('Visitor counters');
  $data['visitors_counter']['table']['base'] = [
    'title' => t('Visitor Counters'),
    'help' => t('Visitors data from visitors DB table.'),
  ];
  $settings = \Drupal::config('visitors.config');
  $supported_entity_types = $settings->get('counter.entity_types') ?? [];
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if (!in_array($entity_type_id, $supported_entity_types) || !$entity_type->entityClassImplements(ContentEntityInterface::class) || !$entity_type->getBaseTable()) {
      continue;
    }

    $base_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
    $args = ['@entity_type' => $entity_type_id];

    // Multilingual properties are stored in data table.
    if (!($table = $entity_type->getDataTable())) {
      $table = $entity_type->getBaseTable();
    }
    $data[$base_table]['visitors_counter'] = [
      'title' => t('Visitors @entity_type counter', $args),
      'help' => t('Relate all visitor counts on the @entity_type.', $args),
      'relationship' => [
        'group' => t('Visitor Counters'),
        'label' => t('Visitor counters'),
        'base' => 'visitors_counter',
        'base field' => 'entity_id',
        'relationship field' => $entity_type->getKey('id'),
        'id' => 'standard',
        'extra' => [
          [
            'field' => 'entity_type',
            'value' => $entity_type_id,
          ],
        ],
      ],
    ];

    $data['visitors_counter']['table']['join'][$table] = [
      'type' => 'LEFT',
      'left_field' => $entity_type->getKey('id'),
      'field' => 'entity_id',
      'extra' => [
        [
          'field' => 'entity_type',
          'value' => $entity_type_id,
        ],
      ],
    ];

  }

  $data['visitors_counter']['total'] = [
    'title' => t('Total views'),
    'help' => t('The total number of times the node has been viewed.'),
    'field' => [
      'id' => 'visitors_numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['visitors_counter']['today'] = [
    'title' => t('Views today'),
    'help' => t('The total number of times the node has been viewed today.'),
    'field' => [
      'id' => 'visitors_numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['visitors_counter']['timestamp'] = [
    'title' => t('Most recent visit'),
    'help' => t('The most recent time the node has been viewed.'),
    'field' => [
      'id' => 'visitors_counter_timestamp',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'date',
    ],
    'argument' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['visitors']['table']['group'] = t('Visitors');
  $data['visitors']['table']['base'] = [
    'title' => t('Visitors'),
    'help' => t('Visitors data from visitors DB table.'),
  ];

  $data['visitors']['visitors_id'] = [
    'title' => t('Visitors ID'),
    'help' => t('Visitors entry ID.'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];
  $data['visitors']['visitors_uid'] = [
    'title' => t('Visitors UID'),
    'help' => t('The user ID of the visitors entry.'),
    'field' => [
      'id' => 'standard',
    ],
    'relationship' => [
      'title' => t('User'),
      'help' => t('The user entity from the visitor entry.'),
      'base' => 'users_field_data',
      'base field' => 'uid',
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];
  $data['visitors']['visitors_date_time'] = [
    'title' => t('Visitors Date Time'),
    'help' => t('The timestamp from the visitors entry.'),
    'field' => [
      'id' => 'date',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];
  $data['visitors']['visitors_ip'] = [
    'title' => t('Visitors IP'),
    'help' => t('The IP of the visitors entry.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];
  $data['visitors']['visitors_url'] = [
    'title' => t('Visitors URL'),
    'help' => t('The URL of the visitors entry.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];
  $data['visitors']['visitors_referer'] = [
    'title' => t('Visitors referer'),
    'help' => t('The referer of the visitors entry.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];
  $data['visitors']['visitors_path'] = [
    'title' => t('Visitors path'),
    'help' => t('The path of the visitors entry.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];
  $data['visitors']['route'] = [
    'title' => t('Route'),
    'help' => t('The route of the visitors entry.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];
  $data['visitors']['visitors_title'] = [
    'title' => t('Visitors title'),
    'help' => t('The title of the visitors entry.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];
  $data['visitors']['visitors_user_agent'] = [
    'title' => t('Visitors user agent'),
    'help' => t('The user agent of the visitors entry.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];
  $data['visitors']['config_resolution'] = [
    'title' => t('Resolution'),
    'help' => t("The visitor's screen resolution."),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];
  $data['visitors']['config_pdf'] = [
    'title' => t('PDF Support'),
    'help' => t("The visitor's browser supports PDFs."),
    'field' => [
      'id' => 'boolean',
    ],
    'filter' => [
      'id' => 'boolean',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];
  $data['visitors']['config_flash'] = [
    'title' => t('Flash Support'),
    'help' => t("The visitor's browser supports Flash."),
    'field' => [
      'id' => 'boolean',
    ],
    'filter' => [
      'id' => 'boolean',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];
  $data['visitors']['config_java'] = [
    'title' => t('Java Support'),
    'help' => t("The visitor's browser supports Java."),
    'field' => [
      'id' => 'boolean',
    ],
    'filter' => [
      'id' => 'boolean',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];
  $data['visitors']['config_quicktime'] = [
    'title' => t('Quicktime Support'),
    'help' => t("The visitor's browser supports Quicktime."),
    'field' => [
      'id' => 'boolean',
    ],
    'filter' => [
      'id' => 'boolean',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];
  $data['visitors']['config_realplayer'] = [
    'title' => t('Realplayer Support'),
    'help' => t("The visitor's browser supports Realplayer."),
    'field' => [
      'id' => 'boolean',
    ],
    'filter' => [
      'id' => 'boolean',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];
  $data['visitors']['config_windowsmedia'] = [
    'title' => t('Windows Media Support'),
    'help' => t("The visitor's browser supports Windows Media."),
    'field' => [
      'id' => 'boolean',
    ],
    'filter' => [
      'id' => 'boolean',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];
  $data['visitors']['config_silverlight'] = [
    'title' => t('Silverlight Support'),
    'help' => t("The visitor's browser supports Silverlight."),
    'field' => [
      'id' => 'boolean',
    ],
    'filter' => [
      'id' => 'boolean',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];
  $data['visitors']['config_cookie'] = [
    'title' => t('Cookie Support'),
    'help' => t("The visitor's browser supports cookies."),
    'field' => [
      'id' => 'boolean',
    ],
    'filter' => [
      'id' => 'boolean',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  return $data;
}
