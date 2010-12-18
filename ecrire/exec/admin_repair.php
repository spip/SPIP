<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

/*
 * REMARQUE IMPORTANTE : SECURITE
 * Ce systeme de reparation doit pouvoir fonctionner meme si
 * la table spip_auteurs est en panne : index.php n'appelle donc pas
 * inc_auth ; seule l'authentification ftp est exigee
 *
 */


// http://doc.spip.org/@exec_admin_repair_dist
function exec_admin_repair_dist()
{
	$ok = false;
	if (!spip_connect())
		$message =  _T('titre_probleme_technique');
	else {
		$version_sql = sql_version();
		if (!$version_sql)
			$message = _T('avis_erreur_connexion_mysql');
		else {
			$s = $GLOBALS['connexions'][0]['type'];
		  
			if ($s == 'mysql'
			AND version_compare($version_sql,'3.23.14','<'))
			  $message = _T('avis_version_mysql', array('version_mysql' => " MySQL $version_sql"));
			else {
				$message = _T('texte_requetes_echouent');
				$ok = true;
			}
		}
		$action = _T('texte_tenter_reparation');
	}
	if ($ok) {
		$admin = charger_fonction('admin', 'inc');
		echo $admin('admin_repair', $action, $message, true);
	} else {
		include_spip('inc/minipres');
		echo minipres(_T('titre_reparation'), "<p>$message</p>");
	}
}
?>
