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
include_spip('inc/plugin'); // pour plugin_est_installe

// http://doc.spip.org/@ligne_plug
function plugins_afficher_plugin_dist($url_page, $plug_file, $checked, $actif, $expose=false, $class_li="item", $dir_plugins=_DIR_PLUGINS) {

	static $id_input = 0;
	static $versions = array();

	$force_reload = (_request('var_mode')=='recalcul');
	$get_infos = charger_fonction('get_infos','plugins');
	$info = $get_infos($plug_file, $force_reload, $dir_plugins);
	$prefix = $info['prefix'];
	$cfg = "";
	$checkable = ($dir_plugins!==_DIR_PLUGINS_DIST);
	$nom = plugin_nom($info,$dir_plugins,$plug_file);

	if (!plugin_version_compatible($info['compatibilite'], $GLOBALS['spip_version_branche'],'spip')){
		$info['slogan'] = _T('plugin_info_non_compatible_spip');
		$erreur = http_img_pack("plugin-dis-32.png",_T('plugin_info_non_compatible_spip')," class='picto_err'",_T('plugin_info_non_compatible_spip'));
		$class_li .= " disabled";
		$checkable = false;
	}
	elseif (isset($info['erreur'])) {
		$class_li .= " error";
		$erreur = http_img_pack("plugin-err-32.png",_T('plugin_info_erreur_xml')," class='picto_err'",_T('plugin_info_erreur_xml'))
		  . "<div class='erreur'>" . join('<br >', $info['erreur']) . "</div>";
		$checkable = false;
	}
	elseif (isset($GLOBALS['erreurs_activation_raw'][$dir_plugins.$plug_file])){
		$class_li .= " error";
		$erreur = http_img_pack("plugin-err-32.png",_T('plugin_impossible_activer', array('plugin' => $nom))," class='picto_err'",_T('plugin_impossible_activer', array('plugin' => $nom)))
		  . "<div class='erreur'>" . implode("<br />",$GLOBALS['erreurs_activation_raw'][$dir_plugins.$plug_file]) . "</div>";
	}
	else
		$cfg = $actif ? plugin_bouton_config($plug_file,$info,$dir_plugins) : "";

	// numerotons les occurrences d'un meme prefix
	$versions[$prefix] = $id = isset($versions[$prefix]) ? $versions[$prefix] + 1 : '';

	$class_li .= ($actif?" actif":"") . ($expose?" on":"");
	return "<li id='$prefix$id' class='$class_li'>"
	. ((!$checkable AND !$checked)
	   ? '': plugin_checkbox(++$id_input, $dir_plugins.$plug_file, $checked))
	.  plugin_resume($info, $dir_plugins, $plug_file, $url_page)
	. $cfg
	. $erreur
	. (($dir_plugins!==_DIR_PLUGINS_DIST AND plugin_est_installe($plug_file))
	    ? plugin_desintalle($plug_file,$nom,$dir_plugins) : '')
	. "<div class='details'>" // pour l'ajax de exec/info_plugin
	. (!$expose ? '' : affiche_bloc_plugin($plug_file, $info, $dir_plugins))
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

function plugin_nom($info, $dir_plugins, $plug_file){
	$prefix = $info['prefix'];
	$dir = "$dir_plugins$plug_file";
	// Si dtd paquet, on traite le nom soit par son item de langue soit par sa valeur immediate a l'index "nom"
	if ($info['dtd'] == "paquet") {
		$nom = PtoBR(plugin_propre("{$prefix}_nom", "$dir/lang/paquet-$prefix"));
		if (!$nom)
			$nom = PtoBR(propre($info['nom']));
	}
	else
		$nom = typo(attribut_html($info['nom']));

	return trim($nom);
}

// Cartouche Resume
function plugin_resume($info, $dir_plugins, $plug_file, $url_page){
	$prefix = $info['prefix'];
	$dir = "$dir_plugins$plug_file";
	$slogan = PtoBR(plugin_propre($info['slogan'], "$dir/lang/paquet-$prefix"));
	// une seule ligne dans le slogan : couper si besoin
	if (($p=strpos($slogan, "<br />"))!==FALSE)
		$slogan = substr($slogan, 0,$p);
	// couper par securite
	$slogan = couper($slogan, 80);

	$nom = plugin_nom($info, $dir_plugins, $plug_file);

	$url = parametre_url($url_page, "plugin", substr($dir,strlen(_DIR_RACINE)));

	if (isset($info['logo']) and $i = trim($info['logo'])) {
		include_spip("inc/filtres_images_mini");
		$i = inserer_attribut(image_reduire("$dir/$i", 32),'alt','');
		$i = "<div class='icon'><a href='$url' rel='info'>$i</a></div>";
	} else $i = '';

	return "<div class='resume'>"
	. "<h3><a href='$url' rel='info'>"
	. $nom
	. "</a></h3>"
	. " <span class='version'>".$info['version']."</span>"
	. " <span class='etat'> - "
	. plugin_etat_en_clair($info['etat'])
	. "</span>"
	. "<div class='short'>".$slogan."</div>"
	. $i
	. "</div>";
}

function plugin_desintalle($plug_file, $nom, $dir_plugins=null){
	if (!$dir_plugins)
		$dir_plugins = _DIR_PLUGINS;

	$action = redirige_action_auteur('desinstaller_plugin',"$dir_plugins::$plug_file",'admin_plugin');
	$text = _T('bouton_desinstaller');
	$text2 = _T('info_desinstaller_plugin');
	$file = basename($plug_file);

	return "<div class='actions'>[".
		"<a href='$action'
		onclick='return confirm(\"$text $nom ?\\n$text2\")'>"
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
	// retirer le retour a la racine du module, car le find_in_path se fait depuis la racine
	if (_DIR_RACINE AND strncmp($module,_DIR_RACINE,strlen(_DIR_RACINE))==0)
		$module = substr($module,strlen(_DIR_RACINE));
	if (preg_match("|^\w+_[\w_]+$|", $texte)) {
		$texte = _T(($module ? "$module:" : '') . $texte, array(), array('force' => false));
	}
	return propre($texte);
}



// http://doc.spip.org/@affiche_bloc_plugin
function affiche_bloc_plugin($plug_file, $info, $dir_plugins=null) {
	if (!$dir_plugins)
		$dir_plugins = _DIR_PLUGINS;

	$prefix = $info['prefix'];
	$dir = "$dir_plugins$plug_file/lang/paquet-$prefix";

	$s = "";
	// TODO: le traiter_multi ici n'est pas beau
	// cf. description du plugin/_stable_/ortho/plugin.xml
	$description = "";
	if (isset($info['description']))
		$description = plugin_propre($info['description'], $dir);

	if (isset($info['documentation'])
	  AND $lien = $info['documentation']){
		$description .= "<p><em class='site'><a href='$lien' class='spip_out'>" . _T('en_savoir_plus') .'</a></em></p>';
	}
	$s .= "<dd class='desc'>".$description."</dd>\n";

	if (isset($info['auteur'])){
		if (is_array($info['auteur']))
			$a = formater_credits($info['auteur'], ', ');
		// pour compat mais ne doit plus arriver
		else
			$a = trim($info['auteur']);
		if ($a)
			$s .= "<dt class='auteurs'>" . _T('public:par_auteur') ."</dt><dd class='auteurs'>". PtoBR(propre($a, $dir)) . "</dd>\n";
	}

	if (isset($info['credit'])){
		if ($a = formater_credits($info['credit'], ', '))
			$s .= "<dt class='credits'>" . _T('plugin_info_credit') ."</dt><dd class='credits'>". PtoBR(propre($a, $dir)) . "</dd>\n";
	}

	if (isset($info['licence'])) {
		if (is_array($info['licence']))
			$a = formater_credits($info['licence'], ', ');
		// pour compat mais ne doit plus arriver
		else
			$a = trim($info['licence']);
	if ($a)
		$s .= "<dt class='licence'>" . _T('intitule_licence') ."</dt><dd class='licence'>". PtoBR(propre($a, $dir)) . "</dd>\n";
	}

	$s = "<dl class='description'>$s</dl>";

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
	$infotech[] = "<dt>"._T('repertoire_plugins')."</dt><dd>".joli_repertoire("$dir_plugins$plug_file")."</dd>";
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

function formater_credits($infos, $sep=', ') {
	$texte = '';

	foreach ($infos as $_credit) {
		if ($texte)
			$texte .= $sep;
		// Si le credit en cours n'est pas un array c'est donc un copyright
		$texte .=
			(!is_array($_credit))
			? PtoBR(propre($_credit))
			: ($_credit['url'] ? '<a href="' . $_credit['url'] . '">' : '') .
			  $_credit['nom'] .
			  ($_credit['url'] ? '</a>' : '');
	}

	return $texte;
}

?>
