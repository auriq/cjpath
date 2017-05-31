(function () {
  'use strict';

  angular
    .module('auriqCJPath')
    .factory('API', API);

  API.$inject = ['$resource', 'API_BASE', 'urlsFactory'];

  function API($resource, API_BASE, urlsFactory) {
      //method = 'JSONP',
    var
      method = 'POST',
      action = createAction.bind(null, method);

    var urls = {};
    for(var key in urlsFactory){
      urls[key] = action(urlsFactory[key]);
    }

    return $resource(
      API_BASE + '/:entity',
      {},
      urls
    );
  }

  function createAction(method, entity, isArray) {
    return {
      method: method,
      isArray: !!isArray,
      params: (function () {
        var params = {
          entity: entity
        };

        //if (method === 'JSONP') {
        //  params.callback = 'JSON_CALLBACK';
        //}

        return params;
      })()
    };
  }

})();
