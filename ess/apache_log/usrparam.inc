####################################################
#  Customizing Parameters 
####################################################

### Import 
PFROM="2014-04-21"; PTO="2014-04-23"; PREVIOUS=0; SAMPLING=1; szTZ="TZ='GMT-1'"
CACHE="$CACHEDIR/$NAME"

### Conversions ###################################
CONVERSION="pname==\"Download\""

#### CAL PATH ###
PAT_LISTING="-if -filt 'ent==\"listing\"' -eval ety '\"Listing:\"+pname' -endif"
PAT_ORGANIC="-if -filt 'ent==\"organic\"' -eval ety '\"Organic:\"' -endif"
PAT_REFERRER="-if -filt 'ent==\"referral\"' -eval ety '\"Ref:\"' -endif "
PAT_PAGE="-if -filt 'src==\"web\"' -eval ety pname -endif"
PAT_CV="-if -filt '$CONVERSION' -eval ety '\"CV\"' -endif"
INCFL="-filt 'ety!=\"\"'"
CreateNode="$PAT_PAGE $PAT_ORGANIC $PAT_REFERRER $PAT_CV $INCFL"
PSSTART=""    # null= calculate path from the top
PSEND="CV"  # Tile CV point
Grouping="-if -filt 'src==\"web\"' -sub,pat page page.csv -endif"
Grouping=""
#
CVDEPTH="profile.depth" # any events between first contact to CV
CVDEPTH="profile.imp" # impression only 
CVDEPTH="profile.days" # default: days 

#### User Segment #########
USEGMENT="-pp,post=next_key cj -filt 'pname==\"Product\"' -goto next_pp -endpp"
USEGMENT="-pp,post=next_key profile -filt 'bwos==\"PC\"' -goto next_pp -endpp"
USEGMENT="-pp,post=next_key cj -filt 'ent==\"organic\"' -goto next_pp -endpp -pp,post=next_key cj -filt 'pname==\"Product\"' -goto next_pp -endpp"
USEGMENT="-pp,post=next_key cj -filt 'ent!=\"robot\"' -goto next_pp -endpp"

