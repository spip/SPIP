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

function exec_config_langage(){
	
	$commencer_page = charger_fonction('commencer_page','inc');
	echo $commencer_page(_T('titre_config_langage'));
	
	echo barre_onglets('infos_perso', 'config_langage');

	echo debut_gauche("configurer_langage",true);
	echo debut_droite("configurer_langage",true);

	echo recuperer_fond('prive/configurer/langage',$_GET);
	echo fin_gauche(),fin_page();
}

?>