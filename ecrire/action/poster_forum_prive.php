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

// http://doc.spip.org/@action_poster_forum_prive_dist
function action_poster_forum_prive_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	// arg = l'eventuel mot a supprimer pour d'eventuelles Row SQL
	if (!preg_match(',^(\d+)\D(\d+)\D(\w+)\W(\w+)\W(\w+)$,', $arg, $r)) 
		spip_log("action poster_forum_prive: $arg pas compris");
	else action_poster_forum_prive_post($r);
}

// http://doc.spip.org/@action_poster_forum_prive_post
function action_poster_forum_prive_post($r)
{
	list(,$id, $id_parent, $statut, $script, $objet) = $r;

	if (_request('valider_forum') AND ($statut!='')) {
		include_spip('inc/texte');
		include_spip('base/abstract_sql');
		include_spip('inc/forum');

		$titre_message = corriger_caracteres(_request('titre_message'));
		$texte = corriger_caracteres(_request('texte'));

		$id_forum = spip_abstract_insert('spip_forum', "($objet, titre, texte, date_heure, nom_site, url_site, statut, id_auteur,	auteur, email_auteur, id_parent)", "($id, " . _q($titre_message) . ", " . _q($texte) . ", NOW(), " . _q(_request('nom_site')) . ", " . _q(_request('url_site')) . ", " . _q($statut) . ", " . $GLOBALS['auteur_session']['id_auteur'] . ", " . _q($GLOBALS['auteur_session']['nom']) . ", " . _q($GLOBALS['auteur_session']['email']) . ", $id_parent)");

		calculer_threads();

		if ($objet == 'id_message') {
			spip_query("UPDATE spip_auteurs_messages SET vu = 'non' WHERE id_message=$id");

		}
		redirige_par_entete(urldecode(_request('redirect'))."#id".$id_forum);
		
	 } else {
	   // previsualisation : on ne fait que passer .... 
	   // et si les clients HTTP respectaient le RFC HTTP selon lequel
	   // une redirection d'un POST doit etre en POST et pas en GET
	   // on n'aurait pas a faire l'horreur ci-dessous.
		  
	   set_request('exec', 'forum_envoi');
	   set_request('id', $id);
	   set_request('id_parent', $id_parent);
	   set_request('statut', $statut);
	   set_request('script', $script);

	   include('ecrire/index.php');
	     }
	 exit;
}
?>
