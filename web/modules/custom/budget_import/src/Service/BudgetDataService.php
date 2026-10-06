<?php

namespace Drupal\budget_import\Service;

class BudgetDataService {

  public function apiData() {
    $database = \Drupal::database();


    $query = $database->select('budget', 'b')
      ->fields('b', ['year', 'category_code', 'dop_fk', 'extence_plan', 'extence_fed_plan', 'extence_reg_plan', 'extence_mun_plan', 'income_fact', 'income_fed_fact', 'income_reg_fact', 'income_mun_fact'])
      ->condition('active', 1)
      ->orderBy('b.year');
      //->orderBy('b.category_code');

    $results = $query->execute()->fetchAll();
    $arYearsData = [];
    $fed_plan = $reg_plan = $mun_plan = $fed_fact = $reg_fact = $mun_fact = $plan = $fact = [];
    foreach ($results as $row) {
      /*if (!isset($categories[$row->category])) {
        $categories[$row->category] = [
          'category' => $row->category,
          'years' => []
        ];
      }
      $categories[$row->category]['years'][$row->year] = (float) $row->amount;*/
      $year = $row->year;
      $fed_plan[$year] = ($fed_plan[$year] ?? 0) + $row->extence_fed_plan;
      $reg_plan[$year] = ($reg_plan[$year] ?? 0) + $row->extence_reg_plan;
      $mun_plan[$year] = ($mun_plan[$year] ?? 0) + $row->extence_mun_plan;
      $plan[$year] = ($plan[$year] ?? 0) + $row->extence_plan;
      $fed_fact[$year] = ($fed_fact[$year] ?? 0) + $row->income_fed_fact;
      $reg_fact[$year] = ($reg_fact[$year] ?? 0) + $row->income_reg_fact;
      $mun_fact[$year] = ($mun_fact[$year] ?? 0) + $row->income_mun_fact;
      $fact[$year] = ($fact[$year] ?? 0) + $row->income_fact;
    }
    foreach ($fed_plan as $year => $summ)
    {
      $fpl = $this->getNormsizer($summ);
      $rpl = $this->getNormsizer($reg_plan[$year]);
      $mpl = $this->getNormsizer($mun_plan[$year]);
      $ffct = $this->getNormsizer($fed_fact[$year]);
      $rfct = $this->getNormsizer($reg_fact[$year]);
      $mfct = $this->getNormsizer($mun_fact[$year]);
      $it_pln = $this->getNormsizer($plan[$year]);
      $it_fct = $this->getNormsizer($fact[$year]);
      $arYearsData[$year] = [
        'fed_fact' => $fpl,
        'reg_fact' => $rpl,
        'mun_fact' => $mpl,
        'fed_plan' => $ffct,
        'reg_plan' => $rfct,
        'mun_plan' => $mfct,
        'itog_fact' => $it_pln, //$fpl + $rpl + $mpl,
        'itog_plan' => $it_fct //$ffct + $rfct + $mfct спутанность переменных
      ];
    }

    /*
    \Drupal::logger('budget_data_service')->debug('<pre>@data</pre>', [
      '@data' => print_r($arYearsData, TRUE),
    ]);
    */

    return $arYearsData;

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
  }

  private function getNormsizer($size) {
    return round($size / 1000000, 1);
  }

}
