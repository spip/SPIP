<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('inc/charsets');

/**
 * Afficher une liste de plugins dans l'interface
 *
 * @param string $url_page
 * @param array $liste_plugins
 * @param array $liste_plugins_checked
 * @param array $liste_plugins_actifs
 * @param string $dir_plugins
 * @param string $afficher_un
 * @return string
 */
function plugins_afficher_liste_dist(
	$url_page,
	$liste_plugins,
	$liste_plugins_checked,
	$liste_plugins_actifs,
	$dir_plugins = _DIR_PLUGINS,
	$afficher_un = 'afficher_plugin'
) {
	$get_infos = charger_fonction('get_infos', 'plugins');
	$ligne_plug = charger_fonction($afficher_un, 'plugins');

	$all_infos = $get_infos($liste_plugins, false, $dir_plugins);

	$all_infos = pipeline(
		'filtrer_liste_plugins',
		[
			'args' => [
				'liste_plugins' => $liste_plugins,
				'liste_plugins_checked' => $liste_plugins_checked,
				'liste_plugins_actifs' => $liste_plugins_actifs,
				'dir_plugins' => $dir_plugins
			],
			'data' => $all_infos
		]
	);

	$liste_plugins = array_flip($liste_plugins);
	foreach (array_keys($liste_plugins) as $chemin) {
		// des plugins ont pu etre enleves de la liste par le pipeline. On en tient compte.
		if (isset($all_infos[$chemin])) {
			$liste_plugins[$chemin] = strtoupper(trim(typo(translitteration(unicode2charset(html2unicode($all_infos[$chemin]['nom']))))));
		} else {
			unset($liste_plugins[$chemin]);
		}
	}
	asort($liste_plugins);
	$exposed = urldecode(_request('plugin') ?? '');

	$block_par_lettre = false;//count($liste_plugins)>10;
	$fast_liste_plugins_actifs = [];
	$fast_liste_plugins_checked = [];
	if (is_array($liste_plugins_actifs)) {
		$fast_liste_plugins_actifs = array_flip($liste_plugins_actifs);
	}
	if (is_array($liste_plugins_checked)) {
		$fast_liste_plugins_checked = array_flip($liste_plugins_checked);
	}

	$res = '';
	$block = '';
	$initiale = '';
	$block_actif = false;
	foreach ($liste_plugins as $plug => $nom) {
		if (($i = substr($nom, 0, 1)) !== $initiale) {
			$res .= $block_par_lettre ? affiche_block_initiale($initiale, $block, $block_actif) : $block;
			$initiale = $i;
			$block = '';
			$block_actif = false;
		}
		// le rep suivant
		$actif = isset($fast_liste_plugins_actifs[$plug]);
		$checked = isset($fast_liste_plugins_checked[$plug]);
		$block_actif |= $actif;
		$expose = ($exposed && ($exposed == $plug || $exposed === $dir_plugins . $plug || $exposed === substr(
			$dir_plugins,
			strlen(_DIR_RACINE)
		) . $plug));
		$block .= $ligne_plug($url_page, $plug, $checked, $actif, $expose, 'item', $dir_plugins) . "\n";
	}
	$res .= $block_par_lettre ? affiche_block_initiale($initiale, $block, $block_actif) : $block;
	$class = basename($dir_plugins);

	return $res ? "<ul class='liste-items plugins $class'>$res</ul>" : '';
}


function affiche_block_initiale($initiale, $block, $block_actif) {
	if (strlen($block)) {
		return "<li class='item'>"
		. bouton_block_depliable($initiale, (bool) $block_actif)
		. debut_block_depliable($block_actif)
		. "<ul>$block</ul>"
		. fin_block()
		. '</li>';
	}

	return '';
}
