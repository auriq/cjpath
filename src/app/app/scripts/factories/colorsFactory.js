'use strict';
angular.module('auriqCJPath')
  .factory('colorsFactory', function colorsFactory() {
    var colors = [
//      '#78B1FF',
//      '#73F7B9',
//      '#F67E7C',
//      '#A262F2',
//      '#B9FC90'
//
      '#528be8',
      '#c680d2',
      '#45b29d',
      '#35b1fc',
      '#556c90',
      '#ed9859',
      '#84c98a',
      '#f7565a',
      '#2f3f59',
      '#6d4374',
      '#91b6f2',
      '#e8b852'
    ];
    return {
      getAll: function () {
        return colors;
      },
      limit: function (count) {
        if (count > colors.length) {
          var arrayClone = colors.slice(0);
          for (var i = 0; i < Math.ceil(count / colors.length); i++) {
            colors = colors.concat(arrayClone);
          }
        }
        return colors.slice(0, count);
      },
      getColor: function (index) {
        return colors[index % colors.length];
      }
    };
  }
);
