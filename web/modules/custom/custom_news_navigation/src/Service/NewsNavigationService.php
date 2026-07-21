<?php

namespace Drupal\custom_news_navigation\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\node\NodeInterface;

/**
 * Сервис для навигации между новостями.
 */
class NewsNavigationService {

  /**
   * Конструктор сервиса.
   */
  public function __construct(
    protected Connection $database,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheBackendInterface $cache,
  ) {}

  /**
   * Получает соседние новости.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Текущий узел новости.
   *
   * @return array
   *   Массив с ключами 'previous' и 'next' (узлы или NULL).
   */
  public function getAdjacentNews(NodeInterface $node): array {
    // Проверяем, что это новость (тип материала 'news').
    if ($node->bundle() !== 'news') {
      return ['previous' => NULL, 'next' => NULL];
    }

    $currentNid = $node->id();
    $cacheId = "news_navigation:{$currentNid}";

    // Пытаемся получить данные из кэша.
    if ($cache = $this->cache->get($cacheId)) {
      return $cache->data;
    }

    // Получаем ID всех опубликованных новостей, отсортированных по ID.
    $query = $this->database->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('n.type', 'news')
      ->condition('n.status', 1)
      ->orderBy('n.nid', 'ASC');

    $allNewsIds = $query->execute()->fetchCol();

    // Находим позицию текущей новости.
    $currentIndex = array_search($currentNid, $allNewsIds);

    $result = [
      'previous' => NULL,
      'next' => NULL,
    ];

    if ($currentIndex !== FALSE) {
      // Предыдущая новость.
      if ($currentIndex > 0) {
        $prevNid = $allNewsIds[$currentIndex - 1];
        $result['previous'] = $this->loadNode($prevNid);
      }

      // Следующая новость.
      if ($currentIndex < count($allNewsIds) - 1) {
        $nextNid = $allNewsIds[$currentIndex + 1];
        $result['next'] = $this->loadNode($nextNid);
      }
    }

    // Сохраняем в кэш на 1 час.
    $this->cache->set($cacheId, $result, time() + 3600, ['node:' . $currentNid]);

    return $result;
  }

  /**
   * Загружает узел по ID.
   *
   * @param int $nid
   *   ID узла.
   *
   * @return \Drupal\node\NodeInterface|null
   *   Узел или NULL.
   */
  private function loadNode(int $nid): ?NodeInterface {
    try {
      $node = $this->entityTypeManager
        ->getStorage('node')
        ->load($nid);

      if ($node && $node->isPublished()) {
        return $node;
      }
    }
    catch (\Exception $e) {
      // Логируем ошибку.
      \Drupal::logger('custom_news_navigation')
        ->error('Ошибка загрузки узла @nid: @error', [
          '@nid' => $nid,
          '@error' => $e->getMessage(),
        ]);
    }

    return NULL;
  }

  /**
   * Очищает кэш для всех новостей.
   */
  public function invalidateCache(): void {
    // Получаем все ID новостей.
    $query = $this->database->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('n.type', 'news')
      ->execute();

    $nids = $query->fetchCol();

    foreach ($nids as $nid) {
      $this->cache->delete("news_navigation:{$nid}");
    }
  }

}
