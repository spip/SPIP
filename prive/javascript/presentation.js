
// Un petit plugin jQuery pour ajouter une classe au survol d'un element
$.fn.hoverClass = function(c) {
	return this.each(function(){
		$(this).hover(
			function() { $(this).addClass(c); },
			function() { $(this).removeClass(c); }
		);
	});
};


var accepter_change_statut = false;
/**
 * Utilisee dans inc/puce_statut pour les puces au survol
 * @param int id
 * @param strong type
 * @param int decal
 * @param string puce
 * @param string script
 */
function selec_statut(id, type, decal, puce, script) {

	node = $('.imgstatut'+type+id);

	if (!accepter_change_statut)
		accepter_change_statut = confirm(confirm_changer_statut);

	if (!accepter_change_statut || !node.length) return;

	$('.statutdecal'+type+id)
	.css('margin-left', decal+'px')
	.removeClass('on');

	$.get(script, function(c) {
		if (!c)
			node.attr('src',puce);
		else {
			r = window.open();
			r.document.write(c);
			r.document.close();
		}
	});
}

/**
 * Utilisee dans inc/puce_statut pour les puces au survol
 * @param objet node
 * @param string nom
 * @param string type
 * @param int id
 * @param string action
 */
function prepare_selec_statut(node, nom, type, id, action)
{
	$(node)
	.hoverClass('on')
	.addClass('on')
	.load(action + '&type='+type+'&id='+id);
}


// deplier un ou plusieurs blocs
jQuery.fn.showother = function(cible) {
	var me = this;
	if (me.is('.replie')) {
		me.addClass('deplie').removeClass('replie');
		jQuery(cible)
		.slideDown('fast',
			function(){
				jQuery(me)
				.addClass('blocdeplie')
				.removeClass('blocreplie')
				.removeClass('togglewait');
			}
		).trigger('deplie');
	}
	return this;
}

// replier un ou plusieurs blocs
jQuery.fn.hideother = function(cible) {
	var me = this;
	if (!me.is('.replie')){
		me.addClass('replie').removeClass('deplie');
		jQuery(cible)
		.slideUp('fast',
			function(){
				jQuery(me)
				.addClass('blocreplie')
				.removeClass('blocdeplie')
				.removeClass('togglewait');
			}
		).trigger('replie');
}
	return this;
}

// pour le bouton qui deplie/replie un ou plusieurs blocs
jQuery.fn.toggleother = function(cible) {
	if (this.is('.deplie'))
		return this.hideother(cible);
	else
		return this.showother(cible);
}

// deplier/replier en hover
// on le fait subtilement : on attend 400ms avant de deplier, periode
// durant laquelle, si la souris  sort du controle, on annule le depliement
// le repliement ne fonctionne qu'au clic
// Cette fonction est appelee a chaque hover d'un bloc depliable
// la premiere fois, elle initialise le fonctionnement du bloc ; ensuite
// elle ne fait plus rien
jQuery.fn.depliant = function(cible) {
	// premier passage
	if (!this.is('.depliant')) {
		var time = 400;

		var me = this;
		this
		.addClass('depliant');

		// effectuer le premier hover
		if (!me.is('.deplie')) {
			me.addClass('hover')
			.addClass('togglewait');
			var t = setTimeout(function(){
				me.toggleother(cible);
				t = null;
			}, time);
		}

		me
		// programmer les futurs hover
		.hover(function(e){
			me
			.addClass('hover');
			if (!me.is('.deplie')) {
				me.addClass('togglewait');
				if (t) { clearTimeout(t); t = null; }
				t = setTimeout(function(){
					me.toggleother(cible);
					t = null;
					}, time);
			}
		}
		, function(e){
			if (t) { clearTimeout(t); t = null; }
			me
			.removeClass('hover');
		})

		// gerer le triangle clicable
		/*.find("a.titremancre")
			.click(function(){
				if (me.is('.togglewait') || t) return false;
				me
				.toggleother(cible);
				return false;
			})*/
		.end();

	}
	return this;
}
jQuery.fn.depliant_clicancre = function(cible) {
		var me = this.parent();
		// gerer le triangle clicable
		if (me.is('.togglewait')) return false;
		me.toggleother(cible);
		return false;
}

/**
 * Recharger les blocs d'une page exec
 * et changer la class du body si necessaire
 * Par defaut les blocs recharges sont #navigation,#extra
 * mais il suffit de passer des valeurs differentes en second argument
 * 
 * @param exec
 * @param blocs
 */
function reloadExecPage(exec, blocs){
	if (window.jQuery) {
		jQuery(function(){
			if (!blocs)
				blocs="#navigation,#extra";
			jQuery(blocs).find('>div').ajaxReload({args:{exec:exec}});
			if (exec.match(/_edit$/))
				jQuery('body').addClass('edition');
			else
				jQuery('body').removeClass('edition');
		})
	}
}