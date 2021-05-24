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
 * CVT Multi étapes
 *
 * Module facilitant l'écriture de formulaires CVT
 * en plusieurs étapes.
 *
 * `#FORMULAIRE_TRUC`
 *
 * Squelette :
 * Chaque étape est representée par un squelette indépendant qui doit
 * implémenter un formulaire autonome pour les saisies de l'étape n :
 *
 * - formulaires/truc.html pour l'etape 1
 * - formulaires/truc_2.html pour l'etape 2
 * - formulaires/truc_n.html pour l'etape n
 *
 * Si un squelette `formulaires/truc_n.html` manque pour l'étape n
 * c'est `formulaires/truc.html` qui sera utilisé
 * (charge à lui de gérer le cas de cette étape).
 *
 * Charger :
 * `formulaires_truc_charger_dist()` :
 *  passer '_etapes' => nombre total d'etapes de saisies (>1 !)
 *  indiquer toutes les valeurs à saisir sur toutes les pages
 *  comme si il s'agissait d'un formulaire unique
 *
 * Vérifier :
 * Le numero d'étape courante est disponible dans `$x=_request('_etape')`, si nécessaire
 * `_request()` permet d'accéder aux saisies effectuées depuis l'étape 1,
 * comme si les étapes 1 a `$x` avaient été saisies en une seule fois
 *
 * - formulaires_truc_verifier_1_dist() : verifier les saisies de l'etape 1 uniquement
 * - formulaires_truc_verifier_2_dist() : verifier les saisies de l'etape 2
 * - formulaires_truc_verifier_n_dist() : verifier les saisies de l'etape n
 *
 * Il est possible d'implémenter toutes les vérifications dans une fonction unique
 * qui sera alors appelée avec en premier argument le numero de l'étape à vérifier
 * `formulaires_truc_verifier_etape_dist($etape,...)` : vérifier les saisies
 * de l'étape `$etape` uniquement.
 *
 * À chaque étape x, les étapes 1 a x sont appelées en vérification
 * pour vérifier l'absence de régression dans la validation (erreur, tentative de réinjection ...)
 * en cas d'erreur, la saisie retourne à la première étape en erreur.
 * en cas de succès, l'étape est incrémentée, sauf si c'est la dernière.
 * Dans ce dernier cas on déclenche `traiter()`.
 *
 * Traiter :
 * `formulaires_truc_traiter_dist()` : ne sera appelé que lorsque **toutes**
 * les étapes auront été saisies sans erreur.
 *
 * La fonction traiter peut donc traiter l'ensemble des saisies comme si il
 * s'agissait d'un formulaire unique dans lequel toutes les données auraient
 * été saisies en une fois.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Reinjecter dans _request() les valeurs postees
 * dans les etapes precedentes
 *
 * @param string $form
 * @return array
 */
function cvtmulti_recuperer_post_precedents($form) {
	include_spip('inc/filtres');
	if ($form
		and $c = _request('cvtm_prev_post')
		and $c = decoder_contexte_ajax($c, $form)
	) {
		#var_dump($c);

		# reinjecter dans la bonne variable pour permettre de retrouver
		# toutes les saisies dans un seul tableau
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$store = &$_POST;
		} else {
			$store = &$_GET;
		}

		foreach ($c as $k => $v) // on ecrase pas si saisi a nouveau !
		{
			if (!isset($store[$k])) {
				$_REQUEST[$k] = $store[$k] = $v;
			} // mais si tableau des deux cotes, on merge avec priorite a la derniere saisie
			elseif (is_array($store[$k])
				and is_array($v)
				and $z = array_keys($v)
				and !is_numeric(reset($z))
				and $z = array_keys($store[$k])
				and !is_numeric(reset($z))
			) {
				$_REQUEST[$k] = $store[$k] = array_merge($v, $store[$k]);
			}
		}

		// vider pour eviter un second appel a verifier_n
		// en cas de double implementation (unipotence)
		set_request('cvtm_prev_post');

		return array($c['_etape'], $c['_etapes']);
	}

	return false;
}

/**
 * Sauvegarder les valeurs postees dans une variable encodee
 * pour les recuperer a la prochaine etape
 *
 * @param string $form
 * @param bool $je_suis_poste
 * @param array $valeurs
 * @return array
 */
function cvtmulti_sauver_post($form, $je_suis_poste, &$valeurs) {
	if (!isset($valeurs['_cvtm_prev_post'])) {
		$post = array('_etape' => $valeurs['_etape'], '_etapes' => $valeurs['_etapes']);
		foreach (array_keys($valeurs) as $champ) {
			if (substr($champ, 0, 1) !== '_') {
				if ($je_suis_poste || (isset($valeurs['_forcer_request']) && $valeurs['_forcer_request'])) {
					if (($v = _request($champ)) !== null) {
						$post[$champ] = $v;
					}
				}
			}
		}
		include_spip('inc/filtres');
		$c = encoder_contexte_ajax($post, $form);
		if (!isset($valeurs['_hidden'])) {
			$valeurs['_hidden'] = '';
		}
		$valeurs['_hidden'] .= "<input type='hidden' name='cvtm_prev_post' value='$c' />";
		// marquer comme fait, pour eviter double encodage (unipotence)
		$valeurs['_cvtm_prev_post'] = true;
	}

	return $valeurs;
}


/**
 * Reperer une demande de formulaire CVT multi page
 * et la reformater
 *
 * @deprecated 3.2
 * @see cvtmulti_formulaire_charger_etapes()
 * @param array $flux
 * @return array
 */
function cvtmulti_formulaire_charger($flux) {
	if (is_array($flux['data'])
		and isset($flux['data']['_etapes'])
	) {
		$flux['data'] = cvtmulti_formulaire_charger_etapes($flux['args'], $flux['data']);
	}

	return $flux;
}

/**
 * Charger une etape du cvt multi
 * @param $args
 * @param $valeurs
 * @return array
 */
function cvtmulti_formulaire_charger_etapes($args, $valeurs) {
	if (!isset($valeurs['_etape'])) {
		$form = $args['form'];
		$je_suis_poste = $args['je_suis_poste'];
		$nb_etapes = $valeurs['_etapes'];
		$etape = _request('_etape');
		$etape = min(max($etape, 1), $nb_etapes);
		set_request('_etape', $etape);
		$valeurs['_etape'] = $etape;

		// sauver les posts de cette etape pour les avoir a la prochaine etape
		$valeurs = cvtmulti_sauver_post($form, $je_suis_poste, $valeurs);
	}
	return $valeurs;
}


/**
 * Verifier les etapes de saisie
 *
 * @deprecated 3.2
 * @see cvtmulti_formulaire_verifier_etapes()
 * @param array $flux
 * @return array
 */
function cvtmulti_formulaire_verifier($flux) {
	$flux['data'] = cvtmulti_formulaire_verifier_etapes($flux['args'], $flux['data']);
	return $flux;
}

/**
 * Verifier les etapes de saisie
 *
 * @param array $args
 * @param $erreurs
 * @return array
 */
function cvtmulti_formulaire_verifier_etapes($args, $erreurs) {
	#var_dump('Pipe verifier');

	if ($form = $args['form']
		and ($e = cvtmulti_recuperer_post_precedents($form)) !== false
	) {
		// recuperer l'etape saisie et le nombre d'etapes total
		list($etape, $etapes) = $e;
		$etape_demandee = intval(_request('aller_a_etape')); // possibilite de poster un entier dans aller_a_etape

		$args['etape_saisie'] = $etape;
		$args['etapes'] = $etapes;
		// lancer les verifs pour chaque etape deja saisie de 1 a $etape
		$erreurs_etapes = array();
		$derniere_etape_ok = 0;
		$e = 0;
		while ($e < max($etape, $etape_demandee -1) and $e < $etapes) {
			$e++;
			$erreurs_etapes[$e] = array();
			if ($verifier = charger_fonction("verifier_$e", "formulaires/$form/", true)) {
				$erreurs_etapes[$e] = call_user_func_array($verifier, $args['args']);
			} elseif ($verifier = charger_fonction("verifier_etape", "formulaires/$form/", true)) {
				$a = $args['args'];
				array_unshift($a, $e);
				$erreurs_etapes[$e] = call_user_func_array($verifier, $a);
			}
			// et on appelle un pipeline dedie aux etapes, plus easy
			$args['etape'] = $e;
			$args['etape_demandee'] = $etape_demandee;
			$erreurs_etapes[$e] = pipeline(
				'formulaire_verifier_etape',
				array(
					'args' => $args,
					'data' => $erreurs_etapes[$e]
				)
			);

			if ($derniere_etape_ok == $e - 1 and !count($erreurs_etapes[$e])) {
				$derniere_etape_ok = $e;
			}
			// possibilite de poster dans _retour_etape_x ou aller_a_etape
			if (!is_null(_request("_retour_etape_$e"))) {
				$etape_demandee = $e;
			}
			// Il se peut que les verifications ait décidé de faire sauter des étapes
			if ($aller_a_etape = intval(_request('aller_a_etape'))) {
				$etape_demandee = $aller_a_etape; // possibilite de poster un entier dans aller_a_etape
			}
		}


		// si la derniere etape OK etait la derniere
		// on renvoie le flux inchange et ca declenche traiter
		if ($derniere_etape_ok == $etapes
			and (!$etape_demandee or $etape_demandee>=$etapes)) {
			return $erreurs;
		} else {
			$etape = $derniere_etape_ok + 1;
			if ($etape_demandee > 0 and $etape_demandee < $etape) {
				$etape = $etape_demandee;
			}
			$etape = min($etape, $etapes);
			#var_dump("prochaine etape $etape");
			// retourner les erreurs de l'etape ciblee
			$erreurs = isset($erreurs_etapes[$etape]) ? $erreurs_etapes[$etape] : array();
			// Ne pas se tromper dans le texte du message d'erreur : la clé '_etapes' n'est pas une erreur !
			if ($erreurs) {
				if (!isset($erreurs['message_erreur'])) {
					$erreurs['message_erreur'] = singulier_ou_pluriel(count($erreurs), 'avis_1_erreur_saisie', 'avis_nb_erreurs_saisie');
				}
			} else {
				$erreurs['message_erreur'] = '';
			}
			$erreurs['_etapes'] = "etape suivante $etape";
			set_request('_etape', $etape);
		}
	}

	return $erreurs;
}

/**
 * Selectionner le bon fond en fonction de l'etape
 * L'etape 1 est sur le fond sans suffixe
 * Les autres etapes x sont sur le fond _x
 *
 * @param array $flux
 * @return array
 */
function cvtmulti_styliser($flux) {
	if (strncmp($flux['args']['fond'], 'formulaires/', 12) == 0
		and isset($flux['args']['contexte']['_etapes'])
		and isset($flux['args']['contexte']['_etape'])
		and ($e = $flux['args']['contexte']['_etape']) > 1
		and $ext = $flux['args']['ext']
		and $f = $flux['data']
		and file_exists($f . "_$e.$ext")
	) {
		$flux['data'] = $f . "_$e";
	}

	return $flux;
}
