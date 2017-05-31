#!/bin/bash
DIR_CURR=$(cd $(dirname $0); pwd)
MYPATH=$(cd $(dirname ${BASH_SOURCE:-$0}); pwd)
source ${MYPATH}/common.sh
source ${MYPATH}/env.sh


#--------------------
#      FUNCTIONS
#--------------------
readDataset(){
  local cachedir=$1
  local pathord=$2
  local cvcond=$3
  local enttype=$4
  local query_ids=$5
  local incfl=$6
  local pathgrouping=$7
  local pathtpdef=${8}
  local userseg=${9}
  local redoflg=${10}
  local tillcv=${11}

  local laststatus=`cat ${ESS_IMPORT_STATUS}`
  echo 'running' > ${ESS_IMPORT_STATUS}
  touch ${ESS_IMPORT_TIMESTAMP}

# hope to make it temporary treatment.
[ "${bdays}" = "" ] && bdays=${CALC_BACKWARD_DAYS} || bdays=${bdays}
[ "${sampling}" = "" ] && sampling=11 || sampling=${sampling}

  goToESS

  [ "$REPOSITORY" = local ] && cachedir="${PROJECT_DIR}/cache/${cachedir}" || cachedir="${CACHEDIR}/${cachedir}"
  local diffPeriodsFlg=`checkDiffCachedir "$cachedir"`
  [ "${laststatus}" = 'failed' ] && { diffPeriodsFlg=1; bash ${ESS_CREATEDB} >/dev/null 2>&1; }
  if [ "${diffPeriodsFlg}" = 0 ]; then
    echo 'runningprofile' > ${ESS_IMPORT_STATUS}
    setProfile "${pathord}" "${cvcond}" "${enttype}" "${query_ids}" "${incfl}" "${pathgrouping}" "${pathtpdef}" "${userseg}" "${tillcv}" 0
    echo 'done' > ${ESS_IMPORT_STATUS}

  else
    #  read data and run profile
    if [ -e ${LOCK_ESS_IMPORTING} ]; then
      echo "WHILE IMPORTING" 1>&2
    else
      echo 'START IMPORT DATA' 1>&2
      touch ${LOCK_ESS_IMPORTING}
      echo 'runningimport' > ${ESS_IMPORT_STATUS}
REDO=0
      bash ${ESS_IMPORT} "${cachedir}" "$REPOSITORY" >/dev/null 2>&1 && ret=0 || { echo $? 1>&2; ret=1; }
echo "done" 1>&2
      rm -f ${LOCK_ESS_IMPORTING}

      [ "$ret" != 0 ] && { echo 'IMPORT FAILED' 1>&2; echo 'failed' > $ESS_IMPORT_STATUS; echo 'done'; exit; }

      echo 'IMPORT DONE' 1>&2
      echo "START PROFILE SHELL" 1>&2
      echo 'runningprofile' > ${ESS_IMPORT_STATUS}
      setProfile "${pathord}" "${cvcond}" "${enttype}" "${query_ids}" "${incfl}" "${pathgrouping}" "${pathtpdef}" "${userseg}" "${tillcv}" 1 > /dev/null 2>&1
      echo "COMPLETE PROFILE SHELL" 1>&2
      echo 'done' > ${ESS_IMPORT_STATUS}
    fi
  fi

  echo 'done'
}

setProfile(){
  local pathord=$1
  local cvcond=$2
  local enttype=$3
#  local filter=$4
  local incfl=$5
  local pathgrouping=$6
  local pathtpdef=$7
  local userseg=$8
  local tillcv=$9
  local forceflg=${10}

  local CACHE=`getCurrCachedDirPath`

  #userseg=`replaceWithReservedWords "$userseg"`
  #filter=`replaceWithReservedWords "$filter"`

  [ ${forceflg} = 0 ] && touch ${ESS_IMPORT_TIMESTAMP}

  # check if the parameter is different from the previous access. 
  local changeFlgCvtag=`getCvcondCompare "${cvcond}"`
  local changeFlgInteraction=`getIntrCompare "${enttype}"`
#  local changeFlgFilterOpt=`getFilterCompare "${filter}"`
local changeFlgFilterOpt=0
  local changeFlgUserSegment=`getUserSegmentCompare "${userseg}"`

  local importedFlg=`checkIfImported`
  if [ ${importedFlg} = 0 ]; then
    # data is not imported, should not run profile before import.
    echo 'done'
    exit
  fi
  if [ ${forceflg} = 0 -a -e ${LOCK_ESS_IMPORTING} ]; then
    # profile should not run during importing data.
    echo 'done'
    exit
  fi

 
  if [ ${forceflg} = 1 -o ${changeFlgCvtag} = 1 -o ${changeFlgInteraction} = 1 -o ${changeFlgFilterOpt} = 1 -o ${changeFlgUserSegment} = 1 ]; then
    echo 'START PROFILE' 1>&2

    local doneall=0
    if [ ${forceflg} = 1 -o ${changeFlgCvtag} = 1 -o ${changeFlgInteraction} = 1 -o ${changeFlgFilterOpt} = 1 ]; then
      doneall=1
    fi
    goToESS
    echo 'runningprofile' > ${ESS_IMPORT_STATUS}
    if [ ${doneall} = 1 ]; then
      bash ${ESS_PROFILE} "${cvcond}" >/dev/null 2>&1 && ret=0 || ret=1
      if [ ! -z "$cvcond" ]; then
        [ "$ret" != 0 ] && { echo 'PROFILE FAILED' 1>&2; echo 'failed' > $ESS_IMPORT_STATUS; echo 'done'; exit; } # comment out, because it always fails when it runs right after 0 hit for user segment.
      fi
    fi

    setCalPath "${pathtpdef}" "${pathgrouping}" "${incfl}" "${userseg}" "${pathord}" "${tillcv}" 1 && ret=0 || ret=1
    [ "$ret" != 0 ] && { echo 'CALPATH FAILED' 1>&2; echo 'failed' > $ESS_IMPORT_STATUS; echo 'done'; exit; }
    echo 'done' > ${ESS_IMPORT_STATUS}
  else
    # proifle does not have to run.
    setCalPath "${pathtpdef}" "${pathgrouping}" "${incfl}" "${userseg}" "${pathord}" "${tillcv}" 1 && ret=0 || ret=1
    [ "$ret" != 0 ] && { echo 'CALPATH FAILED' 1>&2; echo 'failed' > $ESS_IMPORT_STATUS; echo 'done'; exit; }
    if [ ! -e ${LOCK_ESS_IMPORTING} ]; then
      echo 'done' > ${ESS_IMPORT_STATUS}
    fi
  fi
  echo 'done'
}

setCalPath(){
  local pathtpdef=$1
  local pathgrouping=$2
  local incfl=$3
  local userseg=$4
  local pathord=$5
  local tillcv=$6
  local forceflg=$7
  [ "${pathord}" = 100 ] && pathord=`getPathOrder`
  local userseg=`getUserSegmentValue`
  local changeFlgPathTouchPointDef=`getTouchPointDefCompare "${pathtpdef}"`
  local changeFlgPathTouchPointGrouping=`getTouchPointGroupingCompare "${pathgrouping}"`
  local changePathOrder=`getPathOrderCompare "${pathord}"`
  local changePathTillCV=`getPathTillCVCompare "${tillcv}"`
  if [ ${forceflg} = 1  -o "${changeFlgPathTouchPointDef}" = 1   -o "${changePathOrder}" = 1 -o "${changePathTillCV}" = 1 -o "${changeFlgPathTouchPointGrouping}" = 1 ]; then
    goToESS
    echo 'runningprofile' > ${ESS_IMPORT_STATUS}
    [ ${pathord} = 1 ] && local SHELLSCRNAME=${ESS_CALPATH_REVERSE} || local SHELLSCRNAME=${ESS_CALPATH}
    local CACHE=`getCurrCachedDirPath`
    local PSSTART=''
    local cvcond=`getCurrCV`
    [ ${tillcv} = 1 ] && local PSEND='CV' || local PSEND=''
    [ ${tillcv} != 1 -o ${pathord} = 1 ] && [ ! -z "$cvcond" ] && local NODEDEF="${pathtpdef} -if -filt '${cvcond}' -eval ety '\"CV\"' -endif" || local NODEDEF=${pathtpdef}

    echo 'RUNNING CAL_PATH' 1>&2
    bash ${SHELLSCRNAME} "${NODEDEF} ${incfl}" "${userseg}" "${pathgrouping}" "${CACHE}" "${REPOSITORY}" "${REDO}" "${PSSTART}" "${PSEND}"
    echo 'done' > ${ESS_IMPORT_STATUS}
  else
    # CAL PATH DOES NOT HAVE TO RUN.
    if [ ! -e ${LOCK_ESS_IMPORTING} ]; then
      echo 'done' > ${ESS_IMPORT_STATUS}
    fi
  fi
  echo 'done'
}


