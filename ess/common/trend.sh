#!/bin/bash
###################################################################
# Trend Analysis 
###########################################################################
Fmt="$1"
Segment="$2"
Tzone="$3"
Cache="$4"
Repo=$5
ReDo=$6
Cuu=$7
Hash=$((0x$(sha1sum <<<"$Fmt $Segment $Tzone $Cuu")0))
File="trend-$Hash.gz"
echo "# Format=$Fmt Segment=$Segment Tzone=$Tzone" > header
#
r=$(eval "ess ls $Cache/$File")   #check if the cache file exist
if [ ${#r} -eq 0 ] || [ $ReDo -eq 1 ]
then       ## Create result and output it to cache file
eval "aq_udb -clr udb_cnt:." 
eval "aq_udb -clr udb_cntuu:."
###### Count also UU ######
if [ $Cuu -eq 1 ] 
then  ## Count events for combined key = date-hour-uid
ess exec "aq_udb -exp udb_cj:cj $Segment -o,bin - -c t uid pnum -local \
        | $Tzone aq_pp -f,bin - -d i:t s:uid i:pnum -eval s:dt 'TimeToDate(t, \"$Fmt\")' \
	-if -filt 'pnum==1' -eval i:ss 1 -else -eval ss 0 -endif \
	-kenc s:key dt uid -eval i:pv 1 -imp udb_cnt:cnt" 
## Count events and UU by date-hour
ess exec "aq_udb -exp udb_cnt:cnt -o,bin - -local \
	| aq_pp -f,bin - -d s:ik i:pv i:ss \
	-kdec ik s:key s:uid -eval i:uu 1 -imp udb_cntuu:cntuu" 
else  ###### Not count UU  ######
ess exec "aq_udb -exp udb_cj:cj $Segment -o,bin - -c t uid pnum -local \
        | $Tzone aq_pp -f,bin - -d i:t s:uid i:pnum -eval s:dt 'TimeToDate(t, \"$Fmt\")' \
	-if -filt 'pnum==1' -eval i:ss 1 -else -eval ss 0 -endif \
	-kenc s:key dt -eval i:pv 1 -eval i:uu 0 -imp udb_cntuu:cntuu" 
fi
## Output Date-hour, pv, uu
if [ $Repo == "local" ]; then
	eval "(cat header ; aq_udb -exp udb_cntuu:cntuu -sort key) \
	| gzip > $Cache/$File"
else
	ess exec "(cat header ; aq_udb -exp udb_cntuu:cntuu -sort key) \
	| gzip" --master --s3out $Repo:$Cache/$File
fi
fi
## Output results from cache file to stdout
ess cat $Cache/$File | gunzip | aq_pp -f+2 - -d s:key i:pv i:ss i:uu
exit
