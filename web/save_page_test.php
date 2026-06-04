<?php

$url = 'https://xn--80acgfbsl1azdqr.xn--p1ai/news/ko_dnyu_zashchitnika_otechestva_na_donbass_otpravili_40_tonn_gumanitarnogo_gruza/';

function fetchContent($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
  $content = curl_exec($ch);
  curl_close($ch);
  return $content;
}

$html = fetchContent($url);
if ($html) {
  $filename = '/tmp/saved_page_' . time() . '.html';
  file_put_contents($filename, $html);
  echo "Страница сохранена: " . $filename . "\n";
  echo "Размер: " . strlen($html) . " байт\n";

  // Найдем все img с class
  preg_match_all('/<img[^>]+class="[^"]*news-one-head-image[^"]*"[^>]+>/', $html, $matches);
  echo "Найдено img с классом news-one-head-image: " . count($matches[0]) . "\n";

  // Найдем все p
  preg_match_all('/<p[^>]*>/', $html, $pMatches);
  echo "Всего открывающих тегов p: " . count($pMatches[0]) . "\n";

  // Найдем p с классом
  preg_match_all('/<p[^>]+class="[^"]*news-one-announce[^"]*"[^>]*>/', $html, $announceMatches);
  echo "Найдено p с классом news-one-announce: " . count($announceMatches[0]) . "\n";
} else {
  echo "Не удалось загрузить страницу\n";
}
