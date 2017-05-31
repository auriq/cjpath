#!/bin/bash
###################################################################
# Days to CV
######################################################################
Segment=$1
#
eval "aq_udb -clr udb_rslt:."
ess exec "aq_udb -exp udb_cj:profile -filt 'first_cv>0' $Segment -c days -local \
	| aq_pp -ddef -f+1 - -d s:key -eval i:tcnt 1 -imp udb_rslt:rslt"
eval "aq_udb -exp udb_rslt:rslt -c key tcnt | aq_ord -f+1 - -sort i:1"
#
exit
