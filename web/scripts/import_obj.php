<?php

/**
 * Импорт объектов из CSV для Drupal 11 с PostgreSQL
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\file\FileRepositoryInterface;
use Drupal\Core\File\FileExists;

// Загружаем Drupal

if (!defined('DRUPAL_ROOT')) {
  $drupalRoot = '/var/www/html/web';
  /*if (is_dir($drupalRoot)) {
    chdir($drupalRoot);
  }
  require_once 'autoload.php';*/

  $autoloader = require_once $drupalRoot . '/autoload.php';
  $request = Request::createFromGlobals();
  $kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
  $kernel->boot();
  $kernel->prepareLegacyRequest($request);
}



// Загружаем сервисы
$entity_type_manager = \Drupal::entityTypeManager();
$file_repository = \Drupal::service('file.repository');
$file_system = \Drupal::service('file_system');
$database = \Drupal::database();

// Настройки
$csv_file = __DIR__ . '/export_objects.csv';
$remote_image_prefix = 'https://xn--80akpjgfht4a0d.xn--80acgfbsl1azdqr.xn--p1ai/';

echo "=== ИМПОРТ ОБЪЕКТОВ (Drupal 11 + PostgreSQL) ===\n";
echo "Тип БД: " . $database->databaseType() . "\n";
echo "Файл: $csv_file\n\n";

// Читаем CSV
echo "ШАГ 1: Чтение CSV...\n";

if (!file_exists($csv_file)) {
  die("ОШИБКА: Файл $csv_file не найден!\n");
}

$handle = fopen($csv_file, 'r');
if (!$handle) {
  die("ОШИБКА: Не удалось открыть файл!\n");
}

$objects_data = [];
$row_number = 0;

while (($data = fgetcsv($handle, 0, ';')) !== FALSE) {
  $row_number++;

  if (count($data) < 8) {
    echo "Предупреждение: строка $row_number содержит " . count($data) . " колонок\n";
    continue;
  }

  list(
    $name,
    $xml_id,
    $meropriyatie_old_id,
    $cost,
    $srok,
    $address,
    $image_path,
    $coords
    ) = $data;

  $name = trim($name);
  $xml_id = trim($xml_id);
  $meropriyatie_old_id = trim($meropriyatie_old_id);
  $cost = trim($cost);
  $srok = trim($srok);
  $address = trim($address);
  $image_path = trim($image_path);
  $coords = trim($coords);

  if (empty($xml_id) || empty($name)) {
    continue;
  }

  if (!isset($objects_data[$xml_id])) {
    $objects_data[$xml_id] = [
      'name' => $name,
      'xml_id' => $xml_id,
      'meropriyatie_old_id' => $meropriyatie_old_id,
      'cost' => $cost,
      'srok' => $srok,
      'address' => $address,
      'coords' => $coords,
      'images' => [],
    ];
  }

  if (!empty($image_path) && $image_path != ';') {
    $full_image_url = $remote_image_prefix . $image_path;
    $objects_data[$xml_id]['images'][] = $full_image_url;
  }
}

fclose($handle);

echo "Найдено объектов: " . count($objects_data) . "\n\n";

// Построение карты мероприятий (PostgreSQL-оптимизировано)
echo "ШАГ 2: Загрузка мероприятий...\n";

$activity_map = [];

// Для PostgreSQL используем прямой запрос к БД (быстрее и надёжнее)
try {
  $query = $database->select('node__field_xml_id', 'n')
    ->fields('n', ['entity_id', 'field_xml_id_value'])
    ->condition('bundle', 'meropriyatie')
    ->condition('deleted', 0);

  $results = $query->execute();

  foreach ($results as $record) {
    if (!empty($record->field_xml_id_value)) {
      $activity_map[$record->field_xml_id_value] = $record->entity_id;
    }
  }

  echo "Найдено мероприятий: " . count($activity_map) . "\n\n";

} catch (\Exception $e) {
  echo "ОШИБКА при загрузке мероприятий: " . $e->getMessage() . "\n";
  die();
}

// Импорт объектов
echo "ШАГ 3: Импорт объектов...\n";

$created = 0;
$updated = 0;
$errors = 0;
$processed = 0;

foreach ($objects_data as $xml_id => $data) {
  $processed++;

  if ($processed % 10 == 0) {
    echo "Прогресс: $processed из " . count($objects_data) . "\n";
  }

  // Проверка существования объекта (PostgreSQL-совместимо)
  $query = $database->select('node__field_xml_id', 'n')
    ->fields('n', ['entity_id'])
    ->condition('bundle', 'object')
    ->condition('field_xml_id_value', $xml_id)
    ->condition('deleted', 0);

  $existing_nids = $query->execute()->fetchCol();
  $is_new = empty($existing_nids);

  try {
    if ($is_new) {
      $node = Node::create([
        'type' => 'object',
        'title' => $data['name'],
        'status' => 1,
      ]);
    } else {
      $nid = reset($existing_nids);
      $node = Node::load($nid);
      if (!$node) {
        echo "ОШИБКА: Не удалось загрузить объект nid=$nid\n";
        $errors++;
        continue;
      }
    }

    // Заполнение полей
    $node->set('field_xml_id', $data['xml_id']);

    if ($node->hasField('field_cost')) {
      $node->set('field_cost', $data['cost']);
    }

    if ($node->hasField('field_srok')) {
      $node->set('field_srok', $data['srok']);
    }

    if ($node->hasField('field_address')) {
      $node->set('field_address', $data['address']);
    }

    if ($node->hasField('field_coords')) {
      $node->set('field_coords', $data['coords']);
    }

    // Привязка к мероприятию
    if (!empty($data['meropriyatie_old_id']) && isset($activity_map[$data['meropriyatie_old_id']])) {
      if ($node->hasField('field_meropriyatie')) {
        $node->set('field_meropriyatie', $activity_map[$data['meropriyatie_old_id']]);
      }
    } elseif (!empty($data['meropriyatie_old_id'])) {
      echo "Предупреждение: для {$data['name']} не найдено мероприятие old_id={$data['meropriyatie_old_id']}\n";
    }

    $node->save();

    // Загрузка картинок (адаптировано для PostgreSQL)
    if (!empty($data['images']) && $node->hasField('field_foto')) {
      $image_fids = [];
      foreach ($data['images'] as $image_url) {
        $fid = download_and_attach_image_pgsql(
          $image_url,
          $node->id(),
          $file_repository,
          $file_system,
          $database
        );
        if ($fid) {
          $image_fids[] = ['target_id' => $fid];
        }
      }

      if (!empty($image_fids)) {
        $node->set('field_foto', $image_fids);
        $node->save();
      }
    }

    if ($is_new) {
      $created++;
      echo "✓ Создан: {$data['name']} (ID={$node->id()}, картинок=" . count($data['images']) . ")\n";
    } else {
      $updated++;
      echo "✓ Обновлён: {$data['name']} (ID={$node->id()}, картинок=" . count($data['images']) . ")\n";
    }

  } catch (\Exception $e) {
    echo "ОШИБКА: {$data['name']} - " . $e->getMessage() . "\n";
    $errors++;
  }
}

echo "\n=== ИТОГИ ===\n";
echo "Создано: $created\n";
echo "Обновлено: $updated\n";
echo "Ошибок: $errors\n";
echo "Всего: $processed\n";

/**
 * Скачивание картинки с учётом PostgreSQL
 */
function download_and_attach_image_pgsql($url, $object_nid, $file_repository, $file_system, $database) {
  // PostgreSQL: используем точное сравнение uri
  $filename_hash = md5($url);

  $query = $database->select('file_managed', 'f')
    ->fields('f', ['fid'])
    ->condition('uri', 'public://objects/' . $filename_hash . '.%', 'LIKE');

  $existing_fids = $query->execute()->fetchCol();

  if (!empty($existing_fids)) {
    return reset($existing_fids);
  }

  // Скачивание
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

  $image_data = curl_exec($ch);
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  curl_close($ch);

  if ($http_code != 200 || empty($image_data)) {
    echo "  ✗ Ошибка: $url (HTTP $http_code)\n";
    return false;
  }

  // Определение расширения
  $extension = 'jpg';
  if (strpos($content_type, 'png') !== false) {
    $extension = 'png';
  } elseif (strpos($content_type, 'gif') !== false) {
    $extension = 'gif';
  } elseif (strpos($content_type, 'webp') !== false) {
    $extension = 'webp';
  } elseif (strpos($content_type, 'jpeg') !== false || strpos($content_type, 'jpg') !== false) {
    $extension = 'jpg';
  }

  $filename = $object_nid . '_' . $filename_hash . '.' . $extension;
  $directory = 'public://objects/' . $object_nid . '/';

  $file_system->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

  $file_uri = $directory . $filename;

  try {
    $file = $file_repository->writeData($image_data, $file_uri, FileExists::Rename);
    if ($file) {
      $file->setPermanent();
      $file->save();

      echo "  ✓ Картинка: " . basename($filename) . " (" . round(strlen($image_data)/1024) . " KB)\n";
      return $file->id();
    }
  } catch (\Exception $e) {
    echo "  ✗ Ошибка сохранения: " . $e->getMessage() . "\n";
  }

  return false;
}
