<?php
require_once(dirname(__FILE__).'/../utils/utils.php');
require_once(dirname(__FILE__).'/../utils/mysqldb.php');
require_once(dirname(__FILE__).'/ess.php');

class EssParams{
  private $putils, $tutils, $top, $cutils;
  public $DISTTYPES = array('days', 'imp', 'depth'); 
  function __construct(){
    $this->putils = new EssParamsUtil();
    $this->tutils = new EssParamsUtilTable();
    $this->cutils = new EssParamsUtilCVPath();
  }
  public function get_params($argobj){
    # content type
    $contentType = @$argobj['ctype'];
    # parameter cleansing
    $argobj = $this->putils->cleanse_params($argobj);
    $argobj = $this->putils->replace_reserved_variables($argobj, 'usrseg');
    $argobj = $this->putils->replace_reserved_variables($argobj, 'cvcond');
    # build params for shell, based on the "content type"
    $shellparam = [];
    if(in_array($contentType, array('attrScoreFMD', 'attrScore'))){
      $shellparam = $this->get_params_attr($argobj);
    }else if($contentType == 'detailTableRegionMap'){
      $shellparam = $this->get_params_region($argobj);
    }else if($contentType == 'beforeAfterTable'){
      $shellparam = $this->get_params_bfraft($argobj);
    }else if(in_array($contentType, array('detailTableEventCount', 'detailTableEntry'))){
      $shellparam = $this->get_params_event($argobj);
    }else if(in_array($contentType, array('logCountTrend', 'logCountTrendMultiUser'))){
      $shellparam = $this->get_params_trends($argobj);
    }else if(in_array($contentType, array('distFreq', 'distFreqPV'))){
      $shellparam = $this->get_params_dist($argobj);
    }else if($contentType == 'pieBrowsers'){
      $shellparam = $this->get_params_pie($argobj);
    }else if($contentType == 'summ'){
      $shellparam = $this->get_params_summary($argobj);
    }else if($contentType == 'dump'){
      $shellparam = $this->get_params_dump($argobj);
    }else if(in_array($contentType,array('path', 'pathmax'))){
      $shellparam = $this->get_params_paths($argobj, $contentType);
    }else if(in_array($contentType,array('pathdist'))){
      $shellparam = $this->get_params_paths_dist($argobj, $contentType);
    }else if($contentType == 'essloadedsumm'){
      $shellparam = $this->get_params_essloaded($argobj);
    }else if($contentType == 'funcspecific'){
      $shellparam = $this->get_params_funcspecified_noarg($argobj);
    }else if($contentType == 'setup'){
      $shellparam = $this->get_params_setup($argobj);
    }else if($contentType == 'readDataset'){
      $shellparam = $this->get_params_readdataset($argobj);
    }else if($contentType == 'setProfile'){
      $shellparam = $this->get_params_setprofile($argobj);
    }else if($contentType == 'setCalPath'){
      $shellparam = $this->get_params_setcalpath($argobj);
    }
    return $shellparam;
  }
  private function get_params_attr($argobj){
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => 'getAttrScore',
      'vars' => array(
        @$argobj['attrmodel'], # attrmodel
        $this->tutils->build_params_colms(@$argobj['colms']), # colms
        $this->tutils->build_params_intr(@$argobj['intrt']), # intrt
        $this->putils->build_params_paths(@$argobj['paths'], @$argobj['pathord']), # paths
        @$argobj['topx'] # topx
      )
    );
    return array($shellparam);
  }
  private function get_params_region($argobj){
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => 'getRegionMap',
      'vars' => array(
        $this->putils->build_params_paths(@$argobj['paths'], @$argobj['pathord']), # paths
        @$argobj['topx'] # topx
      )
    );
    return array($shellparam);
  }
  private function get_params_bfraft($argobj){
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => 'getBeforeAfter',
      'vars' => array(
        $this->tutils->build_params_colms(@$argobj['colms']), # colms
        @$argobj['logtype'], # logtype
        $this->putils->build_params_paths(@$argobj['paths'], @$argobj['pathord']), # paths
        @$argobj['topx'], # topx
        isset($argobj['redoflg']) ? $argobj['redoflg'] : 0, # redoflg
      )
    );
    return array($shellparam);
  }
  private function get_params_event($argobj){
    $contentType = @$argobj['ctype'];
    $csvFlg      = @$argobj['csvflg'];
    $logtype     = @$argobj['logtype'];

    $colspec='';$filter='';
    if(isset($argobj['colmswtfilter'])){
      $colms   = $this->tutils->build_params_colmswtfilter(@$argobj['colmswtfilter']);
      $colspec = @$colms['colspec'];
      $filter  = @$colms['filter'];
      $logtype = @$colms['logtype'];
    }else if(isset($argobj['colms'])){
      $colspec = $this->tutils->build_params_colms(@$argobj['colms']);
    }
    $colname = $this->tutils->return_colname_from_colspec($colspec);

    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => 'getEvent',
      'vars' => array(
        @$colname, # colname
        @$colspec, # colspec
        @$argobj['edate'], # dateend
        @$argobj['sdate'], # datestart
        @$filter, # filter
        @$logtype, # logtype
        $this->putils->build_params_paths(@$argobj['paths'], @$argobj['pathord']), # paths
        @$argobj['topx'], # topx
        isset($argobj['redoflg']) ? $argobj['redoflg'] : 0, # redoflg
      )
    );
    return array($shellparam);
  
  }
  private function get_params_trends($argobj){
    $shellparams = array();
    foreach($argobj['usersegmentkeys'] as $idx => $usersegmentkey){
      array_push($shellparams, array(
        'uid'  => @$argobj['uid'],
        'lid'  => @$argobj['lid'],
        'func' => 'getTrend',
        'vars' => array(
          @$argobj['bintype'], # bintype
          @$argobj['edate'], # dateend
          @$argobj['sdate'], # datestart
          @$argobj['showPreviousPeriod'], # isshowprev
          $this->putils->build_params_paths(@$argobj['paths'], @$argobj['pathord']), # paths
          $argobj['usersegment'][$usersegmentkey], # usersegment
          isset($argobj['redoflg']) ? $argobj['redoflg'] : 0, # redoflg
        )
      ));
    }
    return $shellparams;
  }
  private function get_params_dist($argobj){
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => 'getDist',
      'vars' => array(
        $argobj['xAxisType'], # disttype
        $this->putils->build_params_paths(@$argobj['paths'], @$argobj['pathord']), # paths
      )
    );
    return array($shellparam);
  }
  private function get_params_pie($argobj){
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => 'getSegBw',
      'vars' => array(
        $this->putils->build_params_paths(@$argobj['paths'], @$argobj['pathord']), # paths
      )
    );
    return array($shellparam);
  }
  private function get_params_summary($argobj){
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => 'getGlobalSummary',
      'vars' => array(
        $this->putils->build_params_paths(@$argobj['paths'], @$argobj['pathord']), # paths
      )
    );
    return array($shellparam);
  }
  private function get_params_dump($argobj){
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => 'getSegDump',
      'vars' => array(
        $this->putils->build_params_paths(@$argobj['paths'], @$argobj['pathord']), # paths
        ($argobj['csvflg']) ? @$argobj['topx'] : 1, # topx
        @$argobj['cvusrflg']    # cv user flag (1:cvuser 0:non-cvuser -1:all)
      )
    );
    return array($shellparam);
  }
  private function get_params_paths($argobj, $contentType){
    $func = ($contentType=='path') ? 'getPath' : 'getPathMaxPage';
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => $func,
      'vars' => array(
        @$argobj['dist'], # dist
        $this->cutils->convert_paging_obj_to_shell_para($argobj['paging']), # paging
        $this->cutils->return_filtering_bykeywords(@$argobj['tpdepth'], @$argobj['pathord'], @$argobj['fldepth'], @$argobj['filter']), # skeyfilt
        @$argobj['tpdepth'], # tpdepth
        $this->putils->build_params_paths(@$argobj['paths'], @$argobj['pathord'])
      )
    );
    return ($contentType=='path') ? array($shellparam) : $shellparam;
  }
  private function get_params_paths_dist($argobj, $contentType){
    $shellparams = array();
    foreach($this->DISTTYPES as $idx => $dtype){
      array_push($shellparams, array(
        'uid'  => @$argobj['uid'],
        'lid'  => @$argobj['lid'],
        'func' => 'getPath',
        'vars' => array(
          @$dtype, # dist
          $this->cutils->convert_paging_obj_to_shell_para($argobj['paging']), # paging
          $this->cutils->return_filtering_bykeywords(@$argobj['tpdepth'], @$argobj['pathord'], @$argobj['fldepth'], @$argobj['filter']), # skeyfilt
          @$argobj['tpdepth'], # tpdepth
          $this->putils->build_params_paths(@$argobj['paths'], @$argobj['pathord'])
        )
      ));
    }
    return $shellparams;
  }
  private function get_params_essloaded($argobj){
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => 'getEssLoadedAmount',
      'vars' => array(
        //'paths'    => $this->putils->build_params_paths(@$argobj['paths'], @$argobj['pathord']),
      )
    );
    return array($shellparam);
  }
  private function get_params_setup($argobj){
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => 'setup',
      'vars' => array(
        @$argobj['instnum'], # instnum (When "master" mode, put 1)
        @$argobj['insttype'] # insttype (When "master" mode, put 'local')
      )
    );
    return $shellparam;
  }
  private function get_params_readdataset($argobj){
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => @$argobj['ctype'],
      'vars' => array(
        #@$argobj['edate'], # dateend
        #@$argobj['sdate'], # datestart
        #@$argobj['bdays'], # dateprev
        @$argobj['cachedir'], # cached dir path
        @$argobj['pathord'], # pathord
        @$argobj['cvcond'], # query_cvcond
        @$argobj['intdef'], # query_enttype
        @$argobj['filter'], # query_filter
        @$argobj['pathfilter'], # query_pathfilt
        @$argobj['touchpointgrouping'], # query_pathgrp
        @$argobj['pathtpdef'], # query_pathtp
        @$argobj['usrseg'], # query_usrseg
        isset($input['paras']['redoflg']) ? $input['paras']['redoflg'] : 0, # redoflg
        #@$argobj['sampling'], # sampling
        @$argobj['tillcv'], # tillcv
        #@$argobj['cpmemo'],  # memo, the last attribute for cache directory (could be empty)
      )
    );
    return $shellparam;
  }
  private function get_params_setprofile($argobj){
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => @$argobj['ctype'],
      'vars' => array(
        @$argobj['pathord'], # pathord
        @$argobj['cvcond'], # query_cvcond
        @$argobj['intdef'], # query_enttype
        @$argobj['filter'], # query_filter
        @$argobj['pathfilter'], # query_pathfilt
        @$argobj['touchpointgrouping'], # query_pathgrp
        @$argobj['pathtpdef'], # query_pathtp
        @$argobj['usrseg'], # query_usrseg
        @$argobj['tillcv'], # tillcv
        0 # forceflg
      )
    );
    return $shellparam;
  }
  private function get_params_setcalpath($argobj){
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => @$argobj['ctype'],
      'vars' => array(
        @$argobj['pathtpdef'], # query_defpath
        @$argobj['touchpointgrouping'], # query_groupingpath
        @$argobj['pathfilter'], # query_pathfilt
        @$argobj['usrseg'], # query_usrseg
        @$argobj['pathord'], # pathord
        @$argobj['tocv'], # tocv
        0 # forceflg
      )
    );
    return $shellparam;
  }
  private function get_params_funcspecified_noarg($argobj){
    $shellparam = array(
      'uid'  => @$argobj['uid'],
      'lid'  => @$argobj['lid'],
      'func' => @$argobj['func'],
      'vars' => array(
        //'paths'    => $this->putils->build_params_paths(@$argobj['paths'], @$argobj['pathord']),
      )
    );
    return array($shellparam);
  }
} 

Class EssParamsUtil{
  private $utils,$ess;
  function __construct(){
    $this->utils = new Utils();
    $this->ess   = new Ess();
    $this->db    = new MysqlQueryCondition();
  }
  public function cleanse_params($argobj){
    # colms
    $colms = $this->utils->return_jsonobj_from_string(@$argobj['colms']);
    $argobj['colms'] = isset($colms['arr']) ? $colms['arr'] : array();
    # colms with filter
    $colmswtfilter = $this->utils->return_jsonobj_from_string(@$argobj['colmswtfilter']);
    $argobj['colmswtfilter'] = $colmswtfilter;
    # path
    $paths = $this->utils->return_jsonobj_from_string(@$argobj['paths']);
    $argobj['paths'] = isset($paths['arr']) ? $paths['arr'] : array();
    # intr
    $intrt = $this->utils->return_jsonobj_from_string(@$argobj['intrt']);
    $argobj['intrt'] = isset($intrt['arr']) ? $intrt['arr'] : array();
    # paging
    $argobj['paging'] = isset($argobj['paging']) ? $this->utils->return_jsonobj_from_string(@$argobj['paging']) : array();
    # tillcv
    $argobj['tillcv'] = isset($argobj['tillcv']) ? $argobj['tillcv'] : $this->ess->get_cached_vars(@$argobj['uid'], @$argobj['lid'], 'udbd-path-tillcv', 1, '');
    # tocv
    $argobj['tocv'] = @$argobj['tocv'] ? 1 : 0;
    # path order
    $argobj['pathord'] = isset($argobj['pathord']) ? $argobj['pathord'] : $this->ess->get_cached_vars(@$argobj['uid'], @$argobj['lid'], 'udbd-path-order', 0, '');
    # csv flg
    $argobj['csvflg'] = isset($argobj['csvflg']) ? $argobj['csvflg'] : False;
    # top X
    $argobj['topx']  = ($argobj['csvflg']) ? 10000 : 21;
    # lang info
    $argobj['lang']  = $this->utils->get_browser_language();
    # ------
    # for trend chart, added usersegment info
    if(in_array($argobj['ctype'], array('logCountTrend', 'logCountTrendMultiUser'))){
      if($argobj['ctype'] == 'logCountTrend'){
        $argobj['usersegment'] = array('trend'=>'SELECTEDBYGLOBAL');
      }else if($argobj['ctype'] == 'logCountTrendMultiUser'){
        $argobj['usersegment'] = $this->db->get_usersegment_group(@$argobj['uid'], @$argobj['lid']);
        $argobj = $this->replace_reserved_variable_for_trends_comparison($argobj, 'usersegment');
      }
      $usersegmentkeys = array_keys($argobj['usersegment']);
      sort($usersegmentkeys);
      $argobj['usersegmentkeys'] = $usersegmentkeys;
    }
    # ------
    return $argobj;
  }
  public function replace_reserved_variables($argobj, $varname){
    if(@$argobj[$varname]==''){
      return $argobj;
    }else{
      $uid = @$argobj['uid']; $lid=@$argobj['lid'];
      $sdate=@$argobj['sdate']; $edate=@$argobj['edate']; $bdays=@$argobj['bdays'];
      if($sdate == '') $sdate = $this->ess->get_cached_vars($uid, $lid, 'udbd-basic-periof-from', '', '');
      if($edate == '') $edate = $this->ess->get_cached_vars($uid, $lid, 'udbd-basic-periof-to',   '', '');
      if($bdays == '') $bdays = $this->ess->get_cached_vars($uid, $lid, 'udbd-basic-prevdays',    '', '');
      if($sdate != '') $argobj[$varname] = str_replace('%START_DATE', $sdate, $argobj[$varname]);
      if($edate != '') $argobj[$varname] = str_replace('%END_DATE',   $edate, $argobj[$varname]);
      if($bdays != '') $argobj[$varname] = str_replace('%PREV',       $bdays, $argobj[$varname]);
      return $argobj;
    }
  }
  private function replace_reserved_variable_for_trends_comparison($argobj, $varname){
     $uid = @$argobj['uid']; $lid=@$argobj['lid'];
     $sdate = $this->ess->get_cached_vars($uid, $lid, 'udbd-basic-periof-from', '', '');
     $edate = $this->ess->get_cached_vars($uid, $lid, 'udbd-basic-periof-to',   '', '');
     $bdays = $this->ess->get_cached_vars($uid, $lid, 'udbd-basic-prevdays',    '', '');
     foreach($argobj[$varname] as $cname => $cvalue){
       $cvalue = $argobj[$varname][$cname];
       if($sdate != '') $cvalue = str_replace('%START_DATE', $sdate, $cvalue);
       if($edate != '') $cvalue = str_replace('%END_DATE',   $edate, $cvalue);
       if($bdays != '') $cvalue = str_replace('%PREV',       $bdays, $cvalue);
       $argobj[$varname][$cname] = $cvalue;
     }
     return $argobj;
  }
  public function build_params_paths($paths, $pathord){
    $path_arr     = $paths;
    $path_arr_new = array();
    $pidx_start   = ($pathord == 0) ? 0 : 20 - count($path_arr);
    foreach($path_arr as $idx => $eachpath){
      $pidx = $pidx_start + $idx + 1;
      $newarg = 'p'.$pidx.'=="'.str_replace('"','',$eachpath).'"';
      array_push($path_arr_new, $newarg);
    }
    $path_str = join(' && ', $path_arr_new);
    if(count($path_arr_new) > 0){
      $path_str = " -pp,n path -filt,yn '".$path_str."' -endpp ";
    }
    return $path_str;
  }
}


Class EssParamsUtilTable{
  public function build_params_intr($intrarr){
    $filter_intr_arr = array();
    foreach($intrarr as $idx => $intr){
      if($intr != ''){
        array_push($filter_intr_arr, "ent==".$intr);
      }
    }
    $filter_intr = (count($filter_intr_arr) > 0) ? " -filt '".implode(' || ', $filter_intr_arr)."'" : "";
    return $filter_intr;
  }
  public function build_params_colms($colmsarr){
    # columns
    $paracol='';
    foreach($colmsarr as $idx => $col){
      $colspec = (strstr($col, ':')) ? '' : 's,k:';
      $paracol = ($col=='') ? $paracol." x" : $paracol.' '.$colspec.$col;
    }
    return $paracol;
  }
  public function buildparams_filter($filterobj){
    $colnm = @$filterobj['label'];
    $colvl = @$filterobj['value'];
    $filter = ($colnm=='' || $colvl=='') ? '' : "'".$colnm."==".$colvl."'";
    $filter = ($filter != '') ? '-filt '.$filter : $filter;
    return $filter;
  }
  public function build_params_colmswtfilter($valueobj){
    $colspec  = $this->build_params_colms(@$valueobj['colms']);
    $filter   = $this->buildparams_filter(@$valueobj['filter']);
    $logtype  = @$valueobj['logtype'];
    return array(
      'colspec' => $colspec,
      'filter'  => $filter,
      'logtype'  => $logtype
    );
  }
  public function return_colname_from_colspec($colspec){
    # ---
    # build colnames (just exclude the x:..)
    $colnamearr = array();
    foreach(explode(' ',$colspec) as $idx => $col){
      $cols = explode(':', $col);
      array_push($colnamearr, @$cols[1]);
    }
    $colname = implode(' ',$colnamearr);
    # ----
    return $colname;
  }
}


Class EssParamsUtilCVPath{
  public function convert_paging_obj_to_shell_para($obj){
    $from = isset($obj['status']['from']) ? $obj['status']['from'] : 1;
    $to   = isset($obj['status']['to'])   ? $obj['status']['to']   : 11;
    return $from.' '.$to;
  }
  public function return_filtering_bykeywords($tpdepth, $pathord, $fldepth, $skeywrd){
    $filtertp = '';
    if($skeywrd != ''){
      $targettp = array(($pathord == 0) ? 'p1' : 'p20');
      if($fldepth == 'any'){
      $cnt = 2;
      while($cnt <= $tpdepth){
        array_push($targettp, ($pathord == 0) ? 'p'.$cnt : 'p'.(20-$cnt+1));
        $cnt++;
      }
      }else if($fldepth == 'last'){
      $targettp = array('p20');
      }
    
      $arg_tp = ' || ';
      $arg_cd = ' || ';
      $skeywrd = str_replace('　',' ',$skeywrd);
      $inputfilters = explode(' ', $skeywrd);
      $filtertparr = array();
      foreach($targettp as $idx => $tp){
        $filtereachtp = array();
        foreach($inputfilters as $fidx => $ft){
          array_push($filtereachtp, 'PatCmp('.$tp.', \"*'.$ft.'*\", \"ncas\")');
        }
        array_push($filtertparr, '('.implode($arg_cd, $filtereachtp).')');
      }
      $filtertp = implode($arg_tp, $filtertparr);
    }
    return $filtertp;
  }

}


?>
