<?php
#       read library
$check_pattern = 'path_detail';
require_once 'common.head.php';
$errFlg = true;
if($errFlg && isset($input['paras']['uid'])){
  $csvFlg          = @$input['paras']['csvflg'];

  require_once(dirname(__FILE__).'/Classes/getContents.php');
  $jsonobj = (new GetContents())->run($input['paras']);

}else{
  $jsonobj = array(
    'xAxis_vals' => array(),
    'legend_label' => array(),
    'nvd3values' => array()
  );
#$CHART_EMPTY_VALUE;
}

#---------------------------
#  output
#---------------------------
$utils = new Utils();
if($csvFlg){
  $utils->output_csv(@$input['paras']['uid'], $jsonobj['data'], $jsonobj['fname']);
}else{
  $utils->output_json_result($jsonobj, $input['paras']);
}


exit();
?>
