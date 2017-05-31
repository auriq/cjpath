(function () {

'use strict';
angular.module('auriqCJPath')
.controller('ModalCacheFilesRefreshCtrl', ['$scope', '$uibModalInstance', 'items', 'API', '$q', function ($scope, $uibModalInstance, items, API, $q) {

  $scope.cachedirname = items.cachedirname;
  $scope.uid = items.uid;
  $scope.lid = items.lid;
  $scope.isLoading = true;
  $scope.filelist = [];
  $scope.isCollapsed = false;
  $scope.clearCacheType = 'withoutcj';

  function getCacheList(){
    API.getCacheQueue({
      uid          : $scope.uid,
      lid          : $scope.lid,
      cachedirname : $scope.cachedirname,
      contenttype  : 'cachefiles',
      oprtype      : 'get' // 'get' / 'remove'
    }).$promise.then(function(data){
      $scope.filelist = data.files;
      $scope.isLoading = false;
    });
  }

  getCacheList();

  $scope.clearCache = function(cleartype){
    $scope.isLoading = true;
    API.getCacheQueue({
      uid            : $scope.uid,
      lid            : $scope.lid,
      cachedirname   : $scope.cachedirname,
      clearcachetype : cleartype,
      contenttype    : 'cachefiles',
      oprtype        : 'remove' // 'get' / 'remove'
    }).$promise.then(function(data){
      $scope.filelist = data.files;
      $scope.isLoading = false;
    });
  };

/*
  $scope.remove = function(){
    //params.obj = JSON.stringify($scope.editObj);
    //API.getCacheQueue(params).$promise.then(function(data){
      $uibModalInstance.close();
    //});
  };
 * */

  $scope.cancel = function () {
    $uibModalInstance.dismiss('cancel');
  };

}])
.controller('ModalCacheListCtrl', ['$scope', '$uibModalInstance', 'items', 'API', '$q', function ($scope, $uibModalInstance, items, API, $q) {
//console.log(items);
  var params = angular.copy(items.params);
  params.uid = items.uid;
  params.lid = items.lid;
  $scope.editObj = angular.copy(items.obj);
  $scope.operationtype = items.type;
  $scope.columnsorder = ['id', 'cachedirname', 'label', 'type', 'start_date','end_date','prev_days','sample', 'memo'];

  $scope.save_modify = function(){
    params.obj = JSON.stringify($scope.editObj);
    API.getCacheQueue(params).$promise.then(function(data){
      $uibModalInstance.close();
    });
  };

  $scope.cancel = function () {
    $uibModalInstance.dismiss('cancel');
  };

}])
.controller('ModalCacheBatchCtrl', ['$scope', '$uibModalInstance', 'items', 'API', '$q', function ($scope, $uibModalInstance, items, API, $q) {
  $scope.isLoading = true;
  $scope.operationtype = items.type;
  $scope.editObj = items.obj || {};

/*
  $scope.selectedCalculationTypeIndex = 0;

  $scope.calculationType = [
    {
      label : 'one time',
      value : 'onetime'
    }
    {
      label : 'periodical',
      value : 'periodical'
    }
  ];
 * */

  function format_check_date(obj){
    var val = obj.value;
    if(val === ''){
      obj.iserror = true;
      obj.message = 'this field cannot be empty';
    }else{
      if(val.match(/^\d{4}([./-])\d{2}\1\d{2}$/)){
        var spl = val.split('-');
        var y = spl[0], m=spl[1], d = spl[2];
        if(y > 1970 && (m>=1 && m<=12) && (d>=1 && d<=31)){
          obj.iserror = false;
          obj.message = '';
        }else{
          obj.iserror = true;
          obj.message = 'invalid format';
        }
      }else{
        obj.iserror = true;
        obj.message = 'invalid format';
      }
    }
  }
  function format_check_int(obj){
    var val = obj.value;
    //if(obj.value.match(/\d/)){
    if(val === ''){
      obj.iserror = true;
      obj.message = 'this field cannot be empty';
    }else{
      if(val % 1 === 0){
        obj.iserror = false;
        obj.message = '';
      }else{
        obj.iserror = true;
        obj.message = 'invalid format';
      }
    }
  }
  function format_check_str(obj){
    obj.iserror = false;
    obj.message = '';
  }


  if($scope.operationtype === 'add'){
    var now = new Date();
    var m = (now.getMonth()+1).toString();
    var d = (now.getDate()).toString();
    m = (m.length === 1) ? '0'+m : m;
    d = (d.length === 1) ? '0'+d : d;
    var today = [now.getFullYear(), m, d].join('-');
    $scope.addObj = [
      {
        'column' : 'sdate',
        'label'  : 'date from',
        'value'  : '',
        'help'   : 'yyyy-mm-dd as ' + today,
        'iserror' : false,
        'formatcheck' : format_check_date
      },
      {
        'column' : 'edate',
        'label'  : 'date to',
        'value'  : '',
        'help'   : 'yyyy-mm-dd as ' + today,
        'iserror' : false,
        'formatcheck' : format_check_date
      },
      {
        'column' : 'bdays',
        'label'  : 'days go back',
        'value'  : '',
        'iserror' : false,
        'formatcheck' : format_check_int
      },
      {
        'column' : 'sample',
        'label'  : 'sample size',
        'value'  : '',
        'iserror' : false,
        'formatcheck' : format_check_int
      },
      {
        'column' : 'comment',
        'label'  : 'comment',
        'value'  : '',
        'iserror' : false,
        'formatcheck' : format_check_str
      }
    ];
    $scope.isLoading = false;
  }else{
    $scope.columnsorder = ['id', 'sdate', 'edate', 'bdays', 'sample', 'comment'];
    $scope.isLoading = false;
  }

  $scope.formatcheck = function(obj){
    obj.formatcheck(obj);
  }

  $scope.cancel = function () {
    $uibModalInstance.dismiss('cancel');
  };

  $scope.save_new = function(){
    var iserror = false;
    for(var i=0,len=$scope.addObj.length; i<len; i++){ 
      iserror = ($scope.addObj[i].iserror === true) ? true : iserror;
    }
    if(iserror){
      $window.alert('There is an error in your input.');
    }else{
      //saveRequest().then(function(){
      API.getCacheQueue({uid:uid, lid:lid, type:'add', obj : JSON.stringify($scope.addObj)}).$promise.then(function(data){
        $uibModalInstance.close();
      });
    }
  };

  $scope.save_modify = function(){
      API.getCacheQueue({uid:uid, lid:lid, type:'edit', obj : JSON.stringify($scope.editObj)}).$promise.then(function(data){
        $uibModalInstance.close();
      });
  }

/*
  function saveRequest(){
    var dfd = $q.defer();
    API.getCacheQueue({uid:uid, lid:lid, type:'add', obj : JSON.stringify($scope.addObj)}).$promise.then(function(data){
      dfd.resolve(data);
    });
    return dfd.promise;
  }
 * */


}])
.controller('CachesCtrl', ['$scope', '$q', '$rootScope', '$window', '$uibModal', 'pathsService', 'envService', 'API', '$location', '$route', '$timeout', function($scope, $q, $rootScope, $window, $uibModal, pathsService, envService, API, $location, $route, $timeout) {
  /* jshint validthis: true */
  var vm = this;
  vm.isAuthUser = $scope.masterFlg;
  vm.isLoading = true;

  ////////////////
  var uid=$scope.uid, lid=$scope.lid;
 


  vm.menus = [
    {
      label : 'Management',
      class : 'large-menu',
      description : '',
      contenttype : 'cachelist',
      params : {
        contenttype : 'cachelist', 
        oprtype : 'get',
        is_show_deleted : false
      }
    },
/*
    {
      label : 'Jobs',
      class : 'large-menu',
      contenttype : 'cachebatch',
      params : {
        contenttype : 'cachebatch',
        oprtype : 'get'
      }
    }
 * */
  ];


/*
  function getCache(){
    API.getCacheQueue({uid:uid, lid:lid, type:'get'}).$promise.then(function(data){
      $scope.cachelist = data.cachelist;
      vm.isLoading = false;
    });
  }

getCache();
vm.isNowAdding = false;
 */

  function getData(menuObj){
    vm.isLoading = true;
    vm.thead = []; vm.tbody = []; vm.templaterow = [];
    if('params' in menuObj){
      var params = menuObj.params;
      params.uid = uid; params.lid = lid;
      API.getCacheQueue(params).$promise.then(function(data){
        $scope.cachelist = data.cachelist;
        vm.isLoading = false;
      });
    }else{
      vm.isLoading = false;
    }
  }

  function menuInit(){
    for(var i=0,len=vm.menus.length; i<len; i++) vm.menus[i].class = vm.menus[i].class.replace(' active','');
  }

  vm.changeMenu = function(menuObj, index){
    menuInit();
    menuObj.class += ' active';
    vm.selectedItem = menuObj;
    getData(vm.selectedItem);
    vm.selectedItem.params.oprtype = 'get';
    $scope.isHelpCollapse = true;
  };


  vm.changeMenu(vm.menus[0], 0);

  vm.syncFromS3 = function(){
    var menuObj = angular.copy(vm.selectedItem);
    menuObj.params.oprtype = 'syncFromS3';
    getData(menuObj);
  };

  vm.changeIfDeletedItemsToShow = function(){
    var menuObj = angular.copy(vm.selectedItem);
    menuObj.params.oprtype = 'get';
    getData(menuObj);
  };

  vm.updateCacheListDbInfo= function(cacheObj, changeColumn){
    var id = cacheObj.id;
    var newval = '';
    if(changeColumn === 'isactive')   newval = cacheObj[changeColumn]==0 ? 1 : 0;
    if(changeColumn === 'deleted_at') newval = (cacheObj[changeColumn]) ? "NULL" : "NOW()";
    var params = angular.copy(vm.selectedItem.params);
    params.oprtype = 'edit';
    var obj = {id : id};
    obj[changeColumn] = newval;
    params.obj = JSON.stringify(obj);
/*
    params.editObj = JSON.stringify([{
      id   : id,
//      sets  : [changeColumn+'='+newval]
      //columns : [changeColumn],
      //values  : [newval]
    }]);
 * */
    vm.isLoading = true;
    API.getCacheQueue(params).$promise.then(function(data){
        $scope.cachelist = data.cachelist;
        vm.isLoading = false;
    });
  };

  $scope.open_modify_panel = function(type, cache /*pattern, projectObj*/){
    var params = angular.copy(vm.selectedItem.params);
    params.oprtype = 'edit';
    $scope.items = {
      type   : type, /* add or edit */
      obj    : cache,
      uid    : uid,
      lid    : lid,
      params : params
    };
    $uibModal.open({
      animation: true,
      templateUrl: (vm.selectedItem.contenttype==='cachelist') ? 'views/modals/cache-list.html' : 'views/modals/cache-batch.html',
      controller: (vm.selectedItem.contenttype==='cachelist') ? 'ModalCacheListCtrl' : 'ModalCacheBatchCtrl',
      size: 'lg',
      resolve: {
        items: function () {
          return $scope.items;
        }
      }
    }).result.then(function (selectedItem) {
//      console.log('succeed');
      //getCache();
      getData(vm.selectedItem);
      }, function () {
//        console.log('canceled');
        //getCache();
    });
  };

  
  vm.openCacheFilesModal = function(cache){
    $uibModal.open({
      animation: true,
      templateUrl: 'views/modals/cache-files-list-refresh.html',
      controller:  'ModalCacheFilesRefreshCtrl',
      size: 'lg',
      resolve: {
        items: function () {
          return {
            uid : uid,
            lid : lid,
            cachedirname : cache.cachedirname
          };
        }
      }
    }).result.then(function (selectedItem) { // when "close" is emited.
    }, function () { // when it's canceled
    });
  };

  $scope.remove = function(obj){
    if(obj.status === 'processing'){
      $window.alert('You cannot delete job/data on processing. Please try this later');
      return false;
    }else if(obj.status === 'requested'){
      if($window.confirm('Are you sure you want to cancel this job?')){
        API.getCacheQueue({uid:uid, lid:lid, type:'cancel', cid:obj.id}).$promise.then(function(data){
          getCache();
        });
      }
    }else if(obj.status === 'done'){
      if($window.confirm('Are you sure you want to remove this data?')){
        API.getCacheQueue({uid:uid, lid:lid, type:'remove', cid:obj.id, fname:obj.fname}).$promise.then(function(data){
          getCache();
        });
      }
    }
  };

  vm.logout = function(){
    pathsService.terminateMyself($scope.uid, $scope.lid);
    $rootScope.islogout = true;
/*
    vm.envStatus = {
      'isDoneInstance'  : false,
      'isImporting'     : false,
      'isReadyForApp'   : false
    };
 * */
  };

  $scope.switchProjects = function(nextproject){
    $scope.isLoading = true;
    pathsService.terminateMyself($scope.uid, $scope.lid).then(function(){
      $location.search({uid : nextproject});
      $rootScope.uid = nextproject;
      $route.reload();
    });
  };

  $timeout(function(){
    $scope.menu_h = angular.element(document.querySelector('.configWrapper.active')).height();
  });


}]);

})();
