'use strict';

angular.module('auriqCJPath')
  .directive('viewCalcSetting', function (colorsFactory, $timeout, $filter, urlsFactory, $http, API, $rootScope, $window) {

  return {
    restrict: 'EA',
    templateUrl: 'views/directives/templ-path-view-calcsetting.html',
    scope: {
      optglbl: '=',
      iscollapsed: '=',
      issidebaropened: '='
    },
    link: function ($scope, $element) {

      var deleteList = [];
      var addList    = [];

      //------------------------
      //
      //  Data Binding
      //
      //------------------------
      // run when global option is changed
      $scope.$watchCollection(function(){
        return {
          'iscollapsed' : $scope.iscollapsed
        };
      }, function(){
        if(!$scope.iscollapsed && 'global' in $scope.optglbl){
          //$scope.words = $scope.wordsdict($scope.optglbl.global.lang);
          run();
        }
      });


      //------------------------
      //
      //  functions
      //
      //------------------------
      //  update content 
      function run(){
        $scope.isLoaded = false;
        getData().then(function(data){
          $scope.words = data.wordsdict;
          // set options
          setView(data.data);
          $scope.isLoaded = true;
        });
      }

      $scope.close = function(){
        $scope.iscollapsed = !$scope.iscollapsed;
      };


      function getData(){
         var paras = {
           uid     : $scope.optglbl.uid,
           lid     : $scope.optglbl.lid,
           gettype : 'infoview',
	   sdate   : $scope.optglbl.global.currentimported.start_date,
	   edate   : $scope.optglbl.global.currentimported.end_date
         };
         return API.getFilters(paras).$promise;
      }

      function setView(data){
        $scope.tbody = data.tbody;
        $scope.thead = data.thead;
      }


    },
    controller: function($scope, $element){
      $scope.isLoaded = false;
    }
  };
});
     

