<?php
require_once(dirname(__FILE__).'/utils/utils.php');
require_once(dirname(__FILE__).'/ess/essParams.php');
require_once(dirname(__FILE__).'/ess/ess.php');
require_once(dirname(__FILE__).'/format/formatTable.php');
require_once(dirname(__FILE__).'/format/formatChart.php');
require_once(dirname(__FILE__).'/format/formatSummary.php');
require_once(dirname(__FILE__).'/format/formatCVPath.php');
require_once(dirname(__FILE__).'/format/formatESSLoadedSummary.php');

class GetContents{
  function __construct(){
  }
  public function run($argobj){
    $dataobj = (new GetContentsData())->run($argobj); # get data
    $data    = (new FormatData())->run($dataobj); # format data
    $data['wordsdict'] = (new Utils())->wordsdict()['toui-contents']; # add "words dictionaty" to return object.
    return $data;
  }
}

class GetContentsData{
  private $utils, $params, $putils, $top, $ess;
  function __construct(){
    $this->utils  = new Utils();
    $this->ess    = new Ess();
    $this->params = new EssParams();
    $this->putils = new EssParamsUtil();
  }
  public function run($argobj){
    $shellparam = $this->params->get_params($argobj);
    $dataarr    = $this->ess->get_arrdata_ess($shellparam);
    $argobj_cln = $this->putils->cleanse_params($argobj);
    $dataarr    = $this->process_data($dataarr, $argobj_cln);
    return array(
      'arr' => $dataarr,
      'arg' => $argobj_cln
    );
  }
  private function process_data($dataarr, $argobj){
    $contentType = @$argobj['ctype'];
    if($contentType == 'pathdist') $dataarr = $this->extract_distinfo_from_pathresult($dataarr);
    $dataarr     = ($contentType == 'logCountTrendMultiUser') ? $dataarr : @$dataarr[0]; # Only "logCountTrendMultiUser" case has a several dataset.
    $dataarr     = ($contentType == 'attrScore') ? $this->combine_scores_with_cost($dataarr, $argobj) : $dataarr;
    return $dataarr;
  }
  private function extract_distinfo_from_pathresult($dataarr){
    $data = array(array('type', 'max', 'min', 'avg'));
    $idxdict = array();
    $disttypes = $this->params->DISTTYPES;
    foreach($dataarr as $idx => $dobj){
      $titles = $dobj[0];
      $values = $dobj[2];
      if(!isset($idxdict['trg_max'])) foreach($titles as $tidx => $title) $idxdict[str_replace(' ','',$title)] = $tidx;
      array_push($data, array($disttypes[$idx], $values[$idxdict['trg_max']], $values[$idxdict['trg_min']], $values[$idxdict['trg_ave']]));
    }
    return array($data);
  }
  private function combine_scores_with_cost($data, $argobj){
    $colms  = @$argobj['colms'];
    $csvFlg = @$argobj['csvflg'];
    $costfpath = $this->ess->return_users_costfilepath(@$argobj['uid'], @$argobj['lid']);
    $lblcnt = 0;
    foreach($colms as $idx => $col){
      $lblcnt =  ($col=='') ? $lblcnt : $lblcnt + 1;
    }
    if(file_exists($costfpath) && $lblcnt==1){
      $fo = fopen($costfpath, 'r');
      $costs = array();
      if($fo){
        while($line = fgets($fo)){
          $cols = explode(',', $line);
          $name = $cols[0];
          $cost = $cols[1];
          $costs[$name] = $cost;
        }
      }
      fclose($fo);
      # combine
      $newdata = array();
      foreach($data as $idx => $row){
        $name  = str_replace('"','', implode(',', array_slice($row, 0, $lblcnt)));
        $score = $row[count($row)-1];
        $cost = isset($costs[$name]) ? $costs[$name] : '-';
        $cpa  = ($cost == '-') ? '-' : round(($cost/$score)*100) / 100;
        if($idx == 0){
          if($csvFlg == True){
            array_push($row, 'Cost');
          }
          array_push($row, 'CPA');
        }else{
          if($csvFlg == True){
            array_push($row, $cost);
          }
          array_push($row, $cpa);
        }
        array_push($newdata, $row);
      }
      $data = $newdata;
    }
    return $data;
  }
} 


class FormatData{
  private $utils;
  function __construct(){
    $this->utils = new Utils();
  }
  public function run($dataobj){
    $data   = @$dataobj['arr'];
    $argobj = @$dataobj['arg'];
    $ctype  = @$argobj['ctype'];
    $ttype  = @$argobj['tmpl'];
    $csvflg = @$argobj['csvflg'];
    if(!$csvflg){
      if($ttype=='table'){
        $result = (new FormatTable())->run($dataobj);
      }else if($ttype=='chart'){
        $result = (new FormatChart())->run($dataobj);
      }else if($ttype=='dump'){
        $result = $this->format_dump($data);
      }else if($ttype=='summ'){
        $result = (new FormatSummary())->run($dataobj);
      }else if($ttype=='path'){
        $result = (new FormatCVPath())->run($dataobj);
      }else if($ttype == 'essloadedsumm'){
        $result = (new FormatESSLoadedSummary())->run($dataobj);
      }
      $result = array('data'=>@$result);
    }else{
      $result = array(
                  'fname'=>$this->config_csvfile_name($argobj),
                  'data'=>($ctype == 'logCountTrendMultiUser') ? $this->build_csvdata($data) : $data
                );
    }
    return $result;
  }
  private function format_dump($data){
    # data format change?
    $newdata = array();
    foreach($data as $idx => $row){
      $row = $this->utils->return_cleanup_row($row);
      array_push($newdata, $row);
    }
    return $newdata;
  }
  private function build_csvdata($data){
    $newdata = array();
    foreach($data as $idx => $dataset){
      $newdata = array_merge($newdata, $dataset);
    }
    return $newdata;
  }
  private function config_csvfile_name($argobj){
    $contentType = @$argobj['ctype'];
    $fnamehead = 'summary-';
    $fnamecont = '';
    if($contentType=='summ'){
      $fnamecont = 'overview';
    }elseif($contentType=='logCountTrend'){
      $fnamecont = 'trend';
    }elseif($contentType=='logCountTrendMultiUser'){
      $fnamecont = 'trend-comp';
    }elseif($contentType=='distFreq'){
      $fnamecont = 'dist';
    }elseif($contentType=='distFreqPV'){
      $fnamecont = 'dist-cvpath';
    }elseif($contentType=='pieBrowsers'){
      $fnamecont = 'device';
    }elseif($contentType=='beforeAfterTable'){
      $fnamecont = 'table-cv-beforeafter';
    }elseif($contentType=='detailTableEventCount'){
      $fnamecont = 'table-ranking';
    }elseif($contentType=='detailTableEntry'){
      $fnamecont = 'table-entry';
    }elseif($contentType=='detailTableRegionMap'){
      $fnamecont = 'table-region';
    }elseif($contentType=='attrScore'){
      $fnamecont = 'table-attribution-score';
    }
    $yymmdd = date('Ymd');
    $hhmmss = date('His');
    $now    = $yymmdd.'-'.$hhmmss;
    $fname = ($fnamecont != '') ? $fnamehead.$fnamecont.'-'.$now : $fnamehead.$now;
    if($contentType=='dump'){
      $fname = 'logs-'.$now;
    }
    return $fname;
  }

}


?>
