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
include_spip('inc/texte');
include_spip('inc/plugin'); // pour charge_instal_plugin

// http://doc.spip.org/@ligne_plug
function plugins_afficher_plugin_dist($url_page, $plug_file, $actif, $expose=false, $class_li="item", $dir_plugins=_DIR_PLUGINS) {

	static $id_input = 0;
	static $versions = array();

	$force_reload = (_request('var_mode')=='recalcul');
	$get_infos = charger_fonction('get_infos','plugins');
	$info = $get_infos($plug_file, $force_reload, $dir_plugins);
	$prefix = $info['prefix'];
	$erreur = (!isset($info['erreur']) ? ''
	: ("<div class='erreur'>" . join('<br >', $info['erreur']) . "</div>"));

	$cfg = !$actif ? '' : plugin_bouton_config($plug_file,$info,$dir_plugins);

	// numerotons les occurrences d'un meme prefix
	$versions[$prefix] = $id = isset($versions[$prefix]) ? $versions[$prefix] + 1 : '';

	$class_li .= ($actif?" actif":"") . ($expose?" on":"") . (isset($info['erreur']) ? " erreur" : '');

	return "<li id='$prefix$id' class='$class_li'>"
	. (($erreur OR $dir_plugins===_DIR_EXTENSIONS)
	   ? '': plugin_checkbox(++$id_input, $plug_file, $actif))
	.  plugin_resume($info, $dir_plugins, $plug_file, $url_page)
	. $cfg
	. $erreur
	. (($dir_plugins!==_DIR_EXTENSIONS AND plugin_est_installe($plug_file))
	    ? plugin_desintalle($plug_file) : '')
	. "<div class='details'>" // pour l'ajax de exec/info_plugin
	. (!$expose ? '' : affiche_bloc_plugin($plug_file, $info))
	. "</div>"
	."</li>";
}

function plugin_bouton_config($nom, $infos, $dir)
{
	// la verification se base sur le filesystem
	// il faut donc n'utiliser que des minuscules, par convention
	$prefix = strtolower($infos['prefix']);
	// si plugin.xml fournit un squelette, le prendre
	if ($infos['config'])
		return recuperer_fond("$dir$nom/" . $infos['config'],
				array('script' => 'configurer_' . $prefix,
					'nom' => $nom));

	// si le plugin CFG est la, l'essayer
	if  (defined('_DIR_PLUGIN_CFG')) {
		if (include_spip('inc/cfg')) // test CFG version >= 1.0.5
			if ($cfg = icone_lien_cfg("$dir$nom", "cfg"))
				return "<div class='cfg_link'>$cfg</div>";
	}

	// sinon prendre le squelette std sur le nom std
	return recuperer_fond("prive/cfg",
			array('script' => 'configurer_' . $prefix,
				'nom' => $nom));
}

// checkbox pour activer ou desactiver
// si ce n'est pas une extension

function plugin_checkbox($id_input, $file, $actif)
{
	$name = substr(md5($file),0,16);

	return "<div class='check'>\n"
	. "<input type='checkbox' name='s$name' id='label_$id_input'"
	. ($actif?" checked='checked'":"")
	. " class='checkbox'  value='O' />"
	. "\n<label for='label_$id_input'>"._T('activer_plugin')."</label>"
	. "</div>";
}

// Cartouche Resume

function plugin_resume($info, $dir_plugins, $plug_file, $url_page)
{
	$desc = plugin_propre($info['description']);
	$dir = $dir_plugins.$plug_file;
	if (($p=strpos($desc, "<br />"))!==FALSE)
		$desc = substr($desc, 0,$p);
	$url = parametre_url($url_page, "plugin", $dir);

	if (isset($info['icon']) and $i = trim($info['icon'])) {
		include_spip("inc/filtres_images_mini");
		$i = inserer_attribut(image_reduire("$dir/$i", 32),'alt','');
		$i = "<div class='icon'><a href='$url' rel='info'>$i</a></div>";
	} else $i = '';

	return "<div class='resume'>"
	. "<h3 class='nom'><a href='$url' rel='info'>"
	. typo($info['nom'])
	. "</a></h3>"
	. " <span class='version'>".$info['version']."</span>"
	. " <span class='etat'> - "
	. plugin_etat_en_clair($info['etat'])
	. "</span>"
	. "<div class='short'>".couper($desc,70)."</div>"
	. $i
	. "</div>";

}

function plugin_desintalle($plug_file){

	$action = redirige_action_auteur('desinstaller_plugin',$plug_file,'admin_plugin');
	$text = _T('bouton_desinstaller');
	$text2 = _T('info_desinstaller_plugin');
	$file = basename($plug_file);

	return "<div class='actions'>[".
		"<a href='$action'
		onclick='return confirm(\"$text $file ?\\n$text2\")'>"
		. $text
		. "</a>]</div>"	;
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
		$s .= "<div class='desc'>".plugin_propre($info['description']) . "</div>\n";

	if (isset($info['auteur']) AND trim($info['auteur']))
		$s .= "<div class='auteurs'>" . _T('public:par_auteur') .' '. plugin_propre($info['auteur']) . "</div>\n";
	if (isset($info['licence']))
		$s .= "<div class='licence'> - " . _T('intitule_licence') .' '. plugin_propre($info['licence']) . "</div>\n";

	if (trim($info['lien'])) {
		$lien = $info['lien'];
		if (!preg_match(',^https?://,iS', $lien))
			$lien = extraire_attribut(extraire_balise(propre($lien),'a'),'href');
		$s .= "<div class='site'><a href='$lien' class='spip_out'>" . _T('en_savoir_plus') .'</a></div>';
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
