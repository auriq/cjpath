<?php
#       read library
$check_pattern = 'path_detail';
require_once 'common.head.php';
if($errFlg){

  #       Define parameter
  $uid    = $input['paras']['uid'];
  $lid    = $input['paras']['lid'];
  $type   = $input['paras']['opttype'];
  $mindate = @$input['paras']['mindate'];
  
  #---------------------------
  #  result
  #---------------------------
  require_once(dirname(__FILE__).'/Classes/getOptions.php');
  require_once(dirname(__FILE__).'/Classes/utils/utils.php');
  $utils   = new Utils();
  $opt     = new GetOptions();
  $jsonobj = array();
  if($type == 'landing'){
    $jsonobj = $opt->get_contents_availability($uid);
  }else if($type=='global' || $type=='eachpathpopup'){
    #  Result array
    $jsonobj = $opt->get_global($uid, $lid, $mindate);
    if($type == 'eachpathpopup'){
      array_splice($jsonobj['contents']['data'][0]['contents'], 0, 1);
    }
    #$jsonobj = $jsonobj;
  }else if($type == 'cvpathpaging'){
    $jsonobj = $opt->get_cvpath_paging($input['paras']);
  }else if($type == 'wordsdict'){
    $jsonobj = array(
      'wordsdict' => $utils->wordsdict()['toui-etc'][@$input['paras']['dtype']]
    );
  }

  #---------------------------
  #  output
  #---------------------------
  $utils->output_json_result($jsonobj, $input['paras']);
}
exit();

?>
