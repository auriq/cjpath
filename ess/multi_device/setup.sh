################################################################################################################
# Setup
################################################################################################################
CURRDIR=$(cd $(dirname ${BASH_SOURCE:-$0}); pwd)
source $CURRDIR/usrconfig.inc

#
ess select local --label $NAME
### Simple apache log sample
ess category add multidev "$PROJECT_DIR/data/multi*.gz" --columnspec "s:memberid s:date s:domain s:pname s:bwos s:device s:gen i:age" --overwrite
ess summary
