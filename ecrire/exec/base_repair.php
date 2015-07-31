<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
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

/**
 * Reparer la base de donnees
 */
function exec_base_repair_dist()
{
	$ok = false;
	if (!spip_connect())
		$message =  _T('titre_probleme_technique');
	else {
		$version_sql = sql_version();
		if (!$version_sql)
			$message = _T('avis_erreur_connexion_mysql');
		else {
			$message = _T('texte_requetes_echouent');
			$ok = true;
		}
		$action = _T('texte_tenter_reparation');
	}
	if ($ok) {
		$admin = charger_fonction('admin', 'inc');
		echo $admin('repair', $action, $message, true);
	} else {
		include_spip('inc/minipres');
		echo minipres(_T('titre_reparation'), "<p>$message</p>");
	}
}
?>
