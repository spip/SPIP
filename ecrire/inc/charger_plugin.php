<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
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

if (!defined("_ECRIRE_INC_VERSION")) return;


include_spip('inc/plugin');


// http://doc.spip.org/@formulaire_charger_plugin
function formulaire_charger_plugin($retour='') {
	global $spip_lang_left, $spip_lang_right;

	include_spip('inc/filtres');
	include_spip('inc/actions');
	include_spip('inc/presentation');

	// Si defini comme non-existant
	if (!_DIR_PLUGINS)
		return '';

	$auto = '';
	if (_DIR_PLUGINS_AUTO) {
		if (!@is_dir(_DIR_PLUGINS_AUTO)
		OR !is_writeable(_DIR_PLUGINS_AUTO)) {
			$auto = _L("Si vous souhaitez autoriser l'installation automatique des plugins, veuillez&nbsp;:
			<ul>
			<li>cr&#233;er un r&#233;pertoire <code>".joli_repertoire(_DIR_PLUGINS_AUTO)."</code>&nbsp;;
			<li>v&#233;rifier que le serveur est autoris&#233; &#224; &#233;crire dans ce r&#233;pertoire.".aide("install0")."</li>
			</ul>");
			$auto .= "<p>"._L("Certains plugins demandent aussi &#224; pouvoir t&#233;l&#233;charger des fichiers dans le r&#233;pertoire <code>lib/</code>, &#224; cr&#233;er le cas &#233;ch&#233;ant &#224; la racine du site.")."</p>";
		}

		if (!$auto)
			$auto = interface_plugins_auto($retour);

		$auto = "<br />"
		. debut_cadre_enfonce('', true, '', 'Installation automatique').$auto.fin_cadre_enfonce(true);
	}

	$message = _L("Vous pouvez installer des plugins, par FTP, dans le r&#233;pertoire <tt>".joli_repertoire(_DIR_PLUGINS)."</tt>");
	if (!@is_dir(_DIR_PLUGINS))
		$message .= " &mdash; "._L("&#224; cr&#233;er &#224; la racine du site.");

	return debut_cadre_trait_couleur("spip-pack-24.png", true, "", _L('Ajouter des plugins'))
		. "<p>".$message."</p>\n"
		. $auto
		. fin_cadre_trait_couleur(true);

}


// http://doc.spip.org/@interface_plugins_auto
function interface_plugins_auto($retour) {

	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";

	if ($retour) {
		$res .= "<tr><td class='verdana2'>";
		$res .= $retour;
		$res .= "</td></tr>\n";
	}

	$res .= "<tr><td class='verdana2'>";

	$liste = liste_plugins_distants();

	if ($liste) {
		$res .= _L('<p>S&#233;lectionnez ci-dessous un plugin : SPIP le t&#233;l&#233;chargera et l\'installera dans le r&#233;pertoire <code>'.joli_repertoire(_DIR_PLUGINS_AUTO).'</code>&nbsp;; si ce plugin existe d&#233;j&#224;, il sera mis &#224; jour.</p>');

		$menu = array();
		foreach ($liste as $url => $info) {
			$titre = $info[0];
			$url_doc = $info[1];
			$titre = typo('<multi>'.$titre.'</multi>'); // recuperer les blocs multi du flux de la zone (temporaire?)
			
			if ($url_doc)
				$titre = "<a href='$url_doc' title='$url_doc'>$titre</a>";
			
			
			$nick = strtolower(basename($url, '.zip'));
			$menu[$nick] = '<div class="desc_plug"><label><input type="radio" name="url_zip_plugin" value="'.entites_html($url).'" />'."<b title='$url'>$nick</b></label> | ".$titre."</div>\n";
		}
		ksort($menu);

		$res .= "<style type='text/css'><!--
		.desc_plug {
	height:1.9em;overflow:hidden;border-bottom:1px dotted grey;
}
#liste_plug {
	border: solid 1px $couleur_foncee; padding:3px; background-color:white; height: 200px; overflow:auto;overflow-y: auto;
}
// --></style>\n";

		$res .= "<div id='liste_plug' class='cadre-trait-couleur'>\n";
# <select name='url_zip_plugin'>
#			."<option>"._L('choisir...')."</option>"
			$res .= join("\n",$menu);
#			."\n</select></p>\n";
		$res .= "</div>\n";

		$res .= "\n<div id='desc'></div>\n";



		$res .= _L("ou...");
	}

	$res .= '<label>';
	$res .= _L("<p>indiquez ci-dessous l'adresse d'un fichier zip de plugin &#224; t&#233;l&#233;charger, ou encore l'adresse d'une liste de plugins.</p>");
	
	$res .= '<p>('._L('exemples :').' http://files.spip.org/spip-zone/paquets.rss.xml.gz ; http://www.spip-contrib.net/spip.php?page=backend&amp;id_mot=112)</p>';


	$res .= "<br />
	<input type='radio' id='antiradio' name='url_zip_plugin' value='' />
	<input type='text' id='url_zip_plugin2' name='url_zip_plugin2' value='http://files.spip.org/spip-zone/' size='50' /></p></label>\n";

	$res .= http_script("
	// charger en ajax le descriptif si on click une div
	jQuery('#liste_plug .desc_plug').click(function(e) {
		jQuery('#desc').load('".generer_url_ecrire('charger_plugin_descr', 'url=', '\\x26')."'+jQuery('input', this).attr('value'));
	});
	// deselectionner un bouton radio si on change l'url
	jQuery('#url_zip_plugin2').bind('change', function() {
		jQuery('#antiradio').attr('checked','on');
		jQuery('#desc').html('');
	});
	jQuery('#antiradio').hide();
	");


	$res .= "</td></tr>";
	$res .= "</table>\n"
		. "<div style='float:".$GLOBALS['spip_lang_right'].";'><input type='submit' value='"
		. _T('bouton_valider')
		.  "' class='fondo' />\n"
		.  "</div>\n";

	$res = redirige_action_auteur('charger_plugin',
				'', // arg = 'plugins' / 'lib', a priori
				'',
				'',
				$res,
				"\nmethod='post'");


	$res .= afficher_liste_listes_plugins();

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
			$quoi['dest'] = 'lib/';
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
	ecrire_metas();
}


// http://doc.spip.org/@liste_fichiers_pclzip
function liste_fichiers_pclzip($status) {
	$list = $status['files'];

	$ret = '<b>'._L('Il contient les fichiers suivants ('
		.taille_en_octets($status['size']).'),<br />pr&#234;ts &#224; installer dans le r&#233;pertoire <code>'.$status['dirname']).'</code></b>';

	$l .= "<ul style='font-size:x-small;'>\n";
	foreach ($list as $f) {
		if (basename($f) == 'svn.revision')
			lire_fichier($status['tmpname'].'/'.$f,$svn);
		if ($joli = preg_replace(',^(.*/)([^/]+/?)$,', '<span style="visibility:hidden">\1</span>\2', $f))
			$l .= "<li>".$joli."</li>\n";
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

	$liste = chercher_enclosures_zip($rss);
	if (!$liste)
		return;

	// Ici c'est bon, on conserve l'url dans spip_meta
	// et une copie du flux dans tmp/
	ecrire_fichier(_DIR_TMP.'syndic_plug_'.md5($url).'.xml', $rss);
	$syndic_plug = @unserialize($GLOBALS['meta']['syndic_plug']);
	$syndic_plug[$url] = count($liste);
	ecrire_meta('syndic_plug', serialize($syndic_plug));
	ecrire_metas();
}

// Recherche les enclosures de type zip dans un flux rss ou atom
// les renvoie sous forme de tableau url => titre
// si $desc on ramene aussi le descriptif du paquet desc
// http://doc.spip.org/@chercher_enclosures_zip
function chercher_enclosures_zip($rss, $desc = '') {
	$liste = array();
	include_spip('cron/syndic');
	foreach(analyser_backend($rss) as $item)
		if ($item['enclosures']
		AND $zips = extraire_balises($item['enclosures'], 'a'))
			foreach ($zips as $zip)
				if (extraire_attribut($zip, 'type') == 'application/zip') {
					if ($url = extraire_attribut($zip, 'href')) {
						$liste[$url] = array($item['titre'], $item['url']);
						if ($desc == $url)
							$liste[$url][] = $item;
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
	$liste = array(
		'http://files.spip.org/spip-zone/crayons.zip' =>
			array('Les Crayons', 'http://www.spip-contrib.net/Les-Crayons'),
		'http://files.spip.org/spip-zone/forms_et_tables_1_9_1.zip' =>
			array('Forms &amp; tables', 'http://www.spip-contrib.net/Forms'),
		'http://files.spip.org/spip-zone/autorite.zip' =>
			array('Autorit&#233;', 'http://www.spip-contrib.net/-Autorite-'),
		'http://files.spip.org/spip-zone/cfg.zip' =>
			array('CFG, outil de configuration', 'http://www.spip-contrib.net/cfg-references'),
		'http://files.spip.org/spip-zone/ortho.zip' =>
			array('Correcteur d\'orthographe')
	);

	if (is_array($flux = @unserialize($GLOBALS['meta']['syndic_plug']))) {
		foreach ($flux as $url => $c) {
			if (file_exists($cache=_DIR_TMP.'syndic_plug_'.md5($url).'.xml')
			AND lire_fichier($cache, $rss))
				$liste = array_merge(chercher_enclosures_zip($rss, $desc),$liste);
		}
	}

	return $liste;
}

// http://doc.spip.org/@afficher_liste_listes_plugins
function afficher_liste_listes_plugins() {
	if (!is_array($flux = @unserialize($GLOBALS['meta']['syndic_plug'])))
		return '';

	$ret = '<p>'._L('Vos listes de plugins :').'</p><ul>';
		$ret .= '<li>'._L('les plugins officiels').'</li>';
	foreach ($flux as $url => $c) {
		$a = '<a href="'.parametre_url(
			generer_action_auteur('charger_plugin', 'supprimer_flux'),'supprimer_flux', $url).'">x</a>';
		$ret .= '<li>'.PtoBR(propre("[->$url]")).' ('.$c
			.' plugins) '.$a.'</li>';
	}
	$ret .= '</ul>';

	$ret .= '<a href="'.parametre_url(
			generer_action_auteur('charger_plugin', 'update_flux'),'update_flux').'">'._L('Mettre &#224; jour les listes').'</a>';

	return $ret;
}

// Si le chargement auto est autorise, un bouton
// sinon on donne l'url du zip
// http://doc.spip.org/@bouton_telechargement_plugin
function bouton_telechargement_plugin($url, $rep) {
	if (_DIR_PLUGINS_AUTO
	AND @is_dir(_DIR_PLUGINS_AUTO))
		$bouton = redirige_action_auteur('charger_plugin',
			$rep, // arg = 'lib' ou 'plugins'
			'',
			'',
			"<input type='hidden' name='url_zip_plugin' value='$url' />"
			."<input type='submit' name='ok' value='"._T('bouton_telecharger')."' />",
			"\nmethod='post'");

	return _L("&#224; t&#233;l&#233;charger depuis $url et &#224; installer dans $rep/").$bouton;

}

?>
