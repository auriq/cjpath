#!/bin/bash
###################################################################
#  summary 
######################################################################
Segment=$1
Click="$2"
Listing="$3"

# Calculate Summary 
CV1=$(eval "aq_udb -cnt udb_cj:cj $Segment -pp profile -if -filt 'first_cv>0' -goto proc_key -else -goto next_key -endif -endpp | awk /pkey/ | cut -d, -f 2")
CV2=$(eval "aq_udb -cnt udb_cj:cj $Segment -pp profile -if -filt 'first_cv==0' -goto proc_key -else -goto next_key -endif -endpp | awk /pkey/ | cut -d, -f 2")
RT=$(echo "scale=4; 100*$CV1/($CV2+$CV1);" | bc)
echo "name, CV, NonCV, CVR"
echo "count_cv, $CV1, $CV2, $RT"
# Count depth
tDP1=$(eval "aq_udb -cnt udb_cj:cj -filt 'profile.depth>0 && profile.depth<=10' $Segment | awk /pkey/ | cut -d, -f 2")
tDP5=$(eval "aq_udb -cnt udb_cj:cj -filt 'profile.depth>0 && profile.depth<=50' $Segment | awk /pkey/ | cut -d, -f 2")
tDP15=$(eval "aq_udb -cnt udb_cj:cj -filt 'profile.depth>0 && profile.depth<=100' $Segment | awk /pkey/ | cut -d, -f 2")
[ $CV1 -gt 0 ] && tRP1=$(echo "scale=4; $tDP1/$CV1;" | bc) || tRP1=0
[ $CV1 -gt 0 ] && tRP5=$(echo "scale=4; $tDP5/$CV1;" | bc) || tRP5=0
[ $CV1 -gt 0 ] && tRP15=$(echo "scale=4; $tDP15/$CV1;" | bc) || tRP15=0
#
echo "pathlen_10, $tRP1, $rRP1"
echo "pathlen_50, $tRP5, $rRP5"
echo "pathlen_100, $tRP15, $rRP15"
# For Pie Chart 
if [ $CV1 -gt 0 ]; then
  tRef=$(eval "aq_udb -cnt udb_cj:cj -filt 'fg>0 && ent==\"referral\"' $Segment | awk /pkey/ | cut -d, -f 2")
  tRefR=$(echo "scale=4; $tRef/$CV1;" | bc)
  tClick=$(eval "aq_udb -cnt udb_cj:cj -filt 'fg>0 && ent==\"click\"' $Segment | awk /pkey/ | cut -d, -f 2")
  tClickR=$(echo "scale=4; $tClick/$CV1;" | bc)
  tListing=$(eval "aq_udb -cnt udb_cj:cj -filt 'fg>0 && ent==\"listing\"' $Segment | awk /pkey/ | cut -d, -f 2")
  tListingR=$(echo "scale=4; $tListing/$CV1;" | bc)
  tOrganic=$(eval "aq_udb -cnt udb_cj:cj -filt 'fg>0 && ent==\"organic\"' $Segment | awk /pkey/ | cut -d, -f 2")
  tOrganicR=$(echo "scale=4; $tOrganic/$CV1;" | bc)
  tImp=$(eval "aq_udb -cnt udb_cj:cj -filt 'fg>0 && ent==\"imp\"' $Segment | awk /pkey/ | cut -d, -f 2")
  tImpR=$(echo "scale=4; $tImp/$CV1;" | bc)
else 
  tRef=0;tRefR=0;tClick=0;tClickR=0;tListing=0;tListingR=0;tOrganic=0;tOrganicR=0;tImp=0;tImpR=0
fi
echo "pie_imp, $tImpR "
echo "pie_click, $tClickR "
echo "pie_listing, $tListingR "
echo "pie_organic, $tOrganicR "
echo "pie_ref, $tRefR "

exit


