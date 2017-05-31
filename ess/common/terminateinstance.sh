#!/bin/bash


if [ "${instanceType}" != "local" ]; then
  ess udbd stop
  #ess cluster stop
fi


#if [ $# -ne 1 ]; then
#  echo "e.g. sh terminateinstance.sh type"; exit
#fi
#
#
#instanceType=$1
#
#if [ "${instanceType}" = "local" ]; then
#  ess udbd stop
#else
#  ess cluster terminate
#fi
#
#
