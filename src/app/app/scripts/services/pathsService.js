(function () {
  'use strict';

  angular
    .module('auriqCJPath')
    .factory('pathsService', pathsService);

  pathsService.$inject = ['API', '$q', '$window', 'API_BASE', 'urlsFactory', 'colorsFactory'];

  function pathsService(API, $q, $window, API_BASE, urlsFactory, colorsFactory) {
    var
      optionsList = [],
      contentsList = [],
      cjPathsParams = [],
      tempParams = [],
      widgetsList = [];



    return {
      optionsList: optionsList,
      tempParams: tempParams,
      contentsList: contentsList,
      cjPathsParams: cjPathsParams,
      getWordDict : getWordDict,
      getParameters: getParameters,
//      readDataSet: readDataSet,
//      createInstance: createInstance,
//      checkInstanceStatus: checkInstanceStatus,
      returnInitGlobalParams: returnInitGlobalParams,
//      checkReadStatus : checkReadStatus,
      terminateMyself : terminateMyself
    };

    ////////////////

    function getWordDict(uid, lid){
      return API.getOptions({
        uid     : uid,
        lid     : lid,
        opttype : 'wordsdict',
        dtype   : 'global_panel'
      }).$promise;
    }

    function getParameters(uid, lid) {

      // content configuration 
      var content_config = {
        'global_summary':{
          'pclass'      : 'col-sm-7',
          'include_path': ''
        },
//        'cj_property':{
//          'pclass'      : 'col-sm-7',
//          'include_path': ''
//        },
        'cj_paths':{
          'pclass'      : 'col-sm-7',
          'include_path': ''
        }
      };


      var promise;
      promise = API.getOptions({uid: uid, lid:lid, opttype:'global'}).$promise;
      //  get parameters
      promise.then(function(data){
      
        //  content options
        var promises = [];
        var cdata  = data.contents.data;
	var idx = 0;
        angular.forEach(cdata, function (edata, key) {
          var promise;
          var cont_data = edata;
          var cont_type = cont_data.type;
          var cont_conf = (cont_type in content_config) ? content_config[cont_type] : {};

          var content = angular.extend(cont_conf, cont_data, {
            isVisible: true,
            isLoaded : false,
            data     : {},
            uid      : uid
          });


          //  create models
          set_default_content_vars(cont_type, content);

          contentsList[idx] = content;

          idx = idx + 1;
        });

      });
      return promise;
    }


//    function readDataSet(uid, lid, globalOpt){
//      var params = {
//        cmdtype : 'import',
//        uid: uid,
//        lid: lid,
//	//segment : globalOpt.segment.value,
//	sdate   : globalOpt.date.start_date,
//	edate   : globalOpt.date.end_date,
//	bdays   : globalOpt.date.back_days,
//        sampling : globalOpt.sampling,
//        redoflg  : globalOpt.redoflg,
//        //custom   : globalOpt.customobj
//        //pathorder : globalOpt.pathorder
//      };
//      return API.essUdbd(params).$promise;
//    }

    /*
    *  Set default (var for ng-model) for contents
    */
    function set_default_content_vars(cont_type, content){
      if('contents' in content){
        if(cont_type === 'cj_paths'){
          cjPathsParams.push(content.contents);
        }
      }
    }

    function returnInitGlobalParams(uid, lid, masterFlg, logtype, options){
      var initpars = {};

      // cache list
      initpars.cachelist = options.cachelist;
      // is cached varible for start/end date
      initpars.iscachedexist = options.iscachedexist;
      // currentimported (set of current imported seggins)
      initpars.currentimported = angular.copy(options.currimported); // setting currently imported
      initpars.userchoice      = angular.copy(options.currimported); // setting currently imported

      initpars.cacheidx = options.cacheidx; // index of cache array that is currently imported.

      // lang
      initpars.lang    = options.lang;

      // uid
      initpars.uid     = uid;

      // lid
      initpars.lid     = lid;

      return initpars;

    }

    function terminateMyself(uid, lid){
      return API.essInstance({uid:uid, lid:lid, dotype:'manipulate', cmdtype:'logout'}).$promise;
      //$window.close();
    }

  }

})();
