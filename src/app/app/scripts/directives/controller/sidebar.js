(function () {
  'use strict';

  angular
    .module('auriqCJPath')
    .directive('sidebar', sidebar);

  sidebar.$inject = ['$location', 'pathsService', '$rootScope', '$timeout'];

  function sidebar($location, pathsService, $rootScope, $timeout) {
    var directive = {
      restrict: 'A',
      templateUrl: 'views/directives/templ-sidebar.html',
      link: link
    };

    return directive;

    /////////////

    function link(scope) {
      scope.isAuthUser = $rootScope.masterFlg;
      scope.groups = groups;
      scope.toggleSidebar = toggleSidebar;
      scope.activateItem = activateItem;

      scope.paths        = pathsService.contentsList;

      /* Initial sidebar state */
      scope.isSidebarOpened = false;

      scope.showSidebarCharts = false;

      // Path Contents
      var path = $location.path();
      angular.forEach(groups, function (group) {
        var isCurrent = group.path === path;

        group.isActive = isCurrent;
        group.isOpen = isCurrent;
      });

      ////////////

      function toggleSidebar() {
        scope.isSidebarOpened = !scope.isSidebarOpened;
        $rootScope.bodyClass = (scope.isSidebarOpened) ? 'no-scroll' : '';
        //if (scope.isSidebarOpened) {
        //  $('html, body').addClass('no-scroll');
        //} else {
        //  $('html, body').removeClass('no-scroll');
        //}
      }

      function activateItem(group) {
        if (group.isActive) {
          group.isOpen = !group.isOpen;
          return;
        }

        angular.forEach(groups, function (group) {
          group.isActive = false;
          group.isOpen   = false;
        });

        group.isActive = true;
        group.isOpen   = true;
        $location.path(group.path);
        $timeout(function(){
          scope.isSidebarOpened = true;
          toggleSidebar();
        }, 400);
      }

      var pagename = $location.path().replace('/','');
      if(pagename==='landing') pagename = 'paths';
      activateItem(groups[pagename]);

    }

  }

  var
    groups = {
      paths: {
        name: 'paths',
        isActive: false,
        isOpen: false,
        path: '/paths'
      },
      caches : {
        name: 'caches',
        isActive: false,
        isOpen: false,
        path: '/caches'
      },
      //devtools : {
      //  name: 'devtools',
      //  isActive: false,
      //  isOpen: false,
      //  path: '/devtools'
      //},
      configs : {
        name: 'configs',
        isActive: false,
        isOpen: false,
        path: '/configs'
      }
    };
})();
