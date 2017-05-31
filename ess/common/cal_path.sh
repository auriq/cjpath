#!/bin/bash
###################################################################
# Calculate and create CV-PATH for all user
###########################################################################
CreateNode="$1"
Segment="$2"
Grouping="$3"   # to be added 
Cache="$4"
Repo="$5"
ReDo=$6
Start="$7"  ## Starting point to calcurate path. null=from the TOP : Not in use 
End="$8"    ## Whether stop at CV or continue
#
Hash=$((0x$(sha1sum <<<"$CreateNode $Segment $Grouping $Start $End")0))
File="path-$Hash.gz"
echo "# Condition=$CreateNode Segment=$Segment Group=$Grouping Start=$Start End=$End" > header
#
individual_path () {
ess exec "aq_udb -exp udb_cj:cj $1 -o,bin - -c uid domain page pname ref ent src skey -local \
	| aq_pp -f,bin - -d s:uid s:domain s:page s:pname s:ref s:ent s:src s:skey -var i:xI1 0  -var s:sPre '\"\"' \
	-eval s:ety '\"\"' \
        $Grouping $CreateNode \
	-filt 'ety!=\"\"' \
	-eval s:sNow 'uid+ety' \
	-if -filt 'sPre!=sNow' -eval sPre sNow -o,bin - -c uid ety -endif \
        | aq_pp -f,bin - -d s:uid s:elm -var s:xId '\"\"' -var i:xI2 0 -var s:xS1 '\"\"' \
        -eval s:p1 '\"\"' -eval s:p2 p1 -eval s:p3 p1 -eval s:p4 p1 -eval s:p5 p1 -eval s:p6 p1 -eval s:p7 p1 \
        -eval s:p8 p1 -eval s:p9 p1 -eval s:p10 p1 -eval s:p11 p1 -eval s:p12 p1 -eval s:p13 p1 -eval s:p14 p1 \
        -eval s:p15 p1 -eval s:p16 p1 -eval s:p17 p1 -eval s:p18 p1 -eval s:p19 p1 -eval s:p20 p1 \
        -if -filt 'uid!=xId' -eval xI2 1 -eval xS1 '\"\"' -else -eval xI2 'xI2+1' -endif \
        -if -filt 'xI2==1' -eval p1 elm -endif \
        -if -filt 'xI2==2' -eval p2 elm -endif \
        -if -filt 'xI2==3' -eval p3 elm -endif \
        -if -filt 'xI2==4' -eval p4 elm -endif \
        -if -filt 'xI2==5' -eval p5 elm -endif \
        -if -filt 'xI2==6' -eval p6 elm -endif \
        -if -filt 'xI2==7' -eval p7 elm -endif \
        -if -filt 'xI2==8' -eval p8 elm -endif \
        -if -filt 'xI2==9' -eval p9 elm -endif \
        -if -filt 'xI2==10' -eval p10 elm -endif \
        -if -filt 'xI2==11' -eval p11 elm -endif \
        -if -filt 'xI2==12' -eval p12 elm -endif \
        -if -filt 'xI2==13' -eval p13 elm -endif \
        -if -filt 'xI2==14' -eval p14 elm -endif \
        -if -filt 'xI2==15' -eval p15 elm -endif \
        -if -filt 'xI2==16' -eval p16 elm -endif \
        -if -filt 'xI2==17' -eval p17 elm -endif \
        -if -filt 'xI2==18' -eval p18 elm -endif \
        -if -filt 'xI2==19' -eval p19 elm -endif \
        -if -filt 'xI2==20' -eval p20 elm -endif \
        -eval xId uid -imp,ddef udb_cj:path"
}
#####################
# MAIN 
#####################
# first clear path data
eval "aq_udb -clr udb_cj:path" 
#check if the cache file exist
r=$(eval "ess ls $Cache/$File")   
if [ ${#r} -eq 0 ] || [ $ReDo -eq 1 ]
then       
## Create result and output it to cache file
### Path pattern Non CV user
individual_path "-filt 'profile.first_cv==0' $Segment"
### Path pattern CV user
if  [ "$End" == "CV" ] 
then
individual_path "-filt 'fg>0 && profile.first_cv>0' $Segment"
else 
individual_path "-filt 'profile.first_cv>0' $Segment"
fi
## output path to S3 cache ##
if [ $Repo == "local" ]; then
	eval "(cat header ; aq_udb -exp udb_cj:path ) \
        | gzip > $Cache/$File"
else
	ess exec "(cat header ; aq_udb -exp udb_cj:path ) \
        | gzip" --master --s3out $Repo:$Cache/$File 
fi
else
ess cat $Cache/$File | gunzip | aq_pp -f+2 - -d  s:uid s:p1 s:p2 s:p3 s:p4 s:p5 s:p6 s:p7 s:p8 s:p9 s:p10 s:p11 s:p12 s:p13 s:p14 s:p15 s:p16 s:p17 s:p18 s:p19 s:p20 -imp udb_cj:path
fi
exit
