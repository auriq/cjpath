#!/bin/bash

#if [ $# -ne 2 ]; then
#  echo "e.g. sh setupinstance.sh type num"; exit
#fi
instanceType=$1
instanceNum=$2

if [ -n "$instanceType" -a "${instanceType}" = "local" ]; then
  ess cluster set local
else
  ess cluster set cloud
  [ `ess cluster status 2>&1 | grep -c "No cluster running."` != 0 ] && ess cluster create --number "$instanceNum" --type "$instanceType" || ess cluster start
fi



#
#
#if [ "${instanceNum}" = "" ]; then
#  instanceNum=1
#fi
#
#if [ "${instanceType}" = "local" ]; then
#  ess cluster set local
#else
#  ess cluster set cloud
#  ess cluster create --number=${instanceNum} --type=${instanceType}
#fi
#
#
