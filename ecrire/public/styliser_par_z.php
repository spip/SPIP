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
 * Gestion de la sélection d'un squelette depuis son nom parmi les
 * chemins connus de SPIP, dans un contexte de type Z
 *
 * Recherche par exemple `contenu\xx` et en absence utilisera `contenu\dist`
 *
 * @package SPIP\Core\Public\Styliser
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Recherche automatique d'un squelette Page à partir de `contenu/xx`
 *
 * @param array $flux Données du pipeline
 * @return array Données modifiées du pipeline
 */
function public_styliser_par_z_dist($flux) {
	static $prefix_path = null;
	static $prefix_length;
	static $z_blocs;
	static $apl_constant;
	static $page;
	static $disponible = [];
	static $echafauder;
	static $prepend = '';

	if (!isset($prefix_path)) {
		$z_blocs = z_blocs(test_espace_prive());
		if (test_espace_prive()) {
			$prefix_path = 'prive/squelettes/';
			$prefix_length = strlen($prefix_path);
			$apl_constant = '_ECRIRE_AJAX_PARALLEL_LOAD';
			$page = 'exec';
			$echafauder = charger_fonction('echafauder', 'prive', true);
			define('_ZCORE_EXCLURE_PATH', '');
		} else {
			$prefix_path = '';
			$prefix_length = 0;
			$apl_constant = '_Z_AJAX_PARALLEL_LOAD';
			$page = _SPIP_PAGE;
			$echafauder = charger_fonction('echafauder', 'public', true);
			define('_ZCORE_EXCLURE_PATH', '\bprive|\bsquelettes-dist' . (defined('_DIR_PLUGIN_DIST') ? '|\b' . rtrim(
				_DIR_PLUGIN_DIST,
				'/'
			) : ''));
		}
		$prepend = (defined('_Z_PREPEND_PATH') ? _Z_PREPEND_PATH : '');
	}
	$z_contenu = reset($z_blocs); // contenu par defaut

	$fond = $flux['args']['fond'];

	if ($prepend or strncmp($fond, $prefix_path, $prefix_length) == 0) {
		$fond = substr($fond, $prefix_length);
		$squelette = $flux['data'];
		$ext = $flux['args']['ext'];
		// Ajax Parallel loading : ne pas calculer le bloc, mais renvoyer un js qui le loadera en ajax
		if (
			defined('_Z_AJAX_PARALLEL_LOAD_OK')
			and $dir = explode('/', $fond)
			and count($dir) == 2 // pas un sous repertoire
			and $dir = reset($dir)
			and in_array($dir, $z_blocs) // verifier deja qu'on est dans un bloc Z
			and defined($apl_constant)
			and in_array($dir, explode(',', constant($apl_constant))) // et dans un demande en APL
			and $pipe = z_trouver_bloc($prefix_path . $prepend, $dir, 'z_apl', $ext) // et qui contient le squelette APL
		) {
			$flux['data'] = $pipe;

			return $flux;
		}

		// surcharger aussi les squelettes venant de squelettes-dist/
		if ($squelette and !z_fond_valide($squelette)) {
			$squelette = '';
			$echafauder = '';
		}
		if ($prepend) {
			$squelette = substr(find_in_path($prefix_path . $prepend . "$fond.$ext"), 0, -strlen(".$ext"));
			if ($squelette) {
				$flux['data'] = $squelette;
			}
		}

		// gerer les squelettes non trouves
		// -> router vers les /dist.html
		// ou scaffolding ou page automatique les contenus
		if (!$squelette) {
			// si on est sur un ?page=XX non trouve
			if (
				(isset($flux['args']['contexte'][$page])
					and $flux['args']['contexte'][$page] == $fond)
				or (isset($flux['args']['contexte']['type-page'])
					and $flux['args']['contexte']['type-page'] == $fond)
				or ($fond == 'sommaire'
					and (!isset($flux['args']['contexte'][$page]) or !$flux['args']['contexte'][$page]))
			) {
				// si on est sur un ?page=XX non trouve
				// se brancher sur contenu/xx si il existe
				// ou si c'est un objet spip, associe a une table, utiliser le fond homonyme
				if (!isset($disponible[$fond])) {
					$disponible[$fond] = z_contenu_disponible($prefix_path . $prepend, $z_contenu, $fond, $ext, $echafauder);
				}

				if ($disponible[$fond]) {
					$flux['data'] = substr(find_in_path($prefix_path . "page.$ext"), 0, -strlen(".$ext"));
				}
			}

			// echafaudage :
			// si c'est un fond de contenu d'un objet en base
			// generer un fond automatique a la volee pour les webmestres
			elseif (strncmp($fond, "$z_contenu/", strlen($z_contenu) + 1) == 0) {
				$type = substr($fond, strlen($z_contenu) + 1);
				if (($type == 'page') and isset($flux['args']['contexte'][$page])) {
					$type = $flux['args']['contexte'][$page];
				}
				if (!isset($disponible[$type])) {
					$disponible[$type] = z_contenu_disponible($prefix_path . $prepend, $z_contenu, $type, $ext, $echafauder);
				}
				if (is_string($disponible[$type])) {
					$flux['data'] = $disponible[$type];
				} elseif (
					$echafauder
					and include_spip('inc/autoriser')
					and isset($GLOBALS['visiteur_session']['statut']) // performance
					and autoriser('echafauder', $type)
					and $is = $disponible[$type]
					and is_array($is)
				) {
					$flux['data'] = $echafauder($type, $is[0], $is[1], $is[2], $ext);
				} else {
					$flux['data'] = ($disponible['404'] = z_contenu_disponible(
						$prefix_path . $prepend,
						$z_contenu,
						'404',
						$ext,
						$echafauder
					));
				}
			}

			// sinon, si on demande un fond non trouve dans un des autres blocs
			// et si il y a bien un contenu correspondant ou echafaudable
			// se rabbatre sur le dist.html du bloc concerne
			else {
				if (
					$dir = explode('/', $fond)
					and $dir = reset($dir)
					and $dir !== $z_contenu
					and in_array($dir, $z_blocs)
				) {
					$type = substr($fond, strlen("$dir/"));
					if (($type == 'page') and isset($flux['args']['contexte'][$page])) {
						$type = $flux['args']['contexte'][$page];
					}
					if ($type !== 'page' and !isset($disponible[$type])) {
						$disponible[$type] = z_contenu_disponible($prefix_path . $prepend, $z_contenu, $type, $ext, $echafauder);
					}
					if ($type == 'page' or $disponible[$type]) {
						$flux['data'] = z_trouver_bloc($prefix_path . $prepend, $dir, 'dist', $ext);
					}
				}
			}
			$squelette = $flux['data'];
		}
		// layout specifiques par type et compositions :
		// body-article.html
		// body-sommaire.html
		// pour des raisons de perfo, les declinaisons doivent etre dans le
		// meme dossier que body.html
		if ($fond == 'body' and substr($squelette, -strlen($fond)) == $fond) {
			if (
				isset($flux['args']['contexte']['type-page'])
				and (
					(isset($flux['args']['contexte']['composition'])
						and file_exists(($f = $squelette . '-' . $flux['args']['contexte']['type-page'] . '-' . $flux['args']['contexte']['composition']) . ".$ext"))
					or
					file_exists(($f = $squelette . '-' . $flux['args']['contexte']['type-page']) . ".$ext")
				)
			) {
				$flux['data'] = $f;
			}
		} elseif (
			$fond == 'structure'
			and z_sanitize_var_zajax()
			and $f = find_in_path($prefix_path . $prepend . 'ajax' . ".$ext")
		) {
			$flux['data'] = substr($f, 0, -strlen(".$ext"));
		} // chercher le fond correspondant a la composition
		elseif (
			isset($flux['args']['contexte']['composition'])
			and (basename($fond) == 'page' or ($squelette and substr($squelette, -strlen($fond)) == $fond))
			and $dir = substr($fond, $prefix_length)
			and $dir = explode('/', $dir)
			and $dir = reset($dir)
			and in_array($dir, $z_blocs)
			and $f = find_in_path($prefix_path . $prepend . $fond . '-' . $flux['args']['contexte']['composition'] . ".$ext")
		) {
			$flux['data'] = substr($f, 0, -strlen(".$ext"));
		}
	}

	return $flux;
}

/**
 * Lister les blocs de la page selon le contexte prive/public
 *
 * @param bool $espace_prive
 * @return array
 */
function z_blocs($espace_prive = false) {
	if ($espace_prive) {
		return (isset($GLOBALS['z_blocs_ecrire']) ? $GLOBALS['z_blocs_ecrire'] : [
			'contenu',
			'navigation',
			'extra',
			'head',
			'hierarchie',
			'top'
		]);
	}

	return (isset($GLOBALS['z_blocs']) ? $GLOBALS['z_blocs'] : ['contenu']);
}

/**
 * Vérifie qu'un type à un contenu disponible,
 * soit parcequ'il a un fond, soit parce qu'il est echafaudable
 *
 * @param string $prefix_path
 * @param string $z_contenu
 * @param string $type
 * @param string $ext
 * @param bool $echafauder
 * @return mixed
 */
function z_contenu_disponible($prefix_path, $z_contenu, $type, $ext, $echafauder = true) {
	if ($d = z_trouver_bloc($prefix_path, $z_contenu, $type, $ext)) {
		return $d;
	}

	return $echafauder ? z_echafaudable($type) : false;
}

/**
 * Teste si le fond de squelette trouvé est autorisé
 *
 * Compare le chemin du squelette trouvé avec les chemins exclus connus.
 *
 * @param string $squelette
 *   Un chemin de squelette
 * @return bool
 *   `true` si on peut l'utiliser, `false` sinon.
 **/
function z_fond_valide($squelette) {
	if (
		!_ZCORE_EXCLURE_PATH
		or !preg_match(',(' . _ZCORE_EXCLURE_PATH . ')/,', $squelette)
	) {
		return true;
	}

	return false;
}

/**
 * Trouve un bloc qui peut être sous le nom
 * `contenu/article.html` ou `contenu/contenu.article.html`
 *
 * @param string $prefix_path
 *  chemin de base qui prefixe la recherche
 * @param string $bloc
 *  nom du bloc cherche
 * @param string $fond
 *  nom de la page (ou 'dist' pour le bloc par defaut)
 * @param string $ext
 *  extension du squelette
 * @return string
 */
function z_trouver_bloc($prefix_path, $bloc, $fond, $ext) {
	if (
		(defined('_ZCORE_BLOC_PREFIX_SKEL') and $f = find_in_path("$prefix_path$bloc/$bloc.$fond.$ext") and z_fond_valide($f))
		or ($f = find_in_path("$prefix_path$bloc/$fond.$ext") and z_fond_valide($f))
	) {
		return substr($f, 0, -strlen(".$ext"));
	}

	return '';
}

/**
 * Tester si un type est echafaudable
 * c'est à dire s'il correspond bien à un objet en base
 *
 * @staticvar array $echafaudable
 * @param string $type
 * @return bool
 */
function z_echafaudable($type) {
	static $pages = null;
	static $echafaudable = [];
	if (isset($echafaudable[$type])) {
		return $echafaudable[$type];
	}
	if (preg_match(',[^\w],', $type)) {
		return $echafaudable[$type] = false;
	}

	if (test_espace_prive()) {
		if (!function_exists('trouver_objet_exec')) {
			include_spip('inc/pipelines_ecrire');
		}
		if ($e = trouver_objet_exec($type)) {
			return $echafaudable[$type] = [$e['table'], $e['table_objet_sql'], $e];
		} else {
			// peut etre c'est un exec=types qui liste tous les objets "type"
			if (
				($t = objet_type($type, false)) !== $type
				and $e = trouver_objet_exec($t)
			) {
				return $echafaudable[$type] = [$e['table'], $e['table_objet_sql'], $t];
			}
		}
	} else {
		if (is_null($pages)) {
			$pages = [];
			$liste = lister_tables_objets_sql();
			foreach ($liste as $t => $d) {
				if ($d['page']) {
					$pages[$d['page']] = [$d['table_objet'], $t];
				}
			}
		}
		if (!isset($pages[$type])) {
			return $echafaudable[$type] = false;
		}
		if (count($pages[$type]) == 2) {
			$trouver_table = charger_fonction('trouver_table', 'base');
			$pages[$type][] = $trouver_table(reset($pages[$type]));
		}

		return $echafaudable[$type] = $pages[$type];
	}

	return $echafaudable[$type] = false;
}


/**
 * Generer a la volee un fond a partir d'un contenu connu
 * tous les squelettes d'echafaudage du prive sont en fait explicites dans prive/echafaudage
 * on ne fait qu'un mini squelette d'inclusion pour reecrire les variables d'env
 *
 * @param string $exec
 * @param string $table
 * @param string $table_sql
 * @param array $desc_exec
 * @param string $ext
 * @return string
 */
function prive_echafauder_dist($exec, $table, $table_sql, $desc_exec, $ext) {
	$scaffold = '';

	// page objet ou objet_edit
	if (is_array($desc_exec)) {
		$type = $desc_exec['type'];
		$primary = $desc_exec['id_table_objet'];

		if ($desc_exec['edition'] === false) {
			$fond = 'objet';
		} else {
			$trouver_table = charger_fonction('trouver_table', 'base');
			$desc = $trouver_table($table_sql);
			if (isset($desc['field']['id_rubrique'])) {
				$fond = 'objet_edit';
			} else {
				$fond = 'objet_edit.sans_rubrique';
			}
		}
		$dir = z_blocs(test_espace_prive());
		$dir = reset($dir);
		$scaffold = "<INCLURE{fond=prive/echafaudage/$dir/" . $fond . ',objet=' . $type . ',id_objet=#' . strtoupper($primary) . ',env}>';
	} // page objets
	elseif ($type = $desc_exec and strpos($type, '/') === false) {
		$dir = z_blocs(test_espace_prive());
		$dir = reset($dir);
		$scaffold = "<INCLURE{fond=prive/echafaudage/$dir/objets,objet=" . $type . ',env} />';
	}
	// morceau d'objet : on fournit le fond de sibstitution dans $desc_exec
	// et objet et tire de $table
	elseif ($fond = $desc_exec) {
		$dir = md5(dirname($fond));
		$scaffold = "<INCLURE{fond=$fond,objet=" . objet_type($table) . ',env} />';
	}

	$base_dir = sous_repertoire(_DIR_CACHE, 'scaffold', false);
	$base_dir = sous_repertoire($base_dir, $dir, false);
	$f = $base_dir . "$exec";
	ecrire_fichier("$f.$ext", $scaffold);

	return $f;
}

/**
 * Recuperer et verifier var_zajax si demande dans l'url
 *
 * @return bool|string
 */
function z_sanitize_var_zajax() {
	$z_ajax = _request('var_zajax');
	if (!$z_ajax) {
		return false;
	}
	if (
		!$z_blocs = z_blocs(test_espace_prive())
		or !in_array($z_ajax, $z_blocs)
	) {
		set_request('var_zajax'); // enlever cette demande incongrue
		$z_ajax = false;
	}

	return $z_ajax;
}
