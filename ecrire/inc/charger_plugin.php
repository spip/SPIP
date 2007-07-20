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


# l'adresse du repertoire de telechargement et de decompactage des plugins
#define('_DIR_PLUGINS_AUTO', _DIR_PLUGINS.'auto/');
define('_DIR_PLUGINS_AUTO', _DIR_PLUGINS);

include_spip('inc/plugin');


function formulaire_charger_plugin($retour='') {
	global $spip_lang_left, $spip_lang_right;

	include_spip('inc/filtres');
	include_spip('inc/actions');
	include_spip('inc/presentation');


	$message = _L("Vous pouvez installer des plugins dans le r&#233;pertoire <code>".joli_repertoire(_DIR_PLUGINS)."</code>.");


	if (!is_dir(_DIR_PLUGINS_AUTO)
	OR !is_writeable(_DIR_PLUGINS_AUTO)) {
		$erreur = _L("Pour permettre l'installation automatique des plugins, veuillez cr&#233;er le r&#233;pertoire <code>".joli_repertoire(_DIR_PLUGINS_AUTO)."</code> et v&#233;rifier que le serveur est autoris&#233; &#224; y &#233;crire.").aide("droits");
	}


	if ($erreur) {
		return debut_cadre_trait_couleur("spip-pack-24.png", true, "", _L('Ajouter des plugins'))
		. "<p>".$message."</p>\n"
		. "<p>".$erreur."</p>\n"
		. fin_cadre_trait_couleur(true);
	}


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

		$menu = '';
		foreach ($liste as $url => $titre)
		$menu .= '<option value="'.entites_html($url).'">'.couper(typo($titre),50)."</option>\n";
		$res .= "<p><select name='url_zip_plugin'>\n"
			."<option>"._L('choisir...')."</option>"
			.$menu."\n</select></p>\n";

		$res .= _L("ou...");
	}


	$res .= _L("<p><label>indiquez ci-dessous l'adresse d'un fichier zip de plugin &#224; t&#233;l&#233;charger, ou encore l'adresse d'une liste de plugins.");
	
	$res .= '<p>('._L('exemples :').' http://files.spip.org/spip-zone/paquets.rss.xml.gz ; http://www.spip-contrib.net/spip.php?page=backend&amp;id_mot=112)</p>';

	// TODO: OU l'adresse d'une liste de plugins
	// TODO: OU uploadez un fichier ZIP ou une liste de plugins

	$res .= "<br /><input type='text' name='url_zip_plugin2' value='http://files.spip.org/spip-zone/' size='50' /></label></p>\n";

	$res .= "</td></tr>";
	$res .= "</table>\n"
		. "<div style='float:".$GLOBALS['spip_lang_right'].";'><input type='submit' value='"
		. _T('bouton_valider')
		.  "' class='fondo' />\n"
		.  "</div>\n";

	$res = redirige_action_auteur('charger_plugin',
				0,
				'',
				'',
				$res,
				"\nmethod='post'");


	$res .= afficher_liste_listes_plugins();

	$res = debut_cadre_trait_couleur("spip-pack-24.png", true, "", _L('Ajouter un plugin'))
	. $res
	. fin_cadre_trait_couleur(true);

	return $res;

}


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
					'dest' => _DIR_RACINE,
					'plugin' => null,
					'cache_cache' => null,
					'rename' => array(),
					'edit' => array())
				as $opt=>$def) {
		isset($quoi[$opt]) || ($quoi[$opt] = $def);
	}

	if (!@file_exists($fichier = $quoi['fichier']))
		return 0;

	include_spip('inc/pclzip');
	$zip = new PclZip($fichier);
	$list = $zip->listContent();
	$i = count($list) - 1;
	$path = explode('/', $list[$i]['filename']);
	while ($i--) {
		$act = explode('/', $list[$i]['filename']);
		for ($j = 0; $j < count($path); ++$j) {
			if ($j >= count($path) || $act[$j] != $path[$j]) {
				break;
			}
		}
		for ( ; $j < count($path); ++$j) {
			unset($path[$j]);
		}
	}

	$aremove = explode('/', $quoi['remove']);
	$jmax = count($path);
	for ($j = 0; $j < $jmax; ++$j) {
		if ($j >= count($aremove) || $path[$j] != $aremove[$j]) {
			break;
		}
		unset($path[$j]);
	}

	$adest = explode('/', $quoi['dest']);
	$kmax = count($adest);
	for ($k = 0 ; $k < $kmax && $j < $jmax; ++$j, ++$k) {
		if ($path[$j] != $adest[$k]) {
			break;
		}
		unset($adest[$k]);
	}
	$quoi['dest'] = implode('/', $adest);

	// si le zip veut se poser a la racine, on le colle dans un sous-repertoire
	// a son nom
	if (!$path) {
		$dirname = $quoi['dest'] = preg_replace(',\.zip$,', '/', $fichier);
		$removex = ',^'.preg_quote($quoi['remove'] . '/', ',').',';
		spip_log("zip racine, $dirname, $remove");
	} else {
		$dirname = $quoi['dest'] . implode('/', $path).'/';
		$removex =  ',^('.preg_quote($quoi['remove'] . '/', ',').')?'
			. preg_quote(implode('/', $path).'/',',').',';
		spip_log("zip bien forme, $dirname, $remove");
	}

	if ($quoi['extract']) {
		$ok = $zip->extract(
			PCLZIP_OPT_PATH, $quoi['dest'],
			PCLZIP_OPT_SET_CHMOD, _SPIP_CHMOD,
			PCLZIP_OPT_REPLACE_NEWER,
			PCLZIP_OPT_REMOVE_PATH, $quoi['remove'] . "/");
		if ($zip->error_code < 0) {
			spip_log('charger_decompresser erreur zip ' . $zip->error_code .
				' pour paquet: ' . $quoi['zip']);
			return $zip->error_code;
		}

		if (!$quoi['cache_cache']) {
			chargeur_montre_tout($quoi);
		}
		if ($quoi['rename']) {
			chargeur_rename($quoi);
		}
		if ($quoi['edit']) {
			chargeur_edit($quoi['dest'], $quoi['edit']);
		}

		if ($quoi['plugin']) {
			chargeur_activer_plugin($quoi['plugin']);
		}

		spip_log('charger_decompresser OK pour paquet: ' . $quoi['zip']);
	}

	$size = $compressed_size = 0;
	foreach ($list as $a => $f) {
		$size += $f['size'];
		$compressed_size += $f['compressed_size'];
		$list[$a] = preg_replace($removex,'',$f['filename']);
	}

	return array(
		'files' => $list,
		'size' => $size,
		'compressed_size' => $compressed_size,
		'dirname' => $dirname #.preg_replace(',/.*,', '', $list[0])
	);
}

// pas de fichiers caches et preg_files() les ignore (*sigh*)
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
function chargeur_activer_plugin($plugin)
{
	spip_log('charger_decompresser activer plugin: ' . $plugin);
	include_spip('inc/plugin');
	ecrire_plugin_actifs(array($plugin), false, 'ajoute');
	ecrire_metas();
}


function liste_fichiers_pclzip($status) {
	$list = $status['files'];
	$ret = '<b>'._L('Il contient les fichiers suivants ('
		.taille_en_octets($status['size']).'),<br />pr&#234;ts &#224; installer dans le r&#233;pertoire <code>'.$status['dirname']).'</code></b>';

	$l .= "<ul style='font-size:x-small;'>\n";
	foreach ($list as $f) {
		if (basename($f) == 'svn.revision')
			lire_fichier($status['dirname'].'/'.$f,$svn);
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
function chercher_enclosures_zip($rss) {
	$liste = array();
	include_spip('inc/syndic');
	foreach(analyser_backend($rss) as $item)
		if ($item['enclosures']
		AND $zips = extraire_balises($item['enclosures'], 'a'))
			foreach ($zips as $zip)
				if (extraire_attribut($zip, 'type') == 'application/zip')
					$liste[extraire_attribut($zip, 'href')] = $item['titre'];
	spip_log(count($liste).' enclosures au format zip');
	return $liste;
}


// Renvoie la liste des plugins distants (accessibles a travers
// l'une des listes de plugins)
function liste_plugins_distants() {
	// TODO une liste multilingue a telecharger
	$liste = array(
		'http://files.spip.org/spip-zone/crayons.zip' => 'Les Crayons',
		'http://files.spip.org/spip-zone/forms_et_tables_1_9_1.zip' => 'Forms &amp; tables',
		'http://files.spip.org/spip-zone/autorite.zip' => 'Autorit&#233;',
		'http://files.spip.org/spip-zone/cfg.zip' => 'CFG, outil de configuration',
		'http://files.spip.org/spip-zone/ortho.zip' => 'Correcteur d\'orthographe'
	);

	if (is_array($flux = @unserialize($GLOBALS['meta']['syndic_plug']))) {
		foreach ($flux as $url => $c) {
			if (file_exists($cache=_DIR_TMP.'syndic_plug_'.md5($url).'.xml')
			AND lire_fichier($cache, $rss))
				$liste = array_merge($liste,chercher_enclosures_zip($rss));
		}
	}

	return $liste;
}

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

?>
