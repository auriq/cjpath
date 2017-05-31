(function () {

'use strict';
angular.module('auriqCJPath')
.factory('configCommonFunction', ['formatCheckFactory', function configCommonFunction(formatCheckFactory){
  var funcs = {};
  funcs.inputValueCheck = function(uinputobj){
    if(uinputobj.format === 'DATE'){
      formatCheckFactory.check_date(uinputobj);
    }else if(uinputobj.format === 'NUMBER'){
      formatCheckFactory.check_int(uinputobj);
    }else{
      uinputobj.iserror = (uinputobj.value!=='');
    }
  };
  funcs.inputValuesCheckAll = function(parentpartsobj){
    if(!parentpartsobj.userinputs) return true;
    if(!parentpartsobj.isSelected) return true;
    for(var j=0,jlen=parentpartsobj.userinputs.length; j<jlen; j++){
      var uinputobj = parentpartsobj.userinputs[j];
      funcs.inputValueCheck(uinputobj);
      if(uinputobj.iserror) parentpartsobj.isSelected = false;
      // set "userinputok" 
      if(uinputobj.iserror) {parentpartsobj.userinputok = false; return false;}
    }
    return true;
  };
  funcs.builtConditionValueFromParts = function(filterobj, separator){
    var plist = [];
    for(var i=0,len=filterobj.length; i<len; i++) if(filterobj[i].type==='condselection') plist=filterobj[i].userselectpartslist;
    var condvalues = [];
    var condselect = [];
    for(var i=0,len=plist.length; i<len; i++){
      if(plist[i].isSelected){
        var tvalue = plist[i].condValue || '';
        for(var j=0,jlen=plist[i].userinputs.length; j<jlen; j++){
          var uinputobj = plist[i].userinputs[j];
          tvalue = tvalue.replace((new RegExp(uinputobj.valname, 'g')), uinputobj.value);
        }
        condselect.push(plist[i]);
        condvalues.push(tvalue);
      }
    }
    return {
      cvalue  : condvalues.join(separator),
      cselect : condselect
    };
  };
  funcs.setConditionValueFromParts = function(filterobj, partsSeparator){
    var selectedOnlyObj = funcs.builtConditionValueFromParts(filterobj, partsSeparator);
    var cvalue  = selectedOnlyObj.cvalue;
    var cselect = selectedOnlyObj.cselect;
    for(var i=0,len=filterobj.length; i<len; i++) if(filterobj[i].type === 'condvalue') filterobj[i].value = cvalue;
    for(var i=0,len=filterobj.length; i<len; i++) if(filterobj[i].type === 'condselection') filterobj[i].value = cselect;
  }
  funcs.getCondName = function(filterobj){
    for(var i=0,len=filterobj.length; i<len; i++) if(filterobj[i].type === 'condname') return filterobj[i].value;
    return '';
  }
  funcs.changeSelectStatus = function(filterobj, parts, partsSeparator){
    parts.isSelected = !parts.isSelected;
    var isInputOK = funcs.inputValuesCheckAll(parts);
    // configure condition value
    funcs.setConditionValueFromParts(filterobj, partsSeparator);
    var condname = funcs.getCondName(filterobj);
//    var condname = '';
//    for(var i=0,len=filterobj.length; i<len; i++) if(filterobj[i].type === 'condname') condname = filterobj[i].value;
    // return true/flase that can be set to "isOkToSave" variable
    return condname!=='' && isInputOK; // condition value could be empty. I removed 'cvalue !== '''
  };
  funcs.checkFormatOfUserInput = function(filterobj, parts, changedUserInputObj, partsSeparator){
    if(parts.isSelected){
      funcs.inputValueCheck(changedUserInputObj);
      if(changedUserInputObj.iserror){
        parts.isSelected = false;
        funcs.setConditionValueFromParts(filterobj, partsSeparator);  // configure condition value
        return true;
      }
      //if(!changedUserInputObj.iserror) funcs.setConditionValueFromParts(filterobj, partsSeparator);  // configure condition value
      funcs.setConditionValueFromParts(filterobj, partsSeparator);  // configure condition value
      var condname = funcs.getCondName(filterobj);
      return condname!=='' && !changedUserInputObj.iserror; // condition value could be empty. I removed 'cvalue !== '''
    }else{
      if(!changedUserInputObj.iserror) funcs.setConditionValueFromParts(filterobj, partsSeparator);  // configure condition value
      var condname = funcs.getCondName(filterobj);
      return condname!=='';
    }
  }
  return funcs;
}])
.controller('ModalOptionsCtrl', ['$scope', '$uibModalInstance', 'items', 'API', '$q', function ($scope, $uibModalInstance, items, API, $q) {
  $scope.operationtype = items.type;
  $scope.menuObj       = items.menuObj;
  $scope.filterObj     = (items.filterObj) ? angular.copy(items.filterObj.colms) : angular.copy(items.template);
  $scope.suggestlist   = items.suggestlist;
  $scope.isOkToSave    = false;
  $scope.essparam      = (items.menuObj.params) ? items.menuObj.params.esspara : '';

  $scope.selectSuggest = function(rowObj, value){
    rowObj.value = value;
  };

  $scope.$watch('filterObj', function(newval, oldval){
    var isEmpty = false;
    for(var i=0,len=newval.length; i<len; i++){
      if(newval[i].isEdit) isEmpty = (!newval[i].isEmptyOk && !newval[i].value) ? true : isEmpty;
    }
    $scope.isOkToSave = !isEmpty;
  }, true);
  
  $scope.cancel = function(){
    $uibModalInstance.dismiss('cancel');
  };

  function buildParamsToSave(){
    var basics = $scope.menuObj.params;
    var id = -1;
    var uid=basics.uid, lid=basics.lid, esspara=basics.esspara, gname=basics.pattern;
    var colmnames = [], values=[];
    for(var i=0,ilen=$scope.filterObj.length; i<ilen; i++){
      var isSkip  = false;
      var colmobj = $scope.filterObj[i];
      var cname   = colmobj.type;
      var cvalue  = colmobj.value;
      if(cname === 'custnoLogin') cvalue=lid;
      if(cname === 'custnoView') cvalue=uid;
      if($scope.operationtype==='add' && cname==='id') isSkip=true;
      if($scope.operationtype==='add' && cname==='essParaName') cvalue = $scope.essparam;
      if($scope.operationtype==='edit' && cname==='id') id = cvalue;
      if(!isSkip){
        colmnames.push(cname);
        values.push(cvalue);
      }
    }
    return {
      uid : uid,
      lid : lid,
      pattern : 'options',
      filterSelection : JSON.stringify([{
        operation : ($scope.operationtype === 'add') ? 'insert' : 'update',
        names     : colmnames,
        values    : values,
        id        : id
      }])
    }
  }
  $scope.save = function(){
    var params = buildParamsToSave();
    API.saveFilters(params).$promise.then(function(){
      $uibModalInstance.close();
    });
  };


}])
.controller('ModalNonGroupCtrl', ['$scope', '$uibModalInstance', 'items', 'API', '$q', 'configCommonFunction', function ($scope, $uibModalInstance, items, API, $q, configCommonFunction) {
  $scope.isLoading = true;
  var partsSeparator = ' && ';
  items = angular.copy(items);
  $scope.operationtype = items.type;
  $scope.filterObj = ($scope.operationtype === 'add') ? items.template : items.filterObj.colms || {};
  var optionparts = angular.copy(items.optionparts);
  $scope.isOwner = items.isOwner;
  $scope.menuObj = items.menuObj || {};
  $scope.isLoading = false;
  $scope.isOkToSave;


  $scope.checkIfNameIsEmpty = function(){
    var cname = '';
    for(var i=0,len=$scope.filterObj.length; i<len; i++) if($scope.filterObj[i].type === 'condname') {cname = $scope.filterObj[i].value; $scope.filterObj[i].iserror=cname==='';}
    $scope.isOkToSave = cname!=='';
  }

  $scope.checkFormatOfUserInput = function(filterObj, parts, uinput){
    $scope.isOkToSave = configCommonFunction.checkFormatOfUserInput(filterObj, parts, uinput, partsSeparator);
  }
  //$scope.changeSelectStatus = configCommonFunction.changeSelectStatus;
  $scope.changeSelectStatus = function(filterObj, parts){
    $scope.isOkToSave = configCommonFunction.changeSelectStatus(filterObj, parts, partsSeparator);
  }

  $scope.cancel = function(){
    $uibModalInstance.dismiss('cancel');
  };
  function buildParamsToSave(){
    var basics = $scope.menuObj.params;
    var id = -1;
    var uid=basics.uid, lid=basics.lid, esspara=basics.esspara, gname=basics.pattern;
    var colmnames = ['groupName','custnoLogin','custnoView','essParaName'], values=[gname,lid,uid,esspara];
    for(var i=0,ilen=$scope.filterObj.length; i<ilen; i++){
      var isSkip  = false;
      var colmobj = $scope.filterObj[i];
      var cname   = colmobj.type;
      var cvalue  = colmobj.value;
      if($scope.operationtype==='add' && cname==='id') isSkip=true;
      if($scope.operationtype==='add' && cname==='condid') cvalue = 0;
      if($scope.operationtype==='add' && cname==='condactiveflg') cvalue = 0;
      if(cname === 'condselection') cvalue = JSON.stringify(cvalue);
      if(cname==='id') id=cvalue;
      if(!isSkip){
        colmnames.push(cname);
        values.push(cvalue);
      }
    }
    return {
      uid : uid,
      lid : lid,
      type : $scope.menuObj.params.esspara,
      pattern : $scope.menuObj.params.pattern,
      filterSelection : JSON.stringify([{
        operation : ($scope.operationtype === 'add') ? 'insert' : 'update',
        names     : colmnames,
        values    : values,
        id        : id
      }])
    }
  }
  $scope.save = function(){
    var params = buildParamsToSave();
    API.saveFilters(params).$promise.then(function(){
      $uibModalInstance.close();
    });
  };

}])
.controller('ModalGroupCtrl', ['$scope', '$uibModalInstance', 'items', 'API', '$q', '$window', 'configCommonFunction', function ($scope, $uibModalInstance, items, API, $q, $window, configCommonFunction) {
  $scope.isLoading = true;
  var partsSeparator = ' ';
  items = angular.copy(items);
  $scope.operationtype = items.type;
  var optionparts = angular.copy(items.optionparts);
  $scope.filterObjArr = [];
  $scope.isOwner = items.isOwner;
  $scope.setName = ($scope.operationtype === 'add') ? null : items.filterObj.setname;
  $scope.setComment = (items.filterObj) ? items.filterObj.memo : '';
  var setName = $scope.setName;
  var setId = ($scope.operationtype === 'add') ? null : items.filterObj.setid;
  var setIsActive = ($scope.operationtype === 'add') ? 0 : items.filterObj.isActive;
  $scope.menuObj = items.menuObj || {};
  $scope.isOkToSave = false;
  $scope.valueErrorStatus = {};
  $scope.setNameIsError = false;
  var dboperation = [];

  $scope.addCondition = function(){
    var temp = angular.copy(items.template);
    $scope.filterObjArr.push(temp);
    // check if it's ok to save
    $scope.valueErrorStatus[$scope.filterObjArr.length-1] = true;
    setIsOkToSave();
  };


  if($scope.operationtype === 'add'){
    $scope.addCondition();
    $scope.isLoading = false;
    setIsActive = 0;
  }else{
    $scope.isLoading = true;
    var params = $scope.menuObj.params;
    setId = items.filterObj.setid;
    setIsActive = items.filterObj.isActive;
    params.setid = setId;
    var filterObj = [];
    API.getFilters(params).$promise.then(function(data){
      var tbody = data.data.tbody;
      $scope.valueErrorStatus = {
        gname : false
      };
      for(var i=0,len=tbody.length; i<len; i++){
        filterObj.push(tbody[i].colms);
        
        $scope.valueErrorStatus[i] = false;
      }
      $scope.filterObjArr = filterObj;
      $scope.isLoading = false;
    });
  }

  //$scope.checkFormatOfUserInput = configCommonFunction.checkFormatOfUserInput;
  $scope.checkFormatOfUserInput = function(filterObj, parts, uinput){
    $scope.isOkToSave = configCommonFunction.checkFormatOfUserInput(filterObj, parts, uinput, partsSeparator);
  }
  $scope.changeSelectStatus = function(filterObj, parts){
    $scope.isOkToSave = configCommonFunction.changeSelectStatus(filterObj, parts, partsSeparator);
  }

  function setIsOkToSave(){
    var svals = Object.values($scope.valueErrorStatus);
    var skeys = Object.keys($scope.valueErrorStatus);
    $scope.isOkToSave = svals.indexOf(true)===-1 && skeys.indexOf('gname')!==-1 && skeys.length > 1;
  }

  $scope.removeCondition = function(rowObj, index){
    var isEmpty = true;
    for(var i=0,len=rowObj.length; i<len; i++){
      if(['condname', 'condvalue'].indexOf(rowObj[i].type)!==-1) isEmpty = rowObj[i].value ? false : isEmpty;
    }
    var isOkToRemove = false;
    if(isEmpty){
      isOkToRemove = true;
    }else{
      if($window.confirm('Are you sure to remove this condition set? (it will not be actually removed until you press "save" button.)')) isOkToRemove = true;
    }
    if(isOkToRemove){
      var removedObj = $scope.filterObjArr.splice(index, 1);
      var removeid = (function(){for(var i=0,len=removedObj[0].length; i<len; i++) if(removedObj[0][i].type=='id') return removedObj[0][i].value; })();
      dboperation.push({
        operation : 'delete',
        ids       : [removeid]
      });
      // check if it's ok to save
      delete $scope.valueErrorStatus[index];
      setIsOkToSave();
    }
  };
  $scope.selectSuggest = function(index, colObj, value){
    colObj.value = value;
    $scope.valueErrorStatus[index] = false;
    setIsOkToSave();
  };
  $scope.checkValue = function(val, name, obj){
    $scope.valueErrorStatus[name] = !val;
    setIsOkToSave();
    if(obj){ // condition name
      obj.isError = !val;
    }else{ // set name
      setName = val;
      $scope.setNameIsError = !val;
    }
  };

  $scope.conditionIsChanged = function(){
    setIsOkToSave();
  };

  $scope.commentIsChanged = function(comment){
    $scope.setComment = comment;
    setIsOkToSave();
  };

/*
  if($scope.filterObjArr.length === 0){
    $scope.addCondition();
  }
 * */

  $scope.cancel = function(){
    $uibModalInstance.dismiss('cancel');
  };
  function buildParamsToSave(){
    var basics = $scope.menuObj.params;
    var uid=basics.uid, lid=basics.lid, esspara=basics.esspara, gname=basics.pattern;
    for(var i=0,ilen=$scope.filterObjArr.length; i<ilen; i++){
      var colmnames = ['groupName','custnoLogin','custnoView','essParaName'], values=[gname,lid,uid,esspara];
      var id,setactiveflg;
      for(var j=0,jlen=$scope.filterObjArr[i].length; j<jlen; j++){
        var isSkip  = false;
        var colmobj = $scope.filterObjArr[i][j];
        var cname   = colmobj.type;
        var cvalue  = colmobj.value;
        if($scope.operationtype==='edit' && cname==='id') id=cvalue;
        if(cname==='id') isSkip=true;
        if($scope.operationtype==='edit' && cname==='setid') cvalue = setId;
        if(cname==='condid') cvalue = i+1;
        if(cname==='setactiveflg'){
          cvalue = setIsActive;
        }
        if(cname === 'condselection') cvalue = JSON.stringify(cvalue);
        if(cname === 'condactiveflg'){
          if($scope.menuObj.params.esspara === 'usrseg'){
            //if($scope.operationtype==='edit') cvalue = (i===0) ? 1 : 0;
            if($scope.operationtype==='add' || setIsActive==0) cvalue = 0;
          }else{
            cvalue = -1;
          }
        }
        if(cname === 'setname') cvalue=setName;
        if(cname === 'memo') cvalue=$scope.setComment;
        if(!isSkip){
          colmnames.push(cname);
          values.push(cvalue);
        }
      }
      dboperation.push({
        operation : ($scope.operationtype==='edit' && id!=='new') ? 'update' : 'insert',
        names     : colmnames,
        values    : values,
        id        : id
      });
    }
    return {
      uid : uid,
      lid : lid,
      type : $scope.menuObj.params.esspara,
      pattern : $scope.menuObj.params.pattern,
      filterSelection : JSON.stringify(dboperation)
    }
  }
  $scope.save = function(){
    $scope.isLoading = true;
    var params = buildParamsToSave();
    dboperation = [];
    API.saveFilters(params).$promise.then(function(){
      $uibModalInstance.close();
    });
  };
}])
.controller('ConfigsCtrl', ['$scope', '$q', '$rootScope', '$window', '$uibModal', 'pathsService', 'envService', 'API', '$location', '$route', '$timeout', function($scope, $q, $rootScope, $window, $uibModal, pathsService, envService, API, $location, $route, $timeout) {

  var vm = this;
  var uid=$scope.uid, lid=$scope.lid;
  vm.isAuthUser = $scope.masterFlg;
  vm.isLoading = true;
  var suggestlist = [];
  var optionparts = [];

  vm.menus = [
    {
      label : 'Configuration',
      class : 'large-menu',
      description : 'Configuration setting for overall report application.'
    },
    {
      label : 'Conversion',
      class : 'sub-menu',
      description : 'Conversion(CV) condition setting. Multipart CV conditions are joined as a logical AND',
      params : {
        type      : 'radio',
        gettype   : 'detail',
        pattern   : 'global',
        esspara   : 'cvcond',
        setid     : -1,
        popupmode : ''
      }
    },
    {
      label : 'User Segment',
      class : 'sub-menu',
      params : {
        type      : 'radio',
        gettype   : 'detail',
        pattern   : 'global',
        esspara   : 'usrseg',
        setid     : -1,
        popupmode : ''
      }
    },
/*
    {
      label  : 'Filter',
      description : 'フィルタ条件の設定画面。条件は複数選択可能。アプリケーションユーザーの選択不可。',
      class  : 'sub-menu',
      params : {
        type      : 'checkbox',
        pattern   : 'global',
        esspara   : 'filter',
        setid     : -1,
        popupmode : ''
      }
    },
 * */
    {
      label : 'Touchpoint',
      description : '',
      class : 'sub-menu',
      params : {
        gettype   : 'detail',
        type      : 'radio',
        pattern   : 'cvpath',
        esspara   : 'pathtpdef',
        setid     : -1,
        popupmode : ''
      }
    },
    {
      label : 'Definition',
      class : 'large-menu',
      description : 'In this section, can define conversion goal and user segment conditions that are used in your aconfigurations.',
    },
    {
      label : 'Conversion',
      class : 'sub-menu',
      params : {
        gettype    : 'options',
        is_show_deleted : false,
        esspara   : 'cvcond',
      }
    },
    {
      label : 'User Segment',
      class : 'sub-menu',
      params : {
        gettype    : 'options',
        is_show_deleted : false,
        esspara   : 'usrseg',
      }
    }
  ];




/*
    {
      label : 'Filter',
      description : 'フィルタ条件の設定。条件は複数選択可能。アプリケーションユーザーの選択不可。',
      class : 'sub-menu',
      params : {
        type      : 'checkbox',
        pattern   : 'cvpath',
        esspara   : 'pathfilter',
        setid     : -1,
        popupmode : ''
      }
    }
*/

/*
  if(vm.isAuthUser){
    vm.menus.push({
      label : 'Parts',
      class : 'large-menu',
      description : 'In this section, you can create "parts" that is used in other section as "CV" or "User segment". In CV or User segment section, user can choose parts and build their configuration.',
      params : {
        gettype    : 'options',
        is_show_deleted : false,
        esspara   : 'cvcond',
        essparams : [
          {
            label    : 'CV',
            value    : 'cvcond',
            isactive : true
          },
          {
            label    : 'User Segment',
            value    : 'usrseg',
            isactive : false
          }
        ]
      }
    });
  }
*/

  function menuInit(){
    for(var i=0,len=vm.menus.length; i<len; i++){
      vm.menus[i].class = vm.menus[i].class.replace(' active','');
    }
  }
 
  function getData(menuObj){
    vm.isLoading = true;
    vm.thead = []; vm.tbody = []; vm.templaterow = [];
    if('params' in menuObj){
      var params = menuObj.params;
      params.uid = uid; params.lid = lid; /*params.gettype = 'detail';*/
      API.getFilters(params).$promise.then(function(data){
        vm.tbody = data.data.tbody;
        vm.thead = data.data.thead;
        vm.addpattern = data.data.addpattern;
        vm.templaterow = data.data.templaterow;
        vm.headerlabels = data.data.dblabels || {};
        suggestlist = data.data.suggest || [];
        optionparts = data.data.parts || [];
        vm.isLoading = false;
      });
    }else{
      vm.isLoading = false;
    }
  }

  vm.changeMenu = function(menuObj, index){
    menuInit();
    menuObj.class += ' active';
    vm.selectedItem = menuObj;
    getData(vm.selectedItem);
    $scope.isHelpCollapse = true;
  };

//  vm.changeFilterTypeInPartsPage = function(essparaobjlist, essparaobj){
//    for(var i=0,len=essparaobjlist.length; i<len; i++) essparaobjlist[i].isactive = false;
//    essparaobj.isactive = true;
//    vm.selectedItem.params.esspara = essparaobj.value;
//    getData(vm.selectedItem);
//  };


  vm.changeMenu(vm.menus[0], 0);

  $timeout(function(){
    $scope.menu_h = angular.element(document.querySelector('.configWrapper.active')).height();
  });

  vm.modal = function(type, rowObj){
    $scope.items = {
      type       : type,
      filterObj  : rowObj,
      template   : vm.templaterow,
      menuObj    : vm.selectedItem,
      suggestlist: suggestlist,
      optionparts : optionparts,
      isOwner     : vm.isAuthUser
    };
    var isGroupMenu = (['usrseg', 'pathtpdef'].indexOf(vm.selectedItem.params.esspara) !== -1);
    var isOptions   = vm.selectedItem.params.gettype === 'options';
    $uibModal.open({
      animation: true,
      templateUrl: (isOptions) ? 'views/modals/config-filter-options.html' : ((isGroupMenu) ? 'views/modals/config-filter-group.html' : 'views/modals/config-filter-nongroup.html'),
      controller:  (isOptions) ? 'ModalOptionsCtrl' : ((isGroupMenu) ? 'ModalGroupCtrl' : 'ModalNonGroupCtrl'),
      size: 'lg',
      resolve: {
        items: function () {
          return $scope.items;
        }
      }
    }).result.then(function (selectedItem) {
      getData(vm.selectedItem);
    }, function () {
    });
  };

  $scope.optionssearchkeyword = '';
  $scope.searchFilter = function(arg){
//    if(vm.selectedItem.label !== 'Parts') return true; // currently, this filter is available only for "Parts" section
    var skey = document.getElementById('optionssearchkeyword').value || '';
    var item = arg.colms;
    if(skey==='') return true;
    var keywds = skey.split(' ');
    //--- in case of ||
    //for(var i=0,len=keywds.length; i<len; i++) if(item.label.indexOf(keywds[i]) !== -1) return true;
    //return false;
    //--- in case of &&
    var ismatch = {};
    for(var k=0,klen=item.length; k<klen; k++) for(var i=0,len=keywds.length; i<len; i++) if(item[k].value.indexOf(keywds[i]) !== -1) ismatch[keywds[i]] = true;
    return keywds.length === Object.keys(ismatch).length;
  };

  vm.remove = function(rowObj){
    if($window.confirm('Are you sure to remove this ?')){
      var params = {};
      if(!rowObj.setname){
        params = {
          uid : uid,
          lid : lid,
          type    : vm.selectedItem.params.esspara,
          pattern : vm.selectedItem.params.pattern || vm.selectedItem.params.gettype,
          filterSelection : JSON.stringify([{
            operation : 'delete',
            ids : [rowObj.colms[0].value]
          }])
        };
      }else{
        params = {
          uid : uid,
          lid : lid,
          type    : vm.selectedItem.params.esspara,
          pattern : vm.selectedItem.params.pattern || vm.selectedItem.params.gettype,
          filterSelection : JSON.stringify([{
            operation : 'delete',
            setid : rowObj.setid
          }])
        };
      }
      API.saveFilters(params).$promise.then(function(){
        getData(vm.selectedItem);
      });
    }
  };

/*
  vm.changeActiveStatus = function(rowObj, currentFlg){
    var id = rowObj.colms[0].value;
    var nextvalue = 1 - currentFlg;
    API.saveFilters({
      uid : uid,
      lid : lid,
      //type :'filter',
      type : vm.selectedItem.params.esspara,
      pattern : vm.selectedItem.params.pattern,
      filterSelection : JSON.stringify([{
        operation : ($scope.operationtype === 'add') ? 'insert' : 'update',
        names     : ['condactiveflg'],
        values    : [nextvalue],
        id        : id
      }])}).$promise.then(function(){
        getData(vm.selectedItem);
      });
  };
 * */

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


}]);

})();
