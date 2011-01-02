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

// reaffichage du formulaire d'une option de configuration 
// apres sa modification par appel du script action/configurer 
// redirigeant ici.

// http://doc.spip.org/@exec_configurer_dist
function exec_configurer_dist()
{
	if(!autoriser('configurer', _request('configuration'))) {
		include_spip('inc/minipres');
		echo minipres(_T('info_acces_interdit'));
		exit;
	}
	include_spip('inc/actions');
	$configuration = charger_fonction(_request('configuration'), 'configuration', true);
	ajax_retour($configuration ? $configuration() : 'configure quoi?');
}
?>
