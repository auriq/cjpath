'use strict';
angular.module('auriqCJPath')
  .controller('MainCtrl', function ($scope, urlsFactory, $rootScope, API, $timeout, $window) {
    var main = this;
   /**
   *  Get Available Contents List 
   */
   API.getOptions({
     uid: uid,
     lid: lid,
     opttype:'landing'
   }).$promise.then(function(data){
     main.availableContentList = data.data;
   });

    $scope.$watch(function(){
      return {
        'rootScope.islogout'   : $rootScope.islogout
      };
    }, function(){
      main.islogout = $rootScope.islogout
      if(main.islogout){
        API.getOptions({
          uid     : uid,
          lid     : lid,
          opttype : 'wordsdict',
          dtype   : 'loggedout'
        }).$promise.then(function(data){
          main.wordsloggedout = data.wordsdict.loggedout;
        });
        //if($rootScope.lang === 'Japanese'){
        //  main.wordsloggedout = 'ログアウトしました。タブを閉じて下さい。';
        //}else{
        //  main.wordsloggedout = 'logged out. Close the tab.';
        //}
      }
    }, true);

  });
