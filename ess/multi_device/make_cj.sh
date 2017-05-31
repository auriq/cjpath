#!/bin/bash
####################################################################################
# Import multi device sample
####################################################################################
source "./usrconfig.inc"
source "./usrparam.inc"
Cache="${CACHE}"

CJ_TABLE_COL="uid t src domain page pname ref skey ip bwos fg ent visit pnum score member.gender member.age"
####### Functions ##############
import_weblog () {
	ess stream multidev "*" "*" "aq_pp -f+1 - -d s:uid s:date s:domain s:pname s:bwos s:ent s:gender s:age \
	-eval i:t 'DateToTime(date,\"Y.m.d.H.M.S\")' \
        -eval s:src '\"web\"' -imp,ddef udb_cj:cj -imp,ddef udb_cj:member"
}
#
write_cj_to_cache () {
	eval "aq_udb -ord udb_cj:cj" 
	eval "aq_udb -exp udb_cj:cj -c $CJ_TABLE_COL | gzip > $Cache/cj.csv.gz" 
}
#
##################################
####       MAIN            #######
##################################
bash setup.sh
bash createdb.sh

#  
# Check if cache folder exists
if [ ! -d "$Cache" ]; then
	mkdir -p $Cache
fi
import_weblog
write_cj_to_cache
exit $?
