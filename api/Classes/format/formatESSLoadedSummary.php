<?php
require_once(dirname(__FILE__).'/../utils/utils.php');

class FormatESSLoadedSummary{
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

    $result = $this->format($data, $wordsdict, $argobj, $lang);

    return $result;
  }
  private function format($data, $wordsdict, $argobj, $lang){
#    $types = array('Total', 'Adv', 'Web');
    require_once getcwd().'/Classes/ess/ess.php';
    $logforview = (new Ess())->return_userconfig(@$argobj['uid'], @$argobj['lid'], 'logtypeforview');
    $types = explode(':', $logforview);
    array_unshift($types, 'Total');

    $logtypes = array();
    $lbldict = array();
    foreach($types as $idx => $ltype){
      if($ltype == 'Total') $lbldict[$ltype] = @$wordsdict['total'];
      if($ltype == 'Adv')   $lbldict[$ltype] = @$wordsdict['advlogdetail'];
      if($ltype == 'Web')   $lbldict[$ltype] = @$wordsdict['weblogdetail'];
    }

    $counttypes = array(
      array('type'=>'event', 'label'=>($lang == 'Japanese') ? @$wordsdict['evcnt'] : @$wordsdict['ev']),
      array('type'=>'user',  'label'=>($lang == 'Japanese') ? @$wordsdict['uucnt'] : @$wordsdict['uu']),
    );
    $datatypes = array(
      array('type'=>'total', 'label'=>@$wordsdict['total'], 'shortlabel'=>'total'),
      array('type'=>'useg', 'label'=>@$wordsdict['useg'], 'shortlabel'=>'segment'),
      array('type'=>'cv', 'label'=>@$wordsdict['cv'], 'shortlabel'=>'cv'),
    );

    $names_evt = @$counttypes[0]['type'];
    $names_usr = @$counttypes[1]['type'];
    $names_ttl = @$datatypes[0]['type'];
    $names_usg = @$datatypes[1]['type'];
    $names_cv  = @$datatypes[2]['type'];

    $dict = array(
      $names_evt => array(),
      $names_usr => array(),
    );
    foreach($types as $idx => $type){
  
      $targetidx = 0;
      foreach($data as $idx => $row){
        if(strstr($row[0], '-'.$type)){
          $targetidx = $idx;
        }
      }
      $header  = $data[$targetidx];
      $content = $data[$targetidx+1];
      $tuidx = -1;
      $suidx = -1;
      $cuidx = -1;
      $tpidx = -1;
      $spidx = -1;
      $cpidx = -1;
      foreach($header as $idx => $hdr){
          $hdr  = str_replace('\n','', $hdr);
          $hdr  = str_replace(' ','', $hdr);
          $tuidx = ($hdr == 'TUU-'.$type) ? $idx : $tuidx;
          $suidx = ($hdr == 'SEGUU-'.$type) ? $idx : $suidx;
          $cuidx = ($hdr == 'CVUU-'.$type) ? $idx : $cuidx;
          $tpidx = ($hdr == 'TPV-'.$type) ? $idx : $tpidx;
          $spidx = ($hdr == 'SEGPV-'.$type) ? $idx : $spidx;
          $cpidx = ($hdr == 'CVPV-'.$type) ? $idx : $cpidx;
      }
      $tuval = is_numeric(@$content[$tuidx]) ? number_format($content[$tuidx]) : '-';
      $suval = is_numeric(@$content[$suidx]) ? number_format($content[$suidx]) : '-';
      $cuval = is_numeric(@$content[$cuidx]) ? number_format($content[$cuidx]) : '-';
      $tpval = is_numeric(@$content[$tpidx]) ? number_format($content[$tpidx]) : '-';
      $spval = is_numeric(@$content[$spidx]) ? number_format($content[$spidx]) : '-';
      $cpval = is_numeric(@$content[$cpidx]) ? number_format($content[$cpidx]) : '-';


      if(!isset($dict[$names_evt][$names_ttl])) $dict[$names_evt][$names_ttl] = array();
      if(!isset($dict[$names_evt][$names_usg])) $dict[$names_evt][$names_usg] = array();
      if(!isset($dict[$names_evt][$names_cv]))  $dict[$names_evt][$names_cv]  = array();
      if(!isset($dict[$names_usr][$names_ttl])) $dict[$names_usr][$names_ttl] = array();
      if(!isset($dict[$names_usr][$names_usg])) $dict[$names_usr][$names_usg] = array();
      if(!isset($dict[$names_usr][$names_cv]))  $dict[$names_usr][$names_cv]  = array();
      $dict[$names_evt][$names_ttl][$type] = $tpval;
      $dict[$names_evt][$names_usg][$type] = $spval;
      $dict[$names_evt][$names_cv][$type]  = $cpval;
      $dict[$names_usr][$names_ttl][$type] = $tuval;
      $dict[$names_usr][$names_usg][$type] = $suval;
      $dict[$names_usr][$names_cv][$type]  = $cuval;
     
      array_push($logtypes, array(
        'type'  => $type,
        'label' => isset($lbldict[$type]) ? $lbldict[$type] : $type
      )); 
    }

    $result = array(
      'datadict'   => $dict,
      'logtypes'   => $logtypes,
      'counttypes' => $counttypes,
      'datatypes'  => $datatypes,
      'lang' => $lang
    );

    return $result;

  }

}


?>
