################################################################################################################
# Setup
################################################################################################################
CURRDIR=$(cd $(dirname ${BASH_SOURCE:-$0}); pwd)
source $CURRDIR/usrconfig.inc

#
ess select local --label $NAME
### sample data for Ad Web Purchase log fusion  
ess category add weblog "$PROJECT_DIR/data/small-weblog*.gz" --columnspec "i:t i:seq i:gid i:mid s:ip s:st s:uid s:domain s:page \
	s:ref s:rpage s:bwos s:os s:rc" --overwrite
ess category add adlog "$PROJECT_DIR/data/small-adlog*.gz" --overwrite \
	--columnspec "s:EventID s:UserID i:EventTypeID s:EventDate s:EntityID s:PlacementID s:SiteID s:CampaignID \
	s:BrandID s:AdvertiserID s:AccountID s:SearchAdID s:AdGroupID s:IP s:CountryID s:StateID s:DMAID s:CityID"
ess category add ad_web_uid_lookup "$PROJECT_DIR/data/ad_web_uid_lookup.csv" --overwrite
ess category add purchase_log "$PROJECT_DIR/data/purchase_log.tsv" --columnspec "i:Num s:memid s:shop s:good s:date s:price \
	s:count s:category s:review s:web_cookie" --overwrite
ess category add member "$PROJECT_DIR/data/member.csv" --columnspec "s:memid s:gen s:age" --overwrite
ess summary
