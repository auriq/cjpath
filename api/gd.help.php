<?php
#       read library
require_once 'common.head.php';
 
require_once(dirname(__FILE__).'/Classes/utils/utils.php');
require_once(dirname(__FILE__).'/Classes/utils/mysqldb.php');
$utils = new Utils();      
$jsonobj['helpContent'] = (new MysqlQueryCondition())->return_helps($utils->get_browser_language());
$utils->output_json_result($jsonobj, $PARA);


exit();
?>
