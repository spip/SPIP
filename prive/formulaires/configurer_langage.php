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

function formulaires_configurer_langage_charger_dist() {

	include_spip('inc/lang');
	$GLOBALS['meta']['langues_proposees'] = '';
	init_langues();
	$langues = explode(',', $GLOBALS['meta']['langues_proposees']);


	$valeurs = [
		'var_lang_ecrire' => $GLOBALS['spip_lang'],
		'_langues' => $langues
	];

	return $valeurs;
}


function formulaires_configurer_langage_traiter_dist() {
	include_spip('action/converser');
	action_converser_changer_langue(true);

	refuser_traiter_formulaire_ajax();

	// on ne peut pas changer la langue pour tout le hit ici,
	// car CVT repasse derriere et retablit la langue avant l'appel a traiter()
	// il faut rediriger !
	return ['message_ok' => _T('config_info_enregistree'), 'editable' => true, 'redirect' => self()];
}

function afficher_langues_choix($langues, $name, $id, $selected) {
	include_spip('inc/lang');
	$ret = '';
	sort($langues);
	foreach ($langues as $l) {
		$checked = ($l == $selected) ? ' checked=\'checked\'' : '';
		$ret .= "<div class='choix'>"
			. "<input type='radio' name='$name' id='{$id}_$l' value='$l'$checked />"
			. "<label for='{$id}_$l'>" . traduire_nom_langue($l) . '</label>'
			. '</div>';
	}

	return $ret;
}
