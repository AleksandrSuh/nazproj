#!/usr/bin/env php
<?php

$url = $argv[1] ?? '';
if (!$url) {
  echo "Использование: php simple_parser.php <url>\n";
  exit(1);
}

function fetchContent($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  $content = curl_exec($ch);
  curl_close($ch);
  return $content;
}

$html = fetchContent($url);
if (!$html) {
  echo "Не удалось загрузить страницу\n";
  exit(1);
}

// Простой парсинг через регулярные выражения
preg_match('/<img[^>]+class="news-one-head-image"[^>]+src="([^"]+)"/', $html, $img);
preg_match('/<p class="news-one-announce">(.*?)<\/p>/s', $html, $announce);

echo json_encode([
  'image' => $img[1] ?? '',
  'announce' => strip_tags($announce[1] ?? ''),
  'size' => strlen($html)
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
