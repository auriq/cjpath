'use strict';

angular.module('auriqCJPath').config(['$httpProvider', '$sceDelegateProvider', 'API_BASE', function($httpProvider, $sceDelegateProvider, API_BASE){
  //$sceProvider.enabled(false);
  $sceDelegateProvider.resourceUrlWhitelist([
    'self',
    API_BASE + '/**'
    //'**'
  ]);
  $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;application/json;charset=utf-8';
  $httpProvider.defaults.withCredentials = true;
}]);

