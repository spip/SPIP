<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion du formulaire d'édition de liens
 *
 * @package SPIP\Core\Formulaires
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Retrouve la source et l'objet de la liaison
 *
 * À partir des 3 premiers paramètres transmis au formulaire,
 * la fonction retrouve :
 * - l'objet dont on utilise sa table de liaison (table_source)
 * - l'objet et id_objet sur qui on lie des éléments (objet, id_objet)
 * - l'objet que l'on veut lier dessus (objet_lien)
 *
 * @param string $a
 * @param string|int $b
 * @param int|string $c
 * @return array
 *   ($table_source,$objet,$id_objet,$objet_lien)
 */
function determine_source_lien_objet($a, $b, $c) {
	$table_source = $objet_lien = $objet = $id_objet = null;
	// auteurs, article, 23 :
	// associer des auteurs à l'article 23, sur la table pivot spip_auteurs_liens
	if (is_numeric($c) && !is_numeric($b)) {
		$table_source = table_objet($a);
		$objet_lien = objet_type($a);
		$objet = objet_type($b);
		$id_objet = $c;
	}
	// article, 23, auteurs
	// associer des auteurs à l'article 23, sur la table pivot spip_articles_liens
	if (is_numeric($b) && !is_numeric($c)) {
		$table_source = table_objet($c);
		$objet_lien = objet_type($a);
		$objet = objet_type($a);
		$id_objet = $b;
	}

	return [$table_source, $objet, $id_objet, $objet_lien];
}

/**
 * Chargement du formulaire d'édition de liens
 *
 * #FORMULAIRE_EDITER_LIENS{auteurs,article,23}
 *   pour associer des auteurs à l'article 23, sur la table pivot spip_auteurs_liens
 * #FORMULAIRE_EDITER_LIENS{article,23,auteurs}
 *   pour associer des auteurs à l'article 23, sur la table pivot spip_articles_liens
 * #FORMULAIRE_EDITER_LIENS{articles,auteur,12}
 *   pour associer des articles à l'auteur 12, sur la table pivot spip_articles_liens
 * #FORMULAIRE_EDITER_LIENS{auteur,12,articles}
 *   pour associer des articles à l'auteur 12, sur la table pivot spip_auteurs_liens
 *
 * @param string $a
 * @param string|int $b
 * @param int|string $c
 * @param array|bool $options
 *    - Si array, tableau d'options
 *    - Si bool : valeur de l'option 'editable' uniquement
 *
 * @return array|false
 */
function formulaires_editer_liens_charger_dist($a, $b, $c, $options = []) {

	// compat avec ancienne signature ou le 4eme argument est $editable
	if (!is_array($options)) {
		$options = ['editable' => $options];
	} elseif (!isset($options['editable'])) {
		$options['editable'] = true;
	}

	$editable = $options['editable'];

	[$table_source, $objet, $id_objet, $objet_lien] = determine_source_lien_objet($a, $b, $c);
	if (!$table_source || !$objet || !$objet_lien || !$id_objet) {
		return false;
	}

	$objet_source = objet_type($table_source);
	$table_sql_source = table_objet_sql($objet_source);

	// verifier existence de la table xxx_liens
	include_spip('action/editer_liens');
	if (!objet_associable($objet_lien)) {
		return false;
	}

	// L'éditabilité :) est définie par un test permanent (par exemple "associermots") ET le 4ème argument
	include_spip('inc/autoriser');
	$editable = ($editable
		&& autoriser('associer' . $table_source, $objet, $id_objet)
		&& autoriser('modifier', $objet, $id_objet));

	if (
		!$editable && !count(objet_trouver_liens(
			[$objet_lien => '*'],
			[($objet_lien == $objet_source ? $objet : $objet_source) => $id_objet]
		))
	) {
		return false;
	}

	// squelettes de vue et de d'association
	// ils sont différents si des rôles sont définis.
	$skel_vue = $table_source . '_lies';
	$skel_ajout = $table_source . '_associer';

	// description des roles
	include_spip('inc/roles');
	if ($roles = roles_presents($objet_source, $objet)) {
		// on demande de nouveaux squelettes en conséquence
		$skel_vue = $table_source . '_roles_lies';
		$skel_ajout = $table_source . '_roles_associer';
	}

	$oups = '';
	if ($editable) {
		$oups = lien_gerer__oups('editer_liens', 'hash');
	}
	$valeurs = [
		'id' => "$table_source-$objet-$id_objet-$objet_lien", // identifiant unique pour les id du form
		'_vue_liee' => $skel_vue,
		'_vue_ajout' => $skel_ajout,
		'_objet_lien' => $objet_lien,
		'id_lien_ajoute' => _request('id_lien_ajoute'),
		'objet' => $objet,
		'id_objet' => $id_objet,
		'objet_source' => $objet_source,
		'table_source' => $table_source,
		'recherche' => '',
		'visible' => 0,
		'ajouter_lien' => '',
		'supprimer_lien' => '',
		'qualifier_lien' => '',
		'ordonner_lien' => '',
		'desordonner_liens' => '',
		'_roles' => $roles, # description des roles
		'_oups' => entites_html($oups),
		'editable' => $editable,
	];

	// les options non definies dans $valeurs sont passees telles quelles au formulaire html
	$valeurs = array_merge($options, $valeurs);

	return $valeurs;
}

/**
 * Traiter le post des informations d'édition de liens
 *
 * Les formulaires peuvent poster dans quatre variables
 * - ajouter_lien et supprimer_lien
 * - remplacer_lien
 * - qualifier_lien
 * - ordonner_lien
 * - desordonner_liens
 *
 * Les deux premières peuvent être de trois formes différentes :
 * ajouter_lien[]="objet1-id1-objet2-id2"
 * ajouter_lien[objet1-id1-objet2-id2]="nimportequoi"
 * ajouter_lien['clenonnumerique']="objet1-id1-objet2-id2"
 * Dans ce dernier cas, la valeur ne sera prise en compte
 * que si _request('clenonnumerique') est vrai (submit associé a l'input)
 *
 * remplacer_lien doit être de la forme
 * remplacer_lien[objet1-id1-objet2-id2]="objet3-id3-objet2-id2"
 * ou objet1-id1 est celui qu'on enleve et objet3-id3 celui qu'on ajoute
 *
 * qualifier_lien doit être de la forme, et sert en complément de ajouter_lien
 * qualifier_lien[objet1-id1-objet2-id2][role] = array("role1", "autre_role")
 * qualifier_lien[objet1-id1-objet2-id2][valeur] = array("truc", "chose")
 * produira 2 liens chacun avec array("role"=>"role1","valeur"=>"truc") et array("role"=>"autre_role","valeur"=>"chose")
 *
 * ordonner_lien doit être de la forme, et sert pour trier les liens
 * ordonner_lien[objet1-id1-objet2-id2] = nouveau_rang
 *
 * desordonner_liens n'a pas de forme précise, il doit simplement être non nul/non vide
 *
 * @param string $a
 * @param string|int $b
 * @param int|string $c
 * @param array|bool $options
 *    - Si array, tableau d'options
 *    - Si bool : valeur de l'option 'editable' uniquement
 *
 * @return array
 */
function formulaires_editer_liens_traiter_dist($a, $b, $c, $options = []) {
	// compat avec ancienne signature ou le 4eme argument est $editable
	if (!is_array($options)) {
		$options = ['editable' => $options];
	} elseif (!isset($options['editable'])) {
		$options['editable'] = true;
	}

	$editable = $options['editable'];

	$res = ['editable' => (bool) $editable];
	[$table_source, $objet, $id_objet, $objet_lien] = determine_source_lien_objet($a, $b, $c);
	if (!$table_source || !$objet || !$objet_lien) {
		return $res;
	}


	if (_request('tout_voir')) {
		set_request('recherche', '');
	}

	include_spip('inc/autoriser');
	if (autoriser('modifier', $objet, $id_objet)) {
		// recuperer le oups du coup d'avant pour le propager à charger() si on ne fait rien par exemple
		lien_gerer__oups('editer_liens', 'request');

		// annuler les suppressions du coup d'avant ?
		if (
			_request('annuler_oups')
			&& ($oups = lien_gerer__oups('editer_liens', 'get'))
		) {
			if ($oups_objets = charger_fonction("editer_liens_oups_{$table_source}_{$objet}_{$objet_lien}", 'action', true)) {
				$oups_objets($oups);
			} else {
				$objet_source = objet_type($table_source);
				include_spip('action/editer_liens');
				foreach ($oups as $oup) {
					if ($objet_lien == $objet_source) {
						objet_associer([$objet_source => $oup[$objet_source]], [$objet => $oup[$objet]], $oup);
					} else {
						objet_associer([$objet => $oup[$objet]], [$objet_source => $oup[$objet_source]], $oup);
					}
				}
			}
			# oups ne persiste que pour la derniere action, si suppression
			lien_gerer__oups('editer_liens', 'reset');
		}

		$supprimer = _request('supprimer_lien');
		$ajouter = _request('ajouter_lien');
		$ordonner = _request('ordonner_lien');

		if (_request('desordonner_liens')) {
			include_spip('action/editer_liens');
			objet_qualifier_liens([$objet_lien => '*'], [$objet => $id_objet], ['rang_lien' => 0]);
		}

		// il est possible de preciser dans une seule variable un remplacement :
		// remplacer_lien[old][new]
		if ($remplacer = _request('remplacer_lien')) {
			foreach ($remplacer as $k => $v) {
				if ($old = lien_verifier_action($k, '')) {
					foreach (is_array($v) ? $v : [$v] as $kn => $vn) {
						if ($new = lien_verifier_action($kn, $vn)) {
							$supprimer[$old] = 'x';
							$ajouter[$new] = '+';
						}
					}
				}
			}
		}

		if ($supprimer) {
			if (
				$supprimer_objets = charger_fonction(
					"editer_liens_supprimer_{$table_source}_{$objet}_{$objet_lien}",
					'action',
					true
				)
			) {
				$oups = $supprimer_objets($supprimer);
			} else {
				include_spip('action/editer_liens');
				$oups = [];

				foreach ($supprimer as $k => $v) {
					if ($lien = lien_verifier_action($k, $v)) {
						$lien = explode('-', $lien);
						[$objet_source, $ids, $objet_lie, $idl, $role] = array_pad($lien, 5, null);
						// appliquer une condition sur le rôle si défini ('*' pour tous les roles)
						$cond = (is_null($role) ? [] : ['role' => $role]);
						if ($objet_lien == $objet_source) {
							$oups = array_merge(
								$oups,
								objet_trouver_liens([$objet_source => $ids], [$objet_lie => $idl], $cond)
							);
							objet_dissocier([$objet_source => $ids], [$objet_lie => $idl], $cond);
						} else {
							$oups = array_merge(
								$oups,
								objet_trouver_liens([$objet_lie => $idl], [$objet_source => $ids], $cond)
							);
							objet_dissocier([$objet_lie => $idl], [$objet_source => $ids], $cond);
						}
					}
				}
			}
			if (!empty($oups)) {
				lien_gerer__oups('editer_liens', 'set', $oups);
			} else {
				lien_gerer__oups('editer_liens', 'reset');
			}
		}

		if ($ajouter) {
			if (
				$ajouter_objets = charger_fonction("editer_liens_ajouter_{$table_source}_{$objet}_{$objet_lien}", 'action', true)
			) {
				$ajout_ok = $ajouter_objets($ajouter);
			} else {
				$ajout_ok = false;
				include_spip('action/editer_liens');
				foreach ($ajouter as $k => $v) {
					if ($lien = lien_verifier_action($k, $v)) {
						$ajout_ok = true;
						[$objet1, $ids, $objet2, $idl] = explode('-', $lien);
						$qualifs = lien_retrouver_qualif($objet_lien, $lien);
						if ($objet_lien == $objet1) {
							lien_ajouter_liaisons($objet1, $ids, $objet2, $idl, $qualifs);
						} else {
							lien_ajouter_liaisons($objet2, $idl, $objet1, $ids, $qualifs);
						}
						set_request('id_lien_ajoute', $ids);
					}
				}
			}
			# oups ne persiste que pour la derniere action, si suppression
			# une suppression suivie d'un ajout dans le meme hit est un remplacement
			# non annulable !
			if ($ajout_ok) {
				lien_gerer__oups('editer_liens', 'reset');
			}
		}

		if ($ordonner) {
			include_spip('action/editer_liens');
			foreach ($ordonner as $k => $rang_lien) {
				if ($lien = lien_verifier_action($k, '')) {
					[$objet1, $ids, $objet2, $idl] = explode('-', $lien);
					$qualif = ['rang_lien' => $rang_lien];

					if ($objet_lien == $objet1) {
						objet_qualifier_liens([$objet1 => $ids], [$objet2 => $idl], $qualif);
					} else {
						objet_qualifier_liens([$objet2 => $idl], [$objet1 => $ids], $qualif);
					}
					set_request('id_lien_ajoute', $ids);
					lien_gerer__oups('editer_liens', 'reset');
				}
			}
		}
	}


	return $res;
}


/**
 * Retrouver l'action de liaision demandée
 *
 * Les formulaires envoient une action dans un tableau ajouter_lien
 * ou supprimer_lien
 *
 * L'action est de la forme : objet1-id1-objet2-id2
 * ou de la forme : objet1-id1-objet2-id2-role
 *
 * L'action peut-être indiquée dans la clé ou dans la valeur.
 * Si elle est indiquee dans la valeur et que la clé est non numérique,
 * on ne la prend en compte que si un submit avec la clé a été envoyé
 *
 * @internal
 * @param string $k Clé du tableau
 * @param string $v Valeur du tableau
 * @return string Action demandée si trouvée, sinon ''
 */
function lien_verifier_action($k, $v) {
	$action = '';
	if (preg_match(',^\w+-[\w*]+-[\w*]+-[\w*]+(-[\w*])?,', $k)) {
		$action = $k;
	}
	if (preg_match(',^\w+-[\w*]+-[\w*]+-[\w*]+(-[\w*])?,', $v)) {
		if (is_numeric($k)) {
			$action = $v;
		}
		if (_request($k)) {
			$action = $v;
		}
	}
	// ajout un role null fictif (plus pratique) si pas défini
	if ($action && count(explode('-', $action)) == 4) {
		$action .= '-';
	}

	return $action;
}


/**
 * Retrouve le ou les qualificatifs postés avec une liaison demandée
 *
 * @internal
 * @param string $objet_lien
 *    objet qui porte le lien
 * @param string $lien
 *   Action du lien
 * @return array
 *   Liste des qualifs pour chaque lien. Tableau vide s'il n'y en a pas.
 **/
function lien_retrouver_qualif($objet_lien, $lien) {
	// un role est défini dans la liaison
	$defs = explode('-', $lien);
	[$objet1, , $objet2, , $role] = array_pad($defs, 5, null);
	$colonne_role = $objet_lien == $objet1 ? roles_colonne($objet1, $objet2) : roles_colonne($objet2, $objet1);

	// cas ou le role est defini en 5e argument de l'action sur le lien (suppression, ajout rapide sans autre attribut)
	if ($role) {
		return [
			// un seul lien avec ce role
			[$colonne_role => $role]
		];
	}

	// retrouver les rôles postés pour cette liaison, s'il y en a.
	$qualifier_lien = _request('qualifier_lien');
	if (!$qualifier_lien || !is_array($qualifier_lien)) {
		return [];
	}

	// pas avec l'action complete (incluant le role)
	$qualif = [];
	if (
		(!isset($qualifier_lien[$lien]) || !$qualif = $qualifier_lien[$lien])
		&& count($defs) == 5
	) {
		// on tente avec l'action sans le role
		array_pop($defs);
		$lien = implode('-', $defs);
		if (!isset($qualifier_lien[$lien]) || !$qualif = $qualifier_lien[$lien]) {
			$qualif = [];
		}
	}

	// $qualif de la forme array(role=>array(...),valeur=>array(...),....)
	// on le reforme en array(array(role=>..,valeur=>..,..),array(role=>..,valeur=>..,..),...)
	$qualifs = [];
	while (is_countable($qualif) ? count($qualif) : 0) {
		$q = [];
		foreach ($qualif as $att => $values) {
			if (is_array($values)) {
				$q[$att] = array_shift($qualif[$att]);
				if (!(is_countable($qualif[$att]) ? count($qualif[$att]) : 0)) {
					unset($qualif[$att]);
				}
			} else {
				$q[$att] = $values;
				unset($qualif[$att]);
			}
		}
		// pas de rôle vide
		if (!$colonne_role || !isset($q[$colonne_role]) || $q[$colonne_role]) {
			$qualifs[] = $q;
		}
	}

	return $qualifs;
}

/**
 * Ajoute les liens demandés en prenant éventuellement en compte le rôle
 *
 * Appelle la fonction objet_associer. L'appelle autant de fois qu'il y
 * a de rôles demandés pour cette liaison.
 *
 * @internal
 * @param string $objet_source Objet source de la liaison (qui a la table de liaison)
 * @param array|string $ids Identifiants pour l'objet source
 * @param string $objet_lien Objet à lier
 * @param array|string $idl Identifiants pour l'objet lié
 * @param array $qualifs
 * @return void
 **/
function lien_ajouter_liaisons($objet_source, $ids, $objet_lien, $idl, $qualifs) {

	// retrouver la colonne de roles s'il y en a a lier
	if (is_array($qualifs) && count($qualifs)) {
		foreach ($qualifs as $qualif) {
			objet_associer([$objet_source => $ids], [$objet_lien => $idl], $qualif);
		}
	} else {
		objet_associer([$objet_source => $ids], [$objet_lien => $idl]);
	}
}



/**
 * Fonction de regroupement pour gerer le _oups de façon sécurisée sans passer par une globale ni par une _request
 * @return array|string|null
 */
function lien_gerer__oups(string $form, string $action, ?array $valeur = null) {
	static $_oups_value;

	switch ($action) {
		case 'reset':
			$res = !empty($_oups_value);
			$_oups_value = null;
			return $res;

		case 'get':
			return $_oups_value ?: null;

		case 'set':
			$_oups_value = $valeur;
			return true;

		case 'request':
			$_oups_value = null;
			if ($oups = _request('_oups')) {
				include_spip('inc/filtres');
				// on accepte uniquement une valeur signée
				if ($oups = decoder_contexte_ajax($oups, $form)) {
					if (
						!is_array($oups)
						|| empty($oups['id_auteur'])
						|| $oups['id_auteur'] !== $GLOBALS['visiteur_session']['id_auteur']
						|| empty($oups['time'])
						|| $oups['time'] < $_SERVER['REQUEST_TIME'] - 86400
						|| empty($oups['args'])
						|| $oups['args'] !== lien_gerer__oups_collecter_args($form, debug_backtrace(0, 5))
						|| empty($oups['oups_value'])
					) {
						$oups = null;
					}
					else {
						$oups = $oups['oups_value'];
						// controler le contenu
						foreach ($oups as $k => $oup) {
							if (!is_array($oup)) {
								unset($oups[$k]);
							} else {
								foreach ($oup as $champ => $valeur) {
									if (!is_scalar($champ) || !is_scalar($valeur) || preg_match(',\W,', $champ)) {
										unset($oups[$k][$champ]);
									}
								}
								if (empty($oups[$k])) {
									unset($oups[$k]);
								}
							}
						}
					}
					$_oups_value = $oups;
					return $_oups_value;
				}
			}
			break;

		case 'hash':
			if (!$_oups_value) {
				return '';
			}

			include_spip('inc/filtres');
			$oups = [
				'id_auteur' => $GLOBALS['visiteur_session']['id_auteur'] ?? 0,
				'time' => strtotime(date('Y-m-d H:00:00')),
				'args' => lien_gerer__oups_collecter_args($form, debug_backtrace(0, 5)),
				'oups_value' => $_oups_value,
			];
			return encoder_contexte_ajax($oups, $form);
	}
}

/**
 * Collecter les args du form utilisant la fonction lien_gerer__oups()
 * @param string $form
 * @param array $trace
 * @return string
 */
function lien_gerer__oups_collecter_args($form, $trace) {
	$args = '';
	if (!empty($trace)) {
		do {
			$t = array_shift($trace);
			$function = $t['function'] ?? '';
			if (str_starts_with((string) $function, 'formulaires_' . $form)) {
				if (isset($t['args'])) {
					$args = json_encode($t['args']);
				}
				break;
			}
		}
		while (count($trace));
	}
	return $args;
}
