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

include_spip('inc/headers');
include_spip('inc/acces');

// Mise en place des fichiers de configuration si ce n'est fait

// http://doc.spip.org/@install_etape_fin_dist
function install_etape_fin_dist()
{
	ecrire_acces();

	$f = str_replace( _FILE_TMP_SUFFIX, '.php', _FILE_CHMOD_TMP);
	if (file_exists(_FILE_CHMOD_TMP)) {
		if (!@rename(_FILE_CHMOD_TMP, $f)) {
			if (@copy(_FILE_CHMOD_TMP, $f))
				spip_unlink(_FILE_CHMOD_TMP);
		}
	}

	$f = str_replace( _FILE_TMP_SUFFIX, '.php', _FILE_CONNECT_TMP);
	if (file_exists(_FILE_CONNECT_TMP)) {
		spip_log("renomme $f");
		if (!@rename(_FILE_CONNECT_TMP, $f)) {
			if (@copy(_FILE_CONNECT_TMP, $f))
				@spip_unlink(_FILE_CONNECT_TMP);
		}
	}

	// on l'envoie dans l'espace prive

	redirige_url_ecrire('accueil');	
}
?>
