'use strict';

angular.module('auriqCJPath')
  .directive('aqChart', function (colorsFactory, $timeout, $filter, urlsFactory, $http, API, API_BASE, $rootScope, $window) {

  return {
    restrict: 'EA',
    templateUrl: 'views/directives/templ-aq-chart.html',
    scope: {
      data            : '=' ,
      charttype       : '=',
      options         : '=?',
      colors          : '=?',
      yaxistitle      : '=?',
      xaxistitle      : '=?',
      yaxislabels     : '=?',
      xaxislabels     : '=?',
      isshowlegendflg : '=?',
      api             : '=?'
    },
    link: function ($scope, $element) {
      //------------------------
      //
      //  Define variables
      //
      //------------------------
      // define chart options
      var colors_all = colorsFactory.getAll();

      //------------------------
      //
      //  Data Binding
      //
      //------------------------
      // run when data is changed
      $scope.$watchCollection(function(){
        return {
          'data' : $scope.data,
        };
      }, function(){
        if($scope.data !== undefined && $scope.data.length > 0){
          colors_all = ($scope.colors !== undefined && $scope.colors.length>0) ? $scope.colors :  colorsFactory.getAll();
          updateWithData();
        }
      });

$element.find('svg').remove();


      //------------------------
      //
      //  functions
      //
      //------------------------
      //  update content 
      function updateWithData(){
        $scope.isLoaded = false;
        $scope.origData = [];
        if($element.find('svg').length > 0){
          var svgs = $element.find('svg');
          for(var i=0,len=svgs.length; i<len; i++){
            svgs[i].innerHTML = '';
          }
        }
  
        var legedata = prepareForLegendData($scope.data);
        var chart_options = return_chart_options($scope.charttype, $scope.options);

        // 
        // assign to scope variable.
        //
        if($scope.isshowlegendflg){
          $scope.origData = legedata.original;
        }

        // the data should be always different
        //   If it's the same with previous access, nvd3 is not show any data.
        var chart_data    = $scope.data.slice(0);
        for(var i=0,len=chart_data.length; i<len; i++){
          if('key' in chart_data[i]){
            chart_data[i].timestamp = new Date();
          }
        }


        $scope.chart_options = chart_options;
        $scope.chart_data    = chart_data;
        $scope.isLoaded  = true;
        $scope.isrunning = false;
      }

      function prepareForLegendData(data){
        var origData = data.slice(0);
        var colorDict = {};
        for(var i=0,len=origData.length; i<len; i++){
          var tlabel = origData[i].label || origData[i].key;
          origData[i].color     = (tlabel === 'Other') ? '#d5dde0' : colors_all[i];
          origData[i].isVisible = true;
          origData[i].key       = tlabel;
          colorDict[tlabel] = (tlabel === 'Other') ? '#d5dde0' : colors_all[i];
        }
        return {
          original  : origData.slice(0),
          colordict : $.extend(true, {}, colorDict)
        };
      }

      function return_chart_options(chartType, customOptions){
        // height * width
        var elm_h = $element.parent().height();
        var elm_w = $element.parent().width();
        var min_h = 100;
        var min_w = 200;

        elm_h = (elm_h < min_h) ? min_h : elm_h;
        elm_w = (elm_w < min_w) ? min_w : elm_w;
        elm_h -=  50; 
        //elm_h = ($scope.isshowlegendflg) ? elm_h - 50 : elm_h;

        //------------------------
        //
        var chart_options = {};
        chart_options = {
          chart : {
            type   : chartType,
            height : elm_h,
            width  : elm_w,
            margin : {
              left : 20
            },
            focusEnable: false, // true: scale at bottom, false: no scaling 
            showControls: true,
            showLegend: false,
            showLabels: true,
            tooltips: true,
            color: colors_all,
            labelSunbeamLayout: false // activate it if you want to show legend options
          }
        };


       if(chartType === 'pieChart'){
         chart_options.chart.x = function(d){ return (d !== null) ? d.label : ''; };
         chart_options.chart.y = function(d){ return (d !== null) ? d.value : 0; ;}
       }

       if(chartType === 'lineChart' || chartType === 'linePlusBarChart'){
         // common
         chart_options.chart.clipVoronoi = false;
         chart_options.chart.interpolate = "linear";
         chart_options.chart.transitionDuration = 300;
         chart_options.chart.useInteractiveGuideline = true;
         chart_options.chart.rotateLabels = -60;
         chart_options.chart.reduceXTicks = false;
         chart_options.chart.x = function(d){
           return (d !== null && d !== undefined) ? ((d.x !== undefined) ? d.x : d[0]) : 0;
         };
         chart_options.chart.y = function(d){
           return (d !== null && d !== undefined) ? ((d.y !== undefined) ? d.y : d[1]) : 0;
         };
         // lineChart
         if(chartType === 'lineChart'){
           chart_options.chart.type = 'lineWithFocusChart';
           chart_options.chart.margin = {
             top: 10,
             left: 90
           };
           chart_options.chart.yAxis = {
             tickFormat : function(d, i){
               return (d.toString().indexOf('.',0)!==-1) ? return_percentage_tickformat(d) : d3.format(',f')(d);
             }
           };
           chart_options.chart.forceY = [0];
         }
         // linePlusBarChart
         if(chartType === 'linePlusBarChart'){
            chart_options.chart.margin = {
              top: 10,
              left: 90,
              right: 90
            };
            chart_options.chart.stacked = true;
            chart_options.chart.y1Axis  = {
              tickFormat : function(d, i){
                return (d.toString().indexOf('.',0)!==-1) ? return_percentage_tickformat(d) : d3.format(',f')(d);
              }
            };
            chart_options.chart.y2Axis  = {
              tickFormat : function(d, i){
                return (d.toString().indexOf('.',0)!==-1) ? return_percentage_tickformat(d) : d3.format(',f')(d);
              }
            };
            chart_options.chart.lines = {
              forceY : [0]
            };
            chart_options.chart.bars  = {
              forceY : [0]
            };

         }
       }


       // set customer options which format is object as $scope.options
       if(customOptions !== undefined){
         for(var key in customOptions){
           if(angular.isObject(customOptions[key])){
             for(var smlkey in customOptions[key]){
               chart_options.chart[key] = (chart_options.chart[key] === undefined) ? {} : chart_options.chart[key];
               chart_options.chart[key][smlkey] = customOptions[key][smlkey];
             }
           }else{
             chart_options.chart[key] = customOptions[key];
           }
         }
       }


      // set customer options which is specific about labels and title of "yaxis" and "xaxis"
      if($scope.xaxislabels !== undefined && $scope.xaxislabels.length > 0){
        if(!('xAxis' in chart_options.chart)) chart_options.chart.xAxis = {};
        chart_options.chart.xAxis.tickFormat = function(d, i){
          return $scope.xaxislabels[d];
        };
        if(!('x2Axis' in chart_options.chart)) chart_options.chart.x2Axis = {};
        chart_options.chart.x2Axis.tickFormat = function(d, i){
          return $scope.xaxislabels[d];
        };
      }
      if($scope.xaxistitle !== undefined && $scope.xaxistitle.length > 0){
        if(!('xAxis' in chart_options.chart)) chart_options.chart.xAxis = {};
        chart_options.chart.xAxis.axisLabel = $scope.xaxistitle;
        if(!('x2Axis' in chart_options.chart)) chart_options.chart.x2Axis = {};
        chart_options.chart.x2Axis.axisLabel = $scope.xaxistitle;
      }



      if($scope.yaxistitle !== undefined && $scope.yaxistitle.length > 0){
        if(angular.isArray($scope.yaxistitle)){
          if(!('y1Axis' in chart_options.chart)){
            chart_options.chart.y1Axis = {}
          }
          if(!('y2Axis' in chart_options.chart)){
            chart_options.chart.y2Axis = {}
          }
          chart_options.chart.y1Axis.axisLabel = $scope.yaxistitle[0];
          chart_options.chart.y2Axis.axisLabel = $scope.yaxistitle[1];
        }else{
          if(!('yAxis' in chart_options.chart)){
            chart_options.chart.yAxis = {}
          }
          chart_options.chart.yAxis.axisLabel = $scope.yaxistitle;
        }
      }


if(chart_options.chart.xAxis){
  delete chart_options.chart.xAxis.axisLabel;
}

//       // event
//       chart_options.chart.dispatch = {
//         renderEnd : function(e){
//console.log('end');
//         }
//       };

       return chart_options;

      }

      function return_percentage_tickformat(v){
        return (Math.round((v*100) * 10)/10).toString() + '%';
      }

    },
    controller: function($scope, $element){
      $scope.isLoaded = false;

      // when legend changed
      $scope.rebuildGraph = function (campaign) {
        if($scope.isLoaded){
          
          if(check_ok_redraw(campaign)){
            $scope.isLoaded = false;
            var elmlines = $element.find('svg > *');
            elmlines.remove();
            $timeout(function(){
              redrawGraph(campaign);
              $(elmlines).ready(function(){
                $scope.isLoaded = true;
              });
            }, 0);
          }

        }
      };
      var redrawGraph = function(campaign){
        $scope.isDrawDone = false;
        var data = [];
$scope.chart_data = [];
        campaign.isVisible = !campaign.isVisible;
        for(var i=0,len=$scope.origData.length; i<len; i++){
          var item = $scope.origData[i];
          if (item.isVisible) {
            data.push($scope.origData[i]);
          } else {
            item.isVisible = false;
          }
        };
        $scope.chart_data = data;
      };

      var check_ok_redraw = function(campaign){
        var okflg = true;
        if(campaign.isVisible){
          var cntvis = 0;
          for(var i=0,len=$scope.origData.length; i<len; i++){
            var item = $scope.origData[i];
            if (item.isVisible) {
              cntvis++;
            }
          }
          
          okflg = (cntvis > 1);
        }

        return okflg;
      };

    }
  };
});
     

