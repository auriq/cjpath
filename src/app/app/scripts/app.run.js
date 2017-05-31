(function () {
  'use strict';

  angular
    .module('auriqCJPath')
    .run(run);

  run.$inject = ['$rootScope', '$window', 'colorsFactory'];

  function run($rootScope, $window, colorsFactory) {
    $rootScope.projects = $window.projects;
    $rootScope.uid = $window.uid;
    $rootScope.lid = $window.lid;
    $rootScope.insttype = $window.insttype || 'local';
    $rootScope.instnum  = $window.instnum || '1';
    $rootScope.masterFlg = $window.masterFlg;
//    $rootScope.logtype = $window.logtype;
//    $rootScope.userarr = $window.userarr;
    $rootScope.islogout = false;

  }
})();
