'use strict';

/**
 * @ngdoc directive
 * @name d3WithAngularJsApp.directive:d3PathToConv
 * @description
 * # d3PathToConv
 */
angular.module('auriqCJPath')
.filter('ellipsedStringCjpath', function($timeout){
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
    var tmpstr = '';
    var tlen = input.length;
    for(var i=0; i<tlen; i++){
      tmpstr += input[i];
      var l = lenOfStr(tmpstr, btn_id);
l = (l<6*(i+1)) ? 6*(i+1) : l;
      if(l > w) return tmpstr.slice(0,-1)+'...';
    }
    return input;
  }
  return function(input, btn_id, max_w){
    var max_w = parseInt(max_w);
    if(input === undefined) return false;
    return processStr(input, max_w, btn_id);
  }
})
//.controller('logDownloadDialogCtrl', ['$scope', '$uibModalInstance', 'urlsFactory', 'API_BASE', 'items', function($scope, $uibModalInstance, urlsFactory, API_BASE, items){
//
//  $scope.usergroup = "-1"; // 1:cvuser 0:non-cvuser -1:all user
//
//  $scope.download = function(){
//    var params = angular.copy(items);
//    params.cvusrflg = parseInt($scope.usergroup);
//    location.href = API_BASE + '/' + urlsFactory.getContents + '?' + $.param(params);
//    $uibModalInstance.close();
//  };
//
//  $scope.cancel = function(){
//    $uibModalInstance.dismiss('cancel');
//  };
//
//}])
.controller('logSampleDialogCtrl', ['$scope', '$uibModalInstance', 'urlsFactory', 'API', 'API_BASE', 'items', '$window', function($scope, $uibModalInstance, urlsFactory, API, API_BASE, items, $window){

  var params = items;
  $scope.words        = items.words;
  $scope.isCVUserOnly = true;
  $scope.isLoaded     = false;

  getData(params);

  $scope.changeCVUserOnlyStatus = function(){
    $scope.isCVUserOnly = !$scope.isCVUserOnly;
    getData(params);
  };

  function getData(params){
    $scope.isLoaded = false;
    params.cvusrflg = ($scope.isCVUserOnly) ? 1 : 0;
    params.csvflg = false;
    API.getContents(params).$promise.then(function(data){
      $scope.isLoaded = true;
      $scope.tabledata = data.data;
    });
  }

  $scope.tableWrapperStyle = {
    'max-height' : angular.element($window).outerHeight() - 250
  };

  $scope.download = function(){
    var params = angular.copy(items);
    params.csvflg = true;
//console.log($scope.dlpattern);
    params.cvusrflg = parseInt($scope.dlpattern);
    location.href = API_BASE + '/' + urlsFactory.getContents + '?' + $.param(params);
    //$uibModalInstance.close();
  };

  $scope.close = function(){
    $uibModalInstance.dismiss('cancel');
  };

}])
.controller('detailedPathInfoCtrl', ['$scope', '$uibModalInstance', 'urlsFactory', 'API', 'items', '$window', '$timeout', function($scope, $uibModalInstance, urlsFactory, API, items, $window, $timeout){

  $scope.params = angular.copy(items);
  $scope.masterflg = items.masterflg;

  $scope.heights = {
    'upper'  : 150,
    'bottom' : 520
  };
  $timeout(function(){
    $scope.params.summary_w = angular.element(document.querySelector('.detailSampleWrapper')).outerWidth();
  });

  //  when window resize
  var resizeId;
  angular.element($window).bind('resize', function(){
    clearTimeout(resizeId);
    resizeId = setTimeout(doneResizing, 500);
  });
  function doneResizing(){
    $timeout(function(){
      $scope.params.summary_w = angular.element(document.querySelector('.detailSampleWrapper')).outerWidth();
    });
  }

  // close modal
  $scope.close = function(){
    $uibModalInstance.dismiss('cancel');
  };

}])
.directive('drawCjPath', ['d3Service', 'colorsFactory', 'API', 'API_BASE', 'urlsFactory', '$parse', '$rootScope','$timeout', '$uibModal', '$cookies', '$window', '$q', function (d3Service, colorsFactory, API, API_BASE, urlsFactory, $parse, $rootScope, $timeout, $uibModal, $cookies, $window, $q) {
  return {
    restrict: 'EA',
    templateUrl: 'views/directives/templ-draw-cjpath.html',
    scope:{
      optcont     : '=',
      pathdata    : '=',
      symbols     : '=',
      appheight   : '=',
      optglbl     : '=',
      optpath     : '=',
      words       : '=',
      isviewmode  : '=',
      masterflg   : '=',
      drawpattern : '='
    },
    link: function($scope, $element, $attrs, $parentCtrl){

      var detailparams;

      $scope.$watchCollection('pathdata', function(){
        if($scope.pathdata !== undefined) draw_content();
      });


      //  when full screen
      $scope.$watchCollection('optcont.isfullscreen', function(){
        $scope.fullscreenFlg = $scope.optcont ? $scope.optcont.isfullscreen : false;
        if($scope.fullscreenFlg !== undefined){

          $timeout(function(){
            var header_h = 0;
            var elm_header = angular.element(document.querySelectorAll('.header-options, .widget-header, .fullscreenWrapperPath'));
            for(var i=0,len=elm_header.length; i<len; i++) header_h += angular.element(elm_header[i]).outerHeight();
            $scope.field_h = angular.element($window).outerHeight() - header_h - 100;
            angular.element(".cjpath-content-wrapper")[0].scrollTop = 0;
          });

          setSizeOfPath();
        }
      });

      function lenOfStr(btn_id){
        var e = document.getElementById(btn_id);
        var width = e ? e.offsetWidth : 0;
        return width;
      }

      function setSizeOfPath(){
        var dfd = $q.defer();
        $scope.maxvalues = {};
        var valtypes = ['trg_cnt', 'rfr_cnt', 'cvrate'];
//        if(!$scope.pathdata) return false;
        for(var i=0,len=$scope.pathdata.length; i<len; i++){
          for(var j=0,jlen=valtypes.length; j<jlen; j++){
            var valtype = valtypes[j];
            var tval = $scope.pathdata[i][valtype].value;
            if(!$scope.maxvalues[valtype]) $scope.maxvalues[valtype] = {value:-999};
            if($scope.maxvalues[valtype].value < tval) $scope.maxvalues[valtype] = $scope.pathdata[i][valtype];
          }
        }

        $timeout(function(){
          $scope.cnt_all_w = lenOfStr('ruler-cjpath-usercnt');
          $scope.cnt_cv_w  = lenOfStr('ruler-cjpath-cvusercnt');
          $scope.rate_cv   = lenOfStr('ruler-cjpath-cvrate');
          var leftbox_w = lenOfStr('ruler-cjpath-leftbox') + 10; // +10 is necessary because the 1st element of the string of cvrate value is not the longest, but the digit is .00.
          var leftbox_min = 0;
          $scope.leftbox_w = (leftbox_w < leftbox_min) ? leftbox_min : leftbox_w;
          $timeout(function(){
            for(var i=0,len=valtypes.length; i<len; i++) $scope.maxvalues[valtypes[i]]='';
          });
          dfd.resolve();
        });
        return dfd.promise;
      }

      function setDrawPattern(){
        var isCutFrstCV = $scope.optpath.cutoption.value == 1;
        var isPathStartFromEntry = $scope.optpath.pathstartpoint.value == 0;
        //var isDummyCVFlg = (isPathStartFromEntry===false) || (isPathStartFromEntry===true && isCutFrstCV===true);
        var isDummyCVFlg = (isPathStartFromEntry===true && isCutFrstCV===true);
        var cutlen = $scope.optpath.cutlen;
//console.log(cutlen);
        var lasttouchpoint_w = 50;
        var wrapperclass = ($scope.drawpattern==='main') ? '.contentField' : '.detailSampleInner';
        var touchpoint_field_w = angular.element($element).closest(wrapperclass).outerWidth() - $scope.leftbox_w;
        for(var i=0,len=$scope.pathdata.length; i<len; i++){
          var rowobj   = $scope.pathdata[i];
//console.log(rowobj);
          var cvusrcnt = rowobj.trg_cnt.value;
          var cvrate   = rowobj.cvrate.value;
          var isDrawDummyCV = isDummyCVFlg && cvusrcnt > 0;
          $scope.pathdata[i].isDrawDummyCV = isDrawDummyCV;
          $scope.pathdata[i].styleEachTouchPath = {
            'width' : 'calc( (100% - '+lasttouchpoint_w+'px) / ' + (rowobj.path.length - 1) + ')'
          };
          $scope.pathdata[i].styleLastTouchPath = {
            'width' : lasttouchpoint_w + 'px'
          };
          var lastColorKey = (rowobj.path.length > 0) ? rowobj.path[rowobj.path.length-1].colorKey : '';
          $scope.pathdata[i].isCutBeforeCV = rowobj.path.length <= cutlen && lastColorKey==='fakecv';
          //$scope.pathdata[i].drawIndex = (isDrawDummyCV) ? rowobj.path.length-1 : rowobj.path.length;
          $scope.pathdata[i].drawSpanNum = rowobj.path.length-1;
          //--  radius adjustment
          var radius_max = 40;
          var radius = touchpoint_field_w / (rowobj.path.length + 1) - 10;
          $scope.pathdata[i].radius = (radius > radius_max) ? radius_max : radius;
          $scope.pathdata[i].mrgtop = (radius_max - $scope.pathdata[i].radius) / 2;
        }
      }

      function draw_content(){
        setSizeOfPath().then(function(){
          setDrawPattern();
        });
        $scope.css_icon_toopendetail = {
          'margin-top' : (($scope.appheight / $scope.optpath.pagerange - 20 - 30) / 2) + 'px'
        };
        var itemnum = ($scope.drawpattern === 'main') ? $scope.optpath.pagerange : 1;
        $scope.css_each_row = {
          height : $scope.appheight / itemnum + 'px'
        };
      }

      $scope.hover_touchpoint = function(classname, outintype){
        var classname = $scope.drawpattern + '-' + classname;
        var targetclass = '.cjpath-path-each-touchpoint-standard';
        if(outintype === 'in'){
          angular.element(document.querySelectorAll(targetclass+'.hovertp')).removeClass('hovertp');
          angular.element(document.querySelectorAll(targetclass+'.'+classname)).addClass('hovertp');
        }
        if(outintype === 'out'){
          angular.element(document.querySelectorAll(targetclass+'.hovertp')).removeClass('hovertp');
        }
      }



      function openModal(ctrlname, templurl, params){
        var items = angular.copy(params);
        return $uibModal.open({
          animation: true,
          templateUrl: templurl,
          controller: ctrlname,
          size: 'lg',
          resolve: {
            items: function () {
              return items;
            }
          }
        });
      }

      $scope.open_detail = function(d){
        if(!$scope.isviewmode){
//          var tappsize = angular.element($element)[0].getBoundingClientRect().width;
          detailparams = {
            isshowapp : true,
            pathdata : [d],
            leftboxsizes : {
              cnt_all_w : $scope.cnt_all_w,
              cnt_cv_w  : $scope.cnt_cv_w,
              rate_cv   : $scope.rate_cv,
              leftbox_w : $scope.leftbox_w - 30
            },
//            summary_w : tappsize > 1000 ? 1000 : tappsize,
            optcont : $scope.optcont,
            symbols : $scope.symbols,
            currentimported : $scope.optglbl.global.currentimported,
            pathpoints : d.original_path_arr,
            uid        : $scope.optglbl.uid,
            lid        : $scope.optglbl.lid,
            isviewmode : $scope.isviewmode,
            wordsdict  : $scope.words.each_path_popup,
            //optcont    : $scope.optcont,
            optpath    : $scope.optpath, 
            masterflg   : $scope.masterflg,
            'words'   : $scope.words,
          };
          openModal('detailedPathInfoCtrl', 'views/modals/cjpath-eachdetails.html', detailparams).result.then(function(sitem){
          }, function(){
          });
          
        }
      };

/*
      $scope.open_download_dialog = function(d){
        if(!$scope.isviewmode){
          var params = {
            'uid'      : $scope.optglbl.uid,
            'lid'      : $scope.optglbl.lid,
            'ctype'    : 'dump',
            'tmpl'     : 'dump',
            'paths'    : {'arr': d.original_path_arr},
            'topx'     : 0,
            'cvusrflg' : null,
            isviewmode : $scope.isviewmode,
            'csvflg'   : true
          };
          openModal('logDownloadDialogCtrl', 'views/modals/cjpath-log-download.html', params).result.then(function(sitem){
          }, function(){
          });
        }
      }
 * */

      $scope.open_sample_log = function(d){
        if(!$scope.isviewmode){
          var params = {
            'uid'     : $scope.optglbl.uid,
            'lid'     : $scope.optglbl.lid,
            'ctype'   : 'dump',
            'tmpl'    : 'dump',
            'paths'   : {'arr': d.original_path_arr},
            'topx'    : 1,
            'words'   : $scope.words,
            isviewmode : $scope.isviewmode,
            'csvflg'  :  false
          };
          openModal('logSampleDialogCtrl', 'views/modals/cjpath-log-sample.html', params).result.then(function(sitem){
          }, function(){
          });
        }
      };


    },
    controller:['$scope', function($scope){
      $scope.isLoaded = false;
    }]
  };
}])
.directive('d3PathToConv', ['d3Service', 'colorsFactory', 'API', 'API_BASE', 'urlsFactory', '$parse', '$rootScope','$timeout', '$uibModal', '$cookies', '$window', function (d3Service, colorsFactory, API, API_BASE, urlsFactory, $parse, $rootScope, $timeout, $uibModal, $cookies, $window) {
  var d3 = d3Service.d3;
  return {
    restrict: 'EA',
    templateUrl: 'views/directives/templ-path-cj-paths.html',
    scope:{
      isapploaded : '=',
      optglbl: '=',
      optpath: '=',
      optcont: '=',
      isessready: '=',
      masterflg   : '=',
      isviewmode: '='
    },
    link: function($scope, $element, $attrs, $parentCtrl){

      var pagingObj = {};
      var c_h_df    = 86;
      var tempvals  = {};
      var appliedparams = {};
      $scope.noParameterAvailable = true;
      $scope.isSelectedExist = true;

//      var path_colors = {
//        'Imp'      : '#528be8',
//        'Click'    : '#c680d2',
//        'Listing'  : '#45b29d',
//        'Organic'  : '#35b1fc',
//        'Ref'      : '#556c90',
//        'CV'       : '#ed9859',
//        ''         : '#84c98a'
//      };
      $scope.$watchCollection(function(){
        return {
          'optionsisvisible' : $scope.optcont.isVisible,
          'optglbl'          : $scope.optglbl,
//          'savestatus'       : $scope.savestatus,
          'isessready'        : $scope.isessready
        };
      }, function(){
        if($scope.optglbl){
          if($scope.optcont.isVisible){
            init_content();
            $scope.isLoaded = false;
//$scope.detailSampleShow = false;
            if($scope.isessready === true && $scope.isrunning===false){
            //if($scope.isrunning===false){
              $scope.isapploaded = false;
              var lang = ($scope.optglbl.global !== undefined) ? $scope.optglbl.global.lang : 'English';
              $scope.isrunning = true;
              tabChangeRun();
            }
          }else{
            $scope.isLoaded = false;
//            $scope.isapploaded = false;
            $scope.totals = {};
            return;
          }
        }
        if(!$scope.optglbl 
           || Object.keys($scope.optglbl).length === 0 
           || $scope.optcont.isVisible === false
           || $scope.isessready === false){
          $scope.isLoaded = false;
//          $scope.isapploaded = false;
          $scope.totals = {};
          return;
        }else{
          init_content();
          $scope.isLoaded = false;
//$scope.detailSampleShow = false;
          if($scope.isessread === true && $scope.isrunning===false){
            $scope.isapploaded = false;
            var lang = ($scope.optglbl.global !== undefined) ? $scope.optglbl.global.lang : 'English';
            $scope.isrunning = true;
            tabChangeRun();
          }
        }
      });


      $scope.$watchCollection('fuploadcnt', function(newval, oldval){
        if(newval != oldval){
          if($scope.fuploadcnt.length > 0){
            $scope.isLoaded = false;
            $scope.isapploaded = false;
            API.essUdbd({
              'uid' : $scope.optglbl.uid,
              'lid' : $scope.optglbl.lid,
              cmdtype  : 'profile',
            }).$promise.then(function(){
              $scope.isessread = false;
            });
          }
        }
      });


/*
      $scope.tabChange = function(index){
        if(index !== $scope.tabSelection && $scope.isLoaded){
          $scope.tabSelection = index;
          if($scope.optpath.trajectoryType in pagingObj){
            run();
          }else{
            tabChangeRun();
          }
        }
      };
*/


      $scope.pageChanged = function(dir){
        var currPageNum = parseFloat($scope.optpath.options.paging.status.pageNum);
        var pageNum = (dir==='n') ? currPageNum+1 : currPageNum-1;
        if(pageNum<0) pageNum=0;
        if(currPageNum !== pageNum && $scope.isLoaded){
          set_page_parameters(pageNum);
          init_content();
          run();
        }
      };

      $scope.pageChangeByNum = function($event, pagenum){
        var index = $scope.tabSelection;
        if(($event.type==='keydown' && $.inArray($event.keyCode, [9, 13]) !== -1) || $event.type==='blur'){
          var currpage = $scope.optpath.options.paging.status.pageNum;
          pagenum = isFinite(pagenum) ? parseInt(pagenum) : currpage;
          pagenum = (pagenum >0 && pagenum <= $scope.pageProp.pageMax) ? pagenum : currpage;
          if(pagenum != currpage){ // when it's changed
            set_page_parameters(pagenum);
            init_content();
            run();
          }else{
            $element.find('#pagenumber-id').val(pagenum);
            $scope.pagenum = pagenum;
          }
        }
      }


      $scope.changeTouchPointDepth = function(depth){
        $scope.tpdepth_temp = depth;
//        $scope.applyQuery();
      };

/*
      $scope.changeFreqDist = function(opt, index){
        $cookies.put('path_freqidx', index);
        $scope.freq      = opt;
        $scope.applyQuery();
      }

      $scope.changeFilterDepth = function(opt, index){
        $cookies.put('path_skidx', index);
        $scope.filterdepthlabel = opt.label;
        $scope.filterdepth      = opt.value;
        $scope.filterdepthguide = opt.defstr;
        $scope.applyQuery();
      };
 * */

      $scope.csvall = function(){
        var filterFirstTp = $scope.optpath.filterFirstTp;
        var params = {
          uid     : $scope.optglbl.uid,
          lid     : $scope.optglbl.lid,
          ctype   : 'path',
          tmpl    : 'path',
          tptype  : $scope.optpath.trajectoryType,
          paging  : {
                     status : {
                       from:1,
                       to:10000
                     }
                    },
          tpdepth : $scope.tpdepth,
          fldepth : $scope.filterdepth,
          filter  : filterFirstTp,
          dist    : $scope.freq.value,
          csvflg  : true
        };
        location.href = API_BASE + '/' +urlsFactory.getContents + '?' + $.param(params); 
      }

      $scope.filterOptionPanel = function(){
        $scope.isCollapsed = !$scope.isCollapsed;
      };

      $scope.applyFilterOption = function(){

        $scope.pagenum = 1;
        $element.find('#pagenumber-id').val(1);

        $scope.pathorder          = $.extend(true, {}, $scope.temppathorder);
        $scope.tillcv             = $.extend(true, {}, $scope.temptillcv);
        $scope.tpdepth            = $scope.tpdepth_temp;
        $cookies.put('path_tpdepth', $scope.tpdepth);
        var parasprof = {
          uid             : $scope.optglbl.uid,
          lid             : $scope.optglbl.lid,
          pathord         : $scope.pathorder.value,
          tocv            : $scope.tillcv.value,
          cmdtype         : 'calpath',
        };

        var oldobj = $scope.filterbuildpathobj;
        var newobj = $scope.tempfilterbuildpathobj;
        if(oldobj.label !== newobj.label){
          oldobj.value = 0;
          newobj.value = 1;
  
oldobj.operation = 'update';     newobj.operation = 'update';
oldobj.names = ['setactiveflg']; newobj.names = ['setactiveflg'];
oldobj.values = [0];             newobj.values=[1];
//oldobj.id = oldobj.uniqueid;     newobj.id = newobj.uniqueid;
        delete oldobj.label;
        delete oldobj.esspara;
        delete oldobj.exception;
        delete oldobj.setid;
        delete oldobj.type;
        delete oldobj.uniqueid;
        delete oldobj.value;
        delete newobj.label;
        delete newobj.esspara;
        delete newobj.exception;
        delete newobj.setid;
        delete newobj.type;
        delete newobj.uniqueid;
        delete newobj.value;
          var paras = {
            uid             : $scope.optglbl.uid,
            lid             : $scope.optglbl.lid,
            filterSelection : JSON.stringify([oldobj, newobj]),
            type            : 'pathtpdef',
            pattern         : 'cvpath',
          };
          $scope.iscollapsed = !$scope.iscollapsed;
          $scope.isLoaded = false;
          $scope.isapploaded = false;
          API.saveFilters(paras).$promise.then(function(){
            //init_page_params();
            $scope.filterbuildpathobj = $.extend(true, {}, $scope.tempfilterbuildpathobj);
            API.essUdbd(parasprof).$promise.then(function(){
              checkProfileStatus();
            });
          });
        }else{
          $scope.isLoaded = false;
          $scope.isapploaded = false;
          API.essUdbd(parasprof).$promise.then(function(){
            checkProfileStatus();
          });
        }

      };

      $scope.forceRedoCalc = function(){
        $scope.isLoaded = false;
        var parasprof = {
          uid             : $scope.optglbl.uid,
          lid             : $scope.optglbl.lid,
          pathord         : $scope.pathorder.value,
          tocv            : $scope.tillcv.value,
          cmdtype         : 'calpath'
        };
        API.essUdbd(parasprof).$promise.then(function(){
          checkProfileStatus();
        });
      };

//      $scope.changeListSelection = function(){
//        $scope.temptillcv.value = ($scope.temptillcv.value - 1) * (-1);
//      }
//      $scope.changeListSelection = function(optlist, index){
//        $scope.temptillcv      = optlist[index];
//      }
      $scope.changeListSelection = function(optobj){
        $scope.temptillcv = optobj;
      }

      function init_content(){
        $($element).find('svg').remove();
        $element.find('svg').remove();
      }

 
      function run(){
$scope.alldata = [];
        $scope.noData   = false;
        get_data().then(function(data){
          $scope.words = data.wordsdict; // define wrods dict (common)
          update_page_max(data.data.pagemax);
          if(data.data.d3objs.length === 0){
            $scope.isLoaded = true;
            $scope.isapploaded = true;
            $scope.noData   = true;
            $scope.isrunning = false;
          }else{
            draw_content(data);
          }
        
          // I do not want to uncomment out,,, I wanna make it true here.
            $scope.isLoaded = true;
            $scope.isapploaded = true;
            $scope.isrunning = false;
        });
      }

      function get_data(){
	$scope.isapploaded = false;
        $scope.isLoaded = false;
        var params = buildParams();
        appliedparams = $.extend(true, {}, params);
        var promise = API.getContents(params).$promise;
        return promise;
      }

      function buildParams(){
        var params = {
          uid     : $scope.optglbl.uid,
          lid     : $scope.optglbl.lid,
          ctype   : 'path',
          tmpl    : 'path',
          tptype  : $scope.optpath.trajectoryType,
          paging  : $scope.optpath.options.paging,
          tpdepth : $scope.tpdepth,
          fldepth : $scope.filterdepth,
          filter  : $scope.optpath.filterFirstTp,
          dist    : $scope.freq.value,
          csvflg  : false
        };
        return params;
      }

      function tabChangeRun(){
        init_content();
        get_paging_options().then(function(data){
          $scope.optpath.filterFirstTp = '';
          $scope.optpath.options = {
            'paging' : data.paging
          };
          //  Path order for the first visit
          if($scope.pathorder === undefined){
            var currval = $scope.optpath.pathorderflg.value;
            $scope.pathorder_options = $scope.optpath.pathorderflg.options;
            for(var i=0,len=$scope.pathorder_options.length; i<len; i++){
              $scope.pathorder = ($scope.pathorder_options[i].value === parseInt(currval)) ? $scope.pathorder_options[i] : $scope.pathorder;
            }
            $scope.temppathorder = $scope.pathorder;
            $scope.pathorderDescription =  $scope.optpath.pathorderflg.description;
          }


          // to CV flg
          if($scope.tillCVList === undefined){
            var currflg = $scope.optpath.tillcvflg.value;
            var curropt = {};
            currflg = (currflg === '') ? 1 : currflg;
            var optlist = $scope.optpath.tillcvflg.options;
            for(var i=0,len=optlist.length; i<len; i++){
              optlist[i].active = (optlist[i].value === parseInt(currflg));
              curropt = (optlist[i].value === parseInt(currflg)) ? optlist[i] : curropt;
            }
            $scope.tillcv          = curropt;
            $scope.temptillcv      = curropt;
            //
            $scope.tillCVList = optlist;
            $scope.tillcvDescription = $scope.optpath.tillcvflg.description;
          }

          // frequency detail
          if($scope.freqlist === undefined){
            $scope.freqlist = $scope.optpath.freqdetail.options;
            var frqindex = parseInt($cookies.get('path_freqidx')) || 0;
            $scope.freq     = $scope.freqlist[frqindex];
            $scope.freqDescription = $scope.optpath.freqdetail.description;
          }

          // path depth
          if($scope.touchPointDepthOptions === undefined){
            $scope.touchPointDepthOptions = $scope.optpath.pathdepth.options;
            var tpdepth_dflt   = $scope.optpath.pathdepth.value;
            var tpdepth_cookie = parseInt($cookies.get('path_tpdepth')) || tpdepth_dflt;
            $scope.tpdepth = ($scope.touchPointDepthOptions.indexOf(tpdepth_cookie)!==-1) ? tpdepth_cookie : tpdepth_dflt;
          }

          if($scope.searchPathDepth === undefined){
            $scope.searchPathDepth = $scope.optpath.searchbykeywords.options;
            var skidx = parseInt($cookies.get('path_skidx')) || $scope.optpath.searchbykeywords.defidx;
            $scope.filterdepthlabel = $scope.searchPathDepth[skidx].label;
            $scope.filterdepth      = $scope.searchPathDepth[skidx].value;
            $scope.filterdepthguide = $scope.searchPathDepth[skidx].defstr;
            
          }

          //
          pagingObj[$scope.tabSelection] = data;
          get_possible_filtering_options().then(function(data){
            init_params(data.data.tbody);
            //init_page_params();
            run();
          });
        });
      }

      function get_paging_options(){
        $scope.isLoaded = false;
        $scope.isapploaded = false;
         var params = {
           uid     : $scope.optglbl.uid,
           lid     : $scope.optglbl.lid,
           //ctype   : 'pathmax',
           fldepth : $scope.filterdepth,
           filter  : $scope.optpath.filterFirstTp,
           opttype : 'cvpathpaging'
        };
        var promise = API.getOptions(params).$promise;
        return promise;
      }

      $scope.applyQuery = function($event){
        if(($event===undefined) || 
           ($event.type==='keydown' && $.inArray($event.keyCode, [9, 13]) !== -1) || 
           ($event.type==='blur')){
          // start from 1,, would be more intuitive.
          if(isChangedParams()){
            $scope.optpath.isCollapsed = false;
            init_page_params();
            init_content();
            run();
          }
        }
      };

      function isChangedParams(){
        var checkparams = ['tpdepth', 'filter', 'fldepth', 'dist'];
        var slctparams = buildParams();
        var applparams = appliedparams;
        var fldepdiffflg = false;
        var isfltempty   = true;
        for(var i=0,len=checkparams.length; i<len; i++){
          var key = checkparams[i];
          if(key==='filter'){
              isfltempty = (slctparams[key] === '');
          }
          if(slctparams[key] === undefined){
            return true;
          }else if(slctparams[key] != applparams[key]){
            if(key==='fldepth'){
              if(!isfltempty){
                return true;
              }
            }else{
              return true;
            }
          }
        }
        
        return false;
      }

      function init_content(){
        $scope.totals = {};
        $($element).find('svg').remove();
        $element.find('svg').remove();
      }

      function draw_content(data){
        if($scope.optpath.options.paging.status.pageNum === undefined) init_page_params();

        var tdata = data.data;

        // totals
        $scope.totals = tdata.totals;

        // to pass to draw-cj-path directive
        $scope.c_h = (c_h_df + 5) * $scope.optpath.options.paging.range;
        $scope.maindata = angular.copy(tdata.d3objs);
        $scope.symbols  = tdata.symbols;
        $scope.pathdetailoption = {
          pathstartpoint : $scope.temppathorder,
          cutoption      : $scope.temptillcv,
          cutlen         : $scope.tpdepth,
          pagerange      : $scope.optpath.options.paging.range
        };
      }


      /*
      *  Functions For Paging
      */
      function get_possible_filtering_options(){
        return API.getFilters({
          uid     : $scope.optglbl.uid,
          lid     : $scope.optglbl.lid,
          gettype : 'detail',
          type    : 'userintselect',
          pattern : 'cvpath', // global or cvpath
          esspara : 'pathtpdef',
          setid   : -1
        }).$promise;
      }
      function init_params(filtopt){
        var tpdepth_dflt   = $scope.optpath.pathdepth.value;
        var tpdepth_cookie = parseInt($cookies.get('path_tpdepth')) || tpdepth_dflt;
        $scope.tpdepth = ($scope.touchPointDepthOptions.indexOf(tpdepth_cookie)!==-1) ? tpdepth_cookie : tpdepth_dflt;
        $scope.tpdepth_temp = $scope.tpdepth;
        // for filtering to which depth/all
        var skidx = parseInt($cookies.get('path_skidx')) || $scope.optpath.searchbykeywords.defidx;
        $scope.filterdepthlabel = $scope.searchPathDepth[skidx].label;
        $scope.filterdepth      = $scope.searchPathDepth[skidx].value;
        $scope.filterdepthguide = $scope.searchPathDepth[skidx].defstr;
        // filtering options (for when it builds path.)
        var sidx = 0;
        if(filtopt.length === 0){
          $scope.noParameterAvailable = true;
          return false;
        }
        $scope.noParameterAvailable = false;
        var colms  = filtopt[0].colms;
        var idx = {
          uniqueid : returnColumnIdx(colms, 'id'),
          label    : returnColumnIdx(colms, 'setname'),
          value    : returnColumnIdx(colms, 'setactiveflg'),
          setid    : returnColumnIdx(colms, 'setid'),
        };
        var list = [];
        var sobj = {};
        var isSelectedExist = false;
        for(var ridx=0,len=filtopt.length; ridx<len; ridx++){
          colms  = filtopt[ridx].colms;
          var uniqid = colms[idx.uniqueid].value;
          var label  = colms[idx.label].value;
          var value  = colms[idx.value].value;
          var setid  = colms[idx.setid].value;
          var tobj   = {
            //operation : 'change',
            label     : label,
            id        : uniqid.toString(),
            uniqueid  : uniqid.toString().split(',')[0],
            exception : (uniqid.toString().split(',').length > 1) ? 'setname' : 'none',
            esspara   : 'pathtpdef',
            setid     : setid,
            type      : 'setactiveflg',
            value     : parseInt(value)
          };
          list.push(tobj);
          sobj = (parseInt(value) === 1) ? tobj : sobj;
          isSelectedExist = (parseInt(value) === 1) ? true : isSelectedExist;
        }
        if(!isSelectedExist){
          list[0].value = 1;
          sobj = list[0];  // if there's no selected item, pick 0 index.
        }
        $scope.filterbuildpathobj      = $.extend(true, {}, sobj);
        $scope.tempfilterbuildpathobj  = $.extend(true, {}, sobj);
        if(!isSelectedExist) $scope.filterbuildpathobj.label = '';
        $scope.filterbuildpathlist = list;
        $scope.isSelectedExist = isSelectedExist;
      }

      function returnColumnIdx(row, type){
        for(var colidx=0,len=row.length; colidx<len; colidx++){
          if(row[colidx].type === type){
            return colidx;
          }
        }
      }


      $scope.changeFilteBuildPath = function(index, value){
        $scope.tempfilterbuildpathobj = $.extend(true, {}, $scope.filterbuildpathlist[index]);
      };

//      $scope.changePathBuildType = function(index, opt){
//        $scope.temppathorder      = opt;
//      }
      $scope.changePathBuildType = function(optobj){
        $scope.temppathorder = optobj;
      }

      function checkProfileStatus(){
        API.essInstance({
          uid     :  $scope.optglbl.uid, 
          lid     :  $scope.optglbl.lid, 
          dotype  : 'check',
          trgtype : 'import'
        }).$promise.then(function(data){
          if(data.status === 'done'){
            tabChangeRun();
          }else{
            $timeout(function(){
              checkProfileStatus();
            }, 1000);
          }
        });
      }

      function update_page_max(pmax){
        if(pmax !== undefined){
          $scope.optpath.options.paging.max = pmax;
          $scope.pageProp = return_paging_property();
        }
      }
      function init_page_params(){
        // paging
        var pageProp = return_paging_property();
        var pageNum = pageProp.pageMin;
        $scope.pageProp = pageProp;
        set_page_parameters(pageNum);
      }
/*
      function return_next_page_num(dir){
        var pageNum = parseFloat($scope.optpath.options.paging.status.pageNum);
        var pageProp = return_paging_property();
        var pageMax = parseFloat(pageProp.pageMax);
        var pageMin = parseFloat(pageProp.pageMin);
        if(dir === 'n'){
          if(pageNum < pageMax){
            pageNum++;
          }
        }else{
          if(pageNum > pageMin){
            pageNum--;
          } 
        }
        return pageNum;
      }
 * */
      function set_page_parameters(pageNum){
        var index = $scope.tabSelection;

        var pageNum  = parseFloat(pageNum);
        var pageProp = return_paging_property();
        var pageMax = pageProp.pageMax;
        var pageMin = pageProp.pageMin;
        var itemRng = pageProp.itemRng;

        //  set page number (to view)
        $scope.pagenum = pageNum;
        $element.find('#pagenumber-id').val(pageNum);

        //  set page number (for parameter)
        $scope.optpath.options.paging.status.pageNum = pageNum;
        $scope.optpath.options.paging.status.from    = (pageNum - 1)* itemRng + 1;
        $scope.optpath.options.paging.status.to      = pageNum * itemRng;
//        $scope.optpath.options.paging.status.itemnum = itemRng;

/*
        //  set class (disabled, if necessary)
        var acclass = {};
        acclass.next = (pageNum === pageMax) ? 'disabled' : '';
        acclass.prev = (pageNum === pageMin) ? 'disabled' : '';
        $scope.optpath.options.paging.class = acclass;
 * */
      }
      function return_paging_property(){
        var index = $scope.tabSelection;
        var itemMax = $scope.optpath.options.paging.max;
        var itemRng = $scope.optpath.options.paging.range;
        return {
          'itemMax': itemMax,
          'itemMin': parseFloat($scope.optpath.options.paging.min),
          'itemRng': itemRng,
          'pageMax': Math.ceil( itemMax / itemRng),
          'pageMin':1
        };
      }




    },
    controller:['$scope', function($scope){
      $scope.isLoaded = false;
      $scope.isrunning = false;
      $scope.tabSelection = 0;
      $scope.fuploadcnt = [];
      $scope.isCollapsed = true;
//      $scope.isCVUserOnly = true;

    }]
  
  };
}]);
