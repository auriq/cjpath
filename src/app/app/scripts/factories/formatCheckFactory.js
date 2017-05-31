'use strict';
angular.module('auriqCJPath')
  .factory('formatCheckFactory', function formatCheckFactory() {
    // 
    return {
      /*
      *  Check Date Format, YYY-mm-dd
      *  argument : obj
      *     obj needs to have "value" attribute and this function will check tha value of the "value" attribute.
      *     this function will set value of "iserror" and "message" as a result of check.
      * */
      check_date : function(obj){
        var val = obj.value;
        if(val === ''){
          obj.iserror = true;
          obj.message = 'this field cannot be empty';
        }else{
          if(val.match(/^\d{4}([./-])\d{2}\1\d{2}$/)){
            var spl = val.split('-');
            var y = spl[0], m=spl[1], d = spl[2];
            if(y > 1970 && (m>=1 && m<=12) && (d>=1 && d<=31)){
              obj.iserror = false;
              obj.message = '';
            }else{
              obj.iserror = true;
              obj.message = 'invalid format';
            }
          }else{
            obj.iserror = true;
            obj.message = 'invalid format';
          }
        }
      },
      /*
      *  Check Integer format,
      *  argument : obj
      *     obj needs to have "value" attribute and this function will check tha value of the "value" attribute.
      *     this function will set value of "iserror" and "message" as a result of check.
      * */
      check_int : function(obj){
        var val = obj.value;
        if(val === ''){
          obj.iserror = true;
          obj.message = 'this field cannot be empty';
        }else{
          if(val % 1 === 0){
            obj.iserror = false;
            obj.message = '';
          }else{
            obj.iserror = true;
            obj.message = 'invalid format';
          }
        }
      }
    };
  }
);
