(function () {

'use strict';
angular.module('auriqCJPath')
.controller('ModalDbCtrl', ['$scope', '$uibModalInstance', 'items', 'API', '$q', function ($scope, $uibModalInstance, items, API, $q) {
  $scope.isLoading = true;
  items = angular.copy(items);
  $scope.operationtype = items.type;
  var rowObj = ($scope.operationtype === 'add') ? {} : items.rowObj;
  var fieldList = items.fields;
  var basics = items.basics;
  var tablename = items.table; 


  if($scope.operationtype === 'add'){
    for(var i=0,len=fieldList.length; i<len; i++){
      rowObj[fieldList[i]] = '';
    }
  }


  $scope.dbRowObj = rowObj;
  $scope.fieldList = fieldList;


  $scope.menuObj = items.menuObj || {};
  $scope.isLoading = false;
  $scope.isOkToSave = true;

  $scope.selectSuggest = function(rowObj, value){
    rowObj.value = value;
  };

  $scope.cancel = function(){
    $uibModalInstance.dismiss('cancel');
  };
  function buildParamsToSave(){
    var id = -1;
    var uid=basics.uid, lid=basics.lid;
    var colmnames = [], values=[];
    for(var key in $scope.dbRowObj){
      var isSkip  = false;
      var cname   = key;
      var cvalue  = $scope.dbRowObj[key];
      if($scope.operationtype==='add' && cname==='id') isSkip=true;
      if(cname==='id') id=cvalue;
      if(!isSkip){
        colmnames.push(cname);
        //values.push("'"+cvalue+"'");
        values.push(cvalue);
      }
    }
    return {
      uid : uid,
      lid : lid,
      oprtype : 'update',
      dbObj : JSON.stringify({
        tblname   : tablename,
        operation : ($scope.operationtype === 'add') ? 'insert' : 'update',
        names     : colmnames,
        values    : values,
        id        : id
      })
    }
  }
  $scope.save = function(){
    var params = buildParamsToSave();
    API.getDbs(params).$promise.then(function(data){
      $scope.isError = data.iserror;
      if(!$scope.isError){
        $uibModalInstance.close();
      }
    });
  };

}])
.controller('DevtoolsCtrl', ['$scope', '$q', '$rootScope', '$window', '$uibModal', 'pathsService', 'envService', 'API', 'API_BASE', 'urlsFactory', '$location', '$route', '$timeout', function($scope, $q, $rootScope, $window, $uibModal, pathsService, envService, API, API_BASE, urlsFactory, $location, $route, $timeout) {

  var vm = this;
  var uid=$scope.uid, lid=$scope.lid;
  vm.isAuthUser = $scope.masterFlg;
  vm.isLoading = true;
  vm.searchtext = '';

  vm.menus = [
    {
      label : 'Log Viewer',
      class : 'large-menu',
      description : 'You can see task.log and udbd logs.<br>- task.log : that is created by ess command when it catched an error.<br>- udbd log that is created when `ess udbd` family is executed<br><br>If you click the trash icon <i class="glyphicon glyphicon-trash"></i>, the log will be deleted. The application side will not take any backup or copy of it.',
    },
    {
      label : 'task.log',
      class : 'sub-menu',
      contenttype : 'log',
      params : {
        type : 'tasklog',
      }
    },
    {
      label : 'udbd log',
      class : 'sub-menu',
      contenttype : 'log',
      params : {
        type : 'udbdlist',
      }
    },
    {
      label : 'Cached Vars',
      class : 'large-menu',
/*
      description : 'This is mostly for debugging. Let system developers know if you find a bug!',
    },
    {
      label : 'stored condition',
      class : 'sub-menu',
*/
      contenttype : 'cachedvars',
      description : 'These are the variables that will be passed to bash script that executes essentia. If those variables are wrong or different from what users specify via GUI, the report users are seeing will be also wrong and there should be a bug in this application. <br><br>This is mostly for debugging. Let system developers know if you find a bug!',
      params : {
        type : 'cachedvarlist',
      }
    },
    {
      label : 'MySQL',
      class : 'large-menu',
      description : 'In "MySQL Viewer" section, you can see data stored in local database. You can also modify, add, and remove data from database.<br>The application does not check format of your input or if it is expected value or not. This is very <b>POWERFUL</b>. Do not run any operation unless you are 100% sure about it. With great power comes great responsibility.<br><br>In "Backups" section, you can take backup of local database (the backup file will be pushed to s3 bucket) by clicking <i class="glyphicon glyphicon-plus"></i> button. You can also restore datatabase with a backup file in s3 bucket by clicking <i class="glyphicon glyphicon-share-alt"></i> icon.'
    },
    {
      label : 'MySQL Viewer',
      class : 'sub-menu',
      contenttype : 'db',
      params : {
        type : 'all'
      }
    },
    {
      label : 'Backups',
      description : '',
      class : 'sub-menu',
      contenttype : 'dbbackups',
      params : {
        type : 'list'
      }
    }
  ];
  function menuInit(){
    vm.list = [];
    vm.searchtext = '';
    for(var i=0,len=vm.menus.length; i<len; i++){
      vm.menus[i].class = vm.menus[i].class.replace(' active','');
    }
  }
 
  vm.convertTimeSecToString = function(sec){
    return (sec === '-') ? sec : (new Date(sec * 1000)).toISOString();
  }

  function getData(menuObj){
    vm.isLoading = true;
    vm.thead = []; vm.tbody = []; vm.templaterow = [];
    if('params' in menuObj){
      var params = menuObj.params;
      if(menuObj.contenttype === 'log'){
        params.uid = uid; params.lid = lid; params.gettype = 'data'; // gettype could be either "data" or "dl", "rm"
        API.getLogs(params).$promise.then(function(data){
          vm.log    = data.log;
          //vm.tstamp = (new Date(data.timestamp * 1000)).toISOString();
          vm.tstamp = vm.convertTimeSecToString(data.timestamp);
          if('list' in data) vm.list = data.list;
          if('list' in data) vm.selectDeopdown = data.list[0] || '';
          vm.isLoading = false;
        });
      }else if(menuObj.contenttype === 'cachedvars'){
        params.uid = uid; params.lid = lid; params.gettype = 'data';
        API.getLogs(params).$promise.then(function(data){
          vm.cvlist = data.list;
          vm.isLoading = false;
        });
      }else if(menuObj.contenttype === 'db'){
        params.uid = uid; params.lid = lid; params.oprtype = 'get'; // oprtype could be either "get", "update", or?
        API.getDbs(params).$promise.then(function(data){
          vm.tbody = data.tbody;
          vm.thead = data.thead;
          if('tablelist' in data) vm.tablelist = data.tablelist;
          if('tablelist' in data) vm.selectDeopdown = vm.tablelist[0] || '';
          vm.isLoading = false;
        });
      }else if(menuObj.contenttype === 'dbbackups'){
        params.uid = uid; params.lid = lid; params.oprtype = 'dbbackups';
        API.getDbs(params).$promise.then(function(data){
          vm.dbbackuplist = data.list;
          vm.isLoading = false;
        });
      }
    }else{
      vm.isLoading = false;
    }
  }

  vm.changeMenu = function(menuObj, index){
    menuInit();
    menuObj.class += ' active';
    vm.selectedItem = menuObj;
    getData(vm.selectedItem);
  }


  vm.changeMenu(vm.menus[0], 0);

  vm.changeDropDown = function(item){
    vm.selectDeopdown = item;
    var menu = angular.copy(vm.selectedItem);
    if(vm.selectedItem.contenttype === 'log' && menu.params.type !== 'tasklog'){
      menu.params.type = 'udbdlog';
      menu.params.udbdlogname = item;
    }
    if(vm.selectedItem.contenttype === 'db'){
      vm.searchtext = '';
      menu.params.type     = 'bytablename';
      menu.params.tblname = item;
    }
    getData(menu);
  };

  vm.log_dl = function(){
    var params = angular.copy(vm.selectedItem.params);
    params.uid = uid; params.lid = lid; params.gettype = 'dl'; // gettype could be either "data" or "dl", "rm"
    location.href = API_BASE + '/' +  urlsFactory.getLogs + '?' + $.param(params);
  };

  vm.log_trash = function(){
    if($window.confirm('Are you sure you want to remove log?')){
      vm.isLoading = true;
      var params = angular.copy(vm.selectedItem.params);
      if(params.type !== 'tasklog'){
        params.type = 'udbdlog';
        params.udbdlogname = vm.selectDeopdown;
      }
      params.uid = uid; params.lid = lid; params.gettype = 'rm'; // gettype could be either "data" or "dl", "rm"
      API.getLogs(params).$promise.then(function(data){
        vm.isLoading = false;
        getData(vm.selectedItem);
      });
    }
  };

  vm.open_dbmodal = function(type, rowObj){
    $scope.items = {
      type   : type,
      rowObj : rowObj,
      fields : angular.copy(vm.thead),
      table : vm.selectDeopdown,
      basics : {
        uid : uid,
        lid : lid,
      }
    };
    $uibModal.open({
      animation: true,
      templateUrl: 'views/modals/devtool-db.html',
      controller: 'ModalDbCtrl',
      size: 'lg',
      resolve: {
        items: function () {
          return $scope.items;
        }
      }
    }).result.then(function (selectedItem) {
      vm.changeDropDown(vm.selectDeopdown);
      }, function () {
    });
  };

  vm.dbremove = function(rowObj){
    if($window.confirm('Are you sure you want to remove this row?')){
      var params = {
        uid       : uid,
        lid       : lid,
        oprtype   : 'delete',
        tblname   : vm.selectDeopdown,
        id        : rowObj.id || rowObj.id
      }
      API.getDbs(params).$promise.then(function(data){
        $scope.isError = data.iserror;
        vm.changeDropDown(vm.selectDeopdown);
      });
    }
  };

  vm.dbrestore = function(bkupfname){
    if($window.confirm('Are you sure you want to restore database with this backup file?')){
      vm.isLoading = true;
      API.getDbs({uid:uid,lid:lid,oprtype:'dbbackups',type:'restore',fname:bkupfname}).$promise.then(function(data){
        vm.dbbackuplist = data.list;
        vm.isLoading = false;
      });
    }
  };

  vm.dbtakebackup = function(){
    vm.isLoading = true;
    API.getDbs({uid:uid,lid:lid,oprtype:'dbbackups',type:'take'}).$promise.then(function(data){
      vm.dbbackuplist = data.list;
      vm.isLoading = false;
    });
  };

  vm.dbdownload = function(bkupfname){
    var params = {uid:uid,lid:lid,oprtype:'dbbackups',type:'download',fname:bkupfname};
    location.href = API_BASE + '/' +  urlsFactory.getDbs + '?' + $.param(params);
  };

  vm.logout = function(){
    pathsService.terminateMyself($scope.uid, $scope.lid);
    $rootScope.islogout = true;
    vm.envStatus = {
      'isDoneInstance'  : false,
      'isImporting'     : false,
      'isReadyForApp'   : false
    };
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
