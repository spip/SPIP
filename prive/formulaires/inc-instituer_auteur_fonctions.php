<?php

/**
 * Fonction pour le squelette du même nom
 *
 * @package SPIP\Core\Formulaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Afficher le formulaire de choix de rubrique restreinte
 * pour insertion dans le formulaire
 *
 * @param int $id_auteur
 * @param string $label
 * @param string $sel_css
 *     Sélecteur CSS déterminant le conteneur de l'input reçevant les rubriques sélectionnées
 * @param string $img_remove
 *     Balise `<img...>` pour enlever des rubriques
 * @return string
 *     Code HTML et javascript
 */
function choisir_rubriques_admin_restreint(
	$id_auteur,
	$label = '',
	$sel_css = '#liste_rubriques_restreintes',
	$img_remove = ''
) {
	global $spip_lang;
	$res = '';
	// Ajouter une rubrique a un administrateur restreint
	if (
		$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc')
		and $a = $chercher_rubrique(0, 'auteur', false)
	) {
		if ($img_remove) {
			$img_remove = addslashes("<a href=\"#\" onclick=\"jQuery(this).parent().remove();return false;\" class=\"removelink\">$img_remove</a>");
		}

		$res =
			"\n<div id='ajax_rubrique'>\n"
			. "<label>$label</label>\n"
			. "<input name='id_auteur' value='$id_auteur' type='hidden' />\n"
			. $a
			. "</div>\n"

			// onchange = pour le menu
			// l'evenement doit etre provoque a la main par le selecteur ajax
			. "<script type='text/javascript'>/*<![CDATA[*/
jQuery(function($){
	$('#id_parent').on('change', function(){
		var id_parent = parseInt(this.value);
		if (id_parent){
			var titre = $('#titreparent').val() || this.options[this.selectedIndex].text;
			titre=titre.replace(/^\\s+/,'');
			// Ajouter la rubrique selectionnee au formulaire,
			// sous la forme d'un input name='rubriques[]'
			var el = '<input type=\'checkbox\' class=\'checkbox\' checked=\'checked\' name=\'restreintes[]\' value=\''+id_parent+'\' /> ' + '<label><a href=\'?exec=rubrique&amp;id_rubrique='+id_parent+'\' target=\'_blank\'>'+titre+'</a></label>';
			el = el + '$img_remove';
			if (!$('$sel_css input[value='+id_parent+']').length) {
				$('$sel_css').append('<li class=\"rubrique\">'+el+'</li>');
			}
		}
	})
	.attr('name','noname');
});
/*]]>*/</script>";
	}

	return $res;
}
