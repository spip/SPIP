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

include_spip('inc/presentation');
include_spip('inc/actions');

// L'ajout d'un auteur se fait par mini-navigateur dans la fourchette:
define('_SPIP_SELECT_MIN_AUTEURS', 30); // en dessous: balise Select
define('_SPIP_SELECT_MAX_AUTEURS', 30); // au-dessus: saisie + return

// http://doc.spip.org/@inc_editer_auteurs_dist
function inc_editer_auteurs_dist($type, $id, $flag, $cherche_auteur, $ids, $titre_boite = NULL, $script_edit_objet = NULL) {

	$arg_ajax = "&id_{$type}=$id&type=$type";
	if ($script_edit_objet===NULL) $script_edit_objet = $type.'s';
	if ($titre_boite===NULL) 
		$titre_boite = _T('texte_auteurs'). aide("artauteurs");
	else 
		$arg_ajax.= "&titre=".urlencode($titre_boite);

	$aff_les_auteurs = afficher_auteurs_objet($type, $id, $flag, '', $script_edit_objet, $arg_ajax);
	
	if ($flag) {
		$futurs = ajouter_auteurs_objet($type, $id, '',$script_edit_objet, $arg_ajax);
	} else $futurs = '';

	$ldap = isset($GLOBALS['meta']['ldap_statut_import']) ?
	  $GLOBALS['meta']['ldap_statut_import'] : '';

	return editer_auteurs_objet($type, $id, $flag, $cherche_auteur, $ids, $aff_les_auteurs, $futurs, $ldap,$titre_boite,$script_edit_objet, $arg_ajax);
}

// http://doc.spip.org/@editer_auteurs_objet
function editer_auteurs_objet($type, $id, $flag, $cherche_auteur, $ids, $les_auteurs, $futurs, $statut, $titre_boite,$script_edit_objet, $arg_ajax)
{
	global $spip_lang_left, $spip_lang_right;

	$bouton_creer_auteur =  $GLOBALS['connect_toutes_rubriques'];
	$clic = _T('icone_creer_auteur');

//
// complement de action/editer_auteurs.php pour notifier la recherche d'auteur
//
	if ($cherche_auteur) {

		$reponse ="<div style='text-align: $spip_lang_left'>"
		. debut_boite_info(true)
		. rechercher_auteurs_objet($cherche_auteur, $ids, $type, $id,$script_edit_objet, $arg_ajax);

		if ($type=='article' && $bouton_creer_auteur) { // pas generique pour le moment

			$legende = generer_url_ecrire("auteur_infos", "new=oui&lier_id_article=$id");
			if (isset($cherche_auteur))
				$legende = parametre_url($legende, 'nom', $cherche_auteur);
			$legende = parametre_url($legende, 'redirect',
				generer_url_ecrire('articles', "id_article=$id", '&'));

			$reponse .="<div style='width: 200px;'>"
			. icone_horizontale($clic, $legende, "redacteurs-24.gif", "creer.gif", false)
			. "</div> ";

			$bouton_creer_auteur = false;
		}

		$reponse .= fin_boite_info(true)
		. '</div>';
	} else $reponse ='';

	$reponse .= $les_auteurs;

//
// Ajouter un auteur
//

	$res = '';
	if ($flag) {

		if ($type=='article' && $bouton_creer_auteur) { // pas generique pour le moment

			$legende = generer_url_ecrire("auteur_infos", "new=oui&lier_id_article=$id");
			if (isset($cherche_auteur))
				$legende = parametre_url($legende, 'nom', $cherche_auteur);
			$legende = parametre_url($legende, 'redirect',
				generer_url_ecrire('articles', "id_article=$id", '&'));

			$clic = "<span class='verdana1'><b>$clic</b></span>";
			$res = icone_horizontale_display($clic, $legende, "redacteurs-24.gif", "creer.gif", false);
		}

		$res = "<div style='float:$spip_lang_right; width:280px;position:relative;display:inline;'>"
		. $futurs
		."</div>\n"
		. $res;
	}

	$idom = "auteurs_$type" . "_$id";
	$bouton = bouton_block_depliable($titre_boite,$flag ?($flag === 'ajax'):-1,$idom);
	$res = debut_cadre_enfonce("auteur-24.gif", true, "", $bouton)
	. $reponse
	. debut_block_depliable($flag === 'ajax',$idom)
	. $res
	. fin_block()
	. fin_cadre_enfonce(true);

	return ajax_action_greffe("editer_auteurs", $id, $res);
}

// Retourne les auteurs attaches a l'objet $id de type $type
// ou rien s'il y en a trop

// http://doc.spip.org/@determiner_auteurs_objet
function determiner_auteurs_objet($type, $id, $cond='', $limit=200)
{
	if (!preg_match(',^[a-z]*$,',$type)) return array();

	$jointure = 'spip_' . table_jointure('auteur', $type);
	$cond = "id_{$type}=".sql_quote($id) . ($cond ? " AND $cond" : '');
	if (sql_countsel($jointure, $cond) > $limit)
	  return array();
	else return array_map('array_shift', sql_allfetsel("id_auteur", $jointure, $cond));
}

// http://doc.spip.org/@determiner_non_auteurs
function determiner_non_auteurs($type, $id, $andcond='')
{
	return auteurs_autorises(determiner_auteurs_objet($type, $id, $andcond));
}

// http://doc.spip.org/@rechercher_auteurs_objet
function rechercher_auteurs_objet($cherche_auteur, $ids, $type, $id, $script_edit_objet, $arg_ajax)
{
	if (!$ids) {
		return "<b>"._T('texte_aucun_resultat_auteur', array('cherche_auteur' => $cherche_auteur)).".</b><br />";
	}
	elseif ($ids == -1) {
		return "<b>"._T('texte_trop_resultats_auteurs', array('cherche_auteur' => $cherche_auteur))."</b><br />";
	}
	elseif (preg_match('/^\d+$/',$ids)) {

		$nom = sql_getfetsel("nom", "spip_auteurs", "id_auteur=$ids");
		return "<b>"._T('texte_ajout_auteur')."</b><br /><ul><li><span class='verdana1 spip_small'><b><span class='spip_medium'>".typo($nom)."</span></b></span></li></ul>";
	}
	else {
		$ids = preg_replace('/[^0-9,]/','',$ids); // securite
		$result = sql_select("*", "spip_auteurs", "id_auteur IN ($ids)", "", "nom");

		$res = "<b>"
		. _T('texte_plusieurs_articles', array('cherche_auteur' => $cherche_auteur))
		. "</b><br />"
		.  "<ul class='verdana1'>";
		while ($row = sql_fetch($result)) {
				$id_auteur = $row['id_auteur'];
				$nom_auteur = $row['nom'];
				$email_auteur = $row['email'];
				$bio_auteur = $row['bio'];

				$res .= "<li><b>".typo($nom_auteur)."</b>";

				if ($email_auteur) $res .= " ($email_auteur)";

				$res .= " | "
				  .  ajax_action_auteur('editer_auteurs', "$id,$type,$id_auteur",$script_edit_objet,"id_{$type}=$id", array(_T('lien_ajouter_auteur')),$arg_ajax);

				if (trim($bio_auteur)) {
					$res .= "<br />".couper(propre($bio_auteur), 100)."\n";
				}
				$res .= "</li>\n";
			}
		$res .= "</ul>";
		return $res;
	}
}

// http://doc.spip.org/@afficher_auteurs_objet
function afficher_auteurs_objet($type, $id, $edit, $cond, $script, $arg_ajax)
{
	
	$from = table_jointure('auteur', $type);
	if (!$from) return '' ; // securite
	$from = "spip_{$from}";
	$where = "id_{$type}=".sql_quote($id) . ($cond ? " AND $cond" : '');

	$presenter_liste = charger_fonction('presenter_liste', 'inc');

	$requete = array('SELECT' => "id_auteur", 'FROM' => $from, 'WHERE' => $where);
	$tmp_var = "editer_auteurs-$id";
	$url = generer_url_ecrire('editer_auteurs',$arg_ajax);

	// charger ici meme si pas d'auteurs
	// car inc_formater_auteur peut aussi redefinir 
	// determiner_non_auteurs qui sert plus loin
	if (!$formater = charger_fonction("formater_auteur_$type", 'inc',true))
		$formater = charger_fonction('formater_auteur', 'inc');

	$retirer = array(_T('lien_retirer_auteur')."&nbsp;". http_img_pack('croix-rouge.gif', "X", " class='puce' style='vertical-align: bottom;'"));

	$styles = array(array('arial11', 14), array('arial2'), array('arial11'), array('arial11'), array('arial11'), array('arial1'));

	$tableau = array(); // ne sert pas
	$f = function_exists($edit) ? $edit : 'ajouter_auteur_un';
	return 	$presenter_liste($requete, $f, $tableau, array($formater, $retirer, $arg_ajax, $edit, $id, $type, $script), false, $styles, $tmp_var, '','', $url);
}

// http://doc.spip.org/@ajouter_auteur_un
function ajouter_auteur_un($row, $own) {
	global $connect_statut, $connect_id_auteur;
	list($formater, $retirer, $arg_ajax, $flag, $id, $type, $script_edit) = $own;

	$id_auteur = $row['id_auteur'];
	$vals = $formater($id_auteur);
	$voir = ($flag AND ($connect_id_auteur != $id_auteur OR $connect_statut == '0minirezo'));
	if ($voir) {
		$vals[] =  ajax_action_auteur('editer_auteurs', "$id,$type,-$id_auteur", $script_edit, "id_{$type}=$id", $retirer, $arg_ajax);
	} else  $vals[] = "";
	return $vals;
}

// http://doc.spip.org/@ajouter_auteurs_objet
function ajouter_auteurs_objet($type, $id, $cond_les_auteurs,$script_edit, $arg_ajax)
{
	if (!$determiner_non_auteurs = charger_fonction('determiner_non_auteurs_'.$type,'inc',true))
		$determiner_non_auteurs = 'determiner_non_auteurs';

	$cond = $determiner_non_auteurs($type, $id, $cond_les_auteurs);
	$all = objet_auteur_select($cond);
	if (!$all) return '';
	$idom = "auteur_$type" . "_$id";
	$new = $idom . '_new';
	$menu = $idom . '_sel';
	$js = "findObj_forcer('$menu').style.visibility='visible';";

	$text = "<span class='verdana1'><label for='$new'><b>"
	. _T('titre_cadre_ajouter_auteur')
	. "</b></label></span>\n";

	if (!is_numeric($all)) {
		$sel = "$text<select name='$new' id='$new' size='1' style='width:150px;' onchange=\"$js\">$all</select>";
		$clic = _T('bouton_ajouter');
	} else if  ((_SPIP_AJAX < 1) OR ($all >= _SPIP_SELECT_MAX_AUTEURS)) {
		  $sel = "$text <input type='text' name='cherche_auteur' id='$new' onclick=\"$js\" value='' size='20' />";
		  $clic = _T('bouton_chercher');
	} else {
		$sel = selecteur_auteur_ajax($type, $id, $js, $text, $idom);
		$clic = _T('bouton_ajouter');
	}

	return ajax_action_post('editer_auteurs', "$id,$type", $script_edit, "id_{$type}=$id", $sel, $clic, " class='visible_au_chargement' id='$menu'",'', $arg_ajax);
}

// http://doc.spip.org/@objet_auteur_select
function objet_auteur_select($cond)
{
	$count = sql_countsel('spip_auteurs', $cond);
	if (!$count) return '';
	if ($count > _SPIP_SELECT_MIN_AUTEURS) return $count;
	$statut_old = '';
	$statuts = $GLOBALS['liste_des_statuts'];
	$res = sql_allfetsel('*', 'spip_auteurs', $cond, '', "statut, nom");
	foreach ($res as $k => $row) {
		$statut = array_search($row["statut"], $statuts);
		$id_auteur = $row["id_auteur"];
		$email = $row["email"];
		if (!autoriser('voir', 'auteur'))
			if ($p = strpos($email, '@'))
				  $email = substr($email, 0, $p).'@...';
		if ($email)
			$email = " ($email)";
		if ($statut != $statut_old) {
			$opt = "</optgroup>\n<optgroup class='option_separateur_statut_auteur' label='" . _T($statut) . "'>";
			$statut_old = $statut;
		} else $opt = '';

		$res[$k]= $opt
		. "\n<option class='option_auteur' value='$id_auteur'>"
		. supprimer_tags(couper(typo($row["nom"] . $email), 40))
		. "</option>";
	}
	return "<optgroup label=''><option value='0'>" 
	  . _T('bouton_choisir')
	  . '</option>'
	  . join('', $res)
	  . "\n</optgroup>";
}

// http://doc.spip.org/@selecteur_auteur_ajax
function selecteur_auteur_ajax($type, $id, $js, $text, $idom='')
{
	include_spip('inc/chercher_rubrique');
	$idom2 = $idom . '_new';
	$idom1 = $idom . '_div';
	$url = generer_url_ecrire('selectionner_auteur',"id_article=$id&type=$type");
	return $text . construire_selecteur($url, $js, $idom1, $idom2, ' type="hidden"');
}
?>
