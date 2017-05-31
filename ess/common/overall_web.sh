#!/bin/bash
###################################################################
# Overall Web only 
######################################################################
Segment=$1
#
Total=$(eval "aq_udb -cnt udb_cj:cj")
TUU=$(echo $Total | awk '{print $2}' | cut -d, -f 2)
TPV=$(echo $Total | awk '{print $3}' | cut -d, -f 2)
SEG=$(eval "aq_udb -cnt udb_cj:cj $Segment")
SEGUU=$(echo $SEG | awk '{print $2}' | cut -d, -f 2)
SEGPV=$(echo $SEG | awk '{print $3}' | cut -d, -f 2)
CV=$(eval "aq_udb -cnt udb_cj:cj $Segment -pp profile -if -filt 'first_cv>0' -goto proc_key -else -goto next_key -endif -endpp")
CVUU=$(echo $CV | awk '{print $2}' | cut -d, -f 2)
CVPV=$(echo $CV | awk '{print $3}' | cut -d, -f 2)
echo "TotalUU,  TotalEvent,  SEGUU, SEGEvent, CVUU,  CVEvent"
echo "$TUU, $TPV, $SEGUU, $SEGPV, $CVUU, $CVPV"
exit
