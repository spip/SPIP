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

include_fichiers_fonctions();

# Determine le squelette associe a une requete
# et l'applique sur le contexte, le nom du cache et le serveur
# en ayant evacue au prealable le cas de la redirection
# Retourne un tableau ainsi construit
# 'texte' => la page calculee
# 'process_ins' => 'html' ou 'php' si presence d'un '< ?php'
# 'invalideurs' => les invalideurs de ce cache
# 'entetes' => headers http
# 'duree' => duree de vie du cache
# 'signal' => contexte (les id_* globales)

# En cas d'erreur process_ins est absent et texte est un tableau de 2 chaines

// https://code.spip.net/@public_parametrer_dist
function public_parametrer_dist($fond, $contexte = '', $cache = '', string $connect = '') {
	static $composer, $styliser, $notes = null;
	$page = tester_redirection($fond, $contexte, $connect);
	if ($page) {
		return $page;
	}

	if (isset($contexte['lang'])) {
		$lang = $contexte['lang'];
	} elseif (!isset($lang)) {
		$lang = $GLOBALS['meta']['langue_site'];
	}

	$select = ((!isset($GLOBALS['forcer_lang']) or !$GLOBALS['forcer_lang']) and $lang <> $GLOBALS['spip_lang']);
	if ($select) {
		$select = lang_select($lang);
	}

	$debug = (defined('_VAR_MODE') && _VAR_MODE == 'debug');

	if (!$styliser) {
		$styliser = charger_fonction('styliser', 'public');
	}
	list($skel, $mime_type, $gram, $sourcefile) =
		$styliser($fond, $contexte, $GLOBALS['spip_lang'], $connect);

	if ($skel) {
		// sauver le nom de l'eventuel squelette en cours d'execution
		// (recursion possible a cause des modeles)
		if ($debug) {
			$courant = @$GLOBALS['debug_objets']['courant'];
			$GLOBALS['debug_objets']['contexte'][$sourcefile] = $contexte;
		}

		// charger le squelette en specifiant les langages cibles et source
		// au cas il faudrait le compiler (source posterieure au resultat)

		if (!$composer) {
			$composer = charger_fonction('composer', 'public');
		}
		$fonc = $composer($skel, $mime_type, $gram, $sourcefile, $connect);
	} else {
		$fonc = '';
	}

	if (!$fonc) { // squelette inconnu (==='') ou faux (===false)
		$page = $fonc;
	} else {
		// Preparer l'appel de la fonction principale du squelette

		spip_timer($a = 'calcul page ' . rand(0, 1000));

		// On cree un marqueur de notes unique lie a cette composition
		// et on enregistre l'etat courant des globales de notes...
		if (is_null($notes)) {
			$notes = charger_fonction('notes', 'inc', true);
		}
		if ($notes) {
			$notes('', 'empiler');
		}

		// Rajouter d'office ces deux parametres
		// (mais vaudrait mieux que le compilateur sache le simuler
		// car ca interdit l'usage de criteres conditionnels dessus).
		if (!isset($contexte['date'])) {
			$contexte['date'] = date('Y-m-d H:i:s');
			$contexte['date_default'] = true;
		} else {
			$contexte['date'] = normaliser_date($contexte['date'], true);
		}

		if (!isset($contexte['date_redac'])) {
			$contexte['date_redac'] = date('Y-m-d H:i:s');
			$contexte['date_redac_default'] = true;
		} else {
			$contexte['date_redac'] = normaliser_date($contexte['date_redac'], true);
		}

		// Passer le nom du cache pour produire sa destruction automatique
		try {
			$page = $fonc(['cache' => $cache], [$contexte]);
		} catch (Throwable $e) {
			$msg = _T('zbug_erreur_execution_page') . " $sourcefile";
			$full_msg = $msg . ' | File ' . $e->getFile() . ' Line ' . $e->getLine() . ' : ' . $e->getMessage();
			$full_msg = str_replace(_ROOT_RACINE, '[…]/', $full_msg);
			$corps = "<pre>$msg</pre>";
			$page = analyse_resultat_skel($fond, ['cache' => $cache], $corps, $sourcefile);
			erreur_squelette($full_msg);
			unset($msg, $full_msg, $corps);
		}

		// Restituer les globales de notes telles qu'elles etaient avant l'appel
		// Si l'inclus n'a pas affiche ses notes, tant pis (elles *doivent*
		// etre dans son resultat, autrement elles ne seraient pas prises en
		// compte a chaque calcul d'un texte contenant un modele, mais seulement
		// quand le modele serait calcule, et on aurait des resultats incoherents)
		if ($notes) {
			$notes('', 'depiler');
		}

		// reinjecter en dynamique la pile des notes
		// si il y a des inclure dynamiques
		// si la pile n'est pas vide
		// la generalisation de cette injection permettrait de corriger le point juste au dessus
		// en faisant remonter les notes a l'incluant (A tester et valider avant application)
		if ($notes) {
			$page['notes'] = $notes('', 'sauver_etat');
		}

		// spip_log: un joli contexte
		$infos = presenter_contexte(array_filter($contexte));

		$profile = spip_timer($a);
		spip_log("calcul ($profile) [$skel] $infos"
			. ' (' . strlen($page['texte']) . ' octets)');

		if (defined('_CALCUL_PROFILER') and intval($profile) > _CALCUL_PROFILER) {
			spip_log("calcul ($profile) [$skel] $infos"
				. ' (' . strlen($page['texte']) . ' octets) | ' . $_SERVER['REQUEST_URI'], 'profiler' . _LOG_AVERTISSEMENT);
		}

		if ($debug) {
			// si c'est ce que demande le debusqueur, lui passer la main
			$t = strlen($page['texte']) ? $page['texte'] : ' ';
			$GLOBALS['debug_objets']['resultat'][$fonc . 'tout'] = $t;
			$GLOBALS['debug_objets']['courant'] = $courant;
			$GLOBALS['debug_objets']['profile'][$sourcefile] = $profile;
			if (
				$GLOBALS['debug_objets']['sourcefile']
				and (_request('var_mode_objet') == $fonc)
				and (_request('var_mode_affiche') == 'resultat')
			) {
				erreur_squelette();
			}
		}
		// Si #CACHE{} n'etait pas la, le mettre a $delais
		if (!isset($page['entetes']['X-Spip-Cache'])) {
			// Dans l'espace prive ou dans un modeles/ on pose un cache 0 par defaut
			// si aucun #CACHE{} spécifié
			// le contexte implicite qui conditionne le cache assure qu'on retombe pas sur le meme
			// entre public et prive
			if (test_espace_prive() or strncmp($fond, 'modeles/', 8) == 0) {
				$page['entetes']['X-Spip-Cache'] = 0;
			} else {
				$page['entetes']['X-Spip-Cache'] = isset($GLOBALS['delais']) ? $GLOBALS['delais'] : 36000;
			}
		}

		$page['contexte'] = $contexte;

		// faire remonter le fichier source
		static $js_inclus = false;
		if (defined('_VAR_INCLURE') and _VAR_INCLURE) {
			$page['sourcefile'] = $sourcefile;
			$page['texte'] =
				"<div class='inclure_blocs'><h6>" . $page['sourcefile'] . '</h6>' . $page['texte'] . '</div>'
				. ($js_inclus ? '' : "<script type='text/javascript'>jQuery(function(){jQuery('.inclure_blocs > h6:first-child').hover(function(){jQuery(this).parent().addClass('hover')},function(){jQuery(this).parent().removeClass('hover')})});</script>");
			$js_inclus = true;
		}

		// Si un modele contenait #SESSION, on note l'info dans $page
		if (isset($GLOBALS['cache_utilise_session'])) {
			$page['invalideurs']['session'] = $GLOBALS['cache_utilise_session'];
			unset($GLOBALS['cache_utilise_session']);
		}
	}

	if ($select) {
		lang_select();
	}

	return $page;
}

/**
 * Retourne une présentation succincte du contexte pour les logs
 * @param array $contexte
 * @return string
*/
function presenter_contexte($contexte, $profondeur_max = 1, $max_lines = 0) {
	$infos = [];
	$line = 0;
	foreach ($contexte as $var => $val) {
		$line++;
		if ($max_lines and $max_lines < $line) {
			$infos[] = '…';
			break;
		}
		if (is_array($val)) {
			if ($profondeur_max > 0) {
				$val = 'array:' . count($val) . '(' . presenter_contexte($val, $profondeur_max - 1, 3) . ')';
			} else {
				$val = 'array:' . count($val);
			}
		} elseif (is_object($val)) {
			$val = get_class($val);
		} elseif (strlen("$val") > 30) {
			$val = substr("$val", 0, 29) . '…';
			if (strstr($val, ' ')) {
				$val = "'$val'";
			}
		} elseif (strstr($val, ' ')) {
			$val = "'$val'";
		}
		$infos[] = $var . '=' . $val;
	}
	return join(', ', $infos);
}


/**
 * Si le champ virtuel est non vide c'est une redirection.
 * avec un eventuel raccourci Spip
 *
 * si le raccourci a un titre il sera pris comme corps du 302
 *
 * @uses public_tester_redirection_dist()
 * @param string $fond
 * @param array $contexte
 * @param string $connect
 * @return array|bool
 */
function tester_redirection($fond, $contexte, $connect) {
	static $tester_redirection = null;
	if (is_null($tester_redirection)) {
		$tester_redirection = charger_fonction('tester_redirection', 'public');
	}
	return $tester_redirection($fond, $contexte, $connect);
}


/**
 * Si le champ virtuel est non vide c'est une redirection.
 * avec un eventuel raccourci Spip
 *
 * si le raccourci a un titre il sera pris comme corps du 302
 *
 * @param string $fond
 * @param array $contexte
 * @param string $connect
 * @return array|bool
 */
function public_tester_redirection_dist($fond, $contexte, $connect) {
	if (
		$fond == 'article'
		and !empty($contexte['id_article'])
		and $id_article = intval($contexte['id_article'])
	) {
		include_spip('public/quete'); // pour quete_virtuel et ses dependances
		$m = quete_virtuel($id_article, $connect);
		if (strlen($m)) {
			include_spip('inc/texte');
			// les navigateurs pataugent si l'URL est vide
			if ($url = virtuel_redirige($m, true)) {
				// passer en url absolue car cette redirection pourra
				// etre utilisee dans un contexte d'url qui change
				// y compris url arbo
				$status = 302;
				if (defined('_STATUS_REDIRECTION_VIRTUEL')) {
					$status = _STATUS_REDIRECTION_VIRTUEL;
				}
				if (!preg_match(',^\w+:,', $url)) {
					include_spip('inc/filtres_mini');
					$url = url_absolue($url);
				}
				$url = str_replace('&amp;', '&', $url);

				return [
					'texte' => '<'
						. "?php include_spip('inc/headers');redirige_par_entete('"
						. texte_script($url)
						. "','',$status);"
						. '?' . '>',
					'process_ins' => 'php',
					'status' => $status
				];
			}
		}
	}

	return false;
}
