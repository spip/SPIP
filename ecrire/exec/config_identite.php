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

function exec_config_identite(){
	if (!autoriser('configurer','identite',0)) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
	$commencer_page = charger_fonction('commencer_page','inc');
	echo $commencer_page(_T('titre_identite_site'));

	echo debut_gauche("configurer_identite",true);

	//
	// Le logo de notre site, c'est site{on,off}0.{gif,png,jpg}
	//
	$iconifier = charger_fonction('iconifier', 'inc');
	echo $iconifier('id_syndic', 0, 'configuration');

	echo debut_droite("configurer_identite",true);


	echo recuperer_fond('prive/configurer/identite',$_GET);
	echo fin_gauche(),fin_page();
}

?>