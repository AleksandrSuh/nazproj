<?php

use Drupal\node\Entity\Node;
use Drupal\Core\Database\Database;

// Загружаем Drupal
chdir(__DIR__ . '/web');
require_once 'autoload.php';
\Drupal::getContainer()->get('kernel')->boot();

/**
 * Парсим CSV и группируем xml_id бюджетов по названиям мероприятий
 */
function parseOldEventsCsv($filePath) {
  $eventsData = [];

  if (($handle = fopen($filePath, 'r')) !== FALSE) {
    // Пропускаем заголовки
    $headers = fgetcsv($handle, 0, ';');

    while (($row = fgetcsv($handle, 0, ';')) !== FALSE) {
      $eventName = trim($row[0]);      // IE_NAME
      $budgetXmlId = trim($row[3]);     // IP_PROP34 (xml_id из таблицы budget)

      if (empty($eventName) || empty($budgetXmlId) || !is_numeric($budgetXmlId)) {
        continue;
      }

      if (!isset($eventsData[$eventName])) {
        $eventsData[$eventName] = [
          'budget_xml_ids' => []
        ];
      }

      // Собираем уникальные xml_id для каждого мероприятия
      if (!in_array($budgetXmlId, $eventsData[$eventName]['budget_xml_ids'])) {
        $eventsData[$eventName]['budget_xml_ids'][] = $budgetXmlId;
      }
    }
    fclose($handle);
  }

  return $eventsData;
}

/**
 * Из таблицы budget получаем ID записей по их xml_id
 * Возвращает массив [xml_id => budget_table_id]
 */
function getBudgetIdsByXmlIds(array $xmlIds) {
  if (empty($xmlIds)) {
    return [];
  }

  $database = Database::getConnection();

  $query = $database->select('budget', 'b')
    ->fields('b', ['id', 'xml_id'])
    ->condition('b.xml_id', $xmlIds, 'IN');

  $result = $query->execute();

  $budgetMap = [];
  foreach ($result as $row) {
    $budgetMap[$row->xml_id] = $row->id;
  }

  return $budgetMap;
}

/**
 * Ищем мероприятие по названию
 */
function findEventNodeByName($eventName) {
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'meropriyatie')  // Уточните тип материала!
    ->condition('title', $eventName)
    ->accessCheck(FALSE)
    ->range(0, 1);

  $result = $query->execute();

  if (!empty($result)) {
    return Node::load(reset($result));
  }

  return null;
}

/**
 * Основной процесс импорта
 */
function attachBudgetsToEvents($csvPath) {
  echo "=== Привязка бюджетов к мероприятиям ===\n";
  echo "Поле field_budget_ids будет заполнено ID через запятую\n\n";

  // 1. Парсим CSV
  echo "📁 Чтение CSV файла...\n";
  $eventsData = parseOldEventsCsv($csvPath);
  echo "📊 Найдено уникальных мероприятий в CSV: " . count($eventsData) . "\n\n";

  $stats = [
    'total' => count($eventsData),
    'found_events' => 0,
    'updated' => 0,
    'not_found_event' => 0,
    'total_budget_links' => 0
  ];

  // 2. Обрабатываем каждое мероприятие
  $counter = 0;
  foreach ($eventsData as $eventName => $data) {
    $counter++;
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "[{$counter}/{$stats['total']}] 📌 " . mb_substr($eventName, 0, 70) . "\n";

    // Ищем мероприятие в Drupal
    $eventNode = findEventNodeByName($eventName);

    if (!$eventNode) {
      echo "   ❌ Мероприятие не найдено!\n";
      $stats['not_found_event']++;
      continue;
    }

    $stats['found_events']++;
    echo "   ✅ Найдено мероприятие (nid: {$eventNode->id()})\n";

    // Получаем ID из таблицы budget по xml_id
    $budgetXmlIds = $data['budget_xml_ids'];
    echo "   📋 XML_id бюджетов: " . implode(', ', $budgetXmlIds) . "\n";

    $budgetMap = getBudgetIdsByXmlIds($budgetXmlIds);

    if (empty($budgetMap)) {
      echo "   ⚠️ Ни одного бюджета не найдено в таблице budget!\n";
      continue;
    }

    // Формируем строку с ID через запятую
    $budgetIdsString = implode(',', array_values($budgetMap));

    echo "   💰 Найдено бюджетов: " . count($budgetMap) . "\n";
    echo "   🆔 ID бюджетов (через запятую): {$budgetIdsString}\n";

    // Обновляем поле мероприятия
    try {
      /*$eventNode->set('field_budget_ids', $budgetIdsString);
      $eventNode->save();

      echo "   ✅ СОХРАНЕНО!\n";*/
      $stats['updated']++;
      $stats['total_budget_links'] += count($budgetMap);

    } catch (\Exception $e) {
      echo "   ❌ Ошибка сохранения: " . $e->getMessage() . "\n";
    }
  }

  // Финальная статистика
  echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
  echo "=== СТАТИСТИКА ИМПОРТА ===\n";
  echo "Всего мероприятий в CSV:       {$stats['total']}\n";
  echo "Найдено в Drupal:               {$stats['found_events']}\n";
  echo "Успешно обновлено:              {$stats['updated']}\n";
  echo "Не найдено мероприятий:         {$stats['not_found_event']}\n";
  echo "Всего связей (бюджет→мероприятие): {$stats['total_budget_links']}\n";
}

// Запуск скрипта
$csvPath = __DIR__ . '/old_events_export.csv';

if (!file_exists($csvPath)) {
  die("Ошибка: CSV файл не найден по пути: {$csvPath}\n");
}

attachBudgetsToEvents($csvPath);
