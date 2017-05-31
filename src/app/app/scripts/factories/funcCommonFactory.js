
'use strict';
angular
  .module('auriqCJPath')
  .factory('funcCommonFactory', funcCommonFactory);

//urlsFactory.$inject = ['$resource'];
funcCommonFactory.$inject = ['API', '$q'];

//function urlsFactory($resource){
function funcCommonFactory(API, $q){
  var pradiclass = 'pradi_';
  var funcs = {
    checkIfYouAreHijacked : function(uid, lid){
      var ishijacked = false;
      var df = $q.defer();
      API.essInstance({uid:uid, lid:lid, dotype:'check', trgtype:'checkifhijacked'}).$promise.then(function(data){
        var localisused = data.localisused || '';
        var wdisused    = data.wdisused || '';
        if(localisused === 'used' || wdisused === 'used'){
          ishijacked = true;
        }
        df.resolve(ishijacked);
      });
      return df.promise;
    }

  };
  return funcs;
  //return $.extend(true, funcs, commons);
}
