################################################################################################################
# Setup
################################################################################################################
CURRDIR=$(cd $(dirname ${BASH_SOURCE:-$0}); pwd)
source $CURRDIR/usrconfig.inc

#
ess select local --label $NAME
### Simple apache log sample
ess category add apachlog "$PROJECT_DIR/data/apach*.zip" --overwrite
ess summary
