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

// Mise a jour de l'option de configuration $arg
// il faudrait limiter a arg plutot que d'executer tout modif_config
// on traite a part seulement le cas du proxy car c'est indispensable
// (message d'erreur eventuel a afficher)

include_spip('inc/config');

// http://doc.spip.org/@action_configurer_dist
function action_configurer_dist() {
	
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();
	$r = rawurldecode(_request('redirect'));
	$r = parametre_url($r, 'configuration', $arg,"&");
	appliquer_modifs_config();

	// Cette globale est fixee par appliquer_modifs_config();
	// c'est un message a afficher dans l'exec de retour (relayeur si comme ici on est en ajax)
	if ($arg == 'relayeur')
		$r = parametre_url($r, 'retour_proxy', $GLOBALS['retour_proxy'],"&");
	else if ($arg == 'langue') {
	  	include_spip('inc/rubriques');
		calculer_langues_rubriques();
	}
	redirige_par_entete($r);
}

?>