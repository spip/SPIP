<?php

/**
 * Afficher le formulaire de choix de rubrique restreinte
 * pour insertion dans le formulaire
 *
 * @param int $id_auteur
 * @param string $label
 * @return string
 */
function choisir_rubriques_admin_restreint($id_auteur,$label='') {
	global $spip_lang;
	$res = "";
	// Ajouter une rubrique a un administrateur restreint
	if ($chercher_rubrique = charger_fonction('chercher_rubrique', 'inc')
	  AND $a = $chercher_rubrique(0, 'auteur', false)) {

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
		var id_parent = this.value;
		var titre = jQuery('#titreparent').attr('value') || this.options[this.selectedIndex].text;
		titre=titre.replace(/^\\s+/,'');
		// Ajouter la rubrique selectionnee au formulaire,
		// sous la forme d'un input name='rubriques[]'
		var el = '<input type=\'checkbox\' checked=\'checked\' name=\'restreintes[]\' value=\''+id_parent+'\' /> ' + '<label><a href=\'?exec=naviguer&amp;id_rubrique='+id_parent+'\' target=\'_blank\'>'+titre+'</a></label>';
		if (!jQuery('#liste_rubriques_restreintes input[value='+id_parent+']').length) {
			jQuery('#liste_rubriques_restreintes').append('<li>'+el+'</li>');
		}
	});
});
/*]]>*/</script>";

	}

	return $res;
}

?>