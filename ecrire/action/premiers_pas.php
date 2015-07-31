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

include_spip('inc/meta');

// http://doc.spip.org/@action_premiers_pas_dist
function action_premiers_pas_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$etape = $securiser_action();
	
	if (_request('cancel')!==NULL)
		effacer_meta('nouvelle_install');
	else {
		if ($f = charger_fonction("premiers_pas_pas_$etape","action",true))
			$f();
		
		if ($etape = intval($etape)){
			$etape++;
			if ($f = charger_fonction("pas_$etape","premiers_pas",true))
				ecrire_meta('nouvelle_install',$etape);
			else
				ecrire_meta('nouvelle_install','fin');
		}
		else if ($etape=='fin')
			effacer_meta('nouvelle_install');
	}
	
	ecrire_metas();
	include_spip('inc/headers');
	redirige_par_entete(generer_url_ecrire('accueil', '', true));
}

// http://doc.spip.org/@action_premiers_pas_pas_1_dist
function action_premiers_pas_pas_1_dist(){
	include_spip('inc/config');
	appliquer_modifs_config();
}

?>