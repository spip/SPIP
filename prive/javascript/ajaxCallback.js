jQuery.spip=jQuery.spip || {};
jQuery.spip.log = function(){
	if (jQuery.spip.debug && window.console && window.console.log)
		window.console.log.apply(this,arguments);
}
// A plugin that wraps all ajax calls introducing a fixed callback function on ajax complete
if(!jQuery.spip.load_handlers) {
	jQuery.spip.load_handlers = new Array();

	/**
	 * OnAjaxLoad allow to
	 * add a function to the list of those
	 * to be executed on ajax load complete
	 *
	 * most of time function f is applied on the loaded data
	 * if not known, the whole document is targetted
	 * 
	 * @param function f
	 */
	function onAjaxLoad(f) {
		jQuery.spip.load_handlers.push(f);
	};

	/**
	 * Call the functions that have been added to onAjaxLoad
	 * @param root
	 */
	jQuery.spip.triggerAjaxLoad = function (root) {
		jQuery.spip.log('triggerAjaxLoad');
		jQuery.spip.log(root);
		for ( var i = 0; i < jQuery.spip.load_handlers.length; i++ )
			jQuery.spip.load_handlers[i].apply( root );
	};

	jQuery.spip.intercepted={};

	// intercept jQuery.fn.load
	jQuery.spip.intercepted.load = jQuery.fn.load;
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
		var callback2 = function() {jQuery.spip.log('jQuery.load');jQuery.spip.triggerAjaxLoad(this);callback.apply(this,arguments);};
		return jQuery.spip.intercepted.load.apply(this,[url, params, callback2]);
	};

	// intercept jQuery.fn.ajaxSubmit
	jQuery.spip.intercepted.ajaxSubmit = jQuery.fn.ajaxSubmit;
	jQuery.fn.ajaxSubmit = function(options){
		// find the first parent that will not be removed by formulaire_dyn_ajax
		// or take the whole document
		if (typeof options.onAjaxLoad=="undefined" || options.onAjaxLoad!=false) {
			var me=jQuery(this).parents('div.ajax');
			if (me.length)
				me=me.parent();
			else
				me = document;
			if (typeof options=='function')
					options = { success: options };
			var callback = options.success || function(){};
			options.success = function(){callback.apply(this,arguments);jQuery.spip.log('jQuery.ajaxSubmit');jQuery.spip.triggerAjaxLoad(me);}
		}
		return jQuery.spip.intercepted.ajaxSubmit.apply(this,[options]);
	}

	// intercept jQuery.ajax
	jQuery.spip.intercepted.ajax = jQuery.ajax;
	jQuery.ajax = function(type) {
		var s = jQuery.extend(true, {}, jQuery.ajaxSettings, type);
		var callbackContext = s.context || s;
		if (jQuery.ajax.caller==jQuery.spip.intercepted.load || jQuery.ajax.caller==jQuery.spip.intercepted.ajaxSubmit)
			return jQuery.spip.intercepted.ajax(type);
		var orig_complete = s.complete || function() {};
		type.complete = function(res,status) {
			// Do not fire OnAjaxLoad if the dataType is not html
			var dataType = type.dataType;
			var ct = (res && (typeof res.getResponseHeader == 'function'))
				? res.getResponseHeader("content-type"): '';
			var xml = !dataType && ct && ct.indexOf("xml") >= 0;
			orig_complete.call( callbackContext, res, status);
			if(!dataType && !xml || dataType == "html") {
				jQuery.spip.log('jQuery.ajax');
				if (typeof s.onAjaxLoad=="undefined" || s.onAjaxLoad!=false)
					jQuery.spip.triggerAjaxLoad(s.ajaxTarget?s.ajaxTarget:document);
			}
		};
		return jQuery.spip.intercepted.ajax(type);
	};

}

/**
 * if not fully visible, scroll the page to position
 * target block at the top of page
 * if force = true, allways scroll
 *
 * @param bool force
 */
jQuery.fn.positionner = function(force) {
	var offset = jQuery(this).offset();
	var hauteur = parseInt(jQuery(this).css('height'));
	var scrolltop = self['pageYOffset'] ||
		jQuery.boxModel && document.documentElement[ 'scrollTop' ] ||
		document.body[ 'scrollTop' ];
	var h = jQuery(window).height();
	var scroll=0;

	if (force || (offset && offset['top'] - 5 <= scrolltop))
		scroll = offset['top'] - 5;
	else if (offset && offset['top'] + hauteur - h + 5 > scrolltop)
		scroll = Math.min(offset['top'] - 5, offset['top'] + hauteur - h + 15);
	if (scroll)
		jQuery('html,body')
		.animate({scrollTop: scroll}, 300);

	// positionner le curseur dans la premiere zone de saisie
	jQuery(jQuery('*', this).filter('input[type=text],textarea')[0]).focus();
	return this; // don't break the chain
}

// deux fonctions pour rendre l'ajax compatible Jaws
jQuery.spip.virtualbuffer_id='spip_virtualbufferupdate';
jQuery.spip.initReaderBuffer = function(){
	if (jQuery('#'+jQuery.spip.virtualbuffer_id).length) return;
	jQuery('body').append('<p style="float:left;width:0;height:0;position:absolute;left:-5000;top:-5000;"><input type="hidden" name="'+jQuery.spip.virtualbuffer_id+'" id="'+jQuery.spip.virtualbuffer_id+'" value="0" /></p>');
}
jQuery.spip.updateReaderBuffer = function(){
	var i = jQuery('#'+jQuery.spip.virtualbuffer_id);
	if (!i.length) return;
	// incrementons l'input hidden, ce qui a pour effet de forcer le rafraichissement du
	// buffer du lecteur d'ecran (au moins dans Jaws)
	i.attr('value',parseInt(i.attr('value'))+1);
}

/**
 * rechargement ajax d'un formulaire dynamique implemente par formulaires/xxx.html
 * @param target
 */
jQuery.fn.formulaire_dyn_ajax = function(target) {
	if (this.length)
		jQuery.spip.initReaderBuffer();
  return this.each(function() {
		var cible = target || this;
		jQuery('form:not(.noajax,.bouton_action_post)', this).each(function(){
		var leform = this;
		var leclk,leclk_x,leclk_y;
		jQuery(this).prepend("<input type='hidden' name='var_ajax' value='form' />")
		.ajaxForm({
			beforeSubmit: function(){
				// memoriser le bouton clique, en cas de repost non ajax
				leclk = leform.clk;
        if (leclk) {
            var n = leclk.name;
            if (n && !leclk.disabled && leclk.type == "image") {
							leclk_x = leform.clk_x;
							leclk_y = leform.clk_y;
            }
        }
				jQuery(cible).wrap('<div />');
				cible = jQuery(cible).parent();
				jQuery(cible).animeajax().positionner(false);
			},
			success: function(c){
				if (c=='noajax'){
					// le serveur ne veut pas traiter ce formulaire en ajax
					// on resubmit sans ajax
					jQuery("input[name=var_ajax]",leform).remove();
					// si on a memorise le nom et la valeur du bouton clique
					// les reinjecter dans le dom sous forme de input hidden
					// pour que le serveur les recoive
					if (leclk){
            var n = leclk.name;
            if (n && !leclk.disabled) {
							jQuery(leform).prepend("<input type='hidden' name='"+n+"' value='"+leclk.value+"' />");
							if (leclk.type == "image") {
								jQuery(leform).prepend("<input type='hidden' name='"+n+".x' value='"+leform.clk_x+"' />");
								jQuery(leform).prepend("<input type='hidden' name='"+n+".y' value='"+leform.clk_y+"' />");
							}
						}
					}
					jQuery(leform).ajaxFormUnbind().submit();
				}
				else {
					jQuery(cible).html(c);
					var a = jQuery('a:first',cible).eq(0);
					var d = jQuery('div.ajax',cible);
					if (!d.length)
						// si pas .ajax dans le form, remettre la classe sur le div que l'on a insere
						jQuery(cible).addClass('ajax').removeClass('loading');
					else {
						// sinon nettoyer les br ajaxie
						d.siblings('br.bugajaxie').remove();
						// desemboiter d'un niveau pour manger le div que l'on a insere
						jQuery(":first",cible).unwrap();
					}
					// ne pas re-executer le js
					if (a.length
					  && a.is('a[name=ajax_ancre]')
					  && jQuery(a.attr('href'),cible).length){
						a = a.attr('href');
						if (jQuery(a,cible).length)
							setTimeout(function(){
							jQuery(a,cible).positionner(false);
							//a = a.split('#');
							//window.location.hash = a[1];
							},10);
					}
					else{
						//jQuery(cible).positionner(false);
						if (a.length && a.is('a[name=ajax_redirect]')){
							a = a.attr('href');
							jQuery(cible).addClass('loading').animeajax();
							setTimeout(function(){
								document.location.replace(a);
							},10);
						}
					}
					// mettre a jour le buffer du navigateur pour aider jaws et autres readers
					jQuery.spip.updateReaderBuffer();
				}
			},
			iframe: jQuery.browser.msie
		})
		// previent qu'on n'ajaxera pas deux fois le meme formulaire en cas de ajaxload
		// mais le marquer comme ayant l'ajax au cas ou on reinjecte du contenu ajax dedans
		.addClass('noajax hasajax') 
		;
		});
  });
}

// permettre d'utiliser onclick='return confirm('etes vous sur?');' sur un lien ajax
var ajax_confirm=true;
var ajax_confirm_date=0;
var spip_confirm = window.confirm;
function _confirm(message){
	ajax_confirm = spip_confirm(message);
	if (!ajax_confirm) {
		var d = new Date();
		ajax_confirm_date = d.getTime();
	}
	return ajax_confirm;
}
window.confirm = _confirm;

// rechargement ajax d'une noisette implementee par {ajax}
// selecteur personalise, sera defini par defaut a '.pagination a,a.ajax'
var ajaxbloc_selecteur;
// mise en cache des url. Il suffit de vider cete variable pour vider le cache
jQuery.spip.preloaded_urls = {};
jQuery.spip.on_ajax_loaded = function(blocfrag,c,u) {
	jQuery(blocfrag)
	.html(c)
	.removeClass('loading');
	if (typeof u != undefined)
		jQuery(blocfrag).attr('data-url',u);
	var a = jQuery('a:first',jQuery(blocfrag)).eq(0);
	if (a.length
		&& a.is('a[name=ajax_ancre]')
		&& jQuery(a.attr('href'),blocfrag).length){
			a = a.attr('href')
		setTimeout(function(){
			jQuery(a,blocfrag).positionner(false);
			//a = a.split('#');
			//window.location.hash = a[1];
		},10);
	}
	else {
		//jQuery(blocfrag).positionner(false);
	}
	jQuery.spip.log('on_ajax_loaded');
	jQuery.spip.triggerAjaxLoad(blocfrag);
	// si le fragment ajax est dans un form ajax,
	// il faut remettre a jour les evenements attaches
	// car le fragment peut comporter des submit ou button
	a = jQuery(blocfrag).parents('form.hasajax')
	if (a.length)
		a.eq(0).removeClass('noajax').parents('div.ajax').formulaire_dyn_ajax();
	jQuery.spip.updateReaderBuffer();
}

jQuery.spip.loadAjax = function(blocfrag,url, href, force, callback){
	jQuery(blocfrag)
	.animeajax()
	.addClass('loading').positionner(false);
	if (jQuery.spip.preloaded_urls[url] && !force) {
		jQuery.spip.on_ajax_loaded(blocfrag,jQuery.spip.preloaded_urls[url],href);
	} else {
		jQuery.ajax({
			url: url,
			onAjaxLoad:false,
			success: function(c){
				jQuery.spip.on_ajax_loaded(blocfrag,c,href);
				jQuery.spip.preloaded_urls[url] = c;
				if (callback && typeof callback == "function")
					callback.apply(blocfrag);
			}
		});
	}
}

jQuery.spip.makeAjaxUrl = function(href,ajax_env){
	var url = href.split('#');
	url[0] = parametre_url(url[0],'var_ajax',1);
	url[0] = parametre_url(url[0],'var_ajax_env',ajax_env);
	if (url[1])
		url[0] = parametre_url(url[0],'var_ajax_ancre',url[1]);
	return url[0];
}

jQuery.fn.ajaxbloc = function() {
	if (this.length)
		jQuery.spip.initReaderBuffer();
	if (ajaxbloc_selecteur==undefined)
		ajaxbloc_selecteur = '.pagination a,a.ajax';

  return this.each(function() {
	  jQuery('div.ajaxbloc',this).ajaxbloc(); // traiter les enfants d'abord
		var blocfrag = jQuery(this);

		var ajax_env = (""+blocfrag.attr('class')).match(/env-([^ ]+)/);
		if (!ajax_env || ajax_env==undefined) return;
		ajax_env = ajax_env[1];

	  jQuery(this).not('.bind-ajaxReload').bind('ajaxReload',function(event, options){
		  var href = $(this).attr('data-url') || $(this).attr('data-origin');
		  if (href && typeof href != undefined){
			  options == options || {};
			  var callback=options.callback || null;
			  var args = options.args || {};
			  for (var key in args)
	        href = parametre_url(href,key,args[key]);
			  var url = jQuery.spip.makeAjaxUrl(href,ajax_env);
			  jQuery.spip.loadAjax(blocfrag, url, href, true, callback);
			  // don't trig reload of parent blocks
			  event.stopPropagation();
		  }
	  }).addClass('bind-ajaxReload');

		jQuery(ajaxbloc_selecteur,this).not('.noajax').not('.bind-ajax').each(function(){
			if (jQuery(this).is('.preload')){
				var href = this.href;
				var url = jQuery.spip.makeAjaxUrl(href,ajax_env);
				if (!jQuery.spip.preloaded_urls[url]) {
					jQuery.ajax({"url":url,onAjaxLoad:false,"success":function(r){jQuery.spip.preloaded_urls[url]=r;}});
				}
			}
			jQuery(this).click(function(){
				if (!ajax_confirm) {
					// on rearme pour le prochain clic
					ajax_confirm=true;
					var d = new Date();
					// seule une annulation par confirm() dans les 2 secondes precedentes est prise en compte
					if ((d.getTime()-ajax_confirm_date)<=2)
						return false;
				}
				var href = this.href;
				var url = jQuery.spip.makeAjaxUrl(href,ajax_env);
				jQuery.spip.loadAjax(blocfrag, url, href, jQuery(this).is('.nocache'));
				return false;
			});
		}).addClass('bind-ajax'); // previent qu'on ajax pas deux fois le meme lien
		// ajaxer les boutons actions qui sont techniquement des form minimaux
		// mais se comportent comme des liens
		jQuery('form.bouton_action_post.ajax', this).not('.noajax').not('.bind-ajax').each(function(){
			var leform = this;
			var url = jQuery(this).attr('action').split('#');
			jQuery(this)
			.prepend("<input type='hidden' name='var_ajax' value='1' /><input type='hidden' name='var_ajax_env' value='"+(ajax_env)+"' />"+(url[1]?"<input type='hidden' name='var_ajax_ancre' value='"+url[1]+"' />":""))
			.ajaxForm({
				beforeSubmit: function(){
					jQuery(blocfrag).addClass('loading').animeajax().positionner(false);
				},
				onAjaxLoad:false,
				success: function(c){
					jQuery.spip.on_ajax_loaded(blocfrag,c);
					jQuery.spip.preloaded_urls = {}; // on vide le cache des urls car on a fait une action en bdd
				},
				iframe: jQuery.browser.msie
			})
			.addClass('bind-ajax') // previent qu'on n'ajaxera pas deux fois le meme formulaire en cas de ajaxload
			;
		});
  });
};

jQuery.fn.followLink = function(){
	$(this).click();
	if (!$(this).is('.bind-ajax'))
		window.location.href = $(this).get(0).href;
	return this;
}
/**
 * Recharger un bloc ajax pour le mettre a jour
 * ajaxid est l'id passe en argument de INCLURE{ajax=ajaxid}
 * options permet de definir une callbackk ou de passer des arguments a l'url
 * au rechargement
 * ajaxReload peut s'utiliser en passant un id :
 * ajaxReload('xx');
 * ou sur un objet jQuery
 * jQuery(this).ajaxReload();
 * Dans ce dernier cas, le plus petit conteneur ajax est recharge
 *
 * @param string ajaxid
 * @param object options
 *  callback : callback after reloading
 *  args : {arg:value,...} to pass tu the url
 */
function ajaxReload(ajaxid, options){
	jQuery('div.ajaxbloc.ajax-id-'+ajaxid).ajaxReload(options);
}

/**
 * Variante jQuery de ajaxReload pour la syntaxe
 * jQuery(..).ajaxReload();
 * cf doc ci-dessus
 * @param options
 */
jQuery.fn.ajaxReload = function(options){
	options = options||{};
	// just trigg the event, as it will bubble up the DOM
	jQuery(this).trigger('ajaxReload', [options]);
	return this; // don't break the chain
}

/**
 * animation du bloc cible pour faire patienter
 *
 */
jQuery.fn.animateLoading = function() {
	this.children().css('opacity', 0.5);
	if (typeof ajax_image_searching != 'undefined'){
		var i = (this).find('.image_loading');
		if (i.length) i.eq(0).html(ajax_image_searching);
		else this.prepend('<span class="image_loading">'+ajax_image_searching+'</span>');
	}
	return this; // don't break the chain
}
// compatibilite avec ancien nommage
jQuery.fn.animeajax = jQuery.fn.animateLoading;

/**
 * animation d'un item que l'on supprime :
 * ajout de la classe remove avec un background tire de cette classe
 * puis fading vers opacity 0
 * quand l'element est masque, on retire les classes et css inline
 *
 * @param function callback 
 *
 */
jQuery.fn.animateRemove = function(callback){
	if (this.length){
		var color = $("<div class='remove'></div>").css('background-color');
		$(this).addClass('remove').css({backgroundColor: color}).animate({opacity: "0.0"}, 'fast',function(){
			$(this).removeClass('remove').css({backgroundColor: ''});
			if (callback)
				callback.apply(this);
		});
	}
	return this; // don't break the chain
}

/**
 * animation d'un item que l'on ajoute :
 * ajout de la classe append
 * fading vers opacity 1 avec background herite de la classe append,
 * puis suppression progressive du background pour revenir a la valeur heritee
 *
 * @param function callback
 */
jQuery.fn.animateAppend = function(callback){
	if (this.length){
		var me=this;
		// recuperer la couleur portee par la classe append (permet une personalisation)
		var color = $("<div class='append'></div>").css('background-color');
		var origin = $(this).css('background-color') || '#ffffff';
		// pis aller
		if (origin=='transparent') origin='#ffffff';
		var sel=$(this);
		// if target is a tr, include td childrens cause background color on tr doesn't works in a lot of browsers
		if (sel.is('tr'))
			sel.add('>td',sel);
		sel.css('opacity','0.0').addClass('append').css({backgroundColor: color}).animate({opacity: "1.0"}, 1000,function(){
			sel.animate({backgroundColor: origin}, 3000,function(){
				sel.removeClass('append').css({backgroundColor: ''});
				if (callback)
					callback.apply(me);
			});
		});
	}
	return this; // don't break the chain
}

/**
 * Equivalent js de parametre_url php de spip
 *
 * Exemples :
 * parametre_url(url,suite,18) (ajout)
 * parametre_url(url,suite,'') (supprime)
 * parametre_url(url,suite) (lit la valeur suite)
 * parametre_url(url,suite[],1) (tableau valeux multiples)
 * @param url
 *   url
 * @param c
 *   champ
 * @param v
 *   valeur
 * @param sep
 *  separateur '&' par defaut
 */
function parametre_url(url,c,v,sep){
	var p;
	// lever l'#ancre
	var ancre='';
	var a='./';
	var args=[];
	p = url.indexOf('#');
	if (p!=-1) {
		ancre=url.substring(p);
		url = url.substring(0,p);
	}

	// eclater
	p=url.indexOf('?');
	if (p!==-1){
		// recuperer la base
		if (p>0) a=url.substring(0,p);
		args = url.substring(p+1).split('&');
	}
        else
            a=url;
	var regexp = new RegExp('^(' + c.replace('[]','\[\]') + '\[?\]?)(=.*)?$');
	var ajouts = [];
	var u = (typeof(v)!=='object')?encodeURIComponent(v):v;
	var na = [];
	// lire les variables et agir
	for(var n=0;n<args.length;n++){
		var val = args[n];
		val = decodeURIComponent(val);
		var r=val.match(regexp);
		if (r && r.length){
			if (v==null){
				return (r.length>2)?r[2].substring(1):'';
			}
			// suppression
			else if (!v.length) {
			}
			// Ajout. Pour une variable, remplacer au meme endroit,
			// pour un tableau ce sera fait dans la prochaine boucle
			else if (r[1].substring(-2) != '[]') {
				na.push(r[1]+'='+u);
				ajouts.push(r[1]);
			}
			else na.push(args[n]);
		}
		else
			na.push(args[n]);
	}

	if (v==null) return v; // rien de trouve
	// traiter les parametres pas encore trouves
	if (v || v.length) {
		ajouts = "="+ajouts.join("=")+"=";
		var all=c.split('|');
		for (n=0;n<all.length;n++){
			if (ajouts.search("="+all[n]+"=")==-1){
				if (typeof(v)!=='object'){
				  na.push(all[n] +'='+ u);
				}
				else {
					var id = ((all[n].substring(-2)=='[]')?all[n]:all[n]+"[]");
					for(p=0;p<v.length;p++)
						na.push(id +'='+ encodeURIComponent(v[p]));
				}
			}
		}
	}

	// recomposer l'adresse
	if (na.length){
		if (!sep) sep='&';
			a = a+"?"+na.join(sep);
	}

	return a + ancre;
}



// Ajaxer les formulaires qui le demandent, au demarrage

jQuery(function() {
	jQuery('form:not(.bouton_action_post)').parents('div.ajax')
	.formulaire_dyn_ajax();
	jQuery('div.ajaxbloc').ajaxbloc();
	jQuery("input[placeholder]:text").placeholderLabel();
});

// ... et a chaque fois que le DOM change
onAjaxLoad(function() {
	if (jQuery){
		jQuery('form:not(.bouton_action_post)', this).parents('div.ajax')
			.formulaire_dyn_ajax();
		if (jQuery(this).is('div.ajaxbloc'))
			jQuery(this).ajaxbloc();
		jQuery('div.ajaxbloc', this)
			.ajaxbloc();
		jQuery("input[placeholder]:text",this).placeholderLabel();
	}
});

