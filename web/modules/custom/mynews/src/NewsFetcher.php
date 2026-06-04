<?php

namespace Drupal\mynews;

use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\pathauto\PathautoState;
/**
 * Класс для получения новостей с сайта-донора и сохранения в БД
 */
class NewsFetcher {

  private string $donorUrl;
  private string $logFilePath;
  private int $timeout;

  /**
   * Конструктор
   */
  public function __construct() {
    $this->donorUrl = 'https://xn--80acgfbsl1azdqr.xn--p1ai/news?type=list';

    // Путь для логов (оставляем для отладки)
    $this->logFilePath = '/var/www/html/web/news_cache/logs/news_fetcher.log';
    $this->timeout = 10;

    $this->ensureLogDirectoryExists();
  }

  /**
   * Создание директории для логов
   */
  private function ensureLogDirectoryExists(): void {
    $logDir = dirname($this->logFilePath);
    if (!is_dir($logDir)) {
      mkdir($logDir, 0777, true);
    }
  }

  /**
   * Логирование сообщений
   */
  private function log(string $message, string $type = 'INFO'): void {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
    file_put_contents($this->logFilePath, $logMessage, FILE_APPEND | LOCK_EX);

    // Также логируем в Drupal
    \Drupal::logger('mynews')->$type($message);
  }

  /**
   * Получение HTML страницы новостей
   */
  private function fetchHtml(string $url): ?string {
    $ch = curl_init();

    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS => 5,
      CURLOPT_TIMEOUT => $this->timeout,
      CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; DrupalBot/1.0)',
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_HEADER => false,
      CURLOPT_ENCODING => 'gzip,deflate',
    ]);

    $html = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($error || $httpCode !== 200) {
      $this->log("Ошибка загрузки: HTTP $httpCode, cURL ошибка: $error", 'ERROR');
      return null;
    }

    return $html;
  }

  /**
   * Парсинг HTML и извлечение новостей
   */
  /**
   * Парсинг HTML и извлечение новостей с полной информацией
   */
  private function parseNews(string $html): array {
    $news = [];

    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new \DOMXPath($dom);

    // Ищем все блоки новостей
    $newsItems = $xpath->query("//div[contains(@class, 'news-list')]//div[contains(@class, 'asrow')]");

    $this->log("Найдено блоков новостей: " . $newsItems->length, 'INFO');

    foreach ($newsItems as $item) {
      if (count($news) >= 3) break;

      // === ПОЛУЧАЕМ ССЫЛКУ И ЗАГОЛОВОК ===
      $linkNode = $xpath->query(".//a[contains(@class, 'news-link')]", $item);
      $title = '';
      $donorUrl = '';

      if ($linkNode->length > 0) {
        $title = trim($linkNode->item(0)->nodeValue);
        $donorUrl = $linkNode->item(0)->getAttribute('href');

        // Преобразуем относительные ссылки в абсолютные
        if (strpos($donorUrl, 'http') !== 0) {
          $baseUrl = 'https://xn--80acgfbsl1azdqr.xn--p1ai';
          $donorUrl = rtrim($baseUrl, '/') . '/' . ltrim($donorUrl, '/');
        }
      }

      if ($this->newsExists($donorUrl)) {
        $this->log("Новость уже существует: " . $title, 'INFO');
        continue;
      }

      // === ПОЛУЧАЕМ ДАТУ ===
      $dateNode = $xpath->query(".//span[contains(@class, 'news-date')]", $item);
      $date = $dateNode->length > 0 ? trim($dateNode->item(0)->nodeValue) : '';

      // === ПОЛУЧАЕМ АНОНС (краткое описание) ===
      $summaryNode = $xpath->query(".//p", $item);
      $summary = $summaryNode->length > 0 ? trim($summaryNode->item(0)->nodeValue) : '';


      // === ПОЛНЫЙ НОВОСТ
      $fullData = $this->fetchFullNewsData($donorUrl);

      if (!empty($title) && !empty($donorUrl)) {
        $news[] = [
          'title' => $title,
          'donor_url' => $donorUrl,
          'date' => $date,
          'summary' => $summary,
          'image_url' => $fullData['image_url'],
          'body' => $fullData['body'],
          'timestamp' => time(),
        ];
      }
    }

    $this->log("Всего найдено новых новостей: " . count($news), 'INFO');
    return $news;
  }


  /**
   * Диагностический метод: сохраняет HTML страницы для анализа
   */
  private function debugPageStructure(string $donorUrl): void {
    $html = $this->fetchHtml($donorUrl);
    if ($html) {
      $filename = '/tmp/debug_page_' . time() . '.html';
      file_put_contents($filename, $html);
      $this->log("Страница сохранена для анализа: " . $filename, 'DEBUG');

      // Найдем все параграфы на странице
      $dom = new \DOMDocument();
      libxml_use_internal_errors(true);
      $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
      libxml_clear_errors();
      $xpath = new \DOMXPath($dom);
      $paragraphs = $xpath->query("//p");
      $this->log("Всего параграфов на странице: " . $paragraphs->length, 'DEBUG');

      for ($i = 0; $i < min(5, $paragraphs->length); $i++) {
        $text = trim($paragraphs->item($i)->nodeValue);
        $this->log("Параграф $i: " . substr($text, 0, 100) . "...", 'DEBUG');
      }
    }
  }

  /**
   * Получает полный текст новости и оригинальную картинку со страницы донора
   */
  private function fetchFullNewsData(string $donorUrl): array {

    //$this->debugPageStructure($donorUrl);
    $html = $this->fetchHtml($donorUrl);
    if (!$html) {
      $this->log("Не удалось получить содержимое страницы: " . $donorUrl, 'ERROR');
      return ['body' => '', 'image_url' => ''];
    }

    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new \DOMXPath($dom);

    // === ПОЛУЧАЕМ КАРТИНКУ В ОРИГИНАЛЬНОМ КАЧЕСТВЕ ===
    $imageUrl = '';
    $imgNode = $xpath->query("//img[contains(@class, 'news-one-head-image')]");
    if ($imgNode->length > 0) {
      $src = $imgNode->item(0)->getAttribute('src');
      if (!empty($src)) {
        // Преобразуем относительную ссылку в абсолютную
        if (strpos($src, 'http') !== 0) {
          $baseUrl = 'https://xn--80acgfbsl1azdqr.xn--p1ai';
          $src = rtrim($baseUrl, '/') . '/' . ltrim($src, '/');
        }
        $imageUrl = $src;
      }
    } else {
      $this->log("Картинка с классом 'news-one-head-image' не найдена", 'WARNING');
    }

    // === ПОЛУЧАЕМ ТЕЛО НОВОСТИ ===
    $bodyHtml = '';
    // 1. Находим p class="news-one-announce"
    $announceParagraph = $xpath->query("//p[contains(@class, 'news-one-announce')]");
    if ($announceParagraph->length > 0) {
      $bodyHtml .= $dom->saveHTML($announceParagraph->item(0));
      $this->log("Найден анонс", 'INFO');
    } else {
      $this->log("Анонс не найден", 'WARNING');
    }

    // 2. Добавляем всё, что идёт после, до div class="row"
    $currentNode = $announceParagraph->item(0);
    $foundRow = false;
    $collected = 0;

    while ($currentNode = $currentNode->nextSibling) {
      // Если встретили div с классом row - останавливаемся
      if ($currentNode instanceof \DOMElement &&
        strpos($currentNode->getAttribute('class'), 'row') !== false) {
        $this->log("Достигнут стоп-элемент <div class='row'>, собрано элементов: " . $collected, 'INFO');
        $foundRow = true;
        break;
      }

      // Добавляем элемент если это параграф или другой нужный контент
      if ($currentNode instanceof \DOMElement) {
        $bodyHtml .= $dom->saveHTML($currentNode);
        $collected++;
      }
    }

    if (!$foundRow) {
      $this->log("Стоп-элемент <div class='row'> не найден, собрано элементов: " . $collected, 'WARNING');
    }

    if (empty($bodyHtml)) {
      $this->log("Текст новости не собран", 'ERROR');
    } else {
      $this->log("Успешно собран текст новости: " . strlen($bodyHtml) . " символов", 'INFO');
    }


    return [
      'body' => $bodyHtml,
      'image_url' => $imageUrl,
    ];
  }


  private function downloadAndSaveImage(string $imageUrl): ?int {
    try {
      // Скачиваем
      $ch = curl_init();
      curl_setopt_array($ch, [
        CURLOPT_URL => $imageUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
      ]);
      $imageData = curl_exec($ch);
      curl_close($ch);

      if (empty($imageData)) {
        return null;
      }

      $extension = 'jpg';
      if (preg_match('/\.(png|jpg|jpeg|gif|webp)/i', $imageUrl, $matches)) {
        $extension = strtolower($matches[1]);
        if ($extension === 'jpeg') $extension = 'jpg';
      }

      $filename = 'news_' . time() . '_' . substr(md5($imageUrl), 0, 8) . '.' . $extension;

      // ЖЁСТКИЙ ПУТЬ, КОТОРЫЙ РАНЬШЕ РАБОТАЛ
      $absolutePath = '/var/www/html/web/sites/default/files/news_images/' . $filename;
      $absoluteDir = dirname($absolutePath);

      if (!is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0777, true);
      }

      file_put_contents($absolutePath, $imageData);

      if (!file_exists($absolutePath)) {
        $this->log("Не удалось создать файл: $absolutePath", 'ERROR');
        return null;
      }

      // URI для Drupal
      $uri = 'public://news_images/' . $filename;

      // Создаём сущность файла
      $file = \Drupal\file\Entity\File::create([
        'uri' => $uri,
        'uid' => 1,
        'status' => 1,
        'filename' => $filename,
      ]);
      $file->setPermanent();
      $file->save();

      return $file->id();

    } catch (\Exception $e) {
      $this->log("Ошибка: " . $e->getMessage(), 'ERROR');
      return null;
    }
  }

  /**
   * Проверка, существует ли уже новость с таким URL донора
   */
  private function newsExists(string $donorUrl): bool {
    // Ищем через state API
    /*$states = \Drupal::state()->get('news_donor_urls', []);
    if (in_array($donorUrl, $states)) {
      return true;
    }*/


    // Резервный поиск по полю если оно есть
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'news')
      ->condition('field_donor_url', $donorUrl)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    if (!empty($query)) {
      // Сохраняем в state для быстрого доступа в следующий раз
      //$states[] = $donorUrl;
      //\Drupal::state()->set('news_donor_urls', $states);
      return true;
    }

    return false;
  }

  /**
   * Сохранение новости в базу данных
   */
  private function saveNewsToDatabase(array $newsItem): bool {
    try {
      // Проверяем дубликат
      if ($this->newsExists($newsItem['donor_url'])) {
        $this->log("Новость уже существует: " . $newsItem['title'], 'INFO');
        return false;
      }

      $imageId = null;
      if (!empty($newsItem['image_url'])) {
        $imageId = $this->downloadAndSaveImage($newsItem['image_url']);
        $this->log("Получен imageId: " . ($imageId ?? 'null'), 'INFO');
      }
      // =============================================

      // Создаём материал
      $node = Node::create([
        'type' => 'news',
        'title' => $newsItem['title'],
        'uid' => 1,
        'status' => 1,
        'created' => $newsItem['timestamp'],
        'changed' => $newsItem['timestamp'],
      ]);

      // Поле: дата новости
      if ($node->hasField('field_news_date') && !empty($newsItem['date'])) {
        $formattedDate = $this->formatForDateField($newsItem['date']);
        $node->set('field_news_date', $formattedDate);
      }

      // Поле: анонс новости
      if ($node->hasField('field_news_summary') && !empty($newsItem['summary'])) {
        $node->set('field_news_summary', [
          'value' => $newsItem['summary'],
          'format' => 'basic_html',
        ]);
      }
      // Поле: полный текст
      if ($node->hasField('field_body') && !empty($newsItem['body'])) {
        $node->set('field_body', [
          'value' => $newsItem['body'],
          'format' => 'full_html',
        ]);
      }

      // === ВОТ ЗДЕСЬ: СОХРАНЯЕМ ID КАРТИНКИ В ПОЛЕ ===
      if ($node->hasField('field_news_image') && $imageId) {
        $node->set('field_news_image', [
          'target_id' => $imageId,
          'alt' => $newsItem['title'],
          'title' => $newsItem['title'],
        ]);
        $this->log("Картинка прикреплена к материалу, ID: $imageId", 'INFO');
      }
      // ===========================================

      // Поле: URL донора
      if ($node->hasField('field_donor_url')) {
        $node->set('field_donor_url', $newsItem['donor_url']);
      }

      //$node->path->pathauto = PathautoState::CREATE;

      $node->save();

      \Drupal::service('pathauto.generator')->updateEntityAlias($node, 'bulkupdate');

      $this->log("Сохранена новость: " . $newsItem['title'] . " (ID: " . $node->id() . ")", 'INFO');
      return true;

    } catch (\Exception $e) {
      $this->log("Ошибка сохранения новости: " . $e->getMessage(), 'ERROR');
      return false;
    }
  }

  /**
   * Создание файла-сущности из URI (альтернативный способ)
   */
  private function createImageFileEntity(string $uri): ?int {
    try {
      // Проверяем существует ли файл
      if (!file_exists($uri)) {
        return null;
      }

      // Создаём файл без обработки изображения
      $file = \Drupal\file\Entity\File::create([
        'uri' => $uri,
        'uid' => 1,
        'status' => 1,
      ]);
      $file->setPermanent();
      $file->save();

      // Отключаем toolkit обработку
      \Drupal::state()->set('file_' . $file->id() . '_no_toolkit', true);

      return (int) $file->id();

    } catch (\Exception $e) {
      $this->log("Не удалось создать сущность файла: " . $e->getMessage(), 'WARNING');
      return null;
    }
  }

  /**
   * Форматирование тела новости
   */
  private function formatBody(array $newsItem): string {
    $body = "<p><strong>Источник:</strong> <a href=\"" . $newsItem['donor_url'] . "\" target=\"_blank\">"
      . $newsItem['donor_url'] . "</a></p>";

    if (!empty($newsItem['description'])) {
      $body .= "<p><strong>Описание:</strong> " . $newsItem['description'] . "</p>";
    }

    if (!empty($newsItem['date'])) {
      $body .= "<p><strong>Дата публикации на источнике:</strong> " . $newsItem['date'] . "</p>";
    }

    return $body;
  }

  /**
   * Основной метод для обновления новостей
   */
  public function updateNews(): bool {
    $this->log("Начало обновления новостей");

    // Загружаем HTML
    $html = $this->fetchHtml($this->donorUrl);
    if ($html === null) {
      return false;
    }

    // Парсим новости
    $news = $this->parseNews($html);

    if (empty($news)) {
      $this->log("Не удалось найти новости на странице", 'ERROR');
      return false;
    }

    // Сохраняем каждую новость в базу
    $savedCount = 0;
    foreach ($news as $newsItem) {
      if ($this->saveNewsToDatabase($newsItem)) {
        $savedCount++;
      }
    }

    $this->log("Обработано новостей: " . count($news) . ", сохранено новых: " . $savedCount, 'INFO');
    return $savedCount > 0;
  }

  /**
   * Получение последних сохранённых новостей из базы
   */
  public function getLatestNews(int $limit = 4): array {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'news')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->accessCheck(FALSE)
      ->execute();

    if (empty($query)) {
      return [];
    }

    $nodes = Node::loadMultiple($query);
    $news = [];

    foreach ($nodes as $node) {
      $news[] = [
        'title' => $node->getTitle(),
        'link' => $node->toUrl()->toString(),
        'date' => date('d.m.Y H:i', $node->getCreatedTime()),
        'body' => $node->get('body')->value,
      ];
    }

    return $news;
  }

  /**
   * Преобразование строки даты с сайта-донора в timestamp
   */
  private function convertDonorDateToTimestamp(string $dateString): ?int {
    // Если строка вида "Сегодня, 13:16"
    if (strpos($dateString, 'Сегодня') !== false) {
      preg_match('/(\d{1,2}):(\d{2})/', $dateString, $matches);
      if (!empty($matches)) {
        $hour = (int)$matches[1];
        $minute = (int)$matches[2];
        return strtotime(date('Y-m-d') . " $hour:$minute:00");
      }
    }

    // Если строка вида "Вчера, 10:30"
    if (strpos($dateString, 'Вчера') !== false) {
      preg_match('/(\d{1,2}):(\d{2})/', $dateString, $matches);
      if (!empty($matches)) {
        $hour = (int)$matches[1];
        $minute = (int)$matches[2];
        $yesterday = strtotime('-1 day');
        return strtotime(date('Y-m-d', $yesterday) . " $hour:$minute:00");
      }
    }

    // Если дата в формате "DD.MM.YYYY, HH:MM"
    if (preg_match('/(\d{2})\.(\d{2})\.(\d{4}), (\d{2}):(\d{2})/', $dateString, $matches)) {
      $day = $matches[1];
      $month = $matches[2];
      $year = $matches[3];
      $hour = $matches[4];
      $minute = $matches[5];
      return strtotime("$year-$month-$day $hour:$minute:00");
    }

    // Если дата в формате "DD.MM.YYYY в HH:MM"
    if (preg_match('/(\d{2})\.(\d{2})\.(\d{4}) в (\d{2}):(\d{2})/', $dateString, $matches)) {
      $day = $matches[1];
      $month = $matches[2];
      $year = $matches[3];
      $hour = $matches[4];
      $minute = $matches[5];
      return strtotime("$year-$month-$day $hour:$minute:00");
    }

    // Если пришло "Сегодня, 13:16" без преобразования
    $this->log("Не удалось распознать дату: $dateString", 'WARNING');
    return time(); // возвращаем текущее время как fallback
  }

  /**
   * Преобразование в формат для поля datetime
   */
  private function formatForDateField(string $dateString): string {
    $timestamp = $this->convertDonorDateToTimestamp($dateString);
    return date('Y-m-d\TH:i:s', $timestamp);
  }

}
