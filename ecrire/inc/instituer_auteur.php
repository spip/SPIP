<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
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

//  affiche le statut de l'auteur dans l'espace prive
// les admins voient et peuvent modifier les droits d'un auteur
// les admins restreints les voient mais 
// ne peuvent les utiliser que pour mettre un auteur a la poubelle

// http://doc.spip.org/@auteur_voir_rubriques
function inc_instituer_auteur_dist($id_auteur, $statut, $url_self)
{
	global $connect_toutes_rubriques, $connect_id_auteur, $connect_statut, $spip_lang_right, $spip_lang;
					 
	if ($connect_statut != "0minirezo") return;

	$result_admin = spip_query("SELECT rubriques.id_rubrique, " . creer_objet_multi ("titre", $spip_lang) . " FROM spip_auteurs_rubriques AS lien, spip_rubriques AS rubriques WHERE lien.id_auteur=$id_auteur AND lien.id_rubrique=rubriques.id_rubrique ORDER BY multi");

	$restreint = spip_num_rows($result_admin);

	if (!$restreint) 
		$res = _T('info_admin_gere_toutes_rubriques');
	else {
		$modif = ($connect_toutes_rubriques AND $connect_id_auteur != $id_auteur) ? "id_auteur=$id_auteur" : '';

		$lien = !$modif 
		? ''
		: array("&nbsp;&nbsp;&nbsp;&nbsp;[<font size='1'>"
			. _T('lien_supprimer_rubrique')
			. "</font>]");

		$res = '';

		while ($row_admin = spip_fetch_array($result_admin)) {
			$id_rubrique = $row_admin["id_rubrique"];
			
			$res .= "\n<li><a href='"
			. generer_url_ecrire("naviguer","id_rubrique=$id_rubrique")
			. "'>"
			. typo($row_admin["multi"])
			. "</a>"
			. (!$modif ? '' :
			   ajax_action_auteur('instituer_auteur', "$id_auteur/-$id_rubrique", $url_self, $modif, $lien))
			. '</li>';
		}

		$res =  _T('info_admin_gere_rubriques')
		. "\n<ul style='list-style-image: url("
		. _DIR_IMG_PACK
		. "rubrique-12.gif)'>"
		. $res
		. "</ul>";
	}

	// si pas admin au chargement, rien a montrer. 
	$vis = ($statut == '0minirezo') ? '' : " style='visibility: hidden'";

		// Ajouter une rubrique a un administrateur restreint
	if ($connect_toutes_rubriques AND $connect_id_auteur != $id_auteur) {

		$label = $restreint ? _T('info_ajouter_rubrique') : _T('info_restreindre_rubrique');

		$selecteur_rubrique = charger_fonction('chercher_rubrique', 'inc');

		$res .= debut_block_visible("statut$id_auteur")
		. "\n<div id='ajax_rubrique' class='arial1'><br />\n"
		. "<b>"
		. $label 
		. "</b><br />"
		. "\n<input name='id_auteur' value='"
		. $id_auteur
		. "' type='hidden' />"
		. $selecteur_rubrique(0, 'auteur', false)
		. "</div>\n"
		. fin_block();
	}
		
	$droit = (($connect_toutes_rubriques OR $statut != "0minirezo")
		   && ($connect_id_auteur != $id_auteur));

	$ancre = "instituer_auteur-" . intval($id_auteur);

	if ($droit) {
		$res = "<b>"._T('info_statut_auteur')." </b> "
		. choix_statut_auteur($statut, "$ancre-aff")
		. "<div id='$ancre-aff'$vis>"
		. $res
		. "</div><div align='"
		.  $spip_lang_right
		. "'><input type='submit' class='fondo' value=\""
		. _T('bouton_valider')
		. "\" /></div>";
		
		$res = ajax_action_auteur('instituer_auteur', $id_auteur, $url_self, (!$id_auteur ? "" : "id_auteur=$id_auteur"), $res);
	}

	return (_request('var_ajaxcharset'))
	? $res
	: (debut_cadre_relief('',true)
		. "<div id='"
		. $ancre
		. "'>"
		. $res 
		. '</div>'
		. fin_cadre_relief(true));
}

// http://doc.spip.org/@cadre_auteur_infos
function cadre_auteur_infos($id_auteur, $auteur)
{
  global $connect_statut;

  if ($id_auteur) {
	debut_boite_info();
	echo "<CENTER>";
	echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=1><B>"._T('titre_cadre_numero_auteur')."&nbsp;:</B></FONT>";
	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=6><B>$id_auteur</B></FONT>";
	echo "</CENTER>";


// "Voir en ligne" si l'auteur a un article publie
// seuls les admins peuvent "previsualiser" une page auteur
	$n = spip_num_rows(spip_query("SELECT lien.id_article FROM spip_auteurs_articles AS lien, spip_articles AS articles WHERE lien.id_auteur=$id_auteur AND lien.id_article=articles.id_article AND articles.statut='publie'"));
	if ($n)
		voir_en_ligne ('auteur', $id_auteur, 'publie');
	else if ($connect_statut == '0minirezo')
		voir_en_ligne ('auteur', $id_auteur, 'prop');

	fin_boite_info();
  }
}

// http://doc.spip.org/@statut_modifiable_auteur
function statut_modifiable_auteur($id_auteur, $auteur)
{
	global $connect_statut, $connect_toutes_rubriques, $connect_id_auteur;

// on peut se changer soi-meme
	  return  (($connect_id_auteur == $id_auteur) ||
  // sinon on doit etre admin
  // et pas admin restreint pour changer un autre admin ou creer qq
		(($connect_statut == "0minirezo") &&
		 ($connect_toutes_rubriques OR 
		  ($id_auteur AND ($auteur['statut'] != "0minirezo")))));
}

// Menu de choix d'un statut d'auteur
// http://doc.spip.org/@choix_statut_auteur
function choix_statut_auteur($statut, $ancre) {
	global $connect_toutes_rubriques;

	$menu = "<select name='statut' size='1' class='fondl'
		onChange=\"findObj_forcer('$ancre').style.visibility = (this.selectedIndex ? 'hidden' : 'visible');\">";

	// Si on est admin restreint, on n'a pas le droit de modifier un admin
	if ($connect_toutes_rubriques)
		$menu .= "\n<option" .
			mySel("0minirezo",$statut) .
			">" . _T('item_administrateur_2')
			. '</option>';

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
	if (!in_array($statut, array('nouveau', '0minirezo', '1comite', '6forum')))
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
?>
