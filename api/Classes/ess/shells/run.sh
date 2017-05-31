#!/bin/bash
#set -e

USERCOOKIE=$1
funcPattern=$2
uid=$3
lid=$4


DIR_CURR=$(cd $(dirname $0); pwd)

source ${DIR_CURR}/scripts-ui/vars.sh
source ${DIR_CURR}/scripts-ui/funcs/udbd.sh
source ${DIR_CURR}/scripts-ui/funcs/query.sh
INITDBSHELL="${DIR_CURR}/scripts-ui/funcs/init_db.sh"



if [ "${funcPattern}" != '' ]; then
  ${funcPattern} "${5}" "${6}" "${7}" "${8}" "${9}" "${10}" "${11}" "${12}" "${13}" "${14}" "${15}" "${16}" "${17}" "${18}" "${19}" "${20}" "${21}"
fi

exit



