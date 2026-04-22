<?php

use Drupal\node\Entity\Node;
use Drupal\Core\Database\Database;

// Загружаем Drupal
if (!defined('DRUPAL_ROOT')) {
  $drupalRoot = '/var/www/html/web';
  if (is_dir($drupalRoot)) {
    chdir($drupalRoot);
  }
  require_once 'autoload.php';
}
\Drupal::getContainer()->get('kernel')->boot();

function parseOldEventsCsv($filePath) {
  if (!file_exists($filePath)) {
    return false;
  }

  $eventsData = [];

  if (($handle = fopen($filePath, 'r')) !== FALSE) {
    $headers = fgetcsv($handle, 0, ';');

    while (($row = fgetcsv($handle, 0, ';')) !== FALSE) {
      $eventName = trim($row[0]);      // IE_NAME
      $eventOldId = trim($row[1]);      // IE_ID (старый ID мероприятия)
      $budgetXmlId = trim($row[3]);     // IP_PROP34

      if (empty($eventName) || empty($budgetXmlId) || !is_numeric($budgetXmlId)) {
        continue;
      }

      // Группируем по названию и по старому ID
      if (!isset($eventsData[$eventName])) {
        $eventsData[$eventName] = [];
      }

      if (!isset($eventsData[$eventName][$eventOldId])) {
        $eventsData[$eventName][$eventOldId] = [
          'old_id' => $eventOldId,
          'budget_xml_ids' => []
        ];
      }

      // Добавляем уникальные xml_id
      if (!in_array($budgetXmlId, $eventsData[$eventName][$eventOldId]['budget_xml_ids'])) {
        $eventsData[$eventName][$eventOldId]['budget_xml_ids'][] = $budgetXmlId;
      }
    }
    fclose($handle);
  }

  return $eventsData;
}

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
 * Ищем мероприятие: сначала по title, потом по field_desc_long
 */
function findEventNodeByName($eventName) {
  // 1. Ищем по обычному названию (title)
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'meropriyatie')
    ->condition('title', $eventName)
    ->accessCheck(FALSE)
    ->range(0, 1);

  $result = $query->execute();

  if (!empty($result)) {
    return Node::load(reset($result));
  }

  // 2. Если не нашли - ищем по полному наименованию (field_desc_long)
  $query2 = \Drupal::entityQuery('node')
    ->condition('type', 'meropriyatie')
    ->condition('field_desc_long', $eventName)
    ->accessCheck(FALSE)
    ->range(0, 1);

  $result2 = $query2->execute();

  if (!empty($result2)) {
    return Node::load(reset($result2));
  }

  return null;
}

// ===== ОСНОВНОЙ ПРОЦЕСС =====
$csvPath = __DIR__ . '/old_events_export.csv';

if (!file_exists($csvPath)) {
  die("❌ CSV файл не найден: $csvPath\n");
}

echo "=== ПРИВЯЗКА БЮДЖЕТОВ К МЕРОПРИЯТИЯМ ===\n";
echo "📁 CSV: $csvPath\n\n";

$eventsData = parseOldEventsCsv($csvPath);

if (!$eventsData || empty($eventsData)) {
  die("❌ Не удалось прочитать CSV\n");
}

// Подсчитываем общее количество вариантов
$totalVariants = 0;
foreach ($eventsData as $eventName => $variants) {
  $totalVariants += count($variants);
}

echo "📊 Найдено уникальных названий в CSV: " . count($eventsData) . "\n";
echo "📊 Из них с конфликтами (несколько old_id): ";
$conflictCount = 0;
foreach ($eventsData as $eventName => $variants) {
  if (count($variants) > 1) {
    $conflictCount++;
  }
}
echo "$conflictCount\n";
echo "📊 Всего вариантов (название+old_id): $totalVariants\n\n";

$stats = [
  'total_names' => count($eventsData),
  'conflicts' => 0,
  'processed' => 0,
  'found_by_title' => 0,
  'found_by_long' => 0,
  'not_found' => 0,
  'updated' => 0,
  'skipped_conflicts' => 0
];

$counter = 0;
foreach ($eventsData as $eventName => $variants) {
  $counter++;
  echo "\n" . str_repeat("─", 60) . "\n";
  echo "[{$counter}/{$stats['total_names']}] 📌 " . mb_substr($eventName, 0, 70);
  if (mb_strlen($eventName) > 70) echo "...";
  echo "\n";

  // Проверяем на конфликт - есть ли разные old_id для одного названия
  if (count($variants) > 1) {
    echo "   ⚠️ КОНФЛИКТ! Название соответствует разным мероприятиям (old_id):\n";
    foreach ($variants as $oldId => $data) {
      echo "      • old_id = {$oldId} (бюджетов: " . count($data['budget_xml_ids']) . ")\n";
    }
    echo "   ❌ СКИПАЮ - невозможно определить, какому мероприятию принадлежат бюджеты\n";
    $stats['conflicts']++;
    $stats['skipped_conflicts']++;
    continue;
  }

  // Если конфликта нет - берем единственный вариант
  $variant = reset($variants);
  $oldId = $variant['old_id'];
  $budgetXmlIds = $variant['budget_xml_ids'];

  echo "   🆔 old_id: {$oldId}\n";
  echo "   📋 XML_id бюджетов (" . count($budgetXmlIds) . " шт): " . implode(', ', array_slice($budgetXmlIds, 0, 5)) . (count($budgetXmlIds) > 5 ? '...' : '') . "\n";

  // Ищем мероприятие в Drupal
  $eventNode = findEventNodeByName($eventName);

  if (!$eventNode) {
    echo "   ❌ НЕ НАЙДЕНО (ни по title, ни по field_desc_long)\n";
    $stats['not_found']++;
    continue;
  }

  $stats['processed']++;

  // Определяем, по какому полю нашли
  if ($eventNode->getTitle() == $eventName) {
    echo "   ✅ Найдено по title (nid: {$eventNode->id()})\n";
    $stats['found_by_title']++;
  } else {
    echo "   ✅ Найдено по field_desc_long (nid: {$eventNode->id()})\n";
    $stats['found_by_long']++;
  }

  // Получаем ID бюджетов из таблицы budget
  $budgetMap = getBudgetIdsByXmlIds($budgetXmlIds);

  if (empty($budgetMap)) {
    $notFoundXmlIds = array_diff($budgetXmlIds, array_keys($budgetMap));
    echo "   ⚠️ Бюджеты не найдены в таблице budget для xml_id: " . implode(', ', $notFoundXmlIds) . "\n";
    continue;
  }

  $budgetIdsString = implode(',', array_values($budgetMap));
  echo "   💰 Найдено в budget (" . count($budgetMap) . " шт): {$budgetIdsString}\n";

  // Обновляем поле
  try {
    $eventNode->set('field_budget_ids', $budgetIdsString);
    $eventNode->save();
    echo "   💾 СОХРАНЕНО\n";
    $stats['updated']++;
  } catch (\Exception $e) {
    echo "   ❌ Ошибка сохранения: " . $e->getMessage() . "\n";
  }
}

// Финальная статистика
echo "\n" . str_repeat("═", 60) . "\n";
echo "=== СТАТИСТИКА ===\n";
echo "Уникальных названий в CSV:     {$stats['total_names']}\n";
echo "Из них с конфликтами:          {$stats['conflicts']}\n";
echo "Без конфликтов:                " . ($stats['total_names'] - $stats['conflicts']) . "\n";
echo "\n";
echo "Обработано (без конфликтов):   {$stats['processed']}\n";
echo "  - Найдено по title:          {$stats['found_by_title']}\n";
echo "  - Найдено по field_desc_long: {$stats['found_by_long']}\n";
echo "  - Не найдено:                {$stats['not_found']}\n";
echo "  - Успешно обновлено:         {$stats['updated']}\n";
echo "\n";
echo "Пропущено из-за конфликтов:    {$stats['skipped_conflicts']}\n";

// Вывод списка конфликтных названий для ручной обработки
if ($stats['conflicts'] > 0) {
  echo "\n" . str_repeat("═", 60) . "\n";
  echo "📋 КОНФЛИКТНЫЕ НАЗВАНИЯ (требуют ручного разбора):\n";
  echo "   Для каждого варианта указаны ID бюджетов, которые можно скопировать и вставить в поле field_budget_ids\n\n";

  foreach ($eventsData as $eventName => $variants) {
    if (count($variants) > 1) {
      echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
      echo "📌 Название: \"{$eventName}\"\n\n";

      foreach ($variants as $oldId => $data) {
        // Получаем ID бюджетов для этого варианта
        $budgetXmlIds = $data['budget_xml_ids'];
        $budgetMap = getBudgetIdsByXmlIds($budgetXmlIds);

        echo "   Вариант old_id: {$oldId}\n";
        echo "     - Количество бюджетов: " . count($data['budget_xml_ids']) . "\n";
        echo "     - XML_id: " . implode(', ', $budgetXmlIds) . "\n";

        if (!empty($budgetMap)) {
          $budgetIdsString = implode(',', array_values($budgetMap));
          echo "     - 💰 Найдено в budget (" . count($budgetMap) . " шт): {$budgetIdsString}\n";
          echo "     - 📋 Скопируйте для вставки: {$budgetIdsString}\n";
        } else {
          echo "     - ⚠️ Бюджеты не найдены в таблице budget\n";
        }
        echo "\n";
      }
    }
  }

  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
  echo "💡 Инструкция:\n";
  echo "   1. Найдите в админке мероприятие с таким названием\n";
  echo "   2. В поле 'field_budget_ids' вставьте нужную строку с ID через запятую\n";
  echo "   3. Если у названия несколько вариантов - определите, какой из них правильный\n";
}
