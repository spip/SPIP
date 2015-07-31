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
include_spip('inc/acces');
include_spip('inc/autoriser');

// http://doc.spip.org/@exec_auteur_infos_dist
function exec_auteur_infos_dist() {

	exec_auteur_infos_args(intval(_request('id_auteur')),
		_request('nom'),
		_request('new'),
		_request('echec'),
		_request('redirect'));
}

// http://doc.spip.org/@exec_auteur_infos_args
function exec_auteur_infos_args($id_auteur, $nom, $new, $echec='', $redirect='')
{
	pipeline('exec_init',
		array('args' => array(
			'exec'=> 'auteur_infos',
			'id_auteur'=>$id_auteur),
			'data'=>''
		)
	);

	if ($id_auteur) {
		$s = sql_select("*", "spip_auteurs", "id_auteur=$id_auteur");
		$auteur = sql_fetch($s);
	} else {
		$auteur = array();
		if (strlen(_request('nom')))
			$auteur['nom'] = $nom;
	}

	if (!$auteur AND !$new AND !$echec) {
		include_spip('inc/minipres');
		echo minipres(_T('public:aucun_auteur'));
	} else auteur_infos_ok($auteur, $id_auteur, $echec, $new, $redirect);
}

// http://doc.spip.org/@auteur_infos_ok
function auteur_infos_ok($auteur, $id_auteur, $echec, $new, $redirect)
{
	global $connect_id_auteur;

	$auteur_infos = charger_fonction('auteur_infos', 'inc');
	$fiche = $auteur_infos($auteur, $new, $echec, _request('edit'), intval(_request('lier_id_article')), $redirect);

		// Entete
		if ($connect_id_auteur == $id_auteur) {
			$commencer_page = charger_fonction('commencer_page', 'inc');
			echo $commencer_page($auteur['nom'], "auteurs", "perso");
		} else {
			$commencer_page = charger_fonction('commencer_page', 'inc');
			echo $commencer_page($auteur['nom'],"auteurs","redacteurs");
		}
		echo "<br /><br /><br />";

		echo debut_gauche('', true);

		echo cadre_auteur_infos($id_auteur, $auteur);

		echo pipeline('affiche_gauche',
			array('args' => array (
				'exec'=>'auteur_infos',
				'id_auteur'=>$id_auteur),
			'data'=>'')
		);

		// Interface de logo
		$iconifier = charger_fonction('iconifier', 'inc');

		if ($id_auteur > 0)
			echo $iconifier('id_auteur', $id_auteur, 'auteur_infos', false, autoriser('modifier', 'auteur', $id_auteur));
		// nouvel auteur : le hack classique
		else if ($fiche)
			echo $iconifier('id_auteur',
			0 - $GLOBALS['visiteur_session']['id_auteur'],
			'auteur_infos');

		echo creer_colonne_droite('', true);
		echo pipeline('affiche_droite',
			      array('args' => array(
						    'exec'=>'auteur_infos',
						    'id_auteur'=>$id_auteur),
				    'data'=>'')
			      );
		echo debut_droite('', true);

		echo debut_cadre_relief("redacteurs-24.gif", true);

		// $fiche est vide si on demande par exemple
		// a creer un auteur alors que c'est interdit
		if ($fiche) {
			echo $fiche;
		} else {
			echo gros_titre(_T('info_acces_interdit'),'', false);
		}

		echo pipeline('affiche_milieu',
			      array('args' => array(
						    'exec'=>'auteur_infos',
						    'id_auteur'=>$id_auteur),
				    'data'=>''));
		
		echo fin_cadre_relief(true);
		echo auteurs_interventions($auteur);
		echo fin_gauche(), fin_page();

}

// http://doc.spip.org/@cadre_auteur_infos
function cadre_auteur_infos($id_auteur, $auteur)
{
	$boite = pipeline ('boite_infos', array('data' => '',
		'args' => array(
			'type'=>'auteur',
			'id' => $id_auteur,
			'row' => $auteur
		)
	));

	if ($boite)
		return debut_boite_info(true) . $boite . fin_boite_info(true);
}


// http://doc.spip.org/@auteurs_interventions
function auteurs_interventions($auteur) {
	$id_auteur = intval($auteur['id_auteur']);
	$statut = $auteur['statut'];

	global $connect_id_auteur;

	include_spip('inc/message_select');

	if (autoriser('voir', 'article')) $aff_art = "'prepa','prop','publie','refuse'";
	else if ($connect_id_auteur == $id_auteur) $aff_art = "'prepa','prop','publie'";
	else $aff_art = "'prop','publie'";

	echo afficher_objets('article',_T('info_articles_auteur'),  array('FROM' => "spip_articles AS articles, spip_auteurs_articles AS lien",  "WHERE" => "lien.id_auteur=$id_auteur AND lien.id_article=articles.id_article AND articles.statut IN ($aff_art)",  'ORDER BY' => "articles.date DESC"));

	// Messages de l'auteur et discussions en cours
	if ($GLOBALS['meta']['messagerie_agenda'] != 'non'
	AND $id_auteur != $connect_id_auteur
	AND autoriser('ecrire', '', '', $auteur)
	) {
		echo "<div class='nettoyeur'>&nbsp;</div>";
		echo debut_cadre_couleur('', true);

		$vus = array();
	
		echo afficher_messages('<b>' . _T('info_discussion_cours') . '</b>', ", spip_auteurs_messages AS lien, spip_auteurs_messages AS lien2", "lien.id_auteur=$connect_id_auteur AND lien2.id_auteur = $id_auteur AND statut='publie' AND type='normal' AND rv!='oui' AND lien.id_message=messages.id_message AND lien2.id_message=messages.id_message", $vus, false, false);
	
		echo afficher_messages('<b>' . _T('info_vos_rendez_vous') . '</b>', ", spip_auteurs_messages AS lien, spip_auteurs_messages AS lien2", "lien.id_auteur=$connect_id_auteur AND lien2.id_auteur = $id_auteur AND statut='publie' AND type='normal' AND rv='oui' AND date_fin > NOW() AND lien.id_message=messages.id_message AND lien2.id_message=messages.id_message", $vus, false, false);
	
		echo icone_horizontale(_T('info_envoyer_message_prive'), generer_action_auteur("editer_message","normal/$id_auteur"),
			  "message.gif","", false);

		echo fin_cadre_couleur(true);
	}
}
?>
