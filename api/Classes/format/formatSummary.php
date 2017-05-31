<?php
require_once(dirname(__FILE__).'/../utils/utils.php');

class FormatSummary{
  private $utils;
  function __construct(){
    $this->utils = new Utils();
    $this->sigdigi = 3;
  }
  public function run($dataobj){
    $data      = @$dataobj['arr'];
    $argobj    = @$dataobj['arg'];
    $lang      = @$argobj['lang'];
    $wordsdict = $this->utils->wordsdict();
    
    $formdata = $this->return_global_summary_format($data, $argobj, $wordsdict);

    $result = array(
      'repvalue' => @$formdata['count']['data'][0],
      'pathlens' => @$formdata['pathlen'],
      'pies'     => @$formdata['pie'],
      'lang'     => $lang,
      'dict'   => array(
        'cv'        => @$wordsdict['cv-full'],
        'mddl'      => @$wordsdict['-'],
        'cvlenhead' => @$wordsdict['cvlenhead'],
        'cvlentail' => @$wordsdict['cvlentail']
      )
    );

    return $result;
  }

  private function return_global_summary_format($data, $argobj, $wordsdict){
    $period_start = @$argobj['sdate'];
    $period_end   = @$argobj['edate'];
    $result = array();
    $titles = array(
      'count' => 'count',
      'path'  => 'Conversion Path Lengths',
      'pie'   => 'pie'
    );
    #  read data
    foreach($data as $row_num => $row){
      if($row_num > 0){
        $val_base=-1;
        $label_name='';
        $label_calc='';
        $label_para='';
        $tobj=array();
        foreach($row as $col_num => $cell){
          if($label_name==''){
            $label_name = explode('_',$cell);
            $label_calc = $label_name[0];
            $label_para = isset($label_name[1]) ? $label_name[1] : '';
          }else if($val_base==-1){
            $val_base = (float) $cell;
            if(count($row) <3){
              $tobj = $this->return_global_summary_format_eachrow($label_calc, $label_para,
                                                           $val_base, (float) $cell,
                                                           $period_start, $period_end, $wordsdict);
            }
          }else{
            $tobj = $this->return_global_summary_format_eachrow($label_calc, $label_para,
                                                         $val_base, (float) $cell,
                                                         $period_start, $period_end, $wordsdict);
          }
        }
        #    push this $tobj to $result array.
        if($label_calc != ''){
          if(!isset($result[$label_calc])){
            $result[$label_calc] = array(
              'title' => isset($titles[$label_calc]) ? $titles[$label_calc] : '',
              'data' => array()
            );
          }
          array_push($result[$label_calc]['data'], $tobj);
        }
      }
    }
    return $result;
  }
  private function return_global_summary_format_eachrow($label_calc, $label_para, $val_base, $val_comp,
                         $period_start, $period_end, $wordsdict){
    $tobj = array();
    if($label_calc == 'count'){
      $rate = $this->return_global_summary_common_rate($val_base, $val_comp);
      $tobj = array(
        'main' => array(
          'value' => $val_base,
          'label' => number_format($val_base),
          'period' => $this->return_date_label($period_start, $period_end)
        ),
        'comp' => array(
          'rate' => array(
            "value" => $rate,
            "label" => $this->return_val_percentage_label($rate)
          ),
          'act' => array(
            'value' => $val_comp,
            'label' => $this->utils->return_val_comma_label($val_comp),
            'period' => $this->return_date_label($period_start, $period_end)
          )
        )
      );
    }else if($label_calc == 'pathlen'){
      $pathlen = $label_para;
      $diff = $val_base - $val_comp;
      $tobj = array(
        "act_value" => array(
          "title" => $pathlen,
          "value" => $this->return_val_percentage_label($val_base)
        ),
        "comp_val" => array(
          "period" => $this->return_date_label($period_start, $period_end),
          "value" => array(
            "value" => $diff,
            "label" => $this->return_val_percentage_label($diff)
          )
        )
      );
    }else if($label_calc == 'pie'){
      $rate_comp = $this->return_global_summary_common_rate($val_base, $val_comp);
      $diff = $val_base - $val_comp;
      $intrName  = @$wordsdict[$label_para];
      $tobj = array(
        "title" => $intrName,
        "comp_value" => array(
          "label" => $this->return_date_label($period_start, $period_end),
          "value" => array(
            "label" => $this->return_val_percentage_label($diff),
            "value" => $diff
          )
        ),
        "legend_label" => array($intrName, "Non ".$intrName),
        "nvd3values" => array(
          array("label"=> $intrName, "value"=>$val_base),
          array("label"=> "Non ".$intrName, "value"=>(1-$val_base))
        )
      );
    }
    return $tobj;
  }

  private function return_global_summary_common_rate($value_m, $value_c){
    $val = '-';
    if($value_c > 0){
      $val = ($value_m - $value_c) / $value_c;
    }
    return $val;
  }

  private function return_date_label($date_from, $date_to){
    return array(
      'from' => str_replace('-','/',$date_from),
      'to'   => str_replace('-','/',$date_to)
    );
  }

  private function return_val_percentage_label($val){
    $str = '-';
    if(is_numeric($val)){
      $val = $this->return_significant_digits_value($val*100);
      $str = $val.'%';
    }
    return $str;
  }

  private function return_significant_digits_value($val){
    $str = (string) $val;
    $newStr = '';
    $digCnt = 0;
    $cntFlg = false;
    $decFlg = false;
    for($i=0,$len=strlen($str); $i<$len; $i++){
      $char = $str[$i];
      if($char != '.'){
        if((int) $char > 0){
          $cntFlg = true;
        }
        if($cntFlg){
          $digCnt++;
        }
      }else{
        $decFlg = true;
      }
      if($digCnt <= $this->sigdigi){
        $newStr = $newStr.$char;
      }
    }
    $result = ($decFlg) ? (float) $newStr : $val;
    return $result;
  }

}


?>
