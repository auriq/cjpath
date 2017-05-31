'use strict';

angular.module('auriqCJPath')
.filter('ellipsedString', function($timeout){
  function lenOfStr(str, btn_id){
    var e = document.getElementById('ruler-'+btn_id);
    var c;
    while (c = e.lastChild) e.removeChild(c);
    var text = e.appendChild(document.createTextNode(str));
    var width = e.offsetWidth;
    e.removeChild(text);
    return width;
  }
  function processStr(input, w, btn_id){
    if(w===0) return input;
    var icon_w = 16 + 16 + 50;
    var space_w = w - icon_w;
    var tmpstr = '';
    var tlen = input.length;
    for(var i=0; i<tlen; i++){
      tmpstr += input[i];
      var l = lenOfStr(tmpstr, btn_id);
l = (l<6*(i+1)) ? 6*(i+1) : l;
      if(l > space_w) return tmpstr.slice(0,-1)+'...';
    }
    return input;
  }
  return function(input, btn_id){
    if(input === undefined) return false;
    var btn_elm = document.getElementById(btn_id);
    var btn_elm_w = btn_elm.offsetWidth;
btn_elm_w = (btn_elm_w < 200) ? 200 : btn_elm_w;
    return processStr(input, btn_elm_w, btn_id);
  }
})
.directive('globalOptionsUserseg', function (colorsFactory, $timeout, $filter, urlsFactory, $http, $window) {
  return {
    restrict: 'EA',
    require:'^globalOptions',
    templateUrl: 'views/directives/templ-global-options-filters.html',
    scope: {
      globalparams : '=',
      settingobj   : '=',
      ischanged   : '=',
      isviewmode: '='
    },
    link: function ($scope, $element, $attrs, $parentCtrl) {

      $scope.$watchCollection(function(){
        return {
          'settingobj' : $scope.settingobj
        };
      }, function(){
        if($scope.settingobj !== undefined){
          $scope.list        = $scope.settingobj.list;
          if($scope.settingobj.curractive){
            if($scope.settingobj.curractive.activeCondName){
              $scope.selectedlbl = $scope.settingobj.curractive.activeCondName;
            }else{
              //$scope.selectedlbl = $scope.settingobj.curractive.list[0].condName;
              $scope.settingobj.currselected = angular.merge({}, $scope.settingobj.curractive, $scope.settingobj.curractive.list[0]);
              $scope.settingobj.currselected.activeCondId = $scope.settingobj.currselected.id;
              $scope.selectedlbl = $scope.settingobj.currselected.condName;
              delete $scope.settingobj.curractive;
              $scope.ischanged = true;
              buildFilterDiffArray();
            }
          }else{
            $scope.settingobj.currselected = angular.merge({}, $scope.settingobj.list[0], $scope.settingobj.list[0].list[0]);
            $scope.settingobj.currselected.activeCondId = $scope.settingobj.currselected.id;
            $scope.selectedlbl = $scope.settingobj.currselected.condName;
            $scope.ischanged = true;
            buildFilterDiffArray();
          }
        }
      });

      function buildFilterDiffArray(){
        if($scope.globalparams.filterdiff===undefined) $scope.globalparams.filterdiff = {};
        if($scope.globalparams.filterdiff.usrseg===undefined) $scope.globalparams.filterdiff.usrseg = [];
        if($scope.ischanged){
          var filterdiff = [];
          if($scope.settingobj.curractive){
            filterdiff.push({
              operation : 'update',
              names : ['condActiveFlg'],
              values : [0],
              id : $scope.settingobj.curractive.activeCondId
            });
          }
          filterdiff.push({
            operation : 'update',
            names : ['condActiveFlg'],
            values : [1],
            id : $scope.settingobj.currselected.activeCondId
          });
          if($scope.settingobj.curractive){
            filterdiff.push({
              operation : 'update',
              names : ['setActiveFlg'],
              values : [0],
              id : $scope.settingobj.curractive.ids.join(',')
            });
          }
          if($scope.settingobj.currselected.ids){
            filterdiff.push({
              operation : 'update',
              names : ['setActiveFlg'],
              values : [1],
              id : $scope.settingobj.currselected.ids.join(',')
            });
          }
          $scope.globalparams.filterdiff.usrseg = angular.copy(filterdiff);
//console.log($scope.globalparams.filterdiff.usrseg);
        }else{
          $scope.globalparams.filterdiff.usrseg = [];
        }
      };
      

      $scope.changeItemInList = function(parentitem, item, evtobj){
        $element.find('.dropdown-2ndlayer').find('li').removeClass('user-select');
        angular.element(evtobj.target).parent().addClass('user-select');
        $scope.settingobj.currselected = angular.copy(parentitem);
        $scope.settingobj.currselected.activeCondId   = item.id;
        $scope.settingobj.currselected.activeCondName = item.condName;
        $scope.selectedlbl = item.condName;
        $scope.ischanged = ($scope.settingobj.curractive) ? ($scope.settingobj.currselected.activeCondId !== $scope.settingobj.curractive.activeCondId) : true;
        buildFilterDiffArray();
      };

    },
    controller: function($scope, $element){
      $scope.icon = 'fa fa-users';
      $scope.layerpattern = 'double';
      $scope.btn_id       = 'dropdown_usersegment_bttn';
    }
  };
})
.directive('globalOptionsCvTag', function (colorsFactory, $timeout, $filter, urlsFactory, $http, $window) {
  return {
    restrict: 'EA',
    require:'^globalOptions',
    templateUrl: 'views/directives/templ-global-options-filters.html',
    scope: {
      globalparams : '=',
      settingobj   : '=',
      ischanged    : '=',
      isviewmode   : '='
    },
    link: function ($scope, $element, $attrs, $parentCtrl) {

      $scope.$watchCollection(function(){
        return {
          'settingobj' : $scope.settingobj
        };
      }, function(){
        if($scope.settingobj !== undefined){
          $scope.list        = $scope.settingobj.list;
          if($scope.settingobj.curractive === undefined){
            $scope.selectedlbl = $scope.list[0].condName; // if there's no active object, take first object in the list.
            $scope.settingobj.currselected = angular.copy($scope.list[0]);
            $scope.ischanged = true;
            buildFilterDiffArray();
          }else{
            $scope.selectedlbl = $scope.settingobj.curractive.condName;
          }
        }
      });

      function buildFilterDiffArray(){
        if($scope.globalparams.filterdiff===undefined) $scope.globalparams.filterdiff = {};
        if($scope.globalparams.filterdiff.cvcond===undefined) $scope.globalparams.filterdiff.cvcond = [];
        if($scope.ischanged){
          var filterdiff = [];
          if($scope.settingobj.curractive){
            filterdiff.push({
              operation : 'update',
              names : ['condActiveFlg'],
              values : [0],
              id : $scope.settingobj.curractive.id
            });
          }
          filterdiff.push({
            operation : 'update',
            names : ['condActiveFlg'],
            values : [1],
            id : $scope.settingobj.currselected.id
          });
          $scope.globalparams.filterdiff.cvcond = angular.copy(filterdiff);
//console.log($scope.globalparams.filterdiff.cvcond);
        }else{
          $scope.globalparams.filterdiff.cvcond = [];
        }
      };
      
      $scope.changeItemInList = function(item, evtobj){
        $element.find('.dropdown-menu').find('li').removeClass('user-select');
        angular.element(evtobj.target).parent().addClass('user-select');
        $scope.settingobj.currselected = angular.copy(item);
        $scope.selectedlbl = item.condName;
        $scope.ischanged = ($scope.settingobj.curractive) ? ($scope.settingobj.currselected.id !== $scope.settingobj.curractive.id) : true;
        buildFilterDiffArray();
      };

    },
    controller: function($scope, $element){
      $scope.icon = 'fa fa-dot-circle-o'
      $scope.layerpattern = 'single';
      $scope.btn_id       = 'dropdown_cv_bttn';
    }
  };
})
.directive('cachePicker', function (colorsFactory, $timeout, $filter, urlsFactory, $http, $window) {
  return {
    restrict: 'EA',
    templateUrl: 'views/directives/templ-cache-picker.html',
    scope: {
      cachelist: '=',
      //idx: '=',
      optlang:'=',
      ischanged : '=',
      globalparams : '=',
      isviewmode : '='
    },
    link: function ($scope, $element) {

      var maxDateRange, currimportedidx;
      //------------------------
      //
      //  Data Binding
      //
      //------------------------
      //$scope.$watch(function(){
      $scope.$watchCollection(function(){
          return {
            'globalparams' : $scope.globalparams,
         };
      }, function(){
        if($scope.cachelist !== undefined && $scope.globalparams!==undefined){
          if($scope.cachelist.length > 0){
            $scope.optglblcurr = $scope.globalparams.currentimported;
            //if($scope.userselect_cachelabel === undefined) initOptions();
            initOptions();
          }
        }
      //}, true);
      });

      $scope.changeCache = function(cachobj, index){
        $scope.ischanged = !cachobj.is_imported;
        $scope.userselect_cachelabel = cachobj.label || '';
        for(var key in cachobj) $scope.globalparams.userchoice[key] = cachobj[key] || $scope.globalparams.userchoice[key] || '';

        for(var i=0,len=$scope.cachelist.length; i<len; i++) $scope.cachelist[i].is_selected = false;
        cachobj.is_selected = true;

        $scope.$parent.$broadcast('globalParmaUpadate', $scope.globalparams);
      };

      function initOptions(){
        for(var i=0,len=$scope.cachelist.length; i<len; i++) if($scope.cachelist[i].is_imported){$scope.changeCache($scope.cachelist[i]); return false;} // find "is_imported"===true object and set it
        // if failed to find "is_imported"===true, then just pick up the first element.
        $scope.changeCache($scope.cachelist[0]);
      }

      $scope.searchFilter = function(item){
        var skey = $element.find('#cachesearchbykeywords').find('input').val() || '';
        if(skey==='') return true;
        var keywds = skey.split(' ');
        //--- in case of ||
        //for(var i=0,len=keywds.length; i<len; i++) if(item.label.indexOf(keywds[i]) !== -1) return true;
        //return false;
        //--- in case of &&
        var ismatch = true;
        for(var i=0,len=keywds.length; i<len; i++) if(item.label.indexOf(keywds[i]) === -1) ismatch=false;
        return ismatch;
      };

//      $scope.openDescription = function(cacheobj){
//        var nextstatus = !cacheobj.isShowDescription;
//        for(var i=0,len=$scope.cachelist.length; i<len; i++) $scope.cachelist[i].isShowDescription = false;
//        cacheobj.isShowDescription = nextstatus;
//      }

    },
    controller: function($scope, $element){
      $scope.btn_id = 'dropdown_cache_bttn';
    }
  };
})
.directive('globalOptions', function (colorsFactory, $timeout, $filter, urlsFactory, $http, API, $rootScope, $window) {
  return {
    restrict: 'EA',
    templateUrl: 'views/directives/templ-global-options.html',
    scope: {
      globalparams: '=',
      isparamavailable : '=',
      isviewmode: '=',
      words : '=',
      isglobalselectchanged : '='
    },
    link: function ($scope, $element) {
      $scope.isLoading = true;

      $scope.$watchCollection(function(){
        return {
          'globalparams' : $scope.globalparams
        };
      }, function(){
        if($scope.globalparams !== undefined){
          $scope.usersegGroupChange = [];
          init();
        }
      });

      $scope.$on('globalParmaUpadate', function (event, args) {
        $scope.globalparams = args;
      });

      function init(){
        //$scope.set_crnt = $scope.globalparams.currentimported;
        //$scope.set_slct = $scope.globalparams.userchoice;
        $scope.uid = $scope.globalparams.uid;
        $scope.lid = $scope.globalparams.lid;
        $scope.cachelist = $scope.globalparams.cachelist;
        $scope.amIChanged = $scope.isglobalselectchanged;
        API.getFilters({
          uid     : $scope.uid,
          lid     : $scope.lid,
          gettype : 'userconfigall',
        }).$promise.then(function(data){
          $scope.isLoading = false;
          $scope.usersetting = angular.copy(data.data);
        });
      }
      
    },
    controller: function($scope, $element){
      $scope.uid, $scope.lid, $scope.set_crnt={}, $scope.set_slct={}, $scope.usersegGroupChange=[], $scope.amIChanged = {};
      $scope.isChanged = false;
    }
  };
});
     

