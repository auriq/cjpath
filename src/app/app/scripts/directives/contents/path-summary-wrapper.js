'use strict';

angular.module('auriqCJPath')
  .directive('pathSummaryWrapper', function (colorsFactory, $timeout, $filter, urlsFactory, $http, API, API_BASE, $rootScope, $window) {

  return {
    restrict: 'EA',
    templateUrl: 'views/directives/templ-path-summary-wrapper.html',
    scope: {
      isapploaded : '=',
      optglbl: '=',
      optcont: '=',
      optpath: '=',
      isessready: '=',
      masterflg : '=',
      filterupdatecnt : '=',
      isviewmode: '='
    },
    link: function ($scope, $element) {

      var cheight_s = 542;
      //------------------------
      //
      //  Data Binding
      //
      //------------------------
      // run when data is changed
      $scope.$watchCollection(function(){
        return {
          'optglbl'          : $scope.optglbl,
          'isessready'        : $scope.isessready
        };
      }, function(){
        if($scope.optglbl && $scope.isessready && $scope.optcont.isVisible){
          if($scope.optcont.isVisible){
            $scope.isactive = true;
            $timeout(function(){
              var containerelm = angular.element($element)[0].getBoundingClientRect();
              cheight_s = (!cheight_s) ? containerelm.height : cheight_s;
              $scope.cheight = cheight_s;
              $scope.cwidth  = containerelm.width;
            }, 1000);
          }else{
            $scope.isactive = false;
          }
        }

      });

      //  when window resize
      var resizeId;
      angular.element($window).bind('resize', function(){
        clearTimeout(resizeId);
        resizeId = setTimeout(doneResizing, 500);
      });
      function doneResizing(){
        $timeout(function(){
          $scope.cwidth = $element.prop('offsetWidth');
        });
      }

      //  when full screen
      $scope.$watchCollection('optcont.isfullscreen', function(){
        $scope.fullscreenFlg = $scope.optcont.isfullscreen;
        if($scope.fullscreenFlg !== undefined){
//          $($element).find('svg').remove();
          $timeout(function() {
            $scope.isactive = true;
            $scope.cheight = ($scope.fullscreenFlg) ? $element.prop('offsetHeight') - 150 : cheight_s;
            $scope.cwidth  = $element.prop('offsetWidth');
          }, 1000);
        }
      });
 


    },
    controller: function($scope, $element){
      $scope.isactive = false;


    }
  };
});
     

