<?php
require_once(dirname(__FILE__).'/../utils/utils.php');
require_once(dirname(__FILE__).'/essParams.php');
require_once(dirname(__FILE__).'/ess.php');
require_once(dirname(__FILE__).'/../utils/mysqldb.php');

class EssManipulation{
  private $ess,$params,$db;
  function __construct(){
    $this->ess    = new Ess();
    $this->utils  = new Utils();
    $this->params = new EssParams();
    $this->db     = new MysqlQueryCondition();
  }
  public function check_instance_existing($argobj){
    $uid = @$argobj['uid'];
    $lid = @$argobj['lid'];
    # check if the instance complete flag
    $result = '';
    if($this->ess->return_isiexist_workdir($uid, $lid)){
      $sts = $this->ess->get_cached_vars($uid, $lid, 'status-instance', 'terminated', '');
      foreach($this->ess->get_property_statusstring() as $arg => $ret){
        $result = ($sts == $arg) ? $ret : $result;
      }
      if($result == 'ready'){
        # If udbd is not alive, then it should start "setup" again.
        $ifudbdalive = $this->ess->get_vars_ess($uid, $lid, 'checkUdbdAlive', '');
        $result      = ($ifudbdalive == '0') ? 'none' : $result;
      }
    }else{
      # there is not your(this ssid's) ess directory, so create it.
      $result = 'none';
    }
    return $result;
  }

  public function get_timestamp_instance_start($argobj){
    return $this->ess->get_cached_timestamp(@$argobj['uid'], @$argobj['lid'], 'timestamp-instance-start', '');
  }

  public function get_timestamp_since_when($argobj){
    $cvarname = (@$argobj['type'] == 'instance') ? 'status-instance' : 'timestamp-import-start';
    return $this->ess->get_cached_timestamp(@$argobj['uid'], @$argobj['lid'], $cvarname, '');
  }

  public function check_if_env_occupied($argobj, $targetdir){
    return $this->ess->check_env_availability($argobj, $targetdir);
  }

  public function get_memory_stats($argobj){
    $memory = $this->ess->get_vars_ess(@$argobj['uid'], @$argobj['lid'], 'getUsingMemory', '');
     
    $memsts = explode(',', $memory);
    $lbls   = array('total','using','insttype', 'instnum');
    $memobj = array();
    foreach($lbls as $idx => $lbl){
      $memobj[$lbl] = isset($memsts[$idx]) ? $memsts[$idx] : 0;
    }
    $rate = $memobj['total']>0 ?  round(($memobj['using'] / $memobj['total']) * 100, 2) : 0;
    $memobj['ratenum'] = $rate;
    $memobj['rate'] = ((string) $rate).'%';
    $memobj['using'] = ((string)round($memobj['using']/1024/1024, 2)).'G';
    $memobj['total'] = ((string)round($memobj['total']/1024/1024, 2)).'G';
    $memobj['signal'] = ($rate > 70) ? 'ORANGE' : (($rate > 50) ? 'YELLOW' : 'GREEN');

    return $memobj;
  }

  public function check_if_import_running_in_machine($argobj){
    # determine which directory to see.
    $wdisused = $this->check_if_env_occupied($argobj, 'wd');
    $lcisused = $this->check_if_env_occupied($argobj, 'local');
    $targetdir = ($wdisused == 'used') ? 'wd' : ($lcisused=='used' ? 'local' : '');
    # if the instance is setting up, then
    $sts = $this->ess->get_cached_vars(@$argobj['uid'], @$argobj['lid'], 'status-instance', '', '');
    if($sts === 'creating'){
      return true;
    }

    return !in_array($this->ess->isimporting($argobj, $targetdir, 'other'), array('done', 'ready', 'failed'));
  }

  public function check_if_data_is_imported($argobj){
    # determine which directory to see.
    $wdisused = $this->check_if_env_occupied($argobj, 'wd');
    $lcisused = $this->check_if_env_occupied($argobj, 'local');
    $targetdir = ($wdisused == 'used') ? 'wd' : ($lcisused=='used' ? 'local' : '');
    # if the instance is setting up, then
    $sts = $this->ess->get_cached_vars(@$argobj['uid'], @$argobj['lid'], 'status-instance', '', '');
    return $this->ess->isimporting($argobj, $targetdir, 'other') == 'done';
  }

  public function logout($argobj){
    $this->ess->get_vars_ess(@$argobj['uid'], @$argobj['lid'], 'quitMySelf', '');
  }

  public function create_instance($argobj){
    $argobj['ctype'] = 'setup';
    $shell_params = $this->params->get_params($argobj);
    $this->ess->run_ess_detached($shell_params);
    # user's mysql db
    #$isempty = $this->db->checkif_customer_db_empty(@$argobj['uid'], @$argobj['lid']);
    $isempty = $this->db->checkif_tables_exists();
    if($isempty){
      $this->ess->get_vars_ess(@$argobj['uid'], @$argobj['lid'], 'initdb', '');
    }
  }

#  public function get_current_env_project($argobj){
#    $curruser = $this->ess->get_current_user($argobj, @$argobj['targetdir']);
#    return @$curruser['uid'];
#  }

  public function takeover_curr_user($argobj){
    if(@$argobj['targetdir'] != ''){
      $curruser = $this->ess->get_current_user($argobj, $argobj['targetdir']);
      return $this->ess->terminate_others($curruser, $argobj);
    }
  }

  public function terminate_instance($argobj){
    $argobj['ctype'] = 'funcspecific';
    $argobj['func']  = 'terminateInstance';
    $shell_params = $this->params->get_params($argobj);
    $this->ess->run_ess_detached($shell_params);
  }

  public function read_data($argobj){
    $argobj['ctype'] = 'readDataset';
    $argobj = $this->build_userparams_mysql($argobj);
    $argobj = $this->build_custom_params($argobj);
$setparams = array(
  'uid' => $argobj['uid'],
  'lid' => $argobj['lid'],
  'func' => 'setImportedPeriods',
  'vars' => array(@$argobj['sdate'], @$argobj['edate'], @$argobj['bdays'], @$argobj['sampling'], @$argobj['cpmemo'])
);
$this->ess->get_vars_ess_fromparams($setparams); # set sdate,edate,bdays,sampling,cpmemo into cached vars.
    $this->ess->run_ess_detached($this->params->get_params($argobj));
    return array(
      'data' => 'reading'
    );
  }

  public function set_profile($argobj){
    $argobj['ctype'] = 'setProfile';
    $argobj = $this->build_userparams_mysql($argobj);
    $argobj = $this->build_custom_params($argobj);
    $this->ess->run_ess_detached($this->params->get_params($argobj));
    return array(
      'data' => 'runningprofile'
    );
  }

  public function build_custom_params($argobj){
    $custom = (isset($argobj['custom']) && strstr(@$argobj['custom'], 'arr'))? $this->utils->return_jsonobj_from_string($argobj['custom'])['arr'] : array();
    if(is_array($custom)){
    foreach($custom as $idx => $rowobj){
      $pattern = @$rowobj['custompattern'];
      if($pattern == 'usrsegByPlacementID'){
        $esspara = $rowobj['esspara'];
        $values  = $rowobj['value'];
        $column  = $rowobj['column'];
        $table   = $rowobj['udbdtable'];
        $filtconds = array();
        foreach($values as $vidx => $value){
          array_push($filtconds, $column.'=="'.$value.'"');
        }
        $argobj[$esspara] = (count($filtconds) > 0)
          ? $argobj[$esspara]." -pp profile -bvar vI1 0 -eval vI1 first_cv -endpp  -pp,n ".$table." -filt,pr '(".implode(' || ', $filtconds).") && t <= vI1' -endpp "
          : $argobj[$esspara];
      }else if($pattern == 'hogehoge'){
        $esspara = $rowobj['esspara'];
        $values  = $rowobj['value'];
        $column  = $rowobj['column'];
        # 
        $tessparaval = trim(@$argobj[$esspara]);
        $tessparavals = explode('-endpp', $tessparaval);
        $tessparavals_0 = count($tessparavals) > 2 ? implode('-endpp', array_slice($tessparavals, 0, count($tessparavals)-1)) : '';
        $tessparavals_1 = count($tessparavals) > 0 ? $tessparavals[count($tessparavals)-2] : '';
        $tessparavals_1_sp = explode("'", $tessparavals_1);
        $filtconds = array();
        foreach($values as $vidx => $value){
          array_push($filtconds, $column.'=="'.$value.'"');
        }
        $filtconds_str = $tessparavals_1_sp[1].' || '.implode(' || ', $filtconds);
        $argobj[$esspara] = (count($filtconds) > 0) 
          ? $tessparavals_0.$tessparavals_1_sp[0]."'".$filtconds_str."'".$tessparavals_1_sp[2]
          : $argobj[$esspara];
      }
    }
    }
    return $argobj;
  }

  public function set_calpath($argobj){
    $argobj['ctype'] = 'setCalPath';
    $argobj = $this->build_userparams_mysql($argobj);
    $this->ess->run_ess_detached($this->params->get_params($argobj));
    return array(
      'data' => 'runningcalpath'
    );
  }

  private function build_userparams_mysql($argobj){
    $storedparas = $this->db->get_userparams($argobj, 'dict');
    foreach($storedparas as $idx => $row){
      $essp = @$row['essParaName'];
      $cval = @$row['condValue'];
      $argobj[$essp] = @$argobj[$essp].' '.$cval;
    }
    return $argobj;
  }

  public function is_you_are_curruser($argobj){
    $status = $this->ess->check_env_availability($argobj, 'wd');
    return $this->ess->is_youre_curruser($status);
  }

}


?>
