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

/*
 * REMARQUE IMPORTANTE : SECURITE
 * Ce systeme de reparation doit pouvoir fonctionner meme si
 * la table spip_auteurs est en panne : index.php n'appelle donc pas
 * inc_auth ; seule l'authentification ftp est exigee
 *
 */

include_spip('base/db_mysql');

// http://doc.spip.org/@exec_admin_repair_dist
function exec_admin_repair_dist()
{
	$ok = false;
	$version_mysql = spip_mysql_version();
	if (!$version_mysql)
	  $message = _T('avis_erreur_connexion_mysql');
	else {
	  if (version_compare($version_mysql,'3.23.14','<'))
	    $message = _T('avis_version_mysql', array('version_mysql' => $version_mysql));
	  else {
	    $message = _T('texte_requetes_echouent');
	    $ok = true;
	  }
	}

	$action = _T('texte_tenter_reparation');

	if ($ok) {
		$admin = charger_fonction('admin', 'inc');
		$admin('admin_repair', $action, $message);
	}
	else {
		include_spip('inc/minipres');
		echo minipres(_T('titre_reparation'), "<p>$message</p>");
		exit;
	}
}
?>
