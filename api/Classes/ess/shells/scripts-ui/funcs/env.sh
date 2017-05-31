#!/bin/bash
DIR_CURR=$(cd $(dirname $0); pwd)
MYPATH=$(cd $(dirname ${BASH_SOURCE:-$0}); pwd)
source ${MYPATH}/common.sh

#--------------------
#      FUNCTIONS
#--------------------
setup(){
  local instanceNum=$1
  local instanceType=$2

  deffiles

  [ ! -e ${APACHE_DIR} ] && mkdir -p ${APACHE_DIR}

  [ -e $LOCK_ESS_SETUP ] && { echo 'done'; exit; }

  touch ${LOCK_ESS_SETUP}
  echo 'creating' > ${ESS_INSTANCE_STATUS}
  [ ! -e ${LOCAL_MASTER_MANAGE_DIR} ] && mkdir -p ${LOCAL_MASTER_MANAGE_DIR}
  goToESS

  quitMySelf # sometimes there are still some status files and it doet not start from "import" and profile

  [ "${instanceType}" = 'local' ] && echo "${USERCOOKIE}${COOKIE_DELIM}${uid}${COOKIE_DELIM}${lid}" > ${LOCAL_MASTER_STATUS}
  echo "${USERCOOKIE}${COOKIE_DELIM}${uid}${COOKIE_DELIM}${lid}" > ${ESS_USER_STATUS}

  echo 'START INSTANCE' 1>&2
  echo ${instanceType} > ${ESS_INSTANCE_TYPE}
  echo ${instanceNum}  > ${ESS_INSTANCE_NUM}
  bash ${ESS_SET_INSTANCE} ${instanceType} ${instanceNum}

  # Check if datastore is set and run setup.sh if it is not.
  ess summary >/dev/null 2>&1; ret=$?
  if [ "$ret" != 0  -a ! -z "$ESS_DATASTORE" ]; then
    echo 'DATASTORE SETTING ' 1>&2
    bash ${ESS_DATASTORE} >/dev/null 2>&1
  fi
  
  echo 'CREATE DB ' 1>&2
  bash ${ESS_CREATEDB} >/dev/null 2>&1
  touch ${ESS_INSTANCE_STATUS}
  touch ${ESS_INSTANCE_START_TIME}

  rm -f ${LOCK_ESS_SETUP}

  echo 'created' > ${ESS_INSTANCE_STATUS}
  echo 'ready' > ${ESS_IMPORT_STATUS}

  echo 'done'
}

terminateInstance(){

    echo 'terminating' > ${ESS_INSTANCE_STATUS}
  
    [ -e ${ESS_INSTANCE_TYPE} ] && inttype=`cat ${ESS_INSTANCE_TYPE}` || inttype=''
  
  
    goToESS
    bash ${ESS_TERMINATE_INSTANCE} ${inttype}
  
    cd ${APACHE_DIR}
    rm -f STARTTIME*
    rm -f IMP*
    rm -f LOCK*
    [ -e CACHEDIRNAME ] && rm -f CACHEDIRNAME
    [ ! -e ${LOCAL_MASTER_MANAGE_DIR} ] && mkdir -p ${LOCAL_MASTER_MANAGE_DIR}
    [ "${inttype}" = 'local' -a -e $LOCAL_MASTER_STATUS ] && echo '' > ${LOCAL_MASTER_STATUS}
  
    echo 'terminated' > ${ESS_INSTANCE_STATUS}
    echo '' > ${ESS_USER_STATUS}
  
    echo 'terminated'
}

quitMySelf(){
  echo '^^^^ quitMySelf ^^^^^' 1>&2
  terminateInstance
  echo 'done' > ${ESS_IMPORT_STATUS}
}

terminateMyselfByOtherUser(){
  local nextssid=${1}
  local nextuid=${2}
  local nextlid=${3}
  [ -e ${ESS_INSTANCE_TYPE} ] && inttype=`cat ${ESS_INSTANCE_TYPE}` || inttype=''
  [ ! -e ${LOCAL_MASTER_MANAGE_DIR} ] && mkdir -p ${LOCAL_MASTER_MANAGE_DIR}
  [ "${inttype}" = 'local' ] && echo  "${nextssid}${COOKIE_DELIM}${nextuid}${COOKIE_DELIM}${nextlid}" > ${LOCAL_MASTER_STATUS}
  [ -e ${ESS_USER_STATUS} ] && echo "${nextssid}${COOKIE_DELIM}${nextuid}${COOKIE_DELIM}${nextlid}" > ${ESS_USER_STATUS}
}

terminateOther(){
  local nextssid=${1}
  local nextuid=${2}
  local nextlid=${3}
  [ -e ${ESS_INSTANCE_TYPE} ] && inttype=`cat ${ESS_INSTANCE_TYPE}` || inttype=''
  [ ! -e ${LOCAL_MASTER_MANAGE_DIR} ] && mkdir -p ${LOCAL_MASTER_MANAGE_DIR}
  [ "${inttype}" = 'local' ] && echo  "${nextssid}${COOKIE_DELIM}${nextuid}${COOKIE_DELIM}${nextlid}" > ${LOCAL_MASTER_STATUS}
  [ -e ${ESS_USER_STATUS} ] && echo "${nextssid}${COOKIE_DELIM}${nextuid}${COOKIE_DELIM}${nextlid}" > ${ESS_USER_STATUS}
}

checkIfImported(){
  local result=0
  [ -e ${ESS_IMPORTED_PERIOD_F} -a  -e ${ESS_IMPORTED_PERIOD_T} ] && result=1 || result=0
  echo ${result}
}
checkDiffCachedir(){
  local cachedir=$1
  local readcache=`getCurrCachedDirPath`
  [ "$cachedir" != "$readcache" ] && local diffFlg=1 || local diffFlg=0

  [ "$diffFlg" = 1 ] && setCachedDirPath $cachedir

  echo $diffFlg
}
checkDiffPeriods(){
  local sdate=${1}
  local edate=${2}
  local bdays=${3}
  local sampl=${4}
  local memo=${5}
  local prev_period_f=''
  local prev_period_t=''
  local prev_bdays=''
  local prev_sampl=''
  local prev_memo=''
  local result=0
  [ -e ${ESS_IMPORTED_PERIOD_F} ] && prev_period_f=`cat ${ESS_IMPORTED_PERIOD_F}` || prev_period_f=''
  [ -e ${ESS_IMPORTED_PERIOD_T} ] && prev_period_t=`cat ${ESS_IMPORTED_PERIOD_T}` || prev_period_t=''
  [ -e ${ESS_IMPORTED_BACK_DAYS} ] && prev_bdays=`cat ${ESS_IMPORTED_BACK_DAYS}` || prev_bdays=''
  [ -e ${ESS_IMPORTED_SAMPLING} ] && prev_sampl=`cat ${ESS_IMPORTED_SAMPLING}` || prev_sampl=11
  [ -e ${ESS_IMPORTED_MEMO} ] && prev_memo=`cat ${ESS_IMPORTED_MEMO}` || prev_memo=''
  [ "${sdate}" != "${prev_period_f}" -o "${edate}" != "${prev_period_t}" -o "${sampl}" != "${prev_sampl}" -o "${bdays}" != "${prev_bdays}" -o "$memo" != "$prev_memo" ] && result=1

  if [ ${result} = 1 ]; then
    setImportedPeriods ${sdate} ${edate} ${bdays} ${sampl} ${memo}
  fi

  echo ${result}
}
setImportedPeriods(){
  local period_m=$1
  local period_c=$2
  local bdays=$3
  local sample=${4}
  local memo=${5}
  echo "${period_m}" > ${ESS_IMPORTED_PERIOD_F}
  echo "${period_c}" > ${ESS_IMPORTED_PERIOD_T}
  echo "${bdays}"    > ${ESS_IMPORTED_BACK_DAYS}
  echo "${sample}" > ${ESS_IMPORTED_SAMPLING}
  echo "${memo}" > ${ESS_IMPORTED_MEMO}
}
setCurrSegment(){
  local segment_m=$1
  echo "${segment_m}" > ${ESS_IMPORTED_SEGMENT}
}
getCurrCV(){
  [ -e $ESS_IMPORTED_CV ] && cat $ESS_IMPORTED_CV || echo ''
}
setCurrCV(){
  local cv=$1
  echo "${cv}" > ${ESS_IMPORTED_CV}
}
getCvcondCompare(){
  local cvcond=$1
  local prev_cvcond=''
  [ -e ${ESS_IMPORTED_CV} ] && prev_cvcond=`cat ${ESS_IMPORTED_CV}` || prev_cvcond=''
  [ "${cvcond}" != "${prev_cvcond}" ] && result=1 || result=0

  if [ ${result} = 1 ]; then
    setCurrCV "${cvcond}"
  fi

  echo ${result}
  
}
setIntrTypes(){
  local intadd=$1
  local intlist=$2
  local intorg=$3
  local intref=$4
  local tempFlg=$5
  local filepath=${ESS_IMPORTED_INTSPEC}

  if [ ${tempFlg} = 1 ]; then
    filepath=${ESS_IMPORTED_INTSPEC_TEMP}
  fi

  echo "${intadd}"  >  ${filepath}
  echo "${intlist}" >> ${filepath}
  echo "${intorg}"  >> ${filepath}
  echo "${intref}"  >> ${filepath}
}
getIntrCompare(){
  local intadd=$1
  local intlist=$2
  local intorg=$3
  local intref=$4
  local diffstr='0'
  local result=0
  
  # store the parameter to temp file
  setIntrTypes "${intadd}" "${intlist}" "${intorg}" "${intref}" 1

  if [ -e ${ESS_IMPORTED_INTSPEC} -a -e ${ESS_IMPORTED_INTSPEC_TEMP} ]; then
    prevdef=`cat ${ESS_IMPORTED_INTSPEC}`
    currdef=`cat ${ESS_IMPORTED_INTSPEC_TEMP}`
    if [ "${currdef}" = "${prevdef}" ]; then
      diffstr=''
    else
      diffstr='0'
    fi
  fi

  [ "${diffstr}" != '' ]  && result=1 || result=0

  if [ ${result} = 1 ]; then
    [ -e ${ESS_IMPORTED_INTSPEC_TEMP} ] && mv ${ESS_IMPORTED_INTSPEC_TEMP} ${ESS_IMPORTED_INTSPEC}
  else
    [ -e ${ESS_IMPORTED_INTSPEC_TEMP} ] && rm -f ${ESS_IMPORTED_INTSPEC_TEMP}
  fi

  echo ${result}
}
getFilterCompare(){
  local filter=$1
  local prevfilter=''
  local result=0
  [ -e ${ESS_IMPORTED_FILTER} ] && prevfilter=`cat ${ESS_IMPORTED_FILTER}` || prevfilter=''
  [ "${filter}" != "${prevfilter}" ] && result=1 || result=0
  
  if [ ${result} = 1 ]; then
    setFilterOpt "${filter}"
  fi

  echo ${result}
}
setFilterOpt(){
  local filter=$1
  echo "${filter}" > ${ESS_IMPORTED_FILTER}
}
setPathOrder(){
  local pathorder=$1
  [ "${pathorder}" != 100 ] && echo "${pathorder}" > ${ESS_IMPORTED_CALPATH_ORDER}
}
getPathOrder(){
  [ -e ${ESS_IMPORTED_CALPATH_ORDER} ] && local pathorder=`cat ${ESS_IMPORTED_CALPATH_ORDER}` || local pathorder=0
  echo ${pathorder}
}
getPathOrderCompare(){
  local pathord=${1}
  local prevval=`getPathOrder`
  [ "${pathord}" = ${prevval} ] && local result=0 || local result=1
  [ $result = 1 ] &&  setPathOrder "${pathord}"
  echo ${result}
}

setPathTillCV(){
  local pathtillcv=$1
  echo "${pathtillcv}" > ${IMPCALPATHTILLCVFLG}
}
getPathTillCV(){
  [ -e ${IMPCALPATHTILLCVFLG} ] && local pathtillcv=`cat ${IMPCALPATHTILLCVFLG}` || local pathtillcv=0
  echo ${pathtillcv}
}
getPathTillCVCompare(){
  local pathtillcv=${1}
  local prevval=`getPathTillCV`
  [ "${pathtillcv}" = ${prevval} ] && local result=0 || local result=1
  [ $result = 1 ] &&  setPathTillCV "${pathtillcv}"
  echo ${result}
}


getTouchPointDefCompare(){
  local pathtpdef=${1} 
  local diffstr='0'
  local result=0
  
  # store the parameter to temp file
  setTouchPointDef "${pathtpdef}" 1

  if [ -e ${ESS_IMPORTED_TOUCHPOINTDEF} -a -e ${ESS_IMPORTED_TOUCHPOINTDEF_TEMP} ]; then
    prevdef=`cat ${ESS_IMPORTED_TOUCHPOINTDEF}`
    if [ "${pathtpdef}" = "${prevdef}" ]; then
      diffstr=''
    else
      diffstr='0'
    fi
  fi

  [ "${diffstr}" != "" ]  && result=1 || result=0


  if [ ${result} = 1 ]; then
    [ -e ${ESS_IMPORTED_TOUCHPOINTDEF_TEMP} ] && mv ${ESS_IMPORTED_TOUCHPOINTDEF_TEMP} ${ESS_IMPORTED_TOUCHPOINTDEF}
  else
    [ -e ${ESS_IMPORTED_TOUCHPOINTDEF_TEMP} ] && rm -f ${ESS_IMPORTED_TOUCHPOINTDEF_TEMP}
  fi

  echo ${result}
}

getTouchPointGroupingCompare(){
  local pathgrouping=$1
  local diffstr='0'
  local result=0
  
  # store the parameter to temp file
  setTouchPointDef "${pathgrouping}" 1

  if [ -e ${ESS_IMPORTED_TOUCHPOINTGROUPING} -a -e ${ESS_IMPORTED_TOUCHPOINTGROUPING_TEMP} ]; then
    prevdef=`cat ${ESS_IMPORTED_TOUCHPOINTGROUPING}`
    if [ "${pathgrouping}" = "${prevdef}" ]; then
      diffstr=''
    else
      diffstr='0'
    fi
  fi

  [ "${diffstr}" != "" ]  && result=1 || result=0


  if [ ${result} = 1 ]; then
    [ -e ${ESS_IMPORTED_TOUCHPOINTGROUPING_TEMP} ] && mv ${ESS_IMPORTED_TOUCHPOINTGROUPING_TEMP} ${ESS_IMPORTED_TOUCHPOINTGROUPING}
  else
    [ -e ${ESS_IMPORTED_TOUCHPOINTGROUPING_TEMP} ] && rm -f ${ESS_IMPORTED_TOUCHPOINTGROUPING_TEMP}
  fi

  echo ${result}
}

#replaceWithReservedWords(){
#  local words="$1"
#  local sdate=''; local edate=''; local bdays=''
#  [ -e ${ESS_IMPORTED_PERIOD_F} ] && sdate=`cat ${ESS_IMPORTED_PERIOD_F}`
#  [ -e ${ESS_IMPORTED_PERIOD_T} ] && edate=`cat ${ESS_IMPORTED_PERIOD_T}`
#  [ -e ${ESS_IMPORTED_BACK_DAYS} ] && bdays=`cat ${ESS_IMPORTED_BACK_DAYS}`
#  [ -z "$sdate" ] && words=`echo "$words" | sed "s/%START_DATE/$sdate/g"` 
#  [ -z "$edate" ] && words=`echo "$words" | sed "s/%END_DATE/$edate/g"` 
#  [ -z "$bdays" ] && words=`echo "$words" | sed "s/%PREV/$bdays/g"` 
#  echo "$words"
#}

getUserSegmentCompare(){
  local userseg=${1}
  local prevuseg=`getUserSegmentValue`
  local diffFlg=1
  [ "${userseg}" != "${prevuseg}" ] && diffFlg=1 || diffFlg=0

  if [ ${diffFlg} = 1 ]; then
    echo "${userseg}" > ${ESS_IMPORTED_USERSEGMENT}
  fi

  echo ${diffFlg}
}
getUserSegmentValue(){
  [ -e ${ESS_IMPORTED_USERSEGMENT} ] && cat ${ESS_IMPORTED_USERSEGMENT} || echo ''
}

#getFilterValue(){
#  [ -e ${ESS_IMPORTED_FILTER} ] && cat ${ESS_IMPORTED_FILTER} || echo ""
#}

setTouchPointDef(){
  local pathtpdef=$1 
  local tempFlg=$2
  local filepath=''

  [ ${tempFlg} = 1 ] && filepath=${ESS_IMPORTED_TOUCHPOINTDEF_TEMP} || ${ESS_IMPORTED_TOUCHPOINTDEF}

  if [ "${filepath}" != "" ]; then
    echo "${pathtpdef}"   >  ${filepath}
  fi

}

initdb(){
  echo '^^^ Init Users DB ^^^' 1>&2
  bash ${INITDBSHELL} "$uid" "$lid" "${DEFAULT_DATA_PATH}"
}


