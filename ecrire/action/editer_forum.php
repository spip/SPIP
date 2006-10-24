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

// http://doc.spip.org/@action_editer_mot_dist
function action_editer_forum_dist() {

	include_spip('inc/actions');
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');
	// arg = l'eventuel mot a supprimer pour d'eventuelles Row SQL
	if (!preg_match(',^(\d+)\D(\d+)\D(\d+)\D(\d+)\D(\d+)\D(\d+)\D(\w+)$,', $arg, $r)) 
		spip_log("action editer_forum: $arg pas compris");
	else action_editer_forum_post($r);
}

// http://doc.spip.org/@action_editer_mot_post
function action_editer_forum_post($r)
{
  global $redirect, $nom_site, $texte, $titre_message, $url_site,  $modif_forum,  $valider_forum;

	list($x,$id_rubrique,$id_parent,$id_article,$id_breve,$id_message,$id_syndic, $statut) = $r;

	 if ($valider_forum AND ($statut!='')) {
		include_spip('inc/texte');
		include_spip('base/abstract_sql');
		include_spip('inc/forum');

		$titre_message = corriger_caracteres($titre_message);
		$texte = corriger_caracteres($texte);

		spip_abstract_insert('spip_forum', "(titre, texte, date_heure, nom_site, url_site, statut, id_auteur,	auteur, email_auteur, id_rubrique, id_parent, id_article, id_breve,	id_message, id_syndic)", "(" . _q($titre_message) . ", " . _q($texte) . ", NOW(), " . _q($nom_site) . ", " . _q($url_site) . ", " . _q($statut) . ", " . $GLOBALS['auteur_session']['id_auteur'] . ", " . _q($GLOBALS['auteur_session']['nom']) . ", " . _q($GLOBALS['auteur_session']['email']) . ",	'$id_rubrique', '$id_parent', '$id_article', '$id_breve',	'$id_message', '$id_syndic')");

		calculer_threads();

		if ($id_message > 0) {
			spip_query("UPDATE spip_auteurs_messages SET vu = 'non' WHERE id_message='$id_message'");

		}
		redirige_par_entete(_DIR_RESTREINT . urldecode($redirect));
	 } else {
	   // on ne fait que passer .... 
	   // et si les clients HTTP respectaient le RFC HTTP selon lequel
	   // une redirection d'un POST doit etre en POST et pas en GET
	   // on n'aurait pas a faire l'horreur ci-dessous.
	   set_request('exec', 'forum_envoi');
	   set_request('id_article', $id_article);
	   set_request('id_breve', $id_breve);
	   set_request('id_message', $id_message);
	   set_request('id_parent', $id_parent);
	   set_request('id_rubrique', $id_rubrique);
	   set_request('id_syndic', $id_syndic);
		     
	   set_request('modif_forum', $modif_forum);
	   set_request('nom_site', $nom_site);
	   set_request('statut', $statut);
	   set_request('texte', $texte);
	   set_request('titre_message', $titre_message);
	   set_request('url_site', $url_site);
	   set_request('url', $redirect);
	   set_request('valider_forum', $valider_forum);
	   include('ecrire/index.php');
	     }
	 exit;
}
?>
