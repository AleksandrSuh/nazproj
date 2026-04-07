<?php

namespace Drupal\budget_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImportXlsxForm extends FormBase {

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  public function __construct(PrivateTempStoreFactory $tempStoreFactory) {
    $this->tempStoreFactory = $tempStoreFactory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  public function getFormId() {
    return 'budget_import_xlsx_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Получаем данные предпросмотра из form_state
    $preview_data = $form_state->get('preview_data');

    $form['xlsx_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('XLSX файл'),
      '#upload_location' => 'public://import/',
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'xlsx xls',
        ],
      ],
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Загрузить и проверить'),
    ];

    // Кнопка подтверждения импорта
    $form['confirm_update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Да, всё хорошо. Обновить'),
      '#submit' => ['::confirmImportSubmit'],
      '#limit_validation_errors' => [],
      '#access' => FALSE, // По умолчанию скрыта
    ];

    // Если есть данные предпросмотра - отображаем их
    if ($preview_data) {
      $form['preview'] = [
        '#type' => 'markup',
        '#markup' => $this->renderPreviewTable($preview_data),
        '#weight' => 10,
      ];

      // Показываем кнопку подтверждения только если годы валидны
      $form['confirm_update']['#access'] = $preview_data['years_valid'];

      if (!$preview_data['years_valid']) {
        $form['preview_error'] = [
          '#type' => 'markup',
          '#markup' => '<div style="color: red; margin: 10px 0; padding: 10px; border: 1px solid red;">Ошибка. Год не определён. Обновить нельзя. Обратитесь к администратору, либо исправьте ошибку в файле.</div>',
          '#weight' => 11,
        ];
      }
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_id = $form_state->getValue('xlsx_file')[0];
    $file = File::load($file_id);

    if ($file) {
      $file_uri = $file->getFileUri();
      $file_path = \Drupal::service('file_system')->realpath($file_uri);

      try {
        // Получаем данные для предпросмотра
        $preview_data = $this->getPreviewData($file_path);

        // Сохраняем данные в form_state для отображения
        $form_state->set('preview_data', $preview_data);

        // Сохраняем путь к файлу в tempstore для последующего импорта
        $tempstore = $this->tempStoreFactory->get('budget_import');
        $tempstore->set('file_path', $file_path);

        // Перестраиваем форму для отображения предпросмотра
        $form_state->setRebuild();

        // Показываем сообщение об успешной загрузке
        $this->messenger()->addStatus($this->t('Файл загружен. Проверьте данные ниже.'));

      } catch (\Exception $e) {
        $this->messenger()->addError(
          $this->t('Ошибка при импорте: @error', ['@error' => $e->getMessage()])
        );
      }
    }
  }

  public function confirmImportSubmit(array &$form, FormStateInterface $form_state) {
    $tempstore = $this->tempStoreFactory->get('budget_import');
    $file_path = $tempstore->get('file_path');

    if ($file_path) {
      try {
        $this->importXlsxData($file_path);
        $this->messenger()->addStatus($this->t('Импорт успешно выполнен'));

        // Очищаем временные данные
        $tempstore->delete('file_path');
        $form_state->set('preview_data', NULL);

        // Перенаправляем на ту же страницу, но без предпросмотра
        $form_state->setRedirect('budget_import.import_form');

      } catch (\Exception $e) {
        $this->messenger()->addError(
          $this->t('Ошибка при импорте: @error', ['@error' => $e->getMessage()])
        );
      }
    }
  }

  private function getPreviewData($file_path) {
    if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
      throw new \Exception('Установите PhpSpreadsheet: ddev composer require phpoffice/phpspreadsheet');
    }

    $xls = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
    $xls->setActiveSheetIndex(0);
    $sheet = $xls->getActiveSheet();

    $new_array = [];
    $zapis = false;
    $zagolovok = false;
    $title = '';
    $year_1 = 0;
    $year_2 = 0;
    $year_3 = 0;

    foreach ($sheet->toArray() as $row) {
      if (($row[0] == 'КЦСР') && ($row[1] == 'Доп. ФК') && !empty($row[0]) && !empty($row[1]) &&
        !empty($row[2]) && !empty($row[3]) && !empty($row[4]) && !empty($row[5]) &&
        !empty($row[6]) && !empty($row[7]) && !empty($row[8]) && !empty($row[9]) &&
        !empty($row[10]) && !empty($row[11]) && !empty($row[12]) && !empty($row[13]) &&
        !empty($row[14]) && !empty($row[15]) && !empty($row[16]) && !empty($row[17])) {
        $zagolovok = true;
        $zapis = true;
        $title = $row;
        $year_1 = intval(preg_replace('/[^0-9]+/', '', $row[6]), 10);
        $year_2 = intval(preg_replace('/[^0-9]+/', '', $row[10]), 10);
        $year_3 = intval(preg_replace('/[^0-9]+/', '', $row[14]), 10);
      } elseif ($zapis == true && !empty($row[0]) && $row[0] != 'Итого') {
        $new_array[] = $row;
      }
    }

    return [
      'title' => $title,
      'new_array' => $new_array,
      'year_1' => $year_1,
      'year_2' => $year_2,
      'year_3' => $year_3,
      'years_valid' => ($year_1 != 0 && $year_2 != 0 && $year_3 != 0),
    ];
  }

  private function importXlsxData($file_path) {

    if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
      throw new \Exception('Установите PhpSpreadsheet: ddev composer require phpoffice/phpspreadsheet');
    }

    $xls = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
    $xls->setActiveSheetIndex(0);
    $sheet = $xls->getActiveSheet();

    // Парсим файл заново, чтобы получить данные
    $new_array = [];
    $zapis = false;
    $title = '';
    $year_1 = 0;
    $year_2 = 0;
    $year_3 = 0;

    foreach ($sheet->toArray() as $row) {
      if (($row[0] == 'КЦСР') && ($row[1] == 'Доп. ФК') && !empty($row[0]) && !empty($row[1]) &&
        !empty($row[2]) && !empty($row[3]) && !empty($row[4]) && !empty($row[5]) &&
        !empty($row[6]) && !empty($row[7]) && !empty($row[8]) && !empty($row[9]) &&
        !empty($row[10]) && !empty($row[11]) && !empty($row[12]) && !empty($row[13]) &&
        !empty($row[14]) && !empty($row[15]) && !empty($row[16]) && !empty($row[17])) {
        $zapis = true;
        $title = $row;
        $year_1 = intval(preg_replace('/[^0-9]+/', '', $row[6]), 10);
        $year_2 = intval(preg_replace('/[^0-9]+/', '', $row[10]), 10);
        $year_3 = intval(preg_replace('/[^0-9]+/', '', $row[14]), 10);
      } elseif ($zapis == true && !empty($row[0]) && $row[0] != 'Итого') {
        $new_array[] = $row;
      }
    }

    // Проверяем, что годы определены
    if ($year_1 == 0 || $year_2 == 0 || $year_3 == 0) {
      throw new \Exception('Не удалось определить годы в файле');
    }

    $database = \Drupal::database();
    $current_user_id = \Drupal::currentUser()->id();
    $current_time = date('Y-m-d H:i:s');
    // транзакция для целостности данных
    $transaction = $database->startTransaction();

    try {
      $count_updated = 0;
      $count_inserted = 0;

      foreach ($new_array as $row) {
        $category_code = trim($row[0]);
        $dop_fk = trim($row[1]);

        // Данные для трёх годов
        $years_data = [
          $year_1 => [
            'extence_plan' => $this->parseNumber($row[2]),
            'extence_fed_plan' => $this->parseNumber($row[3]),
            'extence_reg_plan' => $this->parseNumber($row[4]),
            'extence_mun_plan' => $this->parseNumber($row[5]),
            'income_fact' => $this->parseNumber($row[6]),
            'income_fed_fact' => $this->parseNumber($row[7]),
            'income_reg_fact' => $this->parseNumber($row[8]),
            'income_mun_fact' => $this->parseNumber($row[9]),
          ],
          $year_2 => [
            'extence_plan' => 0,
            'extence_fed_plan' => 0,
            'extence_reg_plan' => 0,
            'extence_mun_plan' => 0,
            'income_fact' => $this->parseNumber($row[10]),
            'income_fed_fact' => $this->parseNumber($row[11]),
            'income_reg_fact' => $this->parseNumber($row[12]),
            'income_mun_fact' => $this->parseNumber($row[13]),
          ],
          $year_3 => [
            'extence_plan' => 0,
            'extence_fed_plan' => 0,
            'extence_reg_plan' => 0,
            'extence_mun_plan' => 0,
            'income_fact' => $this->parseNumber($row[14]),
            'income_fed_fact' => $this->parseNumber($row[15]),
            'income_reg_fact' => $this->parseNumber($row[16]),
            'income_mun_fact' => $this->parseNumber($row[17]),
          ],
        ];

        // Обрабатываем каждый год
        foreach ($years_data as $year => $values) {

          // Проверяем, существует ли запись
          $existing = $database->select('budget', 'b')
            ->fields('b', ['id'])
            ->condition('category_code', $category_code)
            ->condition('dop_fk', $dop_fk)
            ->condition('year', $year)
            ->execute()
            ->fetchField();

          if ($existing) {
            // Обновляем существующую запись
            $database->update('budget')
              ->fields([
                'extence_plan' => $values['extence_plan'],
                'extence_fed_plan' => $values['extence_fed_plan'],
                'extence_reg_plan' => $values['extence_reg_plan'],
                'extence_mun_plan' => $values['extence_mun_plan'],
                'income_fact' => $values['income_fact'],
                'income_fed_fact' => $values['income_fed_fact'],
                'income_reg_fact' => $values['income_reg_fact'],
                'income_mun_fact' => $values['income_mun_fact'],
                'updated_at' => $current_time,
                'uid' => $current_user_id,
              ])
              ->condition('id', $existing)
              ->execute();
            $count_updated++;
          } else {
            // Создаём новую запись
            $database->insert('budget')
              ->fields([
                'category_code' => $category_code,
                'dop_fk' => $dop_fk,
                'year' => $year,
                'xml_id' => time(),
                'extence_plan' => $values['extence_plan'],
                'extence_fed_plan' => $values['extence_fed_plan'],
                'extence_reg_plan' => $values['extence_reg_plan'],
                'extence_mun_plan' => $values['extence_mun_plan'],
                'income_fact' => $values['income_fact'],
                'income_fed_fact' => $values['income_fed_fact'],
                'income_reg_fact' => $values['income_reg_fact'],
                'income_mun_fact' => $values['income_mun_fact'],
                'created_at' => $current_time,
                'updated_at' => $current_time,
              ])
              ->execute();
            $count_inserted++;
          }
          /*\Drupal::logger('budget_import')->notice(
            'Импорт  @inserted',
            ['@inserted' => $category_code. ' ' .$dop_fk. ' ' .$year. ' ' .$row[2]. ' ' .$values['income_fact']]
          );*/
        }

        //break;  пробуем одну строку
      }

      // Если всё успешно, транзакция автоматически закоммитится при выходе из блока try

      /*
      \Drupal::logger('budget_import')->notice(
        'Импорт завершён: обновлено @updated, добавлено @inserted',
        ['@updated' => $count_updated, '@inserted' => $count_inserted]
      );*/

      $this->messenger()->addStatus(
        $this->t('Импорт завершён. Обновлено записей: @updated, добавлено: @inserted', [
          '@updated' => $count_updated,
          '@inserted' => $count_inserted,
        ])
      );

    } catch (\Exception $e) {
      // В случае ошибки откатываем транзакцию
      $transaction->rollBack();
      \Drupal::logger('budget_import')->error('Ошибка импорта: @error', ['@error' => $e->getMessage()]);
      throw $e;
    }

  }

  private function renderPreviewTable($preview_data) {
    $title = $preview_data['title'];
    $new_array = $preview_data['new_array'];
    $year_1 = $preview_data['year_1'];
    $year_2 = $preview_data['year_2'];
    $year_3 = $preview_data['year_3'];

    $html = '<h3>Проверьте данные прежде, чем нажать кнопку "Обновить":</h3>';
    $html .= '<div style="overflow-x: auto;">';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';

    // Заголовок с годами
    $html .= '<tr>';
    $html .= '<td colspan="7" style="background-color: #f0f0f0;">&nbsp;</td>';
    $html .= '<td colspan="4" style="text-align: center; background-color: #e0f0e0;"><strong>' . $year_1 . '</strong></td>';
    $html .= '<td colspan="4" style="text-align: center; background-color: #e0f0e0;"><strong>' . $year_2 . '</strong></td>';
    $html .= '<td colspan="4" style="text-align: center; background-color: #e0f0e0;"><strong>' . $year_3 . '</strong></td>';
    $html .= '</tr>';

    // Заголовки колонок
    $html .= '<tr style="background-color: #f0f0f0;"><th style="padding: 5px;">№</th>';
    foreach ($title as $col) {
      $html .= '<th style="padding: 5px;">' . htmlspecialchars($col) . '</th>';
    }
    $html .= '</tr>';

    // Данные
    $i = 1;
    foreach ($new_array as $row) {
      $html .= '<tr>';
      $html .= '<td style="padding: 5px; text-align: center;">' . $i . '</td>';
      for ($j = 0; $j <= 17; $j++) {
        $value = isset($row[$j]) ? htmlspecialchars($row[$j]) : '';
        $style = ($j >= 6 && $j <= 17) ? 'text-align: right;' : '';
        $html .= '<td style="padding: 5px; ' . $style . '">' . $value . '</td>';
      }
      $html .= '</tr>';
      $i++;
    }

    $html .= '</table>';
    $html .= '</div>';

    return $html;
  }

  private function parseNumber($value) {
    if (empty($value)) {
      return 0;
    }

    if (is_numeric($value)) {
      return floatval($value);
    }

    $value = trim((string) $value);

    $value = str_replace(' ', '', $value);

    $has_comma = strpos($value, ',') !== false;
    $has_dot = strpos($value, '.') !== false;

    if ($has_comma && $has_dot) {
      $last_comma = strrpos($value, ',');
      $last_dot = strrpos($value, '.');

      if ($last_comma > $last_dot) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
      }
      else {
        $value = str_replace(',', '', $value); // Убираем разделители тысяч
      }
    }
    elseif ($has_comma && !$has_dot) {
      if (substr_count($value, ',') > 1) {
        // Несколько запятых - это разделители тысяч (например: "805,517,621")
        $value = str_replace(',', '', $value);
      } else {
        // Одна запятая - это десятичный разделитель (например: "805517621,86")
        $value = str_replace(',', '.', $value);
      }
    }

    $value = preg_replace('/[^0-9\.\-]/', '', $value);

    if (!is_numeric($value)) {
      return 0;
    }

    return floatval($value);
  }

}
