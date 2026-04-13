<?php

namespace Drupal\national_projects\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubsectionController extends ControllerBase {

  /**
   * Находит направление по алиасу.
   */
  private function getDirectionBySlug($slug) {
    $full_alias = '/' . $slug;
    $path = \Drupal::service('path_alias.manager')->getPathByAlias($full_alias);

    if (preg_match('/node\/(\d+)/', $path, $matches)) {
      $node = Node::load($matches[1]);
      if ($node && $node->getType() == 'napravlenie') {
        return $node;
      }
    }
    return null;
  }

  /**
   * Находит подраздел по алиасу.
   */
  private function getSubsectionBySlug($slug) {
    $full_alias = '/' . $slug;
    $path = \Drupal::service('path_alias.manager')->getPathByAlias($full_alias);

    if (preg_match('/node\/(\d+)/', $path, $matches)) {
      $node = Node::load($matches[1]);
      if ($node && $node->getType() == 'podrazdel') {
        return $node;
      }
    }
    return null;
  }

  /**
   * Основной контент страницы подраздела.
   */
  public function content($direction_slug, $subsection_slug) {
    $direction = $this->getDirectionBySlug($direction_slug);
    $subsection = $this->getSubsectionBySlug($subsection_slug);

    // Проверяем существование
    if (!$direction || !$subsection) {
      throw new NotFoundHttpException();
    }

    // Проверяем принадлежность подраздела направлению
    $subsection_direction = $subsection->get('field_napravlenie')->target_id;
    if ($subsection_direction != $direction->id()) {
      throw new NotFoundHttpException();
    }

    // Получаем мероприятия
    $events = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'type' => 'meropriyatie',
        'field_podrazdel' => $subsection->id(),
        'status' => 1,
      ]);

    return [
      '#theme' => 'subsection_page',
      '#direction' => $direction,
      '#subsection' => $subsection,
      '#events' => $events,
    ];
  }

  /**
   * Заголовок страницы.
   */
  public function title($direction_slug, $subsection_slug) {
    $subsection = $this->getSubsectionBySlug($subsection_slug);
    return $subsection ? $subsection->getTitle() : '';
  }

}
