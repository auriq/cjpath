'use strict';

angular.module('auriqCJPath')
  .directive('aqTable', function (colorsFactory, $timeout, $filter, urlsFactory, $http, API, API_BASE, $rootScope, $window) {

  return {
    restrict: 'EA',
    templateUrl: 'views/directives/templ-aq-table.html',
    scope: {
      data   : '=',
      height : '=?',
      width  : '=?',
      ctype  : '=?',
      header : '=?',
      lang   : '=?'
    },
    link: function ($scope, $element) {
      //------------------------
      //
      //  Define variables
      //
      //------------------------
      // define chart options
      var colors_all = colorsFactory.getAll();
      var colors = [colors_all[0], '#d5dde0'];

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
        var data = ($scope.data!==undefined) ? ($scope.data.tbody || []) : []; 
        var lang = ($scope.lang!== undefined) ? $scope.lang : 'English';
        if(data.length > 0){
          if($scope.height !== undefined || $scope.width !== undefined){
            var wrapperstyle = {};
            var ftablestyle  = {};
            if($scope.height !== undefined){
              wrapperstyle.height = $scope.height;
              ftablestyle.height  = (parseFloat($scope.height.replace('px', '')) - 50).toString() + 'px';
            }
            if($scope.width !== undefined){
              wrapperstyle.width = $scope.width;
            }
            $scope.ftablestyle  = ftablestyle;
            $scope.wrapperstyle = wrapperstyle;
          }
        }       

      });



    },
    controller: function($scope, $element){
      $scope.isLoaded = false;


    }
  };
});
     

