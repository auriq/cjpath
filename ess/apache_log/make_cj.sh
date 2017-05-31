#!/bin/bash
####################################################################################
# Import simple apache log, make CJ database 
####### Variables ##################################################################
source "./usrconfig.inc"
source "./usrparam.inc"

Cache="${CACHE}"

##### Initial or Each time after filtering condition change #####

OPTION="--thread=1 --debug"
CJ_TABLE_SPEC="s:uid i:t s:src s:domain s:page s:pname s:ref s:skey s:ip s:bwos is:fg s:ent i:visit i:pnum f:score"
####### Functions ##############
import_weblog () {
	ess stream apachlog "*" "*" "aq_pp -f,qui,eok,div - -d s:ip sep:' ' s:c1 sep:' ' s:c2 sep:' [' s:date sep:'] \"' \
	s:method sep:' ' s:page sep:' ' s:prot sep:'\" ' s:code sep:' ' s:size sep:' \"' s,clf:ref sep:'\" \"' s:bwos sep:'\"' s:c6 \
	-eval s:domain '\"acme.com\"' -eval s:uid 'ToS(SHash(ip+bwos))' -eval i:t 'DateToTime(date,\"%d.%b.%Y.%H.%M.%S.%z\")' \
	-eval s:skey 'SearchKey(ref)' -eval s:ent '\"\"' \
	-if -filt 'PatCmp(bwos,\"*monitis*\") || PatCmp(bwos,\"*robot*\") || PatCmp(bwos,\"*crawler*\")' -eval ent '\"robot\"' -endif \
	-eval bwos 'AgentParse(bwos,ToIP(ip))' \
	-if -filt 'IsCrawler(bwos)>0' -eval ent '\"robot\"' -endif \
	-eval s:pname page -sub,pat pname pagename.csv \
        -eval s:src '\"web\"' -imp,ddef udb_cj:cj" $OPTION
}

write_cj_to_cache () {
	eval "aq_udb -ord udb_cj:cj" 
	eval "aq_udb -exp udb_cj:cj | gzip > $Cache/cj.csv.gz" 
}
#
##################################
####       MAIN            #######
##################################
#bash setup.sh
bash createdb.sh
#  
# Check if cache folder exists
if [ ! -d "$Cache" ]; then
	mkdir -p $Cache
fi
import_weblog
write_cj_to_cache
