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
      // расход - это "факт", ассигнования - "план"
      $year = $row->year;
      $DOP_FK_TMP = $row->dop_fk;
      $check_digit = mb_substr($DOP_FK_TMP, 0, 1);
      $RASHOD_PO_LS_FED_TMP = $row->income_fed_fact;
      $RASHOD_PO_LS_REG_TMP = $row->income_reg_fact;
      $RASHOD_PO_LS_MYN_TMP = $row->income_mun_fact;
      $RASHOD_PO_LS_TMP = $row->income_fact;
      $ASS_FED_TMP = $row->extence_fed_plan;
      $ASS_REG_TMP = $row->extence_reg_plan;
      $ASS_MYN_TMP = $row->extence_mun_plan;
      $ASS_TMP = $row->extence_plan;

      if ($RASHOD_PO_LS_FED_TMP == 0 && $RASHOD_PO_LS_REG_TMP == 0 && $RASHOD_PO_LS_MYN_TMP == 0)
      {
        //if($year==$god)
        if ($check_digit == 8)
        {
          $RASHOD_PO_LS_FED_TMP=$RASHOD_PO_LS_TMP;
        }
        elseif ($check_digit == 6 || $check_digit == 9)
        {
          $RASHOD_PO_LS_REG_TMP = $RASHOD_PO_LS_TMP;
        }
        else
        {
          $RASHOD_PO_LS_MYN_TMP = $RASHOD_PO_LS_TMP;
        }
      }
      if ($ASS_FED_TMP == 0 && $ASS_REG_TMP == 0 && $ASS_MYN_TMP == 0)
      {
        if ($check_digit == 8)
        {
          $ASS_FED_TMP = $ASS_TMP;
        }
        elseif ($check_digit == 6 || $check_digit == 9)
        {
          $ASS_REG_TMP = $ASS_TMP;
        }
        else
        {
          $ASS_MYN_TMP = $ASS_TMP;
        }
      }

      $fed_plan[$year] = ($fed_plan[$year] ?? 0) + $ASS_FED_TMP;
      $reg_plan[$year] = ($reg_plan[$year] ?? 0) + $ASS_REG_TMP;
      $mun_plan[$year] = ($mun_plan[$year] ?? 0) + $ASS_MYN_TMP;
      $plan[$year] = ($plan[$year] ?? 0) + $ASS_TMP;
      $fed_fact[$year] = ($fed_fact[$year] ?? 0) + $RASHOD_PO_LS_FED_TMP;
      $reg_fact[$year] = ($reg_fact[$year] ?? 0) + $RASHOD_PO_LS_REG_TMP;
      $mun_fact[$year] = ($mun_fact[$year] ?? 0) + $RASHOD_PO_LS_MYN_TMP;
      $fact[$year] = ($fact[$year] ?? 0) + $RASHOD_PO_LS_TMP;

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

  }

  private function getNormsizer($size) {
    return round($size / 1000000, 1);
  }

}
