<?php
require_once(dirname(__FILE__).'/../utils/utils.php');

class FormatTable{
  private $utils;
  function __construct(){
    $this->utils = new Utils();
  }
  public function run($dataobj){
    $argobj = @$dataobj['arg'];
    $ctype  = @$argobj['ctype'];
    $data   = $this->translate(@$dataobj['arr'], $ctype, $argobj);
    $colms  = isset($argobj['colmswtfilter']['colms']) ? $argobj['colmswtfilter']['colms'] : @$argobj['colms'];
    $lang   = isset($argobj['lang']) ? $argobj['lang'] : 'English';
    $csvflg = @$argobj['csvflg'];
    $result = array();
    if(!$csvflg){
      $result = $this->format_angular_table($ctype, $data, $colms, $lang);
    }
    return $result;
  }
  private function format_angular_table($ctype, $data, $colms, $lang){
    # angularjs seems to prefer object rather than array??
    $lblidx = 1;
    if(is_array($colms)){
      $lblidx=0;
      foreach($colms as $idx => $colm){
        if($colm != ''){
          $lblidx += 1;
        }
      }
      $lblidx = (count($colms) == 0) ? 1 : $lblidx; # for "region"
    }
    $angtbl = array();
    $angthd = array();
    $frmdict = $this->return_numbers_format($data);
    foreach($data as $idx => $row){
        if($idx == 0){
          $row = $this->return_table_header_translate($ctype, $row, $lang);
          $labels = array_slice($row, 0, $lblidx); 
          $values = array_slice($row, $lblidx); 
        }else{
          if($idx == 1){
            if(strstr($ctype, 'rankingTable') && $row[0]==''){
              $row[0] = 'SUM';
            }
          }
          $row = $this->utils->return_cleanup_row($row);
          $values = array();
          $labels = array();
          foreach($row as $cidx=>$col){
            if($cidx >= $lblidx){
              array_push($values, array(
                'format' => $frmdict[$cidx]['format'],
                'value'  => $col
              ));
            }else{
              array_push($labels, $col);
            }
          }
        }
        $tobj = array(
          'labels' => $labels,
          'values' => $values
        );
        if(count($row) > 1){
          if($idx == 0){
            $angthd = array($tobj);
          }else{
            array_push($angtbl, $tobj);
          }
        }
        $row = array();
    }
    return array(
      'thead' => $angthd,
      'tbody' => $angtbl
    );
  }
  private function translate($arr, $ctype, $argobj){
    $targets = array(
      array(
        'colm'  => 'i:ent',
        'ctype' => 'detailTableEntry'
      )
    );
    $colms = isset($argobj['colmswtfilter']['colms']) ? $argobj['colmswtfilter']['colms'] : array();
    $translation = $this->utils->wordsdict()['table-labels'];
    $result = $arr;
    foreach($colms as $cidx => $colm){
      foreach($targets as $tidx => $target){
        if($ctype == @$target['ctype'] && $colm == @$target['colm']){
          foreach($result as $ridx => $row){
            $result[$ridx][$cidx] = isset($translation[$row[$cidx]]) ? $translation[$row[$cidx]] : $row[$cidx];
          }
        }
      }
    }
    return $result;
  }
  private function return_table_header_translate($ctype, $header, $lang){
      $translation = $this->utils->wordsdict()['table-header'];
      $translation['count'] = ($ctype=='detailTableRegionMap') ? $translation['uu'] : $translation['count']; 
      $new_header = array();
      foreach($header as $idx => $hdr){
          $hdr = str_replace('"','',$hdr);
          $newhdr = isset($translation[$hdr]) ? $translation[$hdr] : $hdr;
          array_push($new_header, $newhdr);
      }
      return $new_header;
  }
  private function return_numbers_format($data){
    $dict = array();
    $scores = array(
      'number'     => 0,
      'decimal'    => 1,
      'percentage' => 2,
      'string'     => 3
    );
    foreach($data as $ridx => $row){
      foreach($row as $cidx => $col){
        if($ridx > 0){
          $fom = 'string';
          if(preg_match("/^[0-9]+$/", $col)){
            $fom = 'number';
          }elseif(preg_match("/^[0-9.]+$/", $col)){
            $fom = 'decimal';
          }
          $score = $scores[$fom];
        }else{
          $col = str_replace('"', '', $col);
          if(strstr($col, 'rate') || $col == 'ratio'){
            $fom = 'percentage';
          }else{
            $fom = 'number';
          }
          $score = $scores[$fom];
        }
  
        
        if(!isset($dict[$cidx])){
          $dict[$cidx] = array('format'=>'number', 'score'=>0);
        }
        if($dict[$cidx]['score'] < $score){
          $dict[$cidx]['format'] = $fom;
          $dict[$cidx]['score']  = $score;
        }
      }
    }
    return $dict;
  }


}


?>
