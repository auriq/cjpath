<?php

require_once 'common.head.php';
require_once(dirname(__FILE__).'/Classes/utils/mysqldb.php');
$ctype = @$PARA['contenttype'];  # content type
$otype = @$PARA['oprtype'];    # operation type
$uid = @$PARA['uid'];
$lid = @$PARA['lid'];


if($ctype == 'cachelist'){
  if($otype == 'syncFromS3'){
    require_once(dirname(__FILE__).'/Classes/ess/ess.php');
    $ess = new Ess();
    $dlist = $ess->get_vars_ess($uid, $lid, 'getCacheListFromS3', '');
    $dlist = explode(' ',$dlist);
    $query = "SELECT cachedirname FROM CacheList WHERE custnoLogin=$lid AND custnoView='$uid'";
    $extdirs = array();
    $retval = (new MysqlDb())->getdata_mysql($query, 'dict');
    foreach($retval as $idx => $row) array_push($extdirs, @$row['cachedirname']);
    $newdname = array();
    foreach($dlist as $idx => $dir){
      if(!in_array($dir, $extdirs)) array_push($newdname, $dir);
    }
    $queries = array();
    foreach($newdname as $idx => $dir){
      $dname = get_default_name_of_cache($dir);
      array_push($queries, "INSERT INTO CacheList (custnoLogin,custnoView,cachedirname,label,isactive) VALUES ($lid,'$uid','$dir','$dname',1)");
    }
    (new MysqlDb())->commit_queries($queries);
  }else if($otype == 'edit'){
    $obj = json_decode($PARA['obj']);
    $id = ''; $sets = array();
    foreach($obj as $column => $value){
      if($column !='$$hashKey' && !is_null($value)){
        $id = ($column == 'id') ? $value : $id;
        if(!in_array($value, array('NOW()','NULL'))) $value = "'$value'";
        if($column != 'id') array_push($sets, "$column=$value");
      }
    } 
    $sets = implode(',', $sets);
#error_log("UPDATE CVPathSettings.CacheList SET $sets WHERE id=$id", 0);
    if($sets!='' && $id!='') (new MysqlDb())->commit_queries(array("UPDATE CVPathSettings.CacheList SET $sets WHERE id=$id"));
  }

  # get data from mysql database and show it.
  $is_show_deleted = @$PARA['is_show_deleted'];
  $dynwhere = '';
  if(!$is_show_deleted) $dynwhere .= " AND deleted_at IS NULL";
  $query = "SELECT * FROM CacheList WHERE custnoLogin=$lid AND custnoView='$uid' $dynwhere";
  $retval = (new MysqlDb())->getdata_mysql($query, 'dict');
  $jsonobj = array(
    'cachelist' => $retval
  );
}else if($ctype == 'cachefiles'){
  $cdirname = @$PARA['cachedirname'];
  require_once(dirname(__FILE__).'/Classes/ess/ess.php');
  $ess = new Ess();
  if($otype == 'get'){
  }else if($otype == 'remove'){
    $cleartype = @$PARA['clearcachetype']; # 'withoutcj' or 'all'
    $ess->get_vars_ess($uid, $lid, 'removeCacheFilesFromRepository', "$cdirname:$cleartype");
  }

  $cachefiles = explode(' ', $ess->get_vars_ess($uid, $lid, 'getCacheFilesFromRepository', $cdirname));
  $jsonobj = array(
    'files' => $cachefiles
  );
#  for($cachefiles as $idx => $cachefile){
#error_log($cachefile, 0);
#  }
}else if($ctype == 'cachebatch'){
  if($otype == 'add'){
    $obj = json_decode($PARA['obj']);
    $cols = array('status', 'modified_at', 'timestamp_requested');
    $vals = array("'requested'", 'now()', 'now()');
    foreach($obj as $idx => $row){
      $column = $row->column;
      $value  = $row->value;
      array_push($cols, $column);
      array_push($vals, "'$value'");
    } 
    $cols = implode(',', $cols);
    $vals = implode(',', $vals);
    $query = "INSERT INTO CVPathSettings.CacheBatchList ($cols) VALUES ($vals)";
    (new MysqlDb())->commit_queries(array($query));
  }else if($otype == 'edit'){
    $obj = json_decode($PARA['obj']);
    $sets=array();
    $id = null;
    foreach($obj as $column => $value){
      #$column = $row->column;
      #$value  = $row->value;
      if($column !='$$hashKey'){
        $id = ($column == 'id') ? $value : $id;
        if($column != 'id') array_push($sets, "$column='$value'");
      }
    } 
    $sets = implode(',', $sets);
    $query = "UPDATE CVPathSettings.CacheBatchList SET $sets WHERE id=$id";
    (new MysqlDb())->commit_queries(array($query));
  }else if(in_array($otype, array('remove','cancel'))){
    $id = @$PARA['cid'];
    $query = "UPDATE CVPathSettings.CacheBatchList SET deleted_at=NOW() WHERE id=$id";
  error_log($query, 0);
    (new MysqlDb())->commit_queries(array($query));
    if($otype == 'remove'){
      $uid = @$PARA['uid'];
      $lid = @$PARA['lid'];
      require_once(dirname(__FILE__).'/Classes/ess/ess.php');
      $ess = new Ess();
      $fname = @$PARA['fname'];
      $ess->get_vars_ess($uid, $lid, 'removeCacheDirFromS3', "$fname");
    }
  }
  
  # --
  # Get cachelist
  # -- 
  $query = "SELECT * FROM CVPathSettings.CacheBatchList WHERE custnoLogin=$lid AND custnoView='$uid'";
  $retval = (new MysqlDb())->getdata_mysql($query, 'dict');
  
  $jsonobj = array(
    'cachelist' => $retval
  );
}

$utils = new Utils();
$utils->output_json_result($jsonobj, $PARA);

function get_default_name_of_cache($dir){
  return $dir;
  #$dirspls = explode('-', $dir);
  ##$pname  = @$dirspls[0];
  #$date_f = @$dirspls[1]."-".@$dirspls[2]."-".@$dirspls[3];
  #$date_t = @$dirspls[4]."-".@$dirspls[5]."-".@$dirspls[6];
  #$date_b = @$dirspls[7];
  #$sample = @$dirspls[8];
  #$index  = isset($dirspls[9]) ? " [".@$dirspls[9]."]" : '';
  #return "$date_f ～ $date_t ($date_b 日遡) 1/$sample$index";
}


exit();
?>
