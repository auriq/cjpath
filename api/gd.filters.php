<?php

#       common process
require_once 'common.head.php';

require_once(dirname(__FILE__).'/Classes/filterManager.php');
(new FilterManager())->get($PARA);


exit();
?>
