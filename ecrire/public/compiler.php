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
 * Fichier principal du compilateur de squelettes
 *
 * @package SPIP\Core\Compilateur\Compilation
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/** Repérer un code ne calculant rien, meme avec commentaire */
define('CODE_MONOTONE', ",^(\n//[^\n]*\n)?\(?'([^'])*'\)?$,");
/** Indique s'il faut commenter le code produit */
define('CODE_COMMENTE', true);

// definition des structures de donnees
include_spip('public/interfaces');

// Definition de la structure $p, et fonctions de recherche et de reservation
// dans l'arborescence des boucles
include_spip('public/references');

// production du code qui peut etre securisee
include_spip('public/sandbox');

// definition des boucles
include_spip('public/boucles');

// definition des criteres
include_spip('public/criteres');

// definition des balises
include_spip('public/balises');

// Gestion des jointures
include_spip('public/jointures');

// Les 2 ecritures INCLURE{A1,A2,A3...} et INCLURE(A1){A2}{A3}... sont admises
// Preferer la premiere.
// Les Ai sont de la forme Vi=Ei ou bien Vi qui veut alors dire Vi=Vi
// Le resultat est un tableau indexe par les Vi
// Toutefois, si le premier argument n'est pas de la forme Vi=Ei
// il est conventionnellement la valeur de l'index 1.
// pour la balise #INCLURE
// mais pas pour <INCLURE> dont le fond est defini explicitement.


// https://code.spip.net/@argumenter_inclure
function argumenter_inclure(
	$params,
	$rejet_filtres,
	$p,
	&$boucles,
	$id_boucle,
	$echap = true,
	$lang = '',
	$fond1 = false
) {
	$l = [];
	$erreur_p_i_i = '';
	if (!is_array($params)) {
		return $l;
	}
	foreach ($params as $k => $couple) {
		// la liste d'arguments d'inclusion peut se terminer par un filtre
		$filtre = array_shift($couple);
		if ($filtre) {
			break;
		}
		foreach ($couple as $n => $val) {
			$var = $val[0];
			if ($var->type != 'texte') {
				if ($n or $k or $fond1) {
					$erreur_p_i_i = [
						'zbug_parametres_inclus_incorrects',
						['param' => $var->nom_champ]
					];
					erreur_squelette($erreur_p_i_i, $p);
					break;
				} else {
					$l[1] = calculer_liste($val, $p->descr, $boucles, $id_boucle);
				}
			} else {
				preg_match(',^([^=]*)(=?)(.*)$,m', $var->texte, $m);
				$m = array_pad($m, 3, null);
				$var = $m[1];
				$auto = false;
;
				if ($m[2]) {
					$v = $m[3];
					if (preg_match(',^[\'"](.*)[\'"]$,', $v, $m)) {
						$v = $m[1];
					}
					$val[0] = new Texte();
					$val[0]->texte = $v;
				} elseif ($k or $n or $fond1) {
					$auto = true;
				} else {
					$var = 1;
				}

				if ($var == 'lang') {
					$lang = !$auto
						? calculer_liste($val, $p->descr, $boucles, $id_boucle)
						: '$GLOBALS["spip_lang"]';
				} else {
					$val = $auto
						? index_pile($id_boucle, $var, $boucles)
						: calculer_liste($val, $p->descr, $boucles, $id_boucle);
					if ($var !== 1) {
						$val = ($echap ? "\'$var\' => ' . argumenter_squelette(" : "'$var' => ")
							. $val . ($echap ? ") . '" : ' ');
					} else {
						$val = $echap ? "'.$val.'" : $val;
					}
					$l[$var] = $val;
				}
			}
		}
	}
	if ($erreur_p_i_i) {
		return false;
	}
	// Cas particulier de la langue : si {lang=xx} est definie, on
	// la passe, sinon on passe la langue courante au moment du calcul
	// sauf si on n'en veut pas
	if ($lang === false) {
		return $l;
	}
	if (!$lang) {
		$lang = '$GLOBALS["spip_lang"]';
	}
	$l['lang'] = ($echap ? "\'lang\' => ' . argumenter_squelette(" : "'lang' => ") . $lang . ($echap ? ") . '" : ' ');

	return $l;
}

/**
 * Code d'appel à un <INCLURE()>
 *
 * Code PHP pour un squelette (aussi pour #INCLURE, #MODELE #LES_AUTEURS)
 */
define('CODE_RECUPERER_FOND', 'recuperer_fond(%s, %s, array(%s), %s)');

/**
 * Compile une inclusion <INCLURE> ou #INCLURE
 *
 * @param Inclure $p
 *     Description de l'inclusion (AST au niveau de l'inclure)
 * @param array $boucles
 *     AST du squelette
 * @param string $id_boucle
 *     Identifiant de la boucle contenant l'inclure
 * @return string
 *     Code PHP appelant l'inclusion
 **/
function calculer_inclure($p, &$boucles, $id_boucle) {

	$_contexte = argumenter_inclure($p->param, false, $p, $boucles, $id_boucle, true, '', true);
	if (is_string($p->texte)) {
		$fichier = $p->texte;
		$code = '"' . str_replace('"', '\"', $fichier) . '"';
	} else {
		$code = calculer_liste($p->texte, $p->descr, $boucles, $id_boucle);
		if ($code and preg_match("/^'([^']*)'/s", $code, $r)) {
			$fichier = $r[1];
		} else {
			$fichier = '';
		}
	}
	if (!$code or $code === '""' or $code === "''") {
		$trace = $p->fonctions;
		while (
			is_array($trace)
			and $trace = array_filter($trace)
			and count($trace) == 1
		) {
			$trace = reset($trace);
		}
		$erreur_p_i_i = [
			'zbug_parametres_inclus_incorrects',
			['param' => print_r($trace, true)]
		];
		erreur_squelette($erreur_p_i_i, $p);

		return "''";
	}
	$compil = texte_script(memoriser_contexte_compil($p));

	if (is_array($_contexte)) {
		// Critere d'inclusion {env} (et {self} pour compatibilite ascendante)
		if ($env = (isset($_contexte['env']) || isset($_contexte['self']))) {
			unset($_contexte['env']);
		}

		// noter les doublons dans l'appel a public.php
		if (isset($_contexte['doublons'])) {
			$_contexte['doublons'] = "\\'doublons\\' => '.var_export(\$doublons,true).'";
		}

		if ($ajax = isset($_contexte['ajax'])) {
			$ajax = preg_replace(',=>(.*)$,ims', '=> ($v=(\\1))?$v:true', $_contexte['ajax']);
			unset($_contexte['ajax']);
		}

		$_contexte = join(",\n\t", $_contexte);
	} else {
		return false;
	} // j'aurais voulu toucher le fond ...

	$contexte = 'array(' . $_contexte . ')';

	if ($env) {
		$contexte = "array_merge('.var_export(\$Pile[0],1).',$contexte)";
	}

	// s'il y a une extension .php, ce n'est pas un squelette
	if ($fichier and preg_match('/^.+[.]php$/s', $fichier)) {
		$code = sandbox_composer_inclure_php($fichier, $p, $contexte);
	} else {
		$_options[] = "\"compil\"=>array($compil)";
		if ($ajax) {
			$_options[] = $ajax;
		}
		$code = " ' . argumenter_squelette($code) . '";
		$code = 'echo ' . sprintf(
			CODE_RECUPERER_FOND,
			$code,
			$contexte,
			implode(',', $_options),
			'_request("connect")'
		) . ';';
	}

	return "\n'<'.'" . '?php ' . $code . "\n?'." . "'>'";
}


/**
 * Gérer les statuts declarés pour cette table
 *
 * S'il existe des statuts sur cette table, déclarés dans la description
 * d'un objet éditorial, applique leurs contraintes
 *
 * @param Boucle $boucle
 *     Descrition de la boucle
 * @param bool $echapper
 *     true pour échapper le code créé
 * @param bool $ignore_previsu
 *     true pour ne tester que le cas publie et ignorer l'eventuel var_mode=preview de la page
 */
function instituer_boucle(&$boucle, $echapper = true, $ignore_previsu = false) {
	/*
	$show['statut'][] = array(
		'champ'=>'statut',  // champ de la table sur lequel porte le filtrage par le statut
		'publie'=>'publie', // valeur ou liste de valeurs, qui definissent l'objet comme publie.
		'previsu'=>'publie,prop', // valeur ou liste de valeurs qui sont visibles en previsu
		'post_date'=>'date', // un champ de date pour la prise en compte des post_dates, ou rien sinon
	  'exception'=>'statut', // liste des modificateurs qui annulent le filtrage par statut
							 // si plusieurs valeurs : array('statut','tout','lien')
	);

	Pour 'publier' ou 'previsu', si la chaine commence par un "!" on exclu au lieu de filtrer sur les valeurs donnees
	si la chaine est vide, on ne garde rien si elle est seulement "!" on n'exclu rien

	Si le statut repose sur une jointure, 'champ' est alors un tableau du format suivant :
	'champ'=>array(
		array(table1, cle1),
		...
		array(tablen, clen),
		champstatut
	 )

	champstatut est alors le champ statut sur la tablen
	dans les jointures, clen peut etre un tableau pour une jointure complexe : array('id_objet','id_article','objet','article')
*/
	$id_table = $boucle->id_table;
	$show = $boucle->show;
	if (isset($show['statut']) and $show['statut']) {
		foreach ($show['statut'] as $k => $s) {
			// Restreindre aux elements publies si pas de {statut} ou autre dans les criteres
			$filtrer = true;
			if (isset($s['exception'])) {
				foreach (is_array($s['exception']) ? $s['exception'] : [$s['exception']] as $m) {
					if (isset($boucle->modificateur[$m]) or isset($boucle->modificateur['criteres'][$m])) {
						$filtrer = false;
						break;
					}
				}
			}

			if ($filtrer) {
				if (is_array($s['champ'])) {
					$statut = preg_replace(',\W,', '', array_pop($s['champ'])); // securite
					$jointures = [];
					// indiquer la description de chaque table dans le tableau de jointures,
					// ce qui permet d'eviter certains GROUP BY inutiles.
					$trouver_table = charger_fonction('trouver_table', 'base');
					foreach ($s['champ'] as $j) {
						$id = reset($j);
						$def = $trouver_table($id);
						$jointures[] = ['', [$id, $def], end($j)];
					}
					$jointures[0][0] = $id_table;
					if (!array_search($id, $boucle->from)) {
						include_spip('public/jointures');
						fabrique_jointures($boucle, $jointures, true, $boucle->show, $id_table, '', $echapper);
					}
					// trouver l'alias de la table d'arrivee qui porte le statut
					$id = array_search($id, $boucle->from);
				} else {
					$id = $id_table;
					$statut = preg_replace(',\W,', '', $s['champ']); // securite
				}
				$mstatut = $id . '.' . $statut;

				$arg_ignore_previsu = ($ignore_previsu ? ',true' : '');
				include_spip('public/quete');
				if (
					isset($s['post_date']) and $s['post_date']
					and $GLOBALS['meta']['post_dates'] == 'non'
				) {
					$date = $id . '.' . preg_replace(',\W,', '', $s['post_date']); // securite
					array_unshift(
						$boucle->where,
						$echapper ?
							"\nquete_condition_postdates('$date'," . _q($boucle->sql_serveur) . "$arg_ignore_previsu)"
							:
							quete_condition_postdates($date, $boucle->sql_serveur, $ignore_previsu)
					);
				}
				array_unshift(
					$boucle->where,
					$echapper ?
						"\nquete_condition_statut('$mstatut',"
						. _q($s['previsu']) . ','
						. _q($s['publie']) . ','
						. _q($boucle->sql_serveur) . "$arg_ignore_previsu)"
						:
						quete_condition_statut($mstatut, $s['previsu'], $s['publie'], $boucle->sql_serveur, $ignore_previsu)
				);
			}
		}
	}
}

/**
 * Produit le corps PHP d'une boucle Spip.
 *
 * Ce corps remplit une variable $t0 retournée en valeur.
 * Ici on distingue boucles recursives et boucle à requête SQL
 * et on insère le code d'envoi au debusqueur du resultat de la fonction.
 *
 * @param string $id_boucle
 *    Identifiant de la boucle
 * @param array $boucles
 *    AST du squelette
 * @return string
 *    Code PHP compilé de la boucle
 */
function calculer_boucle($id_boucle, &$boucles) {

	$boucle = &$boucles[$id_boucle];
	instituer_boucle($boucle);
	$boucles[$id_boucle] = pipeline('post_boucle', $boucles[$id_boucle]);

	// en mode debug memoriser les premiers passages dans la boucle,
	// mais pas tous, sinon ca pete.
	if (_request('var_mode_affiche') != 'resultat') {
		$trace = '';
	} else {
		$_trace = $boucles[$id_boucle]->descr['nom'] . $id_boucle;
		$_trace = "\$GLOBALS['debug_objets']['resultat']['$_trace']";
		$trace = "
		if (empty($_trace)) { 
			$_trace = []; 
		}
		if (count($_trace) < 3) { 
			$_trace" . '[] = $t0; 
		}';
	}

	return ($boucles[$id_boucle]->type_requete == TYPE_RECURSIF)
		? calculer_boucle_rec($id_boucle, $boucles, $trace)
		: calculer_boucle_nonrec($id_boucle, $boucles, $trace);
}


/**
 * Compilation d'une boucle recursive.
 *
 * @internal
 *    Il suffit (ET IL FAUT) sauvegarder les valeurs des arguments passes par
 *    reference, car par definition un tel passage ne les sauvegarde pas
 *
 * @param string $id_boucle
 *    Identifiant de la boucle
 * @param array $boucles
 *    AST du squelette
 * @param string $trace
 *    Code PHP (en mode debug uniquement) servant à conserver une
 *    trace des premières valeurs de la boucle afin de pouvoir
 *    les afficher dans le débugueur ultérieurement
 * @return string
 *    Code PHP compilé de la boucle récursive
 **/
function calculer_boucle_rec($id_boucle, &$boucles, $trace) {
	$nom = $boucles[$id_boucle]->param[0];

	return
		// Numrows[$nom] peut ne pas être encore defini
		"\n\t\$save_numrows = (isset(\$Numrows['$nom']) ? \$Numrows['$nom'] : array());"
		. "\n\t\$t0 = " . $boucles[$id_boucle]->return . ';'
		. "\n\t\$Numrows['$nom'] = (\$save_numrows);"
		. $trace
		. "\n\treturn \$t0;";
}

/**
 * Compilation d'une boucle non recursive.
 *
 * La constante donne le cadre systématique du code:
 *
 * - %s1: initialisation des arguments de calculer_select
 * - %s2: appel de calculer_select en donnant un contexte pour les cas d'erreur
 * - %s3: initialisation du sous-tableau Numrows[id_boucle]
 * - %s4: sauvegarde de la langue et calcul des invariants de boucle sur elle
 * - %s5: boucle while sql_fetch ou str_repeat si corps monotone
 * - %s6: restauration de la langue
 * - %s7: liberation de la ressource, en tenant compte du serveur SQL
 * - %s8: code de trace eventuel avant le retour
 **/
define('CODE_CORPS_BOUCLE', '%s
	if (defined("_BOUCLE_PROFILER")) $timer = time()+(float)microtime();
	$t0 = "";
	// REQUETE
	$iter = IterFactory::create(
		"%s",
		%s,
		array(%s)
	);
	if (!$iter->err()) {
	%s%s$SP++;
	// RESULTATS
	%s
	%s$iter->free();
	}%s
	if (defined("_BOUCLE_PROFILER")
	AND 1000*($timer = (time()+(float)microtime())-$timer) > _BOUCLE_PROFILER)
		spip_log(intval(1000*$timer)."ms %s","profiler"._LOG_AVERTISSEMENT);
	return $t0;');

/**
 * Compilation d'une boucle (non recursive).
 *
 * @param string $id_boucle
 *    Identifiant de la boucle
 * @param array $boucles
 *    AST du squelette
 * @param string $trace
 *    Code PHP (en mode debug uniquement) servant à conserver une
 *    trace des premières valeurs de la boucle afin de pouvoir
 *    les afficher dans le débugueur ultérieurement
 * @return string
 *    Code PHP compilé de la boucle récursive
 **/
function calculer_boucle_nonrec($id_boucle, &$boucles, $trace) {

	$boucle = &$boucles[$id_boucle];
	$return = $boucle->return;
	$type_boucle = $boucle->type_requete;
	$primary = $boucle->primary;
	$constant = preg_match(CODE_MONOTONE, str_replace("\\'", '', $return));
	$flag_cpt = $boucle->mode_partie || $boucle->cptrows;
	$corps = '';

	// faudrait expanser le foreach a la compil, car y en a souvent qu'un
	// et puis faire un [] plutot qu'un "','."
	if ($boucle->doublons) {
		$corps .= "\n\t\t\tforeach(" . $boucle->doublons . ' as $k) $doublons[$k] .= "," . ' .
			index_pile($id_boucle, $primary, $boucles)
			. "; // doublons\n";
	}

	// La boucle doit-elle selectionner la langue ?
	// - par defaut, les boucles suivantes le font
	//    (sauf si forcer_lang==true ou si le titre contient <multi>).
	// - a moins d'une demande explicite via {!lang_select}
	if (
		!$constant && $boucle->lang_select != 'non' &&
		(($boucle->lang_select == 'oui') ||
			in_array($type_boucle, [
				'articles',
				'rubriques',
				'hierarchie',
				'breves'
			]))
	) {
		// Memoriser la langue avant la boucle et la restituer apres
		// afin que le corps de boucle affecte la globale directement
		$init_lang = "lang_select(\$GLOBALS['spip_lang']);\n\t";
		$fin_lang = "lang_select();\n\t";
		$fin_lang_select_public = "\n\t\tlang_select();";

		$corps .=
			"\n\t\tlang_select_public("
			. index_pile($id_boucle, 'lang', $boucles)
			. ", '" . $boucle->lang_select . "'"
			. (in_array($type_boucle, [
				'articles',
				'rubriques',
				'hierarchie',
				'breves'
			]) ? ', ' . index_pile($id_boucle, 'titre', $boucles) : '')
			. ');';
	} else {
		$init_lang = '';
		$fin_lang = '';
		$fin_lang_select_public = '';
		// sortir les appels au traducteur (invariants de boucle)
		if (
			strpos($return, '?php') === false
			and preg_match_all("/\W(_T[(]'[^']*'[)])/", $return, $r)
		) {
			$i = 1;
			foreach ($r[1] as $t) {
				$init_lang .= "\n\t\$l$i = $t;";
				$return = str_replace($t, "\$l$i", $return);
				$i++;
			}
		}
	}

	// gestion optimale des separateurs et des boucles constantes
	if (count($boucle->separateur)) {
		$code_sep = ("'" . str_replace("'", "\'", join('', $boucle->separateur)) . "'");
	}

	$corps .=
		((!$boucle->separateur) ?
			(($constant && !$corps && !$flag_cpt) ? $return :
				(($return === "''") ? '' :
					("\n\t\t" . '$t0 .= ' . $return . ';'))) :
			("\n\t\t\$t1 " .
				((strpos($return, '$t1.') === 0) ?
					('.=' . substr($return, 4)) :
					('= ' . $return)) .
				";\n\t\t" .
				'$t0 .= ((strlen($t1) && strlen($t0)) ? ' . $code_sep . " : '') . \$t1;"));

	// Calculer les invalideurs si c'est une boucle non constante et si on
	// souhaite invalider ces elements
	if (!$constant and $primary) {
		include_spip('inc/invalideur');
		$corps = calcul_invalideurs($corps, $primary, $boucles, $id_boucle);
	}

	// gerer le compteur de boucle
	// avec ou sans son utilisation par les criteres {1/3} {1,4} {n-2,1}...

	if ($boucle->partie or $boucle->cptrows) {
		$corps = "\n\t\t\$Numrows['$id_boucle']['compteur_boucle']++;"
			. $boucle->partie
			. $corps;
	}

	// depiler la lang de la boucle si besoin
	$corps .= $fin_lang_select_public;

	// si le corps est une constante, ne pas appeler le serveur N fois!

	if (preg_match(CODE_MONOTONE, str_replace("\\'", '', $corps), $r)) {
		if (!isset($r[2]) or (!$r[2])) {
			if (!$boucle->numrows) {
				return "\n\t\$t0 = '';";
			} else {
				$corps = '';
			}
		} else {
			$boucle->numrows = true;
			$corps = "\n\t\$t0 = str_repeat($corps, \$Numrows['$id_boucle']['total']);";
		}
	} else {
		$corps = "while (\$Pile[\$SP]=\$iter->fetch()) {\n$corps\n	}";
	}

	$count = '';
	if (!$boucle->select) {
		if (!$boucle->numrows or $boucle->limit or $boucle->mode_partie or $boucle->group) {
			$count = '1';
		} else {
			$count = 'count(*)';
		}
		$boucles[$id_boucle]->select[] = $count;
	}

	if ($flag_cpt) {
		$nums = "\n\t// COMPTEUR\n\t"
			. "\$Numrows['$id_boucle']['compteur_boucle'] = 0;\n\t";
	} else {
		$nums = '';
	}

	if ($boucle->numrows or $boucle->mode_partie) {
		$nums .= "\$Numrows['$id_boucle']['command'] = \$command;\n\t"
			. "\$Numrows['$id_boucle']['total'] = @intval(\$iter->count());"
			. $boucle->mode_partie
			. "\n\t";
	}

	// Ne calculer la requete que maintenant
	// car ce qui precede appelle index_pile qui influe dessus

	$init = (($init = $boucles[$id_boucle]->doublons)
			? ("\n\t$init = array();") : '')
		. calculer_requete_sql($boucles[$id_boucle]);

	$contexte = memoriser_contexte_compil($boucle);

	$a = sprintf(
		CODE_CORPS_BOUCLE,
		$init,
		$boucle->iterateur,
		'$command',
		$contexte,
		$nums,
		$init_lang,
		$corps,
		$fin_lang,
		$trace,
		'BOUCLE' . $id_boucle . ' @ ' . ($boucle->descr['sourcefile'])
	);

#	var_dump($a);exit;
	return $a;
}


/**
 * Calcule le code PHP d'une boucle contenant les informations qui produiront une requête SQL
 *
 * Le code produit est un tableau associatif $command contenant les informations
 * pour que la boucle produise ensuite sa requête, tel que `$command['from'] = 'spip_articles';`
 *
 * @param Boucle $boucle
 *     AST de la boucle
 * @return string
 *     Code PHP compilé définissant les informations de requête
 **/
function calculer_requete_sql($boucle) {
	$init = [];
	$init[] = calculer_dec('table', "'" . $boucle->id_table . "'");
	$init[] = calculer_dec('id', "'" . $boucle->id_boucle . "'");
	# En absence de champ c'est un decompte :
	$init[] = calculer_dec('from', calculer_from($boucle));
	$init[] = calculer_dec('type', calculer_from_type($boucle));
	$init[] = calculer_dec(
		'groupby',
		'array(' . (($g = join("\",\n\t\t\"", $boucle->group)) ? '"' . $g . '"' : '') . ')'
	);
	$init[] = calculer_dec('select', 'array("' . join("\",\n\t\t\"", $boucle->select) . '")');
	$init[] = calculer_dec('orderby', 'array(' . calculer_order($boucle) . ')');
	$init[] = calculer_dec('where', calculer_dump_array($boucle->where));
	$init[] = calculer_dec('join', calculer_dump_join($boucle->join));
	$init[] = calculer_dec(
		'limit',
		(strpos($boucle->limit, 'intval') === false ?
			"'" . $boucle->limit . "'"
			:
		$boucle->limit)
	);
	$init[] = calculer_dec('having', calculer_dump_array($boucle->having));
	$s = $d = '';
	// l'index 0 de $i indique si l'affectation est statique (contenu)
	// ou recalculée à chaque passage (vide)
	foreach ($init as $i) {
		if (reset($i)) {
			$s .= "\n\t\t" . end($i);
		} # statique
		else {
			$d .= "\n\t" . end($i);
		} # dynamique
	}

	return ($boucle->hierarchie ? "\n\t$boucle->hierarchie" : '')
	. $boucle->in
	. $boucle->hash
	. "\n\t" . 'if (!isset($command[\'table\'])) {'
	. $s
	. "\n\t}"
	. $d;
}

/**
 * Retourne une chaîne des informations du contexte de compilation
 *
 * Retourne la source, le nom, l'identifiant de boucle, la ligne, la langue
 * de l'élément dans une chaîne.
 *
 * @see reconstruire_contexte_compil()
 *
 * @param Object $p
 *     Objet de l'AST dont on mémorise le contexte
 * @return string
 *     Informations du contexte séparés par des virgules,
 *     qui peut être utilisé pour la production d'un tableau array()
 **/
function memoriser_contexte_compil($p) {
	return join(',', [
		_q(isset($p->descr['sourcefile']) ? $p->descr['sourcefile'] : ''),
		_q(isset($p->descr['nom']) ? $p->descr['nom'] : ''),
		_q(isset($p->id_boucle) ? $p->id_boucle : null),
		intval($p->ligne),
		'$GLOBALS[\'spip_lang\']'
	]);
}

/**
 * Reconstruit un contexte de compilation
 *
 * Pour un tableau d'information de contexte donné,
 * retourne un objet Contexte (objet générique de l'AST)
 * avec ces informations
 *
 * @see memoriser_contexte_compil()
 *
 * @param array $context_compil
 *     Tableau des informations du contexte
 * @return Contexte
 *     Objet Contexte
 **/
function reconstruire_contexte_compil($context_compil) {
	if (!is_array($context_compil)) {
		return $context_compil;
	}
	$p = new Contexte();
	$p->descr = [
		'sourcefile' => $context_compil[0],
		'nom' => $context_compil[1]
	];
	$p->id_boucle = $context_compil[2];
	$p->ligne = $context_compil[3];
	$p->lang = $context_compil[4];

	return $p;
}

/**
 * Calcule le code d'affectation d'une valeur à une commande de boucle
 *
 * Décrit le code qui complète le tableau $command qui servira entre autres
 * à l'itérateur. Pour un nom de commande donnée et un code PHP décrivant
 * ou récupérant une valeur, on retourne le code PHP qui fait l'affectation.
 *
 * L'index 0 du tableau retourné indique, lorsqu'il n'est pas vide, que l'affectation
 * de la variable pourra être statique (c'est à dire qu'il ne dépend
 * pas d'une quelconque variable PHP), et donc attribué une fois pour toutes
 * quelque soit le nombre d'appels de la boucle.
 *
 * @param string $nom
 *    Nom de la commande
 * @param string $val
 *    Code PHP décrivant la valeur à affecter
 * @return array
 *
 *    - index 0 : Code pour une affectation statique. Si non rempli, la propriété devra
 *                être ré-affectée à chaque appel de la boucle.
 *    - index 1 : Code de l'affectation
 **/
function calculer_dec($nom, $val) {
	$static = 'if (!isset($command[\'' . $nom . '\'])) ';
	// si une variable apparait dans le calcul de la clause
	// il faut la re-evaluer a chaque passage
	if (
		strpos($val, '$') !== false
		/*
		OR strpos($val, 'sql_') !== false
		OR (
			$test = str_replace(array("array(",'\"',"\'"),array("","",""),$val) // supprimer les array( et les echappements de guillemets
			AND strpos($test,"(")!==FALSE // si pas de parenthese ouvrante, pas de fonction, on peut sortir
			AND $test = preg_replace(",'[^']*',UimsS","",$test) // supprimer les chaines qui peuvent contenir des fonctions SQL qui ne genent pas
			AND preg_match(",\w+\s*\(,UimsS",$test,$regs) // tester la presence de fonctions restantes
		)*/
	) {
		$static = '';
	}

	return [$static, '$command[\'' . $nom . '\'] = ' . $val . ';'];
}

/**
 * Calcule l'expression PHP décrivant un tableau complexe (ou une chaîne)
 *
 * Lorsqu'un tableau est transmis, reconstruit de quoi créer le tableau
 * en code PHP (une sorte de var_export) en appelant pour chaque valeur
 * cette fonction de manière récursive.
 *
 * Si le premier index (0) du tableau est "'?'", retourne un code
 * de test entre les 3 autres valeurs (v1 ? v2 : v3). Les valeurs
 * pouvant être des tableaux aussi.
 *
 * @param mixed $a
 *     Les données dont on veut construire un équivalent de var_export
 * @return string
 *     Expression PHP décrivant un texte ou un tableau
 **/
function calculer_dump_array($a) {
	if (!is_array($a)) {
		return $a;
	}
	$res = '';
	if ($a and $a[0] == "'?'") {
		return ('(' . calculer_dump_array($a[1]) .
			' ? ' . calculer_dump_array($a[2]) .
			' : ' . calculer_dump_array($a[3]) .
			')');
	} else {
		foreach ($a as $k => $v) {
			$showk = (is_numeric($k) ? '' : sql_quote($k) . ' => ');
			$res .= ', ' . $showk . calculer_dump_array($v);
		}

		return "\n\t\t\tarray(" . substr($res, 2) . ')';
	}
}

// https://code.spip.net/@calculer_dump_join
function calculer_dump_join($a) {
	$res = '';
	foreach ($a as $k => $v) {
		$res .= ", '$k' => array(" . implode(',', $v) . ')';
	}

	return 'array(' . substr($res, 2) . ')';
}

/**
 * Calcule l'expression PHP décrivant les informations FROM d'une boucle
 *
 * @param Boucle $boucle
 *     Description de la boucle
 * @return string
 *     Code PHP construisant un tableau des alias et noms des tables du FROM
 **/
function calculer_from(&$boucle) {
	$res = '';
	foreach ($boucle->from as $k => $v) {
		$res .= ",'$k' => '$v'";
	}

	return 'array(' . substr($res, 1) . ')';
}

/**
 * Calcule l'expression PHP décrivant des informations de type de jointure
 * pour un alias de table connu dans le FROM
 *
 * @param Boucle $boucle
 *     Description de la boucle
 * @return string
 *     Code PHP construisant un tableau des alias et type de jointure du FROM
 **/
function calculer_from_type(&$boucle) {
	$res = '';
	foreach ($boucle->from_type as $k => $v) {
		$res .= ",'$k' => '$v'";
	}

	return 'array(' . substr($res, 1) . ')';
}

// https://code.spip.net/@calculer_order
function calculer_order(&$boucle) {
	if (
		!$order = $boucle->order
		and !$order = $boucle->default_order
	) {
		$order = [];
	}

	/*if (isset($boucle->modificateur['collate'])){
		$col = "." . $boucle->modificateur['collate'];
		foreach($order as $k=>$o)
			if (strpos($order[$k],'COLLATE')===false)
				$order[$k].= $col;
	}*/

	return join(', ', $order);
}

// Production du code PHP a partir de la sequence livree par le phraseur
// $boucles est passe par reference pour affectation par index_pile.
// Retourne une expression PHP,
// (qui sera argument d'un Return ou la partie droite d'une affectation).

// https://code.spip.net/@calculer_liste
function calculer_liste($tableau, $descr, &$boucles, $id_boucle = '') {
	if (!$tableau) {
		return "''";
	}
	if (is_string($descr)) {
		if (isset($boucles[$descr])) {
			$idb = $descr;
			$descr = [];
			if (isset($boucles[$idb]->descr['id_mere_contexte'])) {
				$descr['id_mere'] = $boucles[$idb]->descr['id_mere_contexte'];
			}
			if (isset($boucles[$idb]->descr['sourcefile'])) {
				$descr['sourcefile'] = $boucles[$idb]->descr['sourcefile'];
			}
		}
		else {
			$descr = [];
		}
	}
	if (!isset($descr['niv'])) {
		$descr['niv'] = 0;
	}
	$codes = compile_cas($tableau, $descr, $boucles, $id_boucle);
	if ($codes === false) {
		return false;
	}
	$n = count($codes);
	if (!$n) {
		return "''";
	}
	$tab = str_repeat("\t", $descr['niv']);
	if (_request('var_mode_affiche') != 'validation') {
		if ($n == 1) {
			return $codes[0];
		} else {
			$res = '';
			foreach ($codes as $code) {
				if (
					!preg_match("/^'[^']*'$/", $code)
					or substr($res, -1, 1) !== "'"
				) {
					$res .= " .\n$tab$code";
				} else {
					$res = substr($res, 0, -1) . substr($code, 1);
				}
			}

			return '(' . substr($res, 2 + $descr['niv']) . ')';
		}
	} else {
		$nom = $descr['nom'] . $id_boucle . ($descr['niv'] ? $descr['niv'] : '');

		return "join('', array_map('array_shift', \$GLOBALS['debug_objets']['sequence']['$nom'] = array(" . join(
			" ,\n$tab",
			$codes
		) . ')))';
	}
}


define('_REGEXP_COND_VIDE_NONVIDE', "/^[(](.*)[?]\s*''\s*:\s*('[^']+')\s*[)]$/");
define('_REGEXP_COND_NONVIDE_VIDE', "/^[(](.*)[?]\s*('[^']+')\s*:\s*''\s*[)]$/");
define('_REGEXP_CONCAT_NON_VIDE', "/^(.*)[.]\s*'[^']+'\s*$/");

// https://code.spip.net/@compile_cas
function compile_cas($tableau, $descr, &$boucles, $id_boucle) {

	$codes = [];
	// cas de la boucle recursive
	if (is_array($id_boucle)) {
		$id_boucle = $id_boucle[0];
	}
	$type = !$id_boucle ? '' : $boucles[$id_boucle]->type_requete;
	$tab = str_repeat("\t", ++$descr['niv']);
	$mode = _request('var_mode_affiche');
	$err_e_c = '';
	// chaque commentaire introduit dans le code doit commencer
	// par un caractere distinguant le cas, pour exploitation par debug.
	foreach ($tableau as $p) {
		switch ($p->type) {
			// texte seul
			case 'texte':
				$code = sandbox_composer_texte($p->texte, $p);
				$commentaire = strlen($p->texte) . ' signes';
				$avant = '';
				$apres = '';
				$altern = "''";
				break;

			case 'polyglotte':
				$code = '';
				foreach ($p->traductions as $k => $v) {
					$code .= ",'" .
						str_replace(['\\', "'"], ['\\\\', "\\'"], $k) .
						"' => '" .
						str_replace(['\\', "'"], ['\\\\', "\\'"], $v) .
						"'";
				}
				$code = 'choisir_traduction(array(' .
					substr($code, 1) .
					'))';
				$commentaire = '&';
				$avant = '';
				$apres = '';
				$altern = "''";
				break;

			// inclure
			case 'include':
				$p->descr = $descr;
				$code = calculer_inclure($p, $boucles, $id_boucle);
				if ($code === false) {
					$err_e_c = true;
					$code = "''";
				} else {
					$commentaire = '<INCLURE ' . addslashes(str_replace("\n", ' ', $code)) . '>';
					$avant = '';
					$apres = '';
					$altern = "''";
				}
				break;

			// boucle
			case TYPE_RECURSIF:
				$nom = $p->id_boucle;
				$newdescr = $descr;
				$newdescr['id_mere'] = $nom;
				$newdescr['niv']++;
				$preaff = calculer_liste($p->preaff, $newdescr, $boucles, $id_boucle);
				$avant = calculer_liste($p->avant, $newdescr, $boucles, $id_boucle);
				$apres = calculer_liste($p->apres, $newdescr, $boucles, $id_boucle);
				$postaff = calculer_liste($p->postaff, $newdescr, $boucles, $id_boucle);
				$newdescr['niv']--;
				$altern = calculer_liste($p->altern, $newdescr, $boucles, $id_boucle);
				if (
					$preaff === false
					or $avant === false
					or $apres === false
					or $altern === false
					or $postaff === false
				) {
					$err_e_c = true;
					$code = "''";
				} else {
					$code = 'BOUCLE' .
						str_replace('-', '_', $nom) . $descr['nom'] .
						'($Cache, $Pile, $doublons, $Numrows, $SP)';
					$commentaire = "?$nom";
					if (
						!$boucles[$nom]->milieu
						and $boucles[$nom]->type_requete <> TYPE_RECURSIF
					) {
						if ($preaff != "''") {
							$code .= "\n. $preaff";
						}
						if ($altern != "''") {
							$code .= "\n. $altern";
						}
						if ($postaff != "''") {
							$code .= "\n. $postaff";
						}
						if ($avant <> "''" or $apres <> "''") {
							spip_log("boucle $nom toujours vide, code superflu dans $descr[sourcefile]");
						}
						$avant = $apres = $altern = "''";
					} else {
						if ($preaff != "''") {
							$avant = compile_concatene_parties_codes($preaff, $avant);
							$altern = compile_concatene_parties_codes($preaff, $altern);
						}
						if ($postaff != "''") {
							$apres = compile_concatene_parties_codes($apres, $postaff);
							$altern = compile_concatene_parties_codes($altern, $postaff);
						}
						if ($altern != "''") {
							$altern = "($altern)";
						}
					}
				}
				break;

			case 'idiome':
				$l = [];
				$code = '';
				foreach ($p->arg as $k => $v) {
					$_v = calculer_liste($v, $descr, $boucles, $id_boucle);
					if ($k) {
						$l[] = _q($k) . ' => ' . $_v;
					} else {
						$code = $_v;
					}
				}
				// Si le module n'est pas fourni, l'expliciter sauf si calculé
				if ($p->module) {
					$m = $p->module . ':' . $p->nom_champ;
				} elseif ($p->nom_champ) {
					$m = MODULES_IDIOMES . ':' . $p->nom_champ;
				} else {
					$m = '';
				}

				$code = (!$code ? "'$m'" :
						($m ? "'$m' . $code" :
							("(strpos(\$x=$code, ':') ? \$x : ('" . MODULES_IDIOMES . ":' . \$x))")))
					. (!$l ? '' : (', array(' . implode(",\n", $l) . ')'));
				$code = "_T($code)";
				if ($p->param) {
					$p->id_boucle = $id_boucle;
					$p->boucles = &$boucles;
					$code = compose_filtres($p, $code);
				}
				$commentaire = ':';
				$avant = '';
				$apres = '';
				$altern = "''";
				break;

			case 'champ':
				// cette structure pourrait etre completee des le phrase' (a faire)
				$p->id_boucle = $id_boucle;
				$p->boucles = &$boucles;
				$p->descr = $descr;
				#$p->interdire_scripts = true;
				$p->type_requete = $type;

				$code = calculer_champ($p);
				$commentaire = '#' . $p->nom_champ . $p->etoile;
				$avant = calculer_liste(
					$p->avant,
					$descr,
					$boucles,
					$id_boucle
				);
				$apres = calculer_liste(
					$p->apres,
					$descr,
					$boucles,
					$id_boucle
				);
				$altern = "''";
				// Si la valeur est destinee a une comparaison a ''
				// forcer la conversion en une chaine par strval
				// si ca peut etre autre chose qu'une chaine
				if (
					($avant != "''" or $apres != "''")
					and $code[0] != "'"
#			AND (strpos($code,'interdire_scripts') !== 0)
					and !preg_match(_REGEXP_COND_VIDE_NONVIDE, $code)
					and !preg_match(_REGEXP_COND_NONVIDE_VIDE, $code)
					and !preg_match(_REGEXP_CONCAT_NON_VIDE, $code)
				) {
					$code = "strval($code)";
				}
				break;

			default:
				// Erreur de construction de l'arbre de syntaxe abstraite
				$code = "''";
				$p->descr = $descr;
				$err_e_c = _T('zbug_erreur_compilation');
				erreur_squelette($err_e_c, $p);
		} // switch

		if ($code != "''") {
			$code = compile_retour($code, $avant, $apres, $altern, $tab, $descr['niv']);
			$codes[] = (($mode == 'validation') ?
				"array($code, '$commentaire', " . $p->ligne . ')'
				: (($mode == 'code') ?
					"\n// $commentaire\n$code" :
					$code));
		}
	} // foreach

	return $err_e_c ? false : $codes;
}

/**
 * Concatene 2 parties de code, en simplifiant si l'une des 2 est vides
 * @param $partie1
 * @param $partie2
 * @return string
 */
function compile_concatene_parties_codes($partie1, $partie2) {
	if ($partie1 === "''") {
		return $partie2;
	}
	if ($partie2 === "''") {
		return $partie1;
	}
	return "$partie1\n. $partie2";
}


/**
 * production d'une expression conditionnelle ((v=EXP) ? (p . v .s) : a)
 * mais si EXP est de la forme (t ? 'C' : '') on produit (t ? (p . C . s) : a)
 * de meme si EXP est de la forme (t ? '' : 'C')
 * https://code.spip.net/@compile_retour
 *
 * @param string $code
 *   le code principal, dont le resultat conditionnera le reste
 * @param string $avant
 *   la partie conditionnelle avant, qui est calculee apres le code, mais s'affiche avant si le code produit un resultat
 * @param string $apres
 *   la partie conditionnelle apres, qui est calculee apres le code, et s'affiche apres si le code produit un resultat
 * @param string $altern
 *   la partie alternative apres, qui est calculee apres le code, et s'affiche apres, si le code ne produit pas de resultat
 * @param string $tab
 *   tabulation
 * @param int $n
 *   compteur
 * @return mixed|string
 */
function compile_retour($code, $avant, $apres, $altern, $tab, $n) {
	if ($avant === "''") {
		$avant = '';
	}
	if ($apres === "''") {
		$apres = '';
	}
	if ($avant or $apres or ($altern !== "''")) {
		if (preg_match(_REGEXP_CONCAT_NON_VIDE, $code)) {
			$t = $code;
			$cond = '';
		} elseif (preg_match(_REGEXP_COND_VIDE_NONVIDE, $code, $r)) {
			$t = $r[2];
			$cond = '!' . $r[1];
		} else {
			if (preg_match(_REGEXP_COND_NONVIDE_VIDE, $code, $r)) {
				$t = $r[2];
				$cond = $r[1];
			} else {
				$t = '$t' . $n;
				$cond = "($t = $code)!==''";
			}
		}

		$res = (!$avant ? '' : "$avant . ") .
			$t .
			(!$apres ? '' : " . $apres");

		if ($res !== $t) {
			$res = "($res)";
		}

		$code = (!$cond ? $res : "($cond ?\n\t$tab$res :\n\t$tab$altern)");
	}

	return $code;
}


function compile_inclure_doublons($lexemes) {
	foreach ($lexemes as $v) {
		if ($v->type === 'include' and $v->param) {
			foreach ($v->param as $r) {
				if (trim($r[0]) === 'doublons') {
					return true;
				}
			}
		}
	}

	return false;
}

// Prend en argument le texte d'un squelette, le nom de son fichier d'origine,
// sa grammaire et un nom. Retourne False en cas d'erreur,
// sinon retourne un tableau de fonctions PHP compilees a evaluer,
// notamment une fonction portant ce nom et calculant une page.
// Pour appeler la fonction produite, lui fournir 2 tableaux de 1 e'le'ment:
// - 1er: element 'cache' => nom (du fichier ou` mettre la page)
// - 2e: element 0 contenant un environnement ('id_article => $id_article, etc)
// Elle retournera alors un tableau de 5 e'le'ments:
// - 'texte' => page HTML, application du squelette a` l'environnement;
// - 'squelette' => le nom du squelette
// - 'process_ins' => 'html' ou 'php' selon la pre'sence de PHP dynamique
// - 'invalideurs' =>  de'pendances de cette page, pour invalider son cache.
// - 'entetes' => tableau des entetes http
// En cas d'erreur, elle retournera un tableau des 2 premiers elements seulement

// https://code.spip.net/@public_compiler_dist
function public_compiler_dist($squelette, $nom, $gram, $sourcefile, $connect = '') {
	// Pre-traitement : reperer le charset du squelette, et le convertir
	// Bonus : supprime le BOM
	include_spip('inc/charsets');
	$squelette = transcoder_page($squelette);

	// rendre inertes les echappements de #[](){}<>
	$i = 0;
	while (false !== strpos($squelette, $inerte = '-INERTE' . $i)) {
		$i++;
	}
	$squelette = preg_replace_callback(
		',\\\\([#[()\]{}<>]),',
		function ($a) use ($inerte) {
			return "$inerte-" . ord($a[1]) . '-';
		},
		$squelette,
		-1,
		$esc
	);

	$descr = [
		'nom' => $nom,
		'gram' => $gram,
		'sourcefile' => $sourcefile,
		'squelette' => $squelette
	];

	// Phraser le squelette, selon sa grammaire

	$boucles = [];
	$f = charger_fonction('phraser_' . $gram, 'public');

	$squelette = $f($squelette, '', $boucles, $descr);

	$boucles = compiler_squelette($squelette, $boucles, $nom, $descr, $sourcefile, $connect);

	// restituer les echappements
	if ($esc) {
		foreach ($boucles as $i => $boucle) {
			$boucles[$i]->return = preg_replace_callback(
				",$inerte-(\d+)-,",
				function ($a) {
					return chr($a[1]);
				},
				$boucle->return
			);
			$boucles[$i]->descr['squelette'] = preg_replace_callback(
				",$inerte-(\d+)-,",
				function ($a) {
					return '\\\\' . chr($a[1]);
				},
				$boucle->descr['squelette']
			);
		}
	}

	$debug = ($boucles and defined('_VAR_MODE') and _VAR_MODE == 'debug');
	if ($debug) {
		include_spip('public/decompiler');
		foreach ($boucles as $id => $boucle) {
			if ($id) {
				$decomp = "\n/* BOUCLE " .
					$boucle->type_requete .
					' ' .
					str_replace('*/', '* /', public_decompiler($boucle, $gram, 0, 'criteres')) .
					($boucle->debug ? "\n *\n * " . implode("\n * ", $boucle->debug) . "\n" : '') .
					" */\n";
			} else {
				$decomp = ("\n/*\n" .
					str_replace('*/', '* /', public_decompiler($squelette, $gram))
					. "\n*/");
			}
			$boucles[$id]->return = $decomp . $boucle->return;
			$GLOBALS['debug_objets']['code'][$nom . $id] = $boucle->return;
		}
	}

	return $boucles;
}

// Point d'entree pour arbre de syntaxe abstraite fourni en premier argument
// Autres specifications comme ci-dessus

function compiler_squelette($squelette, $boucles, $nom, $descr, $sourcefile, $connect = '') {
	static $trouver_table;
	spip_timer('calcul_skel');

	if (defined('_VAR_MODE') and _VAR_MODE == 'debug') {
		$GLOBALS['debug_objets']['squelette'][$nom] = $descr['squelette'];
		$GLOBALS['debug_objets']['sourcefile'][$nom] = $sourcefile;

		if (!isset($GLOBALS['debug_objets']['principal'])) {
			$GLOBALS['debug_objets']['principal'] = $nom;
		}
	}
	foreach ($boucles as $id => $boucle) {
		$GLOBALS['debug_objets']['boucle'][$nom . $id] = $boucle;
	}
	$descr['documents'] = compile_inclure_doublons($squelette);

	// Demander la description des tables une fois pour toutes
	if (!$trouver_table) {
		$trouver_table = charger_fonction('trouver_table', 'base');
	}

	// reperer si les doublons sont demandes
	// pour un inclure ou une boucle document
	// c'est utile a la fonction champs_traitements
	foreach ($boucles as $id => $boucle) {
		if (!($type = $boucle->type_requete)) {
			continue;
		}
		if (
			!$descr['documents'] and (
				(($type == 'documents') and $boucle->doublons) or
				compile_inclure_doublons($boucle->avant) or
				compile_inclure_doublons($boucle->apres) or
				compile_inclure_doublons($boucle->milieu) or
				compile_inclure_doublons($boucle->altern))
		) {
			$descr['documents'] = true;
		}
		if ($type != TYPE_RECURSIF) {
			if (!$boucles[$id]->sql_serveur and $connect) {
				$boucles[$id]->sql_serveur = $connect;
			}

			// chercher dans les iterateurs du repertoire iterateur/
			if (
				$g = charger_fonction(
					preg_replace('/\W/', '_', $boucle->type_requete),
					'iterateur',
					true
				)
			) {
				$boucles[$id] = $g($boucle);

				// sinon, en cas de requeteur d'un type predefini,
				// utiliser les informations donnees par le requeteur
				// cas "php:xx" et "data:xx".
			} else {
				if ($boucle->sql_serveur and $requeteur = charger_fonction($boucle->sql_serveur, 'requeteur', true)) {
					$requeteur($boucles, $boucle, $id);

					// utiliser la description des champs transmis
				} else {
					$show = $trouver_table($type, $boucles[$id]->sql_serveur);
					// si la table n'existe pas avec le connecteur par defaut,
					// c'est peut etre une table qui necessite son connecteur dedie fourni
					// permet une ecriture allegee (GEO) -> (geo:GEO)
					if (
						!$show
						and $show = $trouver_table($type, strtolower($type))
					) {
						$boucles[$id]->sql_serveur = strtolower($type);
					}
					if ($show) {
						$boucles[$id]->show = $show;
						// recopie les infos les plus importantes
						$boucles[$id]->primary = isset($show['key']['PRIMARY KEY']) ? $show['key']['PRIMARY KEY'] : '';
						$boucles[$id]->id_table = $x = preg_replace(',^spip_,', '', $show['id_table']);
						$boucles[$id]->from[$x] = $nom_table = $show['table'];
						$boucles[$id]->iterateur = 'SQL';

						if (empty($boucles[$id]->descr)) {
							$boucles[$id]->descr = &$descr;
						}
						if (
							(!$boucles[$id]->jointures)
							and is_array($show['tables_jointures'])
							and count($x = $show['tables_jointures'])
						) {
							$boucles[$id]->jointures = $x;
						}
						if ($boucles[$id]->jointures_explicites) {
							$jointures = preg_split('/\s+/', $boucles[$id]->jointures_explicites);
							while ($j = array_pop($jointures)) {
								array_unshift($boucles[$id]->jointures, $j);
							}
						}
					} else {
						// Pas une erreur si la table est optionnelle
						if ($boucles[$id]->table_optionnelle) {
							$boucles[$id]->type_requete = '';
						} else {
							$boucles[$id]->type_requete = false;
							$boucle = $boucles[$id];
							$x = (!$boucle->sql_serveur ? '' :
									($boucle->sql_serveur . ':')) .
								$type;
							$msg = [
								'zbug_table_inconnue',
								['table' => $x]
							];
							erreur_squelette($msg, $boucle);
						}
					}
				}
			}
		}
	}

	// Commencer par reperer les boucles appelees explicitement
	// car elles indexent les arguments de maniere derogatoire
	foreach ($boucles as $id => $boucle) {
		if ($boucle->type_requete == TYPE_RECURSIF and $boucle->param) {
			$boucles[$id]->descr = &$descr;
			$rec = &$boucles[$boucle->param[0]];
			if (!$rec) {
				$msg = [
					'zbug_boucle_recursive_undef',
					['nom' => $boucle->param[0]]
				];
				erreur_squelette($msg, $boucle);
				$boucles[$id]->type_requete = false;
			} else {
				$rec->externe = $id;
				$descr['id_mere'] = $id;
				$boucles[$id]->return =
					calculer_liste(
						[$rec],
						$descr,
						$boucles,
						$boucle->param
					);
			}
		}
	}
	foreach ($boucles as $id => $boucle) {
		$id = strval($id); // attention au type dans index_pile
		$type = $boucle->type_requete;
		if ($type and $type != TYPE_RECURSIF) {
			$res = '';
			if ($boucle->param) {
				// retourne un tableau en cas d'erreur
				$res = calculer_criteres($id, $boucles);
			}
			$descr['id_mere'] = $id;
			$boucles[$id]->return =
				calculer_liste(
					$boucle->milieu,
					$descr,
					$boucles,
					$id
				);
			// Si les criteres se sont mal compiles
			// ne pas tenter d'assembler le code final
			// (mais compiler le corps pour detection d'erreurs)
			if (is_array($res)) {
				$boucles[$id]->type_requete = false;
			}
		}
	}

	// idem pour la racine
	$descr['id_mere'] = '';
	$corps = calculer_liste($squelette, $descr, $boucles);


	// Calcul du corps de toutes les fonctions PHP,
	// en particulier les requetes SQL et TOTAL_BOUCLE
	// de'terminables seulement maintenant

	foreach ($boucles as $id => $boucle) {
		$boucle = $boucles[$id] = pipeline('pre_boucle', $boucle);
		if ($boucle->return === false) {
			$corps = false;
			continue;
		}
		// appeler la fonction de definition de la boucle

		if ($req = $boucle->type_requete) {
			// boucle personnalisée ?
			$table = strtoupper($boucle->type_requete);
			$serveur = strtolower($boucle->sql_serveur);
			if (
				// fonction de boucle avec serveur & table
				(!$serveur or
					((!function_exists($f = 'boucle_' . $serveur . '_' . $table))
						and (!function_exists($f = $f . '_dist'))
					)
				)
				// fonction de boucle avec table
				and (!function_exists($f = 'boucle_' . $table))
				and (!function_exists($f = $f . '_dist'))
			) {
				// fonction de boucle standard
				if (!function_exists($f = 'boucle_DEFAUT')) {
					$f = 'boucle_DEFAUT_dist';
				}
			}

			$req = "\n\n\tstatic \$command = array();\n\t" .
				"static \$connect;\n\t" .
				"\$command['connect'] = \$connect = " .
				_q($boucle->sql_serveur) .
				';' .
				$f($id, $boucles);
		} else {
			$req = ("\n\treturn '';");
		}

		$boucles[$id]->return =
			"\n\nfunction BOUCLE" . strtr($id, '-', '_') . $nom .
			'(&$Cache, &$Pile, &$doublons, &$Numrows, $SP) {' .
			$req .
			"\n}\n";
	}

	// Au final, si le corps ou un critere au moins s'est mal compile
	// retourner False, sinon inserer leur decompilation
	if (is_bool($corps)) {
		return false;
	}

	$principal = "\nfunction " . $nom . '($Cache, $Pile, $doublons = array(), $Numrows = array(), $SP = 0) {
'
		// reporter de maniere securisee les doublons inclus
		. '
	if (isset($Pile[0]["doublons"]) AND is_array($Pile[0]["doublons"]))
		$doublons = nettoyer_env_doublons($Pile[0]["doublons"]);

	$connect = ' .
		_q($connect) . ';
	$page = ' .
		// ATTENTION, le calcul de l'expression $corps affectera $Cache
		// c'est pourquoi on l'affecte a la variable auxiliaire $page.
		// avant de referencer $Cache
		$corps . ';

	return analyse_resultat_skel(' . var_export($nom, true)
		. ', $Cache, $page, ' . var_export($sourcefile, true) . ');
}';

	$secondes = spip_timer('calcul_skel');
	spip_log("COMPIL ($secondes) [$sourcefile] $nom.php");
	// $connect n'est pas sûr : on nettoie
	$connect = preg_replace(',[^\w],', '', $connect);

	// Assimiler la fct principale a une boucle anonyme, pour retourner un resultat simple
	$code = new Boucle();
	$code->descr = $descr;
	$code->return = '
//
// Fonction principale du squelette ' .
		$sourcefile .
		($connect ? " pour $connect" : '') .
		(!CODE_COMMENTE ? '' : "\n// Temps de compilation total: $secondes") .
		"\n//\n" .
		$principal;

	$boucles[''] = $code;

	return $boucles;
}


/**
 * Requeteur pour les boucles (php:nom_iterateur)
 *
 * Analyse si le nom d'iterateur correspond bien a une classe PHP existante
 * et dans ce cas charge la boucle avec cet iterateur.
 * Affichera une erreur dans le cas contraire.
 *
 * @param $boucles Liste des boucles
 * @param $boucle  La boucle parcourue
 * @param $id      L'identifiant de la boucle parcourue
 *
 **/
function requeteur_php_dist(&$boucles, &$boucle, &$id) {
	if (class_exists($boucle->type_requete)) {
		$g = charger_fonction('php', 'iterateur');
		$boucles[$id] = $g($boucle, $boucle->type_requete);
	} else {
		$x = $boucle->type_requete;
		$boucle->type_requete = false;
		$msg = [
			'zbug_iterateur_inconnu',
			['iterateur' => $x]
		];
		erreur_squelette($msg, $boucle);
	}
}


/**
 * Requeteur pour les boucles (data:type de donnee)
 * note: (DATA) tout court ne passe pas par ici.
 *
 * Analyse si le type de donnee peut etre traite
 * et dans ce cas charge la boucle avec cet iterateur.
 * Affichera une erreur dans le cas contraire.
 *
 * @param $boucles Liste des boucles
 * @param $boucle  La boucle parcourue
 * @param $id      L'identifiant de la boucle parcourue
 *
 **/
function requeteur_data_dist(&$boucles, &$boucle, &$id) {
	include_spip('iterateur/data');
	if ($h = charger_fonction($boucle->type_requete . '_to_array', 'inc', true)) {
		$g = charger_fonction('data', 'iterateur');
		$boucles[$id] = $g($boucle);
		// from[0] stocke le type de data (rss, yql, ...)
		$boucles[$id]->from[] = $boucle->type_requete;
	} else {
		$x = $boucle->type_requete;
		$boucle->type_requete = false;
		$msg = [
			'zbug_requeteur_inconnu',
			[
				'requeteur' => 'data',
				'type' => $x
			]
		];
		erreur_squelette($msg, $boucle);
	}
}
