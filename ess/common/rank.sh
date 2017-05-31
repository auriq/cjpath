#!/bin/bash
###################################################################
# Rank by given key
# Usage: sh rank before CampaignName SiteName PlacementName AdName
######################################################################
Keys="$1"
From=$2
To=$3
Segment="$4"
Src="$5"
Cache="$6"
Repo="$7"
ReDo=$8
#
ranking () {
Hash=$((0x$(sha1sum <<<"$1 $2")0))
File="rank-$Hash.gz"
echo "# Filt=$1 File=$2" > header
r=$(eval "ess ls $Cache/$File")   #check if the cache file exist
if [ ${#r} -eq 0 ] || [ $ReDo -eq 1 ]
then       ## Create result and output it to cache file
eval "aq_udb -clr udb_cnt:."
ess exec "aq_udb -exp udb_cj:cj $1 -o,bin - -c domain page pname ref -local \
	|aq_pp -f,bin - -d s:domain s:page s:pname s:ref \
	-eval i:pv 1 -eval i:ss 0 -kenc s:key domain page pname ref -imp,ddef udb_cnt:cnt" 
if [ $Repo == "local" ]; then
	eval "aq_udb -exp udb_cnt:cnt -sort,dec pv \
	| (cat header ; \
	aq_pp -f+1 - -d s:ik i:pv x \
	-kdec ik s:domain s:page s:pname s:ref \
	-c domain page pname ref pv) | gzip > $Cache/$File"
else
	ess exec "aq_udb -exp udb_cnt:cnt -sort,dec pv \
	| (cat header ; \
	aq_pp -f+1 - -d s:ik i:pv x \
	-kdec ik s:domain s:page s:pname s:ref \
	-c domain page pname ref pv) | gzip" --master --s3out $Repo:$Cache/$File
fi
fi
ess cat $Cache/$File | gunzip > $2  # output to local
}
##################################################
##  Main 
##	"Usage> sh rank.sh \"keyselectionstring\" top"
##	echo "e.g. sh rank.sh \"s,k:domain s,k:page\" 10"
##	echo "e.g. sh rank.sh \"x s,k:page\" 30"
##################################################

ranking "-filt 'fg>0 && $Src' $Segment" "before.csv"
ranking "-filt 'fg<0 && $Src' $Segment" "after.csv"

TPV_BEFORE=`aq_pp -f+2 before.csv -d x x x x  i:cn -var i:Sum 0 -eval Sum 'Sum+cn' -ovar,notitle -`
TPV_AFTER=`aq_pp -f+2 after.csv -d x x x x i:cn -var i:Sum 0 -eval Sum 'Sum+cn' -ovar,notitle -`
eval "aq_pp -f+2 before.csv -d s:domain s:page s:pname s:ref i:bpv \
	-cat+2 after.csv s:domain s:page s:pname s:ref i:apv \
	-eval f:r_bpv '100.0*bpv/$TPV_BEFORE' \
	-eval f:r_apv '100.0*apv/$TPV_AFTER' \
	-c domain page pname ref bpv r_bpv apv r_apv \
	| rw_csv -f+1 - -d $Keys i,r:before-event f,r:before-rate i,r:after-event f,r:after-rate -merg -sumx -sort before-event -range $From $To"
exit

