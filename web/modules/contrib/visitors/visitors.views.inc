<?php

/**
 * @file
 * Provide views data for visitors.module.
 */

/**
 * Implements hook_views_data().
 */
function visitors_views_data() {
  $data = [];

  // Base data.
  $data['visitors']['table']['group'] = t('Visitors');
  $data['visitors']['table']['base'] = [
    'title' => t('Visitors'),
    'help' => t('Visitors data from visitors DB table.'),
  ];

  // Fields.
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

  return $data;
}
