<?php
require_once(dirname(__FILE__).'/../utils/utils.php');
require_once(dirname(__FILE__).'/../ess/essParams.php');
require_once(dirname(__FILE__).'/../ess/ess.php');
require_once(dirname(__FILE__).'/../utils/mysqldb.php');

class FormatCVPath{
  private $utils,$ess,$params,$db;
  function __construct(){
    $this->utils  = new Utils();
    $this->ess    = new Ess();
    $this->params = new EssParams();
    $this->db     = new MysqlQueryCondition();
    $this->DELIMITER_PATH = '::';
  }
  public function run($dataobj){
    $data   = @$dataobj['arr'];
    $argobj = @$dataobj['arg'];
    return $this->return_cj_paths($data, $argobj);
  }


  private function return_cj_paths($data, $argobj){
    $wordsdict = $this->utils->wordsdict();


    $pathdata = $this->return_cj_paths_formatdata($data, $argobj, $wordsdict);
    $d3objs = $pathdata['d3objs'];
  
    #  assign colors&shapes array
    $colorKeys = $pathdata['colorKeys'];
  
    # to get page max,
    $argpage = $argobj;
    $argpage['ctype'] = 'pathmax';
    $pagemax = intval($this->ess->get_vars_ess_fromparams($this->params->get_params($argpage)));
    

    $result = array(
      'symbols' => $this->return_filter_symbols($colorKeys),
      "d3objs"  => $d3objs,
      "totals"  => $pathdata['totals'],
      "pagemax" =>  $pagemax
    );
  
    return $result;
  }
  
  private function return_filter_symbols($colorKeys){
    $symbols = $this->db->get_symbols();
    $colors = $this->db->get_colors_list();
    $cnt  = 0;
    asort($colorKeys);
    foreach($colorKeys as $idx => $key){
      if(!isset($symbols[$key])){
        $color = $colors[$cnt%count($colors)];
        $symbols[$key] = array(
                            'color' => @$symbols['']['color'],
                            'icon'  => @$symbols['']['icon']
                          );
        $cnt++;
      }
    }
  
    return $symbols;
  }
  private function return_class_str($str){
      $str = mb_strtolower($str);
      $str = preg_replace('/[^0-9a-zA-Z]/', '-', $str);
      return $str;
  }

  private function process_decimal($num, $digit){
    if(!$num) $num = 0;
    $num = round($num * pow(10, $digit)) / (pow(10, $digit));
    $strnum = strval($num);
    $tmpcut = explode('.', $strnum);
    $decpart = @$tmpcut[1];
    for($i=strlen($decpart); $i<$digit; $i++){
      $decpart .= '0';
    }
    return $tmpcut[0].'.'.$decpart;
  }  

  private function return_cj_paths_formatdata($data, $argobj, $wordsdict){
    $pathord   = @$argobj['pathord'];
    $tillcvflg = @$argobj['tillcv'];
    $tpdepth   = @$argobj['tpdepth'];
    $distlabel = @$argobj['dist'];
    $virtualflg = ($pathord==1) || ($pathord==0 && $tillcvflg==1);

    $d3obj   = array();
    $colorKeys = array();
    $shapeKeys = array();
    $header = array();
    foreach($data as $row_num => $row){
      $rslt_path = array();
      $tobj = array();
      $originalPathArr = array();
      foreach($row as $col_idx => $cellValOriginal){
        $cellVal = str_replace('"','',$cellValOriginal);
        $cellVal = str_replace(' ','',$cellVal);
        $cellVal = str_replace('\n','',$cellVal);
        if($row_num == 0){
          array_push($header, $cellVal);
        }else if($row_num == 1){
          if(strstr($cellVal, 'Total=')){
            $totalPathNum = str_replace('Total=','',$cellVal);
          }
          if(strstr($cellVal, 'CVP=')){
            $cvPathNum = str_replace('CVP=','',$cellVal);
          }
        }else{
          $colname = isset($header[$col_idx]) ? $header[$col_idx] : '';
          if(preg_match('/p[0-9]/', $colname)){
            if($cellVal != ''){
              $tps = explode('->', $cellVal); 
              foreach($tps as $idx => $tp){
                $path_obj  = $this->return_cj_paths_format_touchpoint($tp);
                $colorKey  = $path_obj['colorKey'];
                $shapeKey  = $path_obj['shapeKey'];
                array_push($rslt_path, $path_obj);
                if(!in_array($colorKey, $colorKeys)){
                  array_push($colorKeys, $colorKey);
                }
                if(!in_array($shapeKey, $shapeKeys)){
                  array_push($shapeKeys, $shapeKey);
                }
              }
            }
            if($col_idx < $tpdepth){
              array_push($originalPathArr, $cellValOriginal);
            }
          }else{
            if(!is_null($colname) && !is_null($cellVal)){
              if($colname!=='null' && $cellVal!='null'){
                if($colname == 'skey'){
                  $tobj[$colname] = $this->return_cj_paths_format_skey($cellVal);
                }else{
                  $cellValObj = $this->return_cj_path_format_vals($colname, $cellVal, $wordsdict);
                  $tobj[$colname] = $cellValObj;
                }
              }
            }
          }
        }
      }
      $tobj['original_path_arr'] = $originalPathArr;
        $tobj['path'] = $rslt_path;
  #-------------------------------------------------------
  #  rewrite with ESS!!!!!!!!!!!! This is temp!!!!
  #-------------------------------------------------------
  $cnt_t   = isset($tobj['trg_cnt']['value']) ? $tobj['trg_cnt']['value'] : 0;
  $cnt_r   = isset($tobj['rfr_cnt']['value']) ? $tobj['rfr_cnt']['value'] : 0;
  $cvrate  = (($cnt_t + $cnt_r) > 0) ? round($cnt_t / ($cnt_t + $cnt_r) * 100, 2) : 0;
  $sum_str_title = @$wordsdict[$distlabel];
  $tobj['summ_str'] = $sum_str_title.':'
             .(isset($tobj['trg_max']) ? $tobj['trg_max']['label'].$this->utils->return_val_comma_label($tobj['trg_max']['value']) : '').','
             .(isset($tobj['trg_min']) ? $tobj['trg_min']['label'].$tobj['trg_min']['value'] : '').','
             .(isset($tobj['trg_ave']) ? $tobj['trg_ave']['label'].$tobj['trg_ave']['value'] : '');
  $cvrate_label  = $this->process_decimal($cvrate, 1);
  $cvrate_labels = explode('.', $cvrate_label);
  $tobj['cvrate'] = array(
    #'label' => $cvrate.'%',
    'label' => $cvrate_label,
    'labeldetail' => array(
      'int' => @$cvrate_labels[0],
      'dec' => @$cvrate_labels[1]
    ),
    'value' => $cvrate,
  );
  $tobj['trg_cnt']['label'] = isset($tobj['trg_cnt']) ? number_format($tobj['trg_cnt']['value']) : '';
  $tobj['rfr_cnt']['label'] = isset($tobj['rfr_cnt']) ? number_format($tobj['rfr_cnt']['value']) : '';
  #-------------------------------------------------------
  #  rewrite with ESS!!!!!!!!!!!! This is temp!!!!
  #-------------------------------------------------------
        $isvirtual = False;
        if($cnt_t>0 && $virtualflg){
          $isvirtual = True;
          $virtualcv = $this->return_cj_paths_format_virtual_cvpoins();
          array_push($tobj['path'], $virtualcv);
        }
        $tobj['isvirtual'] = $isvirtual;
        if($row_num > 1 && (count($originalPathArr) > 0 && isset($tobj['trg_cnt']['value']))){
          array_push($d3obj, $tobj);
        }
    
    }
    #  sort keys
    sort($colorKeys);
    sort($shapeKeys);
    array_unshift($colorKeys, "cv");
    array_unshift($shapeKeys, "cv");
    return array(
      'd3objs'  => $d3obj,
      'colorKeys' => $colorKeys,
      'shapeKeys' => $shapeKeys,
      'totals'   => array(
        'all' => $totalPathNum,
        'cv'  => $cvPathNum,
      )
    );
  }
  
  private function return_cj_paths_format_skey($skey){
    $skeies = explode($this->DELIMITER_PATH, $skey);
    $skeies_top5 = array_slice($skeies, 0, 5);
    return $skeies_top5;
  }
  
  private function return_cj_paths_format_touchpoint($tp){
    $tps = explode($this->DELIMITER_PATH, $tp);
    $colorKeyIdx = (count($tps) > 1) ? 1 : 0;
    $keys   = explode(':', $tps[$colorKeyIdx]);
    $colorKey = $keys[0];
    $shapeKey = $keys[0];
    $label  = $tps[0];
    $sublabel = isset($tps[1]) ? $tps[1] : '';
    return array(
      'colorKey' => $this->return_class_str($colorKey),
      'shapeKey' => $this->return_class_str($shapeKey),
      'label'  => $label,
      'sublabel' => $sublabel
    ); 
  }
  
  private function return_cj_paths_format_virtual_cvpoins(){
    return array(
      'colorKey' => 'fakecv',
      'shapeKey' => 'fakecv',
      'label'  => 'CV',
      'sublabel' => ''
    );
  }
  private function return_cj_path_format_vals($colname, $val, $wordsdict){
    $trans_arr = array(
      'trg_max' => @$wordsdict['max'].@$wordsdict['spacebtwwords'],
      'trg_min' => @$wordsdict['min'].@$wordsdict['spacebtwwords'],
      'trg_ave' => @$wordsdict['avg'].@$wordsdict['spacebtwwords'],
    ); 
  
    return array(
      'label' => isset($trans_arr[$colname]) ? $trans_arr[$colname] : $colname,
      'value' => (int) $val
    );
  }
  private function convert_paging_obj_to_shell_para($obj){
    $from = isset($obj['status']['from']) ? $obj['status']['from'] : 1;
    $to   = isset($obj['status']['to'])   ? $obj['status']['to']   : 11;
    return $from.' '.$to;
  }
  
}

?>
