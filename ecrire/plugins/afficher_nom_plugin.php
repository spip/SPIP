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
include_spip('inc/charsets');
include_spip('inc/texte');
include_spip('plugins/afficher_plugin');

// http://doc.spip.org/@ligne_plug
function plugins_afficher_nom_plugin_dist($url_page, $plug_file, $checked, $actif, $expose=false, $class_li="item", $dir_plugins=_DIR_PLUGINS){
	static $id_input=0;
	static $versions = array();

	$erreur = false;
	$s = "";

	$get_infos = charger_fonction('get_infos','plugins');
	$info = $get_infos($plug_file, false, $dir_plugins);

	// numerotons les occurences d'un meme prefix
	$versions[$info['prefix']] = isset($versions[$info['prefix']]) ? $versions[$info['prefix']] + 1 : '';
	$id = $info['prefix'] . $versions[$info['prefix']];
	
	$class = $class_li;
	$class .= $actif?" actif":"";
	$class .= $expose?" on":"";
	$erreur = isset($info['erreur']);
	if ($erreur)
		$class .= " error";
	$s .= "<li id='$id' class='$class'>";

	// Cartouche Resume
	$s .= "<div class='resume'>";

	$prefix = $info['prefix'];
	$dir = "$dir_plugins$plug_file/lang/$prefix";
	$desc = plugin_propre($info['description'], $dir);
	$url_stat = parametre_url($url_page, "plugin",$dir_plugins.$plug_file);

	$s .= "<strong class='nom'>".typo($info['nom'])."</strong>";
	$s .= " <span class='version'>".$info['version']."</span>";
	$s .= " <span class='etat'> - ".plugin_etat_en_clair($info['etat'])."</span>";
	$s .= "</div>";

	if ($erreur){
		$s .= "<div class='erreur'>" . join('<br >', $info['erreur']) . "</div>";
	}

	$s .= "</li>";
	return $s;
}

?>
