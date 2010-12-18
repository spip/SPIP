<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/actions');
include_spip('inc/texte');
include_spip('inc/layer');
include_spip('inc/presentation');
include_spip('inc/autoriser');


//  affiche le statut de l'auteur dans l'espace prive
// les admins voient et peuvent modifier les droits d'un auteur
// les admins restreints les voient mais 
// ne peuvent les utiliser que pour mettre un auteur a la poubelle

// http://doc.spip.org/@inc_instituer_auteur_dist
function inc_instituer_auteur_dist($auteur, $modif = true) {

	if (!$id_auteur = intval($auteur['id_auteur'])) {
		$statut = _STATUT_AUTEUR_CREATION;
	} else
		$statut = $auteur['statut'];

	$ancre =  "instituer_auteur-" . intval($id_auteur);

	$menu = $modif ? choix_statut_auteur($statut, $id_auteur, "$ancre-aff"):traduire_statut_auteur($auteur['statut']);
	if (!$menu) return '';

	$label = $modif?'label':'b';
	$res = "<$label>" . _T('info_statut_auteur')."</$label> " . $menu;

	if ($modif)
		$res .= editer_choix_webmestre($auteur);
	else
		$res .= afficher_webmestre($auteur);

	// Prepare le bloc des rubriques pour les admins eventuellement restreints ;
	// si l'auteur n'est pas '0minirezo', on le cache, pour pouvoir le reveler
	// en jquery lorsque le menu de statut change
	$vis = in_array($statut, explode(',', _STATUT_AUTEUR_RUBRIQUE))
		? ''
		: " style='display: none'";

	if ($menu_restreints = choix_rubriques_admin_restreint($auteur, $modif))
		$res .= "<div class='instituer_auteur' "
		  . ($modif?"id='$ancre-aff'":'') // seul le bloc en modification necessite un id
		  . "$vis>"
			. $menu_restreints
			. "</div>";

	return $res;
}


function afficher_webmestre($auteur){
	if (autoriser('webmestre','',0,$auteur['id_auteur']))
		return "<p>"._T("info_admin_webmestre")."</p>";
	return "";
}

function editer_choix_webmestre($auteur){
	$res = "";
	$style = "";
	if (!autoriser('modifier', 'auteur', $auteur['id_auteur'],
	null, array('webmestre' => '?'))){
		$res =  afficher_webmestre($auteur);
	}
	else {
		$res = "<input type='checkbox' class='checkbox' name='webmestre' id='webmestre' value='oui'"
			. ($auteur['webmestre']=='oui'?" checked='checked'":"")
			. " />"
			. "<label for='webmestre'>"
			. _T("info_admin_statuer_webmestre")
			. "</label>";

		$res .= "<input type='hidden' name='saisie_webmestre' value='1' />";
		// visible ou pas ?
		if ($auteur['statut']!='0minirezo')
			$style=" style='display:none;'";
	}
	return "<div class='choix' id='choix-webmestre'$style>$res</div>";
}

// http://doc.spip.org/@traduire_statut_auteur
function traduire_statut_auteur($statut){
	$recom = array(
				"info_administrateurs" => 'item_administrateur_2',
				"info_redacteurs" =>  'intem_redacteur',
				"info_visiteurs" => 'item_visiteur',
				"5poubelle" => 'texte_statut_poubelle', // bouh
			);
	if (isset($recom[$statut]))
		return _T($recom[$statut]);
	
	// retrouver directement par le statut sinon
	if ($t = array_search($statut, $GLOBALS['liste_des_statuts'])){
	  if (isset($recom[$t]))
			return _T($recom[$t]);
		return _T($t);
	}
	
	return '';
}

// Menu de choix d'un statut d'auteur
// http://doc.spip.org/@choix_statut_auteur
function choix_statut_auteur($statut, $id_auteur, $ancre) {

	// Le menu doit-il etre actif ?
	if (!autoriser('modifier', 'auteur', $id_auteur,
	null, array('statut' => '?')))
		return '';

	// A-t-on le droit de promouvoir cet auteur comme admin 
	// et y a-t-il des visiteurs ?

	$droits = $GLOBALS['liste_des_statuts'];

	if (!autoriser('modifier', 'auteur', $id_auteur,
		       null, array('statut' => '0minirezo')))
		unset($droits["info_administrateurs"]);

	if (!avoir_visiteurs() AND $statut!==$droits['info_visiteurs'])
		unset($droits['info_visiteurs']);

	$menu = '';
	foreach($droits as $k => $v) {
		if (($v != '5poubelle') && ($k = traduire_statut_auteur($v)))
			$menu .=  mySel($v, $statut, $k);
	}

	// Chercher les statuts non standards
	$l = $GLOBALS['liste_des_statuts'];
	$l[]= 'nouveau';
	$q = sql_allfetsel("statut", 'spip_auteurs', sql_in('statut', $l, 'NOT'), "statut");

	$hstatut = htmlentities($statut);
	foreach ($q as $r) {
		$nom = htmlentities($r['statut']);
		$t = traduire_statut_auteur($nom);
		$t = !$t ? (_T('info_statut_auteur_autre') . ' ' . $nom) : $t;
		$menu .= mySel($nom, $hstatut, $t);
	}

	// Ajouter l'option "nouveau" si l'auteur n'est pas confirme
	if ($statut == 'nouveau')
		$menu .= mySel('nouveau',$statut,_T('info_statut_auteur_a_confirmer'));

	$statut_rubrique = str_replace(',', '|', _STATUT_AUTEUR_RUBRIQUE);
	return "<select class='select' name='statut' id='statut' size='1'
		onchange=\"(this.options[this.selectedIndex].value.match(/^($statut_rubrique)\$/))?jQuery('#$ancre:hidden').slideDown():jQuery('#$ancre:visible').slideUp();"
	. "(this.options[this.selectedIndex].value=='0minirezo')?jQuery('#choix-webmestre:hidden').slideDown():jQuery('#choix-webmestre:visible').slideUp();\">"
	. $menu
	. "\n<option" .
		mySel("5poubelle",$statut) .
		" class='danger'>&gt; "
		._T('texte_statut_poubelle') .
		'</option>'
	. "</select>\n";
}

// http://doc.spip.org/@afficher_rubriques_admin_restreintes
function afficher_rubriques_admin_restreintes($auteur, $modif = true){
	global $spip_lang;

	$id_auteur = intval($auteur['id_auteur']);

	$result = sql_select("rubriques.id_rubrique, " . sql_multi ("titre", $spip_lang) . "", "spip_auteurs_rubriques AS lien LEFT JOIN spip_rubriques AS rubriques ON lien.id_rubrique=rubriques.id_rubrique", "lien.id_auteur=$id_auteur", "", "multi");

	$menu = $restreint = '';
	// L'autorisation de modifier les rubriques restreintes
	// est egale a l'autorisation de passer en admin
	$modif &= autoriser('modifier', 'auteur', $id_auteur, null, array('statut' => '0minirezo'));
	while ($row_admin = sql_fetch($result)) {
		$id_rubrique = $row_admin["id_rubrique"];
		$h = generer_url_ecrire('naviguer', "id_rubrique=$id_rubrique");
		$restreint .= "\n<li id='rubrest_$id_rubrique'>"
			. ($modif
				? "<input type='checkbox' checked='checked' name='restreintes[]' value='$id_rubrique' />\n"
				: ''
			)
			. "<a href='$h'>"
			. typo($row_admin["multi"])
			. "</a>"
			. '</li>';
	}

	if (!$restreint) {
		$phrase = _T('info_admin_gere_toutes_rubriques')."\n";
	} else {

		$menu =  "<ul id='liste_rubriques_restreintes' style='list-style-image: url("
			. chemin_image("rubrique-12.gif")
			. ")'>"
			. $restreint
			. "</ul>\n";

		// Il faut un element zero pour montrer qu'on a l'interface
		// sinon il est impossible de deslectionner toutes les rubriques
		if ($modif)
			$menu .= "<input type='hidden' name='restreintes[]' value='0' />\n";
		$phrase = _T('info_admin_gere_rubriques');
	}

	if ($auteur['statut'] != '0minirezo')
		$phrase = '';

	return "<p>$phrase</p>\n$menu";
}

// http://doc.spip.org/@choix_rubriques_admin_restreint
function choix_rubriques_admin_restreint($auteur, $modif=true) {
	global $spip_lang;

	$id_auteur = intval($auteur['id_auteur']);
	$res = afficher_rubriques_admin_restreintes($auteur, $modif);

	// Ajouter une rubrique a un administrateur restreint
	if ($modif
	AND autoriser('modifier', 'auteur', $id_auteur, NULL, array('restreintes' => true))
	AND $chercher_rubrique = charger_fonction('chercher_rubrique', 'inc')
	AND $a = $chercher_rubrique(0, 'auteur', false)) {

		$label = $restreint
			? _T('info_ajouter_rubrique')
			: _T('info_restreindre_rubrique');

		$res .= debut_block_depliable(true,"statut$id_auteur")
		. "\n<div id='ajax_rubrique' class='arial1'>\n"
		. "<b>"
		. $label 
		. "</b>"
		. "\n<input name='id_auteur' value='"
		. $id_auteur
		. "' type='hidden' />"
		. $a
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
