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

// rechargement ajax d'un formulaire dynamique implemente par formulaires/forumlaire_.html
jQuery.fn.formulaire_dyn_ajax = function(target) {
	this
	.not('.noajax')
	.prepend("<"+"input type='hidden' name='var_ajax' value='1' /"+">")
	.ajaxForm({"target":'#'+target,
			"beforeSubmit":
			function(){
				$('#'+target).animeajax().addClass('loading');
			},
			"success":
			function(){
				$('#'+target).removeClass('loading');
			}
	})
	.addClass('.noajax');	// previent qu'on n'ajaxera pas deux fois le meme formulaire en cas de ajaxload
	return this; // don't break the chain
}

// rechargement ajax d'une noisette implementee par fond/ajax.html
// avec mise en cache des url
var preloaded_urls = {};
var ajaxbloc_selecteur;
jQuery.fn.ajaxbloc = function() {
		var blocfrag = this;
		var ajax_env = $('input[@name=var_ajax_env]',this).eq(0).attr('value');
		if (!ajax_env || ajax_env==undefined) return;
		var ajax_cle = $('input[@name=var_ajax_cle]',this).eq(0).attr('value');
		if (!ajax_cle || ajax_cle==undefined) return;
		if (ajaxbloc_selecteur==undefined)
			ajaxbloc_selecteur = '.pagination a,a.ajax';
		$(ajaxbloc_selecteur,this).not('.noajax').each(function(){
			var url = this.href.split('#');
			url[0] += (url[0].indexOf("?")>0 ? '&':'?')+'var_ajax=1&var_ajax_env='+ajax_env+'&var_ajax_cle='+ajax_cle;
			if ($(this).is('.preload') && !preloaded_urls[url[0]]) {
				$.ajax({"url":url[0],"success":function(r){preloaded_urls[url[0]]=r;}});
			}
			$(this).click(function(){
				$(blocfrag).animeajax().addClass('loading');
				var on_pagination = function(contenu) {
					preloaded_urls[url[0]] = contenu;
					$(blocfrag).html(preloaded_urls[url[0]]);
					$(blocfrag).removeClass('loading');
					window.location.hash = url[1];
				}
				if(preloaded_urls[url[0]]) {
					on_pagination(preloaded_urls[url[0]]);
					triggerAjaxLoad(blocfrag);
				} else {
					$.ajax({"url":url[0],"success":on_pagination});
				}
				return false;
			});
		}).addClass('.noajax'); // previent qu'on ajax pas deux fois le meme lien
};