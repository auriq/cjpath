(function () {
  'use strict';

  angular
    .module('auriqCJPath')
    .controller('PathsCtrl', PathsCtrl);

  PathsCtrl.$inject = ['$scope', '$q', '$rootScope', '$window', '$cookies', 'pathsService', 'envService', '$location', 'API', '$compile', '$timeout', '$route'];

  function PathsCtrl($scope, $q, $rootScope, $window, $cookies, pathsService, envService, $location, API, $compile, $timeout, $route) {
    var vm = this;
    vm.appStatus = {
      'isLoading' : true,
      'message'   : 'Loading...'
    };
    vm.otherUserStatus = {};
    vm.envStatus = {
      'isDoneInstance'  : null,
      'isImporting'     : null,
      'isImportFailed'  : null,
      'isReadyForApp'   : null
    };
    vm.globalParams = {};
    vm.optionsList  = {};
    vm.wordsDict    = {};
    vm.isGlobalSelectChanged = {};
    vm.isApplyBttnActive = true;
    vm.udbdStatus = {};
    vm.isCollapsed              = true;
    vm.isCollapsedViewSetting   = true;
    vm.isViewMode = false; // it's true when you are taken over by other user.
    vm.importFailedMessage = '';
    var isFilterUpdated = false;
    $scope.notifyFilterUpdated = 0;

    ////////////////
    var uid=$scope.uid, lid=$scope.lid, insttype=$scope.insttype, instnum=$scope.instnum;
    $scope.isDemoProject = $scope.projects.indexOf(uid)!==-1;
    runWhenLaunched();
 
    function runWhenLaunched(){
      pathsService.getWordDict(uid, lid).then(function(data){
        vm.wordsDict = angular.extend({}, vm.wordsDict, data.wordsdict);
      });
      envService.getStatus(uid, lid, 'instance').then(function(data){
        vm.lang = data.lang;
        vm.udbdStatus = data.insttype;
        vm.wordsDict = angular.extend({}, vm.wordsDict, data.wordsdict);
        vm.envStatus.isDoneInstance   = data.status === 'ready';
        vm.envStatus.isRunningInstance = data.status === 'creating';
        vm.envStatus.isImporting      = (vm.envStatus.isDoneInstance) ? data.isimporting : false;
        vm.envStatus.isReadyForApp    = (vm.envStatus.isDoneInstance) ? data.isimported : false; // if you are ready to view contents
        vm.otherUserStatus.isOccupied = ['me','notused'].indexOf(data.wdisused)===-1;
        if(vm.otherUserStatus.isOccupied){ // When someone else is using and it's not available to you,
          vm.envStatus.isReadyForApp = false;
          vm.appStatus.isLoading = false;
        }else{ // If you are the main user,
          becomeMainUser();
          if(vm.envStatus.isDoneInstance){ // instance is ready
            if(vm.envStatus.isImporting){ // you are importing data, you need to wait until done
              waitImporting();
            }else{ // imported, get global option parameters
              pathsService.getParameters(uid, lid).then(function(data){
                setServiceVars(data);
                initGlobalTemps();
                vm.appStatus.isLoading = false;
                $timeout(function(){ vm.helps(); }, 2000);
              });
            }
          }else{ // instance is not ready
            if(vm.envStatus.isRunningInstance){
              waitInstanceReady();
            }else{ // you need to setup instance first.
              envService.envSetupStart(uid, lid, insttype, instnum).then(function(){
                waitInstanceReady();
              });
            }
          }
        }
      });
    }

    function setServiceVars(data){
      vm.optionsList    = data.options;
      vm.contentsList   = pathsService.contentsList;
      $scope.indexOfContentList = parseInt($cookies.get('cindex')) || 0;
      $scope.activeContentObject = angular.copy(vm.contentsList[$scope.indexOfContentList]);
      vm.isAppLoaded = true;
    }
    function initGlobalTemps(){
      vm.globalParams.global = pathsService.returnInitGlobalParams(uid, lid, $scope.masterFlg, $scope.logtype, vm.optionsList);
      vm.globalParams.uid       = uid;
      vm.globalParams.lid       = lid;
      vm.globalParams.masterFlg = $scope.masterFlg;
      vm.globalParams.logtype   = $scope.logtype;
    }

    function waitInstanceReady(){
      vm.envStatus.isRunningInstance = true;
      envService.waitEssSetup(uid, lid).then(function(data){
        vm.udbdStatus = data.insttype;
        vm.envStatus.isDoneInstance = true; // now you wait users to select what to import
        vm.envStatus.isRunningInstance = false;
        pathsService.getParameters(uid, lid).then(function(data){
          setServiceVars(data);
          initGlobalTemps();
          if($scope.isDemoProject){
            envService.saveGlobalFilter(uid, lid, vm.globalParams).then(function(){
              envService.startImport(uid, lid, vm.globalParams).then(function(){ // after import, bash will run profile as well.
                waitImporting();
              });
            });
          }else{
            vm.appStatus.isLoading = false;
            $timeout(function(){ vm.helps(); }, 2000);
          }
        });
      });
    }

    function waitImporting(){
      vm.envStatus.isImporting   = true;
      vm.envStatus.isReadyForApp = false;
      vm.appStatus.isLoading     = true;
      envService.waitImporting(uid, lid).then(function(data){
        if(!vm.otherUserStatus.isOccupied){ // if you are the main user.
          if(data.status === 'done'){
            envService.checkIfFilterIsUpdated(uid, lid).then(function(){ // check config is updated and needs to reload
              vm.envStatus.isImporting   = false;
              vm.envStatus.isReadyForApp = true; // you are ready to view contents
              vm.appStatus.isLoading = false;
              // update udbd status
              envService.getStatus(uid, lid, 'instance').then(function(data){
                vm.udbdStatus = data.insttype;
                $timeout(function(){ vm.helps(); }, 2000);
              });
              if(isFilterUpdated && $scope.activeContentObject.type == 'global_summary') $scope.notifyFilterUpdated++;
            });
          }else if(data.status === 'failed'){
            vm.envStatus.isImportFailed   = true;
            vm.importFailedMessage = 'failed to import data.';
            vm.envStatus.isImporting   = false;
            vm.envStatus.isReadyForApp = false;
            vm.appStatus.isLoading = false;
            envService.getStatus(uid, lid, 'instance').then(function(data){
              vm.udbdStatus = data.insttype;
              $timeout(function(){ vm.helps(); }, 2000);
            });
          }
        }else{
          vm.envStatus.isImporting   = false;
          $timeout(function(){ vm.helps(); }, 2000);
        }
      });
      // in the meantime, you need to get parameters too
      pathsService.getParameters(uid, lid).then(function(data){
        setServiceVars(data);
        initGlobalTemps();
      });
    }

    //++++++++++++++++++
    //
    //  Data Import Section
    //
    //++++++++++++++++++
    //  it runs when "apply" button is clicked 
    $scope.dataImportApply = function(){
      var notyetimported = vm.envStatus.isReadyForApp;
      vm.envStatus.isImportFailed = false;
      vm.importFailedMessage   = '';
      vm.envStatus.isImporting = true;
      vm.appStatus.isLoading   = true;
      vm.envStatus.isReadyForApp = false;
      if(isFilterChanged()){
        // when filter condition changed
        if(vm.isGlobalSelectChanged.period === true || notyetimported === false){
          // when cache (data) changed OR not imported data yet. (=> need to import data first)
          envService.saveGlobalFilter(uid, lid, vm.globalParams).then(function(){
            envService.startImport(uid, lid, vm.globalParams).then(function(){ // after import, bash will run profile as well.
              waitImporting();
            });
          });
        }else{
          // when cache (data) is not changed (no need to import data)
          isFilterUpdated =  true;
          envService.saveGlobalFilter(uid, lid, vm.globalParams).then(function(){
            envService.startProfile(uid, lid).then(function(){
              waitImporting();
            });
          });
        }
      }else{
        // when filter condition is not changed, just import
        envService.startImport(uid, lid, vm.globalParams).then(function(){
          waitImporting();
        });
      }
      // init status obj if global selection is changed from imported setting.
      vm.isGlobalSelectChanged = {};
    }

    // Check if there's a change in filter condition (cv/usersegment)
    function isFilterChanged(){
      return true;
//      var ischanged = false;
//      for(var key in vm.isGlobalSelectChanged){
//        if(key!=='period') ischanged = (vm.isGlobalSelectChanged[key]===true) ? true : ischanged;
//      }
//      return ischanged;
    }

    $scope.filterConditionRefresh = function(){
      var notyetimported = vm.envStatus.isReadyForApp;
      vm.envStatus.isImportFailed = false;
      vm.importFailedMessage   = '';
      vm.envStatus.isImporting = true;
      vm.appStatus.isLoading   = true;
      vm.envStatus.isReadyForApp = false;
      envService.startProfile(uid, lid).then(function(){
        waitImporting();
      });
    };

    // Check if there's a change of global options
    //   yes => activate "apply" button
    //   no  => disactivate "apply" button
    // If there's no data imported, the "apply" button should be always active.
    $scope.$watch(function(){
      return {
        'isGlobalSelectChanged'   : vm.isGlobalSelectChanged,
        'envStatus.isReadyForApp' : vm.envStatus.isReadyForApp
      };
    }, function(){
      if(vm.envStatus.isReadyForApp===true) vm.isApplyBttnActive = Object.values(vm.isGlobalSelectChanged).indexOf(true) !== -1;
      if(vm.envStatus.isReadyForApp===false) vm.isApplyBttnActive = (vm.globalParams.global); // used to be "true"
    }, true);


    //++++++++++++++++++
    //
    //  Switch Contents (global / path pattern)
    //
    //++++++++++++++++++
    $scope.switchContent = function(indexOfContentList, contentObj){
      if(!vm.isAppLoaded) return false;
      if($scope.indexOfContentList !== indexOfContentList){
        $cookies.put('cindex', indexOfContentList);  
        $scope.indexOfContentList = indexOfContentList;
        $scope.activeContentObject = angular.copy(vm.contentsList[indexOfContentList]);
        vm.isAppLoaded = false;
        $timeout(function(){ vm.helps(); }, 2000);
      }
    };

    //++++++++++++++++++
    //
    //  Switch project (uid)
    //
    //++++++++++++++++++
    $scope.switchProjects = function(nextproject){
      vm.appStatus.isLoading     = true;
      vm.envStatus.isReadyForApp = false;
      pathsService.terminateMyself($scope.uid, $scope.lid).then(function(){
        vm.envStatus = {
          'isDoneInstance'  : false,
          'isImporting'     : false,
          'isReadyForApp'   : false
        };
        //$location.path($location.path()).search({uid : nextproject});
        $location.search({uid : nextproject});
        $rootScope.uid = nextproject;
        $route.reload();
      });
    }

    //++++++++++++++++++
    //
    //  Single User Management Section
    //
    //++++++++++++++++++
    $scope.takeOverUser = function(){
      vm.appStatus.isLoading = true;
      envService.getStatus(uid, lid, 'instance').then(function(data){
        vm.envStatus.isImporting = data.isimporting;
        if(vm.envStatus.isImporting){
          vm.appStatus.isLoading = false;
          alert(vm.wordsDict.ess_startup.instance.warningduringimporting);
        }else{
          vm.isViewMode = false;
          envService.takeOverUser(uid, lid).then(function(data){
            vm.appStatus.isLoading = false;
            vm.otherUserStatus.isOccupied = false;
            runWhenLaunched();
          });
        }
      });
    }
    
    function becomeMainUser(){
      vm.isViewMode = false;
      vm.stickypanelcss = {
        'width'  : $($window).width() + 'px',
        'height' : $($window).height() + 'px'
      };
      //angular.element($window).on('click', checkIfItIsHijacked);  // stop "single" mode, do not have to check if it's occupied by yourself.
    }

    function checkIfItIsHijacked(){
      envService.getStatus(uid, lid, 'checkifhijacked').then(function(data){
        var isOccupied = ['me','notused'].indexOf(data.wdisused)===-1;
        if(isOccupied){
          vm.isViewMode = true;
        }
      });
    }

    var help_t;
    function showHelpTip(e){
      clearTimeout(help_t);
      var targetdoc = angular.element(e.target).closest('.help-target');
      var targetelm = angular.element(targetdoc);
      var targetrect = targetdoc[0].getBoundingClientRect();
      var p_t = targetrect.top; //targetelm.prop('clientTop');
      var p_l = targetrect.left - 90; //targetelm.prop('clientLeft');
      var p_w = targetrect.width; //targetelm.outerWidth();
      var p_h = targetrect.height; //targetelm.outerHeight();
      if(p_h<10) p_h += 60;
      //var t_t = (p_t < 200) ? p_t + p_h + 10 : p_t - 10;
      var title = targetelm.data('help-title');
      var text  = targetelm.data('help-text');
      var posx  = targetelm.data('help-posx');
      var posy  = targetelm.data('help-posy');
      // tooltip
      var htip = angular.element(document.querySelector('#help-tip-wrapper'));
      htip.show();
      angular.element(htip.find('h3')).html(title);
      angular.element(htip.find('p')).html(text);
      htip.css({visibility:'hidden'});

      htip.removeClass(function(index, classname){ return (classname.match(/(^|\s)pos-\S+/g) || []).join(' ')});
      htip.addClass('pos-y-'+posy);
      htip.addClass('pos-x-'+posx);
      var t_h = htip.outerHeight();
      var t_w = htip.outerWidth();
      var t_t = (posy==='top') ? p_t - t_h - 10 : ((posy==='bottom') ? p_t + p_h + 10 : p_t);
      var t_l = (posy!=='side') ? ((posx==='left') ? p_l : p_l - t_w + 40) : ((posx==='left') ? p_l-t_w-10: p_l+p_w+16);
      htip.css({top : t_t, left:t_l, visibility:'visible'});
    }

    function showHelpTipEventManage(e){
      clearTimeout(help_t);
      help_t = setTimeout(function(){showHelpTip(e);}, 2000);
      //help_t = setTimeout(function(){showHelpTip(e);}, 0);
    }
    function hideHelpTip(e){
      clearTimeout(help_t);
      angular.element(document.querySelector('#help-tip-wrapper')).css({visibility:'hidden'});
      angular.element(document.querySelector('#help-tip-wrapper')).hide();
    }
    vm.helps = function(){
      API.getHelp({uid:$scope.uid, lid:$scope.lid}).$promise.then(function(data){
        var helparr = data.helpContent.paths;
        for(var i=0,len=helparr.length; i<len; i++){
          var tobj = helparr[i];
          var targetelm = angular.element(document.querySelector(tobj.selector));
          if(targetelm.length > 0){
            targetelm.addClass('help-target');
            targetelm.data('help-title', tobj.title);
            targetelm.data('help-text', tobj.text);
            targetelm.data('help-posx', tobj.pos_x);
            targetelm.data('help-posy', tobj.pos_y);
          }
        }
        angular.element(document.querySelectorAll('.help-target')).bind('mouseover', function(e){showHelpTipEventManage(e)});
        angular.element(document.querySelectorAll('.help-target')).bind('mouseleave', function(e){hideHelpTip(e)});
        angular.element(document.querySelectorAll('.help-target')).bind('click', function(e){hideHelpTip(e)});
      });
    };
    //angular.element(document).ready(function(){

    vm.logout = function(){
      pathsService.terminateMyself($scope.uid, $scope.lid);
      $rootScope.lang = vm.globalParams.global.lang;
      $rootScope.islogout = true;
      vm.envStatus = {
        'isDoneInstance'  : false,
        'isImporting'     : false,
        'isReadyForApp'   : false
      };
    };

  }
})();
