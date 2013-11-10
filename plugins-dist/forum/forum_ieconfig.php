<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

// On déclare ici la config du core
function forum_ieconfig_metas($table){
	$table['forums_contenu']['titre'] = _T('forum:titre_forum');
	$table['forums_contenu']['icone'] = 'forum-public-16.png';
	$table['forums_contenu']['metas_brutes'] = 'forums_titre,forums_texte,forums_urlref,forums_afficher_barre,formats_documents_forum';
	$table['forums_notifications']['titre'] = _T('forum:info_envoi_forum');
	$table['forums_notifications']['icone'] = 'annonce-16.png';
	$table['forums_notifications']['metas_brutes'] = 'prevenir_auteurs';
	$table['forums_participants']['titre'] = _T('forum:info_mode_fonctionnement_defaut_forum_public');
	$table['forums_participants']['icone'] = 'forum-interne-16.png';
	$table['forums_participants']['metas_brutes'] = 'forums_publics';
	$table['forums_prives']['titre'] = _T('forum:titre_config_forums_prive');
	$table['forums_prives']['icone'] = 'forum-interne-16.png';
	$table['forums_prives']['metas_brutes'] = 'forum_prive_objets,forum_prive,forum_prive_admin';
	
	return $table;
}

?>