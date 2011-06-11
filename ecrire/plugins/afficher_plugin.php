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
include_spip('inc/plugin'); // pour plugin_est_installe

// http://doc.spip.org/@ligne_plug
function plugins_afficher_plugin_dist($url_page, $plug_file, $actif, $expose=false, $class_li="item", $dir_plugins=_DIR_PLUGINS) {

	static $id_input = 0;
	static $versions = array();

	$force_reload = (_request('var_mode')=='recalcul');
	$get_infos = charger_fonction('get_infos','plugins');
	$info = $get_infos($plug_file, $force_reload, $dir_plugins);
	$prefix = $info['prefix'];
	$cfg = "";

	if (!plugin_version_compatible($info['compatible'], $GLOBALS['spip_version_branche'])){
		$erreur = http_img_pack("plugin-dis-32.png",_T('plugin_info_non_compatible_spip')," class='picto_err'",_T('plugin_info_non_compatible_spip'));
		$class_li .= " disabled";
	}
	elseif (isset($info['erreur'])) {
		$class_li .= " error";
		$erreur = http_img_pack("plugin-err-32.png",_T('plugin_info_erreur_xml')," class='picto_err'",_T('plugin_info_erreur_xml'))
		  . "<div class='erreur'>" . join('<br >', $info['erreur']) . "</div>";
	}
	else
		$cfg = $actif ? plugin_bouton_config($plug_file,$info,$dir_plugins) : "";

	// numerotons les occurrences d'un meme prefix
	$versions[$prefix] = $id = isset($versions[$prefix]) ? $versions[$prefix] + 1 : '';

	$class_li .= ($actif?" actif":"") . ($expose?" on":"");
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
	return recuperer_fond("prive/squelettes/inclure/cfg",
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
function plugin_resume($info, $dir_plugins, $plug_file, $url_page){
	$prefix = $info['prefix'];
	$dir = "$dir_plugins$plug_file";
	$slogan = plugin_propre($info['slogan'], "$dir/lang/$prefix");
	// une seule ligne dans le slogan : couper si besoin
	if (($p=strpos($slogan, "<br />"))!==FALSE)
		$slogan = substr($slogan, 0,$p);
	// couper par securite
	$slogan = couper($slogan, 80);

	$url = parametre_url($url_page, "plugin", $dir);

	if (isset($info['icon']) and $i = trim($info['icon'])) {
		include_spip("inc/filtres_images_mini");
		$i = inserer_attribut(image_reduire("$dir/$i", 32),'alt','');
		$i = "<div class='icon'><a href='$url' rel='info'>$i</a></div>";
	} else $i = '';

	return "<div class='resume'>"
	. "<h3><a href='$url' rel='info'>"
	. typo($info['nom'])
	. "</a></h3>"
	. " <span class='version'>".$info['version']."</span>"
	. " <span class='etat'> - "
	. plugin_etat_en_clair($info['etat'])
	. "</span>"
	. "<div class='short'>".$slogan."</div>"
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
function plugin_propre($texte, $module='') {
	$mem = $GLOBALS['toujours_paragrapher'];
	$GLOBALS['toujours_paragrapher'] = false;
	if (preg_match("|^\w+_[\w_]+$|", $texte)) {
		$texte = _T(($module ? "$module:" : '') . $texte);
	}
	$texte = propre($texte);
	$GLOBALS['toujours_paragrapher'] = $mem;
	return $texte;
}



// http://doc.spip.org/@affiche_bloc_plugin
function affiche_bloc_plugin($plug_file, $info, $dir_plugins=null) {
	if (!$dir_plugins)
		$dir_plugins = _DIR_PLUGINS;

	$prefix = $info['prefix'];
	$dir = "$dir_plugins$plug_file/lang/$prefix";

	$s = "";
	// TODO: le traiter_multi ici n'est pas beau
	// cf. description du plugin/_stable_/ortho/plugin.xml
	if (isset($info['description'])) {
		$lien = "";
		if (trim($info['lien'])) {
			$lien = $info['lien'];
			if (!preg_match(',^https?://,iS', $lien))
				$lien = extraire_attribut(extraire_balise(propre($lien),'a'),'href');
			$lien = "\n_ <em class='site'><a href='$lien' class='spip_out'>" . _T('en_savoir_plus') .'</a></em>';
		}
		$s .= "<dd class='desc'>".plugin_propre($info['description'] . $lien, $dir);
		$s .= "</dd>\n";
	}

	if (isset($info['auteur']) AND trim($info['auteur']))
	  $s .= "<dt class='auteurs'>" . _T('public:par_auteur') ."</dt><dd class='auteurs'>". plugin_propre($info['auteur'], $dir) . "</dd>\n";
	if (isset($info['licence']))
	  $s .= "<dt class='licence'>" . _T('intitule_licence') ."</dt><dd class='licence'>". plugin_propre($info['licence'], $dir) . "</dd>\n";
	$s = "<dl>$s</dl>";

	//
	// Ajouter les infos techniques
	//
	$infotech = array();

	$version = "<dt>"._T('version')."</dt><dd>".$info['version'];
	// Version SVN
	if ($svn_revision = version_svn_courante($dir_plugins.$plug_file))
		$version .= ($svn_revision<0 ? ' SVN':'').' ['.abs($svn_revision).']';
	$version .="</dd>";
	$infotech[] = $version;

	$infotech[] = "<dt>"._T('repertoire_plugins')."</dt><dd>".joli_repertoire($plug_file)."</dd>";
	// source zip le cas echeant
	$infotech[] = (lire_fichier($dir_plugins.$plug_file.'/install.log', $log)
	AND preg_match(',^source:(.*)$,m', $log, $r))
		? '<dt>'._T('plugin_source').'</dt><dd>'.trim($r[1])."</dd>"
		:'';

	$infotech[] = !$info['necessite'] ? '' :
	  ('<dt>' .  _T('plugin_info_necessite') . '</dt><dd>' . join(' ', array_map('array_shift', $info['necessite'])) . '</dd>');

	$s .= "<dl class='tech'>"
		. join('', $infotech)
		."</dl>";


	return $s;
}
?>
