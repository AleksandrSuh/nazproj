<?php

namespace Drupal\budget_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Budget Import routes.
 */
class BudgetDataController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new BudgetDataController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Display budget data.
   */
  public function viewData(Request $request) {
    // Получаем выбранный год из запроса
    $selected_year = $request->query->get('year', '');

    // Получаем список доступных годов для фильтра
    $years = $this->getAvailableYears();

    // Строим запрос
    $query = $this->database->select('budget', 'b')
      ->fields('b', [
        'id',
        'year',
        'category_code',
        'dop_fk',
        'active',
        'extence_plan',
        'extence_fed_plan',
        'extence_reg_plan',
        'extence_mun_plan',
        'income_fact',
        'income_fed_fact',
        'income_reg_fact',
        'income_mun_fact',
        'created_at',
        'updated_at',
      ]);

    // Применяем фильтр по году, если выбран
    if (!empty($selected_year)) {
      $query->condition('year', $selected_year);
    }

    // Добавляем сортировку
    $query->orderBy('id');
    $query->orderBy('year', 'DESC');
    //$query->orderBy('category_code', 'ASC');
    //$query->orderBy('dop_fk', 'ASC');

    // Добавляем пагинацию (по 50 записей на страницу)
    $pager = $query->extend(PagerSelectExtender::class)->limit(50);
    $results = $pager->execute()->fetchAll();

    // Получаем имена пользователей для uid
    $uids = array_filter(array_unique(array_column($results, 'uid')), function($uid) {
      return !empty($uid);
    });
    $users = $this->getUserNames($uids);

    // Строим таблицу
    $build = [];

    // Форма фильтра
    $build['filter_form'] = $this->buildFilterForm($selected_year, $years);

    // Таблица с данными
    if (empty($results)) {
      $build['no_data'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">Нет данных для отображения.</div>',
      ];
    } else {
      $build['table'] = [
        '#type' => 'table',
        '#header' => $this->buildTableHeader(),
        '#rows' => $this->buildTableRows($results, $users),
        '#empty' => $this->t('Нет данных'),
        '#attributes' => [
          'class' => ['budget-data-table'],
          'style' => 'width: 100%; border-collapse: collapse;',
        ],
      ];

      // Добавляем пагинацию
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }

    // Добавляем CSS для стилизации
    $build['#attached']['library'][] = 'budget_import/budget_data';

    return $build;
  }

  /**
   * Builds the filter form.
   *
   * @param string $selected_year
   *   The currently selected year.
   * @param array $years
   *   Array of available years.
   *
   * @return array
   *   Render array for the filter form.
   */
  private function buildFilterForm($selected_year, array $years) {
    // Формируем опции для селекта
    $options = ['' => '- Все годы -'];
    if (!empty($years)) {
      foreach ($years as $year) {
        $options[$year] = $year;
      }
    }

    // Строим HTML форму
    $url = \Drupal\Core\Url::fromRoute('budget_import.view_data')->toString();

    $html = '<form method="get" action="' . $url . '" class="budget-filter-form">';
    $html .= '<div class="filter-wrapper">';
    $html .= '<button type="submit" class="button">Выбрать год</button>';
    //$html .= '<label for="year-select" style="margin-right: 10px;">Год: </label>';
    $html .= '<select id="year-select" name="year">';

    foreach ($options as $value => $label) {
      $selected = ($selected_year == $value) ? 'selected="selected"' : '';
      $html .= '<option value="' . $value . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }

    $html .= '</select>';

    if (!empty($selected_year)) {
      $reset_url = \Drupal\Core\Url::fromRoute('budget_import.view_data')->toString();
      $html .= '<a href="' . $reset_url . '" class="button">Сбросить</a>';
    }

    $html .= '</div>';
    $html .= '</form>';

    // Возвращаем рендер массив с типом markup
    return [
      '#type' => 'markup',
      '#markup' => $html,
      '#allowed_tags' => ['form', 'div', 'label', 'select', 'option', 'button', 'a', 'input'],
    ];
  }

  /**
   * Builds the table header.
   *
   * @return array
   *   Table header array.
   */
  private function buildTableHeader() {
    return [
      $this->t('ID'),
      $this->t('Год'),
      $this->t('КЦСР'),
      $this->t('Доп. ФК'),
      $this->t('Активность'),
      $this->t('Расход по ЛС'),
      $this->t('Расход по ЛС фед'),
      $this->t('Расход по ЛС рег'),
      $this->t('Расход по ЛС мун'),
      $this->t('Ассигнования'),
      $this->t('Ассигнования фед'),
      $this->t('Ассигнования рег'),
      $this->t('Ассигнования мун'),
      $this->t('Дата создания'),
      $this->t('Дата обновления'),
    ];
  }

  /**
   * Builds the table rows.
   *
   * @param array $results
   *   Database query results.
   * @param array $users
   *   Array of user names keyed by uid.
   *
   * @return array
   *   Table rows array.
   */
  private function buildTableRows($results, array $users) {
    $rows = [];

    foreach ($results as $row) {
      $rows[] = [
        'data' => [
          $row->id,
          $row->year,
          $row->category_code,
          $row->dop_fk,
          $row->active,
          number_format($row->extence_plan, 2, '.', ' '),
          number_format($row->extence_fed_plan, 2, '.', ' '),
          number_format($row->extence_reg_plan, 2, '.', ' '),
          number_format($row->extence_mun_plan, 2, '.', ' '),
          number_format($row->income_fact, 2, '.', ' '),
          number_format($row->income_fed_fact, 2, '.', ' '),
          number_format($row->income_reg_fact, 2, '.', ' '),
          number_format($row->income_mun_fact, 2, '.', ' '),
          $row->created_at ? date('d.m.Y H:i', strtotime($row->created_at)) : '-',
          $row->updated_at ? date('d.m.Y H:i', strtotime($row->updated_at)) : '-',
        ],
        'class' => ['budget-row'],
      ];
    }

    return $rows;
  }

  /**
   * Gets available years from the budget table.
   *
   * @return array
   *   Array of years with year as key and value.
   */
  private function getAvailableYears() {
    $years = [];

    $results = $this->database->select('budget', 'b')
      ->fields('b', ['year'])
      ->distinct()
      ->orderBy('year', 'DESC')
      ->execute()
      ->fetchAll();

    foreach ($results as $row) {
      $years[$row->year] = $row->year;
    }

    return $years;
  }

  /**
   * Gets user names for given uids.
   *
   * @param array $uids
   *   Array of user IDs.
   *
   * @return array
   *   Array of user names keyed by uid.
   */
  private function getUserNames(array $uids) {
    $users = [];

    if (empty($uids)) {
      return $users;
    }

    try {
      $user_storage = \Drupal::entityTypeManager()->getStorage('user');
      $user_entities = $user_storage->loadMultiple($uids);

      foreach ($user_entities as $uid => $user) {
        $users[$uid] = $user->getDisplayName();
      }
    } catch (\Exception $e) {
      $this->logger('budget_import')->error('Error loading users: @error', ['@error' => $e->getMessage()]);
    }

    return $users;
  }



  private function generateBudgetJson__($type_page) {
    $database = \Drupal::database();

    $query = $database->select('budget', 'b')
      ->fields('b', ['year', 'category_code',  'dop_fk'])
      ->orderBy('b.year');

    $results = $query->execute()->fetchAll();
    $arYears = [];
    foreach ($results as $row) {
      /*if (!isset($categories[$row->category])) {
        $categories[$row->category] = [
          'category' => $row->category,
          'years' => []
        ];
      }
      $categories[$row->category]['years'][$row->year] = (float) $row->amount;*/
      $all_years[$row->year] = $row->year;
    }
    $data = ['years' => $all_years];

    /*if($type_page == 'all')
    {
      $arTypes = ['incomes','expenses'];
      foreach ($arTypes as $type)
      {
        $data[$type.'Data'] = $this->getBudgetDataForType($type);
      }
    }
    else
    {
      $data = $this->getBudgetDataForType($type_page);
    }*/

    return $data;
  }

  public function apiData__(Request $request) {

    $type_page = $request->query->get('type_data', '');

    $data = $this->generateBudgetJson($type_page);

    // Можно вернуть в разных форматах
    $format = $request->query->get('format', 'json');

    if ($format === 'json') {
      $response = new JsonResponse($data);

      // Настраиваем CORS заголовки если нужно
      $response->headers->set('Access-Control-Allow-Origin', '*');
      $response->headers->set('Access-Control-Allow-Methods', 'GET');
      $response->headers->set('Content-Type', 'application/json; charset=utf-8');

      return $response;
    }

    // Или просто массив для Drupal
    return new JsonResponse($data);
  }

  private function getUrlGenerator() {
    return \Drupal::service('url_generator');
  }

}
