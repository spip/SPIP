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
		options = options || {};
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
		try {
			if (jQuery.ajax.caller==jQuery.spip.intercepted.load || jQuery.ajax.caller==jQuery.spip.intercepted.ajaxSubmit)
				return jQuery.spip.intercepted.ajax(type);
		}
		catch (err){}
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
jQuery.fn.positionner = function(force, setfocus) {
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
	if (setfocus!==false)
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

jQuery.fn.formulaire_setARIA = function(){
	if (!this.closest('.ariaformprop').length){
		// eviter une double execution du js au moment de sa reinsertion dans le DOM par wrap()
		// cf http://bugs.jquery.com/ticket/7447
		this.find('script').remove();
		this.wrap('<div class="ariaformprop" aria-live="assertive" aria-atomic="true"></div>');
	}
	return this;
}
/**
 * rechargement ajax d'un formulaire dynamique implemente par formulaires/xxx.html
 * @param target
 */
jQuery.fn.formulaire_dyn_ajax = function(target) {
	if (this.length)
		jQuery.spip.initReaderBuffer();
  return this.each(function() {
	  var scrollwhensubmit = !jQuery(this).is('.noscroll');
		var cible = target || this;
	  jQuery(cible).formulaire_setARIA();
		jQuery('form:not(.noajax):not(.bouton_action_post)', this).each(function(){
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
				jQuery(cible).closest('.ariaformprop').animateLoading();
				if (scrollwhensubmit)
					jQuery(cible).positionner(false,false);
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
					// commencons par vider le cache des urls, si jamais un js au retour
					// essaye tout de suite de suivre un lien en cache
					// dans le doute sur la validite du cache il vaut mieux l'invalider
					var preloaded = jQuery.spip.preloaded_urls;
					jQuery.spip.preloaded_urls = {};
					jQuery(cible).html(c);
					var a = jQuery('a:first',cible).eq(0);
					var d = jQuery('div.ajax',cible);
					if (!d.length){
						// si pas .ajax dans le form, remettre la classe sur le div que l'on a insere
						jQuery(cible).addClass('ajax');
						if (!scrollwhensubmit)
							jQuery(cible).addClass('noscroll');
					}
					else {
						// sinon nettoyer les br ajaxie
						d.siblings('br.bugajaxie').remove();
						// desemboiter d'un niveau pour manger le div que l'on a insere
						cible = jQuery(":first",cible);
						cible.unwrap();
					}
					// chercher une ancre en debut de html pour positionner la page
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
						jQuery(cible).closest('.ariaformprop').endLoading(true);
					}
					else{
						//jQuery(cible).positionner(false);
						if (a.length && a.is('a[name=ajax_redirect]')){
							a = a.get(0).href;
							setTimeout(function(){
								var cur = window.location.href.split('#');
								document.location.replace(a);
								// regarder si c'est juste un changement d'ancre : dans ce cas il faut reload
								// (le faire systematiquement provoque des bugs)
								if (cur[0]==a.split('#')[0]){
									window.location.reload();
								}
							},10);
							// ne pas arreter l'etat loading, puisqu'on redirige !
							// mais le relancer car l'image loading a pu disparaitre
							jQuery(cible).closest('.ariaformprop').animateLoading();
						}
						else {
							jQuery(cible).closest('.ariaformprop').endLoading(true);
						}
					}
					// si jamais le formulaire n'a pas un retour OK, retablissons le cache
					// car a priori on a pas fait d'operation en base de donnee
					if (!jQuery('.reponse_formulaire_ok',cible).length)
						jQuery.spip.preloaded_urls = preloaded;
					// mettre a jour le buffer du navigateur pour aider jaws et autres readers
					// a supprimer ?
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

jQuery.fn.formulaire_verifier = function(callback, champ){
	var erreurs = {'message_erreur':'form non ajax'};
	var me=this;
	// si on est aussi en train de submit pour de vrai, abandonner
	if (jQuery(me).closest('.ariaformprop').attr('aria-busy')!='true') {
		if (jQuery(me).is('form.hasajax')){
			jQuery(me).ajaxSubmit({
				dataType:"json",
				data:{formulaire_action_verifier_json:true},
				success:function(errs){
					var args = [errs, champ]
					// si on est aussi en train de submit pour de vrai, abandonner
					if (jQuery(me).closest('.ariaformprop').attr('aria-busy')!='true')
						callback.apply(me,args);
				}
			});
		}
		else
			callback.apply(me,[erreurs, champ]);
	}
	return this;
}

jQuery.fn.formulaire_activer_verif_auto = function(callback){
	callback = callback || formulaire_actualiser_erreurs;
	var me = jQuery(this).closest('.ariaformprop');
	var check = function(){
		var name=jQuery(this).attr('name');
		// declencher apres 50ms pour ne pas double submit sur sequence saisie+submit
		setTimeout(function(){me.find('form').formulaire_verifier(callback,name);},50);
	}
	var activer = function(){
		if (me.find('form').attr('data-verifjson')!='on'){
			me
				.find('form')
				.attr('data-verifjson','on')
				.find('input,select,textarea')
				.bind('change',check);
		}
	}
	jQuery(activer);
	onAjaxLoad(function(){setTimeout(activer,150);});
}

function formulaire_actualiser_erreurs(erreurs){
	var parent = jQuery(this).closest('.formulaire_spip');
	if (!parent.length) return;
	// d'abord effacer tous les messages d'erreurs
	parent.find('.reponse_formulaire,.erreur_message').fadeOut().remove();
	parent.find('.erreur').removeClass('erreur');
	// ensuite afficher les nouveaux messages d'erreur
	if (erreurs['message_ok'])
		parent.find('form').before('<p class="reponse_formulaire reponse_formulaire_ok">'+erreurs['message_ok']+'</p>');
	if (erreurs['message_erreur'])
		parent.find('form').before('<p class="reponse_formulaire reponse_formulaire_erreur">'+erreurs['message_erreur']+'</p>');
	for (var k in erreurs){
		var saisie = parent.find('.editer_'+k);
		if (saisie.length) {
			saisie.addClass('erreur');
			saisie.find('label').after('<span class="erreur_message">'+erreurs[k]+'</span>');
		}
	}
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

/**
 * rechargement ajax d'une noisette implementee par {ajax}
 * selecteur personalise, sera defini par defaut a '.pagination a,a.ajax'
 */
var ajaxbloc_selecteur;

/**
 * mise en cache des url. Il suffit de vider cete variable pour vider le cache
 */
jQuery.spip.preloaded_urls = {};

/**
 * Afficher dans la page
 * le html d'un bloc ajax charge
 * @param object blocfrag
 * @param string c
 * @param string href
 * @param bool history
 */
jQuery.spip.on_ajax_loaded = function(blocfrag,c,href,history) {
	history = history || (history==null);
	if (typeof href == undefined || href==null)
		history = false;
	if (history)
		jQuery.spip.setHistoryState(blocfrag);

	if (jQuery(blocfrag).attr('data-loaded-callback')){
		var callback = eval(jQuery(blocfrag).attr('data-loaded-callback'));
		callback.call(blocfrag, c, href, history);
	}
	else {
		jQuery(blocfrag)
		.html(c)
		.endLoading();
	}

	if (typeof href != undefined)
		jQuery(blocfrag).attr('data-url',href);
	if (history) {
		jQuery.spip.pushHistoryState(href);
		jQuery.spip.setHistoryState(blocfrag);
	}

	var a = jQuery('a:first',jQuery(blocfrag)).eq(0);
	if (a.length
		&& a.is('a[name=ajax_ancre]')
		&& jQuery(a.attr('href'),blocfrag).length){
		a = a.attr('href');
		jQuery(a,blocfrag).positionner(false);
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

jQuery.spip.stateId=0;
jQuery.spip.setHistoryState = function(blocfrag){
	if (!window.history.replaceState) return;
	// attribuer un id au bloc si il n'en a pas
	if (!blocfrag.attr('id')){
		while (jQuery('#ghsid'+jQuery.spip.stateId).length)
			jQuery.spip.stateId++;
		blocfrag.attr('id','ghsid'+jQuery.spip.stateId);
	}
	var href= blocfrag.attr('data-url') || blocfrag.attr('data-origin');
	href = jQuery("<"+"a href='"+href+"'></a>").get(0).href;
	var state={
		id:blocfrag.attr('id'),
		href: href
	};
	var ajaxid = blocfrag.attr('class').match(/\bajax-id-[\w-]+\b/);
	if (ajaxid && ajaxid.length)
		state["ajaxid"] = ajaxid[0];
	// on remplace la variable qui decrit l'etat courant
	// initialement vide
	// -> elle servira a revenir dans l'etat courant
	window.history.replaceState(state,window.document.title, window.document.location);
}

jQuery.spip.pushHistoryState = function(href, title){
	if (!window.history.pushState)
		return false;
	window.history.pushState({}, title, href);
}

window.onpopstate = function(popState){
	if (popState.state && popState.state.href){
		var blocfrag=false;
		if (popState.state.id){
			blocfrag=jQuery('#'+popState.state.id);
		}
		if ((!blocfrag || !blocfrag.length) && popState.state.ajaxid){
			blocfrag=jQuery('.ajaxbloc.'+popState.state.ajaxid);
		}
		if (blocfrag && blocfrag.length==1) {
			jQuery.spip.ajaxClick(blocfrag,popState.state.href,{history:false});
			return true;
		}
		// si on revient apres avoir rompu la chaine ajax, on a pu perdre l'id #ghsidxx ajoute en JS
		// dans ce cas on redirige hors ajax
		else {
			window.location.href = popState.state.href;
		}
	}
}

/**
 * Charger un bloc ajax represente par l'objet jQuery blocajax qui le pointe
 * avec la requete ajax url, qui represente le lien href
 * @param object blocfrag
 *   bloc cible
 * @param string url
 *   url pour la requete ajax
 * @param string href
 *   url du lien clique
 * @param object options
 *   bool force : pour forcer la requete sans utiliser le cache
 *   function callback : callback au retour du chargement
 *   bool history : prendre en charge l'histrisation dans l'url
 */
jQuery.spip.loadAjax = function(blocfrag,url, href, options){
	var force = options.force || false;
	if (jQuery(blocfrag).attr('data-loading-callback')){
		var callback = eval(jQuery(blocfrag).attr('data-loading-callback'));
		callback.call(blocfrag,url,href,options);
	}
	else {
		jQuery(blocfrag).animateLoading();
	}
	if (jQuery.spip.preloaded_urls[url] && !force) {
		// si on est deja en train de charger ce fragment, revenir plus tard
		if (jQuery.spip.preloaded_urls[url]=="<!--loading-->"){
			setTimeout(function(){jQuery.spip.loadAjax(blocfrag,url,href,options);},100);
			return;
		}
		jQuery.spip.on_ajax_loaded(blocfrag,jQuery.spip.preloaded_urls[url],href,options.history);
	} else {
		var d = new Date();
		jQuery.spip.preloaded_urls[url] = "<!--loading-->";
		jQuery.ajax({
			url: parametre_url(url,'var_t',d.getTime()),
			onAjaxLoad:false,
			success: function(c){
				jQuery.spip.on_ajax_loaded(blocfrag,c,href,options.history);
				jQuery.spip.preloaded_urls[url] = c;
				if (options.callback && typeof options.callback == "function")
					options.callback.apply(blocfrag);
			},
			error: function(){
				jQuery.spip.preloaded_urls[url]='';
			}
		});
	}
}

/**
 * Calculer l'url ajax a partir de l'url du lien
 * et de la variable d'environnement du bloc ajax
 * passe aussi l'ancre eventuelle sous forme d'une variable
 * pour que le serveur puisse la prendre en compte
 * et la propager jusqu'a la reponse
 * sous la forme d'un lien cache
 *
 * @param string href
 * @param string ajax_env
 */
jQuery.spip.makeAjaxUrl = function(href,ajax_env,origin){
	var url = href.split('#');
	url[0] = parametre_url(url[0],'var_ajax',1);
	url[0] = parametre_url(url[0],'var_ajax_env',ajax_env);

	// les arguments de origin qui ne sont pas dans href doivent etre explicitement fournis vides dans url
	if (origin){
		var p=origin.indexOf('?');
		if (p!==-1){
			// recuperer la base
			var args = origin.substring(p+1).split('&');
			var val;
			var arg;
			for(var n=0;n<args.length;n++){
				arg = args[n].split('=');
				arg = arg[0];
				p = arg.indexOf('[');
				if (p!==-1)
					arg = arg.substring(0,p);
				val = parametre_url(href,arg);
				if (typeof val=="undefined")
					url[0] = url[0] + '&' + arg + '=';
			}
		}
	}

	if (url[1])
		url[0] = parametre_url(url[0],'var_ajax_ancre',url[1]);
	return url[0];
}

/**
 * fonction appelee sur l'evenement ajaxReload d'un bloc ajax
 * que l'on declenche quand on veut forcer sa mise a jour
 *
 * @param object blocfrag
 * @param object options
 *   callback : fonction appelee apres le rechargement
 *   href : url to load instead of origin url
 *   args : arguments passes a l'url rechargee (permet une modif du contexte)
 *   history : bool to specify if navigation history is modified by reload or not (false if not provided)
 */
jQuery.spip.ajaxReload = function(blocfrag, options){
	var ajax_env = blocfrag.attr('data-ajax-env');
	if (!ajax_env || ajax_env==undefined) return;
	var href = options.href || blocfrag.attr('data-url') || blocfrag.attr('data-origin');
	if (href && typeof href != undefined){
		options == options || {};
		var callback=options.callback || null;
		var history=options.history || false;
		var args = options.args || {};
		for (var key in args)
			href = parametre_url(href,key,args[key]==undefined?'':args[key],'&',args[key]==undefined?false:true);
		var url = jQuery.spip.makeAjaxUrl(href,ajax_env,blocfrag.attr('data-origin'));
		// recharger sans historisation dans l'url
		jQuery.spip.loadAjax(blocfrag, url, href, {force:true, callback:callback, history:history});
		return true;
	}
}

/**
 * fonction appelee sur l'evenement click d'un lien ajax
 *
 * @param object blocfrag
 *   objet jQuery qui cible le bloc ajax contenant
 * @param string href
 *   url du lien a suivre
 * @param object options
 *   force : pour interdire l'utilisation du cache
 *   history : pour interdire la mise en historique
 */
jQuery.spip.ajaxClick = function(blocfrag, href, options){
	var ajax_env = blocfrag.attr('data-ajax-env');
	if (!ajax_env || ajax_env==undefined) return;
	if (!ajax_confirm) {
		// on rearme pour le prochain clic
		ajax_confirm=true;
		var d = new Date();
		// seule une annulation par confirm() dans les 2 secondes precedentes est prise en compte
		if ((d.getTime()-ajax_confirm_date)<=2)
			return false;
	}
	var url = jQuery.spip.makeAjaxUrl(href,ajax_env,blocfrag.attr('data-origin'));
	jQuery.spip.loadAjax(blocfrag, url, href, options);
	return false;
}

/**
 * Implementer le comportemant des liens ajax
 * et boutons post ajax qui se comportent
 * comme un lien ajax
 */
jQuery.fn.ajaxbloc = function() {
	// hack accessibilite vieille version de JAWS
	// a supprimer ?
	if (this.length)
		jQuery.spip.initReaderBuffer();
	if (ajaxbloc_selecteur==undefined)
		ajaxbloc_selecteur = '.pagination a,a.ajax';

  return this.each(function() {
	  // traiter les enfants d'abord :
	  // un lien ajax provoque le rechargement
	  // du plus petit bloc ajax le contenant
	  jQuery('div.ajaxbloc',this).ajaxbloc();
		var blocfrag = jQuery(this);

		var ajax_env = blocfrag.attr('data-ajax-env');
		if (!ajax_env || ajax_env==undefined) return;

	  blocfrag.not('.bind-ajaxReload').bind('ajaxReload',function(event, options){
		  if (jQuery.spip.ajaxReload(blocfrag,options))
				// don't trig reload of parent blocks
				event.stopPropagation();
	  }).addClass('bind-ajaxReload');

		jQuery(ajaxbloc_selecteur,this).not('.noajax,.bind-ajax')
			.click(function(){return jQuery.spip.ajaxClick(blocfrag,this.href,{force:jQuery(this).is('.nocache'),history:!(jQuery(this).is('.nohistory')||jQuery(this).closest('.box_modalbox').length)});})
			.addClass('bind-ajax')
			.filter('.preload').each(function(){
				var href = this.href;
				var url = jQuery.spip.makeAjaxUrl(href,ajax_env,blocfrag.attr('data-origin'));
				if (!jQuery.spip.preloaded_urls[url]) {
					jQuery.spip.preloaded_urls[url] = '<!--loading-->';
					jQuery.ajax({url:url,onAjaxLoad:false,success:function(r){jQuery.spip.preloaded_urls[url]=r;},error:function(){jQuery.spip.preloaded_urls[url]='';}});
				}
			}); // previent qu'on ajax pas deux fois le meme lien

		// ajaxer les boutons actions qui sont techniquement des form minimaux
		// mais se comportent comme des liens
		jQuery('form.bouton_action_post.ajax', this).not('.noajax,.bind-ajax').each(function(){
			var leform = this;
			var url = jQuery(this).attr('action').split('#');
			jQuery(this)
			.prepend("<input type='hidden' name='var_ajax' value='1' /><input type='hidden' name='var_ajax_env' value='"+(ajax_env)+"' />"+(url[1]?"<input type='hidden' name='var_ajax_ancre' value='"+url[1]+"' />":""))
			.ajaxForm({
				beforeSubmit: function(){
					jQuery(blocfrag).animateLoading().positionner(false);
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

/**
 * Suivre un lien en simulant le click sur le lien
 * Si le lien est ajax, on se contente de declencher l'evenement click()
 * Si le lien est non ajax, on finit en remplacant l'url de la page
 */
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
 *   callback : callback after reloading
 *   href : url to load instead of origin url
 *   args : {arg:value,...} to pass tu the url
 *   history : bool to specify if navigation history is modified by reload or not (false if not provided)
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
	this.attr('aria-busy','true').addClass('loading').children().css('opacity', 0.5);
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
 * Fin de l'animation
 * l'argument permet de forcer le raz du contenu si il est inchange
 * @param hard
 */
jQuery.fn.endLoading = function(hard) {
	hard = hard || false;
	this.attr('aria-busy','false').removeClass('loading');
	if (hard){
		this.children().css('opacity', '');
		this.find('.image_loading').html('');
	}
	return this; // don't break the chain
}

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
		var me=this;
		var color = $("<div class='remove'></div>").css('background-color');
		var sel=$(this);
		// if target is a tr, include td childrens cause background color on tr doesn't works in a lot of browsers
		if (sel.is('tr'))
			sel = sel.add('>td',sel);
		sel.addClass('remove').css({backgroundColor: color}).animate({opacity: "0.0"}, 'fast',function(){
			sel.removeClass('remove').css({backgroundColor: ''});
			if (callback)
				callback.apply(me);
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
			sel = sel.add('>td',sel);
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
 * parametre_url(url,suite[],1) (tableau valeurs multiples)
 * @param url
 *   url
 * @param c
 *   champ
 * @param v
 *   valeur
 * @param sep
 *  separateur '&' par defaut
 * @param force_vide
 *  si true et v='' insere &k= dans l'url au lieu de supprimer le k (false par defaut)
 *  permet de vider une valeur dans une requete ajax (dans un reload)
 */
function parametre_url(url,c,v,sep,force_vide){
	// Si l'URL n'est pas une chaine, on ne peut pas travailler dessus et on quitte
	if (typeof(url) == 'undefined'){
		url = '';
	}
	
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
	var regexp = new RegExp('^(' + c.replace('[]','\\[\\]') + '\\[?\\]?)(=.*)?$');
	var ajouts = [];
	var u = (typeof(v)!=='object')?encodeURIComponent(v):v;
	var na = [];
	// lire les variables et agir
	for(var n=0;n<args.length;n++){
		var val = args[n];
		try {
			val = decodeURIComponent(val);
		} catch(e) {}
		var r=val.match(regexp);
		if (r && r.length){
			if (v==null){
				return (r.length>2 && typeof r[2]!=='undefined')?r[2].substring(1):'';
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
	if (v || v.length || force_vide) {
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
if (!window.var_zajax_content)
	window.var_zajax_content = 'contenu';
jQuery(function() {
	jQuery('form:not(.bouton_action_post)').parents('div.ajax')
	.formulaire_dyn_ajax();
	jQuery('div.ajaxbloc').ajaxbloc();
	jQuery("input[placeholder]:text").placeholderLabel();
	jQuery('a.popin').click(function(){if (jQuery.modalbox) jQuery.modalbox(parametre_url(this.href,"var_zajax",jQuery(this).attr('data-var_zajax')?jQuery(this).attr('data-var_zajax'):var_zajax_content));return false;});
});

// ... et a chaque fois que le DOM change
onAjaxLoad(function() {
	if (jQuery){
		jQuery('form:not(.bouton_action_post)', this).parents('div.ajax')
			.formulaire_dyn_ajax();
		if (jQuery(this).is('div.ajaxbloc'))
			jQuery(this).ajaxbloc();
		else if (jQuery(this).closest('div.ajaxbloc').length)
			jQuery(this).closest('div.ajaxbloc').ajaxbloc();
		else
			jQuery('div.ajaxbloc', this).ajaxbloc();
		jQuery("input[placeholder]:text",this).placeholderLabel();
		jQuery('a.popin',this).click(function(){if (jQuery.modalbox) jQuery.modalbox(parametre_url(this.href,"var_zajax",jQuery(this).attr('data-var_zajax')?jQuery(this).attr('data-var_zajax'):var_zajax_content));return false;});
	}
});

