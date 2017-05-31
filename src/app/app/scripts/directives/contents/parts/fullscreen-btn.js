'use strict';
angular.module('auriqCJPath')
  .directive('fullscreenBtn', function ($window, $timeout) {
    function link(scope, $element/*, attrs*/) {
      var W = angular.element($window),
        jWindow = $(window),
        $body = $('body'),
        $widget = angular.element(document.querySelector('#widget-' + scope.widgetid)),
        widgetScope = $widget.scope();
        //$widget = $element.closest('.widget'),
      widgetScope.goFull = true;

      widgetScope.$placeholderDiv = $('<div></div>', {
        'class': 'widget-placeholder'
      });

      function makeFullscreen() {
        var top, left, width, height, originalStyle;
        scope.isfullscreen = widgetScope.goFull;
        if (widgetScope.goFull) {

          top = $widget.position().top;
          left = $widget.position().left;
          width = $widget.outerWidth();
          height = $widget.outerHeight();

          widgetScope.$placeholderDiv.addClass($widget.attr('class'));

          $widget.addClass('pre-fullscreen fullscreen show-topbar');

          widgetScope.$placeholderDiv.insertAfter($widget);

          originalStyle = {
            left: left,
            top: top,
//            width: width,
//            height: height,
          };
          $widget.data('original-style', originalStyle);
          $widget.css(originalStyle);
          $timeout(function () {
            $body.addClass('no-scroll');
            $widget.animate({
              //top: -20, //20 - margin
              //left: -20,
              top: 0, //20 - margin
              left: -20,
//              width: W.width(),
//              height: W.height()
            }, 300, function () {
              widgetScope.goFull = false;
              $widget.addClass('fixed');
              $widget.addClass('no-border-radius');
              jWindow.trigger('resize');
            });
          }, 5);
          if (typeof widgetScope.$parent.initChartToFitScreen !== 'undefined') {
            widgetScope.$parent.initChartToFitScreen(true);
          }
        } else {
          $body.removeClass('no-scroll');
          $widget.removeClass('no-border-radius show-topbar');
          $widget.animate(
            $widget.data('original-style'),
            300, function () {
              widgetScope.$placeholderDiv.remove();
              $widget.removeClass('fixed ');
              $widget.removeClass('fullscreen');
              $widget.removeClass('pre-fullscreen');
              //$widget.removeAttr('style');
              widgetScope.goFull = true;
              jWindow.trigger('resize');
            }
          );
          if (typeof widgetScope.$parent.initChartToFitScreen !== 'undefined' ) {
            widgetScope.$parent.initChartToFitScreen(false);
          }
        }
      }

      $element.bind('click', function () {
        makeFullscreen();
      });
    }

    var directive = {
      link: link,
      //scope: true,
      scope: {
        widgetid : '=',
        isfullscreen : '='
      },
      restrict: 'EA'
    };
    return directive;
  });
