'use strict';
angular.module('auriqCJPath')
  .controller('LandingCtrl', function ($scope, urlsFactory, $rootScope, API, $timeout, $location) {
    var vm = this;

   $scope.contentExist = true;


   /**
   *  Get Available Contents List 
   */
   API.getOptions({
     uid     : uid,
     lid     : lid,
     opttype : 'landing'
   }).$promise.then(function(data){
     var obj = data.data;
     if(obj !== undefined){
       if(obj.paths.isExist === true){
         redir_to_paths(obj);
       }else{
         redir_to_avilable_contents(obj);
       }
     }
   });



   function redir_to_paths(obj){
     $location.path(obj.paths.path);
   }


   function redir_to_avilable_contents(obj){
     var contentExist = false;
     for(var key in obj){
       if(obj[key].isExist === true){
         contentExist = true;
         $location.path(obj[key].path);
         return false;
       }
     }
     $scope.contentExist = contentExist;
   }

});
