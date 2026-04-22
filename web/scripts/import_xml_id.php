<?php

use Drupal\node\Entity\Node;

// Загружаем Drupal
if (!defined('DRUPAL_ROOT')) {
  $drupalRoot = '/var/www/html/web';
  if (is_dir($drupalRoot)) {
    chdir($drupalRoot);
  }
  require_once 'autoload.php';
}
\Drupal::getContainer()->get('kernel')->boot();

/**
 * Парсим CSV и группируем xml_id по названиям мероприятий
 */
function parseOldEventsCsv($filePath) {
  if (!file_exists($filePath)) {
    return false;
  }

  $eventsData = [];

  if (($handle = fopen($filePath, 'r')) !== FALSE) {
    $headers = fgetcsv($handle, 0, ';');

    while (($row = fgetcsv($handle, 0, ';')) !== FALSE) {
      $eventName = trim($row[0]);      // IE_NAME
      $eventXmlId = trim($row[1]);      // IE_ID (старый ID мероприятия)

      if (empty($eventName) || empty($eventXmlId) || !is_numeric($eventXmlId)) {
        continue;
      }

      // Группируем по названию
      if (!isset($eventsData[$eventName])) {
        $eventsData[$eventName] = [
          'xml_ids' => []
        ];
      }

      // Собираем уникальные xml_id для каждого названия
      if (!in_array($eventXmlId, $eventsData[$eventName]['xml_ids'])) {
        $eventsData[$eventName]['xml_ids'][] = $eventXmlId;
      }
    }
    fclose($handle);
  }

  return $eventsData;
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

/**
 * Проверяем, есть ли уже заполненное поле field_xml_id
 */
function getExistingXmlId($eventNode) {
  if ($eventNode->hasField('field_xml_id') && !$eventNode->get('field_xml_id')->isEmpty()) {
    return $eventNode->get('field_xml_id')->value;
  }
  return null;
}

// ===== ОСНОВНОЙ ПРОЦЕСС =====
$csvPath = __DIR__ . '/old_events_export.csv';

if (!file_exists($csvPath)) {
  die("❌ CSV файл не найден: $csvPath\n");
}

echo "=== ИМПОРТ FIELD_XML_ID ДЛЯ МЕРОПРИЯТИЙ ===\n";
echo "📁 CSV: $csvPath\n\n";

$eventsData = parseOldEventsCsv($csvPath);

if (!$eventsData || empty($eventsData)) {
  die("❌ Не удалось прочитать CSV\n");
}

// Подсчитываем статистику по конфликтам
$totalNames = count($eventsData);
$conflictCount = 0;
foreach ($eventsData as $eventName => $data) {
  if (count($data['xml_ids']) > 1) {
    $conflictCount++;
  }
}

echo "📊 Найдено уникальных названий в CSV: $totalNames\n";
echo "📊 Из них с конфликтами (несколько xml_id): $conflictCount\n\n";

$stats = [
  'total_names' => $totalNames,
  'conflicts' => 0,
  'processed' => 0,
  'found_by_title' => 0,
  'found_by_long' => 0,
  'not_found' => 0,
  'already_filled' => 0,
  'updated' => 0,
  'skipped_conflicts' => 0
];

$counter = 0;
foreach ($eventsData as $eventName => $data) {
  $counter++;
  echo "\n" . str_repeat("─", 60) . "\n";
  echo "[{$counter}/{$stats['total_names']}] 📌 " . mb_substr($eventName, 0, 70);
  if (mb_strlen($eventName) > 70) echo "...";
  echo "\n";

  $xmlIds = $data['xml_ids'];

  // Проверяем на конфликт - есть ли разные xml_id для одного названия
  if (count($xmlIds) > 1) {
    echo "   ⚠️ КОНФЛИКТ! Название соответствует разным xml_id:\n";
    foreach ($xmlIds as $xmlId) {
      echo "      • xml_id = {$xmlId}\n";
    }
    echo "   ❌ СКИПАЮ - невозможно определить, какой xml_id правильный\n";
    $stats['conflicts']++;
    $stats['skipped_conflicts']++;
    continue;
  }

  // Если конфликта нет - берем единственный xml_id
  $xmlId = $xmlIds[0];
  echo "   🆔 XML_ID: {$xmlId}\n";

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

  // Проверяем, не заполнено ли уже поле
  $existingXmlId = getExistingXmlId($eventNode);
  if ($existingXmlId !== null) {
    echo "   ⚠️ Поле уже заполнено: field_xml_id = {$existingXmlId}\n";
    if ($existingXmlId == $xmlId) {
      echo "      ✅ Значение совпадает с CSV, пропускаем\n";
      $stats['already_filled']++;
      continue;
    } else {
      echo "      ⚠️ Значение отличается от CSV ({$xmlId}), будет обновлено\n";
    }
  }

  // Обновляем поле
  try {
    $eventNode->set('field_xml_id', $xmlId);
    $eventNode->save();
    echo "   💾 СОХРАНЕНО: field_xml_id = {$xmlId}\n";
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
echo "  - Уже было заполнено:        {$stats['already_filled']}\n";
echo "  - Успешно обновлено:         {$stats['updated']}\n";
echo "\n";
echo "Пропущено из-за конфликтов:    {$stats['skipped_conflicts']}\n";

// Вывод списка конфликтных названий для ручной обработки
if ($stats['conflicts'] > 0) {
  echo "\n" . str_repeat("═", 60) . "\n";
  echo "📋 КОНФЛИКТНЫЕ НАЗВАНИЯ (требуют ручного разбора)\n";
  echo str_repeat("═", 60) . "\n\n";

  $conflictCounter = 0;
  foreach ($eventsData as $eventName => $data) {
    if (count($data['xml_ids']) > 1) {
      $conflictCounter++;
      echo "{$conflictCounter}. Название: \"{$eventName}\"\n";
      echo "   ┌─────────────────────────────────────────\n";
      echo "   ├─ Найдено разных xml_id: " . count($data['xml_ids']) . "\n";
      foreach ($data['xml_ids'] as $xmlId) {
        echo "   ├─ → xml_id: {$xmlId}\n";
      }
      echo "   └─────────────────────────────────────────\n\n";
    }
  }

  echo "💡 ИНСТРУКЦИЯ ПО РУЧНОЙ ОБРАБОТКЕ:\n";
  echo "   1. Найдите мероприятие в админке (/admin/content)\n";
  echo "   2. Откройте редактирование\n";
  echo "   3. В поле 'field_xml_id' вручную введите правильный ID\n";
  echo "   4. Сохраните материал\n";
}

echo "\n✅ Скрипт завершен\n";
