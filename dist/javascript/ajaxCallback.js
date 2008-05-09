// A plugin that wraps all ajax calls introducing a fixed callback function on ajax complete
if(!jQuery.load_handlers) {
	jQuery.load_handlers = new Array();
	//
	// Add a function to the list of those to be executed on ajax load complete
	//
	function onAjaxLoad(f) {
		jQuery.load_handlers.push(f);
	};
	
	//
	// Call the functions that have been added to onAjaxLoad
	//
	function triggerAjaxLoad(root) {
    for ( var i = 0; i < jQuery.load_handlers.length; i++ )
			jQuery.load_handlers[i].apply( root );
	};
	
	jQuery.fn._load = jQuery.fn.load;
	
	jQuery.fn.load = function( url, params, callback ) {
	
		callback = callback || function(){};
	
		// If the second parameter was provided
		if ( params ) {
			// If it's a function
			if ( params.constructor == Function ) {
				// We assume that it's the callback
				callback = params;
				params = null;
			} 
		}
		var callback2 = function(res,status) {triggerAjaxLoad(this);callback(res,status);};
		
		return this._load( url, params, callback2 );
	};

	jQuery._ajax = jQuery.ajax;
	
	jQuery.ajax = function(type) {
		//If called by _load exit now because the callback has already been set
		if (jQuery.ajax.caller==jQuery.fn._load) return jQuery._ajax( type);
			var orig_complete = type.complete || function() {};
			type.complete = function(res,status) {
				// Do not fire OnAjaxLoad if the dataType is not html
				var dataType = type.dataType;
				var ct = (res && (typeof res.getResponseHeader == 'function'))
					? res.getResponseHeader("content-type"): '';
				var xml = !dataType && ct && ct.indexOf("xml") >= 0;
				orig_complete(res,status);
				if(!dataType && !xml || dataType == "html") triggerAjaxLoad(document);
		};
		return jQuery._ajax(type); 
	};

}

// animation du bloc cible pour faire patienter
jQuery.fn.animeajax = function(end) {
	this.children().css('opacity', 0.5);
	if (typeof ajax_image_searching != 'undefined')
		this.prepend(ajax_image_searching);
	return this; // don't break the chain
}

// s'il n'est pas totalement visible, scroller pour positionner
// le bloc cible en haut de l'ecran
jQuery.fn.positionner = function() {
	var offset = $(this).offset({'scroll':false});
	var hauteur = parseInt($(this).css('height'));
	var scrolltop = self['pageYOffset'] ||
		$.boxModel && document.documentElement[ 'scrollTop' ] ||
		document.body[ 'scrollTop' ];
	var h = $(window).height();
	var scroll=0;
	if (offset['top'] - 5 <= scrolltop)
		scroll = offset['top'] - 5;
	else if (offset['top'] + hauteur - h + 5 > scrolltop)
		scroll = offset['top'] + hauteur - h + 15;
	if (scroll)
		jQuery('html,body')
		.animate({scrollTop: scroll}, 300);

	// positionner le curseur dans la premiere zone de saisie
	jQuery(jQuery('*', this).filter('input[@type=text],textarea')[0]).focus();
}

// rechargement ajax d'un formulaire dynamique implemente par formulaires/xxx.html
jQuery.fn.formulaire_dyn_ajax = function(target) {
	if (typeof target == 'undefined') target = this;
	this
	.find('form:not(.noajax)')
		.prepend("<input type='hidden' name='var_ajax' value='form' />")
		.ajaxForm({
			beforeSubmit: function(){
				jQuery(target).addClass('loading').animeajax();
			},
			success: function(c){
				var d = jQuery('.ajax',
					jQuery('<div><\/div>').html(c));
				if (d.length)
					c = d.html();
				jQuery(target)
				.removeClass('loading')
				.html(c)
				.positionner();
			},
			iframe: jQuery.browser.msie
		})
		.addClass('noajax') // previent qu'on n'ajaxera pas deux fois le meme formulaire en cas de ajaxload
	.end();
	return this; // don't break the chain
}

// rechargement ajax d'une noisette implementee par fond/ajax.html
// avec mise en cache des url
var preloaded_urls = {};
var ajaxbloc_selecteur;
jQuery.fn.ajaxbloc = function() {
		var blocfrag = this;

		var on_pagination = function(c) {
			jQuery(blocfrag)
			.html(c)
			.removeClass('loading')
			.positionner();
		}

		var ajax_env = (""+this.attr('class')).match(/env-([^ ]+)/);
		if (!ajax_env || ajax_env==undefined) return;
		ajax_env = ajax_env[1];
		var ajax_cle = (""+this.attr('class')).match(/cle-([^ ]+)/);
		if (!ajax_cle || ajax_cle==undefined) return;
		ajax_cle = ajax_cle[1];
		if (ajaxbloc_selecteur==undefined)
			ajaxbloc_selecteur = '.pagination a,a.ajax';
		jQuery(ajaxbloc_selecteur,this).not('.noajax').each(function(){
			var url = this.href.split('#');
			url[0] += (url[0].indexOf("?")>0 ? '&':'?')+'var_ajax=1&var_ajax_env='+ajax_env+'&var_ajax_cle='+ajax_cle;
			if (jQuery(this).is('.preload') && !preloaded_urls[url[0]]) {
				jQuery.ajax({"url":url[0],"success":function(r){preloaded_urls[url[0]]=r;}});
			}
			jQuery(this).click(function(){
				jQuery(blocfrag)
				.animeajax()
				.addClass('loading');
				if (preloaded_urls[url[0]]) {
					on_pagination(preloaded_urls[url[0]]);
					triggerAjaxLoad(document);
				} else {
					jQuery.ajax({
						url: url[0],
						success: function(c){
							on_pagination(c);
							preloaded_urls[url[0]] = c;
						}
					});
				}
				return false;
			});
		}).addClass('noajax'); // previent qu'on ajax pas deux fois le meme lien
};

// Ajaxer les formulaires qui le demandent, au demarrage
jQuery(function() {
	jQuery('.ajax').formulaire_dyn_ajax();
	jQuery('.ajaxbloc').ajaxbloc();
});
// ... et a chaque fois que le DOM change
onAjaxLoad(function() {
	jQuery('.ajax', this)
	.formulaire_dyn_ajax();
	jQuery('.ajaxbloc', this)
	.ajaxbloc();
});

