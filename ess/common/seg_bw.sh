#!/bin/bash
###################################################################
# CV-PATH browser drill down from profile
###########################################################################
Filter=$1

UU=$(eval "aq_udb -cnt udb_cj:cj $Filter | awk /pkey/ | cut -d, -f 2")
### Browser count by uu in the segment and its percentage
eval "aq_udb -exp udb_cj:profile $Filter -c bwos \
        | aq_cnt -f+1 - -d s:bwos -kX - k1 bwos \
        | aq_ord -f+1 - -sort,dec i:2 \
        | aq_pp -f - -d s:bn i:cn -eval f:pc '100.0*cn/$UU' -c bn cn pc"
#
exit
