function init_gadgets(url_toutsite,url_navrapide,url_agenda,html_messagerie){
	jQuery('#boutonbandeautoutsite').one('mouseover',function(){
		jQuery(this).siblings('ul').animeajax();
		jQuery.ajax({
						url: url_toutsite,
						success: function(c){
							jQuery('#boutonbandeautoutsite').siblings('ul').remove();
							jQuery('#boutonbandeautoutsite').after(c);
						}
					});
	});
}
function focus_zone(selecteur){
	jQuery(selecteur).eq(0).find('a,input:visible').get(0).focus();
	return false;
}
jQuery(document).ready(function(){
	// deplier le menu au focus clavier,
	// enlever ce depliement si passage a la souris,
	// delai de fermeture.
	jQuery.fn.menuFocus = function(){
		jQuery(this)
		// le replier si un hover de souris sur un autre onglet,
		// timer sur la fermeture des onglets pour ne pas que ca aille trop vite
		.hover(
			function(){
				if (this.timer)
						clearTimeout(this.timer);
				this.timer = null;
				/*jQuery(this).parents('ul').find('>li.actif').removeClass('actif');*/
				jQuery(this)
					.addClass('actif')
					.parents('li').addClass('actif');
				jQuery(this).siblings('li').removeClass('actif_tempo');
			}
			,
			function(){
				var me = jQuery(this).removeClass('actif').addClass('actif_tempo');
				this.timer = setTimeout(function(){
					me.removeClass('actif_tempo');
				}, 400);
			}
		)
		// navigation au clavier :
		// deplier le ul enfant
		.find('>a').focus(function(){
			//jQuery(this).parents('ul').find('>li.actif').removeClass('actif');
			jQuery(this).parents('li').addClass('actif');
		})
		// cacher en partant de l'onglet...
		.blur(function(){
			jQuery(this).parents('li').removeClass('actif');
		});
		return this;
	}
	
	// Controler la position verticale des sous-menus
	// pour l'instant, effectuer a chaque hover, en cas de changement de taille d'affichage par exemple
	jQuery('#bando_navigation').hover(function(){
		hauteur = parseInt(jQuery('#bando_navigation .largeur').height())
			+  parseInt(jQuery('#bando_navigation').css("padding-top"))
			+  parseInt(jQuery('#bando_navigation').css("padding-bottom"));
		jQuery('#bando_navigation ul li>ul').css({'top':hauteur});
	});
	
	jQuery('#bando_navigation li').menuFocus();
	jQuery('#bando_outils ul.bandeau_rubriques li').menuFocus();

	jQuery('#bandeau_haut #formRecherche input').hover(function(){
		jQuery('#bandeau_haut ul.actif').trigger('mouseout');
	});
	jQuery('#bando_liens_rapides a')
		.focus(function(){
			jQuery('#bando_liens_rapides').addClass('actif');
		})
		.blur(function(){
			jQuery('#bando_liens_rapides').removeClass('actif');
		});
});
