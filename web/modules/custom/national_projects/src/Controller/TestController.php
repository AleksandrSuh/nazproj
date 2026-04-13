<?php

namespace Drupal\national_projects\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

class TestController extends ControllerBase {

  public function hello($name) {
    // Проверяем доступ
    $account = \Drupal::currentUser();
    $roles = $account->getRoles();

    return new Response('<pre>
    Name: ' . htmlspecialchars($name) . '
    User ID: ' . $account->id() . '
    Is authenticated: ' . ($account->isAuthenticated() ? 'yes' : 'no') . '
    Roles: ' . print_r($roles, true) . '
    </pre>
    <h1>Hello ' . htmlspecialchars($name) . '!</h1>
    <p>If you see this, controller works!</p>');
  }

}
