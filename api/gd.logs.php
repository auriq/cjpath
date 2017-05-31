<?php

require_once 'common.head.php';
require_once(dirname(__FILE__).'/Classes/ess/ess.php');
require_once(dirname(__FILE__).'/Classes/utils/utils.php');

$uid   = @$PARA['uid'];
$lid   = @$PARA['lid'];
$ctype = @$PARA['type'];
$gtype = @$PARA['gettype'];

$loginit = array(
  'log' => '',
  'timestamp' => '-'
);

if($ctype == 'udbdlist'){
  $alllist = scandir('/opt/aq_tool/udb');
  $udbdlist = array();
  foreach($alllist as $idx => $fname){
    if(preg_match('/^udbd-[0-9]+.log$/',$fname)){
      array_push($udbdlist, $fname);
    }
  }
}

$data = $loginit;
$fpath = null;
switch ($ctype){
  case 'tasklog':
    $fpath = (new Ess())->return_ssid_ess_path('', $uid, $lid).'/task.log';
    break;
  case 'udbdlog':
    $udbdlogname = @$PARA['udbdlogname'];
    $fpath = "/opt/aq_tool/udb/$udbdlogname";
    break;
  case 'udbdlist':
    if(isset($udbdlist[0])) $fpath = "/opt/aq_tool/udb/".$udbdlist[0];
    break;
}

if(in_array($gtype, array('data', 'dl'))){
  if(file_exists($fpath)){
    $data = array(
      'log' => file_get_contents($fpath),
      'timestamp' =>  filemtime($fpath)
    );
  }
}

if($ctype == 'udbdlist') $data['list'] = $udbdlist;


# --
# Cached Variable under .talktoapache directory.
# --
if($ctype == 'cachedvarlist'){
  $ess = new Ess();
  $wdir = $ess->get_vars_ess($uid, $lid, 'returnUsersWorkspaceDir', "");
  $cdir = "$wdir/.gui";
  $cachedvarfist = scandir($cdir);
  $cvarlist = array();
  foreach($cachedvarfist as $idx => $fname){
    if(!in_array($fname, array('.','..'))){
      array_push($cvarlist, array(
        'fname' => $fname,
        'value' => file_get_contents("$cdir/$fname"),
        'timestamp' => filemtime ("$cdir/$fname")
      ));
    }
  }
  $data['list'] = $cvarlist;
}


  $mpip = @$_SERVER['SERVER_NAME'];
$utils = new Utils();
if($gtype == 'data'){ // return to ajax
  $utils->output_json_result($data, $PARA);
}else if($gtype == 'dl'){ // download contents
  $fname = basename($fpath);
  $utils->set_headers_to_download($fname);
  echo @$data['log'];
}else if($gtype == 'rm'){ // remove log
#error_log($fpath, 0);
  unlink($fpath);
  $utils->output_json_result($data, $PARA);
}

exit();
?>
