<?php
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

chdir(__DIR__);
require_once 'autoload.php';

$kernel = DrupalKernel::createFromRequest(Request::createFromGlobals(), 'web', 'prod');
$kernel->boot();

$match = \Drupal::service('router.no_access_checks')->match('/infrastruktura-dlya-zhizni');

echo "Controller: " . ($match['_controller'] ?? 'None') . "\n";
echo "Route: " . ($match['_route'] ?? 'None') . "\n";
