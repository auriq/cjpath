<?php

$ISDEV = True;

##---------------------------
##       Time zone settings
##---------------------------
#$TIME_ZONE  = 'Asia/Tokyo';
#$OUT_FILT_CHARCODE = 'UTF-8';

#---------------------------
#  CORS ALLOW "Access-Control-Allow-Origin"
#   (beside "myself:9000"
#---------------------------
$OKORIGINS = 'http://*.auriq.com';

#---------------------------
#  DB (MySQL) Connection
#---------------------------
$DB_DBNAME   = 'CVPathSettings';
$DB_HOSTNAME = 'localhost';
$DB_USERNAME = 'essentia';
$DB_PASSWORD = 'dzc7xze6DUAQCada';
$DB_CHARCODE = 'utf8';


#---------------------------
#  Parameter check
#---------------------------
$PARA_CHECK_CONFG_COMMON = array(
    'uid' => array(
      'Null' => 'NO',
      'Type' => 'string'
    ),
    'type' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'callback' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'samp' => array(
      'Null' => 'YES',
      'Type' => 'boolean',
      'Default' => False
    ),
    'tabletype' => array(
      'Null'    => 'YES',
      'Type'    => 'boolean',
      'Default' => False
    )
);
$PARA_CHECK_CONFG = array(
        'all'  => array(),
  'path' => array(
    'lid' => array(
      'Null' => 'NO',
      'Type' => 'number'
    ),
    'type' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'global' => array(
      'Null' => 'YES',
      'Type' => 'object',
      'Default' => array()
    ),
    'filter' => array(
      'Null' => 'YES',
      'Type' => 'object',
      'Default' => array()
    ),
    'options' => array(
      'Null' => 'YES',
      'Type' => 'object',
      'Default' => array()
    ),
    'paging' => array(
      'Null' => 'YES',
      'Type' => 'object',
      'Default' => array()
    ),
    'sdate' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'edate' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'segment' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'tptype' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => 'SiteName'
    ),
    'filter' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'tpdepth' => array(
      'Null' => 'YES',
      'Type' => 'number',
      'Default' => '1'
    ),
    'fldepth' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => 'all'
    ),
    'dist' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => 'depth'
    ),
    'csvflg' => array(
      'Null' => 'YES',
      'Type' => 'boolean',
      'Default' => False
    )
  ),
  'path_readdata' => array(
    'lid' => array(
      'Null' => 'NO',
      'Type' => 'number'
    ),
    'cmdtype' => array(
      'Null' => 'NO',
      'Type' => 'string'
    ),
    'sdate' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'edate' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'bdays' => array(
      'Null' => 'YES',
      'Type' => 'number'
    ),
    'sampling' => array(
      'Null' => 'YES',
      'Type' => 'number'
    ),
    'cachedir' => array(
      'Null' => 'NO',
      'Type' => 'string'
    ),
    'cpmemo' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'redoflg' => array(
      'Null' => 'YES',
      'Type' => 'number',
      'Default' => '0'
    ),
    'pathord' => array(
      'Null' => 'YES',
      'Type' => 'number',
      'Default' => '100'
    ),
    'withoroutcv' => array(
      'Null' => 'YES',
      'Type' => 'number',
      'Default' => '1'
    ),
    'tocv' => array(
      'Null' => 'YES',
      'Type' => 'number',
      'Default' => '1'
    ),
    'custom' => array(
      'Null' => 'YES',
      'Type' => 'object',
      'Default' => '{arr:[]}'
    )
  ),
  'path_filter_detail' => array(
    'type' => array(
      'Null' => 'NO',
      'Type' => 'string'
    ),
    'lid' => array(
      'Null' => 'NO',
      'Type' => 'number'
    ),
    'pattern' => array(
      'Null' => 'NO',
      'Type' => 'string'
    ),
    'esspara' => array(
      'Null' => 'NO',
      'Type' => 'string'
    ),
    'setid' => array(
      'Null' => 'YES',
      'Type' => 'number',
      'Default' => -1
    ),
    'popupmode' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    )
  ),
  'path_save' => array(
    'lid' => array(
      'Null' => 'NO',
      'Type' => 'number'
    ),
    'type' => array(
      'Null' => 'NO',
      'Type' => 'string'
    ),
    'filterSelection' => array(
      'Null' => 'YES',
      'Type' => 'object',
      'Default' => '{}'
    ),
    'pattern' => array(
      'Null' => 'NO',
      'Type' => 'string'
    )
  ),
  'path_detail' => array(
    'lid' => array(
      'Null' => 'NO',
      'Type' => 'number'
    ),
    'opttype' => array( # for getOptions
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'dtype' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => 'lineChart'
    ),
    'ddepth' => array(
      'Null' => 'YES',
      'Type' => 'number',
      'Default' => '100'
    ),
    'ctype' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => 'abs'
    ),
    'paths' => array(
      'Null' => 'YES',
      'Type' => 'object',
      'Default' => '{}'
    ),
    'topx' => array(
      'Null' => 'YES',
      'Type' => 'number',
      'Default' => 10
    ),
    'colms' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => 's,k:campaignname x x x x'
    ),
    'filter' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'colmswtfilter' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'attrmodel' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => 'last_click'
    ),
    'intrt' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'logtype' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'sdate' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'edate' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'countType' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'bintype' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => '%F %H'
                ),
    'detailKey' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'xAxisType' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'yAxisType' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'tmpl' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => ''
    ),
    'showPreviousPeriod' => array(
      'Null' => 'YES',
      'Type' => 'number',
      'Default' => 0
    ),
    'cvusrflg' => array(
      'Null' => 'YES',
      'Type' => 'number',
      'Default' => -1
    ),
    'csvflg' => array(
      'Null' => 'YES',
      'Type' => 'boolean',
      'Default' => False
    ),
    'paging' => array(
      'Null' => 'YES',
      'Type' => 'object',
      'Default' => array()
    ),
    'tpdepth' => array(
      'Null' => 'YES',
      'Type' => 'number',
      'Default' => '1'
    ),
    'fldepth' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => 'all'
    ),
    'dist' => array(
      'Null' => 'YES',
      'Type' => 'string',
      'Default' => 'profile.days'
    ),
    'redoflg' => array(
      'Null' => 'YES',
      'Type' => 'number',
      'Default' => '0'
    )
  ),
  'filemanipulation' => array(
    'lid' => array(
      'Null' => 'NO',
      'Type' => 'number'
    ),
    'ftype' => array(
      'Null' => 'NO',
      'Type' => 'string'
    ),
    'mtype' => array(
      'Null' => 'NO',
      'Type' => 'string'
    )
  )
);

foreach($PARA_CHECK_CONFG as $key => $value){
  $PARA_CHECK_CONFG[$key] = array_merge($value, $PARA_CHECK_CONFG_COMMON);

}

?>
