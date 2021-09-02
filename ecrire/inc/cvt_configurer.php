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
 * Les formulaires CVT de configuration.
 *
 * Prendre en compte les `#FORMULAIRE_CONFIGURER_XX`
 * dans les squelettes de SPIP
 *
 * @package SPIP\Core\Formulaires\CVT\Configurer
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/config');

/**
 * Proposer un chargement par defaut pour les #FORMULAIRE_CONFIGURER_XXX
 *
 * @param array $flux
 * @return array
 */
function cvtconf_formulaire_charger($flux) {
	if (
		$form = $flux['args']['form']
		and strncmp($form, 'configurer_', 11) == 0 // un #FORMULAIRE_CONFIGURER_XXX
	) {
		// Pour tous les formulaires CONFIGURER, ayant une fonction charger ou pas, on teste si autorisé
		include_spip('inc/autoriser');
		if (!autoriser('configurer', '_' . substr($form, 11))) {
			return false;
		}

		// S'il n'y a pas de fonction charger(), on génère un contexte automatiquement
		if (!charger_fonction('charger', "formulaires/$form/", true)) {
			$flux['data'] = cvtconf_formulaires_configurer_recense($form);
			$flux['data']['editable'] = true;
			if (_request('var_mode') == 'configurer' and autoriser('webmestre')) {
				if (!_AJAX) {
					var_dump($flux['data']);
				}
				// reinjecter pour la trace au traitement
				$flux['data']['_hidden'] = "<input type='hidden' name='var_mode' value='configurer' />";
			}
		}
	}

	return $flux;
}

/**
 * Proposer un traitement par defaut pour les #FORMULAIRE_CONFIGURER_XXX
 *
 * @param array $flux
 * @return array
 */
function cvtconf_formulaire_traiter($flux) {
	if (
		$form = $flux['args']['form']
		and strncmp($form, 'configurer_', 11) == 0 // un #FORMULAIRE_CONFIGURER_XXX
		and !charger_fonction('traiter', "formulaires/$form/", true) // sans fonction traiter()
	) {
		$trace = cvtconf_formulaires_configurer_enregistre($form, $flux['args']['args']);
		$flux['data'] = ['message_ok' => _T('config_info_enregistree') . $trace, 'editable' => true];
	}

	return $flux;
}

/**
 * Enregistrer les donnees d'un formulaire $form appele avec les arguments $args
 * Cette fonction peut etre appellee manuellement et explicitement depuis la fonction traiter()
 * d'un formulaire configurer_xxx dont on veut personaliser le traitement
 * sans reecrire le stockage des donnees
 *
 * @param string $form
 *   nom du formulaire "configurer_xxx"
 * @param array $args
 *   arguments de l'appel de la fonction traiter ($args = func_get_args();)
 * @return string
 */
function cvtconf_formulaires_configurer_enregistre($form, $args) {
	$valeurs = [];
	// charger les valeurs
	// ce qui permet de prendre en charge une fonction charger() existante
	// qui prend alors la main sur l'auto detection
	if ($charger_valeurs = charger_fonction('charger', "formulaires/$form/", true)) {
		$valeurs = call_user_func_array($charger_valeurs, $args);
	}
	$valeurs = pipeline(
		'formulaire_charger',
		[
			'args' => ['form' => $form, 'args' => $args, 'je_suis_poste' => false],
			'data' => $valeurs
		]
	);
	// ne pas stocker editable !
	unset($valeurs['editable']);

	// recuperer les valeurs postees
	$store = [];
	foreach ($valeurs as $k => $v) {
		if (substr($k, 0, 1) !== '_') {
			$store[$k] = _request($k);
		}
	}

	return cvtconf_configurer_stocker($form, $valeurs, $store);
}

/**
 * Définir la règle de conteneur, en fonction de la présence de certaines données
 *
 * - `_meta_table` : nom de la table `spip_metas` ou stocker (par défaut 'meta')
 * - `_meta_casier` : nom du casier dans lequel sérialiser (par défaut xx de `formulaire_configurer_xx`)
 * - `_meta_prefixe` : préfixer les `meta` (alternative au casier) dans la table des meta (par defaur rien)
 * - `_meta_stockage` : Méthode externe de stockage. Aucune n'est fournie par le core.
 *
 * @param string $form
 * @param array $valeurs
 * @return array
 */
function cvtconf_definir_configurer_conteneur($form, $valeurs) {
	// stocker en base
	// par defaut, dans un casier serialize dans spip_meta (idem CFG)
	$casier = substr($form, 11);
	$table = 'meta';
	$prefixe = '';
	$stockage = '';

	if (isset($valeurs['_meta_casier'])) {
		$casier = $valeurs['_meta_casier'];
	}
	if (isset($valeurs['_meta_prefixe'])) {
		$prefixe = $valeurs['_meta_prefixe'];
	}
	if (isset($valeurs['_meta_stockage'])) {
		$stockage = $valeurs['_meta_stockage'] . '::';
	}

	// si on indique juste une table, il faut vider les autres proprietes
	// car par defaut on utilise ni casier ni prefixe dans ce cas
	if (isset($valeurs['_meta_table'])) {
		$table = $valeurs['_meta_table'];
		$casier = (isset($valeurs['_meta_casier']) ? $valeurs['_meta_casier'] : '');
	}

	return [$table, $casier, $prefixe, $stockage];
}

/**
 * Retrouver les champs d'un formulaire en parcourant son squelette
 * et en extrayant les balises input, textarea, select
 *
 * @param string $form
 * @return array
 */
function cvtconf_formulaires_configurer_recense($form) {
	$valeurs = ['editable' => ' '];

	// sinon cas analyse du squelette
	if (
		$f = find_in_path($form . '.' . _EXTENSION_SQUELETTES, 'formulaires/')
		and lire_fichier($f, $contenu)
	) {
		for ($i = 0; $i < 2; $i++) {
			// a la seconde iteration, evaluer le fond avec les valeurs deja trouvees
			// permet de trouver aussi les name="#GET{truc}"
			if ($i == 1) {
				$contenu = recuperer_fond("formulaires/$form", $valeurs);
			}

			$balises = array_merge(
				extraire_balises($contenu, 'input'),
				extraire_balises($contenu, 'textarea'),
				extraire_balises($contenu, 'select')
			);

			foreach ($balises as $b) {
				if (
					$n = extraire_attribut($b, 'name')
					and preg_match(',^([\w\-]+)(\[\w*\])?$,', $n, $r)
					and !in_array($n, ['formulaire_action', 'formulaire_action_args'])
					and extraire_attribut($b, 'type') !== 'submit'
				) {
					$valeurs[$r[1]] = '';
					// recuperer les valeurs _meta_xx qui peuvent etre fournies
					// en input hidden dans le squelette
					if (strncmp($r[1], '_meta_', 6) == 0) {
						$valeurs[$r[1]] = extraire_attribut($b, 'value');
					}
				}
			}
		}
	}


	cvtconf_configurer_lire_meta($form, $valeurs);

	return $valeurs;
}

/**
 * Stocker les metas
 *
 * @param string $form
 * @param array $valeurs
 * @param array $store
 * @return string
 */
function cvtconf_configurer_stocker($form, $valeurs, $store) {
	$trace = '';
	list($table, $casier, $prefixe, $stockage) = cvtconf_definir_configurer_conteneur($form, $valeurs);
	// stocker en base
	// par defaut, dans un casier serialize dans spip_meta (idem CFG)
	if (!isset($GLOBALS[$table])) {
		lire_metas($table);
	}

	$prefixe = ($prefixe ? $prefixe . '_' : '');
	$table = ($table) ? "/$table/" : '';
	$casier = ($casier) ? rtrim($casier, '/') . '/' : ''; // slash final, sinon rien

	foreach ($store as $k => $v) {
		ecrire_config("$stockage$table$prefixe$casier$k", $v);
		if (_request('var_mode') == 'configurer' and autoriser('webmestre')) {
			$trace .= "<br />table $table : " . $prefixe . $k . " = $v;";
		}
	}

	return $trace;
}

/**
 * Lecture en base des metas d'un form
 *
 * @param string $form
 * @param array $valeurs
 */
function cvtconf_configurer_lire_meta($form, &$valeurs) {
	list($table, $casier, $prefixe, $stockage) = cvtconf_definir_configurer_conteneur($form, $valeurs);

	$table = ($table) ? "/$table/" : '';
	$prefixe = ($prefixe ? $prefixe . '_' : '');
	if ($casier) {
		$meta = lire_config("$stockage$table$prefixe$casier");
		$prefixe = '';
	} else {
		$table = rtrim($table, '/');
		$meta = lire_config("$stockage$table");
	}

	foreach ($valeurs as $k => $v) {
		if (substr($k, 0, 1) !== '_') {
			$valeurs[$k] = (isset($meta[$prefixe . $k]) ? $meta[$prefixe . $k] : null);
		}
	}
}
