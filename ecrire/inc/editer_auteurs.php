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
function inc_editer_auteurs_dist($type, $id, $flag, $cherche_auteur, $ids, $titre_boite = NULL, $script_edit_objet = NULL) {

	$arg_ajax = "&id_{$type}=$id";
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
			$legende = parametre_url($legende, 'nom', $cherche_auteur);
			$legende = parametre_url($legende, 'redirect',
				generer_url_ecrire('articles', "id_article=$id", '&'));

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
	$result = spip_abstract_select("id_auteur", "spip_{$jointure}", "id_{$type}="._q($id) . ($cond ? " AND $cond" : ''),'','', $limit);

	return $result;
}
// http://doc.spip.org/@determiner_non_auteurs
function determiner_non_auteurs($type, $id, $cond_les_auteurs, $order)
{
	$cond = '';
	$res = determiner_auteurs_objet($type, $id, $cond_les_auteurs);
	if (spip_num_rows($res)<200){ // probleme de performance au dela, on ne filtre plus
		while ($row = spip_abstract_fetch($res))
			$cond .= ",".$row['id_auteur'];
	}
	if ($cond) $cond = "id_auteur NOT IN (" . substr($cond,1) . ')  ';

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

		$row = spip_abstract_fetch(spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur=$ids"));
		return "<b>"._T('texte_ajout_auteur')."</b><br /><ul><li><span class='verdana1 spip_small'><b><span class='spip_medium'>".typo($row['nom'])."</span></b></span></li></ul>";
	}
	else {
		$ids = preg_replace('/[^0-9,]/','',$ids); // securite
		$result = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur IN ($ids) ORDER BY nom");

		$res = "<b>"
		. _T('texte_plusieurs_articles', array('cherche_auteur' => $cherche_auteur))
		. "</b><br />"
		.  "<ul class='verdana1'>";
		while ($row = spip_abstract_fetch($result)) {
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
function afficher_auteurs_objet($type, $id, $flag_editable, $cond_les_auteurs, $script_edit, $arg_ajax)
{
	global $connect_statut, $connect_id_auteur, $spip_display;
	
	$les_auteurs = array();
	if (!preg_match(',^[a-z]*$,',$type)) return $les_auteurs; 

	$result = determiner_auteurs_objet($type,$id,$cond_les_auteurs);
	$cpt = spip_num_rows($result);

	$tmp_var = "editer_auteurs-$id";
	$nb_aff = floor(1.5 * _TRANCHES);
	if ($cpt > $nb_aff) {
		$nb_aff = _TRANCHES; 
		$tranches = afficher_tranches_requete($cpt, $tmp_var, generer_url_ecrire('editer_auteurs',$arg_ajax), $nb_aff);
	} else $tranches = '';
	
	$deb_aff = _request($tmp_var);
	$deb_aff = ($deb_aff !== NULL ? intval($deb_aff) : 0);
	
	$limit = (($deb_aff < 0) ? '' : "$deb_aff, $nb_aff");
	$result = determiner_auteurs_objet($type,$id,$cond_les_auteurs,$limit);

	// charger ici meme si ps d'auteurs
	// car inc_formater_auteur peut aussi redefinir determiner_non_auteurs qui sert plus loin
	if (!$formater_auteur = charger_fonction("formater_auteur_$type", 'inc',true))
		$formater_auteur = charger_fonction('formater_auteur', 'inc');

	if (!spip_num_rows($result)) return '';

	$table = array();

	while ($row = spip_abstract_fetch($result)) {
		$id_auteur = $row['id_auteur'];
		$vals = $formater_auteur($id_auteur);

		if ($flag_editable AND ($connect_id_auteur != $id_auteur OR $connect_statut == '0minirezo')) {
			$vals[] =  ajax_action_auteur('editer_auteurs', "$id,$type,-$id_auteur", $script_edit, "id_{$type}=$id", array(_T('lien_retirer_auteur')."&nbsp;". http_img_pack('croix-rouge.gif', "X", " class='puce' style='vertical-align: bottom;'")),$arg_ajax);
		} else  $vals[] = "";
		$table[] = $vals;
	}
	
	$largeurs = array('14', '', '', '', '', '');
	$styles = array('arial11', 'arial2', 'arial11', 'arial11', 'arial11', 'arial1');

	$t = afficher_liste($largeurs, $table, $styles);
	if ($spip_display != 4)
	  $t = $tranches
	  	. "<table width='100%' cellpadding='3' cellspacing='0' border='0'>"
	    . $t
	    . "</table>";
	return "<div class='liste'>$t</div>\n";
}


// http://doc.spip.org/@ajouter_auteurs_objet
function ajouter_auteurs_objet($type, $id, $cond_les_auteurs,$script_edit, $arg_ajax)
{
	if (!$determiner_non_auteurs = charger_fonction('determiner_non_auteurs_'.$type,'inc',true))
		$determiner_non_auteurs = 'determiner_non_auteurs';

	$query = $determiner_non_auteurs($type, $id, $cond_les_auteurs, "statut, nom");
	if (!$num = spip_num_rows($query)) return '';
	$js = "findObj_forcer('valider_ajouter_auteur').style.visibility='visible';";

	$text = "<span class='verdana1'><b>"
	. _T('titre_cadre_ajouter_auteur')
	. "</b></span>\n";

	if ($num <= _SPIP_SELECT_MIN_AUTEURS){
		$sel = "$text<select name='nouv_auteur' size='1' style='width:150px;' class='fondl' onchange=\"$js\">" .
		   objet_auteur_select($query) .
		   "</select>";
		$clic = _T('bouton_ajouter');
	} else if  ((_SPIP_AJAX < 1) OR ($num >= _SPIP_SELECT_MAX_AUTEURS)) {
		  $sel = "$text <input type='text' name='cherche_auteur' onclick=\"$js\" class='fondl' value='' size='20' />";
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
	while ($row = spip_abstract_fetch($result)) {
		$id_auteur = $row["id_auteur"];
		$nom = $row["nom"];
		$email = $row["email"];
		$statut = array_search($row["statut"], $GLOBALS['liste_des_statuts']);
		$premiere = strtoupper(substr(trim($nom), 0, 1));

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
	$url = generer_url_ecrire('selectionner_auteur',"id_article=$id_article");

	return $text . construire_selecteur($url, $js, 'selection_auteur', 'nouv_auteur', ' type="hidden"');
}
?>
