<?php


#  get current directory
$CURR_DIR = getcwd();
#  Read configuration file
require_once $CURR_DIR.'/.gd.confg.php';
#  Read common library
require_once(dirname(__FILE__).'/Classes/utils/utils.php');
require_once(dirname(__FILE__).'/Classes/inputCheck.php');
require_once(dirname(__FILE__).'/Classes/ess/ess.php');


#------------------------
#  Checks
#------------------------
$errFlg = True;

if($_SERVER["REQUEST_METHOD"] != "POST"){
  if(!$ISDEV) exit;
  $PARA = $_GET;
}else{
  $PARA = json_decode(file_get_contents("php://input"), true);
  #$PARA = $_POST;
}

if(@$PARA['uid'] == 'common'){ # just in case.
  exit("Invalid parameters.");
}

$TIME_ZONE = (new Ess())->return_userconfig(@$PARA['uid'], @$PARA['lid'], 'timezone');
if($TIME_ZONE!='done') date_default_timezone_set($TIME_ZONE);

#  input check
if(isset($check_pattern)){
  #$input = \LIB\COMMON\input_check($PARA, $PARA_CHECK_CONFG[$check_pattern]);
  $input = (new InputCheck())->run($PARA, $PARA_CHECK_CONFG[$check_pattern]);
}else{
  $input = array(
    'check' => true,
    'paras' => array(
      'uid' => 'common'
    )
  );
}
if(!$input['check']) {
  $errFlg = False;
}

#  refere check
$errFlg = (new Utils())->referer_check() ? $errFlg : False;

if($errFlg){
  $input['paras'] = (new Utils())->load_userconfig_assign($input['paras']);
  if(@$input['paras']['timezone']!='') if(@$input['paras']['timezone']!='done') date_default_timezone_set(@$input['paras']['timezone']);
}

if( !isset($_SESSION) ) {
  session_start();
}

# --
#  Set access header for CORS
# --
$okorigin = (strstr(@$_SERVER['HTTP_ORIGIN'], ':9000') || $ISDEV || strstr(@$_SERVER['HTTP_ORIGIN'], '.auriq.com')) ? @$_SERVER['HTTP_ORIGIN'] : $OKORIGINS;
header("Access-Control-Allow-Origin: $okorigin");
header('Access-Control-Allow-Credentials:true');


?>
