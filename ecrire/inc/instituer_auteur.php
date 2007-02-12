<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');
include_spip('inc/texte');
include_spip('inc/layer');
include_spip('inc/presentation');
include_spip('inc/message_select');

//  affiche le statut de l'auteur dans l'espace prive
// les admins voient et peuvent modifier les droits d'un auteur
// les admins restreints les voient mais 
// ne peuvent les utiliser que pour mettre un auteur a la poubelle

function inc_instituer_auteur_dist($auteur) {
	global $connect_toutes_rubriques, $connect_id_auteur, $connect_statut, $spip_lang_right, $spip_lang;

	if ($connect_statut != '0minirezo') return '';

	$statut = $auteur['statut'];

	if (!$id_auteur = $auteur['id_auteur']) {
		$new = true;
		$statut = '1comite';
	}

	$ancre = "instituer_auteur-" . intval($id_auteur);

	if ($menu = choix_statut_auteur($statut, $id_auteur, "$ancre-aff"))
		$res = "<b>"._T('info_statut_auteur')."</b> " . $menu;

	// Prepare le bloc des rubriques restreintes ;
	// si l'auteur n'est pas admin, on le cache
	$vis = ($statut == '0minirezo') ? '' : " style='display: none'";
	if ($menu_restreints = choix_rubriques_admin_restreint($auteur))
		$res .= "<div id='$ancre-aff'$vis>"
			. $menu_restreints
			. "</div>";

	return debut_cadre_relief('',true)
		. "<div id='"
		. $ancre
		. "'>"
		. $res 
		. '</div>'
		. fin_cadre_relief(true);
}


// Menu de choix d'un statut d'auteur
// http://doc.spip.org/@choix_statut_auteur
function choix_statut_auteur($statut, $id_auteur, $ancre) {

	// Le menu doit-il etre actif ?
	if (!autoriser('modifier', 'auteur', $id_auteur,
	null, array('statut' => '?')))
		return '';

	// Calculer le menu
	$menu = "<select name='statut' size='1' class='fondl'
		onchange=\"(this.options[this.selectedIndex].value == '0minirezo')?jQuery('#$ancre').slideDown():jQuery('#$ancre:visible').slideUp();\">";

	// A-t-on le droit de promouvoir cet auteur comme admin ?
	if (autoriser('modifier', 'auteur', intval($id_auteur),
	null, array('statut' => '0minirezo'))) {
		$menu .= "\n<option" .
			mySel("0minirezo",$statut) .
			">" . _T('item_administrateur_2')
			. '</option>';
	}

	// Ajouter le choix "comite"
	$menu .=
		"\n<option" .
		mySel("1comite",$statut) .
		">" .
		_T('intem_redacteur') .
		'</option>';

	// Ajouter le choix "visiteur" si :
	// - l'auteur est visiteur
	// - OU, on accepte les visiteurs (ou forums sur abonnement)
	// - OU il y a des visiteurs dans la base
	$x = (($statut == '6forum')
	      OR ($GLOBALS['meta']['accepter_visiteurs'] == 'oui')
	      OR ($GLOBALS['meta']['forums_publics'] == 'abo'));
	if (!$x) {
	  $x = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_auteurs WHERE statut='6forum' LIMIT 1"));
	  $x = $x['n'];
	}
	if ($x)
		$menu .= "\n<option" .
			mySel("6forum",$statut) .
			">" .
			_T('item_visiteur') .
			'</option>';

	// Ajouter l'option "nouveau" si l'auteur n'est pas confirme
	if ($statut == 'nouveau')
		$menu .= "\n<option" .
			mySel('nouveau',$statut) .
			">" .
			_T('info_statut_auteur_a_confirmer') .
			'</option>';

	// Ajouter l'option "autre" si le statut est inconnu
	if (!in_array($statut, array('nouveau', '0minirezo', '1comite', '5poubelle', '6forum')))
		$menu .= "\n<option" .
			mySel('autre','autre') .
			">" .
			_T('info_statut_auteur_autre').' '.htmlentities($statut).
			'</option>';

	$menu .= "\n<option" .
		mySel("5poubelle",$statut) .
		" style='background:url(" . _DIR_IMG_PACK . "rayures-sup.gif)'>&gt; "
		._T('texte_statut_poubelle') .
		'</option>' .
		"</select>\n";

	return $menu;
}


function choix_rubriques_admin_restreint($auteur) {
	global $connect_toutes_rubriques, $connect_id_auteur, $connect_statut, $spip_lang_right, $spip_lang;

	$id_auteur = intval($auteur['id_auteur']);

	$result_admin = spip_query("SELECT rubriques.id_rubrique, " . creer_objet_multi ("titre", $spip_lang) . " FROM spip_auteurs_rubriques AS lien, spip_rubriques AS rubriques WHERE lien.id_auteur=$id_auteur AND lien.id_rubrique=rubriques.id_rubrique ORDER BY multi");


	if (spip_num_rows($result_admin) == 0) {
		$phrase = _T('info_admin_gere_toutes_rubriques')."\n";
		$menu = '';
	} else {
		// L'autorisation de modifier les rubriques restreintes
		// est egale a l'autorisation de passer en admin
		$modif = autoriser('modifier', 'auteur', $id_auteur, null, array('statut' => '0minirezo'));

		// Il faut un element zero pour montrer qu'on a l'interface
		// sinon il est impossible de deslectionner toutes les rubriques
		$menu = $modif
			? "<input type='hidden' name='restreintes[]' value='0' />\n"
			: '';

		while ($row_admin = spip_fetch_array($result_admin)) {
			$id_rubrique = $row_admin["id_rubrique"];

			$menu .= "\n<li id='rubrest_$id_rubrique'>"
			. ($modif
				? "<input type='checkbox' checked='checked' name='restreintes[]' value='$id_rubrique' />\n"
				: ''
			)
			. "<a href='?exec=naviguer&amp;id_rubrique=$id_rubrique'>"
			. typo($row_admin["multi"])
			. "</a>"
			. '</li>';
		}

		$phrase = _T('info_admin_gere_rubriques');
	}

	if ($auteur['statut'] != '0minirezo')
		$phrase = '';

	$res = "<p>$phrase</p>\n"
		. "<ul id='liste_rubriques_restreintes' style='list-style-image: url("
		. _DIR_IMG_PACK
		. "rubrique-12.gif)'>"
		. $menu
		. "</ul>\n";

	// Ajouter une rubrique a un administrateur restreint
	if ($connect_toutes_rubriques AND $connect_id_auteur != $id_auteur) {

		$label = $restreint
			? _T('info_ajouter_rubrique')
			: _T('info_restreindre_rubrique');

		$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');

		$res .= debut_block_visible("statut$id_auteur")
		. "\n<div id='ajax_rubrique' class='arial1'><br />\n"
		. "<b>"
		. $label 
		. "</b><br />"
		. "\n<input name='id_auteur' value='"
		. $id_auteur
		. "' type='hidden' />"
		. $chercher_rubrique(0, 'auteur', false)
		. "</div>\n"

		// onchange = pour le menu
		// l'evenement doit etre provoque a la main par le selecteur ajax
		. "<script type='text/javascript'><!--
		jQuery('#id_parent')
		.bind('change', function(){
			var id_parent = this.value;
			var titre = jQuery('#titreparent').attr('value') || this.options[this.selectedIndex].text;
			// Ajouter la rubrique selectionnee au formulaire,
			// sous la forme d'un input name='rubriques[]'
			var el = '<input type=\'checkbox\' checked=\'checked\' name=\'restreintes[]\' value=\''+id_parent+'\' /> ' + '<a href=\'?exec=naviguer&amp;id_rubrique='+id_parent+'\'>'+titre+'</a>';
			if (jQuery('#rubrest_'+id_parent).size() == 0) {
				jQuery('#liste_rubriques_restreintes')
				.append('<li id=\'rubrest_'+id_parent+'\'>'+el+'</li>');
			}
		}); //--></script>\n"

		. fin_block();
	}

	return $res;
}


?>
