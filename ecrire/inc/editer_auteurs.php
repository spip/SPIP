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

include_spip('inc/presentation');
include_spip('inc/actions');

// L'ajout d'un auteur se fait par mini-navigateur dans la fourchette:
define('_SPIP_SELECT_MIN_AUTEURS', 10); // en dessous: balise Select
define('_SPIP_SELECT_MAX_AUTEURS', 100); // au-dessus: saisie + return

// http://doc.spip.org/@inc_editer_auteurs_dist
function inc_editer_auteurs_dist($id_article, $flag, $cherche_auteur, $ids)
{
	global $options;

	$les_auteurs = determiner_auteurs_article($id_article);
	if ($flag AND $options == 'avancees') {
		$futurs = ajouter_auteurs_articles($id_article, $les_auteurs);
	} else $futurs = '';

	$les_auteurs = afficher_auteurs_articles($id_article, $flag, $les_auteurs);
	return editer_auteurs_article($id_article, $flag, $cherche_auteur, $ids, $les_auteurs, $futurs, $GLOBALS['meta']['ldap_statut_import']);
}

// http://doc.spip.org/@editer_auteurs_article
function editer_auteurs_article($id_article, $flag, $cherche_auteur, $ids, $les_auteurs, $futurs, $statut)
{
	global $spip_lang_left, $spip_lang_right, $options;

	$bouton_creer_auteur =  $GLOBALS['connect_toutes_rubriques'];
	$clic = _T('icone_creer_auteur');
	$arg = "0/$id_article/"  . ($statut ? $statut : '1comite') . '/';
//
// complement de action/editer_auteurs.php pour notifier la recherche d'auteur
//
	if ($cherche_auteur) {

		$reponse ="<p align='$spip_lang_left'>"
		. debut_boite_info(true)
		. rechercher_auteurs_articles($cherche_auteur, $ids,  $id_article);

		if ($bouton_creer_auteur) {

			$legende = redirige_action_auteur("legender_auteur", $arg . rawurlencode($cherche_auteur), "articles","id_article=$id_article");

			$reponse .="<div style='width: 200px;'>"
			. icone_horizontale($clic, $legende, "redacteurs-24.gif", "creer.gif", false)
			. "</div> ";

			$bouton_creer_auteur = false;
		}

		$reponse .= fin_boite_info(true)
		. '</p>';
	} else $reponse ='';

	$reponse .= $les_auteurs;

//
// Ajouter un auteur
//

	$res = '';
	if ($flag AND $options == 'avancees') {

		if ($bouton_creer_auteur) {

			$legende = redirige_action_auteur("legender_auteur",$arg, "articles","id_article=$id_article");

			$clic = "<span class='verdana1'><b>$clic</b></span>";
			$res = "<div style='width:170px;'>"
			. icone_horizontale($clic, $legende, "redacteurs-24.gif", "creer.gif", false)
			. "</div>\n";
		}

		$res = "<div style='float:$spip_lang_right; width:280px;position:relative;display:inline;'>"
		. $futurs
		."</div>\n"
		. $res;
	}

	$bouton = (!$flag 
		   ? ''
		   : (($flag === 'ajax')
			? bouton_block_visible("auteursarticle")
			: bouton_block_invisible("auteursarticle")))
	. _T('texte_auteurs')
	. aide("artauteurs");

	$res = '<div>&nbsp;</div>' // pour placer le gif patienteur
	. debut_cadre_enfonce("auteur-24.gif", true, "", $bouton)
	. $reponse
	.  ($flag === 'ajax' ?
		debut_block_visible("auteursarticle") :
		debut_block_invisible("auteursarticle"))
	. $res
	. fin_block()
	. fin_cadre_enfonce(true);

	return ajax_action_greffe("editer_auteurs-$id_article", $res);
}

// http://doc.spip.org/@determiner_auteurs_article
function determiner_auteurs_article($id_article, $cond='')
{
	$les_auteurs = array();

	$result = auteurs_article($id_article,$cond); // function de inc/auth

	while ($row = spip_fetch_array($result))
		$les_auteurs[]= $row['id_auteur'];

	return $les_auteurs;
}


// http://doc.spip.org/@rechercher_auteurs_articles
function rechercher_auteurs_articles($cherche_auteur, $ids, $id_article)
{
	if (!$ids) {
		return "<b>"._T('texte_aucun_resultat_auteur', array('cherche_auteur' => $cherche_auteur)).".</b><br />";
	}
	elseif ($ids == -1) {
		return "<b>"._T('texte_trop_resultats_auteurs', array('cherche_auteur' => $cherche_auteur))."</b><br />";
	}
	elseif (preg_match('/^\d+$/',$ids)) {

		$row = spip_fetch_array(spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur=$ids"));
		return "<b>"._T('texte_ajout_auteur')."</b><br /><ul><li><span style='font-size: 14px;' class='verdana1'><b><span style='font-size: 16px;'>".typo($row['nom'])."</span></b></span></li></ul>";
	}
	else {
		$ids = preg_replace('/[^0-9,]/','',$ids); // securite
		$result = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur IN ($ids) ORDER BY nom");

		$res = "<b>"
		. _T('texte_plusieurs_articles', array('cherche_auteur' => $cherche_auteur))
		. "</b><br />"
		.  "<ul class='verdana1'>";
		while ($row = spip_fetch_array($result)) {
				$id_auteur = $row['id_auteur'];
				$nom_auteur = $row['nom'];
				$email_auteur = $row['email'];
				$bio_auteur = $row['bio'];

				$res .= "<li><b>".typo($nom_auteur)."</b>";

				if ($email_auteur) $res .= " ($email_auteur)";

				$res .= " | "
				  .  ajax_action_auteur('editer_auteurs', "$id_article,$id_auteur","articles","id_article=$id_article", array(_T('lien_ajouter_auteur')));

				if (trim($bio_auteur)) {
					$res .= "<br />".couper(propre($bio_auteur), 100)."\n";
				}
				$res .= "</li>\n";
			}
		$res .= "</ul>";
		return $res;
	}
}

// http://doc.spip.org/@afficher_auteurs_articles
function afficher_auteurs_articles($id_article, $flag_editable, $les_auteurs)
{
	global $connect_statut, $options,$connect_id_auteur, $spip_display;

	if (!$les_auteurs) return '';

	$table = array();

	$formater_auteur = charger_fonction('formater_auteur', 'inc');
	foreach($les_auteurs as $id_auteur) {
		$vals = $formater_auteur($id_auteur);

		if ($flag_editable AND ($connect_id_auteur != $id_auteur OR $connect_statut == '0minirezo') AND $options == 'avancees') {
			$vals[] =  ajax_action_auteur('editer_auteurs', "$id_article,-$id_auteur",'articles', "id_article=$id_article", array(_T('lien_retirer_auteur')."&nbsp;". http_img_pack('croix-rouge.gif', "X", "width='7' height='7' border='0' align='middle'")));
		} else  $vals[] = "";
		$table[] = $vals;
	}
	
	$largeurs = array('14', '', '', '', '', '');
	$styles = array('arial11', 'arial2', 'arial11', 'arial11', 'arial11', 'arial1');

	$t = afficher_liste($largeurs, $table, $styles);
	if ($spip_display != 4)
	  $t = "<table width='100%' cellpadding='3' cellspacing='0' border='0'>"
	    . $t
	    . "</table>";
	return "<div class='liste'>$t</div>\n";
}


// http://doc.spip.org/@ajouter_auteurs_articles
function ajouter_auteurs_articles($id_article, $les_auteurs)
{
	$query = determiner_non_auteurs($les_auteurs, "statut, nom");
	if (!$num = spip_num_rows($query)) return '';

	$js = "findObj_forcer('valider_ajouter_auteur').style.visibility='visible';";

	$text = "<span class='verdana1'><b>"
	. _T('titre_cadre_ajouter_auteur')
	. "</b></span>\n";

	$sel = (($num <= _SPIP_SELECT_MIN_AUTEURS)
		? ("$text<select name='nouv_auteur' size='1' style='width:150px;' class='fondl' onchange=\"$js\">" .
		   articles_auteur_select($query) .
		   "</select>" .
		   "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>" .
		   " <input type='submit' value='"._T('bouton_ajouter')."' class='fondo' />" .
		   "</span>")
		: (((_SPIP_AJAX < 1) OR
		    ($num >= _SPIP_SELECT_MAX_AUTEURS))
	      ? ("$text <input type='text' name='cherche_auteur' onclick=\"$js\" class='fondl' value='' size='20' /><span  class='visible_au_chargement' id='valider_ajouter_auteur'>\n<input type='submit' value='"._T('bouton_chercher')."' class='fondo' /></span>")
	      : (selecteur_auteur_ajax($id_article, $js, $text)
		 .  "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>"
		 . " <input type='submit' value='"._T('bouton_ajouter')."' class='fondo' />"
		 . "</span>")));

	return ajax_action_auteur('editer_auteurs', $id_article,'articles', "id_article=$id_article", $sel);
}

// http://doc.spip.org/@determiner_non_auteurs
function determiner_non_auteurs($les_auteurs, $order)
{
	if (!$les_auteurs)
	  $cond = '';
	else $cond = "id_auteur NOT IN (" . join(',',$les_auteurs) . ') AND ';

	return spip_query("SELECT * FROM spip_auteurs WHERE $cond" . "statut!='5poubelle' AND statut!='6forum' AND statut!='nouveau' ORDER BY $order");
}


// http://doc.spip.org/@articles_auteur_select
function articles_auteur_select($result)
{
	global $couleur_claire ;

	$statut_old = $premiere_old = $res = '';

	while ($row = spip_fetch_array($result)) {
		$id_auteur = $row["id_auteur"];
		$nom = $row["nom"];
		$email = $row["email"];
		$statut = $row["statut"];

		$statut=str_replace("0minirezo", _T('info_administrateurs'), $statut);
		$statut=str_replace("1comite", _T('info_redacteurs'), $statut);
		$statut=str_replace("6visiteur", _T('info_visiteurs'), $statut);
				
		$premiere = strtoupper(substr(trim($nom), 0, 1));

		if ($connect_statut != '0minirezo')
			if ($p = strpos($email, '@'))
				  $email = substr($email, 0, $p).'@...';
		if ($email)
			$email = " ($email)";

		if ($statut != $statut_old) {
			$res .= "\n<option value=\"x\" />";
			$res .= "\n<option value=\"x\" style='background-color: $couleur_claire;'> $statut</option>";
		}

		if ($premiere != $premiere_old AND ($statut != _T('info_administrateurs') OR !$premiere_old))
			$res .= "\n<option value=\"x\" />";
				
		$res .= "\n<option value=\"$id_auteur\">&nbsp;&nbsp;&nbsp;&nbsp;" . supprimer_tags(couper(typo("$nom$email"), 40)) . '</option>';
		$statut_old = $statut;
		$premiere_old = $premiere;
	}
	return $res;
}

// http://doc.spip.org/@selecteur_auteur_ajax
function selecteur_auteur_ajax($id_article, $js, $text)
{
	include_spip('inc/chercher_rubrique');
	$url = generer_url_ecrire('selectionner_auteur',"id_article=$id_article");

	return $text . construire_selecteur($url, $js, 'selection_auteur', 'nouv_auteur', ' type="hidden"');
}
?>
