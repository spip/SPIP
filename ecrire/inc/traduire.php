<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

use Spip\I18n\Description;

/**
 * Outils pour la traduction et recherche de traductions
 *
 * @package SPIP\Core\Traductions
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Rechercher tous les lang/file dans le path
 * qui seront ensuite chargés dans l'ordre du path
 *
 * Version dédiée et optimisée pour cet usage de find_in_path
 *
 * @see find_in_path()
 *
 * @staticvar array $dirs
 *
 * @param string $file
 *     Nom du fichier cherché, tel que `mots_fr.php`
 * @param string $dirname
 *     Nom du répertoire de recherche
 * @return array
 *     Liste des fichiers de langue trouvés, dans l'ordre des chemins
 */
function find_langs_in_path($file, $dirname = 'lang') {
	static $dirs = [];
	$liste = [];
	foreach (creer_chemin() as $dir) {
		if (!isset($dirs[$a = $dir . $dirname])) {
			$dirs[$a] = (is_dir($a) || !$a);
		}
		if ($dirs[$a] && is_readable($a .= $file)) {
			$liste[] = $a;
		}
	}

	return array_reverse($liste);
}

/**
 * Recherche le ou les fichiers de langue d'un module de langue
 *
 * @param string $module
 *     Nom du module de langue, tel que `mots` ou `ecrire`
 * @param string $lang
 *     Langue dont on veut obtenir les traductions.
 *     Paramètre optionnel uniquement si le module est `local`
 * @return array
 *     Liste des fichiers touvés pour ce module et cette langue.
 **/
function chercher_module_lang($module, $lang = '') {
	if ($lang) {
		$lang = '_' . $lang;
	}

	// 1) dans un repertoire nomme lang/ se trouvant sur le chemin
	if (
		$f = ($module == 'local'
		? find_in_path($module . $lang . '.php', 'lang/')
		: find_langs_in_path($module . $lang . '.php', 'lang/'))
	) {
		return is_array($f) ? $f : [$f];
	}

	// 2) directement dans le chemin (old style, uniquement pour local)
	return ($module == 'local' || strpos($module, '/'))
		? (($f = find_in_path($module . $lang . '.php')) ? [$f] : false)
		: false;
}

/**
 * Charge en mémoire les couples cle/traduction d'un module de langue
 * et une langue donnée
 *
 * Interprête un fichier de langue pour le module et la langue désignée
 * s'il existe, et sinon se rabat soit sur la langue principale du site
 * (définie par la meta `langue_site`), soit sur le français.
 *
 * Définit la globale `idx_lang` qui sert à la lecture du fichier de langue
 * (include) et aux surcharges via `surcharger_langue()`
 *
 * @uses chercher_module_lang()
 * @uses surcharger_langue()
 *
 * @param string $lang Code de langue
 * @param string $module Nom du module de langue
 * @return void
 **/
function charger_langue($lang, $module = 'spip') {
	static $langs = [];
	$var = 'i18n_' . $module . '_' . $lang;
	if (!isset($langs[$lang])) {
		$langs[$lang] = [];
		if ($lang) {
			$langs[$lang][] = $lang;
			if (str_contains($lang, '_')) {
				$l = explode('_', $lang);
				$langs[$lang][] = reset($l);
			}
		}
		$langs[$lang][] = $GLOBALS['meta']['langue_site'];
		$langs[$lang][] = _LANGUE_PAR_DEFAUT;
	}
	foreach ($langs[$lang] as $l) {
		if ($fichiers_lang = chercher_module_lang($module, $l)) {
			$GLOBALS['idx_lang'] = 'i18n_' . $module . '_' . $l;
			$GLOBALS[$GLOBALS['idx_lang']] = lire_fichier_langue(array_shift($fichiers_lang));
			surcharger_langue($fichiers_lang);
			if ($l !== $lang) {
				$GLOBALS[$var] = &$GLOBALS['i18n_' . $module . '_' . $l];
			}
			$GLOBALS['lang_' . $var] = $l;
			#spip_log("module de langue : {$module}_$l.php", 'traduire');
			break;
		}
	}
}

/**
 * Retourne les entrées d’un fichier de langue
 *
 * Les fichiers de langue retournent soit un array [ cle => valeur ],
 * soit peuplent une globale `$GLOBALS[$GLOBALS['idx_lang']]`.
 *
 * @return string Chemin du fichier de langue (un fichier PHP)
 * @return array<string, string>
 */
function lire_fichier_langue(string $fichier): array {
	$idx_lang_before = $GLOBALS['idx_lang'] ?? null;
	$idx_lang_tmp = ($GLOBALS['idx_lang'] ?? 'lang') . '@temporaire';
	$GLOBALS['idx_lang'] = $idx_lang_tmp;
	$idx_lang = include $fichier;
	$GLOBALS['idx_lang'] = $idx_lang_before;
	if (!is_array($idx_lang)) {
		if (isset($GLOBALS[$idx_lang_tmp]) && is_array($GLOBALS[$idx_lang_tmp])) {
			$idx_lang = $GLOBALS[$idx_lang_tmp];
		} else {
			$idx_lang = [];
			spip_log(sprintf('Fichier de langue incorrect : %s', $fichier), _LOG_ERREUR);
		}
		unset($GLOBALS[$idx_lang_tmp]);
	}
	return $idx_lang;
}

/**
 * Surcharger le fichier de langue courant avec un ou plusieurs autres
 *
 * Charge chaque fichier de langue dont les chemins sont transmis et
 * surcharge les infos de cette langue/module déjà connues par les nouvelles
 * données chargées. Seule les clés nouvelles ou modifiées par la
 * surcharge sont impactées (les clés non présentes dans la surcharge
 * ne sont pas supprimées !).
 *
 * La fonction suppose la présence de la globale `idx_lang` indiquant
 * la destination des couples de traduction, de la forme
 * `i18n_{$module}_{$lang}`
 *
 * @param array $fichiers
 *    Liste des chemins de fichiers de langue à surcharger.
 **/
function surcharger_langue($fichiers) {
	static $surcharges = [];
	if (!isset($GLOBALS['idx_lang'])) {
		return;
	}

	if (!is_array($fichiers)) {
		$fichiers = [$fichiers];
	}
	if ($fichiers === []) {
		return;
	}
	foreach ($fichiers as $fichier) {
		if (!isset($surcharges[$fichier])) {
			$surcharges[$fichier] = lire_fichier_langue($fichier);
		}
		if (is_array($surcharges[$fichier])) {
			$GLOBALS[$GLOBALS['idx_lang']] ??= [];
			$GLOBALS[$GLOBALS['idx_lang']] = array_merge(
				$GLOBALS[$GLOBALS['idx_lang']],
				$surcharges[$fichier]
			);
		}
	}
}

/**
 * Traduire une chaine internationalisée
 *
 * Lorsque la langue demandée n'a pas de traduction pour la clé de langue
 * transmise, la fonction cherche alors la traduction dans la langue
 * principale du site (défini par la meta `langue_site`), puis, à défaut
 * dans la langue française.
 *
 * Les traductions sont cherchées dans les modules de langue indiqués.
 * Par exemple le module `mots` dans la clé `mots:titre_mot`, pour une
 * traduction `es` (espagnol) provoquera une recherche dans tous les fichiers
 * `lang\mots_es.php`.
 *
 * Des surcharges locales peuvent être présentes également
 * dans les fichiers `lang/local_es.php` ou `lang/local.php`
 *
 * @note
 *   Les couples clé/traductions déjà connus sont sauvés en interne
 *   dans les globales `i18n_{$module}_{$lang}` tel que `i18n_mots_es`
 *   et sont également sauvés dans la variable statique `deja_vu`
 *   de cette fonction.
 *
 * @uses charger_langue()
 * @uses chercher_module_lang()
 * @uses surcharger_langue()
 *
 * @param string $ori
 *     Clé de traduction, tel que `bouton_enregistrer` ou `mots:titre_mot`
 * @param string $lang
 *     Code de langue, la traduction doit se faire si possible dans cette langue
 * @param bool $raw
 *     - false : retourne le texte (par défaut)
 *     - true  : retourne une description de la chaine de langue (module, texte, langue)
 * @return string|Description
 *     - string : Traduction demandée. Chaîne vide si aucune traduction trouvée.
 *     - Description : traduction et description (texte, module, langue)
 **/
function inc_traduire_dist($ori, $lang, $raw = false) {
	static $deja_vu = [];
	static $local = [];

	if (isset($deja_vu[$lang][$ori]) && _request('var_mode') != 'traduction') {
		return $raw ? $deja_vu[$lang][$ori] : $deja_vu[$lang][$ori]->texte;
	}

	// modules demandes explicitement <xxx|yyy|zzz:code> cf MODULES_IDIOMES
	if (strpos($ori, ':')) {
		[$modules, $code] = explode(':', $ori, 2);
		$modules = explode('|', $modules);
		$ori_complet = $ori;
	} else {
		$modules = ['spip', 'ecrire'];
		$code = $ori;
		$ori_complet = implode('|', $modules) . ':' . $ori;
	}

	$desc = new Description();

	// parcourir tous les modules jusqu'a ce qu'on trouve
	foreach ($modules as $module) {
		$var = 'i18n_' . $module . '_' . $lang;

		if (empty($GLOBALS[$var])) {
			charger_langue($lang, $module);
			// surcharges persos -- on cherche
			// (lang/)local_xx.php et/ou (lang/)local.php ...
			if (!isset($local['local_' . $lang])) {
				// redéfinir la langue en cours pour les surcharges (chercher_langue a pu le changer)
				$GLOBALS['idx_lang'] = $var;
				// ... (lang/)local_xx.php
				$local['local_' . $lang] = chercher_module_lang('local', $lang);
			}
			if ($local['local_' . $lang]) {
				surcharger_langue($local['local_' . $lang]);
			}
			// ... puis (lang/)local.php
			if (!isset($local['local'])) {
				$local['local'] = chercher_module_lang('local');
			}
			if ($local['local']) {
				surcharger_langue($local['local']);
			}
		}

		if (isset($GLOBALS[$var][$code])) {
			$desc->code = $code;
			$desc->module = $module;
			$desc->langue = $GLOBALS['lang_' . $var] ?? $lang;
			$desc->texte = $GLOBALS[$var][$code];
			break;
		}
	}

	if (!$desc->corrections) {
		$desc->corrections = true;
		// Retour aux sources si la chaine est absente dans la langue cible ;
		// on essaie d'abord la langue du site, puis a defaut la langue fr
		if (
			($desc->texte === null || !strlen($desc->texte))
			&& $lang !== _LANGUE_PAR_DEFAUT
		) {
			if ($lang !== $GLOBALS['meta']['langue_site']) {
				$desc = inc_traduire_dist($ori, $GLOBALS['meta']['langue_site'], true);
			} else {
				$desc = inc_traduire_dist($ori, _LANGUE_PAR_DEFAUT, true);
			}
		}

		// Supprimer la mention <NEW> ou <MODIF>
		if ($desc->texte && str_starts_with($desc->texte, '<')) {
			$desc->texte = str_replace(['<NEW>', '<MODIF>'], [], $desc->texte);
		}

		// Si on n'est pas en utf-8, la chaine peut l'etre...
		// le cas echeant on la convertit en entites html &#xxx;
		if (
			(!isset($GLOBALS['meta']['charset']) || $GLOBALS['meta']['charset'] !== 'utf-8')
			&& preg_match(',[\x7f-\xff],S', $desc->texte)
		) {
			include_spip('inc/charsets');
			$desc->texte = charset2unicode($desc->texte, 'utf-8');
		}
	}

	if (_request('var_mode') == 'traduction') {
		$desc = definir_details_traduction($desc, $ori_complet);
	} else {
		$deja_vu[$lang][$ori] = $desc;
	}

	return $raw ? $desc : $desc->texte;
}

/**
 * Modifie le texte de traduction pour indiquer des éléments
 * servant au debug de celles-ci. (pour var_mode=traduction)
 *
 * @param Description $desc
 * @param string $modules Les modules qui étaient demandés
 * @return Description
 */
function definir_details_traduction($desc, $modules) {
	if (!$desc->mode && $desc->texte) {
		// ne pas modifier 2 fois l'affichage
		$desc->mode = 'traduction';
		$classe = 'debug-traduction' . ($desc->module == 'ecrire' ? '-prive' : '');
		$desc->texte = '<span '
			. 'lang=' . $desc->langue
			. ' class=' . $classe
			. ' data-module=' . $desc->module
			. ' data-code=' . $desc->code
			. ' title=' . $modules . '(' . $desc->langue . ')>'
			. $desc->texte
			. '</span>';
		$desc->texte = str_replace(
			["$desc->module:", "$desc->module|"],
			["*$desc->module*:", "*$desc->module*|"],
			$desc->texte
		);
	}
	return $desc;
}
