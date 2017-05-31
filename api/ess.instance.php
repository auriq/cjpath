<?php
    
require_once 'common.head.php';
#---------------------------
#    read library
#---------------------------

$operationType = @$PARA['dotype'];
$checkTarget   = @$PARA['trgtype'];
$cmdtype       = @$PARA['cmdtype'];
$uid           = @$PARA['uid'];
$lid           = @$PARA['lid'];

require_once(dirname(__FILE__).'/Classes/essController.php');

(new EssController())->run($PARA);


exit();
?>
