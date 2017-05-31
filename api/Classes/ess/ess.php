<?php

class Ess{
  private $SHELL_SCRIPT,$CACHEFILENAMES,$FPATH_USRDIR,$FPATH_UCOST,$STATUS_STRING,$COOKIE_DELIM,$SESSIOIN_MIN;
  ##  Constructor
  function __construct(){
    $ESSROOT = dirname(__FILE__).'/shells/';
    $this->SHELL_SCRIPT   = $ESSROOT.'run.sh';
    $this->FPATH_USRDIR   = '/user/';
    $this->FPATH_UCOST    = $this->FPATH_USRDIR.'cost.csv';
    $this->CACHEFILENAMES = array(
      'status-instance'                => '/.gui/STATUSINSTANCE',
      'status-import'                  => '/.gui/STATUSIMPORT',
      'timestamp-instance-start'       => '/.gui/STARTTIMEINSTANCE',
      'timestamp-import-start'         => '/.gui/STARTTIMEIMPORT',
      'userstatus-cookies-localmaster' => '/.local/userascookie',
      'userstatus-cookies-workingdir'  => '/.gui/USERASCOOKIE',
      'userstatus-timestamp'           => '/.gui/TIMESTAMP',
      'udbd-basic-cached'              => '/.gui/CACHEDIRNAME',
      'udbd-basic-periof-from'         => '/.gui/IMPPERIODFROM',
      'udbd-basic-periof-to'           => '/.gui/IMPPERIODTO',
      'udbd-basic-prevdays'            => '/.gui/IMPBACKDAYS',
      'udbd-basic-sampling'            => '/.gui/IMPSAMPLING',
      'udbd-basic-memo'                => '/.gui/IMPMEMO',
      'udbd-path-order'                => '/.gui/IMPCALPATHORDER',
      'udbd-path-tillcv'               => '/.gui/IMPCALPATHTILLCVFLG',
      'imported-tpdef'                 => '/.gui/IMPTOUCHPOINTDEF',
      'imported-useg'                  => '/.gui/IMPUSERSEGMENT',
      'imported-cv'                    => '/.gui/IMPCV'
    );
    $this->STATUS_STRING = array(
      'creating'    => 'creating',
      'terminated'  => 'none',
      'terminating' => 'terminating',
      'alive'       => 'ready',
      'created'     => 'ready',
      ''            => 'ready'
    );
    $this->COOKIE_DELIM = '---';
    $this->ENV_STATUS = array(
      'us' => 'used',
      'nu' => 'notused',
      'me' => 'me'
    );
    $this->SESSIOIN_MIN = 30;
  }
  private function get_ssid(){
    if(!isset($_SESSION)){
      session_start();
    }
    $SSID=session_id();
    #return $SSID;
    return 'FIXEDDUMMY';
  }
  public function get_arrdata_ess($params){
    $ssid = $this->get_ssid();
    $arrdata = array();
ini_set('memory_limit', '-1');
    foreach($params as $idx => $param){
      $uid  = @$param['uid'];
      $lid  = @$param['lid'];
      $func = @$param['func'];
      $vars = $this->format_shell_params(@$param['vars']);
      $this->update_timestamp_ess($ssid, $uid, $lid);
      $shell_command = $this->SHELL_SCRIPT.' '.$ssid.' '.$func.' '.$uid.' '.$lid.' '.$vars;
      $fdata = shell_exec($shell_command);
      array_push($arrdata, $this->format_fromstring_toarray($fdata));
    }

    return $arrdata;
  }
  public function get_vars_ess($uid, $lid, $func, $argsstr){
    $ssid = $this->get_ssid();
    $shell_command = $this->SHELL_SCRIPT.' "'.$ssid.'" "'.$func.'" "'.$uid.'" "'.$lid.'" "'.$argsstr.'"';
#error_log($shell_command, 0);
    $fdata = shell_exec($shell_command);
    $fdata = str_replace("\n",'',$fdata);
    if($func != 'returnUsersWorkspaceDir'){
      $this->update_timestamp_ess($ssid, $uid, $lid);
    }
    return $fdata;
  }
  public function get_vars_ess_fromparams($param){
    $ssid = $this->get_ssid();
    $uid  = @$param['uid'];
    $lid  = @$param['lid'];
    $func = @$param['func'];
    $vars = $this->format_shell_params(@$param['vars']);
    $this->update_timestamp_ess($ssid, $uid, $lid);
    $shell_command = $this->SHELL_SCRIPT.' '.$ssid.' '.$func.' '.$uid.' '.$lid.' '.$vars;
    $fdata = shell_exec($shell_command);
    $fdata = str_replace("\n",'',$fdata);
    $this->update_timestamp_ess($ssid, $uid, $lid);
    return $fdata;
  }
  public function run_ess_detached($param){
    $ssid = $this->get_ssid();
    $uid  = @$param['uid'];
    $lid  = @$param['lid'];
    $func = @$param['func'];
    $vars = $this->format_shell_params(@$param['vars']);
    $this->update_timestamp_ess($ssid, $uid, $lid);
    $shell_command = $this->SHELL_SCRIPT.' '.$ssid.' '.$func.' '.$uid.' '.$lid.' '.$vars;
    system('sh '.$shell_command.' > /dev/null &');
  }
  public function is_available_for_user($uid){
    #return file_exists($this->get_vars_ess($uid, '', 'returnOriginalScriptDir', ''));
    return file_exists($this->get_vars_ess($uid, '', 'returnUsersWorkspaceDir', ''));
  }
  public function get_cached_vars($uid, $lid, $varname, $dfval, $forcessid){
    $ssid = (@$forcessid!='') ? $forcessid : $this->get_ssid();
    $ESSFILEPATH = $this->return_ssid_ess_path($ssid, $uid, $lid);
    if($varname == 'userstatus-cookies-localmaster') $ESSFILEPATH= $this->return_ess_root_dir($ssid, $uid, $lid);
    $ESSFILEPATH = $ESSFILEPATH.$this->CACHEFILENAMES[$varname];
    $varval = $dfval;
    if(file_exists($ESSFILEPATH)){
      $farr = mb_split('\n',file_get_contents($ESSFILEPATH));
      $varval = $farr[0];
    }
    return $varval;
  }
  public function get_cached_timestamp($uid, $lid, $varname, $tform){
    $ssid = $this->get_ssid();
    $ESSFILEPATH = $this->return_ssid_ess_path($ssid, $uid, $lid);
    $ESSFILEPATH = $ESSFILEPATH.$this->CACHEFILENAMES[$varname];
    $stamp = (file_exists($ESSFILEPATH)) ? filemtime($ESSFILEPATH) : time();
    return ($tform == '') ? $stamp : date($tform, $stamp);
  }
  public function return_users_costfilepath($uid, $lid){
    $ssid = $this->get_ssid();
    $ESSFILEPATH = $this->return_ssid_ess_path($ssid, $uid, $lid);
    return $ESSFILEPATH.$this->FPATH_UCOST;
  }
  public function return_users_uploadfile_path($argobj){
    $ssid = $this->get_ssid();
    $ESSFILEPATH = $this->return_ssid_ess_path($ssid, @$argobj['uid'], @$argobj['lid']);
    return $ESSFILEPATH.$this->FPATH_USRDIR.@$argobj['ftype'];
  }
  public function return_users_uploadfile_list($argobj){
    $ssid = $this->get_ssid();
    $ESSFILEPATH = $this->return_ssid_ess_path($ssid, @$argobj['uid'], @$argobj['lid']);
    $dpath = $ESSFILEPATH.$this->FPATH_USRDIR;
    $filelist = array();
    if($dir = opendir($dpath)){
      while(($file = readdir($dir)) !== False){
        if(strstr($file, '.csv')){
          array_push($filelist, array(
            'title' => $file,
            'ftype' => $file
          ));
        }
      }
      closedir($dir);
    }
    return $filelist;
  }
  public function return_isiexist_workdir($uid, $lid){
    $ssid = $this->get_ssid();
    return file_exists($this->return_ssid_ess_path($ssid, $uid, $lid));
  }
  // User Configs From cvpath scripts directory.
  public function return_userconfig($uid, $lid, $vartype){
    # $vartype : "charcode", "timezone", "logtypeforview"
    return $this->get_vars_ess($uid, $lid, 'returnUsersConfig', $vartype);
  }
  // 
  public function return_ssid_ess_path($ssid, $uid, $lid){
    return $this->get_vars_ess($uid, $lid, 'returnUsersWorkspaceDir', '');
  }
  public function return_ess_root_dir($ssid, $uid, $lid){
    #return $this->get_vars_ess($uid, $lid, 'returnUsersWorkspaceDir', '');
    return $this->get_vars_ess($uid, $lid, 'returnESSRootDir', '');
  }
  private function format_shell_params($varsobj){
    $varsarr = array();
    foreach($varsobj as $idx => $tobj){
      $val = str_replace('\"', '"',  @$tobj);
      $val = str_replace('"',  '\"', $val);
      array_push($varsarr, '"'.$val.'"');
    }
    return implode(' ', $varsarr);
  }
  private function update_timestamp_ess($ssid, $uid, $lid){
    $ESS_DIR  = $this->return_ssid_ess_path($ssid, $uid, $lid);
    # overwrite the timestamp of the "TIMESTAMP" file
    if(file_exists($ESS_DIR)){
        $curruser = explode($this->COOKIE_DELIM, $this->get_cached_vars($uid, $lid, 'userstatus-cookies-workingdir', '', ''));
        $currssid = @$curruser[0];
        if($currssid == $ssid){
          $ESS_TIMESTAMP = $ESS_DIR.$this->CACHEFILENAMES['userstatus-timestamp'];
          touch($ESS_TIMESTAMP);
        }
    }
  }
  private function format_fromstring_toarray($str){
    #  when $getColIdx==-1, get all column as arrow
    $getRowFrom=0;$getColIdx=-1;
    $list = array();
    #  read data
    $lines = mb_split('\n', $str);
    # set memory
    ini_set('memory_limit', '-1');
    # for the cell contains comma
    $specialreplace = '&&&&&';
    foreach($lines as $row_num => $row){
      if($row_num >= $getRowFrom){
        $rowwithoutcommainquote = preg_replace_callback('/"(.){1,}?"/', 
          function($m) {
            $specialreplace = '&&&&&';
            return preg_replace('/,/', $specialreplace, $m[0]);
          },
          $row);
        $cells_tmp = split(',', $rowwithoutcommainquote);
        $cells = array();
        if($rowwithoutcommainquote == $row){
          $cells = $cells_tmp;
        }else{
          foreach($cells_tmp as $cidx => $cell){
            array_push($cells, str_replace($specialreplace,',', $cell)); 
          }
        }
        if($getColIdx >= 0){
          if(isset($cells[$getColIdx])){
            if($cells[$getColIdx] != ''){
              array_push($list, $cells[$getColIdx]);
            }
          }
        }else{
          # if "$getColIdx == -1", then push all.
          if(count($cells) ==1 && $cells[0] ==""){
            # no need this data.
          }else{
            array_push($list, $cells);
          }
        }
      }
    }
    return $list;
  }
  public function get_property_statusstring(){
    return $this->STATUS_STRING;
  }
  public function check_env_availability($argobj, $targetdir){
    $isUsedFlg   = $this->ENV_STATUS['nu'];
    $currentuser = $this->get_current_user($argobj, $targetdir);
    if(@$currentuser['ssid']!='' && @$currentuser['uid']!='' && @$currentuser['lid']!=''){
      $argobj['ssid'] = $this->get_ssid();
      $isUsedFlg = $this->ENV_STATUS['me'];
      foreach($currentuser as $vnm => $vvl){
        $isUsedFlg = ($vvl != @$argobj[$vnm]) ? $this->ENV_STATUS['us'] : $isUsedFlg;
      }
      if($isUsedFlg == $this->ENV_STATUS['us']){
        # kill them if session is timed out.
        $takeover = $this->kill_others_if_expire($currentuser, $argobj);
        $isUsedFlg = ($takeover) ? $this->ENV_STATUS['nu'] : $isUsedFlg;
      }
    }
    return $isUsedFlg;
  }
  public function is_youre_curruser($isUsedFlg){
    return in_array($isUsedFlg, array($this->ENV_STATUS['nu'], $this->ENV_STATUS['me']));
  }
  public function get_current_user($argobj, $targetdir){
    $targetcache = ($targetdir == 'local') ? 'userstatus-cookies-localmaster' : 'userstatus-cookies-workingdir';
    $cacheval    = $this->get_cached_vars(@$argobj['uid'], @$argobj['lid'], $targetcache, '', '');
    $currentuser = array();
    if($cacheval != ''){
      $currentuser = $this->get_userids_for_cache($cacheval);
    }
    return $currentuser;
  }
  private function build_userid_for_cache($argobj){
    $ssid = $this->get_ssid();
    $ids  = array($ssid, @$argobj['uid'], @$argobj['lid']);
    return implode($this->COOKIE_DELIM, $ids);
  }
  private function get_userids_for_cache($cacedvar){
    $ids = explode($this->COOKIE_DELIM, $cacedvar);
    return array(
      'ssid' => @$ids[0],
      'uid'  => @$ids[1],
      'lid'  => @$ids[2]
    );
  }
  private function kill_others_if_expire($otherobj, $meobj){
    $tstamp = $this->get_cached_timestamp(@$otherobj['uid'], @$otherobj['lid'], 'userstatus-timestamp', 'U');
    $nstamp = date("U", time());
    $diff   = ($nstamp - $tstamp) / (60);
    $takeover = ($diff > $this->SESSIOIN_MIN);

    if($takeover){
      $this->cleanup_cookies($otherobj, $meobj);
    }
    return $takeover;
  }
  private function cleanup_cookies($otherobj, $meobj){
    $shell_command = $this->SHELL_SCRIPT.' '.@$otherobj['ssid'].' terminateMyselfByOtherUser '.@$otherobj['uid'].' '.@$otherobj['lid'].' '.@$meobj['ssid'].' '.@$meobj['uid'].' '.@$meobj['lid'];
    $fdata = shell_exec($shell_command);
  }
  public function terminate_others($otherobj, $meobj){
    $meobj['ssid'] = isset($meobj['ssid']) ? $meobj['ssid'] : $this->get_ssid();
    $shell_command = $this->SHELL_SCRIPT.' '.@$otherobj['ssid'].' terminateOther '.@$otherobj['uid'].' '.@$otherobj['lid'].' '.@$meobj['ssid'].' '.@$meobj['uid'].' '.@$meobj['lid'];
    $fdata = shell_exec($shell_command);
  }
  public function isimporting($argobj, $targetdir, $targetuser){
    $curruser = ($targetuser == 'other') ? $this->get_current_user($argobj, $targetdir) : $argobj;
    $stts     = $this->get_cached_vars(@$curruser['uid'], @$curruser['lid'], 'status-import', 'noneimportstatus', @$curruser['ssid']);
    return $stts;
  }
}


?>
