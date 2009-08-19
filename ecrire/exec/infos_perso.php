<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function exec_infos_perso_dist(){
	$auteur = sql_fetsel("*", "spip_auteurs", "id_auteur=".intval($GLOBALS['visiteur_session']['id_auteur']));
	if (!$auteur) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

		pipeline('exec_init',
			array('args' => array(
				'exec'=> 'auteur_infos',
				'id_auteur'=>$auteur['id_auteur']),
				'data'=>''
			      )
			 );

		$commencer_page = charger_fonction('commencer_page','inc');
		echo $commencer_page(_T('info_informations_personnelles'));

		echo barre_onglets('infos_perso', 'infos_perso');
		echo debut_gauche('', true);

		charger_fonction('auteur_infos','exec');

		auteur_infos_ok($auteur, $auteur['id_auteur'], _request('echec'), '', self());
		echo auteurs_interventions($auteur);
		echo fin_gauche(),fin_page();
	}
}

?>