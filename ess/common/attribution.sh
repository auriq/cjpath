#!/bin/bash
###################################################################
# Calculate Attribution Score
#  cst1 -- Sum of click till CV 
#  cst2 -- Sum of imp till CV or Timesatmp of last click
######################################################################
Model="$1"
Segment="$2"
Ckey="$3"
OUTPUT="$4"

CTR="0.00001"	# hypotethica click ratio of banner ad

## reset attribution and parameters
reset_score () {
eval "aq_udb -scn udb_cj:cj -pp cj -eval score 0 -endpp" 
eval "aq_udb -scn udb_cj:cj -pp profile -eval cst1 0 -eval cst2 0 -eval sum 0.0 -endpp" 
}
## Output result
output_score () {
eval "aq_udb -exp udb_cj:cj -filt 'score>0' $Segment -o,bin - -c domain page pname ref score ent \
	| aq_pp -f,bin - -d s:domain s:page s:pname s:ref f:score s:ent \
	  -if -filt 'ent==\"organic\"' -eval page '\"Organic Search\"' -eval domain page -eval pname page \
	  -endif \
          -c domain page pname ref ent score \
	| rw_csv -f+1 - -d $Ckey s,k:interactionType f,r:score -merg -sumx -sort score $OUTPUT"
}

####### Find First=1, Mid=2 and Last=3 Ad(src="ad") only #############
first_mid_last () {
reset_score
eval "aq_udb -scn udb_cj:cj \
	-pp cj \
		-if -filt 't<profile.first_cv && src==\"ad\"' -eval score 2 -endif -endpp \
	-pp cj \
		-if -filt 't<profile.first_cv && src==\"ad\"' \
		-eval score 1 -endif -goto next_pp -endpp \
	-pp cj \
		-if -filt 't<profile.first_cv && src==\"ad\"' \
		-eval profile.cst2 t -endif -endpp \
	-pp cj \
		-if -filt 't==profile.cst2 && src==\"ad\"' \
		-eval score '3' -goto next_pp -endif -endpp" 
# output result
eval "aq_udb -exp udb_cj:cj -filt 'score>0' $Segment -c domain page pname ref score \
	| aq_pp -f+1 - -d s:domain s:page s:pname s:ref i:pst \
	 -eval i:first 0 -eval i:mid 0 -eval i:last 0 \
	 -if -filt 'pst==1' -eval first 1 -endif \
	 -if -filt 'pst==2' -eval mid 1 -endif \
	 -if -filt 'pst==3' -eval last 1 -endif \
         -c domain page pname ref first mid last \
	| rw_csv -f+1 - -d $Ckey i,r:first i,r:mid i,r:last -merg -sort last"
}
####### Last Click only. Very basic  #############
last_click () {
reset_score
eval "aq_udb -scn udb_cj:cj \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\")' \
		-eval profile.cst2 t -endif -endpp \
	-pp cj \
		-if -filt 't==profile.cst2 && (ent==\"click\" || ent==\"listing\")' \
		-eval score '1.0' -goto next_pp -endif -endpp" 
output_score
}
####### Last Click only including Organic  #############
last_click_seo () {
reset_score
eval "aq_udb -scn udb_cj:cj \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\" || ent==\"organic\")' \
		-eval profile.cst2 t -endif -endpp \
	-pp cj \
		-if -filt 't==profile.cst2 && (ent==\"click\" || ent==\"listing\" || ent==\"organic\")' \
		-eval score '1.0' -goto next_pp -endif -endpp" 
output_score
}
####### Last Click with virtual click = consider last imp as the click #############
last_click_2 () {
reset_score
eval "aq_udb -scn udb_cj:cj \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\")' \
		-eval profile.cst2 t -endif -endpp \
	-pp cj \
		-if -filt 't<profile.first_cv && profile.cst2==0 && ent==\"imp\"' \
		-eval profile.cst2 t -endif -endpp \
	-pp cj \
		-if -filt 't==profile.cst2 && src==\"ad\"' \
		-eval score '1.0' -goto next_pp -endif -endpp"
output_score
}
####### Last Click including Organic and virtual click = consider last imp as the click #############
last_click_seo_2 () {
reset_score
eval "aq_udb -scn udb_cj:cj \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\" || ent==\"organic\")' \
		-eval profile.cst2 t -endif -endpp \
	-pp cj \
		-if -filt 't<profile.first_cv && profile.cst2==0 && ent==\"imp\"' \
		-eval profile.cst2 t -endif -endpp \
	-pp cj \
		-if -filt 't==profile.cst2 && (ent==\"imp\" || ent==\"click\" || ent==\"listing\" || ent==\"organic\")' \
		-eval score '1.0' -goto next_pp -endif -endpp" 
output_score
}
####### Click only. Even distribution #############
click_only () {
reset_score
eval "aq_udb -scn udb_cj:cj \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\")' \
		-eval score '1.0' -eval profile.cst1 'profile.cst1+1' -endif -endpp \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\")' \
		-eval score 'score/profile.cst1' -endif -endpp" 
output_score
}
####### Click Only with virtual click = consider last imp as the click #############
click_only_2 () {
reset_score
eval "aq_udb -scn udb_cj:cj \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\")' \
		-eval score '1.0' -eval profile.cst1 'profile.cst1+1' -endif -endpp \
	-pp cj \
		-if -filt 't<profile.first_cv && profile.cst1==0 && ent==\"imp\"' \
		-eval profile.cst2 t -endif -endpp \
	-pp cj \
		-if -filt 't==profile.cst2 && ent==\"imp\"' \
		-eval score '1.0' -eval profile.cst1 'profile.cst1+1' -endif -endpp \
	-pp cj \
		-if -filt 't<profile.first_cv && src==\"ad\"' \
		-eval score 'score/profile.cst1' -endif -endpp" 
output_score
}
####### Click only SEO. Even distribution including SEO as a click #############
click_only_seo () {
reset_score
eval "aq_udb -scn udb_cj:cj \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\" || ent==\"organic\")' \
		-eval score '1.0' -eval profile.cst1 'profile.cst1+1' -endif -endpp \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\" || ent==\"organic\")' \
		-eval score 'score/profile.cst1' -endif -endpp" 
output_score
}
####### Click only SEO_2. Even distribution including SEO as a click AND last imp as click  #############
click_only_seo_2 () {
reset_score
eval "aq_udb -scn udb_cj:cj \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\" || ent==\"organic\")' \
		-eval score '1.0' -eval profile.cst1 'profile.cst1+1' -endif -endpp \
	-pp cj \
		-if -filt 't<profile.first_cv && profile.cst1==0 && ent==\"imp\"' \
		-eval profile.cst2 t -endif -endpp \
	-pp cj \
		-if -filt 't==profile.cst2 && ent==\"imp\"' \
		-eval score '1.0' -eval profile.cst1 'profile.cst1+1' -endif -endpp \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"imp\" || ent==\"click\" || ent==\"listing\" || ent==\"organic\")' \
		-eval score 'score/profile.cst1' -endif -endpp" 
output_score
}
####### CTR weighted  #############
ctr_wgt () {
reset_score
eval "aq_udb -scn udb_cj:cj \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\")' \
		-eval score '1.0' -endif -endpp \
	-pp cj \
		-if -filt 't<profile.first_cv && ent==\"imp\"' \
		-eval score '$CTR' -endif -endpp \
	-pp cj -eval profile.sum 'profile.sum+score' -endpp \
	-pp cj -if -filt 'profile.sum>0' -eval score 'score/profile.sum' -endif -endpp" 
output_score
}
####### CTR weighted SEO  #############
ctr_wgt_seo () {
reset_score
eval "aq_udb -scn udb_cj:cj \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\" || ent==\"organic\")' \
		-eval score '1.0' -endif -endpp \
	-pp cj \
		-if -filt 't<profile.first_cv && ent==\"imp\"' \
		-eval score '$CTR' -endif -endpp \
	-pp cj -eval profile.sum 'profile.sum+score' -endpp \
	-pp cj -if -filt 'profile.sum>0' -eval score 'score/profile.sum' -endif -endpp"
output_score
}
####### First Click  #############
first_click () {
reset_score
eval "aq_udb -scn udb_cj:cj \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\")' \
		-eval profile.cst2 t -goto next_pp -endif -endpp \
	-pp cj \
		-if -filt 't==profile.cst2 && (ent==\"click\" || ent==\"listing\")' \
		-eval score '1.0' -goto next_pp -endif -endpp" 
output_score
}
####### First Click 2 consider first imp as a click in case no click found  #############
first_click_2 () {
reset_score
eval "aq_udb -scn udb_cj:cj \
	-pp cj \
		-if -filt 't<profile.first_cv && (ent==\"click\" || ent==\"listing\")' \
		-eval profile.cst2 t -goto next_pp -endif -endpp \
	-pp cj \
		-if -filt 't<profile.first_cv && profile.cst2==0 && ent==\"imp\"' \
		-eval profile.cst2 t -goto next_pp -endif -endpp \
	-pp cj \
		-if -filt 't==profile.cst2 && src==\"ad\"' \
		-eval score '1.0' -goto next_pp -endif -endpp" 
output_score
}

###########################################
###  MAIN 
###########################################
if [ "$Model" == "first_mid_last" ]; then first_mid_last; exit; fi
if [ "$Model" == "last_click" ]; then last_click; exit; fi
if [ "$Model" == "last_click_2" ]; then last_click_2; exit; fi
if [ "$Model" == "last_click_seo" ]; then last_click_seo; exit; fi
if [ "$Model" == "last_click_seo_2" ]; then last_click_seo_2; exit; fi
if [ "$Model" == "click_only" ]; then click_only; exit; fi
if [ "$Model" == "click_only_2" ]; then click_only_2; exit; fi
if [ "$Model" == "click_only_seo" ]; then click_only_seo; exit; fi
if [ "$Model" == "click_only_seo_2" ]; then click_only_seo_2; exit; fi
if [ "$Model" == "ctr_wgt" ]; then ctr_wgt; exit; fi
if [ "$Model" == "ctr_wgt_seo" ]; then ctr_wgt_seo; exit; fi
if [ "$Model" == "first_click" ]; then first_click; exit; fi
if [ "$Model" == "first_click_2" ]; then first_click_2; exit; fi
echo "Specify Attribution Model"
echo "      (last_click, last_click_2, click_only, click_only_2, click_only_seo, click_only_seo_2, ctr_wgt, ctr_wgt_seo, first_click, first_click_2)"
echo "Or First Mid or Last touch: first_mid_last"
exit
