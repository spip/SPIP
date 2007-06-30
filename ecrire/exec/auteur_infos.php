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
include_spip('inc/acces');
include_spip('inc/autoriser');

// http://doc.spip.org/@exec_auteur_infos_dist
function exec_auteur_infos_dist() {
	global $connect_id_auteur, $spip_display;

	$id_auteur = intval(_request('id_auteur'));
	$redirect = _request('redirect');
	$echec = _request('echec');
	$new = _request('new');

	pipeline('exec_init',
		array('args' => array(
			'exec'=> 'auteur_infos',
			'id_auteur'=>$id_auteur),
			'data'=>''
		)
	);

	if ($id_auteur) {
		$s = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur");
		$auteur = spip_fetch_array($s);
	} else {
		$auteur = array();
		if (strlen(_request('nom')))
			$auteur['nom'] = _request('nom');
	}

	if (!$auteur AND !$new) {
		include_spip('inc/headers');
		redirige_par_entete(generer_url_ecrire('auteurs'));
	}

	$auteur_infos = charger_fonction('auteur_infos', 'inc');
	$fiche = $auteur_infos($auteur, $new, $echec, _request('edit'), intval(_request('lier_id_article')), $redirect);

/*	// Si on est appele en ajax, on renvoie la fiche
	if (_request('var_ajaxcharset')) {
		ajax_retour($fiche);
	}
	
	// Sinon on la met en page
	else {
*/
		// Entete
		if ($connect_id_auteur == $id_auteur) {
			$commencer_page = charger_fonction('commencer_page', 'inc');
			echo $commencer_page($auteur['nom'], "auteurs", "perso");
		} else {
			$commencer_page = charger_fonction('commencer_page', 'inc');
			echo $commencer_page($auteur['nom'],"auteurs","redacteurs");
		}
		echo "<br /><br /><br />";

		debut_gauche();

		echo cadre_auteur_infos($id_auteur, $auteur);

		echo pipeline('affiche_gauche',
			array('args' => array (
				'exec'=>'auteur_infos',
				'id_auteur'=>$id_auteur),
			'data'=>'')
		);


		creer_colonne_droite();
		echo pipeline('affiche_droite',
			      array('args' => array(
						    'exec'=>'auteur_infos',
						    'id_auteur'=>$id_auteur),
				    'data'=>'')
			      );


		// Interface de logo
		if ($spip_display != 4) {
			$iconifier = charger_fonction('iconifier', 'inc');

			if ($id_auteur > 0)
				echo $iconifier('id_auteur', $id_auteur, 'auteur_infos');

			// nouvel auteur : le hack classique
			else if ($fiche)
				echo $iconifier('id_auteur',
				0 - $GLOBALS['auteur_session']['id_auteur'],
				'auteur_infos');
		}

		echo debut_droite();

		echo debut_cadre_relief("redacteurs-24.gif", true);

		// $fiche est vide si on demande par exemple
		// a creer un auteur alors que c'est interdit
		if ($fiche) {
			echo $fiche;
		} else {
			gros_titre(_T('info_acces_interdit'));
		}

		echo pipeline('affiche_milieu',
			      array('args' => array(
						    'exec'=>'auteur_infos',
						    'id_auteur'=>$id_auteur),
				    'data'=>''));
		
		echo fin_cadre_relief(true);
		echo auteurs_interventions($auteur);
		echo fin_gauche(), fin_page();
/*	} */

}

// http://doc.spip.org/@cadre_auteur_infos
function cadre_auteur_infos($id_auteur, $auteur)
{
	global $connect_statut;

	if (!$id_auteur) return '';

	$res = "\n<div style='font-weight: bold; text-align: center' class='verdana1 spip_xx-small'>" 
	. _T('titre_cadre_numero_auteur')
	. "<br /><span class='spip_xx-large'>"
	. $id_auteur
	. '</span></div>';

// "Voir en ligne" si l'auteur a un article publie
// seuls les admins peuvent "previsualiser" une page auteur
	$n = spip_num_rows(spip_query("SELECT lien.id_article FROM spip_auteurs_articles AS lien, spip_articles AS articles WHERE lien.id_auteur=$id_auteur AND lien.id_article=articles.id_article AND articles.statut='publie'"));

	if ($n)
	  $res .= voir_en_ligne ('auteur', $id_auteur, 'publie', 'racine-24.gif', false);
	else if (autoriser('voir', 'auteur'))
	  $res .= voir_en_ligne ('auteur', $id_auteur, 'prop', 'racine-24.gif', false);

	return debut_boite_info(true) . $res . fin_boite_info(true);
}


// http://doc.spip.org/@auteurs_interventions
function auteurs_interventions($auteur) {
	$id_auteur = $auteur['id_auteur'];
	$statut = $auteur['statut'];

	global $connect_id_auteur;

	include_spip('inc/message_select');

	if (autoriser('voir', 'article')) $aff_art = "'prepa','prop','publie','refuse'";
	else if ($connect_id_auteur == $id_auteur) $aff_art = "'prepa','prop','publie'";
	else $aff_art = "'prop','publie'";

	echo afficher_objets('article',_T('info_articles_auteur'),  array('FROM' => "spip_articles AS articles, spip_auteurs_articles AS lien",  "WHERE" => "lien.id_auteur='$id_auteur' AND lien.id_article=articles.id_article AND articles.statut IN ($aff_art)",  'ORDER BY' => "articles.date DESC"));

	if ($id_auteur != $connect_id_auteur
	AND autoriser('ecrire', '', '', $auteur)) {
		echo "<div class='nettoyeur'>&nbsp;</div>";
		debut_cadre_couleur();

		$vus = array();
	
		echo afficher_messages('<b>' . _T('info_discussion_cours') . '</b>', ", spip_auteurs_messages AS lien, spip_auteurs_messages AS lien2", "lien.id_auteur=$connect_id_auteur AND lien2.id_auteur = $id_auteur AND statut='publie' AND type='normal' AND rv!='oui' AND lien.id_message=messages.id_message AND lien2.id_message=messages.id_message", $vus, false, false);
	
		echo afficher_messages('<b>' . _T('info_vos_rendez_vous') . '</b>', ", spip_auteurs_messages AS lien, spip_auteurs_messages AS lien2", "lien.id_auteur=$connect_id_auteur AND lien2.id_auteur = $id_auteur AND statut='publie' AND type='normal' AND rv='oui' AND date_fin > NOW() AND lien.id_message=messages.id_message AND lien2.id_message=messages.id_message", $vus, false, false);
	
		icone_horizontale(_T('info_envoyer_message_prive'), generer_action_auteur("editer_message","normal/$id_auteur"),
				  "message.gif");
		fin_cadre_couleur();
	}
}
?>
