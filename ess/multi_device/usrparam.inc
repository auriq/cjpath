####################################################
#  Custom Parameters 
####################################################

### Import 
PFROM="2016-02-01"; PTO="2016-02-07"; PREVIOUS=0; SAMPLING=1; szTZ="TZ='GMT-1'"
CACHE="$CACHEDIR/$NAME"

### Conversions ###################################
CONVERSION="pname==\"Purchase complete\""

#### CAL PATH ###
PAT_PAGE="-if -filt 'src==\"web\"' -eval ety 'pname+\":\"+ent' -endif"
PAT_PAGE="-if -filt 'src==\"web\"' -eval ety ent -endif"
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
USEGMENT="-pp,post=next_key profile -filt 'bwos==\"PC\"' -goto next_pp -endpp"
USEGMENT=""


