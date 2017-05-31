#!/bin/bash
###################################################################
#  PV distribution 
######################################################################
Segment=$1
#
eval "aq_udb -clr udb_rslt:."
ess exec "aq_udb -exp udb_cj:profile -filt 'first_cv>0' $Segment -c pv -local \
	| aq_pp -ddef -f+1 - -d s:key -eval i:tcnt 1 -imp udb_rslt:rslt"
ess exec "aq_udb -exp udb_cj:profile -filt 'first_cv==0' $Segment -c pv -local \
	| aq_pp -ddef -f+1 - -d s:key -eval i:rcnt 1 -imp udb_rslt:rslt"
eval "aq_udb -exp udb_rslt:rslt -c key tcnt rcnt | aq_ord -f+1 - -sort i:1"
#
exit
