<?php
require_once(dirname(__FILE__).'/utils/utils.php');
require_once(dirname(__FILE__).'/ess/essManipulation.php');
require_once(dirname(__FILE__).'/ess/ess.php');

class EssController{
  function __construct(){
    $this->utils = new Utils();
  }
  public function run($argobj){
    $dotype = @$argobj['dotype'];
    if($dotype == 'check'){
      $jsonobj = (new EssStatusCheck())->return_status($argobj);
      $this->utils->output_json_result($jsonobj, $argobj);
    }else if($dotype == 'manipulate'){
      (new EssStatusChange())->run($argobj);
      $this->utils->output_json_result(array(
        'error'  => false,
        'status' => 0
      ), $argobj);
    }else if($dotype == 'udbd'){
      $jsonobj = (new EssUdbd())->run($argobj);
      $this->utils->output_json_result($jsonobj, $argobj);
    }
  }
}

class EssStatusCheck{
  function __construct(){
    $this->utils = new Utils();
    $this->lang  = $this->utils->get_browser_language();
    $this->ess    = new Ess();
    $this->essman = new EssManipulation();
  }
  public function return_status($argobj){
    $chekctype = @$argobj['trgtype'];
    $jsonobj = array();
    if($chekctype == 'instance'){
      $jsonobj = array(
        'status'      => $this->essman->check_instance_existing($argobj),
        'timestamp'   => $this->essman->get_timestamp_instance_start($argobj),
        'waitfrom'    => $this->essman->get_timestamp_since_when($argobj),
        'localisused' => $this->essman->check_if_env_occupied($argobj, 'local'),
        'wdisused'    => $this->essman->check_if_env_occupied($argobj, 'wd'),
        'isimporting' => $this->essman->check_if_import_running_in_machine($argobj),
        'isimported'  => $this->essman->check_if_data_is_imported($argobj),
#        'curr_uid'    => $this->essman->get_current_env_project($argobj),
        'lang'        => $this->lang
      );
      if($jsonobj['status'] != 'none'){
        $jsonobj['insttype'] = $this->essman->get_memory_stats($argobj);
      }
    }else if($chekctype == 'import'){
      $jsonobj = array(
        'status'   => $this->ess->isimporting($argobj, 'wd', 'myself'),
        'waitfrom' => $this->essman->get_timestamp_since_when($argobj),
        'lang'     => $this->lang
      );
#    }else if($chekctype == 'mstat'){
#      $jsonobj = array(
#        'memorystat' => $this->essman->get_memory_stats($argobj),
#        'udbdstatus' => $this->essman->get_udbd_status($argobj)
#      );
    }else if($chekctype == 'checkifhijacked'){
      $jsonobj = array(
        'localisused' => $this->essman->check_if_env_occupied($argobj, 'local'),
        'wdisused'    => $this->essman->check_if_env_occupied($argobj, 'wd'),
        'isimporting' => $this->essman->check_if_import_running_in_machine($argobj)
      );
    }
    $jsonobj['wordsdict'] = (new Utils())->wordsdict()['toui-ess']; # add "words dictionaty" to return object.
    return $jsonobj;
  }
}


class EssStatusChange{
  function __construct(){
    $this->utils = new Utils();
    $this->lang  = $this->utils->get_browser_language();
    $this->essman = new EssManipulation();
  }
  public function run($argobj){
    $cmdtype = @$argobj['cmdtype'];
    if($cmdtype == 'terminate'){
      $this->essman->terminate_instance($argobj);
    }else if($cmdtype == 'logout'){
      $this->essman->logout($argobj);
    }else if($cmdtype == 'create'){
      $this->essman->create_instance($argobj);
    }else if($cmdtype == 'takeover'){
      $this->essman->takeover_curr_user($argobj);
    }
  }
}

class EssUdbd{
  function __construct(){
    $this->utils = new Utils();
    $this->essman = new EssManipulation();
  }
  public function run($argobj){
    if($this->essman->is_you_are_curruser($argobj)){
      $cmdtype = @$argobj['cmdtype'];
      if($cmdtype == 'import'){
        return $this->essman->read_data($argobj);
      }else if($cmdtype == 'profile'){
        return $this->essman->set_profile($argobj);
      }else if($cmdtype == 'calpath'){
        return $this->essman->set_calpath($argobj);
      }
    }else{
      return array(
        'error'   => true,
        'message' => 'You are not the current user.'
      );
    }
  }
}

?>
