<?php

$log_dir = __DIR__ . '/news_cache/logs';

if (!is_dir($log_dir)) {
  mkdir($log_dir, 0755, true);
}

//$log_file = $log_dir . '/cron_test.txt';
//file_put_contents($log_file, 'Записано '.date('Y-m-d H:i:s') . "\n", FILE_APPEND);
//exit();

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;


$autoloader = require_once __DIR__ . '/autoload.php';
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->preHandle($request);

/*
$exists = class_exists('Drupal\mynews\NewsFetcher');
file_put_contents($log_file, "NewsFetcher существует: " . ($exists ? 'ДА' : 'НЕТ') . "\n", FILE_APPEND);

// Пробуем найти файл класса напрямую
$class_file = __DIR__ . '/modules/custom/mynews/src/NewsFetcher.php';
file_put_contents($log_file, "Файл класса существует: " . (file_exists($class_file) ? 'ДА' : 'НЕТ') . "\n", FILE_APPEND);

if (file_exists($class_file)) {
  file_put_contents($log_file, "Путь к файлу: $class_file\n", FILE_APPEND);
}*/

use Drupal\mynews\NewsFetcher;

$timestamp = date('Y-m-d H:i:s');
try {
  $fetcher = new NewsFetcher();
  $result = $fetcher->updateNews();

  $log_message = "[$timestamp] Результат: " . ($result ? 'успешно' : 'ошибка') . "\n";
  file_put_contents($log_dir . '/cron_news.log', $log_message);//, FILE_APPEND);

  if ($result) {
    echo "[$timestamp] Новости обновлены успешно\n";
  } else {
    echo "[$timestamp] Ошибка обновления новостей\n";
  }
} catch (Exception $e) {
  $log_message = "[$timestamp] Исключение: " . $e->getMessage() . "\n";
  //file_put_contents($log_dir . '/cron_news.log', $log_message, FILE_APPEND);
  echo "[$timestamp] Ошибка: " . $e->getMessage() . "\n";
}
