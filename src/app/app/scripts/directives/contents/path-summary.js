'use strict';

angular.module('auriqCJPath')
  .directive('pathSummary', function (colorsFactory, funcCommonFactory, $timeout, $filter, urlsFactory, $http, API, API_BASE, $rootScope, $window, $cookies) {

  return {
    restrict: 'EA',
    templateUrl: 'views/directives/templ-path-summary.html',
    scope: {
      isapploaded : '=',
      optglbl     : '=',
      optcont     : '=',
      isactive    : '=',
      ctype       : '=',
      cheight     : '=',
      cwidth      : '=',
      masterflg   : '=',
      filterupdatecnt : '=',
      isviewmode  : '='
    },
    link: function ($scope, $element) {

      $scope.$watchCollection(function(){
        return {
          isactive : $scope.isactive,
          cheight  : $scope.cheight,
          cwidth   : $scope.cwidth,
          optglbl  : $scope.optglbl
        };
      }, function(newobj, oldobj){
        $scope.isLoaded = false;
        if($scope.isactive && $scope.optglbl!==undefined && $scope.cheight > 100 && $scope.cwidth!==undefined && $scope.optcont.isVisible){
//console.log($scope.optglbl);
//console.log($scope.data);
//console.log($scope.tabSelection);
          $scope.isLoaded = false;
          if($scope.options===undefined || $scope.data===undefined){
            run();
          }else{
            //updateWithData(false);
            //  full screen mode switch / window is resized
            draw();
          }
        //}else{
        //  $element.find('svg').remove();
        //  $scope.optpath = [];
        }
      });

      // when filter(usersegment/cv condition) is updated.
      $scope.$watchCollection('filterupdatecnt', function(){
        if($scope.filterupdatecnt > 0){
          $scope.isLoaded = false;
          run();
        }
      });

      //------------------------
      //
      //  functions
      //
      //------------------------
      //
      function run(){
        // for summary
        getOptions().then(function(data){
	  var defaultIndex = parseInt($cookies.get('gsindex_' + $scope.ctype)) || 0;
	  //var defaultIndex = parseInt($cookies.get('gsindex')) || 0;
          $scope.optpath = (function(){ for(var i=0,len=data.contents.data.length;i<len;i++) if(data.contents.data[i].content_name==='path_global_summary') return angular.copy(data.contents.data[i].contents); })();
          if($scope.ctype === 'eachpathpopup'){
            $scope.optpath.shift();
var distToCVObj = {
  contentType : 'distToCV',
  templateType : 'table',
  title : 'Distance to CV'
};
            $scope.optpath.unshift(distToCVObj);
          }
          $scope.selectedIndex = defaultIndex;
          $timeout(function(){
            $scope.tabChange(defaultIndex, true);
          },0);
        });
      }

      function getOptions(){
        return API.getOptions({
          uid      : $scope.optglbl.uid,
          lid      : $scope.optglbl.lid,
          opttype  : ($scope.ctype == '') ? 'global' : $scope.ctype
        }).$promise;
      }

      //  tab change
      $scope.tabChange = function(index, forceFlg){
        if(((index !== $scope.tabSelection && $scope.isLoaded && !$scope.isviewmode) || forceFlg)){
          $scope.isLoaded = false;
          if($scope.optpath[index].isCogCollapse === undefined) $scope.optpath[index].isCogCollapse = true;
          $cookies.put('gsindex_'+$scope.ctype, index);
          //$cookies.put('gsindex', index);
          $scope.selectedIndex = index;
          $scope.isrunning = true;
          $scope.isChanged = false;
          $element.find('svg').remove();
          $scope.tabSelection = index;
          setDefaultOptions(index);
          setLoadingHeight();
          updateWithData(false);
        }
      };

      function setDefaultOptions(index){
        // build default option set..
        var options = $scope.optpath[index].options || [];
        var cookieval_head = 'sumopt_' + $scope.ctype + $scope.tabSelection + '_';
        for(var i=0,len=options.length; i<len; i++){
          var cookievalname = cookieval_head + i;
          options[i].varname = cookievalname;
          if(options[i].type === 'select'){
            var dfltidx = (options[i].defaultindex) ? options[i].defaultindex : 0;
            var toptidx = $cookies.get(cookievalname) || dfltidx;
            options[i].selected    = (options[i].list) ? options[i].list[toptidx] : '';
            options[i].selectedidx = toptidx;
            if(options[i].selected.children !== undefined){
              var chddfltidx = (options[i].selected.children.defaultindex) ? options[i].selected.children.defaultindex : 0;
              var cookievalname_children = cookievalname + '_chld';
              var toptidx_chld = $cookies.get(cookievalname_children) || chddfltidx;
              options[i].selected.children.varname     = cookievalname_children;
              options[i].selected.children.selected    = options[i].selected.children.list[toptidx_chld] || '';
              options[i].selected.children.selectedidx = toptidx_chld;
              options[i].selected.children.applied     = options[i].selected.children.selected;
            }
            options[i].applied  = $.extend(true, {}, options[i].selected);
          }else if(options[i].type === 'number'){
            var dfltval = (options[i].default) ? options[i].default : '';
            var toptval = $cookies.get(cookievalname) || dfltval;
//console.log(toptval);
            options[i].applied  = (options[i].default) ? options[i].default : '';
            options[i].selected = (options[i].default) ? options[i].default : '';
          }
        }
        setDisabledParts(options);
        // assign to the scope variable.
        $scope.options      = options;
        $scope.contentType  = $scope.optpath[index].contentType;
        $scope.currentTitle = $scope.optpath[index].title;
        $scope.templateType = $scope.optpath[index].templateType;
        $scope.chartType    = $scope.optpath[index].chartType || '';
      }

      function setDisabledParts(options){
        for(var i=0,len=options.length; i<len; i++){
          if(options[i].type === 'select'){
            var dsbflg = (options[i].disabled !== undefined && options[i].disabled.target !== undefined);
            if(dsbflg){
              for(var j=0; j<len; j++){
                if(options[j].name === options[i].disabled.target){
                  var dsbval = ($.inArray(options[j].selected.value, options[i].disabled.list) !== -1);
                  options[i].disabled.status = dsbval;
                  if(dsbval){
                    options[i].selected = options[i].list[options[i].disabled.selectidx];
                  }else{
                    //options[i].selected = options[i].applied;
                  }
                }
              }
            }else{
              options[i].disabled = {
                'status' : false
              };
            }
          }
        }
      }

      $scope.returnIsDisabledItem = function(optobj, obj){
        if(obj.disabled !== undefined){
          var dsbl = obj.disabled;
          var trgt = dsbl.target;
          var list = dsbl.list;
          for(var i=0,len=$scope.options.length; i<len; i++){
            var name = $scope.options[i].name;
            if(trgt === name){
              var slct = $scope.options[i].selected;
              return $.inArray(slct.value, list) !== -1; 
            }
          }
        }
        return false;
      }

      $scope.setOptions = function(optobj, obj, idx){
        optobj.selected = $.extend(true, {}, obj);
        optobj.selectedidx = idx;
        if(optobj.selected.children){
          var dfltidx = optobj.selected.children.defaultindex || 0;
          optobj.selected.children.selected = optobj.selected.children.list[dfltidx];
          optobj.selected.children.selectedidx = dfltidx;
        }
        setDisabledParts($scope.options);
        $scope.setIsChangedToApplyButton();
      }
      $scope.applySelection = function(pattern){
        $scope.isChanged = false;
        if(pattern === 'view'){
          $scope.isLoaded  = false;
          $scope.isapploaded = false;
          for(var i=0,len=$scope.options.length; i<len; i++){
            $scope.options[i].applied = $scope.options[i].selected;
            var pslctidx = $scope.options[i].selectedidx || 0; 
            $cookies.put($scope.options[i].varname, pslctidx);
            if($scope.options[i].applied.children){
              $scope.options[i].applied.children.applied = $.extend(true, {}, $scope.options[i].applied.children.selected);
              $cookies.put($scope.options[i].selected.children.varname, $scope.options[i].selected.children.selectedidx);
              // cleanup !!!!
              for(var j=0,jlen=$scope.options[i].list.length; j<jlen; j++){
                if($scope.options[i].list[j].children.applied === undefined){
                  var newobj = $.extend(true, {}, $scope.options[i].list[j].children);
                  delete newobj.selected;
                  delete newobj.applied;
                  $scope.options[i].list[j].children =  $.extend(true, {}, newobj);
                }
                //if(j !== pslctidx){
                //  delete $scope.options[i].list[j].children.selected;
                //  delete $scope.options[i].list[j].children.applied;
                //}
              } // end of cleanup
            }
          }
          updateWithData(false);
        }
        if(pattern === 'dl'){
          $scope.downloadTable();
        }
      }
      $scope.forceRun = function(){
        $scope.isLoaded = false;
        updateWithData(true);
      };

      $scope.setIsChangedToApplyButton =function(){
        var isChanged = false;
        for(var i=0,len=$scope.options.length; i<len; i++){
          var topt = $scope.options[i];
          if(topt.type === 'select'){
            isChanged = (topt.applied.value != topt.selected.value) ? true : isChanged;
            if(topt.applied.children){
              if(topt.selected.children.applied){
                isChanged = (topt.selected.children.selected.value != topt.selected.children.applied.value) ? true : isChanged;
              }else{
                isChanged = true;
              }
            }
          }else{
            isChanged = (topt.applied != topt.selected) ? true : isChanged;
          }
          
        }
        $scope.isChanged = isChanged;
      }


      $scope.downloadTable = function(){
         var paras = {
           uid     : $scope.optglbl.uid,
           lid     : $scope.optglbl.lid,
           sdate   : $scope.optglbl.currentimported.start_date,
           edate   : $scope.optglbl.currentimported.end_date,
           ctype   : $scope.contentType,
           dtype   : $scope.chartType,
           tmpl    : $scope.templateType,
           csvflg  : true
         };
         if($scope.optglbl.pathpoints !== undefined){
           paras.paths = {'arr': $scope.optglbl.pathpoints};
         }
         paras = buildParameter(paras);

         location.href = API_BASE + '/' +  urlsFactory.getContents + '?' + $.param(paras);
      }

      //------------------------
      //
      //  functions
      //
      //------------------------
      //  update content 
      function updateWithData(isforceflg){
        funcCommonFactory.checkIfYouAreHijacked($scope.optglbl.uid, $scope.optglbl.lid).then(function(data){
          if(!data){
//console.log(data);
            $scope.origData        = [];
            $scope.summary         = [];
            $scope.message         = '';
            $scope.chart_colors    = [];
            $scope.isshowlegendflg = true;
            // remove svg elements.
            if($element.find('svg').length > 0){
              var svgs = $element.find('svg');
              for(var i=0,len=svgs.length; i<len; i++){
                svgs[i].innerHTML = '';
              }
            }
            $scope.data = {};
            getData(isforceflg).then(function(data){
              $scope.isNoData = false;
              $scope.words = data.wordsdict; // define words dict (common)
              $scope.data[$scope.tabSelection] = data.data;
              draw();
            });
          }
        });
      }

      function draw(){
        if($scope.templateType === 'chart') $element.find('svg').remove();
        var data =  angular.copy($scope.data[$scope.tabSelection]);
        var currContentType = $scope.contentType;
        var templateType    = $scope.templateType;
        //$element.find('svg').remove();
        $scope.data[$scope.tabSelection] = {};
        $scope.isLoaded = false;
        if(templateType === 'summ'){
          var h = returnChartHeight(currContentType);
          var w = $scope.cwidth / data.pies.data.length + 70;
          var chart_h = (w < h) ? w : h;
          chart_h = (chart_h > 280) ? 280 : chart_h;
          // set options
          $scope.piechart_options = {
            height : chart_h,
            margin: {
              top: -(chart_h * 0.08),
              left: 0
            },
            showControles: true,
            showLegend   : false,
            showLabels   : false,
            tooltips     : false
          };
          $scope.piechart_colors = ['#528be8', '#d5dde0'];
        }else if(templateType == 'table'){
          var table_h = (returnChartHeight(currContentType)).toString() + 'px';
          // table header
          var opt = $scope.options.slice(0);
          var table_title = (currContentType === 'detailTableRegionMap') ? [$scope.currentTitle] : [''];
          for(var i=0,len=opt.length; i<len; i++){
            if(opt[i].name === 'colms'){
              table_title = opt[i].applied.title || [opt[i].applied.label];
            }
            if(opt[i].applied.children){
              if(opt[i].applied.children.name === 'colms'){
                table_title = opt[i].applied.children.applied.title || [opt[i].applied.children.applied.label];
              }
            }
          }
          $scope.table_title = table_title;
          // table height
          $scope.table_h = table_h;
//console.log(table_h);
          $scope.isLoaded  = true;
          $scope.isapploaded = true;
        }else if(templateType === 'chart'){
          var chart_options = {};
    
    
          // define height
          chart_options.height = returnChartHeight(currContentType);
          chart_options.width  = $scope.cwidth;
          if($scope.ctype === 'eachpathpopup') chart_options.width -= 60;
//          if(chart_options.width<0) chart_options.width = $scope.cwidth;
          if(currContentType === 'pieBrowsers'){
            chart_options = returnEventChartOptions(chart_options, data);
          }
          if(currContentType  === 'distFreqPV'){
          }
          if(currContentType.indexOf('logCountTrend', 0) !== -1){
            chart_options.margin = {
              bottom : 100
            };
            chart_options.xAxis = {
              rotateLabels : -45
            };
          }
    
          $scope.yaxis_title     = data.yAxis_label || '';
          $scope.yaxis_labels    = data.yAxis_values || [];
          $scope.xaxis_title     = data.xAxis_label || '';
          if($scope.options[0] !== undefined){
            if($scope.options[0].name === 'bintype' || (currContentType==='distFreq' && $scope.options[0].name==='xAxisType')){
              $scope.xaxis_title = $scope.options[0].applied.label || $scope.xaxis_title;
            }
          }
          $scope.xaxis_labels    = data.xAxis_values || [];
          $scope.chart_options   = chart_options;
          $scope.summary         = data.summary || [];
          $scope.tailrate_title  = data.tailrate_title;
          $scope.tailrate        = data.tailrate;
          $scope.isshowlegendflg = (currContentType !== 'distFreq');
          $scope.message         = data.overmaxmessage || '';
          if(currContentType.indexOf('logCountTrend', 0) !==-1){
            $scope.chart_colors  = (currContentType === 'logCountTrend') ?
                                    ['#72727a', '#a0add3', '#add8e6'] : 
                                    ['#72727a', '#a0add3', '#add8e6', '#bfe1bf', '#e0af82'];
          }
        }
        // summary, line-height adjustment.
        adjust_line_height(currContentType, chart_h);
    
        // For IE, who cannot show attribution table.
        if((navigator.userAgent.toString().match(/Win[0-9]/)!==null || navigator.userAgent.match(/Trident/) || navigator.userAgent.match(/MSID/))){
          $timeout(function(){
            $scope.isLoaded  = true;
            $scope.isapploaded = true;
            $scope.isrunning = false;
          },10);
        }
        $scope.data[$scope.tabSelection] = angular.copy(data);
        $timeout(function(){
          // summary, line-height adjustment.
          adjust_line_height(currContentType, chart_h);
          $scope.isLoaded  = true;
          $scope.isapploaded = true;
          $scope.isrunning = false;
        }, 500);
      }

      function setLoadingHeight(){
/*
        $timeout(function(){
          var h_main = returnContentHeight(false);
          $element.find('.loading').each(function(){
            $(this).css({
              'height'      : (h_main).toString() + 'px',
              'line-height' : (h_main).toString() + 'px'
            });
            $scope.isLoaded = false;
            $scope.isapploaded = false;
          }); 
        },0);
 * */
      }
      function returnContentHeight(headerAllFlg){
        var h_opt = 0;
        $element.find('.summary_tabcont_'+$scope.contentType).find('.header-options').each(function(){
          var t_h = $(this).height();
          if(headerAllFlg){
            h_opt += (t_h > 0) ? t_h : 80;
          }else{
            h_opt += (t_h > 0) ? t_h : 0;
          }
        });
        //var h_main  = $scope.cheight - (h_opt);
        var h_main  = $scope.cheight;
        return h_main;
      }
      function returnChartHeight(currContentType){
        var h_main = returnContentHeight(true);
        var h_adj_c = (currContentType === 'distFreqPV') ? 50 : 0;
        if(currContentType.indexOf('Trend')!==-1) h_adj_c = 90;
        if(currContentType.indexOf('distFreq')!==-1) h_adj_c = 120;
        if(currContentType.indexOf('distFreqPV')!==-1) h_adj_c = 170;
        h_main = h_main - h_adj_c;
        return (currContentType === 'summ') ? (h_main/2)*1.1 : h_main - 70;
      }

      function adjust_line_height(currContentType, chart_h){
        if(currContentType === 'summ'){
          $timeout(function(){
            $element.find('.pie-chart-label').css({
              'line-height':(chart_h*0.85).toString() + 'px'
            });
          }, 10);
        }
      }

      function returnEventChartOptions(chart_options, data){
        chart_options.callback = function(e){
          var tip = $element.find('#pieBrowserDetailTooltip');
          $('.nv-slice').off('mouseover').on('mouseover', function (e) {
            var legIndex = $(this).parent().index();
            var legValue = data.nvd3[legIndex].key || '';
          });
          $('.nv-slice').off('mouseout').on('mouseout', function (e) {
            tip.hide();
          });
        };
        return chart_options;
      }


      function buildParameter(paras){
        for(var i=0,len=$scope.options.length; i<len; i++){
          var topt = $scope.options[i];
          if(topt.type === 'select'){
            var tavalp = topt.applied.value;
            paras[topt.name] = angular.isArray(tavalp) ? {'arr':tavalp} : tavalp;
            if(topt.applied.children){
              var taval = topt.applied.children.applied.value || topt.applied.children.selected.value;
              paras[topt.applied.children.name] = angular.isArray(taval) ? {'arr':taval} : taval;
            }
          }else{
            paras[topt.name] = topt.applied;
          }
          
        }
        if($scope.contentType === 'distToCV'){
          paras.ctype = 'pathdist';
          paras.tmpl  = 'table';
        }
        return paras;
      }

      function getData(isforceflg){
         var paras = {
           uid     : $scope.optglbl.uid,
           lid     : $scope.optglbl.lid,
           sdate   : $scope.optglbl.currentimported.start_date,
           edate   : $scope.optglbl.currentimported.end_date,
           ctype   : $scope.contentType,
           dtype   : $scope.chartType,
           tmpl    : $scope.templateType,
           redoflg : isforceflg ? 1 : 0
         };
         if($scope.optglbl.pathpoints !== undefined){
           paras.paths = {'arr': $scope.optglbl.pathpoints};
         }
         paras = buildParameter(paras);
         return API.getContents(paras).$promise;
      }



    },
    controller: function($scope, $element){
      $scope.isLoaded = false;
      $scope.isrunning = false;
      $scope.isNoData = false;
      $scope.tabSelection = -1;
      $scope.fuploadcnt = [];


    }
  };
});
     

