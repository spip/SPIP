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
 * Fonctions génériques pour les balises formulaires
 *
 * @package SPIP\Core\Formulaires
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/filtres');
include_spip('inc/texte');

/**
 * Protéger les saisies d'un champ de formulaire
 *
 * Proteger les ' et les " dans les champs que l'on va injecter,
 * sans toucher aux valeurs sérialisées
 *
 * @see entites_html()
 * @param mixed $texte
 *     Saisie à protéger
 * @return string|array|null
 *     Saisie protégée
 **/
function protege_champ($texte) {
	if (is_array($texte)) {
		return array_map('protege_champ', $texte);
	} elseif ($texte === null) {
		return $texte;
	} elseif (is_bool($texte)) {
		return $texte ? '1' : '';
	} elseif (is_string($texte) and $texte) {
		if (preg_match(',^[abis]:\d+[:;],', $texte) and @unserialize($texte) !== false) {
			// ne pas corrompre une valeur serialize
			return $texte;
		} elseif (strpbrk($texte, "&\"'<>") !== false) {
			return spip_htmlspecialchars($texte, ENT_QUOTES);
		}
	}

	return $texte;
}

/**
 * Teste si un formulaire demandé possède un squelette pour l'afficher
 *
 * @see trouver_fond()
 * @param string $form
 *     Nom du formulaire
 * @return string|bool
 *     - string : chemin du squelette
 *     - false : pas de squelette trouvé
 **/
function existe_formulaire($form) {
	if (substr($form, 0, 11) == 'FORMULAIRE_') {
		$form = strtolower(substr($form, 11));
	} else {
		$form = strtolower($form);
	}

	if (!$form) {
		return '';
	} // on ne sait pas, le nom du formulaire n'est pas fourni ici

	return trouver_fond($form, 'formulaires/') ? $form : false;
}

/**
 * Tester si un formulaire est appele via un modele type <formulaire|...> et le cas echeant retourne les arguments passes au modele
 * false sinon
 * @return false|array
 */
function test_formulaire_inclus_par_modele() {
	$trace = debug_backtrace(null, 20);
	$trace_fonctions = array_column($trace, 'function');
	$trace_fonctions = array_map('strtolower', $trace_fonctions);

	// regarder si un flag a ete leve juste avant l'appel de balise_FORMULAIRE_dyn
	if (
		function_exists('arguments_balise_dyn_depuis_modele')
		and $form = arguments_balise_dyn_depuis_modele(null, 'read')
	) {
		if (in_array('balise_formulaire__dyn', $trace_fonctions)) {
			$k = array_search('balise_formulaire__dyn', $trace_fonctions);
			if ($trace[$k]['args'][0] === $form) {
				return $trace[$k]['args'];
			}
		}
	}

	// fallback qui ne repose pas sur le flag lie a l'analyse de contexte_compil,
	// mais ne marche pas si executer_balise_dynamique est appelee via du php dans le squelette
	if (in_array('eval', $trace_fonctions) and in_array('inclure_modele', $trace_fonctions)) {
		$k = array_search('inclure_modele', $trace_fonctions);
		// les arguments de recuperer_fond() passes par inclure_modele()
		return $trace[$k - 1]['args'][1]['args'];
	}
	return false;
}

/**
 * Balises Formulaires par défaut.
 *
 * Compilé en un appel à une balise dynamique.
 *
 * @param Champ $p
 *     Description de la balise formulaire
 * @return Champ
 *     Description complétée du code compilé appelant la balise dynamique
 **/
function balise_FORMULAIRE__dist($p) {

	// Cas d'un #FORMULAIRE_TOTO inexistant : renvoyer la chaine vide.
	// mais si #FORMULAIRE_{toto} on ne peut pas savoir a la compilation, continuer
	if (existe_formulaire($p->nom_champ) === false) {
		$p->code = "''";
		$p->interdire_scripts = false;

		return $p;
	}

	// sinon renvoyer un code php dynamique
	$p = calculer_balise_dynamique($p, $p->nom_champ, []);

	if (!test_espace_prive()
	  and !empty($p->descr['sourcefile'])
	  and $f = $p->descr['sourcefile']
	  and basename(dirname($f)) === 'modeles') {
		// un modele est toujours inséré en texte dans son contenant
		// donc si on est dans le public avec un cache on va perdre le dynamisme
		// et on risque de mettre en cache les valeurs pre-remplies du formulaire
		// on injecte donc le PHP qui va appeler la fonction pour generer le formulaire au lieu de directement la fonction
		$p->code = "'<'.'?php echo (".texte_script($p->code)."); ?'.'>'";
		// dans l'espace prive on a pas de cache, donc pas de soucis (et un leak serait moins grave)
	}
	return $p;
}

/**
 * Balise dynamiques par défaut des formulaires
 *
 * @note
 *     Deux moyen d'arriver ici :
 *     soit #FORMULAIRE_XX reroute avec 'FORMULAIRE_XX' ajoute en premier arg
 *     soit #FORMULAIRE_{xx}
 *
 * @param string $form
 *     Nom du formulaire
 * @param array $args
 *     Arguments envoyés à l'appel du formulaire
 * @return string|array
 *     - array : squelette à appeler, durée du cache, contexte
 *     - string : texte à afficher directement
 */
function balise_FORMULAIRE__dyn($form, ...$args) {
	$form = existe_formulaire($form);
	if (!$form) {
		return '';
	}

	$contexte = balise_FORMULAIRE__contexte($form, $args);
	if (!is_array($contexte)) {
		return $contexte;
	}

	return ["formulaires/$form", 3600, $contexte];
}

/**
 * Calcule le contexte à envoyer dans le squelette d'un formulaire
 *
 * @param string $form
 *     Nom du formulaire
 * @param array $args
 *     Arguments envoyés à l'appel du formulaire
 * @return array
 *     Contexte d'environnement à envoyer au squelette
 **/
function balise_FORMULAIRE__contexte($form, $args) {
	// tester si ce formulaire vient d'etre poste (memes arguments)
	// pour ne pas confondre 2 #FORMULAIRES_XX identiques sur une meme page
	// si poste, on recupere les erreurs

	$je_suis_poste = false;
	if (
		$post_form = _request('formulaire_action')
		and $post_form == $form
		and $p = _request('formulaire_action_args')
		and is_array($p = decoder_contexte_ajax($p, $post_form))
	) {
		// enlever le faux attribut de langue masque
		array_shift($p);
		if (formulaire__identifier($form, $args, $p)) {
			$je_suis_poste = true;
		}
	}

	$editable = true;
	$erreurs = $post = [];
	if ($je_suis_poste) {
		$post = traiter_formulaires_dynamiques(true);
		$e = "erreurs_$form";
		$erreurs = isset($post[$e]) ? $post[$e] : [];
		$editable = "editable_$form";
		$editable = (!isset($post[$e]))
			|| count($erreurs)
			|| (isset($post[$editable]) && $post[$editable]);
	}

	$valeurs = formulaire__charger($form, $args, $je_suis_poste);

	// si $valeurs n'est pas un tableau, le formulaire n'est pas applicable
	// C'est plus fort qu'editable qui est gere par le squelette
	// Idealement $valeur doit etre alors un message explicatif.
	if (!is_array($valeurs)) {
		return is_string($valeurs) ? $valeurs : '';
	}

	// charger peut passer une action si le formulaire ne tourne pas sur self()
	// ou une action vide si elle ne sert pas
	$action = (isset($valeurs['action'])) ? $valeurs['action'] : self('&amp;', true);
	// bug IEx : si action finit par /
	// IE croit que le <form ... action=../ > est autoferme
	if (substr($action, -1) == '/') {
		// on ajoute une ancre pour feinter IE, au pire ca tue l'ancre qui finit par un /
		$action .= '#';
	}

	// recuperer la saisie en cours si erreurs
	// seulement si c'est ce formulaire qui est poste
	// ou si on le demande explicitement par le parametre _forcer_request = true
	$dispo = ($je_suis_poste || (isset($valeurs['_forcer_request']) && $valeurs['_forcer_request']));
	foreach (array_keys($valeurs) as $champ) {
		if ($champ[0] !== '_' and !in_array($champ, ['message_ok', 'message_erreur', 'editable'])) {
			if ($dispo and (($v = _request($champ)) !== null)) {
				$valeurs[$champ] = $v;
			}
			// nettoyer l'url des champs qui vont etre saisis
			if ($action) {
				$action = parametre_url($action, $champ, '');
			}
			// proteger les ' et les " dans les champs que l'on va injecter
			$valeurs[$champ] = protege_champ($valeurs[$champ]);
		}
	}

	if ($action) {
		// nettoyer l'url
		$action = parametre_url($action, 'formulaire_action', '');
		$action = parametre_url($action, 'formulaire_action_args', '');
	}

	/**
	 * @deprecated
	 * servait pour poster sur les actions de type editer_xxx() qui ne prenaient pas d'argument autrement que par _request('arg') et pour lesquelles il fallait donc passer un hash valide
	 */
	/*
	if (isset($valeurs['_action'])) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$secu = $securiser_action(reset($valeurs['_action']), end($valeurs['_action']), '', -1);
		$valeurs['_hidden'] = (isset($valeurs['_hidden']) ? $valeurs['_hidden'] : '') .
			"<input type='hidden' name='arg' value='" . $secu['arg'] . "' />"
			. "<input type='hidden' name='hash' value='" . $secu['hash'] . "' />";
	}
	*/

	// empiler la lang en tant que premier argument implicite du CVT
	// pour permettre de la restaurer au moment du Verifier et du Traiter
	array_unshift($args, $GLOBALS['spip_lang']);

	$valeurs['formulaire_args'] = encoder_contexte_ajax($args, $form);
	$valeurs['erreurs'] = $erreurs;
	$valeurs['action'] = $action;
	$valeurs['form'] = $form;

	$valeurs['formulaire_sign'] = '';
	if (!empty($GLOBALS['visiteur_session']['id_auteur'])) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$secu = $securiser_action($valeurs['form'], $valeurs['formulaire_args'], '', -1);
		$valeurs['formulaire_sign'] = $secu['hash'];
	}

	if (!isset($valeurs['id'])) {
		$valeurs['id'] = 'new';
	}
	// editable peut venir de charger() ou de traiter() sinon
	if (!isset($valeurs['editable'])) {
		$valeurs['editable'] = $editable;
	}
	// dans tous les cas, renvoyer un espace ou vide (et pas un booleen)
	$valeurs['editable'] = ($valeurs['editable'] ? ' ' : '');

	if ($je_suis_poste) {
		$valeurs['message_erreur'] = '';
		if (isset($erreurs['message_erreur'])) {
			$valeurs['message_erreur'] = $erreurs['message_erreur'];
		}

		$valeurs['message_ok'] = '';
		if (isset($post["message_ok_$form"])) {
			$valeurs['message_ok'] = $post["message_ok_$form"];
		} elseif (isset($erreurs['message_ok'])) {
			$valeurs['message_ok'] = $erreurs['message_ok'];
		}

		// accessibilite : encapsuler toutes les erreurs dans un role='alert'
		// uniquement si c'est une string et au premier niveau (on ne touche pas au tableaux)
		// et si $k ne commence pas par un _ (c'est bien une vrai erreur sur un vrai champ)
		if (html5_permis()) {
			foreach ($erreurs as $k => $v) {
				if (is_string($v) and strlen(trim($v)) and strpos($k, '_') !== 0) {
					// on encapsule dans un span car ces messages sont en general simple, juste du texte, et deja dans un span dans le form
					$valeurs['erreurs'][$k] = "<span role='alert'>" . $erreurs[$k] . '</span>';
				}
			}
		}
	}

	return $valeurs;
}

/**
 * Charger les valeurs de saisie du formulaire
 *
 * @param string $form
 * @param array $args
 * @param bool $poste
 * @return array
 */
function formulaire__charger($form, $args, $poste) {
	if ($charger_valeurs = charger_fonction('charger', "formulaires/$form", true)) {
		$valeurs = call_user_func_array($charger_valeurs, $args);
	} else {
		$valeurs = [];
	}

	$valeurs = pipeline(
		'formulaire_charger',
		[
			'args' => ['form' => $form, 'args' => $args, 'je_suis_poste' => $poste],
			'data' => $valeurs
		]
	);

	// prise en charge CVT multi etape
	if (is_array($valeurs) and isset($valeurs['_etapes'])) {
		include_spip('inc/cvt_multietapes');
		$valeurs = cvtmulti_formulaire_charger_etapes(
			['form' => $form, 'args' => $args, 'je_suis_poste' => $poste],
			$valeurs
		);
	}

	// si $valeurs et false ou une chaine, pas de formulaire, donc pas de pipeline !
	if (is_array($valeurs)) {
		if (!isset($valeurs['_pipelines'])) {
			$valeurs['_pipelines'] = [];
		}
		// l'ancien argument _pipeline devient maintenant _pipelines
		// reinjectons le vieux _pipeline au debut de _pipelines
		if (isset($valeurs['_pipeline'])) {
			$pipe = is_array($valeurs['_pipeline']) ? reset($valeurs['_pipeline']) : $valeurs['_pipeline'];
			$args = is_array($valeurs['_pipeline']) ? end($valeurs['_pipeline']) : [];

			$pipelines = [$pipe => $args];
			$valeurs['_pipelines'] = array_merge($pipelines, $valeurs['_pipelines']);
		}

		// et enfin, ajoutons systematiquement un pipeline sur le squelette du formulaire
		// qui constitue le cas le plus courant d'utilisation du pipeline recuperer_fond
		// (performance, cela evite de s'injecter dans recuperer_fond utilise pour *tous* les squelettes)
		$valeurs['_pipelines']['formulaire_fond'] = ['form' => $form, 'args' => $args, 'je_suis_poste' => $poste];
	}

	return $valeurs;
}

/**
 * Vérifier que le formulaire en cours est celui qui est poste
 *
 * On se base sur la fonction identifier (si elle existe) qui fournit
 * une signature identifiant le formulaire a partir de ses arguments
 * significatifs
 *
 * En l'absence de fonction identifier, on se base sur l'egalite des
 * arguments, ce qui fonctionne dans les cas simples
 *
 * @param string $form
 * @param array $args
 * @param array $p
 * @return bool
 */
function formulaire__identifier($form, $args, $p) {
	if ($identifier_args = charger_fonction('identifier', "formulaires/$form", true)) {
		return call_user_func_array($identifier_args, $args) === call_user_func_array($identifier_args, $p);
	}

	return $args === $p;
}
