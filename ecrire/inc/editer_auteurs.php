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
function inc_editer_auteurs_dist($type, $id, $flag, $cherche_auteur, $ids, $titre_boite = NULL, $script_edit_objet = NULL, $script_edit_auteur = NULL)
{
	global $options;
	$arg_ajax = "&id_{$type}=$id";
	if ($script_edit_auteur===NULL) 
		$script_edit_auteur = 'auteur_infos';
	else
		$arg_ajax .= "&script_aut=$script_edit_auteur";
	if ($script_edit_objet===NULL) $script_edit_objet = $type.'s';
	if ($titre_boite===NULL) 
		$titre_boite = _T('texte_auteurs'). aide("artauteurs");
	else 
		$arg_ajax.= "&titre=".urlencode($titre_boite);


	$les_auteurs = determiner_auteurs_objet($type,$id);
	if ($flag AND $options == 'avancees') {
		$futurs = ajouter_auteurs_objet($type, $id, $les_auteurs,$script_edit, $arg_ajax);
	} else $futurs = '';

	$les_auteurs = afficher_auteurs_objet($type, $id, $flag, $les_auteurs, $script_edit_objet, $script_edit_auteur, $arg_ajax);
	return editer_auteurs_objet($type, $id, $flag, $cherche_auteur, $ids, $les_auteurs, $futurs, $GLOBALS['meta']['ldap_statut_import'],$titre_boite,$script_edit_objet, $arg_ajax);
}

function editer_auteurs_objet($type, $id, $flag, $cherche_auteur, $ids, $les_auteurs, $futurs, $statut, $titre_boite,$script_edit_objet, $arg_ajax)
{
	global $spip_lang_left, $spip_lang_right, $options;

	$bouton_creer_auteur =  $GLOBALS['connect_toutes_rubriques'];
	$clic = _T('icone_creer_auteur');
	$arg = "0/$id/"  . ($statut ? $statut : '1comite') . '/';
//
// complement de action/editer_auteurs.php pour notifier la recherche d'auteur
//
	if ($cherche_auteur) {

		$reponse ="<p align='$spip_lang_left'>"
		. debut_boite_info(true)
		. rechercher_auteurs_objet($cherche_auteur, $ids, $type, $id,$script_edit_objet, $arg_ajax);

		if ($type=='article' && $bouton_creer_auteur) { // pas generique pour le moment

			$legende = redirige_action_auteur("legender_auteur", $arg . rawurlencode($cherche_auteur), "articles","id_article=$id");

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

		if ($type=='article' && $bouton_creer_auteur) { // pas generique pour le moment

			$legende = redirige_action_auteur("legender_auteur",$arg, "articles","id_article=$id");

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
			? bouton_block_visible("auteurs$type")
			: bouton_block_invisible("auteurs$type")))
	. $titre_boite;

	$res = '<div>&nbsp;</div>' // pour placer le gif patienteur
	. debut_cadre_enfonce("auteur-24.gif", true, "", $bouton)
	. $reponse
	.  ($flag === 'ajax' ?
		debut_block_visible("auteurs$type") :
		debut_block_invisible("auteurs$type"))
	. $res
	. fin_block()
	. fin_cadre_enfonce(true);

	return ajax_action_greffe("editer_auteurs-$id", $res);
}

function determiner_auteurs_objet($type, $id, $cond='')
{
	$les_auteurs = array();
	if (!preg_match(',^[a-z]*$,',$type)) return $les_auteurs; 

	$result = spip_query("SELECT id_auteur FROM spip_auteurs_{$type}s WHERE id_{$type}="._q($id) . ($cond ? " AND $cond" : ''));
	while ($row = spip_fetch_array($result))
		$les_auteurs[]= $row['id_auteur'];

	return $les_auteurs;
}

function rechercher_auteurs_objet($cherche_auteur, $ids, $type, $id, $script_edit_objet, $arg_ajax)
{
	if (!$ids) {
		return "<b>"._T('texte_aucun_resultat_auteur', array('cherche_auteur' => $cherche_auteur)).".</b><br />";
	}
	elseif ($ids == -1) {
		return "<b>"._T('texte_trop_resultats_auteurs', array('cherche_auteur' => $cherche_auteur))."</b><br />";
	}
	elseif (preg_match('/^\d+$/',$ids)) {

		$row = spip_fetch_array(spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur=$ids"));
		return "<b>"._T('texte_ajout_auteur')."</b><br /><ul><li><span class='verdana1 spip_small'><b><span class='spip_medium'>".typo($row['nom'])."</span></b></span></li></ul>";
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

function afficher_auteurs_objet($type, $id, $flag_editable, $les_auteurs, $script_edit, $script_edit_auteur, $arg_ajax)
{
	global $connect_statut, $options,$connect_id_auteur, $spip_display;

	if (!$les_auteurs) return '';

	$table = array();

	$formater_auteur = charger_fonction('formater_auteur', 'inc');
	foreach($les_auteurs as $id_auteur) {
		$vals = $formater_auteur($id_auteur, $script_edit_auteur);

		if ($flag_editable AND ($connect_id_auteur != $id_auteur OR $connect_statut == '0minirezo') AND $options == 'avancees') {
			$vals[] =  ajax_action_auteur('editer_auteurs', "$id,$type,-$id_auteur", $script_edit, "id_{$type}=$id", array(_T('lien_retirer_auteur')."&nbsp;". http_img_pack('croix-rouge.gif', "X", " class='puce' style='vertical-align: bottom;'")),$arg_ajax);
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


function ajouter_auteurs_objet($type, $id, $les_auteurs,$script_edit, $arg_ajax)
{
	$query = determiner_non_auteurs($les_auteurs, "statut, nom");
	if (!$num = spip_num_rows($query)) return '';

	$js = "findObj_forcer('valider_ajouter_auteur').style.visibility='visible';";

	$text = "<span class='verdana1'><b>"
	. _T('titre_cadre_ajouter_auteur')
	. "</b></span>\n";

	$sel = (($num <= _SPIP_SELECT_MIN_AUTEURS)
		? ("$text<select name='nouv_auteur' size='1' style='width:150px;' class='fondl' onchange=\"$js\">" .
		   objet_auteur_select($query) .
		   "</select>" .
		   "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>" .
		   " <input type='submit' value='"._T('bouton_ajouter')."' class='fondo' />" .
		   "</span>")
		: (((_SPIP_AJAX < 1) OR
		    ($num >= _SPIP_SELECT_MAX_AUTEURS))
	      ? ("$text <input type='text' name='cherche_auteur' onclick=\"$js\" class='fondl' value='' size='20' /><span  class='visible_au_chargement' id='valider_ajouter_auteur'>\n<input type='submit' value='"._T('bouton_chercher')."' class='fondo' /></span>")
	      : (selecteur_auteur_ajax($type, $id, $js, $text)
		 .  "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>"
		 . " <input type='submit' value='"._T('bouton_ajouter')."' class='fondo' />"
		 . "</span>")));

	return ajax_action_auteur('editer_auteurs', "$id,$type",$script_edit, "id_{$type}=$id", $sel,$arg_ajax);
}

// http://doc.spip.org/@determiner_non_auteurs
function determiner_non_auteurs($les_auteurs, $order)
{
	if (!$les_auteurs)
	  $cond = '';
	else $cond = "id_auteur NOT IN (" . join(',',$les_auteurs) . ') AND ';

	return spip_query("SELECT * FROM spip_auteurs WHERE $cond" . "statut!='5poubelle' AND statut!='6forum' AND statut!='nouveau' ORDER BY $order");
}


function objet_auteur_select($result)
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
function selecteur_auteur_ajax($type, $id, $js, $text)
{
	include_spip('inc/chercher_rubrique');
	$url = generer_url_ecrire('selectionner_auteur',"id_article=$id_article");

	return $text . construire_selecteur($url, $js, 'selection_auteur', 'nouv_auteur', ' type="hidden"');
}
?>
