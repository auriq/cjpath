'use strict';

angular.module('auriqCJPath')
.filter('ordinalNumberSuffix', function () {
  return function(input) {
    var suffixes = ['th', 'st', 'nd', 'rd'];
    var num = input;
    if(!isFinite(num)){
      num = parseInt(input.replace('st',''));
    }
    var d1 = num % 10;
    var d2 = Math.floor(( num / 10) % 10);
    var suffix = (d2 !== 1 && d1 < suffixes.length-1) ? suffixes[d1] : suffixes[0];
    return num.toString()+suffix;
  };
});
