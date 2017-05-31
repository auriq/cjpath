<?php
#       read library
$check_pattern = 'path_readdata';
require_once 'common.head.php';
$errFlg = true;
$OPTIONS_EMPTY_VALUE = array(
    "data"   => array()
);
if($errFlg){
  require_once(dirname(__FILE__).'/Classes/essController.php');
  $input['paras']['dotype']  = 'udbd';
  (new EssController())->run($input['paras']);
}

exit();
?>
