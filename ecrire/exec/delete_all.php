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

include_spip('inc/admin');

// http://doc.spip.org/@exec_delete_all_dist
function exec_delete_all_dist()
{
$action = _T('titre_page_delete_all');

 debut_admin("delete_all", $action);

// faudrait plutot recuperer dans inc_serialbase et inc_auxbase
// mais il faudra prevenir ceux qui affectent les globales qui s'y trouvent
// Afficher la liste de ce qu'on va detruire et demander confirmation 
// ca vaudrait mieux

spip_query("DROP TABLE spip_articles");
spip_query("DROP TABLE spip_auteurs");
spip_query("DROP TABLE spip_auteurs_articles");
spip_query("DROP TABLE spip_auteurs_messages");
spip_query("DROP TABLE spip_auteurs_rubriques");
spip_query("DROP TABLE spip_breves");
spip_query("DROP TABLE spip_documents");
spip_query("DROP TABLE spip_documents_articles");
spip_query("DROP TABLE spip_documents_breves");
spip_query("DROP TABLE spip_documents_rubriques");
spip_query("DROP TABLE spip_forum");
spip_query("DROP TABLE spip_forum_cache");
spip_query("DROP TABLE spip_groupes_mots");
spip_query("DROP TABLE spip_index");
spip_query("DROP TABLE spip_index_table");
spip_query("DROP TABLE spip_index_dico");
spip_query("DROP TABLE spip_messages");
spip_query("DROP TABLE spip_meta");
spip_query("DROP TABLE spip_mots");
spip_query("DROP TABLE spip_mots_articles");
spip_query("DROP TABLE spip_mots_breves");
spip_query("DROP TABLE spip_mots_forum");
spip_query("DROP TABLE spip_mots_rubriques");
spip_query("DROP TABLE spip_mots_syndic");
spip_query("DROP TABLE spip_petitions");
spip_query("DROP TABLE spip_referers");
spip_query("DROP TABLE spip_referers_articles");
spip_query("DROP TABLE spip_rubriques");
spip_query("DROP TABLE spip_signatures");
spip_query("DROP TABLE spip_syndic");
spip_query("DROP TABLE spip_syndic_articles");
spip_query("DROP TABLE spip_types_documents");
spip_query("DROP TABLE spip_visites");
spip_query("DROP TABLE spip_visites_articles");
spip_query("DROP TABLE spip_test");
spip_query("DROP TABLE spip_caches");
spip_query("DROP TABLE spip_mots_documents");
spip_query("DROP TABLE spip_ortho_cache");
spip_query("DROP TABLE spip_ortho_dico");
spip_query("DROP TABLE spip_versions");
spip_query("DROP TABLE spip_versions_fragments");
spip_query("DROP TABLE spip_ajax_fonc");


// un pipeline pour DROP les tables installees par les plugins
pipeline('delete_tables', '');

@unlink(_ACCESS_FILE_NAME);
@unlink(_FILE_CONNECT);

@header("Location: ./");

fin_admin($action);
}

?>
