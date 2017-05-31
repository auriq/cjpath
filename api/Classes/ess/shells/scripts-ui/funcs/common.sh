#!/bin/bash

returnUsersWorkspaceDir(){
  #echo ${ESS_DIR}
  echo ${ESS_DIR_USER}
}

returnESSRootDir(){
  echo ${ESS_ROOT}
}

returnUsersConfig(){
  local vartype=$1
  goToESS
  if [ "$vartype" = 'charcode' ]; then
    [ ! -z "$GUI_CHARCODE" ] && echo "$GUI_CHARCODE" || echo 'UTF-8' # UTF-8 / SJIS
  elif [ "$vartype" = 'timezone' ]; then
    [ ! -z "$GUI_TIMEZONE" ] && echo "$GUI_TIMEZONE" || echo 'GMT'
  elif [ "$vartype" = 'logtypeforview' ]; then
    [ ! -z "$GUI_SHOW_LOGTYPE" ] && echo "$GUI_SHOW_LOGTYPE" || echo 'Adv:Web'
  fi
}

returnCachedDirName(){
  # define this function if you want to customized one
  local sdate=${1}
  local edate=${2}
  local sampling=${3}
  local bdays=${4}
  local cpmemo=${5}
  [ -n "$cpmemo" ] && "-${cpmemo}"
  goToESS
  #local name=${NAME}
  local name=${uid}
  local s3path=${CACHEDIR}
  local CACHE="${s3path}/${name}-${sdate}-${edate}-${bdays}-${sampling}${cpmemo}"
  [ "${sdate}" != '' -a "${edate}" != '' -a "${bdays}" != '' -a "${sampling}" != '' ] && echo ${CACHE} > ${ESS_CACHE_DIRNAME}
  echo ${CACHE}
}
getCurrCachedDirPath(){
  [ -e $ESS_CACHE_DIRNAME ] && CACHE=`cat ${ESS_CACHE_DIRNAME}` || CACHE=''
  echo ${CACHE}
}
setCachedDirPath(){
  [ ! -z "$1" ] && echo $1 > ${ESS_CACHE_DIRNAME}
}

getCacheListFromS3(){
  goToESS
  if [ "$REPOSITORY" = 'local' ]; then
    list=$(ls -1 ${CACHEDIR})
  else
    list=$(AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY} AWS_SECRET_ACCESS_KEY=${AWS_SECRET_KEY} aws s3 ls s3://${REPOSITORY}${CACHEDIR}/ | awk -F'PRE ' '{print $2}' | sed -e 's/\///g')
    [ "${AWS_ACCESS_KEY}" = '' -o "${AWS_SECRET_KEY}" = '' ] && list=`ess ls ${CACHEDIR} | awk -F'${${CACHEDIR}}/' '{print $2}' | sed -e 's/\///g'`
  fi

  echo $list
}


getCacheFilesFromRepository(){
  local cdir=${1}
  goToESS
  if [ "$REPOSITORY" = 'local' ]; then
    list=$(ls -1 ${CACHEDIR}/${cdir})
  else
    list=$(AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY} AWS_SECRET_ACCESS_KEY=${AWS_SECRET_KEY} aws s3 ls s3://${REPOSITORY}${CACHEDIR}/${cdir}/ | awk -F' ' '{print $4}')
  fi

  echo $list
}
removeCacheFilesFromRepository(){
  local params=$1
  local cdir=`echo $params | awk -F':' '{print $1}'`     # target cache directory
  local cleartype=`echo $params | awk -F':' '{print $2}'` # 'withoutcj' or 'all'
  goToESS
  if [ "$REPOSITORY" = 'local' ]; then
    [ "$cleartype" = "withoutcj" ] && rfilepattern='*[!csv].gz' || local rfilepattern='*.gz'
    rm -f ${CACHEDIR}/${cdir}/$rfilepattern
  else
    local excludepattern=''
    [ "$cleartype" = "withoutcj" ] && local excludepattern="--exclude "*.csv.gz""
    list=$(AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY} AWS_SECRET_ACCESS_KEY=${AWS_SECRET_KEY} aws s3 rm s3://${REPOSITORY}${CACHEDIR}/${cdir}/ ${excludepattern} --recursive) 
  fi
  echo $?
}


getMysqlBackupListS3(){
  goToESS
  if [ "$REPOSITORY" = 'local' ]; then
    [ ! -e ${CACHEDIR}/../mysql-backups ] && mkdir -p ${CACHEDIR}/../mysql-backups
    list=`ls -1 -l --time-style="long-iso" ${CACHEDIR}/../mysql-backups | awk -F' ' '{print $6,$7,$5,$8}' | sed 's/ /@/g'`
  else
    s3dir=`dirname s3://${REPOSITORY}${CACHEDIR}`"/mysql-backups/"
    list=$(AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY} AWS_SECRET_ACCESS_KEY=${AWS_SECRET_KEY} aws s3 ls $s3dir | tr -s ' ' | sed 's/ /@/g') 
  fi
  echo $list
}

uploadMysqlBackupToS3(){
  local fpath=$1
  goToESS
  if [ "$REPOSITORY" = 'local' ]; then
    [ ! -e ${CACHEDIR}/../mysql-backups ] && mkdir -p ${CACHEDIR}/../mysql-backups
    mv $fpath $CACHEDIR/../mysql-backups
  else
    s3dir=`dirname s3://${REPOSITORY}${CACHEDIR}`"/mysql-backups/"
    export AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY}
    export AWS_SECRET_ACCESS_KEY=${AWS_SECRET_KEY}
    [ -e "$fpath" ] && { aws s3 mv $fpath $s3dir; echo $?; } || { echo 1; }
  fi
}

downloadMysqlBackupFromS3(){
  local fpath=$1
  local fname=`basename $fpath`
  goToESS
  if [ "$REPOSITORY" = 'local' ]; then
    [ ! -e ${CACHEDIR}/../mysql-backups ] && mkdir -p ${CACHEDIR}/../mysql-backups
    cp $CACHEDIR/../mysql-backups/$fname $fpath
    echo $?
  else
    s3dir=`dirname s3://${REPOSITORY}${CACHEDIR}`"/mysql-backups/"
    export AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY}
    export AWS_SECRET_ACCESS_KEY=${AWS_SECRET_KEY}
    aws s3 cp $s3dir$fname $fpath
    echo $?
  fi
}

getPeriodFilter(){
  local sdate=$1
  local edate=$2
  local TIMEZONE=$3
  if [ -z ${sdate} -o -z ${edate} ]; then
    echo ""
  else
    if [ -n "$TIMEZONE" ]; then
      local sdate_t=$(eval $TIMEZONE export TZ; date -d ${sdate} +%s)
      local edate_t=$(eval $TIMEZONE export TZ; date -d "${edate} 1 day" +%s)
    else
      local sdate_t=$(date -d ${sdate} +%s)
      local edate_t=$(date -d "${edate} 1 day" +%s)
    fi
    filter=" -filt 't>=${sdate_t} && t<${edate_t}' "
    echo "${filter}"
  fi
}

getUsingMemory(){
  if [ ! -e ${LOCK_ESS_SETUP} -a ! -e ${LOCK_ESS_IMPORTING} -a -e ${ESS_DIR} ]; then
    goToESS
    mems=`ess udbd ckmem | grep Total | awk -F'Total:' '{print $2}' | sed 's/KB\| //g' | sed 's/of/,/' | awk -F',' '{sumuse+=$1} {sumttl+=$2} END {print sumuse,sumttl}'`
    memttl=`echo "$mems" | awk -F' ' '{print $2}'`
    memuse=`echo "$mems" | awk -F' ' '{print $1}'`

    [ -e ${ESS_INSTANCE_TYPE} ] && inttype=`cat ${ESS_INSTANCE_TYPE}` || inttype=''
    [ -e ${ESS_INSTANCE_NUM} ] && intnum=`cat ${ESS_INSTANCE_NUM}` || inttype=''
  
    [ "${inttype}" = "local" ] && localFlg=1 || localFlg=0
    #if [ ${localFlg} = 1 ]; then
    #  inttype=`wget -q -O - http://169.254.169.254/latest/meta-data/instance-type`
    #  intnum='MASTER'
    #fi
  
    echo ${memttl}','${memuse}','${inttype}','${intnum}
  else
    echo ',,,'
  fi
}

getUdbdStatus(){
  goToESS
  echo `ess udbd status`
}

goToESS(){
  cd ${ESS_DIR_USER}
  [ -e ${ESS_DIR_USER}/usrconfig.inc ] && source ${ESS_DIR_USER}/usrconfig.inc
  [ -e ${ESS_DIR_COMMON}/eh.inc ] && source ${ESS_DIR_COMMON}/eh.inc
  export PATH=/usr/local/bin:/usr/local/lib/ess/bin/aq_tool/bin:$PATH
  export ESS_CACHE_DIR=/var/www/html/mydmp/aws/1/.ess
  export ESS_AWS_DIR=/var/www/html/mydmp/aws/1/.aws
  export GUI_CHARCODE=$GUI_CHARCODE
  export GUI_TIMEZONE=$GUI_TIMEZONE
  export GUI_SHOW_LOGTYPE=$GUI_SHOW_LOGTYPE
}

checkUdbdAlive(){
  [ ! -e "$ESS_INSTANCE_TYPE" ] && { echo 0; exit; }
  if [ `cat "$ESS_INSTANCE_TYPE"` = local ]; then
    # if it's runnign at local...
    [[ `ps aux | grep udbd | grep -v grep` ]] && local checkifalive=1 || local checkifalive=0
    # could be checked with `ess udbd status` as cluster case, but it's slow.
  else
    # if it's running with clusters
    goToESS
    notrunning=`ess udbd status 2>&1 | grep -c 'not running'`
    [ "$notrunning" -lt 0 ] && local checkifalive=1 || local checkifalive=0
  fi


  # If it's no alive, then delete local cache file.
  if [ ${checkifalive} = 0 ]; then
    goToESS
    cd ${APACHE_DIR}
    rm -f STARTTIME*
    rm -f IMP*
    rm -f LOCK*
    rm -f USERASCOOKIE
    [ -e ${LOCAL_MASTER_MANAGE_DIR}/userascookie ] && rm -f ${LOCAL_MASTER_MANAGE_DIR}/userascookie
  fi
  echo "${checkifalive}"
}

getUsersMaxPrevious(){
  goToESS
  [ "${MAXPREVIOUS}" = '' ] && local mprev=90 || local mprev=${MAXPREVIOUS}
  echo $mprev
}


