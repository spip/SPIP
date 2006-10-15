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
include_spip('inc/presentation');
include_spip('inc/acces');

// http://doc.spip.org/@exec_auteur_infos_dist
function exec_auteur_infos_dist()
{
	global $id_auteur, $redirect, $echec, $initial,
	  $connect_statut, $connect_toutes_rubriques, $connect_id_auteur;

	$id_auteur = intval($id_auteur);

	pipeline('exec_init',
		array('args' => array(
			'exec'=>'auteur_infos',
			'id_auteur'=>$id_auteur),
			'data'=>'')	);


	// id_auteur nul ==> creation, et seuls les admins complets creent
	if (!$id_auteur AND $connect_toutes_rubriques) {
		$arg = "0/";
		redirige_par_entete(generer_action_auteur('legender_auteur', $arg, $redirect, true));
		exit;
	}

	$auteur = spip_fetch_array(spip_query("SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur"));

	if (!$auteur) {
                gros_titre(_T('info_acces_interdit'));
                exit;
        }

	$legender_auteur = charger_fonction('legender_auteur', 'inc');
	$legender_auteur = $legender_auteur($id_auteur, $auteur, $initial, $echec, $redirect);

	if (_request('var_ajaxcharset')) ajax_retour($legender_auteur);

	if ($connect_id_auteur == $id_auteur)
		debut_page($auteur['nom'], "auteurs", "perso");
	else
		debut_page($auteur['nom'],"auteurs","redacteurs");

	echo "<br /><br /><br />";

	debut_gauche();

	echo cadre_auteur_infos($id_auteur, $auteur);

	echo pipeline('affiche_gauche',
		array('args' => array(
			'exec'=>'auteur_infos',
			'id_auteur'=>$id_auteur),
		'data'=>'')
	);

  // charger ça tout de suite pour diposer de la fonction ci-dessous
	$instituer_auteur = charger_fonction('instituer_auteur', 'inc');
	$instituer_auteur = $instituer_auteur($id_auteur, $auteur['statut'], "auteurs_edit");

	if (statut_modifiable_auteur($id_auteur, $auteur) AND ($spip_display != 4)) {
		$iconifier = charger_fonction('iconifier', 'inc');
		$iconifier = $iconifier('id_auteur', $id_auteur, 'auteur_infos');
	} else $iconifier ='';

	creer_colonne_droite();
	echo pipeline('affiche_droite',
		array('args' => array(
			'exec'=>'auteur_infos',
			'id_auteur'=>$id_auteur),
		'data'=>'')
	);

	echo $iconifier, 

	debut_droite();

	echo 
	  debut_cadre_relief("redacteurs-24.gif", true),
	  $legender_auteur, $instituer_auteur;

	auteurs_interventions($id_auteur, $auteur['statut']);

	echo fin_cadre_relief(true),
	  fin_page();
}

// http://doc.spip.org/@cadre_auteur_infos
function cadre_auteur_infos($id_auteur, $auteur)
{
	global $connect_statut;

	if (!$id_auteur) return '';

	$res = "<center>"
	.  "<font face='Verdana,Arial,Sans,sans-serif' size='1'><b>"._T('titre_cadre_numero_auteur')."&nbsp;:</b></font>"
	. "<br /><font face='Verdana,Arial,Sans,sans-serif' size='6'><b>$id_auteur</b></font>"
	. "</center>";

// "Voir en ligne" si l'auteur a un article publie
// seuls les admins peuvent "previsualiser" une page auteur
	$n = spip_num_rows(spip_query("SELECT lien.id_article FROM spip_auteurs_articles AS lien, spip_articles AS articles WHERE lien.id_auteur=$id_auteur AND lien.id_article=articles.id_article AND articles.statut='publie'"));

	if ($n)
	  $res .= voir_en_ligne ('auteur', $id_auteur, 'publie', 'racine-24.gif', false);
	else if ($connect_statut == '0minirezo')
	  $res .= voir_en_ligne ('auteur', $id_auteur, 'prop', 'racine-24.gif', false);

	return debut_boite_info(true) . $res . fin_boite_info(true);
}


function auteurs_interventions($id_auteur, $statut)
{
	global $connect_statut, $connect_id_auteur;

	if ($connect_statut == "0minirezo") $aff_art = "'prepa','prop','publie','refuse'";
	else if ($connect_id_auteur == $id_auteur) $aff_art = "'prepa','prop','publie'";
	else $aff_art = "'prop','publie'";

	afficher_articles(_T('info_articles_auteur'),  array('FROM' => "spip_articles AS articles, spip_auteurs_articles AS lien",  "WHERE" => "lien.id_auteur='$id_auteur' AND lien.id_article=articles.id_article AND articles.statut IN ($aff_art)",  'ORDER BY' => "articles.date DESC"), true);

	if ($id_auteur != $connect_id_auteur
	    AND ($statut == '0minirezo' OR $statut == '1comite')) {
		echo "<div>&nbsp;</div>";
		debut_cadre_couleur();

		$vus = array();
	
		afficher_messages(_T('info_discussion_cours'), ", spip_auteurs_messages AS lien, spip_auteurs_messages AS lien2", "lien.id_auteur=$connect_id_auteur AND lien2.id_auteur = $id_auteur AND statut='publie' AND type='normal' AND rv!='oui' AND lien.id_message=messages.id_message AND lien2.id_message=messages.id_message", $vus, false, false);
	
		afficher_messages(_T('info_vos_rendez_vous'), ", spip_auteurs_messages AS lien, spip_auteurs_messages AS lien2", "lien.id_auteur=$connect_id_auteur AND lien2.id_auteur = $id_auteur AND statut='publie' AND type='normal' AND rv='oui' AND date_fin > NOW() AND lien.id_message=messages.id_message AND lien2.id_message=messages.id_message", $vus, false, false);
	
		icone_horizontale(_T('info_envoyer_message_prive'), generer_url_ecrire("message_edit", "new=oui&type=normal&dest=$id_auteur"),
				  "message.gif");
		fin_cadre_couleur();
	}
}
?>
