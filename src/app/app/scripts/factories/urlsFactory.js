'use strict';
angular
  .module('auriqCJPath')
  .factory('urlsFactory', urlsFactory);

urlsFactory.$inject = ['$resource'];

function urlsFactory($resource){
  return {
    getHelp     : 'gd.help.php',
    getContents : 'gd.content.php',
    getOptions  : 'gd.options.php',
    getFilters  : 'gd.filters.php',
    getCacheQueue : 'gd.cachequeue.php',
    saveFilters : 'sv.filters.php',
    essInstance : 'ess.instance.php',
    essUdbd     : 'ess.udbd.php',
    getLogs     : 'gd.logs.php',
    getDbs      : 'gd.db.php',
    fileManipulation : 'mn.file.php'
  };
}
