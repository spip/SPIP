<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Afficher le formulaire de choix de rubrique restreinte
 * pour insertion dans le formulaire
 *
 * @param int $id_auteur
 * @param string $label
 * @return string
 */
function choisir_rubriques_admin_restreint($id_auteur,$label='', $sel_css="#liste_rubriques_restreintes", $img_remove="") {
	global $spip_lang;
	$res = "";
	// Ajouter une rubrique a un administrateur restreint
	if ($chercher_rubrique = charger_fonction('chercher_rubrique', 'inc')
	  AND $a = $chercher_rubrique(0, 'auteur', false)) {

		if ($img_remove)
			$img_remove = addslashes("<a href=\"#\" onclick=\"jQuery(this).parent().remove();return false;\" class=\"removelink\">$img_remove</a>");

		$res =
		  "\n<div id='ajax_rubrique'>\n"
		. "<label>$label</label>\n"
		. "<input name='id_auteur' value='$id_auteur' type='hidden' />\n"
		. $a
		. "</div>\n"

		// onchange = pour le menu
		// l'evenement doit etre provoque a la main par le selecteur ajax
		. "<script type='text/javascript'>/*<![CDATA[*/
jQuery(function(){
	jQuery('#id_parent')
	.bind('change', function(){
		var id_parent = parseInt(this.value);
		if (id_parent){
			var titre = jQuery('#titreparent').attr('value') || this.options[this.selectedIndex].text;
			titre=titre.replace(/^\\s+/,'');
			// Ajouter la rubrique selectionnee au formulaire,
			// sous la forme d'un input name='rubriques[]'
			var el = '<input type=\'checkbox\' class=\'checkbox\' checked=\'checked\' name=\'restreintes[]\' value=\''+id_parent+'\' /> ' + '<label><a href=\'?exec=rubrique&amp;id_rubrique='+id_parent+'\' target=\'_blank\'>'+titre+'</a></label>';
			el = el + '$img_remove';
			if (!jQuery('$sel_css input[value='+id_parent+']').length) {
				jQuery('$sel_css').append('<li class=\"rubrique\">'+el+'</li>');
			}
		}
	})
	.attr('name','noname');
});
/*]]>*/</script>";

	}

	return $res;
}

?>