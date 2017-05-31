#!/bin/bash
###################################################################
# Depth (events from first contact to CV)
######################################################################
Segment=$1
CKey=$2
if [ "$CKey" == "" ]; then CKey="depth"; fi
#
eval "aq_udb -clr udb_rslt:."
ess exec "aq_udb -exp udb_cj:profile -filt 'first_cv>0' $Segment -c $CKey -local \
	| aq_pp -ddef -f+1 - -d s:key -eval i:tcnt 1 -imp udb_rslt:rslt"
eval "aq_udb -exp udb_rslt:rslt -c key tcnt | aq_ord -f+1 - -sort i:1"
#
exit
