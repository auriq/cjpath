<?php

class MysqlDb{
  protected $dbserver,$DB_DBNAME,$DB_HOSTNAME,$DB_USERNAME,$DB_PASSWORD,$DB_CHARCODE, $LABELS_USERS;
  function __construct(){
    require_once getcwd().'/.gd.confg.php';
    global $DB_DBNAME,$DB_HOSTNAME,$DB_USERNAME,$DB_PASSWORD,$DB_CHARCODE;
    $this->DB_DBNAME   = $DB_DBNAME;
    $this->DB_HOSTNAME = $DB_HOSTNAME;
    $this->DB_USERNAME = $DB_USERNAME;
    $this->DB_PASSWORD = $DB_PASSWORD;
    $this->DB_CHARCODE = $DB_CHARCODE;
    $this->TABLE_CALC_MASTER = $this->DB_DBNAME.'.CalcSettingMaster';
    $this->TABLE_FILTER_PARTS = $this->DB_DBNAME.'.CalcSettingOptionParts';
    $this->TABLE_CALC_USER   = $this->DB_DBNAME.'.CalcSettingUser';
    $this->TABLE_SYMBOLS     = $this->DB_DBNAME.'.SymbolsSetting';
    $this->TABLE_ICONS       = $this->DB_DBNAME.'.Icons';
    $this->TABLE_COLORS      = $this->DB_DBNAME.'.Colors';
    $this->TABLE_CACHELIST   = $this->DB_DBNAME.'.CacheList';

    $this->LABELS_USERS = array(
      'id'            => 'ID',
      'setid'         => 'Set ID',
      'setname'       => 'Name',
      'setactiveflg'  => 'Set isActive',
      'condid'        => 'Condition ID',
      'condactiveflg' => 'Condition isActive',
      'condname'      => 'Name',
      'condselection' => 'Condition',
      'condvalue'     => 'Condition Value',
      'memo'          => 'Comment'
    );
    $this->TABLECOLUMNS = array();
  }
  protected function connect_mysql(){
    #       MySql Connection
    $db_database=$this->DB_DBNAME;
    $db_server=mysql_connect($this->DB_HOSTNAME, $this->DB_USERNAME, $this->DB_PASSWORD);
    if(!$db_server) die("Unable to connect to MySQL: " . mysql_error());
    mysql_set_charset($this->DB_CHARCODE, $db_server);
    mysql_select_db($db_database) or die("Unable to select database: " . mysql_error());
    # set as property
    $this->dbserver = $db_server;
  }
  protected function disconnect_mysql(){
    mysql_close($this->dbserver);
  }
  public function getdata_mysql($query, $format){
    $this->connect_mysql();
    $result=mysql_query($query);
    if (!$result) return array('error' => True,'msg'   => mysql_error());
    $retval = array();
    if($format == 'dict'){
      while($row = mysql_fetch_assoc($result)){
        array_push($retval, $row);
      }
    }else if($format == 'list'){
      while($row = mysql_fetch_row($result)){
        foreach($row as $idx => $col){
          array_push($retval, $col);
        }
      }
    }else{
      while($row = mysql_fetch_row($result)){
        array_push($retval, $row);
      }
    }
    $this->disconnect_mysql();
    return $retval;
  }
  public function commit_queries($queries){
    $this->connect_mysql();
    foreach($queries as $idx => $query){
      $result=mysql_query($query);
      if(!$result){
        error_log('========== SQL ERROR ==========',0);
        error_log(print_r(mysql_error(), true), 0);
        error_log($query, 0);
        return 1;
      #}else{
      #  error_log('--- SQL Succeeded ---', 0);
      #  error_log($query, 0);
      }
    }
    $this->disconnect_mysql();
    return 0;
  }
  protected function dbcol_to_nickname($dbcol){
    return strtolower($dbcol);
  }
  protected function translate_nickname_to_colname($table, $nickname){
    if(!isset($this->TABLECOLUMNS[$table])){
      $this->TABLECOLUMNS[$table] = $this->return_table_colname($table);
    }
    foreach($this->TABLECOLUMNS[$table] as $idx => $colname){
      if($nickname == $this->dbcol_to_nickname($colname)){
        return $colname;
      }
    }
  }
  protected function return_table_colname($table){
    $result = array();
    foreach($this->getdata_mysql('DESC '.$table, 'dict') as $idx => $row){
      array_push($result, @$row['Field']);
    }
    return $result;
  }
}

class MysqlQueryCondition extends MysqlDb{
  public function get_usersegment_group($uid, $lid){
$lid = 1;
    $query = "SELECT condName,condValue FROM ".$this->TABLE_CALC_USER
             ." WHERE custnoLogin=".$lid." AND custnoView='".$uid."' AND essParaName='usrseg' AND setActiveFlg!=0 ORDER BY setId,condId";
    $dict = $this->getdata_mysql($query, 'dict');

    $result = array();
    foreach($dict as $idx=>$rcrd){
      $cnam = @$rcrd['condName'];
      $cval = @$rcrd['condValue'];
      $result[$cnam] = $cval;
    }
    return $result;
  }
  public function get_symbols(){
    $query = " SELECT symbolname,color,icn.dval "
             ." FROM ".$this->TABLE_SYMBOLS." sym "
             ." INNER JOIN ".$this->TABLE_ICONS." icn WHERE icn.name=sym.icon";
    
    $dict = $this->getdata_mysql($query, 'dict');
    $result = array();
    foreach($dict as $idx=>$rcrd){
      $result[@$rcrd['symbolname']] = array(
        'color' => @$rcrd['color'],
        'icon'  => @$rcrd['dval']
      );
    }
    return $result;
  }
  public function get_colors_list(){
    $query = "SELECT color FROM ".$this->TABLE_COLORS." WHERE cvflg = 0 ";
    $dict = $this->getdata_mysql($query, 'dict');
    $result = array();
    foreach($dict as $idx=>$rcrd){
      array_push($result, @$rcrd['color']);
    }
    return $result;
  }

  public function checkif_tables_exists(){
    $query = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '".$this->DB_DBNAME."'";
    $arr = $this->getdata_mysql($query, 'arr');
    $cnt = isset($arr[0][0]) ? $arr[0][0] : 0;
    return ($cnt == 0);
  }

  public function checkif_customer_db_empty($uid, $lid){
$lid = 1;
    $query = "SELECT COUNT(*) FROM $this->TABLE_CALC_USER WHERE custnoLogin=".$lid." AND custnoView='".$uid."'";
    $arr = $this->getdata_mysql($query, 'arr');
    $cnt = isset($arr[0][0]) ? $arr[0][0] : 0;
    return ($cnt == 0);
  }

  public function get_userparams($argobj, $format){
    $lid = @$argobj['lid'];
$lid = 1;
    $colms = array('master.essParaName','master.bigCateLabel','master.smlCateLabel','condName','condValue');
    $query = "SELECT ".implode(',',$colms)
         ." FROM ".$this->TABLE_CALC_USER." AS user"
         ." INNER JOIN ".$this->TABLE_CALC_MASTER." AS master "
             ." ON user.essParaName=master.essParaName "
             ."    AND user.groupName=master.groupName "
         ." WHERE "
             ." custnoLogin=".$lid
             ." AND custnoView='".@$argobj['uid']."'"
             ." AND condActiveFlg!=0 AND setActiveFlg!=0 ORDER BY setId,condId";
    $result = $this->getdata_mysql($query, $format);
    if($format == 'dict'){
      return $result;
    }else{
      # thead
      $thead = array();
      foreach($colms as $idx => $col){
        if($idx > 0){
          $colarr = explode('.', $col);
          array_push($thead, $colarr[count($colarr)-1]);
        }
      }
      # tbody
      $tbody = array();
      foreach($result as $idx => $row){
        $newrow = $row;
        array_shift($newrow);
        array_push($tbody, $newrow);
      }
      return array(
        'thead' => array($thead),
        'tbody' => $tbody
      );
    }
  }

  public function get_userparams_all($argobj){
    # return ALL(meaning "include inactive condition too") parameters in CalcSettingUser
    $lid = @$argobj['lid'];
$lid = 1;
    $colms = array('id', 'essParaName', 'setId', 'setName', 'setActiveFlg', 'condActiveFlg', 'condName', 'condValue');
    $query1 = "SELECT ".implode(',',$colms)
         ." FROM ".$this->TABLE_CALC_USER
         ." WHERE "
             ." custnoLogin=$lid"
             ." AND custnoView='".@$argobj['uid']."'"
             ." AND essParaName NOT IN ('usrseg','pathtpdef')"
             #." AND essParaName NOT IN ('usrseg')"
             ." ORDER BY essParaName,setId,condId";
    $result1 = $this->getdata_mysql($query1, 'dict');
    $query2 = "SELECT ".implode(',',$colms)
         ." FROM ".$this->TABLE_CALC_USER
         ." WHERE "
             ." custnoLogin=$lid"
             ." AND custnoView='".@$argobj['uid']."'"
             ." AND essParaName IN ('usrseg','pathtpdef')"
             #." AND essParaName IN ('usrseg')"
             ." ORDER BY essParaName,setId,condId";
    $result2 = $this->getdata_mysql($query2, 'dict');
    $usrsetobj = array();
    # list obj (non-group setting filter/cvcond)
    foreach($result1 as $idx => $row){
      $esspname   = @$row['essParaName'];
      $condactflg = @$row['condActiveFlg'];
      if(!isset($usrsetobj[$esspname])) $usrsetobj[$esspname] = array('list'=>array());
      array_push($usrsetobj[$esspname]['list'], $row);
      if($condactflg == 1 && !in_array($esspname, array('filter','pathfilter'))) $usrsetobj[$esspname]['curractive'] = $row;
    }
    # list obj (group setting usrseg/pathtpdef)
    $chidx = -1;  $currsetid=''; $newsetflg = False; $curresspname='';
    foreach($result2 as $idx => $row){
      $esspname   = @$row['essParaName'];
      $setname    = @$row['setName'];
      $setid      = @$row['setId'];
      $setactflg  = @$row['setActiveFlg'];
      $condactflg = @$row['condActiveFlg'];
      if(!isset($usrsetobj[$esspname])) $usrsetobj[$esspname] = array('list'=>array());
      $newsetflg = ($setid != $currsetid || $esspname != $curresspname);
      if($newsetflg){
        array_push($usrsetobj[$esspname]['list'], array('list'=>array(),'ids'=>array(),'setId'=>$setid,'setName'=>$setname,'setActiveFlg'=>$setactflg));
        $chidx = count($usrsetobj[$esspname]['list']) - 1;
        $curresspname=$esspname; $currsetid=$setid;
      }
      array_push($usrsetobj[$esspname]['list'][$chidx]['list'], $row);
      array_push($usrsetobj[$esspname]['list'][$chidx]['ids'], @$row['id']);
      if($setactflg==1){
        if(!isset($usrsetobj[$esspname]['curractive'])) $usrsetobj[$esspname]['curractive']=$usrsetobj[$esspname]['list'][$chidx];
        $usrsetobj[$esspname]['curractive']['ids'] = $usrsetobj[$esspname]['list'][$chidx]['ids'];
        $usrsetobj[$esspname]['curractive']['list'] = $usrsetobj[$esspname]['list'][$chidx]['list'];
        if($condactflg==1) {
          $usrsetobj[$esspname]['curractive']['activeCondId']   = @$row['id'];
          $usrsetobj[$esspname]['curractive']['activeCondName'] = @$row['condName'];
        }
      }
    }
    return $usrsetobj;
  }

  public function get_all_filter_options_parts($argobj, $format){
    $uid = @$argobj['uid'];
    $lid = @$argobj['lid'];
    $esspara = @$argobj['esspara'];
    #$format = @$argobj['format'];
    $isShowDelete = @$argob['is_show_deleted'];
$lid = 1;
    $colms = array('id', 'custnoLogin','custnoView','essParaName','condName','condValue','memo');
    $dyn_where = ($isShowDelete) ? '' : ' AND deletedAt IS NULL';
    $dyn_where = ($esspara=='') ? '' : "$dyn_where AND essParaName='$esspara'";
    $query = "SELECT ".implode(',',$colms)." FROM ".$this->TABLE_FILTER_PARTS." WHERE custnoView='$uid' AND custnoLogin=$lid $dyn_where";
    $result = $this->getdata_mysql($query, $format);
    if($format == 'dict'){
      return $result;
    }else{
      # thead
      $thead = $colms;
      # tbody
      $tbody = $result;
      $dblabels = $this->get_dbcolumns_labels();
      $dblabels['condName'] = 'Name';
      $dblabels['condValue'] = 'Definitions';
      return array(
        'thead' => $thead,
        'tbody' => $tbody,
        'dblabels' => $dblabels
      );
    }
  }

  public function get_filter_master($argobj){
    $lang    = @$argobj['lang'];
    $gnam    = @$argobj['pattern'];
    $collang = ($lang != "English") ? $lang : "";
    $gnam    = str_replace("'", "", $gnam);
    $query = "SELECT "
             ."bigCateId,"
             ."smlCateId,"
             ."essParaName,"
             ."type,"
             ."bigCateLabel".$collang.","
             ."smlCateLabel".$collang." "
         ." FROM ".$this->TABLE_CALC_MASTER
         ." WHERE groupName='".$gnam."' "
         ." ORDER BY bigCateId,smlCateId ASC";
    $result = $this->getdata_mysql($query, 'arr');

    $bigids = array();
    $rslarr = array();
    foreach ($result as $idx => $row){
      $bigid   = $row[0];
      $smlid   = $row[1];
      $epara   = $row[2];
      $type    = $row[3];
      $biglb   = $row[4];
      $smllb   = $row[5];
      if(!in_array($bigid, $bigids)){
        array_push($bigids, $bigid);
        if(isset($tobj)){
          array_push($rslarr, $tobj);
        }
        $tobj = array(
          'id'     => $bigid,
          'name'   => $biglb,
          'smalls' => array()
        );
      }
      array_push($tobj['smalls'], array(
        'id'     => $smlid,
        'name'   => $smllb,
        'esspara'=> $epara,
        'type'   => $type
      ));
    }
    if(isset($tobj)){
      array_push($rslarr, $tobj);
    }
    return $rslarr;
  
  }

  public function get_filter_users($argobj, $showpattern){
    
    $uid   = @$argobj['uid'];
    $lid   = @$argobj['lid'];
$lid = 1;
    $essp  = @$argobj['esspara'];
    $gnam  = @$argobj['pattern'];
    $setid = isset($argobj['setid']) ? $argobj['setid'] : -1;

    $COLS = ($showpattern != 'main') 
         ? array('ID', 'setId','setName','setActiveFlg','condId','condActiveFlg','condName', 'condSelection', 'condValue', 'memo') 
         : array("GROUP_CONCAT(ID separator ',') AS id", 'setId','setName','setActiveFlg','condId','condActiveFlg','condName', 'condSelection', 'condValue', 'memo');
    $WHRQ = ($showpattern == 'popup') ? ' AND setId='.$setid.' ' : '';
    $GRPQ = ($showpattern == 'main' ) ? ' GROUP BY setId,essParaName ' : '';
    
    $gnam = str_replace("'", "", $gnam);
    $query = "SELECT ".implode(',', $COLS)
           ." FROM ".$this->TABLE_CALC_USER
           ." WHERE "
           ." custnoLogin=".$lid
           ." AND custnoView='".$uid."'"
           ." AND essParaName='".$essp."'"
           ." AND groupName='".$gnam."' ".$WHRQ.$GRPQ." ORDER BY setId,condId";
    $result = $this->getdata_mysql($query, 'dict');
    $maxsetid = 0;
    $rtnobj = array();
    foreach($result as $idx => $row){
      $newrow = array();
      foreach($row as $key => $col){
        $newrow[$this->dbcol_to_nickname($key)] = $col;
      }
      $newrow['rowIsShow'] = True;
      array_push($rtnobj, $newrow);
      $maxsetid = (@$row['setId'] > $maxsetid) ? @$row['setId'] : $maxsetid;
    }


    return array(
      'usselect' => $rtnobj,
      'maxsetid' => $maxsetid,
      'dbcolmns' => $this->transform_dbcolmns_nicknames($COLS),
      'initlobj' => $this->initial_obj_usertable($COLS, $showpattern, $maxsetid),
      'dblabels' => $this->get_dbcolumns_labels()
    );

  }
  private function get_dbcolumns_labels(){
    return $this->LABELS_USERS;
  }
  private function transform_dbcolmns_nicknames($cols){
    $newcols = array();
    foreach($cols as $idx => $col){
      $col = $this->dbcol_to_nickname($col);
      if(strstr($col, 'as')){
        $col_tmp = explode('as', $col);
        $col     = str_replace(' ','',$col_tmp[1]);
      }
      array_push($newcols, $col);
    }
    return $newcols;
  }
  private function initial_obj_usertable($COLS, $showpattern, $maxsetid){
    $initlobj = array();
    foreach($COLS as $idx => $colm){
      $colm = $this->dbcol_to_nickname($colm);
      $inival = '';
      switch ($colm){
        case 'setid':
          $inival = $maxsetid;
          break;
        case 'condid':
          $inival = 1;
          break;
        case 'condactiveflg':
          $inival = 0;
          break;
        case 'setactiveflg':
          $inival = ($showpattern=='main') ? 0 : -1;
          break;
      }
      $initlobj[$colm] = $inival;
    }
    return array($initlobj);
  }

  public function return_filteroptions_suggestlist($argobj){
$lid=@$argobj['lid'];
$lid=1;
    $pttrn = str_replace("'","", @$argobj['pattern']);
    $query = "SELECT condName,condValue"
           ." FROM ".$this->TABLE_CALC_USER
           ." WHERE "
             ." custnoLogin=".$lid
             ." AND custnoView='".@$argobj['uid']."'"
             ." AND essParaName='".@$argobj['esspara']."'"
             ." AND groupName='".$pttrn."'";
    $data = $this->getdata_mysql($query, 'arr');
    $result = array();
    foreach($data as $idx => $row){
      if(!in_array($row[1], $result)){
        array_push($result, $row[1]);
      }
    }

    return $result;
  }
  public function return_helps($lang){
    #  define target data label
    $get_data_arr = array('type','selector','pos_x','pos_y','is_active', 'title','text');
    if($lang == 'Japanese'){
      $get_data_arr = array('type','selector','pos_x','pos_y','is_active', 'title_jap','text_jap');
    }
    $get_data_str = implode(',', $get_data_arr);
    
    #  define query
    $query = "SELECT ".$get_data_str." FROM Help WHERE is_active=1 ORDER BY id ASC";
    $data  = $this->getdata_mysql($query, 'dict');
    $result = array();
    foreach($data as $idx => $row){
      $row['isActivate'] = @$row['isActivate'] == 1;
      $row['isVisible']  = @$row['isVisible'] == 1;
      $row['offsetX']    = (int) @$row['offsetX'];
      $row['offsetY']    = (int) @$row['offsetY'];
      if($lang == 'Japanese'){
        $row['title'] = @$row['title_jap'];
        $row['text']  = @$row['text_jap'];
      }
      if(!isset($result[@$row['type']])){
        $result[@$row['type']] = array();
      }
      array_push($result[@$row['type']], $row);
    }   
    return $result;

  }
}

class MysqlSaveCondition extends MysqlDb{
  public function add_filter($argobj, $obj){
    $uid  = @$argobj['uid'];
    $lid  = @$argobj['lid'];
    $table = (@$argobj['pattern'] == 'options') ? $this->TABLE_FILTER_PARTS : $this->TABLE_CALC_USER;
$lid=1;
    $mysqlval = array();
    $values = @$obj['values'];
    foreach($values as $idx => $value){
      $value = mysql_escape_string($value);
      array_push($mysqlval, "'$value'");
    }
    $mysqlnames  = implode(',', @$obj['names']);
    $mysqlvalues = implode(',', $mysqlval);
    $query = "INSERT INTO $table ($mysqlnames) VALUES($mysqlvalues)";
    $this->commit_queries(array($query));
  }
  public function change_filter($argobj, $obj){
    $uid  = @$argobj['uid'];
    $lid  = @$argobj['lid'];
$lid=1;
    $table = (@$argobj['pattern'] == 'options') ? $this->TABLE_FILTER_PARTS : $this->TABLE_CALC_USER;
    $id   = @$obj['id'];
    $sets = array();
    $values = @$obj['values'];
    foreach(@$obj['names'] as $idx => $name){
      $value = mysql_escape_string($values[$idx]);
      array_push($sets, "$name='$value'");
    }
    $set = implode(',', $sets);
    $query = "UPDATE $table SET $set WHERE ID in ($id)";
    $this->commit_queries(array($query));

  }
  public function delete_filter($argobj, $obj){
    $uid  = @$argobj['uid'];
    $lid  = @$argobj['lid'];
$lid=1;
    $query = '';
    $table = (@$argobj['pattern'] == 'options') ? $this->TABLE_FILTER_PARTS : $this->TABLE_CALC_USER;
    if(isset($obj['setid'])){
      $setid  = $obj['setid'];
      $esspara = @$argobj['type'];
      $groupname = @$argobj['pattern'];
      $query = "DELETE FROM $table WHERE custnoLogin=$lid AND custnoView='$uid' AND setid = $setid AND essParaName='$esspara' AND groupName='$groupname'";
#error_log($query, 0);
    }
    if(isset($obj['ids'])){
      $ids  = $obj['ids'];
      $ids  = implode(',', $ids);
      $query = "DELETE FROM $table WHERE id in ($ids)";
#error_log($query, 0);
    }
    if($query){
      $this->commit_queries(array($query));
    }

  }
}

class MysqlCacheList extends MysqlDb{
  public function get_cache_list_for_options($uid, $lid){
$lid = 1;
    $query = "SELECT * FROM ".$this->TABLE_CACHELIST." WHERE custnoLogin=$lid AND custnoView='$uid' AND isactive=1 AND deleted_at IS NULL";
    return $this->getdata_mysql($query, 'dict');
  }
}

?>
