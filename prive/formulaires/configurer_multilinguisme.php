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

function formulaires_configurer_multilinguisme_charger_dist() {
	$valeurs = [];
	$valeurs['multi_secteurs'] = $GLOBALS['meta']['multi_secteurs'];
	foreach (['multi_objets', 'gerer_trad_objets'] as $m) {
		$valeurs[$m] = explode(',', isset($GLOBALS['meta'][$m]) ? $GLOBALS['meta'][$m] : '');
	}

	if (
		count($valeurs['multi_objets'])
		or count(explode(',', $GLOBALS['meta']['langues_utilisees'])) > 1
	) {
		$selection = (is_null(_request('multi_objets')) ?
			explode(',', $GLOBALS['meta']['langues_multilingue']) : _request('langues_auth'));
		$valeurs['_langues'] = saisie_langues_utiles('langues_auth', $selection ? $selection : []);
		$valeurs['_nb_langues_selection'] = count($selection);
	}

	return $valeurs;
}


function formulaires_configurer_multilinguisme_traiter_dist() {
	$res = ['editable' => true];
	// un checkbox seul de name X non coche n'est pas poste.
	// on verifie le champ X_check qui indique que la checkbox etait presente dans le formulaire.
	foreach (['multi_secteurs'] as $m) {
		if (!is_null(_request($m . '_check'))) {
			ecrire_meta($m, _request($m) ? 'oui' : 'non');
		}
	}
	foreach (['multi_objets', 'gerer_trad_objets'] as $m) {
		if (!is_null($v = _request($m))) {
			// join et enlever la valeur vide ''
			ecrire_meta($m, implode(',', array_diff($v, [''])));
		}
	}

	if ($i = _request('langues_auth') and is_array($i)) {
		$i = array_unique(array_merge($i, explode(',', $GLOBALS['meta']['langues_utilisees'])));
		ecrire_meta('langues_multilingue', implode(',', $i));
	}
	$res['message_ok'] = _T('config_info_enregistree');

	return $res;
}

/**
 * Tester si une table supporte les langues (champ lang)
 *
 * @param string $table_sql
 * @return string
 */
function table_supporte_lang($table_sql) {
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($table_sql);
	if (!$desc or !isset($desc['field']['lang'])) {
		return '';
	}

	return ' ';
}

/**
 * Tester si une table supporte les traductions (champ id_trad)
 *
 * @param string $table_sql
 * @return string
 */
function table_supporte_trad($table_sql) {
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($table_sql);
	if (!$desc or !isset($desc['field']['id_trad'])) {
		return '';
	}

	return ' ';
}


function saisie_langues_utiles($name, $selection) {
	include_spip('inc/lang_liste');
	$langues = $GLOBALS['codes_langues'];

	$langues_installees = explode(',', $GLOBALS['meta']['langues_proposees']);
	$langues_trad = array_flip($langues_installees);

	$langues_bloquees = explode(',', $GLOBALS['meta']['langues_utilisees']);

	$res = '';

	$i = 0;
	foreach ($langues_bloquees as $code_langue) {
		$nom_langue = $langues[$code_langue];
		$res .= "<li class='choix "
			. alterner(++$i, 'odd', 'even')
			. (isset($langues_trad[$code_langue]) ? ' traduite' : '')
			. "'>"
			. "<input type='hidden' name='{$name}[]' value='$code_langue'>" // necessaire ...
			. "<input type='checkbox' name='{$name}[]' id='{$name}_$code_langue' value='$code_langue' checked='checked' disabled='disabled' />"
			. "<label for='{$name}_$code_langue'>" . $nom_langue . "&nbsp;&nbsp; <span class='code_langue'>[$code_langue]</span></label>"
			. '</li>';
	}

	if ($res) {
		$res = "<ul id='langues_bloquees'>" . $res . "</ul><div class='nettoyeur'></div>";
	}

	$res .= "<ul id='langues_proposees'>";

	$i = 0;
	$langues_bloquees = array_flip($langues_bloquees);
	foreach ($langues as $code_langue => $nom_langue) {
		if (!isset($langues_bloquees[$code_langue])) {
			$checked = (in_array($code_langue, $selection) ? ' checked="checked"' : '');
			$res .= "<li class='choix "
				. alterner(++$i, 'odd', 'even')
				. (isset($langues_trad[$code_langue]) ? ' traduite' : '')
				. "'>"
				. "<input type='checkbox' name='{$name}[]' id='{$name}_$code_langue' value='$code_langue'"
				. $checked
				. '/>'
				. "<label for='{$name}_$code_langue'"
				. ($checked ? " class='on'" : '')
				. '>'
				. $nom_langue . "&nbsp;&nbsp; <span class='code_langue'>[$code_langue]</span></label>"
				. '</li>';
		}
	}

	$res .= "</ul><div class='nettoyeur'></div>";

	return $res;
}
