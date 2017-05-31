#!/bin/bash
DIR_CURR=$(cd $(dirname $0); pwd)
DIR_CURR=$(cd $(dirname ${BASH_SOURCE:-$0}); pwd)

if [ $# -ne 3 ]; then
  echo 'Invalid Parameter Number.'
  echo 'e.x.) bash generate_new_customer.sh viewid loginid'
  exit
fi

viewid=$1
#loginid=$2
loginid=1
datapath=$3

#DATADIR='../schema/data/'
#DATADIR='/var/www/html/apiaqlog/lib/schema/data/customers/'

#MYSQL_DB='AQLogCVPathSettings'
MYSQL_DB='CVPathSettings'
MYSQL_TABLE='CalcSettingUser'
MYSQL_USER='essentia'
MYSQL_PASS='dzc7xze6DUAQCada'
MYSQL_CHAR='utf8'

mysql -u ${MYSQL_USER} -p${MYSQL_PASS} ${MYSQL_DB} -A << EOF
use ${MYSQL_DB};

DROP TABLE IF EXISTS CalcSettingMaster;
CREATE TABLE CalcSettingMaster(
  id   int(11)         NOT NULL AUTO_INCREMENT PRIMARY KEY,
  groupName            varchar(255) NOT NULL,
  bigCateId            int(11)      NOT NULL,
  smlCateId            int(11),
  essParaName          varchar(500) NOT NULL,
  type                 varchar(255) NOT NULL,
  bigCateLabel         varchar(500) NOT NULL,
  bigCateLabelJapanese varchar(500) NOT NULL,
  smlCateLabel         varchar(500),
  smlCateLabelJapanese varchar(500),
  UNIQUE(groupName, bigCateId, smlCateId)
) CHARACTER SET utf8;

SET character_set_database=utf8;
LOAD DATA LOCAL INFILE "${DIR_CURR}/data/master.csv" INTO TABLE CalcSettingMaster
FIELDS TERMINATED BY ',' ENCLOSED BY '"' IGNORE 1 LINES;


DROP TABLE IF EXISTS Colors;
CREATE TABLE Colors(
  id    int(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  color varchar(255) NOT NULL,
  cvflg int(11)      NOT NULL,
  UNIQUE(color)
) CHARACTER SET utf8;

SET character_set_database=utf8;
LOAD DATA LOCAL INFILE "${DIR_CURR}/data/colors.csv" INTO TABLE Colors
FIELDS TERMINATED BY ',' ENCLOSED BY '"' IGNORE 1 LINES (color, cvflg);


DROP TABLE IF EXISTS Icons;
CREATE TABLE Icons(
  id   int(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) NOT NULL,
  dval varchar(1000) NOT NULL,
  UNIQUE(name)
) CHARACTER SET utf8;

SET character_set_database=utf8;
LOAD DATA LOCAL INFILE "${DIR_CURR}/data/icons.csv" INTO TABLE Icons
FIELDS TERMINATED BY ',' ENCLOSED BY '"' IGNORE 1 LINES (name, dval);



DROP TABLE IF EXISTS SymbolsSetting;
CREATE TABLE SymbolsSetting(
  id         int(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  symbolname varchar(255) NOT NULL,
  color      varchar(255) NOT NULL,
  icon       varchar(500) NOT NULL,
  UNIQUE(symbolname)
) CHARACTER SET utf8;

SET character_set_database=utf8;
LOAD DATA LOCAL INFILE "${DIR_CURR}/data/symbols.csv" INTO TABLE SymbolsSetting
FIELDS TERMINATED BY ',' ENCLOSED BY '"' IGNORE 1 LINES (symbolname, color, icon);


DROP TABLE IF EXISTS Help;
CREATE TABLE Help(
        id              int(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
        type            varchar(20) NOT NULL,
        selector        varchar(64) NOT NULL,
        pos_x           varchar(64) NOT NULL,
        pos_y           varchar(64) NOT NULL,
        is_active       tinyint(1) NOT NULL,
        title           varchar(64) NOT NULL,
        text            TEXT(1024) NOT NULL,
        title_jap       varchar(64) NOT NULL,
        text_jap        TEXT(1024) NOT NULL
) CHARACTER SET utf8;

SET character_set_database=utf8;
LOAD DATA LOCAL INFILE "${DIR_CURR}/data/help.csv" INTO TABLE Help
FIELDS TERMINATED BY ',' ENCLOSED BY '"' IGNORE 1 LINES;


DROP TABLE IF EXISTS CalcSettingUser;
CREATE TABLE CalcSettingUser(
  id             int(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  groupName      varchar(255) NOT NULL,
  custnoLogin    int(11)      NOT NULL,
  custnoView     varchar(255) NOT NULL,
  essParaName    varchar(500) NOT NULL,
  setId          int(11),
  setName        varchar(500),
  setActiveFlg   int(11),
  condId         int(11),
  condActiveFlg  int(11),
  condName       varchar(500),
  condSelection  text,
  condValue      text,
  memo           text
) CHARACTER SET utf8;


#SET character_set_database=${MYSQL_CHAR};
#DELETE FROM ${MYSQL_TABLE} WHERE custnoView="${viewid}" AND custnoLogin=${loginid};
#LOAD DATA LOCAL INFILE "${datapath}" INTO TABLE ${MYSQL_TABLE} 
#FIELDS TERMINATED BY ',' ENCLOSED BY '"' IGNORE 1 LINES 
#(groupName,@dummy,custnoView,essParaName,setId,setName,setActiveFlg,condId,condActiveFlg,condName,condValue) 
#SET custnoLogin=${loginid};

DROP TABLE IF EXISTS CalcSettingOptionParts;
CREATE TABLE CalcSettingOptionParts(
  id             int(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  custnoLogin    int(11)      NOT NULL,
  custnoView     varchar(255) NOT NULL,
  essParaName    varchar(500) NOT NULL,
  condName       varchar(500),
  condValue      text,
  memo           text,
  deletedAt      datetime DEFAULT NULL
) CHARACTER SET utf8;


DROP TABLE IF EXISTS CacheBatchList;
CREATE TABLE CacheBatchList(
  id                  int(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  custnoLogin    int(11)      NOT NULL,
  custnoView     varchar(255) NOT NULL,
  sdate               varchar(255) NOT NULL,
  edate               varchar(255) NOT NULL,
  bdays               varchar(255) NOT NULL,
  sample              varchar(255) NOT NULL,
  fname               varchar(255) NOT NULL,
  comment             varchar(255) NOT NULL,
  status              varchar(255) NOT NULL,
  deleted_at          varchar(255) NOT NULL,
  modified_at         varchar(255) NOT NULL,
  timestamp_done      varchar(255) NOT NULL,
  timestamp_requested varchar(255) NOT NULL
) CHARACTER SET utf8;


DROP TABLE IF EXISTS CacheList;
CREATE TABLE CacheList(
  id             int(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  custnoLogin    int(11)      NOT NULL,
  custnoView     varchar(255) NOT NULL,
  cachedirname   varchar(255) NOT NULL,
  type           varchar(255),
  label          varchar(255),
  start_date     varchar(255),
  end_date       varchar(255),
  prev_days      varchar(255),
  sample         varchar(255),
  memo           text,
  isactive       int(11)      NOT NULL,
  deleted_at     varchar(255)
) CHARACTER SET utf8;


EOF
#LOAD DATA LOCAL INFILE "${DATADIR}${viewid}.csv" INTO TABLE ${MYSQL_TABLE} 

echo 'done'

exit
