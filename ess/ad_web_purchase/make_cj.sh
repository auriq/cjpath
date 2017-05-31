#!/bin/bash
######################################################################
# Import multiple logs and create a data for Customer Journey Analysis
####### Variables #####################################################
source "./usrconfig.inc"
source "./usrparam.inc"
Start="${PFROM}"
End="${PTO}"
Cache="${CACHE}"
Recal=1
OPTION="--thread=1 --debug"
CJ_TABLE_COL="uid t src domain page pname ref skey ip bwos fg ent visit pnum score member.gender member.age"
#
####### Functions ##############
import_weblog () {
	ess stream weblog "$1" "$2" "aq_pp -f,eok - -d %cols \
	-filt 'uid!=\"\" && !PatCmp(uid,\"xnk*\") && !PatCmp(uid,\"gx*\")' -eval bwos 'bwos+os' \
        -eval s:src '\"web\"' -imp,ddef udb_cj:cj" $OPTION
}
#
import_adlog () {
	if [ ! -f "ad_web_uid_lookup.csv" ]; then
		ess stream ad_web_uid_lookup "*" "*" "cat > %FILE"
	fi
	ess stream adlog $1 $2 "aq_pp -f+1,eok - -d %cols \
	-eval s:domain CampaignID -eval s:page SiteID -eval s:ref PlacementID \
	-cmb+1 ad_web_uid_lookup.csv s:Userid s:uid -filt 'uid!=\"\"' \
	-eval i:t 'DateToTime(EventDate,\"m.d.Y.H.M.S.p\")' \
	-if -filt 'EventTypeID==1' -eval s:ent '\"imp\"' -endif \
	-if -filt 'EventTypeID==2' -eval ent '\"click\"' -endif \
	-if -filt 'EventTypeID==3' -eval ent '\"listing\"' -endif \
	-eval s:src '\"ad\"' -imp,ddef,nonew udb_cj:cj" $OPTION
}
#
import_purchaselog () {
	if [ ! -f "member.csv" ]; then
		ess stream member "*" "*" "cat > %FILE"
	fi
	ess stream purchase_log "*" "*" "aq_pp -f+1,eok,tsv - -d %cols \
	-eval s:domain shop -eval s:page good -eval s:ref price \
	-eval s:member memid -cmb+1 member.csv s:member s:gender s:age \
	-eval i:t 'DateToTime(date,\"Y.m.d.H.M.S\")' -eval s:skey count -eval s:uid web_cookie \
	-eval s:src '\"purchase\"' -imp,ddef,nonew udb_cj:cj -imp,ddef,nonew udb_cj:member" $OPTION
}
#
write_cj_to_cache () {
	ess exec "aq_udb -ord udb_cj:cj" --master
	ess exec "aq_udb -exp udb_cj:cj -c $CJ_TABLE_COL | gzip > $Cache/cj.csv.gz" --debug
}
#
##################################
####       MAIN            #######
##################################

bash setup.sh
bash createdb.sh
#  
# Check if cache folder exists
rr=$(eval ess ls $Cache/cj.csv.gz)
if [ ${#rr} -eq 0 ] || [ $Recal -eq 1 ]
then
	if [ ! -d "$Cache" ]; then
		mkdir -p $Cache
	fi
	## Read log data from original raw files
	import_weblog $Start $End 
	import_adlog $Start $End 
	import_purchaselog $Start $End
	write_cj_to_cache
fi
