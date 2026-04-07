<?php

namespace Drupal\budget_import\Plugin\Block;

//use Drupal\budget\MyHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\budget_import\Service\BudgetDataService;
use Drupal\Core\Render\Markup;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Budgets' block.
 *
 * @Block(
 *   id = "budg_chart",
 *   admin_label = @Translation("Бюджет на графике в шапке"),
 *   category = @Translation("Budgets")
 * )
 */
class Budgets extends BlockBase implements ContainerFactoryPluginInterface
{


  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   * @var \Drupal\budget_import\Service\BudgetDataService
   */
  protected $routeMatch;
  protected $budgetDataService;

  public function __construct(
    array               $configuration,
                        $plugin_id,
                        $plugin_definition,
    RouteMatchInterface $route_match,
    BudgetDataService $budgetDataService
  )
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->budgetDataService = $budgetDataService;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('budget_import.budget_data_service')
    );
  }

  public function build()
  {
    $data = $this->budgetDataService->apiData();
    $build['years'] = $data;
    return $data;

  }

}
