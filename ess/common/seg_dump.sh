#!/bin/bash
###################################################################
# From CV-PATH Dump all/sampled log out 
###########################################################################
Filter="$1"
Limit=$2
 ### Browser count by uu and its percentage
eval "aq_udb -exp udb_cj:cj $Filter -lim_usr $Limit \
	-c t uid domain page pname ref skey ip bwos fg src ent \
	| aq_pp -f+1 - -d i:t s:uid s:dom s:pa s:pnm s:rf s:skey s:ip s:bwos is:num_to_cv s:src s:type \
	-eval s:date 'TimeToDate(t, \"%F:%H:%M:%S\")' -eval s:no '\"\"' \
	-eval s:camp no -eval s:site no -eval s:placement no -eval s:adname no -eval s:domain no \
	-eval s:page no -eval s:pagename no -eval s:referrer no -eval s:conversion no \
	-if -filt 'src==\"ad\"' -eval camp dom -eval site pa -eval adname pnm -eval placement rf -endif \
	-if -filt 'src==\"web\"' -eval domain dom -eval page pa -eval pagename pnm -eval referrer rf -endif \
	-if -filt 'src==\"purchase\"' -eval conversion dom -endif \
	-c date uid ip bwos src camp site placement adname domain page pagename referrer skey conversion type num_to_cv"
exit
