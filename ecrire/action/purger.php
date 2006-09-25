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

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser

// http://doc.spip.org/@action_purger_dist
function action_purger_dist()
{
	include_spip('inc/actions');
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');

	include_spip('inc/invalideur');

  switch ($arg) {

  case 'index': 
	include_spip('inc/indexation');
	spip_log("purger_indx");
	purger_index();
	creer_liste_indexation();
	break;

  case 'cache': 
      supprime_invalideurs();
      purger_repertoire(_DIR_CACHE, 0);
      break;

  case 'squelettes':
	purger_repertoire(_DIR_SKELS);
	break;

  case 'vignettes':
	purger_repertoire(_DIR_IMG, $age='ignore', $regexp = '^cache\-');
	spip_log('vider le cache');
	supprime_invalideurs();
	purger_repertoire(_DIR_CACHE, 0);
	break;

  case 'taille_vignettes':

  	global $lang;
	$handle = @opendir(_DIR_IMG);
	if (!$handle) return;

	$taille = 0;
	while (($fichier = @readdir($handle)) !== false) {
		// Eviter ".", "..", ".htaccess", etc.
		if ($fichier[0] == '.') continue;
		if ($regexp AND !ereg($regexp, $fichier)) continue;
		if (is_dir(_DIR_IMG.$fichier) AND ereg("^cache-", $fichier)) {
			$taille += calculer_taille_dossier(_DIR_IMG.$fichier);
		}
	}
	closedir($handle);
	
	include_spip('inc/filtres');
	include_spip('inc/lang');
	lang_select($lang);
	echo "<html><body>\n";
	echo "<div style='font-family: verdana, arial, sans; font-size: 12px;'>";
	echo "<p align='justify'>\n";
	echo _T('ecrire:taille_cache_image', array('dir' => _DIR_IMG,
		'taille' => "<b>".taille_en_octets($taille)."</b>"));
	echo "</p></div></body></html>";
	break;
  }
}
?>
