<?php

namespace Drupal\napr_podrazdely;

use Drupal\Core\Database\Connection;

class BudgetService {

  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }


  public function getBudgetDataMeropr($meropr_node) {

    // Получаем мероприятия по id
    $meropriyatiya = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'type' => 'meropriyatie',
        'nid' => $meropr_node->id(),
        'status' => 1,
      ]);
    /*$meropriyatiya = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($meropr_node->id());
    */
      $result = self::DataCalc($meropriyatiya);
      return $result;
  }

  /**
   * Получает данные бюджетов для мероприятий подраздела.
   */
  public function getBudgetDataForSection($section_node) {
    // 1. Получаем все мероприятия подраздела
    $meropriyatiya = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'type' => 'meropriyatie',
        'field_section' => $section_node->id(),
        'status' => 1,
      ]);
    return self::DataCalc($meropriyatiya);
  }

  private function DataCalc($meropriyatiya)
  {

    $result = [];

    foreach ($meropriyatiya as $meropr) {
      //dump($meropr);
      $meropr_id = $meropr->id();
      $budget_ids_field = $meropr->get('field_budget_ids')->value;

      if (empty($budget_ids_field)) {
        $result[$meropr_id] = [];
        continue;
      }

      // 2. Разбираем строку с ID через запятую
      $budget_ids = array_map('trim', explode(',', $budget_ids_field));

      // 3. Загружаем строки из таблицы budget
      $query = $this->database->select('budget', 'b')
        ->fields('b')
        ->condition('id', $budget_ids, 'IN')
        ->condition('active', 1);
      $budget_rows = $query->execute()->fetchAllAssoc('id');


      // 4. Формируем структуру $arMeropr[мероприятие][id_бюджета][данные]
      foreach ($budget_ids as $budget_id) {
        if (isset($budget_rows[$budget_id])) {
          $result[$meropr_id][$budget_id] = $budget_rows[$budget_id];
        }
      }
    }

    return $result;
  }
}
