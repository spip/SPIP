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
include_spip('inc/autoriser');


// Deux constantes surchargeables, cf. plugin autorite :
	// statut par defaut a la creation
	define('_STATUT_AUTEUR_CREATION', '1comite');
	// statuts associables a des rubriques (separes par des virgules)
	define('_STATUT_AUTEUR_RUBRIQUE', '0minirezo');


//  affiche le statut de l'auteur dans l'espace prive
// les admins voient et peuvent modifier les droits d'un auteur
// les admins restreints les voient mais 
// ne peuvent les utiliser que pour mettre un auteur a la poubelle

// http://doc.spip.org/@inc_instituer_auteur_dist
function inc_instituer_auteur_dist($auteur) {

	if (!$id_auteur = $auteur['id_auteur']) {
		$statut = _STATUT_AUTEUR_CREATION; 
	} else
		$statut = $auteur['statut'];

	$ancre = "instituer_auteur-" . intval($id_auteur);

	$menu = choix_statut_auteur($statut, $id_auteur, "$ancre-aff");

	if (!$menu) return '';

	$res = "<b>" . _T('info_statut_auteur')."</b> " . $menu;

	// Prepare le bloc des rubriques pour les admins eventuellement restreints ;
	// si l'auteur n'est pas '0minirezo', on le cache, pour pouvoir le reveler
	// en jquery lorsque le menu de statut change
	$vis = in_array($statut, explode(',', _STATUT_AUTEUR_RUBRIQUE))
		? ''
		: " style='display: none'";

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

	$autres = "";
	// Chercher tous les statuts non standards.
	// Le count(*) ne sert pas, mais en son absence
	// SQL (enfin, une version de SQL) renvoie un ensemble vide !
	$q = spip_query("SELECT statut, count(*) FROM spip_auteurs WHERE statut NOT IN (" . _q($GLOBALS['liste_des_statuts']) . ") GROUP BY statut");

	$hstatut = htmlentities($statut);
	while ($r = spip_fetch_array($q, SPIP_NUM)) {
		$nom = htmlentities($r[0]);
		$autres .= mySel($nom, $hstatut, _T('info_statut_auteur_autre') . ' ' . $nom);
	}

	// Calculer le menu
	$statut_rubrique = str_replace(',', '|', _STATUT_AUTEUR_RUBRIQUE);
	return "<select name='statut' size='1' class='fondl'
		onchange=\"(this.options[this.selectedIndex].value.match(/^($statut_rubrique)\$/))?jQuery('#$ancre:hidden').slideDown():jQuery('#$ancre:visible').slideUp();\">"
	. liste_statuts_instituer($statut, $id_auteur) 
	. $autres
	. "\n<option" .
		mySel("5poubelle",$statut) .
		" class='danger'>&gt; "
		._T('texte_statut_poubelle') .
		'</option>'
	. "</select>\n";
}

// http://doc.spip.org/@liste_statuts_instituer
function liste_statuts_instituer($courant, $id_auteur) {
	$recom = array("info_administrateurs" => _T('item_administrateur_2'),
		       "info_redacteurs" =>  _T('intem_redacteur'),
		       "info_visiteurs" => _T('item_visiteur'));
	
	// A-t-on le droit de promouvoir cet auteur comme admin 
	// et y a-t-il des visiteurs ?

	$droits = array("info_administrateurs" =>
		       autoriser('modifier', 'auteur', $id_auteur,
				 null, array('statut' => '0minirezo')),
		       "info_redacteurs" => true,
		       "info_visiteurs" => avoir_visiteurs());
	
	$menu = '';
	foreach($GLOBALS['liste_des_statuts'] as $k => $v) {
		if (isset($recom[$k]) AND $droits[$k])
			$menu .=  mySel($v, $courant, $recom[$k]);

	}
	// Ajouter l'option "nouveau" si l'auteur n'est pas confirme
	if ($courant == 'nouveau')
		$menu .= mySel('nouveau',$courant,_T('info_statut_auteur_a_confirmer'));

	return $menu;
}

// http://doc.spip.org/@choix_rubriques_admin_restreint
function choix_rubriques_admin_restreint($auteur) {
	global $spip_lang;

	$id_auteur = intval($auteur['id_auteur']);

	$result_admin = spip_query("SELECT rubriques.id_rubrique, " . creer_objet_multi ("titre", $spip_lang) . " FROM spip_auteurs_rubriques AS lien, spip_rubriques AS rubriques WHERE lien.id_auteur=$id_auteur AND lien.id_rubrique=rubriques.id_rubrique ORDER BY multi");

	$restreint = (spip_num_rows($result_admin) > 0);

	if (!$restreint) {
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
	if (autoriser('modifier', 'auteur', $id_auteur, NULL, array('restreintes' => true))) {

		$label = $restreint
			? _T('info_ajouter_rubrique')
			: _T('info_restreindre_rubrique');

		$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');

		$res .= debut_block_depliable(true,"statut$id_auteur")
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
