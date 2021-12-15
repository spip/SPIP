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
 * Fonctions d'appel aux serveurs SQL presentes dans le code compile
 *
 * NB : à l'exception des fonctions pour les balises dynamiques
 *
 * @package SPIP\Core\Compilateur\Quetes
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


include_spip('base/abstract_sql');

/**
 * Retourne l'URL de redirection d'un article virtuel, seulement si il est publié
 *
 * @param $id_article
 * @param $connect
 * @return array|bool|null
 */
function quete_virtuel($id_article, $connect) {
	return sql_getfetsel(
		'virtuel',
		'spip_articles',
		['id_article=' . intval($id_article), "statut='publie'"],
		'',
		'',
		'',
		'',
		$connect
	);
}

/**
 * Retourne le couple `parent,lang` pour toute table
 *
 * En pratique `id_rubrique` si présent (ou `id_parent` pour table rubriques)
 * et champ `lang` si présent
 *
 * @param string $table
 * @param int $id
 * @param string $connect
 * @return array
 */
function quete_parent_lang($table, $id, string $connect = '') {
	static $cache_quete = [];

	if (!isset($cache_quete[$connect][$table][$id])) {
		if (!isset($cache_quete[$connect][$table]['_select'])) {
			$trouver_table = charger_fonction('trouver_table', 'base');
			if (
				!$desc = $trouver_table(
					$table,
					$connect
				) or !isset($desc['field']['id_rubrique'])
			) {
				// pas de parent rubrique, on passe
				$cache_quete[$connect][$table]['_select'] = false;
			} else {
				$select = ($table == 'spip_rubriques' ? 'id_parent' : 'id_rubrique');
				$select .= isset($desc['field']['lang']) ? ', lang' : '';
				$cache_quete[$connect][$table]['_select'] = $select;
				$cache_quete[$connect][$table]['_id'] = id_table_objet(objet_type($table));
			}
		}
		if ($cache_quete[$connect][$table]['_select']) {
			$cache_quete[$connect][$table][$id] = sql_fetsel(
				$cache_quete[$connect][$table]['_select'],
				$table,
				$cache_quete[$connect][$table]['_id'] . '=' . intval($id),
				'',
				'',
				'',
				'',
				$connect
			);
		}
	}

	return isset($cache_quete[$connect][$table][$id]) ? $cache_quete[$connect][$table][$id] : null;
}


/**
 * Retourne le parent d'une rubrique
 *
 * Repose sur la fonction quete_parent_lang pour la mutualisation
 * +mise en cache SQL des requêtes
 *
 * @uses quete_parent_lang()
 *
 * @param int $id_rubrique
 * @param string $connect
 * @return int
 */
function quete_parent($id_rubrique, string $connect = '') {
	if (!$id_rubrique = intval($id_rubrique)) {
		return 0;
	}
	$id_parent = quete_parent_lang('spip_rubriques', $id_rubrique, $connect);
	return $id_parent ? $id_parent['id_parent'] : 0;
}

/**
 * Retourne la rubrique d'un article
 *
 * Repose sur la fonction quete_parent_lang pour la mutualisation
 * +mise en cache SQL des requêtes
 *
 * @uses quete_parent_lang()
 *
 * @param int $id_article
 * @param $serveur
 * @return int
 */
function quete_rubrique($id_article, $serveur) {
	$id_parent = quete_parent_lang('spip_articles', $id_article, $serveur);

	return $id_parent['id_rubrique'];
}


/**
 * Retourne la profondeur d'une rubrique
 *
 * @uses quete_parent()
 *
 * @param int $id
 * @param string $connect
 * @return int
 */
function quete_profondeur($id, string $connect = '') {
	$n = 0;
	while ($id) {
		$n++;
		$id = quete_parent($id, $connect);
	}

	return $n;
}


/**
 * Retourne la condition sur la date lorsqu'il y a des post-dates
 *
 * @param string $champ_date
 *     Nom de la colonne de date dans la table SQL
 * @param string $serveur
 * @param bool $ignore_previsu
 *     true pour forcer le test même en prévisu
 * @return string
 *     Morceau de la requête SQL testant la date
 */
function quete_condition_postdates($champ_date, $serveur = '', $ignore_previsu = false) {
	if (defined('_VAR_PREVIEW') and _VAR_PREVIEW and !$ignore_previsu) {
		return '1=1';
	}

	return
		(isset($GLOBALS['meta']['date_prochain_postdate'])
			and $GLOBALS['meta']['date_prochain_postdate'] > time())
			? "$champ_date<" . sql_quote(date('Y-m-d H:i:s', $GLOBALS['meta']['date_prochain_postdate']), $serveur)
			: '1=1';
}


/**
 * Calculer la condition pour filtrer les status,
 *
 * @param string $mstatut
 *   Le champ de la table sur lequel porte la condition
 * @param string $previsu
 *   Mode previsu : statut ou liste des statuts séparés par une virgule
 * @param string $publie
 *   Mode publie : statut ou liste des statuts séparés par une virgule
 * @param string $serveur
 *   Serveur de BDD
 * @param bool $ignore_previsu
 *   true pour forcer le test même en prévisu
 * @return array|string
 */
function quete_condition_statut($mstatut, $previsu, $publie, $serveur = '', $ignore_previsu = false) {
	static $cond = [];
	$key = func_get_args();
	$key = implode('-', $key);
	if (isset($cond[$key])) {
		return $cond[$key];
	}

	$liste_statuts = $publie;
	if (defined('_VAR_PREVIEW') and _VAR_PREVIEW and !$ignore_previsu) {
		$liste_statuts = $previsu;
	}
	$not = false;
	if (strncmp($liste_statuts, '!', 1) == 0) {
		$not = true;
		$liste_statuts = substr($liste_statuts, 1);
	}
	// '' => ne rien afficher, '!'=> ne rien filtrer
	if (!strlen($liste_statuts)) {
		return $cond[$key] = ($not ? '1=1' : '0=1');
	}

	$liste_statuts = explode(',', $liste_statuts);
	$where = [];
	foreach ($liste_statuts as $k => $v) {
		// filtrage /auteur pour limiter les objets d'un statut (prepa en general)
		// a ceux de l'auteur identifie
		if (strpos($v, '/') !== false) {
			$v = explode('/', $v);
			$filtre = end($v);
			$v = reset($v);
			$v = preg_replace(',\W,', '', $v);
			if (
				$filtre == 'auteur'
				and (strpos($mstatut, '.') !== false)
				and $objet = explode('.', $mstatut)
				and $id_table = reset($objet)
				and $objet = objet_type($id_table)
			) {
				$w = "$mstatut<>" . sql_quote($v);

				// retrouver l’id_auteur qui a filé un lien de prévisu éventuellement,
				// sinon l’auteur en session
				include_spip('inc/securiser_action');
				if ($desc = decrire_token_previsu()) {
					$id_auteur = $desc['id_auteur'];
				} elseif (isset($GLOBALS['visiteur_session']['id_auteur'])) {
					$id_auteur = intval($GLOBALS['visiteur_session']['id_auteur']);
				} else {
					$id_auteur = null;
				}

				// dans ce cas (admin en general), pas de filtrage sur ce statut
				if (!autoriser('previsualiser' . $v, $objet, '', $id_auteur)) {
					// si pas d'auteur identifie pas de sous-requete car pas d'article qui matche
					if (!$id_auteur) {
						$where[] = $w;
					} else {
						$primary = id_table_objet($objet);
						$where[] = "($w OR $id_table.$primary IN (" . sql_get_select(
							'ssss.id_objet',
							'spip_auteurs_liens AS ssss',
							'ssss.objet=' . sql_quote($objet) . ' AND ssss.id_auteur=' . intval($id_auteur),
							'',
							'',
							'',
							'',
							$serveur
						) . '))';
					}
				}
			} // ignorer ce statut si on ne sait pas comment le filtrer
			else {
				$v = '';
			}
		}
		// securite
		$liste_statuts[$k] = preg_replace(',\W,', '', $v);
	}
	$liste_statuts = array_filter($liste_statuts);
	if (count($liste_statuts) == 1) {
		$where[] = ['=', $mstatut, sql_quote(reset($liste_statuts), $serveur)];
	} else {
		$where[] = sql_in($mstatut, $liste_statuts, $not, $serveur);
	}

	while (count($where) > 1) {
		$and = ['AND', array_pop($where), array_pop($where)];
		$where[] = $and;
	}
	$cond[$key] = reset($where);
	if ($not) {
		$cond[$key] = ['NOT', $cond[$key]];
	}

	return $cond[$key];
}

/**
 * Retourne le fichier d'un document
 *
 * @param int $id_document
 * @param string $serveur
 * @return array|bool|null
 */
function quete_fichier($id_document, $serveur = '') {
	return sql_getfetsel('fichier', 'spip_documents', ('id_document=' . intval($id_document)), '', [], '', '', $serveur);
}

/**
 * Toute les infos sur un document
 *
 * @param int $id_document
 * @param string $serveur
 * @return array|bool
 */
function quete_document($id_document, $serveur = '') {
	return sql_fetsel('*', 'spip_documents', ('id_document=' . intval($id_document)), '', [], '', '', $serveur);
}

/**
 * Récuperer une meta sur un site (spip) distant (en local il y a plus simple)
 *
 * @param string $nom Nom de la méta
 * @param string $serveur Connecteur
 * @return array|bool|null
 */
function quete_meta($nom, $serveur) {
	return sql_getfetsel('valeur', 'spip_meta', 'nom=' . sql_quote($nom), '', '', '', '', $serveur);
}

/**
 * Retourne le logo d'un objet, éventuellement par héritage
 *
 * Si flag != false, retourne le chemin du fichier, sinon retourne un tableau
 * de 3 elements :
 * le chemin du fichier, celui du logo de survol, l'attribut style=w/h.
 *
 * @param string $cle_objet
 *     Nom de la clé de l'objet dont on veut chercher le logo.
 * @param string $onoff
 *     Sélectionne quel(s) logo(s) : "on" pour le logo normal, "off" pour le logo de survol, ou "ON" pour l'ensemble.
 * @param int $id
 *     Identifiant de l'objet dont on veut chercher le logo.
 * @param int $id_rubrique
 *     Identifiant de la rubrique parente si l'on veut aller chercher son logo
 *     dans le cas où l'objet demandé n'en a pas.
 * @param bool $flag
 *     Lorsque le drapeau est évalué comme "true", la fonction ne renvoie
 *     que le chemin du fichier, sinon elle renvoie le tableau plus complet.
 * @return array|string
 *     Retourne soit un tableau, soit le chemin du fichier.
 */
function quete_logo($cle_objet, $onoff, $id, $id_rubrique, $flag) {
	include_spip('base/objets');
	$nom = strtolower($onoff);

	$cle_objet = id_table_objet($cle_objet);

	while (1) {
		$objet = objet_type($cle_objet);

		$on = quete_logo_objet($id, $objet, $nom);

		if ($on) {
			if ($flag) {
				return basename($on['chemin']);
			} else {
				$taille = @spip_getimagesize($on['chemin']);

				// Si on a déjà demandé un survol directement ($onoff = off)
				// ou qu'on a demandé uniquement le normal ($onoff = on)
				// alors on ne cherche pas du tout le survol ici
				if ($onoff != 'ON') {
					$off = '';
				} else {
					// Sinon, c'est qu'on demande normal ET survol à la fois, donc on cherche maintenant le survol
					$off = quete_logo_objet($id, $objet, 'off');
				}

				// on retourne une url du type IMG/artonXX?timestamp
				// qui permet de distinguer le changement de logo
				// et placer un expire sur le dossier IMG/
				$res = [
					$on['chemin'] . ($on['timestamp'] ? "?{$on['timestamp']}" : ''),
					($off ? $off['chemin'] . ($off['timestamp'] ? "?{$off['timestamp']}" : '') : ''),
					(!$taille ? '' : (' ' . $taille[3]))
				];
				$res['src'] = $res[0];
				$res['logo_on'] = $res[0];
				$res['logo_off'] = $res[1];
				$res['width'] = ($taille ? $taille[0] : '');
				$res['height'] = ($taille ? $taille[1] : '');

				return $res;
			}
		} else {
			if (defined('_LOGO_RUBRIQUE_DESACTIVER_HERITAGE')) {
				return '';
			} else {
				if ($id_rubrique) {
					$cle_objet = 'id_rubrique';
					$id = $id_rubrique;
					$id_rubrique = 0;
				} else {
					if ($id and $cle_objet == 'id_rubrique') {
						$id = quete_parent($id);
					} else {
						return '';
					}
				}
			}
		}
	}
}

/**
 * Chercher le logo d'un contenu précis
 *
 * @param int $id_objet
 * 		Idenfiant de l'objet dont on cherche le logo
 * @param string $objet
 * 		Type de l'objet dont on cherche le logo
 * @param string $mode
 * 		"on" ou "off" suivant le logo normal ou survol
 **/
function quete_logo_objet($id_objet, $objet, $mode) {
	static $chercher_logo;
	if (is_null($chercher_logo)) {
		$chercher_logo = charger_fonction('chercher_logo', 'inc');
	}
	$cle_objet = id_table_objet($objet);

	// On cherche pas la méthode classique
	$infos_logo = $chercher_logo($id_objet, $cle_objet, $mode);
	// Si la méthode classique a trouvé quelque chose, on utilise le nouveau format
	if (!empty($infos_logo)) {
		$infos_logo = [
			'chemin' => $infos_logo[0],
			'timestamp' => $infos_logo[4],
		];
	}

	// On passe cette recherche de logo dans un pipeline
	$infos_logo = pipeline(
		'quete_logo_objet',
		[
			'args' => [
				'id_objet' => $id_objet,
				'objet' => $objet,
				'cle_objet' => $cle_objet,
				'mode' => $mode,
			],
			'data' => $infos_logo,
		]
	);

	return $infos_logo;
}

/**
 * Retourne le logo d’un fichier (document spip) sinon la vignette du type du fichier
 *
 * Fonction appeleé par la balise `#LOGO_DOCUMENT`
 *
 * @param array $row
 * @param string $connect
 * @return bool|string
 */
function quete_logo_file($row, $connect = null) {
	include_spip('inc/documents');
	$logo = vignette_logo_document($row, $connect);
	if (!$logo) {
		$logo = image_du_document($row, $connect);
	}
	if (!$logo) {
		$f = charger_fonction('vignette', 'inc');
		$logo = $f($row['extension'], false);
	}
	// si c'est une vignette type doc, la renvoyer direct
	if (
		strcmp($logo, _DIR_PLUGINS) == 0
		or strcmp($logo, _DIR_PLUGINS_DIST) == 0
		or strcmp($logo, _DIR_RACINE . 'prive/') == 0
	) {
		return $logo;
	}

	return get_spip_doc($logo);
}

/**
 * Trouver l'image logo d'un document
 *
 * @param  $row
 *   description du document, issue de la base
 * @param  $lien
 *   url de lien
 * @param  $align
 *   alignement left/right
 * @param  $mode_logo
 *   mode du logo :
 *     '' => automatique (vignette sinon apercu sinon icone)
 *     icone => icone du type du fichier
 *     apercu => apercu de l'image exclusivement, meme si une vignette existe
 *     vignette => vignette exclusivement, ou rien si elle n'existe pas
 * @param  $x
 *   largeur maxi
 * @param  $y
 *   hauteur maxi
 * @param string $connect
 *   serveur
 * @return string
 */
function quete_logo_document($row, $lien, $align, $mode_logo, $x, $y, string $connect = '') {

	include_spip('inc/documents');
	$logo = '';
	if (!in_array($mode_logo, ['icone', 'apercu'])) {
		$logo = vignette_logo_document($row, $connect);
	}
	// si on veut explicitement la vignette, ne rien renvoyer si il n'y en a pas
	if ($mode_logo == 'vignette' and !$logo) {
		return '';
	}
	if ($mode_logo == 'icone') {
		$row['fichier'] = '';
	}

	return vignette_automatique($logo, $row, $lien, $x, $y, $align, null, $connect);
}

/**
 * Recuperer le HTML du logo d'apres ses infos
 * @param $logo
 * @param $align
 * @param $lien
 * @return string
 */
function quete_html_logo($logo, $align, $lien) {

	if (!is_array($logo)) {
		return '';
	}

	$contexte = [];
	foreach ($logo as $k => $v) {
		if (!is_numeric($k)) {
			$contexte[$k] = $v;
		}
	}
	$contexte['align'] = $align;
	$contexte['lien'] = $lien;
	return recuperer_fond('modeles/logo', $contexte);
}

/**
 * Retourne le chemin d’un document lorsque le connect est précisé
 *
 * Sur un connecteur distant, voir si on connait l’adresse du site (spip distant)
 * et l’utiliser le cas échéant.
 *
 * @param string $fichier Chemin
 * @param string $connect Nom du connecteur
 * @return string|false
 */
function document_spip_externe($fichier, $connect) {
	if ($connect) {
		$site = quete_meta('adresse_site', $connect);
		if ($site) {
			$dir = quete_meta('dir_img', $connect);
			return "$site/$dir$fichier";
		}
	}
	return false;
}

/**
 * Retourne la vignette explicitement attachee a un document
 * le resutat est un fichier local existant, ou une URL
 * ou vide si pas de vignette
 *
 * @param array $row
 * @param string $connect
 * @return string
 */
function vignette_logo_document($row, string $connect = '') {
	if (!$row['id_vignette']) {
		return '';
	}
	$fichier = quete_fichier($row['id_vignette'], $connect);
	if ($url = document_spip_externe($fichier, $connect)) {
		return $url;
	}

	$f = get_spip_doc($fichier);
	if ($f and @file_exists($f)) {
		return $f;
	}
	if ($row['mode'] !== 'vignette') {
		return '';
	}

	return generer_url_entite($row['id_document'], 'document', '', '', $connect);
}

/**
 * Calcul pour savoir si un objet est expose dans le contexte
 * fournit par $reference
 *
 * @param int $id
 * @param string $prim
 * @param array $reference
 * @param int $parent
 * @param string $type
 * @param string $connect
 * @return bool|string
 */
function calcul_exposer($id, $prim, $reference, $parent, $type, string $connect = '') {
	static $exposer = [];

	// Que faut-il exposer ? Tous les elements de $reference
	// ainsi que leur hierarchie ; on ne fait donc ce calcul
	// qu'une fois (par squelette) et on conserve le resultat
	// en static.
	if (!isset($exposer[$m = md5(serialize($reference))][$prim])) {
		$principal = isset($reference[$type]) ? $reference[$type] :
			// cas de la pagination indecte @xx qui positionne la page avec l'id xx
			// et donne la reference dynamique @type=xx dans le contexte
			(isset($reference["@$type"]) ? $reference["@$type"] : '');
		// le parent fournit en argument est le parent de $id, pas celui de $principal
		// il n'est donc pas utile
		$parent = 0;
		if (!$principal) { // regarder si un enfant est dans le contexte, auquel cas il expose peut etre le parent courant
			$enfants = ['id_rubrique' => ['id_article'], 'id_groupe' => ['id_mot']];
			if (isset($enfants[$type])) {
				foreach ($enfants[$type] as $t) {
					if (
						isset($reference[$t])
						// cas de la reference donnee dynamiquement par la pagination
						or isset($reference["@$t"])
					) {
						$type = $t;
						$principal = isset($reference[$type]) ? $reference[$type] : $reference["@$type"];
						continue;
					}
				}
			}
		}
		$exposer[$m][$type] = [];
		if ($principal) {
			$principaux = is_array($principal) ? $principal : [$principal];
			foreach ($principaux as $principal) {
				$exposer[$m][$type][$principal] = true;
				if ($type == 'id_mot') {
					if (!$parent) {
						$parent = sql_getfetsel('id_groupe', 'spip_mots', 'id_mot=' . intval($principal), '', '', '', '', $connect);
					}
					if ($parent) {
						$exposer[$m]['id_groupe'][$parent] = true;
					}
				} else {
					if ($type != 'id_groupe') {
						if (!$parent) {
							if ($type == 'id_rubrique') {
								$parent = $principal;
							}
							if ($type == 'id_article') {
								$parent = quete_rubrique($principal, $connect);
							}
						}
						do {
							$exposer[$m]['id_rubrique'][$parent] = true;
						} while ($parent = quete_parent($parent, $connect));
					}
				}
			}
		}
	}

	// And the winner is...
	return isset($exposer[$m][$prim]) ? isset($exposer[$m][$prim][$id]) : '';
}

/**
 * Trouver le numero de page d'une pagination indirecte
 * lorsque debut_xxx=@123
 * on cherche la page qui contient l'item dont la cle primaire vaut 123
 *
 * @param string $primary
 * @param int|string $valeur
 * @param int $pas
 * @param objetc $iter
 * @return int
 */
function quete_debut_pagination($primary, $valeur, $pas, $iter) {
	// on ne devrait pas arriver ici si la cle primaire est inexistante
	// ou composee, mais verifions
	if (!$primary or preg_match('/[,\s]/', $primary)) {
		return 0;
	}

	$pos = 0;
	while ($row = $iter->fetch() and $row[$primary] != $valeur) {
		$pos++;
	}
	// si on a pas trouve
	if (!$row or $row[$primary] != $valeur) {
		return 0;
	}

	// sinon, calculer le bon numero de page
	return floor($pos / $pas) * $pas;
}

/**
 * Retourne true si ce where doit être appliqué,
 * dans le cas des critères avec ? tel que `{id_article ?}`
 *
 * @param mixed $value
 * @return boolean
 */
function is_whereable($value) : bool {
	if (is_array($value) && count($value)) {
		return true;
	}
	if (is_scalar($value) && strlen($value)) {
		return true;
	}
	return false;
}