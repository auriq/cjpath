#!/bin/bash
###################################################################
# Aggregate Reverse CV-PATH pattern
###########################################################################
FROM=$1; TO=$2; DEPTH=$3; FLT=$4; Diff=$5

if [ $DEPTH -eq 1 ]; then Path="p20"; DecPath="s:p20"
elif [ $DEPTH -eq 2 ]; then Path="p19 p20"; DecPath="s:p19 s:p20"
elif [ $DEPTH -eq 3 ]; then Path="p18 p19 p20"; DecPath="s:p18 s:p19 s:p20"
elif [ $DEPTH -eq 4 ]; then Path="p17 p18 p19 p20"; DecPath="s:p17 s:p18 s:p19 s:p20"
elif [ $DEPTH -eq 5 ]; then Path="p16 p17 p18 p19 p20"; DecPath="s:p16 s:p17 s:p18 s:p19 s:p20"
else Path="p1 p2 p3 p4 p5 p6 p7 p8 p9 p10 p11 p12 p13 p14 p15 p16 p17 p18 p19 p20"
DecPath="s:p1 s:p2 s:p3 s:p4 s:p5 s:p6 s:p7 s:p8 s:p9 s:p10 s:p11 s:p12 s:p13 s:p14 s:p15 s:p16 s:p17 s:p18 s:p19 s:p20"
fi

aq_udb -clr udb_rslt:.

ess exec "aq_udb -exp udb_cj:path $FLT -o,bin - -c uid $Path $Diff profile.first_cv -local \
	| aq_pp -f,bin - -d x $DecPath i:d i:cv \
	-eval i:tmax 0 -eval i:tmin 0 -eval i:tave 0 -eval i:tcnt 0 -eval i:rcnt 0 \
        -kenc s:key $Path \
        -if -filt 'cv>0' -eval tmax d -eval tmin d -eval tave d -eval tcnt 1 \
        -else -eval rcnt 1 -endif \
        -imp,ddef udb_rslt:rslt" 

### Outpu CV path result as requested 
PathCol=`echo $Path | sed -e "s/ /,/g"`
echo "$PathCol, trg_cnt, rfr_cnt, trg_max, trg_min, trg_ave"
TOT=$(eval "aq_udb -cnt udb_rslt:rslt | awk /pkey/ | cut -d, -f 2")
CVP=$(eval "aq_udb -cnt udb_rslt:rslt -filt 'tcnt>0' | awk /pkey/ | cut -d, -f 2")
echo "Total=$TOT, CVP=$CVP"
aq_udb -exp udb_rslt:rslt -sort,dec tcnt rcnt -lim_rec $(($TO)) \
	| aq_pp -f+1 - -d s:ik i:tcnt i:rcnt i:tmax i:tmin i:tave -kdec ik $DecPath \
	-eval tave 'tave/tcnt' \
	-c $Path tcnt rcnt tmax tmin tave \
	| tail -n +$(($FROM+1))

exit
