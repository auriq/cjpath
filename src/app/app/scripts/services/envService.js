(function () {
  'use strict';

  angular
    .module('auriqCJPath')
    .factory('envService', envService);

  envService.$inject = ['API', '$q', '$window', '$timeout', 'API_BASE', 'urlsFactory', 'colorsFactory'];

  function envService(API, $q, $window, $timeout, API_BASE, urlsFactory, colorsFactory) {
    var
      optionsList = [],
      widgetsList = [];
    var pollingSpan = 1000;


    return {
      optionsList   : optionsList,
      getStatus     : getStatus,
      envSetupStart : envSetupStart,
      waitEssSetup  : waitEssSetup,
      startImport   : startImport,
      waitImporting : waitImporting,
      startProfile  : startProfile,
      saveGlobalFilter : saveGlobalFilter,
      checkIfFilterIsUpdated : checkIfFilterIsUpdated,
      takeOverUser : takeOverUser
    };

    ////////////////

    // get status
    function getStatus(uid, lid, type){
      // type could be
      // "instance", "import", "mstat", "checkifhijacked"
      return API.essInstance({uid: uid, lid:lid, dotype:'check', trgtype:type}).$promise;
    }

    // ess environment setup
    function envSetupStart(uid, lid, insttype, instnum){
      return API.essInstance({
        uid       : uid,
        lid       : lid,
        dotype    : 'manipulate',
        cmdtype   : 'create',
        insttype  : insttype,
        instnum   : instnum
      }).$promise;
    }
    function waitEssSetup(uid, lid){
      return checkEssSetup(uid, lid, $q.defer());
    }
    function checkEssSetup(uid, lid, def){
      getStatus(uid, lid,'instance').then(function(data){
        if(data.status === 'ready'){
          def.resolve(data);
        }else{
          $timeout(function(){
            checkEssSetup(uid, lid, def);
          }, pollingSpan);
        }
      });
      return def.promise;
    }


    // importing
    function startImport(uid, lid, globalOpt){
      var params = {
        cmdtype : 'import',
        uid: uid,
        lid: lid,
        cachedir : globalOpt.global.userchoice.fname || globalOpt.global.cachelist[0].fname,
	sdate   :  globalOpt.global.userchoice.start_date,
	edate   :  globalOpt.global.userchoice.end_date,
	bdays   :  globalOpt.global.userchoice.back_days,
        sampling : globalOpt.global.userchoice.sampling,
        cpmemo   : globalOpt.global.userchoice.memo,
        redoflg  : 0, //globalOpt.redoflg,
        //custom   : globalOpt.customobj
      };
      return API.essUdbd(params).$promise;
    }
    function waitImporting(uid, lid){
      return checkImporting(uid, lid, $q.defer());
    }
    function checkImporting(uid, lid, def){
      //API.essInstance({uid: uid, lid:lid, dotype:'check', trgtype:'import'}).$promise.then(function(data){
      getStatus(uid, lid, 'import').then(function(data){
        if(data.status === 'done' || data.status === 'failed'){
          def.resolve(data);
        }else{
          $timeout(function(){
            checkImporting(uid, lid, def);
          }, pollingSpan);
        }
      });
      return def.promise;
    }


    // save global filter condition (user segment / cv tag)
    function saveGlobalFilter(uid, lid, globalOpt){
      var filterselection = buildFilterSelection(globalOpt.global.filterdiff);
      return API.saveFilters({
        uid             : uid,
        lid             : lid,
        pattern         : 'global',
        type            : 'filter',
        filterSelection : JSON.stringify(filterselection)
      }).$promise;
    }

    function buildFilterSelection(filterselection){
      var fs = [];
      for(var key in filterselection) fs = fs.concat(filterselection[key]);
      return fs;
    }

    function startProfile(uid, lid){
      return API.essUdbd({
        'uid' : uid,
        'lid' : lid,
        cmdtype  : 'profile',
      }).$promise;
    }

    function checkIfFilterIsUpdated(uid,lid){
      var dfd = $q.defer();
      API.getFilters({uid:uid,lid:lid,gettype:'diff'}).$promise.then(function(data){
        if(data.data.isExistDiff){
          startProfile(uid,lid).then(function(){
            return dfd.resolve();
          });
        }else{
          return dfd.resolve();
        }
      });
      return dfd.promise;
    }

    function takeOverUser(uid, lid){
      return API.essInstance({
        uid: uid,
        lid: lid,
        dotype    : 'manipulate',
        cmdtype   : 'takeover',
        //targetdir : 'local', /*local or workdir*/
        targetdir : 'workdir', /*local or workdir*/
        insttype  : '',
        instnum   : ''
      }).$promise;
    }


  }

})();
