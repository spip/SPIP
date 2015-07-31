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


/*
 * Ce fichier est extrait du plugin charge : action charger decompresser
 *
 * Auteur : bertrand@toggg.com
 * © 2007 - Distribue sous licence LGPL
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/plugin');
include_spip('inc/actions');

// http://doc.spip.org/@formulaire_charger_plugin
function formulaire_charger_plugin($retour='') {
	global $spip_lang_left, $spip_lang_right;

	include_spip('inc/filtres');
	include_spip('inc/presentation');

	// Si defini comme non-existant
	if (!_DIR_PLUGINS)
		return '';

	$auto = '';
	if (_DIR_PLUGINS_AUTO) {
		if (!@is_dir(_DIR_PLUGINS_AUTO)
		OR !is_writeable(_DIR_PLUGINS_AUTO)) {
		  $auto = _T('plugin_info_automatique1')."\n"
			.'<ol class="spip"><li>'._T('plugin_info_automatique2',array('rep'=>joli_repertoire(_DIR_PLUGINS_AUTO))).'</li>'
			.'<li>'._T('plugin_info_automatique3').aide("install0")."</li></ol>"
		  ."\n<p>"._T('plugin_info_automatique_lib')."</p>";
		}

		if (!$auto)
			$auto = interface_plugins_auto($retour);

	}

	$message = _T('plugin_info_automatique_ftp',array('rep'=>joli_repertoire(_DIR_PLUGINS)));
	if (!@is_dir(_DIR_PLUGINS))
		$message .= " &mdash; "._T('plugin_info_automatique_creer');

	return debut_cadre_trait_couleur("spip-pack-24.png", true, "", _T('plugin_titre_automatique_ajouter'))
		. "<h3>"._T('plugin_titre_automatique')."</h3>"
		. "<p>".$message."</p>\n"
		. $auto
		. fin_cadre_trait_couleur(true);

}


// http://doc.spip.org/@interface_plugins_auto
function interface_plugins_auto($retour) {

	$res = "<div class='verdana2'>";

	if ($retour) {
		$res .= "<div>$retour</div>\n";
	}

	$liste = liste_plugins_distants();

	$message .= '<div class="explication">'._T('plugin_zip_adresse')
  . '<br />'._T('plugin_info_automatique_exemples').'<ul class="spip">';
	
	$les_urls = array('http://plugins.spip.net/rss-+-selection-2-1-+','http://www.spip-contrib.net/?page=rss-plugins-spip-2-1');
	if (isset($GLOBALS['chargeur_urls_rss']) AND is_array($GLOBALS['chargeur_urls_rss']))
		$les_urls = array_merge($les_urls,$GLOBALS['chargeur_urls_rss']);
	foreach($les_urls as $url)
		$message .= "<li><a href='$url' onclick=\"jQuery('#url_zip_plugin2').attr('value',jQuery(this).html()).focus();return false;\">"
		.$url
		."</a></li>";
	$message .= "</ul></div>";
	
	$form = "";
	$form .= "<ul><li class='editer_url_zip_plugin2 obligatoire'>";

	$form .= "<label for='url_zip_plugin2'>"._T('plugin_zip_adresse_champ')."</label>";
	$form .= $message;
	$form .= "
	<input type='text' class='text' id='url_zip_plugin2' name='url_zip_plugin2' value='' size='40' />";
	$form .= "</li></ul>";
	$form .=	"<div class='boutons' id='loadrss'><input type='submit' value='"
		. _T('bouton_valider')
		.  "'/>\n"
		.  "</div>\n";
	$form = redirige_action_post('charger_plugin',
				'', // arg = 'plugins' / 'lib', a priori
				'',
				'',
				$form);
	
	$res .= "<div class='formulaire_spip formulaire_editer'>";

	$res .= $form;
	$res .= "</div>\n";
	

	$res .= "</div>\n";

	$res .= afficher_liste_listes_plugins();
	
	if ($liste) {
		$res .= afficher_liste_plugins_distants($liste);

		$menu = array();
		$compte = 0;

		$res .=
		http_script("
	jQuery(function(){
		jQuery('.plugins li.item a[rel=info]').click(function(){
			var li = jQuery(this).parents('li').eq(0);
			if (!jQuery('div.details',li).html()) {
				jQuery('div.details',li).prepend(ajax_image_searching).load(
					jQuery(this).attr('href').replace(/admin_plugin|plugins|charger_plugin/, 'info_plugin_distant'), {}, function(){
						li.addClass('on');
					}
				);
			}
			else {
				if (jQuery('div.details',li).toggle().is(':visible'))
					li.addClass('on');
				else
					li.removeClass('on');
			}
			return false;
		});
	});
	");
			
	}
	return $res;
}

function afficher_liste_plugins_distants($liste){
	$res = "";
	if (!$liste) return "";
	
	$menu = array();
	$compte = 0;

	$afficher_plugin_distant = charger_fonction('afficher_plugin_distant','plugins');
	$url_page = self();
	foreach ($liste as $url => $info) {
		$titre = $info[0];
		$titre = strtoupper(trim(typo(translitteration(unicode2charset(html2unicode($titre))))));
		$menu[$titre] = $afficher_plugin_distant($url_page, $url, $info, _request('plugin')==$url);
	}
	ksort($menu);

	$res .=
		"<h3>"._T('plugins_compte',array('count' => count($menu)))."</h3>"
	  . '<p>'._T('plugin_info_automatique_select',array('rep'=>joli_repertoire(_DIR_PLUGINS_AUTO))).'</p>'
		. "<ul class='liste-items plugins distants'>".join("\n",$menu)."</ul>";

	return $res;
}

// http://doc.spip.org/@chargeur_charger_zip
function chargeur_charger_zip($quoi = array())
{
	if (!$quoi) {
		return true;
	}
	if (is_scalar($quoi)) {
		$quoi = array('zip' => $quoi);
	}
	if (isset($quoi['depot']) || isset($quoi['nom'])) {
		$quoi['zip'] = $quoi['depot'] . $quoi['nom'] . '.zip';
	}
	foreach (array(	'remove' => 'spip',
					'arg' => 'lib',
					'plugin' => null,
					'cache_cache' => null,
					'rename' => array(),
					'edit' => array(),
					'root_extract' => false, # extraire a la racine de dest ?
					'tmp' => sous_repertoire(_DIR_CACHE, 'chargeur')
				)
				as $opt=>$def) {
		isset($quoi[$opt]) || ($quoi[$opt] = $def);
	}


	# destination finale des fichiers
	switch($quoi['arg']) {
		case 'lib':
			$quoi['dest'] = _DIR_RACINE.'lib/';
			break;
		case 'plugins':
			$quoi['dest'] = _DIR_PLUGINS_AUTO;
			break;
		default:
			$quoi['dest'] = '';
			break;
	}


	if (!@file_exists($fichier = $quoi['fichier']))
		return 0;

	include_spip('inc/pclzip');
	$zip = new PclZip($fichier);
	$list = $zip->listContent();

	// on cherche la plus longue racine commune a tous les fichiers
	foreach($list as $n) {
		$p = array();
		foreach(explode('/', $n['filename']) as $n => $x) {
			$sofar = join('/',$p);
			$paths[$n][$sofar]++;
			$p[] = $x;
		}
	}

	$total = $paths[0][''];
	$i = 0;
	while (isset($paths[$i])
	AND count($paths[$i]) <= 1
	AND array_values($paths[$i]) == array($total))
		$i++;

	$racine = $i
		? array_pop(array_keys($paths[$i-1])).'/'
		: '';

	$quoi['remove'] = $racine;

	if (!strlen($nom = basename($racine)))
		$nom = basename($fichier, '.zip');

	$dir_export = $quoi['root_extract']
		? $quoi['dest']
		: $quoi['dest'] . $nom.'/';

	$tmpname = $quoi['tmp'].$nom.'/';

	// On extrait, mais dans tmp/ si on ne veut pas vraiment le faire
	$ok = $zip->extract(
		PCLZIP_OPT_PATH,
			$quoi['extract']
				? $dir_export
				: $tmpname
		,
		PCLZIP_OPT_SET_CHMOD, _SPIP_CHMOD,
		PCLZIP_OPT_REPLACE_NEWER,
		PCLZIP_OPT_REMOVE_PATH, $quoi['remove']
	);
	if ($zip->error_code < 0) {
		spip_log('charger_decompresser erreur zip ' . $zip->error_code .
			' pour paquet: ' . $quoi['zip']);
		return //$zip->error_code
			$zip->errorName(true);
	}

/*
 * desactive pour l'instant
 *
 *
		if (!$quoi['cache_cache']) {
			chargeur_montre_tout($quoi);
		}
		if ($quoi['rename']) {
			chargeur_rename($quoi);
		}
		if ($quoi['edit']) {
			chargeur_edit($dir_export, $quoi['edit']);
		}

		if ($quoi['plugin']) {
			chargeur_activer_plugin($quoi['plugin']);
		}
*/

	spip_log('charger_decompresser OK pour paquet: ' . $quoi['zip']);



	$size = $compressed_size = 0;
	$removex = ',^'.preg_quote($quoi['remove'], ',').',';
	foreach ($list as $a => $f) {
		$size += $f['size'];
		$compressed_size += $f['compressed_size'];
		$list[$a] = preg_replace($removex,'',$f['filename']);
	}

	// Indiquer par un fichier install.log
	// a la racine que c'est chargeur qui a installe ce plugin
	ecrire_fichier($tmpname.'/install.log',
		"installation: charger_plugin\n"
		."date: ".gmdate('Y-m-d\TH:i:s\Z', time())."\n"
		."source: ".$quoi['zip']."\n"
	);



	return array(
		'files' => $list,
		'size' => $size,
		'compressed_size' => $compressed_size,
		'dirname' => $dir_export,
		'tmpname' => $tmpname
	);
}

// pas de fichiers caches et preg_files() les ignore (*sigh*)
// http://doc.spip.org/@chargeur_montre_tout
function chargeur_montre_tout($quoi)
{
	# echo($quoi['dest']);
	if (!($d = @opendir($quoi['dest']))) {
		return;
	}
	while (($f = readdir($d)) !== false) {
		if ($f == '.' || $f == '..' || $f[0] != '.') {
			continue;
		}
		rename($quoi['dest'] . '/' . $f, $quoi['dest'] . '/'. substr($f, 1));
	}
}

// renommer des morceaux
// http://doc.spip.org/@chargeur_edit
function chargeur_edit($dir, $edit)
{
	if (!($d = @opendir($dir))) {
		return;
	}
	while (($f = readdir($d)) !== false) {
		if ($f == '.' || $f == '..') {
			continue;
		}
		if (is_dir($f = $dir . '/' . $f)) {
			chargeur_edit($f, $edit);
		}
		$contenu = 	file_get_contents($f);
		if (($change = preg_replace(
				array_keys($edit), array_values($edit), $contenu)) == $contenu) {
			continue;
		}
		$fw = fopen($f, 'w');
		fwrite($fw, $change);
		fclose($fw);
	}
}

// renommer des morceaux
// http://doc.spip.org/@chargeur_rename
function chargeur_rename($quoi)
{
/*
 preg_files() est deficiante pour les fichiers caches, ca aurait pu etre bien pourtant ...
*/
	spip_log($quoi);
	foreach ($quoi['rename'] as $old => $new) {
		!is_writable($file = $quoi['dest'] . '/' . $old) ||
			rename($file, $quoi['dest'] . '/'. $new);
	}
}

// juste activer le plugin du repertoire $plugin
// http://doc.spip.org/@chargeur_activer_plugin
function chargeur_activer_plugin($plugin)
{
	spip_log('charger_decompresser activer plugin: ' . $plugin);
	include_spip('inc/plugin');
	ecrire_plugin_actifs(array($plugin), false, 'ajoute');
}


// http://doc.spip.org/@liste_fichiers_pclzip
function liste_fichiers_pclzip($status) {
	$list = $status['files'];

	$ret = '<b>'._T('plugin_zip_content',array('taille'=>taille_en_octets($status['size']), 'rep'=>$status['dirname'])).'</b>';

	$l .= "<ul style='font-size:x-small;'>\n";
	foreach ($list as $f) {
		if (basename($f) == 'svn.revision')
			lire_fichier($status['tmpname'].'/'.$f,$svn);
		if ($joli = preg_replace(',^(.*/)([^/]+/?)$,', '<span style="visibility:hidden">\1</span>\2', $f)) {
			if (!$vu[dirname($f.'x')]++)
				$l .= "<li>".$f."</li>\n";
			else
				$l .= "<li>".$joli."</li>\n";
		}
	}
	$l .= "</ul>\n";

	include_spip('inc/filtres');
	if (preg_match(',<revision>([^<]+)<,', $svn, $t))
		$rev = '<div>revision '.$t[1].'</div>';
	if (preg_match(',<commit>([^<]+),', $svn, $t))
		$date = '<div>' . affdate($t[1]) .'</div>';

	return $ret . $rev . $date . $l;
}

// Attention on ne sait pas ce que vaut cette URL
// http://doc.spip.org/@essaie_ajouter_liste_plugins
function essaie_ajouter_liste_plugins($url) {
	if (!preg_match(',^https?://[^.]+\.[^.]+.*/.*[^/]$,', $url))
		return;

	include_spip('inc/distant');
	if (!$rss = recuperer_page($url)
	OR !preg_match(',<item,i', $rss))
		return;

	$liste = chercher_enclosures_zip($rss,true);
	if (!$liste)
		return;

	// Ici c'est bon, on conserve l'url dans spip_meta
	// et une copie du flux analise dans tmp/
	ecrire_fichier(_DIR_TMP.'syndic_plug_'.md5($url).'.txt', serialize($liste));
	$syndic_plug = @unserialize($GLOBALS['meta']['syndic_plug']);
	$syndic_plug[$url] = count($liste);
	ecrire_meta('syndic_plug', serialize($syndic_plug));
}

// Recherche les enclosures de type zip dans un flux rss ou atom
// les renvoie sous forme de tableau url => titre
// si $desc on ramene aussi le descriptif du paquet desc
// http://doc.spip.org/@chercher_enclosures_zip
function chercher_enclosures_zip($rss, $desc = '') {
	$liste = array();
	include_spip('inc/syndic');
	foreach(analyser_backend($rss) as $item){
		if ($item['enclosures']
		AND $zips = extraire_balises($item['enclosures'], 'a')){
			if ($img = extraire_balise($item['descriptif'], 'img')
			  AND $src = extraire_attribut($img, 'src')) {
				$item['icon'] = $src;
			}
			foreach ($zips as $zip)
				if (extraire_attribut($zip, 'type') == 'application/zip') {
					if ($url = extraire_attribut($zip, 'href')) {
						$liste[$url] = array($item['titre'], $item['url']);
						if ($desc===true OR $desc == $url)
							$liste[$url][] = $item;
					}
				}
		}
	}
	spip_log(count($liste).' enclosures au format zip');
	return $liste;
}


// Renvoie la liste des plugins distants (accessibles a travers
// l'une des listes de plugins)
// Si on passe desc = un url, ramener le descriptif de ce paquet
// http://doc.spip.org/@liste_plugins_distants
function liste_plugins_distants($desc = false) {
	// TODO une liste multilingue a telecharger
	$liste = array();

	if (is_array($flux = @unserialize($GLOBALS['meta']['syndic_plug']))) {
	
		foreach ($flux as $url => $c) {
			if (file_exists($cache=_DIR_TMP.'syndic_plug_'.md5($url).'.txt')
			AND lire_fichier($cache, $rss))
				$liste = array_merge(unserialize($rss),$liste);
		}
	}

	return $liste;
}

// http://doc.spip.org/@afficher_liste_listes_plugins
function afficher_liste_listes_plugins() {
	if (!is_array($flux = @unserialize($GLOBALS['meta']['syndic_plug'])))
		return '';

	if (count($flux)){
		$ret = '<h3>'._T('plugin_info_automatique_liste').'</h3><ul class="liste-items">';
			//$ret .= '<li>'._T('plugin_info_automatique_liste_officielle').'</li>';
		foreach ($flux as $url => $c) {
			$a = '<div class="actions">[<a href="'.parametre_url(
				generer_action_auteur('charger_plugin', 'supprimer_flux'),'supprimer_flux', $url).'">'._T('lien_supprimer').'</a>]</div>';
			$time = @filemtime(_DIR_TMP.'syndic_plug_'.md5($url).'.txt');
			$ret .= '<li class="item">'.inserer_attribut(PtoBR(propre("[->$url]")),'title',$url).' ('._T('plugins_compte',array('count' => $c)).') '
							.($time?"<div class='small'>" . _T('info_derniere_syndication').' '.affdate(date('Y-m-d H:i:s',$time)) ."</div>":'')
							. $a .'</li>';
		}
		$ret .= '</ul>';
	
		$ret .= '<div style="text-align:'.$GLOBALS['spip_lang_right'].'"><a href="'.parametre_url(
										  generer_action_auteur('charger_plugin', 'update_flux'),'update_flux', 'oui').'">'._T('plugin_info_automatique_liste_update').'</a></div>';
	}

	return $ret;
}

// Si le chargement auto est autorise, un bouton
// sinon on donne l'url du zip
// http://doc.spip.org/@bouton_telechargement_plugin
function bouton_telechargement_plugin($url, $rep) {
	// essayer de creer le repertoire lib/ si on en a le droit
	if (($rep == 'lib') AND !is_dir(_DIR_RACINE . 'lib'))
		sous_repertoire(_DIR_RACINE . 'lib','',false,true);

	if (($rep == 'lib')?
			is_dir(_DIR_RACINE . 'lib'):
			(_DIR_PLUGINS_AUTO AND @is_dir(_DIR_PLUGINS_AUTO))
		)
		$bouton = redirige_action_post('charger_plugin',
			$rep, // arg = 'lib' ou 'plugins'
			'',
			'',
			"<input type='hidden' name='url_zip_plugin' value='$url' />"
			."<input type='submit' name='ok' value='"._T('bouton_telecharger')."' />",
			'class="noajax"');
	else if ($rep == 'lib'){
		$bouton = "<div class='info_todo'>"._T('plugin_info_automatique1_lib')."\n"
		.'<ol><li>'._T('plugin_info_automatique2',array('rep'=>joli_repertoire(_DIR_RACINE . 'lib/'))).'</li>'
		.'<li>'._T('plugin_info_automatique3').aide("install0")."</li></ol></div>";
	}

	return _T('plugin_info_telecharger',array('url'=>$url,'rep'=>$rep.'/')).$bouton;

}

?>
