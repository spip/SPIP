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

// http://doc.spip.org/@exec_forum_envoi_dist
function exec_forum_envoi_dist()
{
	forum_envoi(  
		    intval(_request('id')),
		    intval(_request('id_parent')),
		    _request('script'),
		    _request('statut'),
		    _request('titre_message'),
		    _request('texte'),
		    _request('modif_forum'),
		    _request('nom_site'),
		    _request('url_site'));
}

// http://doc.spip.org/@forum_envoi
function forum_envoi(
		     $id,
		     $id_parent,
		     $script,
		     $statut,
		     $titre_message,
		     $texte,
		     $modif_forum,
		     $nom_site,
		     $url_site)
{
	$titre = $script == 'message' ? _T('onglet_messagerie') : _T('titre_cadre_forum_interne');
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('texte_nouveau_message'), "accueil", "accueil");
	echo debut_gauche('', true);
	echo debut_droite('', true);
	echo gros_titre($titre,'', false);

	$forum_envoi = charger_fonction('forum_envoi', 'inc');
	echo $forum_envoi($id, $id_parent, $script, $statut, $titre_message, $texte, $modif_forum, $nom_site, $url_site),
	  fin_gauche(), fin_page();
}

?>
