<?php

/**
 * @file
 * Provide Views data for the budget table.
 */

/**
 * Implements hook_views_data().
 */
function connr_view_table_budget_views_data() {
  $data = [];

  // Описываем таблицу budget
  $data['budget']['table']['group'] = t('Budget');
  $data['budget']['table']['base'] = [
    'field' => 'id',           // первичный ключ
    'title' => t('Budget rows'),
    'help' => t('Budget data from XLSX import'),
    'database' => 'default',
  ];

  // Поле ID (первичный ключ)
  $data['budget']['id'] = [
    'title' => t('ID'),
    'help' => t('Budget row ID'),
    'field' => ['id' => 'standard'],
    'filter' => ['id' => 'numeric'],
    'sort' => ['id' => 'standard'],
  ];

  // Поле для отображения: category_code
  $data['budget']['category_code'] = [
    'title' => t('Category code'),
    'help' => t('Budget category code'),
    'field' => ['id' => 'standard'],
    'filter' => ['id' => 'string'],
    'sort' => ['id' => 'standard'],
  ];

  // Поле для отображения: dop_fk
  $data['budget']['dop_fk'] = [
    'title' => t('DOP FK'),
    'help' => t('Additional identifier'),
    'field' => ['id' => 'standard'],
    'filter' => ['id' => 'string'],
    'sort' => ['id' => 'standard'],
  ];

  // Поле для отображения: year
  $data['budget']['year'] = [
    'title' => t('Year'),
    'help' => t('Budget year'),
    'field' => ['id' => 'standard'],
    'filter' => [
      'id' => 'numeric',
      'allow empty' => TRUE,
    ],
    'sort' => ['id' => 'standard'],
  ];

  $data['budget']['extence_plan'] = [
    'title' => t('Extence plan'),
    'help' => t('Общий план расходов'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

// План по федеральному бюджету
  $data['budget']['extence_fed_plan'] = [
    'title' => t('Extence fed plan'),
    'help' => t('План расходов по федеральному бюджету'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

// План по региональному бюджету
  $data['budget']['extence_reg_plan'] = [
    'title' => t('Extence reg plan'),
    'help' => t('План расходов по региональному бюджету'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

// План по муниципальному бюджету
  $data['budget']['extence_mun_plan'] = [
    'title' => t('Extence mun plan'),
    'help' => t('План расходов по муниципальному бюджету'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

// ============================================
// ФАКТ (FACT)
// ============================================

// Общий факт (доходы)
  $data['budget']['income_fact'] = [
    'title' => t('Income fact'),
    'help' => t('Общий факт доходов'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

// Факт по федеральному бюджету
  $data['budget']['income_fed_fact'] = [
    'title' => t('Income fed fact'),
    'help' => t('Факт доходов по федеральному бюджету'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

// Факт по региональному бюджету
  $data['budget']['income_reg_fact'] = [
    'title' => t('Income reg fact'),
    'help' => t('Факт доходов по региональному бюджету'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

// Факт по муниципальному бюджету
  $data['budget']['income_mun_fact'] = [
    'title' => t('Income mun fact'),
    'help' => t('Факт доходов по муниципальному бюджету'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Вспомогательное поле для красивого отображения в выпадающем списке
  // (склеиваем category_code + dop_fk + year)
  $data['budget']['display_title'] = [
    'title' => t('Display title'),
    'help' => t('Combined title for selection'),
    'real field' => 'id',  // используем id как реальное поле
    'field' => [
      'id' => 'custom',
      'click sortable' => FALSE,
    ],
  ];

  return $data;
}

/**
 * Implements hook_views_data_alter().
 *
 * Добавляем возможность фильтрации по году в Entity Reference поле.
 */
function connr_view_table_budget_views_data_alter(&$data) {
  if (isset($data['budget'])) {
    // Для фильтрации по году используем стандартный numeric фильтр
    $data['budget']['year']['filter']['id'] = 'numeric';

    // возможность задавать год по умолчанию
    $data['budget']['year']['filter']['allow empty'] = TRUE;

    $data['budget']['year']['help'] = t('Отфильтруйте строки бюджета по годам. Оставьте поле пустым, чтобы показать все.');
  }
}
