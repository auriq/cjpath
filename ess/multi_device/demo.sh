########################################################################################################
# demo.sh: A simple demo to show how CJpath analysing data
# Copyright (C)  2017  AuriQ Systems, Inc.
# This script is open source and distributed under the GNU GPL v3
########################################################################################################
source "./usrconfig.inc"
source "./usrparam.inc"

REDO=1
##### Initial or Each time after filtering condition change #####
bash createdb.sh
bash import.sh "$CACHE"
bash profile.sh "$CONVERSION" 
############################## TREND #################################################################
# montly %m  
echo "Day"
bash ../common/trend.sh "%F" "$USEGMENT" "$TIMEZONE" "$CACHE" "$REPOSITORY" $REDO 1
#############################  EVENT  ###############################################################
echo "bwos"
bash ../common/event.sh "bwos" "s:bwos" "$USEGMENT" "$CACHE" "$REPOSITORY" 1 10 $REDO
echo "Domain Page"
bash ../common/event.sh "domain page" "s:domain s:page" "$USEGMENT" "$CACHE" "$REPOSITORY" 1 10 $REDO

### Query ########################################################################
echo "-----Overall --- $USEGMENT"
bash ../common/overall_ad_web.sh "$USEGMENT"
echo "-----SUMMARY --- $USEGMENT"
bash ../common/summary.sh "$USEGMENT"
echo "-----days to conversion ---"
bash ../common/depth.sh "$USEGMENT" "days" | head
echo "-----depth to conversion ---"
bash ../common/depth.sh "$USEGMENT" "depth" | head
echo "----- PV  converged user vs non conversged ---"
bash ../common/pv_dist.sh "$USEGMENT" | head

bash ../common/seg_bw.sh "$USEGMENT" | head
bash ../common/event.sh "skey" "s:skey" "$USEGMENT" "$CACHE" "$REPOSITORY" 1 10 $REDO
echo "----- Page before/after ---"
bash ../common/rank.sh "s,k:domain s,k:page x x" 1 20  "$USEGMENT" "src==\"web\"" "$CACHE" "$REPOSITORY" $REDO
#
### Path Calculation  #######################################################
echo "--Calculating CV based config---"
bash ../common/cal_path.sh "$CreateNode" "$USEGMENT" "$Grouping" "$CACHE" "$REPOSITORY" $REDO "$PSSTART" "$PSEND"
### Path Graph
echo "----- path of CV user ---"
bash ../common/aggr_path.sh 1 10 5 "$USEGMENT" "$CVDEPTH"
#

### Drill down ##################################
echo "----- drill down by browser ---"
bash ../common/seg_bw.sh "$USEGMENT -pp,n path -filt,yn 'p1==\"Imp:ADJUST JP (Site)\" && p2==\"Click:ADJUST JP (Site)\"' -endpp"
echo "----- drill down by keyword ---"
bash ../common/event.sh "skey" "s:skey" "$USEGMENT -pp,n path -filt,yn 'p1==\"Imp:ADJUST JP (Site)\" && p2==\"Click:ADJUST JP (Site)\"' -endpp" "$CACHE" "$REPOSITORY" 1 10 0
echo "----- dump log ---"
bash ../common/seg_dump.sh "$USEGMENT" 1 | head

### Reverse Path Calculation ################################################
echo "--Calculating Reverse CVpath based config---"
bash ../common/rev_path.sh "$CreateNode" "$USEGMENT" "$Grouping" "$CACHE" "$REPOSITORY" $REDO
### Reverse Path Graph
echo "----- reverse path of CV user ---"
bash ../common/aggr_rev_path.sh 1 10 5 "$USEGMENT" "$CVDEPTH"

exit

