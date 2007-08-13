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

function base_maj016_dist($version_installee, $version_cible)
{

	if (upgrade_vers(1.600, $version_installee, $version_cible)) {
#8/08/07  plus d'indexation dans le core
#		include_spip('inc/indexation');
#		purger_index();
#		creer_liste_indexation();
		maj_version (1.600);
	}

	if (upgrade_vers(1.601, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_forum ADD INDEX id_syndic (id_syndic)");
		maj_version (1.601);
	}

	if (upgrade_vers(1.603, $version_installee, $version_cible)) {
		// supprimer les fichiers deplaces
		spip_unlink('inc_meta_cache.php');
		spip_unlink('inc_meta_cache.php3');
		spip_unlink('data/engines-list.ini');
		maj_version (1.603);
	}

	if (upgrade_vers(1.604, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		$u = spip_query("SELECT * FROM spip_auteurs WHERE prefs LIKE '%spip_lang%'");
		while ($row = sql_fetch($u)) {
			$prefs = unserialize($row['prefs']);
			$l = $prefs['spip_lang'];
			unset ($prefs['spip_lang']);
			spip_query("UPDATE spip_auteurs SET lang=" . _q($l) . ", prefs='".addslashes(serialize($prefs))."' WHERE id_auteur=".$row['id_auteur']);
		}
		$u = spip_query("SELECT lang FROM spip_auteurs");
		maj_version (1.604, $u);
	}
}


?>
