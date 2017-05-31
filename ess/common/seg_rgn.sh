#!/bin/bash
###################################################################
# City & Country from profile
###########################################################################
Filter=$1

DIR_CURR=$(cd $(dirname $0); pwd)

UU=$(eval "aq_udb -cnt udb_cj:cj $Filter | awk /pkey/ | cut -d, -f 2")
### Browser count by uu in the segment and its percentage
eval "aq_udb -exp udb_cj:profile $Filter -c rgn \
        | aq_cnt -f+1 - -d s:rgn -kX - k1 rgn \
        | aq_ord -f+1 - -sort,dec i:2 \
        | aq_pp -f - -d s:region i:count -eval f:ratio '100.0*count/$UU' -sub region ${DIR_CURR}/matchtables/region.csv -c region count ratio"
#
exit
