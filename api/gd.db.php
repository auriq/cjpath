<?php

require_once 'common.head.php';
require_once(dirname(__FILE__).'/Classes/ess/ess.php');
require_once(dirname(__FILE__).'/Classes/utils/utils.php');
require_once(dirname(__FILE__).'/Classes/utils/mysqldb.php');
$mydb  = new MysqlDb();
$utils = new Utils();

$uid   = @$PARA['uid'];
$lid   = @$PARA['lid'];
$ctype = @$PARA['type'];
$otype = @$PARA['oprtype'];

$loginit = array(
  'log' => '',
  'timestamp' => '-'
);


$data = array(
  'iserror' => true
);

# --
#  db select/insert/update/delete
# --
if($otype == 'get'){
  if($ctype == 'all'){
    $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' AND TABLE_SCHEMA='CVPathSettings'";
    $tablelist = $mydb->getdata_mysql($query, 'list');
  }
  
  $tblname = ($ctype=='all') ? @$tablelist[0] : @$PARA['tblname'];
  
  if($tblname != ''){
    $query = "SELECT * FROM CVPathSettings.$tblname";
    $tabledata = $mydb->getdata_mysql($query, 'dict');
    $data = array(
      'tbody' => $tabledata,
      'thead' => array_keys(isset($tabledata[0]) ? $tabledata[0] : array())
    );
    if($ctype == 'all') $data['tablelist'] = $tablelist;
  }
}
if($otype == 'update'){
  $ret = -1;
  $dbobj = @$PARA['dbObj'];
  $dbobj = $utils->return_jsonobj_from_string($dbobj);
  $operatoin = $dbobj['operation']; $names=$dbobj['names']; $values=$dbobj['values']; $tblname=$dbobj['tblname']; $id=$dbobj['id'];
  if($operatoin == 'insert'){
    $names  = implode(',', $names);
    $escaped_values = array();
    foreach($values as $idx => $value){
      array_push($escaped_values, "'".mysql_escape_string($value)."'");
    }
    $values = implode(',', $escaped_values);
    $query = "INSERT INTO CVPathSettings.$tblname ($names) VALUES ($values)";
  }else if($operatoin == 'update'){
    $sets = array();
    foreach($names as $idx => $name){
      $value = $values[$idx];
      if($value != "'null'") array_push($sets, "$name='".mysql_escape_string($value)."'");
      if($value == "'null'")  array_push($sets, "$name=NULL");
    }
    $sets = implode(',', $sets);
    $query = "UPDATE CVPathSettings.$tblname SET $sets WHERE id=$id";
  }
  if($query){
    $ret = $mydb->commit_queries(array($query));
  }
  $data['iserror'] = ($ret !== 0);
}
if($otype == 'delete'){
  $tblname = @$PARA['tblname'];
  $id      = @$PARA['id'];
  $query = "DELETE FROM CVPathSettings.$tblname WHERE id=$id";
#error_log($query, 0);
  $ret = $mydb->commit_queries(array($query));
  $data['iserror'] = ($ret !== 0);
}

# --
#  backups
# --
if($otype == 'dbbackups'){
  $ess = new Ess();
  if($ctype == 'restore'){
    $fname = @$PARA['fname'];
    $fpath = "/tmp/$fname";
    $ess->get_vars_ess($uid, $lid, 'downloadMysqlBackupFromS3', "$fpath");
    if(file_exists($fpath)){
      $cmd   = "mysql -u $DB_USERNAME -p$DB_PASSWORD $DB_DBNAME < $fpath";
      shell_exec($cmd);
    }
  }
  if($ctype == 'download'){
    $fname = @$PARA['fname'];
    $fpath = "/tmp/$fname";
    $ess->get_vars_ess($uid, $lid, 'downloadMysqlBackupFromS3', "$fpath");
#    $mpip = @$_SERVER['SERVER_NAME'];
#    header("Access-Control-Allow-Origin: http://$mpip:9000");
#    header('Access-Control-Allow-Credentials : true');
#    header('Content-Type:text/plain');
#    header('Content-Disposition: attachment; filename="'.$fname.'"');
    $utils->set_headers_to_download($fname);
    if(file_exists($fpath)){
      echo file_get_contents($fpath);
      unlink($fpath);
    }else{
      echo 'error';
    }
    exit();
  }
  if($ctype == 'take'){
    $myserver = $_SERVER['SERVER_NAME'];
    $fpath = "/tmp/mysqldump-$myserver-$DB_DBNAME-`date +%F | sed 's/-//g'`_`date +%T | sed 's/://g'`.sql";
    $cmd   = "mysqldump -u $DB_USERNAME -p$DB_PASSWORD $DB_DBNAME > $fpath";
    shell_exec($cmd);
    $ess->get_vars_ess($uid, $lid, 'uploadMysqlBackupToS3', "$fpath");
  }
  # --
  # build list
  # --
  $s3list = $ess->get_vars_ess($uid, $lid, 'getMysqlBackupListS3', "");
  $s3list = explode(' ', $s3list);
  $list = array();
  foreach($s3list as $idx => $item){
    $item = explode('@', $item);
    if(@$item[1] != 'PRE' && @$item[1] != ''){
      array_push($list, array(
        'timestamp' => "$item[0] $item[1]",
        'size'      => @$item[2],
        'fname'     => @$item[3]
      ));
    }
  }
  $data = array(
    'list' => $list
  );
}



$utils->output_json_result($data, $PARA);


exit();
?>
