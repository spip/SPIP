<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

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


	$cond_les_auteurs = "";
	$aff_les_auteurs = afficher_auteurs_objet($type, $id, $flag, $cond_les_auteurs, $script_edit_objet, $arg_ajax);
	
	if ($flag) {
		$futurs = ajouter_auteurs_objet($type, $id, $cond_les_auteurs,$script_edit_objet, $arg_ajax);
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

	$bouton = bouton_block_depliable($titre_boite,$flag ?($flag === 'ajax'):-1,"auteurs$type");
	$res = debut_cadre_enfonce("auteur-24.gif", true, "", $bouton)
	. $reponse
	. debut_block_depliable($flag === 'ajax',"auteurs$type")
	. $res
	. fin_block()
	. fin_cadre_enfonce(true);

	return ajax_action_greffe("editer_auteurs", $id, $res);
}

// http://doc.spip.org/@determiner_auteurs_objet
function determiner_auteurs_objet($type, $id, $cond='', $limit='')
{
	$les_auteurs = array();
	if (!preg_match(',^[a-z]*$,',$type)) return $les_auteurs; 

	$jointure = table_jointure('auteur', $type);
	$result = sql_select("id_auteur", "spip_{$jointure}", "id_{$type}=".sql_quote($id) . ($cond ? " AND $cond" : ''),'','', $limit);

	return $result;
}
// http://doc.spip.org/@determiner_non_auteurs
function determiner_non_auteurs($type, $id, $cond_les_auteurs, $order)
{
	$res = determiner_auteurs_objet($type, $id, $cond_les_auteurs);
	if (sql_count($res)<200){ // probleme de performance au dela, on ne filtre plus
		$cond = array();
		while ($row = sql_fetch($res))$cond[] = $row['id_auteur'];
		$cond = sql_in("id_auteur", $cond, 'NOT');
	} else  $cond = '';
	sql_free($res);
	return auteurs_autorises($cond, $order);
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

		$row = sql_fetsel("nom", "spip_auteurs", "id_auteur=$ids");
		return "<b>"._T('texte_ajout_auteur')."</b><br /><ul><li><span class='verdana1 spip_small'><b><span class='spip_medium'>".typo($row['nom'])."</span></b></span></li></ul>";
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
function afficher_auteurs_objet($type, $id, $flag_editable, $cond, $script_edit, $arg_ajax)
{
	global $connect_statut, $connect_id_auteur, $spip_display;
	
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
	return 	$presenter_liste($requete, 'ajouter_auteur_un', $tableau, array($formater, $retirer, $arg_ajax, $flag_editable, $id, $type, $script_edit), false, $styles, $tmp_var, '','', $url);
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

	$query = $determiner_non_auteurs($type, $id, $cond_les_auteurs, "statut, nom");
	if (!$num = sql_count($query)) return '';
	$js = "findObj_forcer('valider_ajouter_auteur').style.visibility='visible';";

	$text = "<span class='verdana1'><label for='nouv_auteur'><b>"
	. _T('titre_cadre_ajouter_auteur')
	. "</b></label></span>\n";

	if ($num <= _SPIP_SELECT_MIN_AUTEURS){
		$sel = "$text<select name='nouv_auteur' id='nouv_auteur' size='1' style='width:150px;' class='fondl' onchange=\"$js\">" .
		   objet_auteur_select($query) .
		   "</select>";
		$clic = _T('bouton_ajouter');
	} else if  ((_SPIP_AJAX < 1) OR ($num >= _SPIP_SELECT_MAX_AUTEURS)) {
		  $sel = "$text <input type='text' name='cherche_auteur' id='nouv_auteur' onclick=\"$js\" class='fondl' value='' size='20' />";
		  $clic = _T('bouton_chercher');
	} else {
	    $sel = selecteur_auteur_ajax($type, $id, $js, $text);
	    $clic = _T('bouton_ajouter');
	}

	return ajax_action_post('editer_auteurs', "$id,$type", $script_edit, "id_{$type}=$id", $sel, $clic, "class='fondo visible_au_chargement' id='valider_ajouter_auteur'", "", $arg_ajax);
}

// http://doc.spip.org/@objet_auteur_select
function objet_auteur_select($result)
{
	$statut_old = $premiere_old = $res = '';
	$t = 'info_administrateurs';
	while ($row = sql_fetch($result)) {
		$id_auteur = $row["id_auteur"];
		$nom = $row["nom"];
		$email = $row["email"];
		$statut = array_search($row["statut"], $GLOBALS['liste_des_statuts']);
#		$premiere = strtoupper(substr(trim($nom), 0, 1));

		if (!autoriser('voir', 'auteur'))
			if ($p = strpos($email, '@'))
				  $email = substr($email, 0, $p).'@...';
		if ($email)
			$email = " ($email)";

		if ($statut != $statut_old) {
			$res .= "\n<option value=\"x\" />";
			$res .= "\n<option value=\"x\" class='option_separateur_statut_auteur'> " . _T($statut) . "</option>";
		}

		if ($premiere != $premiere_old AND ($statut != $t OR !$premiere_old))
			$res .= "\n<option value=\"x\" />";
				
		$res .= "\n<option value=\"$id_auteur\">&nbsp;&nbsp;&nbsp;&nbsp;" . supprimer_tags(couper(typo("$nom$email"), 40)) . '</option>';
		$statut_old = $statut;
		$premiere_old = $premiere;
	}
	return $res;
}

// http://doc.spip.org/@selecteur_auteur_ajax
function selecteur_auteur_ajax($type, $id, $js, $text)
{
	include_spip('inc/chercher_rubrique');
	$url = generer_url_ecrire('selectionner_auteur',"id_article=$id");

	return $text . construire_selecteur($url, $js, 'selection_auteur', 'nouv_auteur', ' type="hidden"');
}
?>
