<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

function action_quete_autocomplete_dist(){
	$securiser_action = charger_fonction('securiser_action','inc');
	$arg = $securiser_action();
	if ($arg
	  AND $arg==$GLOBALS['visiteur_session']['id_auteur']){
		include_spip('inc/actions');
		echo ajax_retour(
			recuperer_fond('prive/squelettes/inclure/organiseur-autocomplete-auteur',array('q'=>_request('q'))),
			'text/plain'
		);
	}
}