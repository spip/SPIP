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

/**
 * @param string $redirect
 * @return string
 */
function securiser_redirect_action($redirect) {
	$redirect ??= '';
	// cas d'un double urlencode : si un urldecode de l'url n'est pas secure, on retient ca comme redirect
	if (strpos($redirect, '%') !== false) {
		$r2 = urldecode($redirect);
		if (($r3 = securiser_redirect_action($r2)) !== $r2) {
			return $r3;
		}
	}
	if (
		(tester_url_absolue($redirect) or preg_match(',^\w+:,', trim($redirect)))
		and !defined('_AUTORISER_ACTION_ABS_REDIRECT')
	) {
		// si l'url est une url du site, on la laisse passer sans rien faire
		// c'est encore le plus simple
		$base = $GLOBALS['meta']['adresse_site'] . '/';
		if (strlen($base) and strncmp($redirect, $base, strlen($base)) == 0) {
			return $redirect;
		}
		$base = url_de_base();
		if (strlen($base) and strncmp($redirect, $base, strlen($base)) == 0) {
			return $redirect;
		}

		return '';
	}

	return $redirect;
}

function traiter_appels_actions() {
	// cas de l'appel qui renvoie une redirection (302) ou rien (204)
	if ($action = _request('action')) {
		include_spip('base/abstract_sql'); // chargement systematique pour les actions
		include_spip('inc/autoriser');
		include_spip('inc/headers');
		include_spip('inc/actions');
		// des actions peuvent appeler _T
		if (!isset($GLOBALS['spip_lang'])) {
			include_spip('inc/lang');
			utiliser_langue_visiteur();
		}
		// si l'action est provoque par un hit {ajax}
		// il faut transmettre l'env ajax au redirect
		// on le met avant dans la query string au cas ou l'action fait elle meme sa redirection
		if (
			($v = _request('var_ajax'))
			and ($v !== 'form')
			and ($args = _request('var_ajax_env'))
			and ($url = _request('redirect'))
		) {
			$url = parametre_url($url, 'var_ajax', $v, '&');
			$url = parametre_url($url, 'var_ajax_env', $args, '&');
			set_request('redirect', $url);
		} else {
			if (_request('redirect')) {
				set_request('redirect', securiser_redirect_action(_request('redirect')));
			}
		}
		$var_f = charger_fonction($action, 'action');
		$var_f();
		if (!isset($GLOBALS['redirect'])) {
			$GLOBALS['redirect'] = _request('redirect') ?? '';
			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$GLOBALS['redirect'] = urldecode($GLOBALS['redirect']);
			}
			$GLOBALS['redirect'] = securiser_redirect_action($GLOBALS['redirect']);
		}
		if ($url = $GLOBALS['redirect']) {
			// si l'action est provoque par un hit {ajax}
			// il faut transmettre l'env ajax au redirect
			// qui a pu etre defini par l'action
			if (
				($v = _request('var_ajax'))
				and ($v !== 'form')
				and ($args = _request('var_ajax_env'))
			) {
				$url = parametre_url($url, 'var_ajax', $v, '&');
				$url = parametre_url($url, 'var_ajax_env', $args, '&');
				// passer l'ancre en variable pour pouvoir la gerer cote serveur
				$url = preg_replace(',#([^#&?]+)$,', "&var_ajax_ancre=\\1", $url);
			}
			$url = str_replace('&amp;', '&', $url); // les redirections se font en &, pas en en &amp;
			redirige_par_entete($url);
		}

		// attention : avec zlib.output_compression=1 on a vu des cas de ob_get_length() qui renvoi 0
		// et du coup en renvoi un status 204 a tort (vu sur le menu rubriques notamment)
		if (
			!headers_sent()
			and !ob_get_length()
		) {
			http_response_code(204);
		} // No Content
		return true;
	}

	return false;
}


function refuser_traiter_formulaire_ajax() {
	if (
		$v = _request('var_ajax')
		and $v == 'form'
		and $form = _request('formulaire_action')
		and $args = _request('formulaire_action_args')
		and decoder_contexte_ajax($args, $form) !== false
	) {
		// on est bien dans le contexte de traitement d'un formulaire en ajax
		// mais traiter ne veut pas
		// on le dit a la page qui va resumbit
		// sans ajax
		include_spip('inc/actions');
		ajax_retour('noajax', false);
		exit;
	}
}

function traiter_appels_inclusions_ajax() {
	// traiter les appels de bloc ajax (ex: pagination)
	if (
		$v = _request('var_ajax')
		and $v !== 'form'
		and $args = _request('var_ajax_env')
	) {
		include_spip('inc/filtres');
		include_spip('inc/actions');
		if (
			$args = decoder_contexte_ajax($args)
			and $fond = $args['fond']
		) {
			include_spip('public/assembler');
			$contexte = calculer_contexte();
			$contexte = array_merge($args, $contexte);
			$page = recuperer_fond($fond, $contexte, ['trim' => false]);
			$texte = $page;
			if ($ancre = _request('var_ajax_ancre')) {
				// pas n'importe quoi quand meme dans la variable !
				$ancre = str_replace(['<', '"', "'"], ['&lt;', '&quot;', ''], $ancre);
				$texte = "<a href='#$ancre' name='ajax_ancre' style='display:none;'>anchor</a>" . $texte;
			}
		} else {
			include_spip('inc/headers');
			http_response_code(400);
			$texte = _L('signature ajax bloc incorrecte');
		}
		ajax_retour($texte, false);

		return true; // on a fini le hit
	}

	return false;
}

// au 1er appel, traite les formulaires dynamiques charger/verifier/traiter
// au 2e se sachant 2e, retourne les messages et erreurs stockes au 1er
// Le 1er renvoie True si il faut faire exit a la sortie

function traiter_formulaires_dynamiques($get = false) {
	static $post = [];
	static $done = false;

	if ($get) {
		return $post;
	}
	if ($done) {
		return false;
	}
	$done = true;

	if (
		!($form = _request('formulaire_action')
		and $args = _request('formulaire_action_args'))
	) {
		return false;
	} // le hit peut continuer normalement

	// verifier que le post est licite (du meme auteur ou d'une session anonyme)
	$sign = _request('formulaire_action_sign');
	if (!empty($GLOBALS['visiteur_session']['id_auteur'])) {
		if (empty($sign)) {
			spip_log("signature ajax form incorrecte : $form (formulaire non signe mais on a une session)", 'formulaires' . _LOG_ERREUR);
			return false;
		}
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$secu = $securiser_action($form, $args, '', -1);
		if ($sign !== $secu['hash']) {
			spip_log("signature ajax form incorrecte : $form (formulaire signe mais ne correspond pas a la session)", 'formulaires' . _LOG_ERREUR);
			return false;
		}
	}
	else {
		if (!empty($sign)) {
			spip_log("signature ajax form incorrecte : $form (formulaire signe mais pas de session)", 'formulaires' . _LOG_ERREUR);
			return false;
		}
	}

	include_spip('inc/filtres');
	if (($args = decoder_contexte_ajax($args, $form)) === false) {
		spip_log("signature ajax form incorrecte : $form (encodage corrompu)", 'formulaires' . _LOG_ERREUR);

		return false; // continuons le hit comme si de rien etait
	} else {
		include_spip('inc/lang');
		// sauvegarder la lang en cours
		$old_lang = $GLOBALS['spip_lang'];
		// changer la langue avec celle qui a cours dans le formulaire
		// on la depile de $args car c'est un argument implicite masque
		changer_langue(array_shift($args));


		// inclure mes_fonctions et autres filtres avant verifier/traiter
		include_fichiers_fonctions();
		// ainsi que l'API SQL bien utile dans verifier/traiter
		include_spip('base/abstract_sql');

		/**
		 * Pipeline exécuté lors de la soumission d'un formulaire,
		 * mais avant l'appel de la fonction de vérification.
		 */
		pipeline(
			'formulaire_receptionner',
			[
				'args' => ['form' => $form, 'args' => $args],
				'data' => null,
			]
		);

		$verifier = charger_fonction('verifier', "formulaires/$form/", true);
		$post["erreurs_$form"] = pipeline(
			'formulaire_verifier',
			[
				'args' => ['form' => $form, 'args' => $args],
				'data' => $verifier ? $verifier(...$args) : []
			]
		);
		// prise en charge CVT multi etape si besoin
		if (_request('cvtm_prev_post')) {
			include_spip('inc/cvt_multietapes');
			$post["erreurs_$form"] = cvtmulti_formulaire_verifier_etapes(
				['form' => $form, 'args' => $args],
				$post["erreurs_$form"]
			);
		}

		// accessibilite : si des erreurs mais pas de message general l'ajouter
		if ((isset($post["erreurs_$form"]) and is_countable($post["erreurs_$form"]) ? count($post["erreurs_$form"]) : 0) and !isset($post["erreurs_$form"]['message_erreur'])) {
			$post["erreurs_$form"]['message_erreur'] = singulier_ou_pluriel(
				is_countable($post["erreurs_$form"]) ? count($post["erreurs_$form"]) : 0,
				'avis_1_erreur_saisie',
				'avis_nb_erreurs_saisie'
			);
		}

		// si on ne demandait qu'une verif json
		if (_request('formulaire_action_verifier_json')) {
			include_spip('inc/json');
			include_spip('inc/actions');
			ajax_retour(json_encode($post["erreurs_$form"], JSON_THROW_ON_ERROR), 'text/plain');

			return true; // on a fini le hit
		}
		$retour = '';
		if (isset($post["erreurs_$form"]) and ((is_countable($post["erreurs_$form"]) ? count($post["erreurs_$form"]) : 0) == 0)) {
			$rev = '';
			if ($traiter = charger_fonction('traiter', "formulaires/$form/", true)) {
				$rev = $traiter(...$args);
			}

			$rev = pipeline(
				'formulaire_traiter',
				[
					'args' => ['form' => $form, 'args' => $args],
					'data' => $rev
				]
			);
			// le retour de traiter est
			// un tableau explicite ('editable'=>$editable,'message_ok'=>$message,'redirect'=>$redirect,'id_xx'=>$id_xx)
			// il permet le pipelinage, en particulier
			// en y passant l'id de l'objet cree/modifie
			// si message_erreur est present, on considere que le traitement a echoue
			$post["message_ok_$form"] = '';
			// on peut avoir message_ok et message_erreur
			if (isset($rev['message_ok'])) {
				$post["message_ok_$form"] = $rev['message_ok'];
			}

			// verifier si traiter n'a pas echoue avec une erreur :
			if (isset($rev['message_erreur'])) {
				$post["erreurs_$form"]['message_erreur'] = $rev['message_erreur'];
				// si il y a une erreur on ne redirige pas
			} else {
				// sinon faire ce qu'il faut :
				if (isset($rev['editable'])) {
					$post["editable_$form"] = $rev['editable'];
				}
				// si une redirection est demandee, appeler redirigae_formulaire qui choisira
				// le bon mode de redirection (302 et on ne revient pas ici, ou javascript et on continue)
				if (isset($rev['redirect']) and $rev['redirect']) {
					include_spip('inc/headers');
					[$masque, $message] = redirige_formulaire($rev['redirect'], '', 'ajaxform');
					$post["message_ok_$form"] .= $message;
					$retour .= $masque;
				}
			}
		}
		// si le formulaire a ete soumis en ajax, on le renvoie direct !
		if (_request('var_ajax')) {
			if (find_in_path('formulaire_.php', 'balise/', true)) {
				include_spip('inc/actions');
				include_spip('public/assembler');
				$retour .= inclure_balise_dynamique(balise_formulaire__dyn($form, ...$args), false);
				// on ajoute un br en display none en tete du retour ajax pour regler un bug dans IE6/7
				// sans cela le formulaire n'est pas actif apres le hit ajax
				// la classe ajax-form-is-ok sert a s'assurer que le retour ajax s'est bien passe
				$retour = "<br class='bugajaxie ajax-form-is-ok' style='display:none;'/>" . $retour;
				ajax_retour($retour, false);

				return true; // on a fini le hit
			}
		}
		// restaurer la lang en cours
		changer_langue($old_lang);
	}

	return false; // le hit peut continuer normalement
}
