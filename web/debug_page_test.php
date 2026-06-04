<?php

$url = 'https://xn--80acgfbsl1azdqr.xn--p1ai/news/100893-bessmertnyy-polk-proydet-po-ekaterinburgu';

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

// Сохраняем HTML для анализа
file_put_contents('/tmp/debug.html', $html);
echo "HTML сохранен в /tmp/debug.html\n";

// Ищем все img
preg_match_all('/<img[^>]+>/i', $html, $images);
echo "\n--- ИЗОБРАЖЕНИЯ ---\n";
foreach ($images[0] as $i => $img) {
  if (strpos($img, 'news') !== false) {
    echo ($i+1) . ": " . $img . "\n";
  }
}

// Ищем все p с их классами
preg_match_all('/<p([^>]*)>([^<]*(?:<[^>]+>[^<]*<\/[^>]+>[^<]*)*)<\/p>/is', $html, $paragraphs);
echo "\n--- ПАРАГРАФЫ (первые 10) ---\n";
for ($i = 0; $i < min(10, count($paragraphs[0])); $i++) {
  $attrs = $paragraphs[1][$i];
  $text = strip_tags($paragraphs[0][$i]);
  $text = trim(substr($text, 0, 100));
  echo ($i+1) . ": атрибуты: $attrs\n";
  echo "   текст: $text\n\n";
}

// Ищем div с классом row
preg_match_all('/<div[^>]*class="[^"]*row[^"]*"[^>]*>/i', $html, $rowDivs);
echo "\n--- DIV ROW ---\n";
foreach ($rowDivs[0] as $i => $div) {
  echo ($i+1) . ": $div\n";
}

// Конкретно ищем p class="news-one-announce"
preg_match('/<p[^>]*class="[^"]*news-one-announce[^"]*"[^>]*>(.*?)<\/p>/is', $html, $announce);
echo "\n--- АНОНС (news-one-announce) ---\n";
if (!empty($announce[1])) {
  echo "Найден: " . strip_tags($announce[1]) . "\n";
} else {
  echo "НЕ НАЙДЕН\n";
}

// Ищем блок контента
preg_match('/<div[^>]*class="[^"]*news-one-content[^"]*"[^>]*>(.*?)<\/div>\s*<\div[^>]*class="[^"]*row[^"]*"[^>]*>/is', $html, $content);
echo "\n--- КОНТЕНТ (news-one-content до row) ---\n";
if (!empty($content[1])) {
  $clean = strip_tags($content[1]);
  echo substr($clean, 0, 500) . "\n";
} else {
  echo "НЕ НАЙДЕН\n";
}
