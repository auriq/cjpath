#!/bin/bash
###################################################################
# Overall AD & Web
######################################################################
Segment=$1
#
##### Overall ############
Total=$(eval "aq_udb -cnt udb_cj:cj")
TUU=$(echo $Total | awk '{print $2}' | cut -d, -f 2)
TPV=$(echo $Total | awk '{print $3}' | cut -d, -f 2)
SEG=$(eval "aq_udb -cnt udb_cj:cj $Segment")
SEGUU=$(echo $SEG | awk '{print $2}' | cut -d, -f 2)
SEGPV=$(echo $SEG | awk '{print $3}' | cut -d, -f 2)
CV=$(eval "aq_udb -cnt udb_cj:cj -filt 'profile.first_cv>0' $Segment")
CVUU=$(echo $CV | awk '{print $2}' | cut -d, -f 2)
CVPV=$(echo $CV | awk '{print $3}' | cut -d, -f 2)
echo "CVUU-Total, SEGUU-Total, TUU-Total, CVPV-Total, SEGPV-Total, TPV-Total"
echo "$CVUU, $SEGUU, $TUU, $CVPV, $SEGPV, $TPV"
#
##### break down by AD and Web ############
Tad=$(eval "aq_udb -cnt udb_cj:cj -filt 'src==\"ad\"'")
TadUU=$(echo $Tad | awk '{print $2}' | cut -d, -f 2)
TadPV=$(echo $Tad | awk '{print $3}' | cut -d, -f 2)
SEGad=$(eval "aq_udb -cnt udb_cj:cj -filt 'src==\"ad\"' $Segment")
SEGadUU=$(echo $SEGad | awk '{print $2}' | cut -d, -f 2)
SEGadPV=$(echo $SEGad | awk '{print $3}' | cut -d, -f 2)
CVad=$(eval "aq_udb -cnt udb_cj:cj -filt 'src==\"ad\" && profile.first_cv>0' $Segment")
CVadUU=$(echo $CVad | awk '{print $2}' | cut -d, -f 2)
CVadPV=$(echo $CVad | awk '{print $3}' | cut -d, -f 2)
Twb=$(eval "aq_udb -cnt udb_cj:cj -filt 'src==\"web\"'")
TwbUU=$(echo $Twb | awk '{print $2}' | cut -d, -f 2)
TwbPV=$(echo $Twb | awk '{print $3}' | cut -d, -f 2)
SEGwb=$(eval "aq_udb -cnt udb_cj:cj -filt 'src==\"web\"' $Segment")
SEGwbUU=$(echo $SEGwb | awk '{print $2}' | cut -d, -f 2)
SEGwbPV=$(echo $SEGwb | awk '{print $3}' | cut -d, -f 2)
CVwb=$(eval "aq_udb -cnt udb_cj:cj -filt 'src==\"web\" && profile.first_cv>0' $Segment")
CVwbUU=$(echo $CVwb | awk '{print $2}' | cut -d, -f 2)
CVwbPV=$(echo $CVwb | awk '{print $3}' | cut -d, -f 2)
echo "CVUU-Adv, SEGUU-Adv, TUU-Adv, CVPV-Adv, SEGPV-Adv, TPV-Adv"
echo "$CVadUU, $SEGadUU, $TadUU, $CVadPV, $SEGadPV, $TadPV"
echo "CVUU-Web, SEGUU-Web, TUU-Web, CVPV-Web, SEGPV-Web, TPV-Web"
echo "$CVwbUU, $SEGwbUU, $TwbUU, $CVwbPV, $SEGwbPV, $TwbPV"
#
exit
