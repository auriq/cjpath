#!/bin/bash
###################################################################
# Event Analysis 
###########################################################################
Ckey="$1"
Ckey_type="$2"
Segment="$3"
Cache="$4"
Repo="$5"
From=$6
To=$7
ReDo=$8
Hash=$((0x$(sha1sum <<<"$1 $2 $3")0))
File="event-$Hash.gz"
echo "# Ckey=$Ckey Ckey_type=$Ckey_type FiltSegment=$Segment" > header
##
r=$(eval "ess ls $Cache/$File")   #check if the cache file exist
if [ ${#r} -eq 0 ] || [ $ReDo -eq 1 ]
then       ## Create result and output it to cache file
##  Reset UDB
eval "aq_udb -clr udb_cnt:."
eval "aq_udb -clr udb_cntuu:."
## Count events for combined key = date-hour-uid
ess exec "aq_udb -exp udb_cj:cj $Segment -o,bin - -c domain page ref skey ent bwos ip uid -local \
        | aq_pp -f,bin - -d s:domain s:page s:ref s:skey s:ent s:bwos s:ip s:uid \
	-kenc s:key $Ckey uid -eval i:pv 1 -imp,ddef udb_cnt:cnt"
## Count events and UU by date-hour
ess exec "aq_udb -exp udb_cnt:cnt -o,bin - -local \
	| aq_pp -f,bin - -d s:ik i:pv i:ss \
	-kdec ik $Ckey_type s: \
	-kenc s:key $Ckey -eval i:uu 1 -imp udb_cntuu:cntuu"
## Output Date-hour, pv, uu
if [ $Repo == "local" ]; then
	eval "aq_udb -exp udb_cntuu:cntuu -sort,dec uu \
	| (cat header ; \
	aq_pp -f+1 - -d s:ik i:pv x i:uu -kdec ik $Ckey_type -c $Ckey pv uu) \
	| gzip > $Cache/$File"
else
	ess exec "aq_udb -exp udb_cntuu:cntuu -sort,dec uu \
	| (cat header ; \
	aq_pp -f+1 - -d s:ik i:pv x i:uu -kdec ik $Ckey_type -c $Ckey pv uu) \
	| gzip" --master --s3out $Repo:$Cache/$File
fi
fi
## OUTPUT RESULT: keys, pv, uu
ess cat $Cache/$File | rw_csv -f+2l - -d $Ckey_type i,r:event i,r:uu -range $From $To
exit
