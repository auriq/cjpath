#!/bin/bash
###################################################################
# Calculate profile vector 
######################################################################
Conversion="$1"

EntryTypeDef="-if -filt 'src==\"web\" && PatCmp(page,\"*cid=*\")' -eval ent '\"listing\"' -endif \
	-if -filt 'src==\"web\" && PatCmp(ref,\"*yahoo*\")' -eval ent '\"organic\"' -endif \
	-if -filt 'src==\"web\" && PatCmp(ref,\"*google*\")' -eval ent '\"referral\"' -endif"

Device_Type="-bvar vS1 '\"Other\"' \
	-if -filt 'PatCmp(bwos,\"*window*\",\"ncas\")' -eval vS1 '\"PC\"' -goto next_pp -endif \
	-if -filt 'PatCmp(bwos,\"*ipad*\",\"ncas\")' -eval vS1 '\"Tablet\"' -goto next_pp -endif \
	-if -filt 'PatCmp(bwos,\"*iphone*\",\"ncas\")' -eval vS1 '\"SP\"' -goto next_pp -endif" 

# reset profile vector 
eval "aq_udb -clr  udb_cj:profile" 
## Count session
eval "aq_udb -cnt udb_cj:cj \
                -pp cj -bvar vI1 0 -bvar vI2 0 -bvar vI3 0 \
                    	-if -filt 'src==\"web\" && (t-vI1)>1800' -eval vI2 'vI2+1' -eval vI3 0 -endif \
			-if -filt 'src==\"web\" && visit==0' -eval visit vI2 -eval vI3 'vI3+1' -eval pnum vI3 -eval visit vI2 -eval vI1 t -endif \
                -pp profile -eval ss vI2" 
ret=$?; [ $ret != 0 ] && exit $ret;
## Count page views
eval "aq_udb -cnt udb_cj:cj \
                -pp cj -bvar vI2 0 -eval vI2 'vI2+1' \
                -pp profile -eval pv vI2" 
ret=$?; [ $ret != 0 ] && exit $ret;
## Find first_cv: time of the first convertion 
eval "aq_udb -scn udb_cj:cj \
                -pp cj -bvar vI1 0 \
                    -if -filt '$Conversion' -eval vI1 t -goto next_pp -endif \
                -pp profile -eval first_cv vI1" 
ret=$?; [ $ret != 0 ] && exit $ret;
## Count number of conversions
eval "aq_udb -scn udb_cj:cj \
                -pp cj -bvar vI1 0 \
                    -if -filt '$Conversion' -eval vI1 'vI1+1' -endif \
                -pp profile -eval cv_cnt vI1" 
ret=$?; [ $ret != 0 ] && exit $ret;
## Count days from first_cv Ad to CV
eval "aq_udb -scn udb_cj:cj \
                -pp profile -bvar vI2 0 -eval vI2 first_cv -endpp \
                -pp cj -bvar vI1 0 \
                     -if -filt 't<=vI2' -eval vI1 t -goto next_pp -endif -endpp \
                -pp profile -if -filt 'first_cv>0' -eval days '1+((first_cv-vI1)/86400)' -endif -endpp" 
ret=$?; [ $ret != 0 ] && exit $ret;
## Count Ad & Web events from first_cv Ad to CV
eval "aq_udb -scn udb_cj:cj \
                -pp profile -bvar vI2 0 -eval vI2 first_cv -endpp \
                -pp cj -bvar vI1 0 \
                       -if -filt 't<vI2 && (src==\"web\" || src==\"ad\")' -eval vI1 'vI1+1' -endif -endpp \
                -pp profile -eval depth vI1 -endpp" 
ret=$?; [ $ret != 0 ] && exit $ret;
## Count Ad imp and put it to profile.imp
eval "aq_udb -scn udb_cj:cj \
                -pp profile -bvar vI2 0 -eval vI2 first_cv -endpp \
                -pp cj -bvar vI1 0 \
                       -if -filt 't<vI2 && ent==\"imp\"' -eval vI1 'vI1+1' -endif -endpp \
                -pp profile -eval imp vI1 -endpp"
ret=$?; [ $ret != 0 ] && exit $ret;
## Count Ad click and put it to profile.click
eval "aq_udb -scn udb_cj:cj \
                -pp profile -bvar vI2 0 -eval vI2 first_cv -endpp \
                -pp cj -bvar vI1 0 \
                       -if -filt 't<vI2 && (ent==\"click\" || ent==\"listing\")' -eval vI1 'vI1+1' -endif -endpp \
                -pp profile -eval click vI1 -endpp"
ret=$?; [ $ret != 0 ] && exit $ret;
## Put sequence number from top=1 
eval "aq_udb -scn udb_cj:cj \
                -pp profile -bvar vI2 0 -eval vI2 first_cv -endpp \
                -pp cj -bvar vI1 0 \
                        -if -filt 't<vI2' \
				-eval vI1 'vI1+1' \
				-eval fg vI1 \
			-elif -filt 't>vI2 && vI2>0' \
				-eval fg '-1' \
			-else \
				-eval fg 0 \
			-endif -endpp"
ret=$?; [ $ret != 0 ] && exit $ret;
## Categorize entity type
eval "aq_udb -scn udb_cj:cj \
                -pp cj $EntryTypeDef -endpp"
ret=$?; [ $ret != 0 ] && exit $ret;
## Categorize device Type  PC/SmartPhone/Tablet/Others
eval "aq_udb -scn udb_cj:cj \
        -pp cj $Device_Type -endpp \
        -pp profile -eval bwos vS1 -endpp"
ret=$?; [ $ret != 0 ] && exit $ret;
## City and Country (IP address at present) 
#eval "aq_udb -scn udb_cj:cj -pp cj -bvar vS1 '' -eval vS1 ip -goto next_pp -endpp -pp profile -eval rgn vS1 -endpp"
eval "aq_udb -scn udb_cj:cj -pp cj -eval profile.rgn 'IpToCountry(ToIP(ip))' -goto proc_bucket -endpp"
exit $?
