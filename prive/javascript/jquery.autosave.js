/**
 * autosave plugin
 *
 * Copyright (c) 2009-2014 Fil (fil@rezo.net)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */

/*
 * Usage: $("form").autosave({options...});
 * to use with SPIP's action/session.php
 */

(function($){
	$.fn.autosave = function(opt) {
		opt = $.extend(opt,{
			confirm: false,
			confirmstring: 'Sauvegarder ?'
		});
		$(window)
		.bind('unload',function() {
			$('form.autosavechanged')
			.each(function(){
				if (!opt.confirm || confirm(opt.confirmstring)) {
					var contenu = $(this).serialize();
					$.post('spip.php', {
						'action': 'session',
						'var': 'autosave_' + $('input[name=autosave]', this).val(),
						'val': contenu
					});
				}
			});
		});
		return this
		.bind('change keyup', function() {
			$(this).addClass('autosavechanged');
		})
		.bind('submit',function() {
			$(this).removeClass('autosavechanged');
		});
	}
})(jQuery);

