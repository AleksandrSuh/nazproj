<?php
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;

$autoload = require __DIR__ . '/autoload.php';
$kernel = DrupalKernel::createFromRequest(Request::createFromGlobals(), $autoload, 'prod');
$kernel->boot();

echo "=== Test Pathauto Save ===\n";

// Находим последнюю новость
$nodes = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['type' => 'news', 'status' => 1], ['created' => 'DESC'], 1, 0);

$node = reset($nodes);

if (!$node) {
  echo "Новость не найдена\n";
  exit(1);
}

echo "Название: " . $node->getTitle() . "\n";
echo "ID: " . $node->id() . "\n";
echo "Текущий URL: " . $node->toUrl()->toString() . "\n";
/*
// Проверяем настройки Pathauto
echo "\n=== Pathauto settings ===\n";
echo "pathauto state: " . (property_exists($node->path, 'pathauto') ? $node->path->pathauto : 'NOT SET') . "\n";

// Принудительно устанавливаем автоматическую генерацию
use Drupal\pathauto\PathautoState;
$node->path->pathauto = PathautoState::CREATE;

echo "Установлен pathauto = CREATE\n";

// Сохраняем
$node->save();
*/
echo "Новость сохранена\n";
echo "Новый URL: " . $node->toUrl()->toString() . "\n";

// Проверяем синоним
$alias = \Drupal::database()->select('path_alias', 'pa')
  ->fields('pa', ['alias'])
  ->condition('entity_id', $node->id())
  ->condition('entity_type', 'node')
  ->execute()
  ->fetchField();

echo "Синоним в базе: " . ($alias ? $alias : 'НЕ НАЙДЕН') . "\n";
