;(function($) {
	$.fn.previsu_spip = function(settings) {
		var options;

		options = {
			previewParserPath:	'' ,
			previewParserVar:	'data',
			textEditer:	'Editer',
			textVoir:	'Voir'
		};
		$.extend(options, settings);

		return this.each(function() {

			var $$, textarea, tabs, preview;
			$$ = $(this);
			textarea = this;

			// init and build previsu buttons
			function init() {
				$$.addClass("pp_previsualisation");
				tabs = $('<div class="markItUpTabs"></div>').prependTo($$.parent());
				$(tabs).append(
					'<a href="#previsuVoir" class="previsuVoir">' + options.textVoir + '</a>' +
					'<a href="#previsuEditer" class="previsuEditer on">' + options.textEditer + '</a>'
				);
				
				preview = $('<div class="markItUpPreview"></div>').insertAfter(tabs);
				preview.hide();
				
				$('.previsuVoir').click(function(){
					mark = $(this).parent().parent();
					objet = mark.parents('.formulaire_spip')[0].className.match(/formulaire_editer_(\w+)/);
					champ = mark.parents('li')[0].className.match(/editer_(\w+)/);
					$(mark).find('.markItUpPreview').height(
						  $(mark).find('.markItUpHeader').height()
						+ $(mark).find('.markItUpEditor').height()
						+ $(mark).find('.markItUpFooter').height()
					);

					$(mark).find('.markItUpHeader').hide();
					$(mark).find('.markItUpEditor').hide();
					$(mark).find('.markItUpFooter').hide();
					$(this).addClass('on').next().removeClass('on');
					$(mark).find('.markItUpPreview').show()
						.addClass('ajaxLoad')
						.html(renderPreview(
							$(mark).find('textarea.pp_previsualisation').val(),
							champ[1].toUpperCase(),
							(objet ? objet[1] : ''))
						)
						.removeClass('ajaxLoad');
					
					//ouvre un nouvel onglet lorsqu'on clique sur un lien dans la prévisualisation
					$(".markItUpPreview a").attr("target","blank");

					return false;
				});
				$('.previsuEditer').click(function(){
					mark = $(this).parent().parent();
					$(mark).find('.markItUpPreview').hide();
					$(mark).find('.markItUpHeader').show();
					$(mark).find('.markItUpEditor').show();
					$(mark).find('.markItUpFooter').show();
					$(this).addClass('on').prev().removeClass('on');
					return false;
				});
			}


			function renderPreview(val, champ, objet) {
				var phtml;
				if (options.previewParserPath !== '') {
					$.ajax( {
						type: 'POST',
						async: false,
						url: options.previewParserPath,
						data: 'champ='+champ
							+'&objet='+objet
							+'&' + options.previewParserVar+'='+encodeURIComponent(val),
						success: function(data) {
							phtml = data; 
						}
					} );
				}
				return phtml;
			}
	
			init();
		});
	};
})(jQuery);
