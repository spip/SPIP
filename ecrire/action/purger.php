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

function action_purger_dist()
{
  global $action, $arg, $hash, $id_auteur;
  include_spip('inc/session');
  if (!verifier_action_auteur("$action $arg", $hash, $id_auteur)) {
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
  }
  include_spip('inc/invalideur');

  switch ($arg) {

  case 'cache': 
      supprime_invalideurs();
      purger_repertoire(_DIR_CACHE, 0);
      break;

  case 'squelettes':
	purger_repertoire(_DIR_CACHE, 0, '^skel_');
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
