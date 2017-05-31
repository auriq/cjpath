<?php
require_once(dirname(__FILE__).'/../utils/utils.php');

class FormatChart{
  private $utils,$MAX_NVD3_ROW;
  function __construct(){
    $this->utils = new Utils();
    $this->MAX_NVD3_ROW = 1000;
  }
  public function run($dataobj){
    $data   = @$dataobj['arr'];
    $argobj = @$dataobj['arg'];
    
    $result = array();
    $contenttype = @$argobj['ctype'];
    $charttype = @$argobj['dtype'];
    $wordsdict = $this->utils->wordsdict();
    if($charttype == 'pieChart'){
      $result['nvd3'] = $this->format_nvd3_pie($data);
      if($contenttype == 'pieBrowsers'){
        $result['colorKeys'] = $this->return_unique_label_list_from_nvd3($result['nvd3']);
      }
    }else if(in_array($charttype, array('lineChart', 'linePlusBarChart'))){
      if(in_array($contenttype, array('logCountTrend', 'logCountTrendMultiUser'))){
        $idxdict = array(
          'date' => 0,
          'pv'   => 1,
          'ss'   => 2,
          'uu'   => 3
        );
        $yAxisType  = @$argobj['yAxisType'];
        $bintype    = @$argobj['bintype'];
        $usersegs   = @$argobj['usersegmentkeys'];
        $bintransfunc = 'return_asis';
        if($bintype == '%w' || $bintype == '%u'){
          $bintransfunc = 'translate_week';
        }
        if($contenttype == 'logCountTrendMultiUser'){
          $result = $this->format_multitrends_nvd3_line($data, $idxdict, $yAxisType, $usersegs, 
                                                           $bintransfunc, $bintype, $wordsdict);
        }else if($contenttype == 'logCountTrend'){
          $result = $this->format_trends_nvd3_barplusline($data, $idxdict, $yAxisType, $usersegs, 
                                                           $bintransfunc, $bintype, $wordsdict);
        }
      }else if(in_array($contenttype, array('distFreq', 'distFreqPV'))){
        $result = $this->format_dist_nvd3_line($data, $argobj, $wordsdict); 
      }
    }else if($charttype == ''){
    }


    return $result;
  }
  private function format_nvd3_pie($data){
      $result = array();
      foreach($data as $idx => $row){
          $obj = array(
            'label' => str_replace('"','',$row[0]),
            'value' => (float) str_replace('"','',$row[1])
          );
          if($row[1] > 0){
              array_push($result, $obj);
          }
      }
      return $result;
  }
  private function format_multitrends_nvd3_line($data, $idxdict, $yAxisType, $keyvals, $bintransfunc, $bintype, $wordsdict){
    $nvd3  = array();
    $xaxis = array();
    $overMaxFlg = False;
    $finishFlg  = False;
    foreach($data as $didx => $dataset){
      $linearr = array();
      foreach($dataset as $row_num => $row){
        if(!$finishFlg){
          if($row_num > 0){
            if($row_num < $this->MAX_NVD3_ROW){
              $cells = $row;
              $date  = isset($cells[$idxdict['date']]) ? str_replace('"','',$cells[$idxdict['date']]): 0;
              $value = isset($cells[$idxdict[$yAxisType]])  ? $cells[$idxdict[$yAxisType]]   : 0;
              $date  = $this->$bintransfunc($date);
              if($date != ''){
                array_push($linearr, array($row_num-1, (int) $value));
                if(!in_array($date, $xaxis)){
                  array_push($xaxis, $date);
                }
              }else{
                $finishFlg = True;
              }
            }else{
              $overMaxFlg = True;
            }
          }
        }
      }
      array_push($nvd3, array(
        'key'    => $keyvals[$didx],
        'values' => $linearr
      ));
    }
    return array(
      'nvd3'         => $nvd3,
      'xAxis_values' => $xaxis,
      'yAxis_label'  => @$wordsdict[$yAxisType].@$wordsdict['cnt'],
      'xAxis_label'  => $this->return_xaxis_label($bintype),
      'overmaxmessage' => ($overMaxFlg) ? @$wordsdict['overmaxmessage'] : ''
    );
  }
  private function format_trends_nvd3_barplusline($data, $idxdict, $yAxisType, $keyvals, $bintransfunc, $bintype, $wordsdict){
    $bararr  = array();
    $linearr = array();
    $linearrSess = array();
    $xaxis   = array();
    $overMaxFlg = False;
    $finishFlg = False;
    foreach($data as $row_num => $row){
      if(!$finishFlg){
        if($row_num > 0){
          if($row_num < $this->MAX_NVD3_ROW){
            $cells = $row;
            $date = isset($cells[$idxdict['date']]) ? str_replace('"','',$cells[$idxdict['date']]): 0;
            $pv   = isset($cells[$idxdict['pv']])   ? $cells[$idxdict['pv']]   : 0;
            $ss   = isset($cells[$idxdict['ss']])   ? $cells[$idxdict['ss']]   : 0;
            $uu   = isset($cells[$idxdict['uu']])   ? $cells[$idxdict['uu']]   : 0;
            $date = $this->$bintransfunc($date);
            if($date != ''){
              array_push($bararr,  array($row_num-1, (int) $uu));
              array_push($linearr, array($row_num-1, (int) $pv));
              array_push($linearrSess, array($row_num-1, (int) $ss));
              array_push($xaxis, $date);
            }else{
              $finishFlg = True;
            }
          }else{
            $overMaxFlg = True;
          }
        }
      }
    }
  
    $result = array(
      'nvd3' => array(
        array(
          'key' => @$wordsdict['pv'],
          'values' => $linearr
        ),
        array(
          'key' => @$wordsdict['ss'],
          'values' => $linearrSess
        ),
        array(
          'key' => @$wordsdict['uu'],
          'bar' => True,
          'values' => $bararr
        )
      ),
      'yAxis_label'  => array(@$wordsdict['uu'].@$wordsdict['cnt'], @$wordsdict['pv'].@$wordsdict['/'].@$wordsdict['ss'].@$wordsdict['cnt']),
      'yAxis_values' => $xaxis,
      'xAxis_label'  => $this->return_xaxis_label($bintype),
      'xAxis_values' => $xaxis,
      'overmaxmessage' => ($overMaxFlg) ? @$wordsdict['overmaxmessage'] : ''
    );
    if($bintype == '%F %H %M'){
      # should not show UU, because it'd be empty (it slow down dramatically, so ess script does not calc for UU by minute.)
      unset($result['nvd3'][$idxdict['uu']]);
      unset($result['xaxis'][$idxdict['uu']]);
    }
    return $result;
  }
  private function return_xaxis_label($binvalue){
    $binname = '';
    if($binvalue == '%F'){
      $binname = 'daily';
    }else if($binvalue == '%F %H %M'){
      $binname = 'by minute';
    }else if($binvalue == '%F %H'){
      $binname = 'by hour';
    }else if($binvalue == '%W' || $binvalue == '%U'){
      $binname = 'weekly';
    }else if($binvalue == '%m'){
      $binname = 'monthly';
    }else if($binvalue == '%H'){
      $binname = 'hour';
    }else if($binvalue == '%u' || $binvalue == '%w'){
      $binname = 'by day';
    }
    return $binname;
  }
  private function return_asis($data){
    return $data;
  }
  private function translate_week($date){
    return $this->utils->translate_week($date);
  }
  private function format_dist_nvd3_line($data, $argobj, $wordsdict){
    $distType   = @$argobj['xAxisType'];
    $distDepth  = @$argobj['ddepth'];
    $yAxisType  = @$argobj['yAxisType'];
    $dist_obj_t = array();
    $dist_obj_r = array();
    $dist_arr   = array();
    $sum_t = 0;
    $sum_r = 0;
    $val_min = 9999;
    $val_max = -9999;
    $val_avg = 0;
    $val_frq_max = 0;
    $val_frq_bin = 0;
    $max_bin = 0;
    foreach($data as $rownum => $rowObj){
      $data_bin = isset($rowObj[0]) ? (int) str_replace('"','',$rowObj[0]): 0;
      $data_val_t = isset($rowObj[1]) ? (int) $rowObj[1] : 0;
      $data_val_r = isset($rowObj[2]) ? (int) $rowObj[2] : 0;
      $dist = ($data_bin < $distDepth) ? $data_bin : $distDepth;
      $val_t= (isset($dist_obj_t[$dist])) ? $dist_obj_t[$dist]['y'] + $data_val_t : $data_val_t;
      $val_r= (isset($dist_obj_r[$dist])) ? $dist_obj_r[$dist]['y'] + $data_val_r : $data_val_r;
      $dist_obj_t[$dist] = array(
        "x" => $dist,
        "y" => $val_t
      );
      $dist_obj_r[$dist] = array(
        "x" => $dist,
        "y" => $val_r
      );
      if(!in_array($dist, $dist_arr)){
        array_push($dist_arr, $dist);
      }
      $val_min = ($data_bin < $val_min) ? $data_bin : $val_min;
      $val_max = ($data_bin > $val_max) ? $data_bin : $val_max;
      $val_avg += $data_bin * $data_val_t;
      $val_frq_max = ($data_val_t > $val_frq_max) ? $data_val_t : $val_frq_max;
      $val_frq_bin = ($val_frq_max == $data_val_t) ? $data_bin : $val_frq_bin;
      $sum_t += $data_val_t;
      $sum_r += $data_val_r;
  
      $max_bin = $data_bin;
    }
    $val_avg = ($sum_t > 0) ? $val_avg / $sum_t : 0;
    $xAxi       = $this->return_path_length_distribution_xaxis($dist_arr, $distType, $wordsdict);
    $xAxis_vals = $xAxi['xAxis_vals'];
    $xAxis_lbls = $xAxi['xAxis_lbls'];
    $arr_cumu_t = array();
    $arr_actu_t = array();
    $arr_cumu_r = array();
    $arr_actu_r = array();
    $cumu_t = 0;
    $cumu_r = 0;
    foreach($xAxis_vals as $idx => $dist){
      # target period
      $rslt_t = $this->return_path_dist_d3objs_eachpoint($idx, $dist_obj_t, $dist, $sum_t, $cumu_t);
      array_push($arr_cumu_t, $rslt_t['obj_cumu']);
      array_push($arr_actu_t, $rslt_t['obj_actl']);
      $cumu_t = $rslt_t['cumu'];
      # reference period
      $rslt_r = $this->return_path_dist_d3objs_eachpoint($idx, $dist_obj_r, $dist, $sum_r, $cumu_r);
      array_push($arr_cumu_r, $rslt_r['obj_cumu']);
      array_push($arr_actu_r, $rslt_r['obj_actl']);
      $cumu_r = $rslt_r['cumu'];
    }
    $tailrate = 0;
    if($max_bin > $distDepth){
      $tailrate = $arr_cumu_t[count($arr_cumu_t)-1]['y'] - $arr_cumu_t[count($arr_cumu_t)-2]['y'];
      array_pop($arr_actu_t);
      array_pop($arr_actu_r);
      array_pop($arr_cumu_t);
      array_pop($arr_cumu_r);
    }
    $arr_actu = array($arr_actu_t, $arr_actu_r);
    $arr_cumu = array($arr_cumu_t, $arr_cumu_r);
    
    
    $chartData = array();
    if($yAxisType == 'cumu'){
      $chartData =
        ($distType == 'pv') ?
          array(
            array(
              'key'    => @$wordsdict['cvcumu'],
              'values' => $arr_cumu_t
            ),
            array(
              'key'    => @$wordsdict['noncvcumu'],
              'values' => $arr_cumu_r
            )
          )
        :
          array(
            array(
              'key'    => @$wordsdict['cvcumu'],
              'values' => $arr_cumu_t
            ),
          );
    }else{
        $rates = array();
        if($distType=='pv'){
          foreach($arr_actu_t as $idx => $tobj){
            $t = $tobj['y'];
            $r = $arr_actu_r[$idx]['y'];
            array_push($rates, array($idx,  (($t + $r > 0) ? ($t / ($t + $r)) : 0)));
          }
        }
        $chartData =
          ($distType == 'pv') ?
            array(
              array(
                'key' => @$wordsdict['cvuurate'],
                'bar' => True,
                'values' => $rates
              ),
              array(
                'key' => @$wordsdict['cvuucnt'],
                'bar' => False,
                'values' => $arr_actu_t
              ),
              array(
                'key' => @$wordsdict['noncvuucnt'],
                'bar' => False,
                'values' => $arr_actu_r
              )
            )
          :
            array(
              array(
                'key' => @$wordsdict['cvuucnt'],
                'values' => $arr_actu_t
              )
            );
    }
    
    if($yAxisType == 'cumu'){
    }
    return array(
      'nvd3'    => $chartData,
      'summary' => array(
        array('title'=>@$wordsdict['max'].@$wordsdict['spacebtwwords'].@$wordsdict[$distType], 'value'=>$val_max),
        array('title'=>@$wordsdict['min'].@$wordsdict['spacebtwwords'].@$wordsdict[$distType], 'value'=>$val_min),
        array('title'=>@$wordsdict['avg'].@$wordsdict['spacebtwwords'].@$wordsdict[$distType], 'value'=>$val_avg),
        array('title'=>@$wordsdict['mostfreq'].@$wordsdict['spacebtwwords'].@$wordsdict[$distType], 'value'=>$val_frq_bin)
      ),
      'tailrate_title' => @$wordsdict['tailrate'],
      'tailrate'       => $tailrate,
      'xAxis_values'   => $xAxis_lbls,
      'xAxis_label'    => ($distType=='pv') ? @$wordsdict['evtocv'] : @$wordsdict[$distType].@$wordsdict['spacebtwwords'].@$wordsdict['cnt'],
      'yAxis_label'    => ($distType=='pv')
                          ? (($yAxisType=='cumu') ? array('',@$wordsdict['cvcumu']) : array(@$wordsdict['cvuurate'], @$wordsdict['cvuucnt']))
                          : (@$wordsdict['cv'.$yAxisType]),
    );

  }
  private function return_path_length_distribution_xaxis($dist_arr, $distType, $wordsdict){
    $xAxis_vals = array();
    $xAxis_lbls = array();
    if(count($dist_arr) > 0){
        $dist_x_max = max($dist_arr);
        $dist_x_min = min($dist_arr);
        for($i = $dist_x_min; $i<=$dist_x_max; $i++) {
            $i_unit = ($i<=1 || $distType == 'imp') 
                     ? $i.@$wordsdict['spacebtwwords'].@$wordsdict[$distType] 
                     : $i.@$wordsdict['spacebtwwords'].@$wordsdict[$distType].@$wordsdict['plural'];
            array_push($xAxis_vals, $i);
            array_push($xAxis_lbls, $i_unit);
        }
    }
    return array(
        'xAxis_vals' => $xAxis_vals,
        'xAxis_lbls' => $xAxis_lbls
    );
  }
  private function return_path_dist_d3objs_eachpoint($idx, $dist_obj, $dist, $sum, $cumu){
    $obj = isset($dist_obj[$dist]) ? $dist_obj[$dist] : array();
    $val = isset($obj['y']) ? $obj['y'] : 0;
    $cumu = $cumu + $val;
    $tcumu = array('x'=>$idx, 'y'=>($sum > 0) ? $cumu/$sum : 0);
    $tactu = array('x'=>$idx, 'y'=>$val);
    return array(
        'obj_cumu' => $tcumu,
        'obj_actl' => $tactu,
        'cumu'     => $cumu
    );
  }
  private function return_unique_label_list_from_nvd3($nvd3){
    $labels = array();
    foreach($nvd3 as $idx => $row){
      array_push($labels, $row['label']);
    }
    return $labels;
  }
}


?>
