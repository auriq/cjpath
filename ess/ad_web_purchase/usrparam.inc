####################################################
#  Customizing Parameters 
####################################################

### Import 
PFROM="2014-04-20"; PTO="2014-04-23"; PREVIOUS=0; SAMPLING=1; szTZ="TZ='GMT-1'"
CACHE="$CACHEDIR/$NAME-$PFROM-$PTO-$PREVIOUS-$SAMPLING"

### Conversions ###################################
CONVERSION="src==\"purchase\" && domain==\"California\""

#### CAL PATH ###
PAT_IMP="-if -filt 'ent==\"imp\"' -eval ety '\"Imp:\"' -endif"
PAT_CLICK="-if -filt 'ent==\"click\"' -eval ety '\"Click:\"' -endif"
PAT_LISTING="-if -filt 'ent==\"listing\"' -eval ety '\"Listing:\"+pname' -endif"
PAT_ORGANIC="-if -filt 'ent==\"organic\"' -eval ety '\"Organic:\"' -endif"
PAT_REFERRER="-if -filt 'ent==\"referral\"' -eval ety '\"Ref:\"' -endif "
PAT_PAGE="-if -filt 'src==\"web\"' -eval ety domain -endif"
PAT_PAGE="-if -filt 'src==\"web\"' -eval ety '\"Page\"' -endif"
PAT_CV="-if -filt '$CONVERSION' -eval ety '\"CV\"' -endif"
INCFL="-filt 'ety!=\"\"'"
CreateNode="$PAT_PAGE $PAT_IMP $PAT_CLICK $PAT_ORGANIC $PAT_REFERRER $PAT_CV $INCFL"
PSSTART=""    # null= calculate path from the top
PSEND="CV"  # Tile CV point
Grouping="-if -filt 'src==\"web\"' -sub,pat domain domain_group.csv -endif"
#
CVDEPTH="profile.depth" # any events between first contact to CV
CVDEPTH="profile.imp" # impression only 
CVDEPTH="profile.days" # default: days 

#### User Segment #########
USEGMENT="-pp,n profile -filt,pr 'bwos==\"Tablet\"' -endpp"
USEGMENT="-pp,n profile -filt,pr 'bwos==\"SP\"' -endpp"
USEGMENT="-pp,post=next_key profile -filt 'bwos==\"PC\"' -goto next_pp -endpp"
USEGMENT="-pp,post=next_key cj -filt 'gender==\"M\"' -goto next_pp -endpp"
USEGMENT=""

