<?php

namespace Drupal\national_projects\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DirectionController extends ControllerBase {

  /**
   * Находит направление по алиасу.
   */
  private function getDirectionBySlug__($slug) {
    $full_alias = '/' . $slug;

    try {
      $path = \Drupal::service('path_alias.manager')->getPathByAlias($full_alias);

      if (preg_match('/node\/(\d+)/', $path, $matches)) {
        $node = Node::load($matches[1]);
        if ($node && $node->getType() == 'napravlenie') {
          return $node;
        }
      }
    } catch (\Exception $e) {
      return null;
    }

    return null;
  }

  private function getDirectionBySlug($slug) {
    \Drupal::logger('national_projects')->notice('Шаг 1: Получен slug: @slug', ['@slug' => $slug]);

    // Сначала ищем по алиасу
    $full_alias = '/' . $slug;
    \Drupal::logger('national_projects')->notice('Шаг 2: Ищем алиас: @alias', ['@alias' => $full_alias]);

    try {
      $path = \Drupal::service('path_alias.manager')->getPathByAlias($full_alias);
      \Drupal::logger('national_projects')->notice('Шаг 3: Найден путь: @path', ['@path' => $path]);

      if (preg_match('/node\/(\d+)/', $path, $matches)) {
        $nid = $matches[1];
        \Drupal::logger('national_projects')->notice('Шаг 4: Найден ID материала: @nid', ['@nid' => $nid]);

        $node = Node::load($nid);
        if ($node && $node->getType() == 'napravlenie') {
          \Drupal::logger('national_projects')->notice('Шаг 5: УСПЕХ! Найдено направление: @title', ['@title' => $node->getTitle()]);
          return $node;
        }
      }
    } catch (\Exception $e) {
      \Drupal::logger('national_projects')->notice('Шаг 3-Ошибка: Алиас не найден - @error', ['@error' => $e->getMessage()]);

      // Ищем по заголовку
      $title = str_replace('-', ' ', $slug);
      \Drupal::logger('national_projects')->notice('Шаг 6: Ищем по заголовку: @title', ['@title' => $title]);

      $nodes = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadByProperties([
          'type' => 'napravlenie',
          'title' => $title,
          'status' => 1,
        ]);

      if (!empty($nodes)) {
        $node = reset($nodes);
        \Drupal::logger('national_projects')->notice('Шаг 7: УСПЕХ! Найдено по заголовку: @title', ['@title' => $node->getTitle()]);
        return $node;
      }

      \Drupal::logger('national_projects')->notice('Шаг 7-Ошибка: Ничего не найдено по заголовку');
    }

    \Drupal::logger('national_projects')->notice('Шаг 8: ВОЗВРАЩАЕМ NULL');
    return null;
  }

  /**
   * Основной контент страницы направления.
   */
  public function content($slug) {
    // Логгируем для отладки
    \Drupal::logger('national_projects')->notice('DirectionController content вызван с slug: @slug', ['@slug' => $slug]);

    $direction = $this->getDirectionBySlug($slug);

    if (!$direction) {
      throw new NotFoundHttpException();
    }

    // Получаем подразделы
    $subsections = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'type' => 'razdel_napravleniya',
        'field_napravlenie' => $direction->id(),
        'status' => 1,
      ]);

    // Сортируем по заголовку
    uasort($subsections, function($a, $b) {
      return strcmp($a->getTitle(), $b->getTitle());
    });

    $direction_slug = $slug;

    return [
      '#theme' => 'direction_page',
      '#direction' => $direction,
      '#direction_slug' => $direction_slug,
      '#subsections' => $subsections,
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * Заголовок страницы.
   */
  public function title($slug) {
    $direction = $this->getDirectionBySlug($slug);
    return $direction ? $direction->getTitle() : '';
  }


}
