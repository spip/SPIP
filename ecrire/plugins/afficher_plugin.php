<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('inc/charsets');

// http://doc.spip.org/@ligne_plug
function plugins_afficher_plugin_dist($plug_file, $actif,$class_li="item"){
	global $spip_lang_right;
	static $id_input=0;
	static $versions = array();

	$erreur = false;
	$s = "";

	$get_infos = charger_fonction('get_infos','plugins');
	$info = $get_infos($plug_file);

	// numerotons les occurences d'un meme prefix
	$versions[$info['prefix']] = isset($versions[$info['prefix']]) ? $versions[$info['prefix']] + 1 : '';
	$id = $info['prefix'] . $versions[$info['prefix']];
	
	$class = $class_li;
	$class .= $actif?"on":"";
	if (isset($info['erreur']))
		$class .= " erreur";
	$s .= "<li id='$id' class='$class'>";
	
	// plug pour CFG
	if ($actif
	AND defined('_DIR_PLUGIN_CFG')) {
		if (include_spip('inc/cfg') // test CFG version >= 1.0.5
		AND $i = icone_lien_cfg(_DIR_PLUGINS.$plug_file))
			$s .= '<div class="cfg_link">'.$i.'</div>';
	}

	// nom

	$s .= "<div class='nomplugin ".($actif?'nomplugin_on':'')."'>";
	if (isset($info['erreur'])){
		$s .=  "<div class='plugin_erreur'>";
		$erreur = true;
		foreach($info['erreur'] as $err)
			$s .= "/!\ $err <br/>";
		$s .=  "</div>";
	}

	$etat = 'dev';
	if (isset($info['etat']))
		$etat = $info['etat'];
	$nom = typo($info['nom']);

	$id = "aide_$id";
	// si $actif vaut -1, c'est actif, et ce n'est pas desactivable (extension)
	if (!$erreur
	AND $actif>=0
	){
		$name = 's' . substr(md5("statusplug_$plug_file"),0,16);
		$s .= "\n<input type='checkbox' name='$name' id='label_$id_input' value='O'";
		$s .= $actif?" checked='checked'":"";
		$s .= " class='check' />";
		$s .= "\n<label for='label_$id_input'>"._T('activer_plugin')."</label>";
	}
	$id_input++;

	$url_stat = generer_url_ecrire(_request('exec'),"plugin=".urlencode($plug_file));
	$s .= "<a href='$url_stat' rel='info'>$nom</a>";

	// afficher les details d'un plug en secours ; la div sert pour l'ajax
	$s .= "<div class='info'>";
	if (urldecode(_request('plugin'))==$plug_file OR urldecode(_request('plugin'))==substr(_DIR_PLUGINS,strlen(_DIR_RACINE)) . $plug_file)
		$s .= affiche_bloc_plugin($plug_file, $info);
	$s .= "</div>";

	$s .= "</div>";
	$s .= "</li>";
	return $s;
}


?>