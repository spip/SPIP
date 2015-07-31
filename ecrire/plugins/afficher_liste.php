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
include_spip('inc/charsets');

// http://doc.spip.org/@affiche_liste_plugins
function plugins_afficher_liste_dist($url_page,$liste_plugins, $liste_plugins_actifs, $dir_plugins=_DIR_PLUGINS,$afficher_un = 'afficher_plugin'){
	$get_infos = charger_fonction('get_infos','plugins');
	$ligne_plug = charger_fonction($afficher_un,'plugins');
	$liste_plugins = array_flip($liste_plugins);
	foreach(array_keys($liste_plugins) as $chemin) {
		if ($info = $get_infos($chemin, false, $dir_plugins))
			$liste_plugins[$chemin] = strtoupper(trim(typo(translitteration(unicode2charset(html2unicode($info['nom']))))));
	}
	asort($liste_plugins);
	$exposed = urldecode(_request('plugin'));

	$block_par_lettre = false;//count($liste_plugins)>10;
	$fast_liste_plugins_actifs = array_flip($liste_plugins_actifs);
	$res = '';
	$block = '';
	$initiale = '';
	$block_actif = false;
	foreach($liste_plugins as $plug => $nom){
		if (($i=substr($nom,0,1))!==$initiale){
			$res .= $block_par_lettre ? affiche_block_initiale($initiale,$block,$block_actif): $block;
			$initiale = $i;
			$block = '';
			$block_actif = false;
		}
		// le rep suivant
		$actif = @isset($fast_liste_plugins_actifs[$plug]);
		$block_actif = $block_actif | $actif;
		$expose = ($exposed AND ($exposed==$plug OR $exposed==$dir_plugins . $plug OR $exposed==substr($dir_plugins,strlen(_DIR_RACINE)) . $plug));
		$block .= $ligne_plug($url_page, $plug, $actif, $expose, "item", $dir_plugins)."\n";
	}
	$res .= $block_par_lettre ? affiche_block_initiale($initiale,$block,$block_actif): $block;
	$class = basename($dir_plugins);
	return $res ? "<ul class='liste-items plugins $class'>$res</ul>" : "";
}


// http://doc.spip.org/@affiche_block_initiale
function affiche_block_initiale($initiale,$block,$block_actif){
	if (strlen($block)){
		return "<li class='item'>"
		  . bouton_block_depliable($initiale,$block_actif?true:false)
		  . debut_block_depliable($block_actif)
		  . "<ul>$block</ul>"
		  . fin_block()
		  . "</li>";
	}
	return "";
}

?>
