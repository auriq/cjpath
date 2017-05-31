#!/bin/bash
DIR_CURR=$(cd $(dirname $0); pwd)
MYPATH=$(cd $(dirname ${BASH_SOURCE:-$0}); pwd)
source ${MYPATH}/common.sh
source ${MYPATH}/env.sh

getPath(){
  local dist=$1
  local limit_from_to=$2
  local tp_filter=$3
  local tpdepth=$4
  local filter=$5
  local fname=""
  local shcomm=""
  local userseg=`getUserSegmentValue`
  local pathord=`getPathOrder`


  local limit_from=`echo ${limit_from_to} | awk -F' ' '{print $1}'`
  local limit_to=`echo ${limit_from_to} | awk -F' ' '{print $2}'`

  # define which shell script to use.
  [ "${pathord}" = 1 ] && local shellname=${ESS_PATH_REVERSE} || local shellname=${ESS_PATH}


  goToESS

  filterOpt="${userseg}"
  if [ "${tp_filter}" != "" ];then
    filterOpt="${userseg} -pp,n path -filt,yn '(${tp_filter})' -endpp"
  fi

  echo '^^^^^ Start Path Shell ^^^^^^^' 1>&2
  bash ${shellname} "${limit_from}" "${limit_to}" "${tpdepth}" "${filterOpt} ${filter}" "profile.${dist}"
}

getGlobalSummary(){
  local pathstr=$1
  local userseg=`getUserSegmentValue`
  goToESS
  echo '^^^^^ Start Summary Shell ^^^^^^^' 1>&2
  bash ${ESS_QUERY_SUMMARY} "${userseg} ${pathstr}" "${ENT_CLICK}" "${ENT_LISTING}"
}

getPathMaxPage(){
  local tp_filter=$3
  local tpdepth=$4
  local userseg=`getUserSegmentValue`
  goToESS

  local filterOpt="${userseg}"
  if [ "${tp_filter}" != "" ]; then
    filterOpt="${userseg} -pp,n path -filt,yn '(${tp_filter})' -endpp"
  fi

  local pathord=`getPathOrder`
  # define which shell script to use.
  [ "${pathord}" = 1 ] && local shellname=${ESS_PATH_REVERSE} || local shellname=${ESS_PATH}
  echo '^^^^^ Start Get Max Page ^^^^^^^' 1>&2
  if [ -e ${LOCK_ESS_IMPORTING} ]; then
    echo 100
  else
    bash ${shellname} "1" "10" "${tpdepth}" "${filterOpt}" "profile.days" | awk 'NR==2' | cut -d ',' -f 1 | cut -d '=' -f 2
  fi


}

getDist(){
  local distType=$1
  local pathstr=$2
  local userseg=`getUserSegmentValue`
  local shname=''

  # distType should be one of the "days, depth"
  goToESS
  echo '^^^^^ Start Dist Chart ^^^^^^^' 1>&2
  if [ "${distType}" = 'pv' ]; then
    bash ${ESS_QUERY_DIST_PV} "${userseg} ${pathstr}"
  else
    bash ${ESS_QUERY_DIST_FREQ} "${userseg} ${pathstr}" "${distType}"
  fi
}

getSegBw(){
  local pcond=${1}
  local userseg=`getUserSegmentValue`

  goToESS
  echo '^^^^^ Start Browsers Shell ^^^^^^^' 1>&2
  bash ${ESS_PATH_DETAIL_BW} "${userseg} ${pcond}"
}

getRegionMap(){
  local pathstr=${1}
  local topx=${2}
  local userseg=`getUserSegmentValue`
  goToESS
  echo '^^^^^ Start Region Shell ^^^^^^^' 1>&2
  [ "${topx}" = '' ] &&  bash ${ESS_PATH_REGION_MAP} "${userseg} ${pathstr}"
  [ "${topx}" != '' ] &&  bash ${ESS_PATH_REGION_MAP} "${userseg} ${pathstr}" | head -n ${topx}
}
getAttrScore(){
  local attrmodel=$1 
  local paracol=$2
  local filterintr=$3 
  local pathstr=$4
  local topx=$5
  local userseg=`getUserSegmentValue`
  goToESS
  echo '^^^^^ Start Attribution Score ^^^^^^^' 1>&2
  [ "${topx}" = '' ] &&  bash ${ESS_PATH_ATTRIBUTION_SCORE} "${attrmodel}" "${filterintr} ${userseg} ${pathstr}" "${paracol}"
  [ "${topx}" != '' ] &&  bash ${ESS_PATH_ATTRIBUTION_SCORE} "${attrmodel}" "${filterintr} ${userseg} ${pathstr}" "${paracol}" | head -n ${topx}
  
}

getSegDump(){
  local pcond=${1}
  local usernum=${2}
  local iscvuserflg=$3   # 1: cv user 0: non-cv user -1:all
  local userseg=`getUserSegmentValue`
  #[ "$iscvuserflg" = 1 ] && cvspec="-filt 'profile.first_cv>0'" || cvspec="-filt 'profile.first_cv==0'"
  local cvspec=""
  if [ "$iscvuserflg" = 1 ]; then
    cvspec="-pp,n profile -filt,pr 'first_cv>0' -endpp"
  elif [ "$iscvuserflg" = 0 ]; then
    cvspec="-pp,n profile -filt,pr 'first_cv==0' -endpp"
  fi
  goToESS
  echo '^^^^^ Start Dump Shell ^^^^^^^' 1>&2
#  echo "$iscvuserflg" 1>&2
#  echo "$cvspec" 1>&2
  bash ${ESS_PATH_DETAIL_DUMP} "${cvspec} ${userseg} ${pcond}" ${usernum}
}

getBeforeAfter(){
  local colms=$1
  local logtype=$2
  local pathstr=$3
  local topx=$4
  local redo=$5
  goToESS
  if [ "${colms}" = "" ]; then
    colms="s,k:domain x"
  fi
  [ ${logtype} = "filterbyad" ] && local adpage="(${AD_LOG})" || local adpage="(${WEB_LOG})"

  local CACHE=`getCurrCachedDirPath`
  local userseg=`getUserSegmentValue`

  echo '^^^^^ Start Before/After Table ^^^^^^^' 1>&2
  bash ${ESS_PATH_BEFOREAFTER} "${colms}" 1 "${topx}" "${userseg} ${pathstr}" "${adpage}" "${CACHE}" "${REPOSITORY}" "${redo}"
}

getEssLoadedAmount(){
  local userseg=`getUserSegmentValue`
  goToESS
  echo '^^^^^ Start Loaded Amount Summary ^^^^^^^' 1>&2
  local shname=${ESS_QUERY_OVERALL_AD_WEB}
  [ "${shname}" != '' ] && bash ${shname} "${userseg}"
}

getTrend(){
  bintype=$1
  edate=$2
  sdate=$3
  isshowprev=$4
  pathstr=$5
  userseg=$6
  redo=$7
  [ "${userseg}" = "SELECTEDBYGLOBAL" ] && userseg=`getUserSegmentValue`
  local filter="${userseg}"
  goToESS
  if [ "${isshowprev}" = 0 ]; then
    periodfilter=`getPeriodFilter "$sdate" "$edate" ${TIMEZONE}`
    filter="${periodfilter} ${filter}"
  fi
  CACHE=`getCurrCachedDirPath`
  echo '^^^^^ Start Trend Shell ^^^^^^^' 1>&2
  [ "${bintype}" = '%F %H %M' ] && local uuflg=0 || local uuflg=1
  bash ${ESS_TREND} "${bintype}" "${filter} ${pathstr}" "${TIMEZONE}" "${CACHE}" "${REPOSITORY}" "${redo}" "${uuflg}"
}

getEvent(){
  local colnames=$1
  local colspec=$2
  local edate=$3
  local sdate=$4
  local filter=$5
  local logtype=$6
  local pathstr=$7
  local topx=$8
  local redo=$9
  local userseg=`getUserSegmentValue`

  goToESS
  if [ "${logtype}" = "filterbysite" ]; then
    local logfilter="-filt '${WEB_LOG}'"
  elif [ "${logtype}" = "filterbyad" ]; then
    local logfilter="-filt '${AD_LOG}'"
  fi
  CACHE=`getCurrCachedDirPath`
  echo '^^^^^ Start Event Shell ^^^^^^^' 1>&2
  bash ${ESS_EVENT} "${colnames}" "${colspec}" "${logfilter} ${filter} ${userseg} ${pathstr} " "${CACHE}" "${REPOSITORY}" 1 "${topx}" "${redo}"
}


