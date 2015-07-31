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
include_spip('inc/texte');

// http://doc.spip.org/@ligne_plug
function plugins_afficher_plugin_dist($url_page, $plug_file, $actif, $expose=false, $class_li="item", $dir_plugins=_DIR_PLUGINS){
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
	$class .= $actif?" actif":"";
	$class .= $expose?" on":"";
	$erreur = isset($info['erreur']);
	if ($erreur)
		$class .= " erreur";
	$s .= "<li id='$id' class='$class'>";


	// checkbox pour activer ou desactiver
	// si $actif vaut -1, c'est actif, et ce n'est pas desactivable (extension)
	if (!$erreur AND $actif>=0){
		$name = 's' . substr(md5("statusplug_$plug_file"),0,16);
		$id_input++;
		$check = "\n<input type='checkbox' name='$name' id='label_$id_input' value='O'";
		$check .= $actif?" checked='checked'":"";
		$check .= " class='checkbox' />";
		$check .= "\n<label for='label_$id_input'>"._T('activer_plugin')."</label>";
		$s .= "<div class='check'>$check</div>";
	}

	// Cartouche Resume
	$s .= "<div class='resume'>";

	$desc = plugin_propre($info['description']);
	$url_stat = parametre_url($url_page, "plugin",$plug_file);

	$s .= "<h3 class='nom'><a href='$url_stat' rel='info'>".typo($info['nom'])."</a></h3>";
	$s .= " <span class='version'>".$info['version']."</span>";
	$s .= " <span class='etat'> - ".plugin_etat_en_clair($info['etat'])."</span>";
	$s .= "<div class='short'>".couper($desc,60)."</div>";
	if (isset($info['icon'])) {
		include_spip("inc/filtres_images_mini");
		$s.= "<div class='icon'>".image_reduire($dir_plugins.$plug_file.'/'.trim($info['icon']), 32)."</div>";
	}
	$s .= "</div>";

	// plug pour CFG
	if ($actif
	AND defined('_DIR_PLUGIN_CFG')) {
		if (include_spip('inc/cfg') // test CFG version >= 1.0.5
		AND $i = icone_lien_cfg($dir_plugins.$plug_file))
			$s .= '<div class="cfg_link">'.$i.'</div>';
	}

	if ($erreur){
		$s .=  "<div class='erreur'>";
		foreach($info['erreur'] as $err)
			$s .= "$err <br/>";
		$s .=  "</div>";
	}

	// bouton de desinstallation
	if (plugin_est_installe($plug_file)){
		$action = redirige_action_auteur('desinstaller_plugin',$plug_file,'admin_plugin');
		$s .= "<div class='actions'>[".
		"<a href='$action'
		onclick='return confirm(\""._T('bouton_desinstaller')
		." ".basename($plug_file)." ?\\n"._T('info_desinstaller_plugin')."\")'>"
		._T('bouton_desinstaller')
		."</a>]</div>"
		;
	}

	// afficher les details d'un plug en secours ; la div sert pour l'ajax
	$s .= "<div class='details'>";
	if ($expose)
		$s .= affiche_bloc_plugin($plug_file, $info);
	$s .= "</div>";

	$s .= "</li>";
	return $s;
}


function plugin_etat_en_clair($etat){
	if (!in_array($etat,array('stable','test','experimental')))
		$etat = 'developpement';
	return _T('plugin_etat_'.$etat);
}

// http://doc.spip.org/@plugin_propre
function plugin_propre($texte) {
	$mem = $GLOBALS['toujours_paragrapher'];
	$GLOBALS['toujours_paragrapher'] = false;
	$regexp = "|\[:([^>]*):\]|";
	if (preg_match_all($regexp, $texte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs)
		$texte = str_replace($regs[0],
		_T('spip/ecrire/public:'.$regs[1]), $texte);
	$texte = propre($texte);
	$GLOBALS['toujours_paragrapher'] = $mem;
	return $texte;
}



// http://doc.spip.org/@affiche_bloc_plugin
function affiche_bloc_plugin($plug_file, $info, $dir_plugins=null) {
	if (!$dir_plugins)
		$dir_plugins = _DIR_PLUGINS;

	$s = "";
	// TODO: le traiter_multi ici n'est pas beau
	// cf. description du plugin/_stable_/ortho/plugin.xml
	if (isset($info['description']))
		$s .= "<div class='desc'>".plugin_propre($info['description']) . "</div>";

	if (isset($info['auteur']) AND trim($info['auteur']))
		$s .= "<div class='auteurs'>" . _T('public:par_auteur') .' '. plugin_propre($info['auteur']) . "</div>";
	if (isset($info['licence']))
		$s .= "<div class='licence'>" . _T('intitule_licence') .' '. plugin_propre($info['licence']) . "</div>";

	if (trim($info['lien'])) {
		$lien = $info['lien'];
		if (preg_match(',^https?://,iS', $lien))
			$lien = "[->$lien]";
		$s .= "<div class='site'>" . _T('en_savoir_plus') .' '. plugin_propre($lien) . "</div>";
	}

	//
	// Ajouter les infos techniques
	//
	$infotech = array();

	$version = _T('version') .' '.  $info['version'];
	// Version SVN
	if ($svn_revision = version_svn_courante($dir_plugins.$plug_file))
		$version .= ($svn_revision<0 ? ' SVN':'').' ['.abs($svn_revision).']';
	$infotech[] = $version;

	// source zip le cas echeant
	$source = (lire_fichier($dir_plugins.$plug_file.'/install.log', $log)
	AND preg_match(',^source:(.*)$,m', $log, $r))
		? '<br />'._T('plugin_source').' '.trim($r[1])
		:'';

	$s .= "<div class='tech'>"
		. join(' &mdash; ', $infotech) .
		 '<br />' . _T('repertoire_plugins') .' '. $plug_file
		. $source
		."</div>";


	return $s;
}
?>