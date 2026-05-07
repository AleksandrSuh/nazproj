<?php

namespace Drupal\custom_news\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

class NewsController extends ControllerBase {

  public function list() {
    $query = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'news')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 10);

    $nids = $query->execute();
    $nodes = Node::loadMultiple($nids);

    return [
      '#theme' => 'news_list',
      '#nodes' => $nodes,
    ];
  }

  public function view($node) {
    return [
      '#theme' => 'news_detail',
      '#node' => $node,
    ];
  }

  public function getTitle($node) {
    return $node->getTitle();
  }
}
