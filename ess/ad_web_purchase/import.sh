#!/bin/bash
#################################################################
# Import Customer Journey from cache
####### Variables ###############################################
CJ_TABLE_SPEC="s:uid i:t s:src s:domain s:page s:pname s:ref s:skey s:ip s:bwos is:fg s:ent i:visit i:pnum f:score s:gender s:age"

####### Functions ##############
read_cj_from_cache () {
	ess exec "aq_udb -clr udb_cj" --master
	ess category add cache "$Cache/cj.csv.gz" --noprobe --overwrite 
	ess stream cache "*" "*" "aq_pp -f+1 - -d $CJ_TABLE_SPEC -imp,ddef udb_cj:cj" $OPTION
}
#
##################################
####       MAIN            #######
##################################
Cache="$1"
#  
# Check if cache folder exists
rr=$(eval ess ls $Cache/cj.csv.gz)
if [ ${#rr} -eq 0 ] 
then
	echo "Error: cache $Cache does not exit"
	exit 2
else
	read_cj_from_cache
	exit $?
fi
