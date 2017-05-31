<?php
require_once(dirname(__FILE__).'/utils/utils.php');
require_once(dirname(__FILE__).'/ess/essManipulation.php');
require_once(dirname(__FILE__).'/ess/ess.php');

class FilterManager{
  function __construct(){
    $this->utils = new Utils();
    $this->lang  = $this->utils->get_browser_language();
  }
  public function get($argobj){
    $argobj['lang'] = $this->lang;
    $jsonobj = (new GetFilter())->run($argobj);
    $jsonobj['wordsdict'] = $this->utils->wordsdict()['toui-filters']; # add "words dictionaty" to return object.
    $this->utils->output_json_result($jsonobj, $argobj);
  }
  public function save($argobj){
    $argobj['lang'] = $this->lang;
    $jsonobj = (new SaveFilter())->run($argobj);
    $this->utils->output_json_result($jsonobj, $argobj);
  }

}

class GetFilter{
  function __construct(){
    $this->db    = new MysqlQueryCondition();
    $this->ess   = new Ess();
    $this->utils = new Utils();
  }
  public function run($argobj){
    $mantype = $argobj['gettype'];
    $rtndata = array();
    if($mantype == 'main'){
      $rtndata =  $this->get_main($argobj);
    }else if($mantype == 'detail'){
      if(@$argobj['esspara'] == 'fileup'){
        $rtndata = $this->return_userfile_list($argobj);
      }else{
        $rtndata = $this->get_detail($argobj);
      }
    }else if($mantype == 'options'){
      $rtndata = $this->get_options($argobj);
    }else if($mantype == 'infoview'){
      $rtndata = $this->db->get_userparams($argobj, 'arr');
    }else if($mantype == 'userconfigall'){
      $rtndata = $this->db->get_userparams_all($argobj);
    }else if($mantype == 'diff'){ # diff of mysql and stored vars
      $rtndata = $this->get_diff_userparams($argobj);
    }
    return array('data' => $rtndata);
  }

  private function return_userfile_list($argobj){
    $list = $this->ess->return_users_uploadfile_list($argobj);
    $words = $this->utils->wordsdict();
    $title = array(
      'df' => @$words['uploaddeffile']
    );
    
    return array(
      array(
        'title' => (@$argobj['esspara'] == 'fileup') ? @$title['df'] : @$title['tp'],
        'child' => $list
      )
    );

  }

  private function get_main($argobj){
    return $this->db->get_filter_master($argobj);
  }
  private function get_detail($argobj){
    $showpttn = $this->config_show_pattern($argobj);
    $ufilters = $this->db->get_filter_users($argobj, $showpttn);
    $maxsetid = isset($ufilters['maxsetid']) ? $ufilters['maxsetid'] : 0;
    $usselect = @$ufilters['usselect'];
    $dbcolmns = @$ufilters['dbcolmns'];
    $dblabels = @$ufilters['dblabels'];
    $optionparts = $this->get_option_parts_list_for_user_selection($argobj);


    # if there's no options, then assign a initial obj.
    $isNoData = False;
    if(count($usselect) == 0){
      $usselect = @$ufilters['initlobj'];
      $isNoData = True;
    }

    $mainobj = $this->return_filteroptions($argobj, $usselect, $dbcolmns, $dblabels, $showpttn, $optionparts);
    $tmplobj = $this->return_filteroptions_templates($mainobj, $showpttn, $maxsetid, $optionparts);

    return array(
      'thead'       => $tmplobj,
      'tbody'       => ($isNoData) ? array() : $mainobj,
      'templaterow' => $tmplobj,
      'addpattern'  => (@$argobj['type'] == 'all') ? 'none' : (($showpttn === 'main') ? 'popup' : 'row'),
      'parts'       => $optionparts
    );
  }
  private function get_option_parts_list_for_user_selection($argobj){
    $optionpartsdata = $this->db->get_all_filter_options_parts($argobj, 'dict');
    $optionparts = array();
    foreach($optionpartsdata as $idx => $parts){
      $cvalue = @$parts['condValue'];
      $uvallist = array();
      $cvalue_uvals = explode('%USER_INPUT_', $cvalue);
      array_shift($cvalue_uvals);
      $uniquvallist = array();
      foreach($cvalue_uvals as $jidx => $uval){
        $uvals = explode(' ',$uval);
        $uval = preg_replace('/[^A-Z_]/', '', @$uvals[0]);
        if(!in_array($uval, $uniquvallist)){
          array_push($uvallist, array(
            'valname' => "%USER_INPUT_$uval",
            'format'  => $uval,
            'label'   => ($uval == 'DATE') ? 'Date (YYYY-mm-dd)' : 'Value X (Integer)',
            'value'   => '',
            'iserror' => false
          ));
          array_push($uniquvallist, $uval);
        }
      }
      $parts['userinputs'] =  $uvallist;
      $parts['userinputok'] = (count($uvallist)> 0) ? false : true;
      array_push($optionparts, $parts);
    }
    return $optionparts;
  }
  private function get_options($argobj){
    $rtndata = $this->db->get_all_filter_options_parts($argobj, 'arr');
    $labelsdict  = $rtndata['dblabels'];
    $thead=array();
    #$essparams =array('usrseg','cvcond','pathtpdef');
#    $essparams =array('usrseg','cvcond');
    foreach($rtndata['thead'] as $idx => $label){
      array_push($thead, array(
        'type'      => $label,
        'label'     => isset($labelsdict[$label]) ? $labelsdict[$label] : $label,
        'isShow'    => !in_array($label, array('custnoLogin','custnoView','essParaName')),
        'isEdit'    => !in_array($label, array('custnoLogin','custnoView','id', 'essParaName')),
        'isEmptyOk' => $label == 'memo',
        'editType'  => (in_array($label, array('condValue','memo'))) ? 'textarea' : 'text',
        #'editType'  => (in_array($label, array('condValue','memo'))) ? 'textarea' : (($label=='essParaName') ? 'hidden' : 'text'),
#        'options'   => ($label=='essParaName') ? $essparams : array()
      ));
    }

    $suggest = array(); $tbody = array(); $essparam = '';
    foreach($rtndata['tbody'] as $idx => $row){
      $newrow = array();
      foreach($row as $ridx => $col){
        $type  = @$thead[$ridx]['type'];
        $label = @$thead[$ridx]['label'];
        if($type == 'essParaName') $essparam = $col;
        if($essparam!='' && !isset($suggest[$essparam])) $suggest[$essparam] = array();
        if($type == 'condValue' && !in_array($col, $suggest[$essparam])) array_push($suggest[$essparam], $col);
        array_push($newrow, array(
          'type'      => $type,
          'label'     => $label,
          'value'     => $col,
          'isShow'    => !in_array($type, array('custnoLogin','custnoView', 'essParaName')),
          'isEdit'    => !in_array($type, array('custnoLogin','custnoView','id', 'essParaName')),
          'isEmptyOk' => $type == 'memo',
          'editType'  => (in_array($type, array('condValue','memo'))) ? 'textarea' : 'text'
        ));
      }
      array_push($tbody, array(
        'essparam' => $essparam,
        'colms'    => $newrow
      ));
    }
    
    return array(
      'thead' => $thead,
      'tbody' => $tbody,
      'templaterow' => $thead,
      'suggest' => $suggest,
    );
  }
  private function config_show_pattern($argobj){
    $essp  = @$argobj['esspara'];
    $setid = isset($argobj['setid']) ? $argobj['setid'] : -1;
    $showpattern = (($essp == 'pathtpdef' || $essp == 'usrseg') && $setid>0)
               ? 'popup'
               : (($essp == 'pathtpdef' || $essp == 'usrseg') ? 'main' : '');
    return $showpattern;
  }

  private function return_filteroptions($argobj, $usselect, $dbcolmns, $dblabels, $showpttn, $optionparts){
    $suggest = $this->db->return_filteroptions_suggestlist($argobj);
    $tbody = array();
    $colname_active = ($showpttn === 'main') ? 'setactiveflg' : 'condactiveflg';
    foreach($usselect as $ridx => $row){
      $newrow = array();
      $isRowShow = @$row['rowIsShow'];
      $cidx = 0;
      $isActive = false;
      $setName = @$row['setname'];
      $setId   = @$row['setid'];
      foreach($dbcolmns as $cidx => $colmn){
        if(array_key_exists($colmn, $row)){
          $isActive = ($colmn == $colname_active) ? $row[$colmn] : $isActive;
          array_push($newrow, $this->return_filteroptions_condition_obj($argobj, $colmn, $row[$colmn], $showpttn, $ridx, $cidx, $suggest, $dblabels, $isRowShow, $optionparts));
        }
      }
      array_push($tbody, array(
                         'setname'  => $setName,
                         'setid'    => $setId,
                         'memo'     => @$row['memo'],
                         'isActive' => $isActive,                  
                         'colms'    => $newrow));
    }
    return $tbody;
  }
  private function return_filteroptions_condition_obj($argobj, $nam, $val, $showpttn, $ridx, $cidx, $suggest, $dblabels, $isRowShow, $optionparts){
    $pmode = @$argobj['popupmode'];
    $essp  = @$argobj['esspara'];
    $type  = @$argobj['type'];
    $val = ($nam=='condselection') ? $this->utils->return_jsonobj_from_string($val) : $val;
    if($nam=='condselection') $userselectpartslist = $this->built_userselectpartslist($val, $optionparts);
    $SHOWCOLUMNS     = array();
    $EDITABLECOLUMNS = array();
    $ADDCOLUMNS      = array();
    $select          = array();
    if($showpttn == 'popup'){
      if($pmode == 'mod'){
        $SHOWCOLUMNS     = array('condname', 'condselection', 'condvalue', 'memo');
        $EDITABLECOLUMNS = array();
        $ADDCOLUMNS      = ($essp == 'usrseg') ?
                           array('condactiveflg','condname', 'condselection', 'condvalue', 'memo') :
                           array('condname', 'condselection', 'condvalue', 'memo');
      }
      if($pmode == 'new'){
        $SHOWCOLUMNS     = ($essp == 'usrseg') ?
                           array('condname', 'condselection', 'condvalue', 'memo') :
                           array('condname', 'condselection', 'condvalue', 'memo');
        $EDITABLECOLUMNS = $SHOWCOLUMNS;
        $ADDCOLUMNS      = $SHOWCOLUMNS;
      }
    }else{
      $SHOWCOLUMNS = ($essp == 'usrseg' || $essp == 'pathtpdef') ?
                      array('setname', 'memo') :
                      array('condname', 'memo');
      $MANCOLUMNS = ($essp == 'usrseg' || $essp == 'pathtpdef') ? $SHOWCOLUMNS : array('condname', 'condselection', 'condvalue', 'memo');
      $EDITABLECOLUMNS = $MANCOLUMNS;
      $ADDCOLUMNS      = $MANCOLUMNS;
      if($essp=='intdef'){
        $SHOWCOLUMNS     = array('condname', 'condselection', 'condvalue', 'memo');
        $EDITABLECOLUMNS = array('condselection', 'condvalue', 'memo');
        $ADDCOLUMNS      = array('condselection', 'condvalue', 'memo');
      }
      if($type == 'all'){
        $EDITABLECOLUMNS = array('condselection', 'condvalue', 'memo');
        $ADDCOLUMNS      = array('condselection', 'condvalue', 'memo');
      }
    }
    
    #if($nam !== 'condvalue'){
    #  $suggest = array();
    #}
    $EDITFORMAT = array(
                    'setid'         => 'none',
                    'condid'        => 'none',
                    'setname'       => 'popup',
                    'setactiveflg'  => 'radio',
                    'condactiveflg' => ($showpttn == 'popup'  && $essp == 'pathtpdef') ? 'checkbox' : $type,
                    'condname'      => 'text',
                    'condselection' => 'textarea',
                    'condvalue'     => 'textarea',
                    'memo'          => 'textarea',
                  );
    
    $isShow   = in_array($nam, $SHOWCOLUMNS);
    $isEdit   = in_array($nam, $EDITABLECOLUMNS);
    $isAdd    = in_array($nam, $ADDCOLUMNS);
    $editType = @$EDITFORMAT[$nam];
    
    $tobj = array(
      'isRowShow' => $isRowShow,
      'ridx'      => $ridx,
      'cidx'      => $cidx,
      'type'      => $nam,
      'class'     => $nam,
      'value'     => $val,
      'label'     => @$dblabels[$nam],
      'isShow'    => $isShow,
      'isEdit'    => $isEdit,
      'isAdd'     => $isAdd,
      'editType'  => $editType,
      'whileedit' => False,
      'editvalue' => $val,
      'suggest'   => $suggest,
      #'select'    => $select
    );

    if(isset($userselectpartslist)) $tobj['userselectpartslist'] = $userselectpartslist;    

    return $tobj;
  }
  private function built_userselectpartslist($userselect, $optionparts){
    if(gettype($userselect) == 'array'){
      $userselectpartslist = array();
      foreach($optionparts as $idx => $parts){
        $newparts = $parts;
        foreach($userselect as $iidx => $uselect){
          if(@$parts['id'] == @$uselect['id']) { # If user has a choice for this id,
            foreach(array('userinputs','isSelected') as $oidx => $overwriteKey){
              $newparts[$overwriteKey] = @$uselect[$overwriteKey]; # overwrite these attribute with users selection.
            }
          }
        }
        array_push($userselectpartslist, $newparts);
      }
      # Since this user has selection for this parts, overwrite $optionparts with users' selection $userselect.
      return $userselectpartslist;
    }else{
      # This user does not have users' selection. Put $optionparts.
      return $optionparts;
    }
  }
  private function return_filteroptions_templates($tbody, $showpattern, $maxsetid, $optionparts){
    $templaterow = array();
    $firstrow = $tbody[0]['colms'];
    foreach($firstrow as $idx => $col){
      if(in_array($col['editType'], array('text', 'popup'))){
        if($showpattern == 'popup' && in_array($col['type'], array('setname'))){
        }else{
          $col['value']     = null;
          $col['editvalue'] = null;
        }
      }
      if(in_array($col['type'], array('id'))){
        $col['value']     = 'new';
        $col['editvalue'] = 'new';
      }else if(in_array($col['type'], array('setactiveflg'))){
        $col['value']     = ($showpattern=='main') ? 0 : -1;
        $col['editvalue'] = ($showpattern=='main') ? 0 : -1;
      }else if(in_array($col['type'], array('condactiveflg'))){
        $col['value']     = 0;
        $col['editvalue'] = 0;
      }else if(in_array($col['type'], array('memo','condname','setname','condvalue'))){
        $col['value']     = '';
        $col['editvalue'] = '';
      }else if(in_array($col['type'], array('condselection'))){
        $col['userselectpartslist'] = $optionparts;
      }
      if($showpattern == 'main'){
        if(in_array($col['type'], array('setid'))){
          $col['value']     = $maxsetid + 1;
          $col['editvalue'] = $maxsetid + 1;
        }
      }
      array_push($templaterow, $col);
    }
    return $templaterow;
  }
  private function get_diff_userparams($argobj){
    $ess = new Ess();
    $uid = $argobj['uid'];
    $lid = $argobj['lid'];
    $uparams = $this->db->get_userparams_all($argobj);
    $isanydiff = False;
    $uobj = array();
    $keytovarname = array(
      'cvcond'    => 'imported-cv',
      'pathtpdef' => 'imported-tpdef',
      'usrseg'    => 'imported-useg'
    );
    foreach($uparams as $key => $upobj){
      $mysqlval = '';
      $actobj = $upobj['curractive'];
      if(isset($actobj['condValue'])){
        $mysqlval = " ".$actobj['condValue'];
      }
      if(isset($actobj['list'])){
        $ulist = $actobj['list'];
        foreach($ulist as $idx => $lobj){
          if(@$lobj['setActiveFlg']==1 && @$lobj['condActiveFlg']!=0) $mysqlval .= " ".@$lobj['condValue'];
        }
      }
      $cachedvarval = $ess->get_cached_vars($uid, $lid, @$keytovarname[$key], '', '');
      $isdiff = $cachedvarval != $mysqlval;
      $uobj[$key] = array(
        'mysqlval'  => $mysqlval,
        'cachedval' => $cachedvarval,
        'isdiff'    => $isdiff
      );
      $isanydiff = ($isdiff) ? True : $isanydiff;
    }

    return array(
      'isExistDiff' => $isanydiff,
      'detail'      => $uobj
    );
  }

}

class SaveFilter{
  function __construct(){
    $this->db    = new MysqlSaveCondition();
    $this->utils = new Utils();
  }
  public function run($argobj){
    $argobj = $this->cleanse_params($argobj);

    foreach(@$argobj['filterSelection'] as $idx => $obj){
      $operation =  @$obj['operation'];
      if($operation == 'insert'){
        $this->db->add_filter($argobj, $obj);
      }
      if($operation == 'update'){
        $this->db->change_filter($argobj, $obj);
      }
      if($operation == 'delete'){
        $this->db->delete_filter($argobj, $obj);
      }
    }
    return array('reloadFlg' => True);
  }
  private function cleanse_params($argobj){
    $argobj['filterSelection'] = $this->utils->return_jsonobj_from_string(@$argobj['filterSelection']); # convert string to json
    return $argobj;
  }
  
}


?>
