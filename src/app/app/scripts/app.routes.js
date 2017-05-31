(function () {
  'use strict';

  angular
    .module('auriqCJPath')
    .config(config);

  config.$inject = ['$routeProvider'];

  function config($routeProvider) {
    $routeProvider
      //.when('/', {
      //  templateUrl: 'views/trends.html',
      //  controller: 'TrendsCtrl',
      //  title: 'Auriq Dashboard'
      //})
      .when('/landing', {
        templateUrl: 'views/landing.html',
        controller: 'LandingCtrl',
        controllerAs: 'vm',
        title: 'Auriq Dashboard'
      })
      .when('/paths', {
        templateUrl: 'views/paths.html',
        controller: 'PathsCtrl',
        controllerAs: 'vm',
        title: 'Auriq Dashboard - Path Report'
      })
      .when('/caches', {
        templateUrl: 'views/caches.html',
        controller: 'CachesCtrl',
        controllerAs: 'vm',
        title: 'Auriq Dashboard - Cache Management'
      })
      //.when('/devtools', {
      //  templateUrl: 'views/devtools.html',
      //  controller: 'DevtoolsCtrl',
      //  controllerAs: 'vm',
      //  title: 'Auriq Dashboard - Development Tools'
      //})
      .when('/configs', {
        templateUrl: 'views/configs.html',
        controller: 'ConfigsCtrl',
        controllerAs: 'vm',
        title: 'Auriq Dashboard - Configuration'
      })
      .otherwise({
        redirectTo: '/landing'
      });
  }
})();
