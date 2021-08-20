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

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Evaluer la page produite par un squelette
 *
 * Évalue une page pour la transformer en texte statique
 * Elle peut contenir un < ?xml a securiser avant eval
 * ou du php d'origine inconnue
 *
 * Attention cette partie eval() doit impérativement
 * être déclenchée dans l'espace des globales (donc pas
 * dans une fonction).
 *
 * @param array $page
 * @return bool
 */
$res = true;

// Cas d'une page contenant du PHP :
if (empty($page['process_ins']) or $page['process_ins'] != 'html') {
	include_spip('inc/lang');

	// restaurer l'etat des notes avant calcul
	if (
		isset($page['notes'])
		and $page['notes']
		and $notes = charger_fonction('notes', 'inc', true)
	) {
		$notes($page['notes'], 'restaurer_etat');
	}
	ob_start();
	if (strpos($page['texte'], '?xml') !== false) {
		$page['texte'] = str_replace('<' . '?xml', "<\1?xml", $page['texte']);
	}

	try {
		$res = eval('?' . '>' . $page['texte']);
		// error catching 5.2<=PHP<7
		if (
			$res === false
			and function_exists('error_get_last')
			and ($erreur = error_get_last())
		) {
			$code = $page['texte'];
			$GLOBALS['numero_ligne_php'] = 1;
			if (!function_exists('numerote_ligne_php')) {
				function numerote_ligne_php($match) {
					$GLOBALS['numero_ligne_php']++;
					return "\n/*" . str_pad($GLOBALS['numero_ligne_php'], 3, '0', STR_PAD_LEFT) . '*/';
				}
			}
			$code = '/*001*/' . preg_replace_callback(",\n,", 'numerote_ligne_php', $code);
			$code = trim(highlight_string($code, true));
			erreur_squelette('L' . $erreur['line'] . ': ' . $erreur['message'] . '<br />' . $code, [$page['source'],'',$erreur['file'],'',$GLOBALS['spip_lang']]);
			$page['texte'] = '<!-- Erreur -->';
		}
		else {
			$page['texte'] = ob_get_contents();
		}
	}
	catch (Exception $e) {
		$code = $page['texte'];
		$GLOBALS['numero_ligne_php'] = 1;
		if (!function_exists('numerote_ligne_php')) {
			function numerote_ligne_php($match) {
				$GLOBALS['numero_ligne_php']++;
				return "\n/*" . str_pad($GLOBALS['numero_ligne_php'], 3, '0', STR_PAD_LEFT) . '*/';
			}
		}
		$code = '/*001*/' . preg_replace_callback(",\n,", 'numerote_ligne_php', $code);
		$code = trim(highlight_string($code, true));
		erreur_squelette('L' . $e->getLine() . ': ' . $e->getMessage() . '<br />' . $code, [$page['source'],'',$e->getFile(),'',$GLOBALS['spip_lang']]);
		$page['texte'] = '<!-- Erreur -->';
	}
	ob_end_clean();

	$page['process_ins'] = 'html';

	if (strpos($page['texte'], '?xml') !== false) {
		$page['texte'] = str_replace("<\1?xml", '<' . '?xml', $page['texte']);
	}
}

page_base_href($page['texte']);
