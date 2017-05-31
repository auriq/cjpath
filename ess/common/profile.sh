#!/bin/bash
###################################################################
# Calculate profile vector 
######################################################################
Conversion="$1"
EntryTypeDef="$2"
Device_Type="$3"

# reset profile vector 
eval "aq_udb -clr  udb_cj:profile" 
## Count SS 
eval "aq_udb -cnt udb_cj:cj \
                -pp cj -bvar vI1 0 -bvar vI2 0 -bvar vI3 0 \
                    	-if -filt 'src==3 && (t-vI1)>1800' -eval vI2 'vI2+1' -eval vI3 0 -endif \
			-if -filt 'src==3 && visit==0' -eval visit vI2 -eval vI3 'vI3+1' -eval pnum vI3 -eval visit vI2 -eval vI1 t -endif \
                -pp profile -eval ss vI2" 
## Count PV
eval "aq_udb -cnt udb_cj:cj \
                -pp cj -bvar vI2 0 -eval vI2 'vI2+1' \
                -pp profile -eval pv vI2" 
## Find first_cv CV time 
eval "aq_udb -scn udb_cj:cj \
                -pp cj -bvar vI1 0 \
                    -if -filt '$Conversion' -eval vI1 t -goto next_pp -endif \
                -pp profile -eval first_cv vI1" 
## Count CV 
eval "aq_udb -scn udb_cj:cj \
                -pp cj -bvar vI1 0 \
                    -if -filt '$Conversion' -eval vI1 'vI1+1' -endif \
                -pp profile -eval cv_cnt vI1" 
## Count days from first_cv Ad to CV
eval "aq_udb -scn udb_cj:cj \
                -pp profile -bvar vI2 0 -eval vI2 first_cv -endpp \
                -pp cj -bvar vI1 0 \
                     -if -filt 't<=vI2' -eval vI1 t -goto next_pp -endif -endpp \
                -pp profile -if -filt 'first_cv>0' -eval days '1+((first_cv-vI1)/86400)' -endif -endpp" 
## Count Ad & Web events from first_cv Ad to CV
eval "aq_udb -scn udb_cj:cj \
                -pp profile -bvar vI2 0 -eval vI2 first_cv -endpp \
                -pp cj -bvar vI1 0 \
                       -if -filt 't<vI2 && src<4' -eval vI1 'vI1+1' -endif -endpp \
                -pp profile -eval depth vI1 -endpp" 
## Count Ad imp and put it to profile.imp
eval "aq_udb -scn udb_cj:cj \
                -pp profile -bvar vI2 0 -eval vI2 first_cv -endpp \
                -pp cj -bvar vI1 0 \
                       -if -filt 't<vI2 && src==1 && ent==1' -eval vI1 'vI1+1' -endif -endpp \
                -pp profile -eval imp vI1 -endpp"
## Count Ad click and put it to profile.click
eval "aq_udb -scn udb_cj:cj \
                -pp profile -bvar vI2 0 -eval vI2 first_cv -endpp \
                -pp cj -bvar vI1 0 \
                       -if -filt 't<vI2 && src==1 && (ent==2 || ent==3)' -eval vI1 'vI1+1' -endif -endpp \
                -pp profile -eval click vI1 -endpp"
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
## Flag entry 12-Ad click 13-listing 10-organic 14-refer 0-other
eval "aq_udb -scn udb_cj:cj \
                -pp cj $EntryTypeDef -endpp"
## Device Type  PC/SmartPhone/Tablet/Others
eval "aq_udb -scn udb_cj:cj \
        -pp cj $Device_Type -endpp \
        -pp profile -eval bwos vS1 -endpp"
## City and Country (IP address at present) 
#eval "aq_udb -scn udb_cj:cj -pp cj -bvar vS1 '' -eval vS1 ip -goto next_pp -endpp -pp profile -eval rgn vS1 -endpp"
eval "aq_udb -scn udb_cj:cj -pp cj -eval profile.rgn 'IpToCountry(ToIP(ip))' -goto proc_bucket -endpp"
exit
