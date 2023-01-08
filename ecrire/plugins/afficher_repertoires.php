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

function plugins_afficher_repertoires_dist($url_page, $liste_plugins, $liste_plugins_actifs) {
	$ligne_plug = charger_fonction('afficher_plugin', 'plugins');
	$racine = basename(_DIR_PLUGINS);
	$init_dir = $current_dir = '';
	// liste des repertoires deplies : construit en remontant l'arbo de chaque plugin actif
	// des qu'un path est deja note deplie on s'arrete
	$deplie = [$racine => true];
	$fast_liste_plugins_actifs = [];
	foreach ($liste_plugins_actifs as $key => $plug) {
		$chemin_plug = chemin_plug($racine, $plug);
		$fast_liste_plugins_actifs[$chemin_plug] = true;
		$dir = dirname($chemin_plug);
		$maxiter = 100;
		while (strlen($dir) && !isset($deplie[$dir]) && $dir != $racine && $maxiter-- > 0) {
			$deplie[$dir] = true;
			$dir = dirname($dir);
		}
	}

	// index repertoires --> plugin
	$dir_index = [];
	foreach ($liste_plugins as $key => $plug) {
		$liste_plugins[$key] = chemin_plug($racine, $plug);
		$dir_index[dirname($liste_plugins[$key])][] = $key;
	}

	$visible = @isset($deplie[$current_dir]);
	$maxiter = 1000;

	$res = '';
	while ((is_countable($liste_plugins) ? count($liste_plugins) : 0) && $maxiter--) {
		// le rep suivant
		$dir = dirname(reset($liste_plugins));
		if ($dir != $current_dir) {
			$res .= tree_open_close_dir($current_dir, $dir, $deplie);
		}

		// d'abord tous les plugins du rep courant
		if (isset($dir_index[$current_dir])) {
			foreach ($dir_index[$current_dir] as $key) {
				$plug = $liste_plugins[$key];
				$actif = @isset($fast_liste_plugins_actifs[$plug]);
				$id = substr(md5($plug), 0, 16);
				$res .= $ligne_plug(
					$url_page,
					str_replace(_DIR_PLUGINS, '', _DIR_RACINE . $plug),
					$actif,
					'menu-entree'
				) . "\n";
				unset($liste_plugins[$key]);
			}
		}
	}
	$res .= tree_open_close_dir($current_dir, $init_dir, true);

	return "<ul class='menu-liste plugins'>"
	. $res
	. '</ul>';
}


// vraiment n'importe quoi la gestion des chemins des plugins
// une fonction pour aider...
function chemin_plug($racine, $plug) {
	return preg_replace(',[^/]+/\.\./,', '', "$racine/$plug");
}

function tree_open_close_dir(&$current, $target, $deplie = []) {
	if ($current == $target) {
		return '';
	}
	$tcur = explode('/', $current);
	$ttarg = explode('/', $target);
	$tcom = [];
	$output = '';
	// la partie commune
	while (reset($tcur) == reset($ttarg)) {
		$tcom[] = array_shift($tcur);
		array_shift($ttarg);
	}
	// fermer les repertoires courant jusqu'au point de fork
	while ($close = array_pop($tcur)) {
		$output .= "</ul>\n";
		$output .= fin_block();
		$output .= "</li>\n";
	}
	$chemin = '';
	if (count($tcom)) {
		$chemin .= implode('/', $tcom) . '/';
	}
	// ouvrir les repertoires jusqu'a la cible
	while ($open = array_shift($ttarg)) {
		$visible = @isset($deplie[$chemin . $open]);
		$chemin .= $open . '/';
		$output .= '<li>';
		$output .= bouton_block_depliable($chemin, $visible);
		$output .= debut_block_depliable($visible);

		$output .= "<ul>\n";
	}
	$current = $target;

	return $output;
}
