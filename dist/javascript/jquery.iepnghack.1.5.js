/*
 * jQuery pngfix plugin
 * Version 1.5  (23/07/2007)
 * @requires jQuery v1.1.3
 *
 * Examples at: http://khurshid.com/jquery/iepnghack/
 * Copyright (c) 2007 Khurshid M.
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 */
 /**
  *
  * @example
  *
  * $('img[@src$=.png], #panel').pngfix();
  *
  * @apply hack to all png images and #panel which icluded png img in its css
  *
  * @name pngfix
  * @type jQuery
  * @cat Plugins/Image
  * @return jQuery
  * @author jQuery Community
  */
 
(function($) {
	/**
	 * helper variables and function
	 */
	var hack = {
		ltie7 : $.browser.msie && /MSIE\s(5\.5|6\.)/.test(navigator.userAgent),
		pixel : 'images/pixel.gif',
		filter : function(src) {
			return "progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true,sizingMethod=crop,src='"+src+"')";
		}
	};
	/**
	 * Applies ie png hack to selected dom elements
	 *
	 * $('img[@src$=.png]').pngfix();
	 * @desc apply hack to all images with png extensions
	 *
	 * $('#panel, img[@src$=.png]').pngfix();
	 * @desc apply hack to element #panel and all images with png extensions
	 *
	 * @name pngfix
	 * @type jQuery
	 * @cat Plugins/pngfix
	 */
	$.fn.pngfix = hack.ltie7 ? function() {

	if (pixelspip) hack.pixel = pixelspip; // indiquer la valeur de l'exterieur du script

    	return this.each(function() {
			var $$ = $(this);
			var base = $('base').attr('href'); // need to use this in case you are using rewriting urls
			if ($$.is('img') || $$.is('input')) { // hack image tags present in dom
				if ($$.attr('src').match(/.*\.png([?].*)?$/i)) { // make sure it is png image
					// use source tag value if set 
					var source = (base && $$.attr('src').substring(0,1)!='/') ? base + $$.attr('src') : $$.attr('src');
					// apply filter
					$$.css({filter:hack.filter(source), width:$$.width(), height:$$.height()})
					  .attr({src:hack.pixel})
					  .positionFix();
				}
			} else { // hack png css properties present inside css
				var image = $$.css('backgroundImage');
				if (image.match(/^url\(["']?(.*\.png([?].*)?)["']?\)$/i)) {
					image = RegExp.$1;
					$$.css({backgroundImage:'none', filter:hack.filter(image)})
					  .positionFix();
				}
			}
		});
	} : function() { return this; };
	/**
	 * Removes any png hack that may have been applied previously
	 *
	 * $('img[@src$=.png]').pngunfix();
	 * @desc revert hack on all images with png extensions
	 *
	 * $('#panel, img[@src$=.png]').iepnghack();
	 * @desc revert hack on element #panel and all images with png extensions
	 *
	 * @name pngunfix
	 * @type jQuery
	 * @cat Plugins/iepnghack
	 */
	$.fn.pngunfix = hack.ltie7 ? function() {
    	return this.each(function() {
			var $$ = $(this);
			var src = $$.css('filter');
			if (src.match(/src=["']?(.*\.png([?].*)?)["']?/i)) { // get img source from filter
				src = RegExp.$1;
				if ($$.is('img') || $$.is('input')) {
					$$.attr({src:src}).css({filter:''});
				} else {
					$$.css({filter:'', background:'url('+src+')'});
				}
			}
		});
	} : function() { return this; };
	/**
	 * positions selected item relatively
	 */
	$.fn.positionFix = function() {
		return this.each(function() {
			var $$ = $(this);
			var position = $$.css('position');
			if (position != 'absolute' && position != 'relative') {
				$$.css({position:'relative'});
			}
		});
	};

})(jQuery);
