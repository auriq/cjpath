'use strict';

/**
 * @ngdoc directive
 * @name d3WithAngularJsApp.directive:d3PathToConv
 * @description
 * # d3PathToConv
 */
angular.module('auriqCJPath')
//.controller('detailStatOfLoadedDataCtrl', ['$scope', '$uibModalInstance', 'urlsFactory', 'API_BASE', 'items', function(){
.controller('detailStatOfLoadedDataCtrl', ['$scope', '$uibModalInstance', 'items', function($scope, $uibModalInstance, items){

  $scope.datadict   = angular.copy(items.datadict);
  $scope.logtypes   = angular.copy(items.logtypes);
  $scope.counttypes = angular.copy(items.counttypes);
  $scope.datatypes  = angular.copy(items.datatypes);

  $scope.cancel = function(){
    $uibModalInstance.dismiss('cancel');
  };

}])
.directive('essLoadedDataView', ['d3Service', 'colorsFactory', 'API', 'API_BASE', 'urlsFactory', '$parse', '$timeout', '$uibModal', function (d3Service, colorsFactory, API, API_BASE, urlsFactory, $parse, $timeout, $uibModal) {
  var d3 = d3Service.d3;
  return {
    restrict: 'EA',
    templateUrl: 'views/directives/templ-ess-loaded-data-view.html',
    scope:{
      uid : '=',
      lid : '=',
      isessready: '=',
    },
    link: function($scope, $element){


      $scope.$watchCollection(function(){
        return {
          'isessready' : $scope.isessready,
          'uid'        : $scope.uid,
          'lid'        : $scope.lid,
        };
      }, function(){
        if($scope.isessready == true && $scope.isLoaded && $scope.uid && $scope.lid){
          $scope.isLoaded = false;
          get_data();
        }

      });


      function get_data(){
         var params = {
           uid   : $scope.uid,
           lid   : $scope.lid,
           ctype : 'essloadedsumm',
           tmpl  : 'essloadedsumm'
        };
        $scope.isLoaded = false;
        API.getContents(params).$promise.then(
          function(data){
            $scope.words = data.wordsdict.title_essamountviewer; // define wrods dict (common)
            $scope.datadict = data.data.datadict;
            $scope.logtypes = data.data.logtypes;
            $scope.counttypes = data.data.counttypes;
            $scope.datatypes = data.data.datatypes;
            $scope.lang = data.data.lang;
            $scope.isLoaded = true;
          },
          function(data){
            if(item.type){
              alert('Disactivate "adblock" to show the summary for "Ad".');
            }
        });
      }

      $scope.openclose = function(pattern){
        $scope.isCollapse = (pattern === 'close');
      };

      $scope.openDetailStatOfLoadedData = function(){
        $uibModal.open({
          animation: true,
          templateUrl: 'views/modals/ess-loaded-summary.html',
          controller: 'detailStatOfLoadedDataCtrl',
          size: 'lg',
          resolve: {
            items: function () {
              return {
                datadict   : $scope.datadict,
                logtypes   : $scope.logtypes,
                counttypes : $scope.counttypes,
                datatypes  : $scope.datatypes
              }
            }
          }
        }).result.then(function(sitem){
        }, function(){
        });
      };


    },
    controller:['$scope', function($scope){
      $scope.isCollapse = true;
      $scope.isLoaded = true;

    }]
  
  };
}]);
