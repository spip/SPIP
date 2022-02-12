<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion des textes et raccourcis SPIP
 *
 * @package SPIP\Core\Texte
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/texte_mini');
include_spip('inc/lien');

/*************************************************************************************************************************
 * Fonctions inutilisees en dehors de inc/texte
 *
 */

/**
 * Raccourcis dépendant du sens de la langue
 *
 * @return array Tablea ('','')
 */
function definir_raccourcis_alineas() {
	return ['', ''];
}


/**
 * Traitement des raccourcis de tableaux
 *
 * Ne fait rien ici. Voir plugin Textwheel.
 *
 * @param string $bloc
 * @return string
 */
function traiter_tableau($bloc) {
	return $bloc;
}


/**
 * Traitement des listes
 *
 * Ne fais rien. Voir Plugin Textwheel.
 * (merci a Michael Parienti)
 *
 * @param string $texte
 * @return string
 */
function traiter_listes($texte) {
	return $texte;
}

/**
 * Nettoie un texte, traite les raccourcis autre qu'URL, la typo, etc.
 *
 * Ne fais rien ici. Voir plugin Textwheel.
 *
 * @pipeline_appel pre_propre
 * @pipeline_appel post_propre
 *
 * @param string $letexte
 * @return string
 */
function traiter_raccourcis($letexte) {

	// Appeler les fonctions de pre_traitement
	$letexte = pipeline('pre_propre', $letexte);

	// APPELER ICI UN PIPELINE traiter_raccourcis ?
	// $letexte = pipeline('traiter_raccourcis', $letexte);

	// Appeler les fonctions de post-traitement
	$letexte = pipeline('post_propre', $letexte);

	return $letexte;
}

/*************************************************************************************************************************
 * Fonctions utilisees en dehors de inc/texte
 */


/**
 * Échapper et affichier joliement les `<script` et `<iframe`...
 *
 * @param string $t
 * @param string $class Attributs HTML du conteneur à ajouter
 * @return string
 */
function echappe_js($t, $class = ' class = "echappe-js"') {
	foreach (['script', 'iframe'] as $tag) {
		if (
			stripos($t, (string) "<$tag") !== false
			and preg_match_all(',<' . $tag . '.*?($|</' . $tag . '.),isS', $t, $r, PREG_SET_ORDER)
		) {
			foreach ($r as $regs) {
				$t = str_replace(
					$regs[0],
					"<code$class>" . nl2br(spip_htmlspecialchars($regs[0])) . '</code>',
					$t
				);
			}
		}
	}

	return $t;
}


/**
 * Empêcher l'exécution de code PHP et JS
 *
 * Sécurité : empêcher l'exécution de code PHP, en le transformant en joli code
 * dans l'espace privé. Cette fonction est aussi appelée par propre et typo.
 *
 * De la même manière, la fonction empêche l'exécution de JS mais selon le mode
 * de protection passe en argument
 *
 * Il ne faut pas désactiver globalement la fonction dans l'espace privé car elle protège
 * aussi les balises des squelettes qui ne passent pas forcement par propre ou typo après
 * si elles sont appelées en direct
 *
 * @param string $arg
 *     Code à protéger
 * @param int $mode_filtre
 *     Mode de protection
 *       -1 : protection dans l'espace privé et public
 *       0  : protection dans l'espace public
 *       1  : aucune protection
 *     utilise la valeur de la globale filtrer_javascript si non fourni
 * @return string
 *     Code protégé
 **/
function interdire_scripts($arg, $mode_filtre = null) {
	// on memorise le resultat sur les arguments non triviaux
	static $dejavu = [];

	// Attention, si ce n'est pas une chaine, laisser intact
	if (!$arg or !is_string($arg) or !strstr($arg, '<')) {
		return $arg;
	}

	if (is_null($mode_filtre) or !in_array($mode_filtre, [-1, 0, 1])) {
		$mode_filtre = $GLOBALS['filtrer_javascript'];
	}

	if (isset($dejavu[$mode_filtre][$arg])) {
		return $dejavu[$mode_filtre][$arg];
	}

	// echapper les tags asp/php
	$t = str_replace('<' . '%', '&lt;%', $arg);

	// echapper le php
	$t = str_replace('<' . '?', '&lt;?', $t);

	// echapper le < script language=php >
	$t = preg_replace(',<(script\b[^>]+\blanguage\b[^\w>]+php\b),UimsS', '&lt;\1', $t);

	// Pour le js, trois modes : parano (-1), prive (0), ok (1)
	switch ($mode_filtre) {
		case 0:
			if (!_DIR_RESTREINT) {
				$t = echappe_js($t);
			}
			break;
		case -1:
			$t = echappe_js($t);
			break;
	}

	// pas de <base href /> svp !
	$t = preg_replace(',<(base\b),iS', '&lt;\1', $t);

	// Reinserer les echappements des modeles
	if (defined('_PROTEGE_JS_MODELES')) {
		$t = echappe_retour($t, 'javascript' . _PROTEGE_JS_MODELES);
	}
	if (defined('_PROTEGE_PHP_MODELES')) {
		$t = echappe_retour($t, 'php' . _PROTEGE_PHP_MODELES);
	}

	return $dejavu[$mode_filtre][$arg] = $t;
}


/**
 * Applique la typographie générale
 *
 * Effectue un traitement pour que les textes affichés suivent les règles
 * de typographie. Fait une protection préalable des balises HTML et SPIP.
 * Transforme les balises `<multi>`
 *
 * @filtre
 * @uses traiter_modeles()
 * @uses corriger_typo()
 * @uses echapper_faux_tags()
 * @see  propre()
 *
 * @param string $letexte
 *     texte d'origine
 * @param bool|string $echapper
 *     Échapper ?
 * @param string|null $connect
 *     Nom du connecteur à la bdd
 * @param array $env
 *     Environnement (pour les calculs de modèles)
 * @return string $t
 *     texte transformé
 **/
function typo($letexte, $echapper = true, $connect = null, $env = []) {
	// Plus vite !
	if (!$letexte) {
		return $letexte;
	}

	// les appels directs a cette fonction depuis le php de l'espace
	// prive etant historiquement ecrit sans argment $connect
	// on utilise la presence de celui-ci pour distinguer les cas
	// ou il faut passer interdire_script explicitement
	// les appels dans les squelettes (de l'espace prive) fournissant un $connect
	// ne seront pas perturbes
	$interdire_script = false;
	if (is_null($connect)) {
		$connect = '';
		$interdire_script = true;
		$env['espace_prive'] = test_espace_prive();
	}

	// Echapper les codes <html> etc
	if ($echapper) {
		$letexte = echappe_html($letexte, 'TYPO');
	}

	//
	// Installer les modeles, notamment images et documents ;
	//
	// NOTE : propre() ne passe pas par ici mais directement par corriger_typo
	// cf. inc/lien

	$letexte = traiter_modeles($mem = $letexte, false, $echapper ? 'TYPO' : '', $connect ?? '', null, $env);
	if ($letexte != $mem) {
		$echapper = true;
	}
	unset($mem);

	$letexte = corriger_typo($letexte);
	$letexte = echapper_faux_tags($letexte);

	// reintegrer les echappements
	if ($echapper) {
		$letexte = echappe_retour($letexte, 'TYPO');
	}

	// Dans les appels directs hors squelette, securiser ici aussi
	if ($interdire_script) {
		$letexte = interdire_scripts($letexte);
	}

	// Dans l'espace prive on se mefie de tout contenu dangereux
	// https://core.spip.net/issues/3371
	// et aussi dans l'espace public si la globale filtrer_javascript = -1
	// https://core.spip.net/issues/4166
	if (
		$GLOBALS['filtrer_javascript'] == -1
		or (isset($env['espace_prive']) and $env['espace_prive'] and $GLOBALS['filtrer_javascript'] <= 0)
	) {
		$letexte = echapper_html_suspect($letexte, [], $connect, $env);
	}

	return $letexte;
}

// Correcteur typographique
define('_TYPO_PROTEGER', "!':;?~%-");
define('_TYPO_PROTECTEUR', "\x1\x2\x3\x4\x5\x6\x7\x8");

define('_TYPO_BALISE', ',</?[a-z!][^<>]*[' . preg_quote(_TYPO_PROTEGER) . '][^<>]*>,imsS');

/**
 * Corrige la typographie
 *
 * Applique les corrections typographiques adaptées à la langue indiquée.
 *
 * @pipeline_appel pre_typo
 * @pipeline_appel post_typo
 * @uses corriger_caracteres()
 * @uses corriger_caracteres()
 *
 * @param string $letexte texte
 * @param string $lang Langue
 * @return string texte
 */
function corriger_typo($letexte, $lang = '') {

	// Plus vite !
	if (!$letexte) {
		return $letexte;
	}

	$letexte = pipeline('pre_typo', $letexte);

	// Caracteres de controle "illegaux"
	$letexte = corriger_caracteres($letexte);

	// Proteger les caracteres typographiques a l'interieur des tags html
	if (preg_match_all(_TYPO_BALISE, $letexte, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$insert = $reg[0];
			// hack: on transforme les caracteres a proteger en les remplacant
			// par des caracteres "illegaux". (cf corriger_caracteres())
			$insert = strtr($insert, _TYPO_PROTEGER, _TYPO_PROTECTEUR);
			$letexte = str_replace($reg[0], $insert, $letexte);
		}
	}

	// trouver les blocs idiomes et les traiter à part
	$letexte = extraire_idiome($ei = $letexte, $lang, true);
	$ei = ($ei !== $letexte);

	// trouver les blocs multi et les traiter a part
	$letexte = extraire_multi($em = $letexte, $lang, true);
	$em = ($em !== $letexte);

	// Charger & appliquer les fonctions de typographie
	$typographie = charger_fonction(lang_typo($lang), 'typographie');
	$letexte = $typographie($letexte);

	// Les citations en une autre langue, s'il y a lieu
	if ($em) {
		$letexte = echappe_retour($letexte, 'multi');
	}
	if ($ei) {
		$letexte = echappe_retour($letexte, 'idiome');
	}

	// Retablir les caracteres proteges
	$letexte = strtr($letexte, _TYPO_PROTECTEUR, _TYPO_PROTEGER);

	// pipeline
	$letexte = pipeline('post_typo', $letexte);

	# un message pour abs_url - on est passe en mode texte
	$GLOBALS['mode_abs_url'] = 'texte';

	return $letexte;
}


/**
 * Paragrapher seulement
 *
 * /!\ appelée dans inc/filtres et public/composer
 *
 * Ne fait rien ici. Voir plugin Textwheel
 *
 * @param string $letexte
 * @param null $forcer
 * @return string
 */
function paragrapher($letexte, $forcer = true) {
	return $letexte;
}

/**
 * Harmonise les retours chariots et mange les paragraphes HTML
 *
 * Ne sert plus
 *
 * @param string $letexte texte
 * @return string texte
 **/
function traiter_retours_chariots($letexte) {
	$letexte = preg_replace(",\r\n?,S", "\n", $letexte);
	$letexte = preg_replace(',<p[>[:space:]],iS', "\n\n\\0", $letexte);
	$letexte = preg_replace(',</p[>[:space:]],iS', "\\0\n\n", $letexte);

	return $letexte;
}


/**
 * Transforme les raccourcis SPIP, liens et modèles d'un texte en code HTML
 *
 * Filtre à appliquer aux champs du type `#TEXTE*`
 *
 * @filtre
 * @uses echappe_html()
 * @uses expanser_liens()
 * @uses traiter_raccourcis()
 * @uses echappe_retour_modeles()
 * @see  typo()
 *
 * @param string $t
 *     texte avec des raccourcis SPIP
 * @param string|null $connect
 *     Nom du connecteur à la bdd
 * @param array $env
 *     Environnement (pour les calculs de modèles)
 * @return string $t
 *     texte transformé
 **/
function propre($t, $connect = null, $env = []) {
	// les appels directs a cette fonction depuis le php de l'espace
	// prive etant historiquement ecrits sans argment $connect
	// on utilise la presence de celui-ci pour distinguer les cas
	// ou il faut passer interdire_script explicitement
	// les appels dans les squelettes (de l'espace prive) fournissant un $connect
	// ne seront pas perturbes
	$interdire_script = false;
	if (is_null($connect)) {
		$connect = '';
		$interdire_script = true;
		$env['espace_prive'] = true;
	}

	if (!$t) {
		return strval($t);
	}

	// Dans l'espace prive on se mefie de tout contenu dangereux
	// avant echappement des balises <html>
	// https://core.spip.net/issues/3371
	// et aussi dans l'espace public si la globale filtrer_javascript = -1
	// https://core.spip.net/issues/4166
	if (
		$interdire_script
		or $GLOBALS['filtrer_javascript'] == -1
		or (!empty($env['espace_prive']) and $GLOBALS['filtrer_javascript'] <= 0)
		or (!empty($env['wysiwyg']) and $env['wysiwyg'] and $GLOBALS['filtrer_javascript'] <= 0)
	) {
		$t = echapper_html_suspect($t, ['strict' => false], $connect, $env);
	}
	$t = echappe_html($t);
	$t = expanser_liens($t, $connect, $env);
	$t = traiter_raccourcis($t);
	$t = echappe_retour_modeles($t, $interdire_script);

	return $t;
}
