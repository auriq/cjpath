####################################################
#  Configurataion parameters 
####################################################
NAME="ad_web_purchase"                               # project name
PROJECT_DIR=$(cd $(dirname ${BASH_SOURCE:-$0}); pwd) # home dircotry for this project

### Device Type #####################################
REPOSITORY="local" 
CACHEDIR="$PROJECT_DIR/cache"
REDO=0
TIMEZONE="TZ='GMT'"

## Gui Integration ##############
GUI_CHARCODE=UTF-8
GUI_TIMEZONE=GMT
GUI_SHOW_LOGTYPE=Adv:Web

