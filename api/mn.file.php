<?php
#       read library
require_once 'common.head.php';
require_once(dirname(__FILE__).'/Classes/ess/ess.php');
require_once(dirname(__FILE__).'/Classes/utils/utils.php');
#global $OUT_FILT_CHARCODE;


$ess   = new Ess();
$utils = new Utils();

$errFlg = false;
$csvFlg = false;
$jsonobj = array();
$data = array();
if(isset($PARA['uid'])){
  $PARA = (new Utils())->load_userconfig_assign($PARA);
  #       Define parameter
  $uid    = @$PARA['uid'];
  $lid    = @$PARA['lid'];
  $ftype  = @$PARA['ftype'];
  $mtype  = @$PARA['mtype'];
  $fname  = $ess->return_users_uploadfile_path($PARA);
  $chrcd  = @$PARA['charcode'];
  if($mtype == 'upload'){
    if(isset($_FILES["upfile"])){
      if(is_uploaded_file($_FILES["upfile"]["tmp_name"])){
        setlocale(LC_ALL, 'ja_JP');
        setlocale(LC_ALL, 'ja_JP.UTF-8');
        setlocale(LC_ALL, 'ja_JP.EUC-JP');
        setlocale(LC_ALL, 'ja_JP.Shift_JIS');
        $tmpfname = $_FILES["upfile"]["tmp_name"];
        #if($OUT_FILT_CHARCODE == 'SJIS'){
        if($chrcd == 'SJIS'){
          $fp = trim(mb_convert_encoding(file_get_contents($tmpfname), 'utf-8', 'sjis-win'));
        }else{
          $fp = trim(file_get_contents($tmpfname));
        }
  
        #move_uploaded_file($tmpfname, $fname);
        file_put_contents($fname, $fp);
      }
    }
    $jsonobj = array('error' => $errFlg);
  }else if($mtype == 'checkexists'){
    $fexist = file_exists($fname);
    $jsonobj = array(
      'isexist' => $fexist,
      'wordsdict' => $utils->wordsdict()['toui-etc']['fupload_status']
    );
  }else if($mtype == 'remove'){
    unlink($fname);
    $jsonobj = array('error' => $errFlg);
  }else if($mtype == 'download'){
    $csvFlg = True;
    if(file_exists($fname)){
      $fl = file($fname);
      foreach($fl as $idx => $row){
        array_push($data, explode(',',$row));
      }
    }
  }
}else{
  $errFlg = true;
  $jsonobj = array('error' => $errFlg);
}

#---------------------------
#  output
#---------------------------
if($csvFlg== True && $errFlg == False){
  $dlfname = $ftype;
  $utils->output_csv($uid, $data, $dlfname);
}else{
  $utils->output_json_result($jsonobj, $PARA);
}


exit();
?>
