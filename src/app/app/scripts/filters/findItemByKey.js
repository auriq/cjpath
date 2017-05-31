'use strict';
//http://krispo.github.io/angular-nvd3/#/ - framework
//https://github.com/mbostock/d3/wiki/Formatting - formating
//https://github.com/johnpapa/angularjs-styleguide  - style guide

angular.module('auriqCJPath')
  .filter('findItemByKey', function () {
    return function (input, id) {
      var i = 0, len = input.length;
      for (; i < len; i++) {
        if (+input[i].id === +id) {
          return input[i];
        }
      }
      return null;
    };
  });
