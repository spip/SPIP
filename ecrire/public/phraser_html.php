<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

use Spip\Compilateur\Noeud\Boucle;
use Spip\Compilateur\Noeud\Champ;
use Spip\Compilateur\Noeud\Critere;
use Spip\Compilateur\Noeud\Idiome;
use Spip\Compilateur\Noeud\Inclure;
use Spip\Compilateur\Noeud\Polyglotte;
use Spip\Compilateur\Noeud\Texte;

/**
 * Phraseur d'un squelette ayant une syntaxe SPIP/HTML
 *
 * Ce fichier transforme un squelette en un tableau d'objets de classe Boucle
 * il est chargé par un include calculé pour permettre différentes syntaxes en entrée
 *
 * @package SPIP\Core\Compilateur\Phraseur
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/** Début de la partie principale d'une boucle */
define('BALISE_BOUCLE', '<BOUCLE');
/** Fin de la partie principale d'une boucle */
define('BALISE_FIN_BOUCLE', '</BOUCLE');
/** Début de la partie avant non optionnelle d'une boucle (toujours affichee)*/
define('BALISE_PREAFF_BOUCLE', '<BB');
/** Début de la partie optionnelle avant d'une boucle */
define('BALISE_PRECOND_BOUCLE', '<B');
/** Fin de la partie optionnelle après d'une boucle */
define('BALISE_POSTCOND_BOUCLE', '</B');
/** Fin de la partie après non optionnelle d'une boucle (toujours affichee) */
define('BALISE_POSTAFF_BOUCLE', '</BB');
/** Fin de la partie alternative après d'une boucle */
define('BALISE_ALT_BOUCLE', '<//B');

/** Indique un début de boucle récursive */
define('TYPE_RECURSIF', 'boucle');
/** Expression pour trouver le type de boucle (TABLE autre_table ?) */
define('SPEC_BOUCLE', '/\s*\(\s*([^\s?)]+)(\s*[^)?]*)([?]?)\)/');
/** Expression pour trouver un identifiant de boucle */
define('NOM_DE_BOUCLE', '[0-9]+|[-_][-_.a-zA-Z0-9]*');
/**
 * Nom d'une balise #TOTO
 *
 * Écriture alambiquée pour rester compatible avec les hexadecimaux des vieux squelettes */
define('NOM_DE_CHAMP', '#((' . NOM_DE_BOUCLE . "):)?(([A-F]*[G-Z_][A-Z_0-9]*)|[A-Z_]+)\b(\*{0,2})");
/** Balise complète [...(#TOTO) ... ] */
define('CHAMP_ETENDU', '/\[([^\[]*?)\(' . NOM_DE_CHAMP . '([^)]*\)[^]]*)\]/S');

define('BALISE_INCLURE', '/<INCLU[DR]E[[:space:]]*(\(([^)]*)\))?/S');
define('BALISE_POLYGLOTTE', ',<multi>(.*)</multi>,Uims');
define('BALISE_IDIOMES', ',<:(([a-z0-9_]+):)?([a-z0-9_]*)({([^\|=>]*=[^\|>]*)})?((\|[^>]*)?:/?>),iS');
define('BALISE_IDIOMES_ARGS', '@^\s*([^= ]*)\s*=\s*((' . NOM_DE_CHAMP . '[{][^}]*})?[^,]*)\s*,?\s*@s');

/** Champ sql dans parenthèse ex: (id_article) */
define('SQL_ARGS', '(\([^)]*\))');
/** Fonction SQL sur un champ ex: SUM(visites) */
define('CHAMP_SQL_PLUS_FONC', '`?([A-Z_\/][A-Z_\/0-9.]*)' . SQL_ARGS . '?`?');

/**
 * Parser les <INCLURE> dans le texte
 */
function phraser_inclure(string $texte, int $ligne, array $result): array {

	while (
		(($p = strpos($texte, '<INC')) !== false)
		&& preg_match(BALISE_INCLURE, $texte, $match, PREG_OFFSET_CAPTURE, $p)
	) {
		$poss = array_column($match, 1);
		$match = array_column($match, 0);
		$match = array_pad($match, 3, null);

		$p = $poss[0];
		$debut = substr($texte, 0, $p);
		if ($p) {
			$result = phraser_champs($debut, $ligne, $result);
		}
		$ligne += public_compte_ligne($debut);

		$champ = new Inclure();
		$champ->ligne = $ligne;
		$ligne += public_compte_ligne((string) $match[0]);
		$fichier = $match[2];
		$champ->texte = $fichier;

		$texte = substr($texte, $p + strlen((string) $match[0]));

		// on assimile {var=val} a une liste de un argument sans fonction
		$pos_apres = 0;
		phraser_args($texte, '/>', '', $result, $champ, $pos_apres);
		if (!$champ->texte || (is_countable($champ->param) ? count($champ->param) : 0) > 1) {
			if (!function_exists('normaliser_inclure')) {
				include_spip('public/normaliser');
			}
			normaliser_inclure($champ);
		}
		$pos_fin = strpos($texte, '>', $pos_apres) + 1;
		if (
			(strpos($texte, '</INCLUDE>', $pos_fin) === $pos_fin)
			|| (strpos($texte, '</INCLURE>', $pos_fin) === $pos_fin)
		) {
			$pos_fin += 10;
		}
		$texte = substr($texte, $pos_fin);
		$result[] = $champ;
	}

	if ($texte != '') {
		$result = phraser_champs($texte, $ligne, $result);
	}

	return $result;
}

/**
 * Phraser les <multi>...</multi>
 * on passe en dernier de toutes les analyses :
 * a ce stade il ne reste que des morceaux de texte entre balises/boucles, donc une <multi> ne peut pas contenir de balises
 *
 * @use Spip\Texte\Collecteur\Multis
 */
function phraser_polyglotte(string $texte, int $ligne, array $result): array {

	$collecteur = new Spip\Texte\Collecteur\Multis();
	$multis = $collecteur->collecter($texte);

	if (!empty($multis)) {
		$pos_prev = 0;
		foreach ($multis as $multi) {
			if ($multi['pos'] > $pos_prev) {
				$champ = new Texte();
				$champ->texte = substr($texte, $pos_prev, $multi['pos'] - $pos_prev);
				$champ->ligne = $ligne;
				$result[] = $champ;
				$ligne += public_compte_ligne($champ->texte);
			}

			$champ = new Polyglotte();
			$champ->ligne = $ligne;
			$champ->traductions = $multi['trads'];
			$result[] = $champ;
			$ligne += public_compte_ligne($multi['raw']);
			$pos_prev = $multi['pos'] + $multi['length'];
		}
		$texte = substr($texte, $pos_prev);
	}

	if ($texte !== '') {
		$champ = new Texte();
		$champ->texte = $texte;
		$champ->ligne = $ligne;
		$result[] = $champ;
	}

	return $result;
}


/**
 * Repérer les balises de traduction (idiomes)
 *
 * Phrase les idiomes tel que
 * - `<:chaine:>`
 * - `<:module:chaine:>`
 * - `<:module:chaine{arg1=texte1,arg2=#BALISE}|filtre1{texte2,#BALISE}|filtre2:>`
 * - `<:module:chaine{arg1=texte1,arg2=[(#BALISE)]}|filtre1{texte2,#BALISE}|filtre2:>`
 * - `<:module:{=#VAL{chaine},arg1=texte1,arg2=[(#BALISE)]}|filtre1{texte2,#BALISE}|filtre2:>`
 * - `<:{=#VAL{module:chaine},arg1=texte1,arg2=[(#BALISE)]}|filtre1{texte2,#BALISE}|filtre2:>`
 *
 * Pour permettre une syntaxe complète dans les arguments, on transforme la syntaxe <:..:> en une syntaxe standard de balises
 * `[(#TRAD{module:chaine,arg1=texte1,arg2=[(#BALISE)]}|filtre1{texte2,#BALISE}|filtre2)]`
 *
 * qui est ensuite parsée comme une balise standard
 *
 * @note
 *    `chaine` peut etre vide si `=texte1` est present et `arg1` est vide
 *    sinon ce n'est pas un idiome
 *
 * @param string $texte
 * @param int $ligne
 * @param array $result
 * @return string
 **/
function phraser_preparer_idiomes(string $texte, int $ligne, string $sep, array $result) {
	if (empty($texte) or !str_contains($texte, '<:')) {
		return $texte;
	}

	// definir un placholder pour les idiomes dont on est certain d'avoir aucune occurence dans le squelette
	do {
		$idiomes_placeholder = 'PLACEHOLDER_IDIOME_' . strtoupper(substr(md5(uniqid()),0,8));
	} while (str_contains((string) $texte, $idiomes_placeholder));


	$search_pos = 0;
	$nbl = $ligne;
	while ((($p = strpos($texte, '<:', $search_pos)) !== false)
		&& preg_match(BALISE_IDIOMES, $texte, $match, PREG_OFFSET_CAPTURE, $p)) {

		$poss = array_column($match, 1);
		$match = array_column($match, 0);

		$module = $match[2];
		$chaine = $match[3];
		$idiome = $match[1] . $match[3];
		$args = $match[5];
		$filtres = $match[7];

		// faux idiome de la forme <:module:{xxx=..}:> ou <:module::>
		if (!$chaine && (empty($args) || !str_starts_with($args, '='))) {
			// neutraliser le faux idiome pour eviter de revenir là encore et encore
			$texte = substr_replace($texte, '#VAL{<}', $poss[0], 1);
			$search_pos = $poss[0] + 7;
			continue;
		}

		// gerer les cas implicites :
		// si pas de module défini, injecter le par défaut, sauf si c'est une chaine de langue dynamique
		if (!$module and $chaine) {
			$idiome = "'" . MODULES_IDIOMES . "':" . $idiome;
		}

		// si possible injecter une version légère de la balise pour faciliter le parsing
		if (empty($filtres) and (empty($args) || strpbrk($args, '[]') === false)) {
			$texte_idiome = '#TRAD{' . ($idiome ?: "''") . ($args ? "," . $args : '') . '}';
		}
		else {
			$texte_idiome = '[(#TRAD{' . ($idiome ?: "''") . ($args ? "," . $args : '') . '}'.$filtres.')]';
		}

		// parser le $texte_idiome pour en déduire le $champ qui correspond
		$nbl += public_compte_ligne($texte, $search_pos, $poss[0]);
		$champs = phraser_champs_interieurs($texte_idiome, $nbl, $sep);

		// on doit trouver un et un seul champ, celui de notre balise #TRAD
		if (count($champs) !== 1) {
			erreur_squelette("Echec analyse idiome ".htmlentities($match[0]. " / " . $texte_idiome));
			// fallback, on insere le texte_idiome plutot que le placeholder...
			$texte = substr_replace($texte, $texte_idiome, $poss[0], strlen($match[0]));
		}
		else {
			$champ = reset($champs);
			$placeholder = public_generer_placeholder($match[0], $champ, $idiomes_placeholder, 0);

			$texte = substr_replace($texte, $placeholder, $poss[0], strlen($match[0]));
		}

		$search_pos = $poss[0] + 1;
	}
	return $texte;
}

/**
 * Repère et phrase les balises SPIP tel que `#NOM` dans un texte
 *
 * Phrase également ses arguments si la balise en a (`#NOM{arg, ...}`)
 *
 * @uses phraser_polyglotte()
 * @uses phraser_args()
 * @uses phraser_vieux()
 *
 * @param string $texte
 * @param int $ligne
 * @param array $result
 * @return array
 **/
function phraser_champs(string $texte, int $ligne, array $result): array {

	while (
		(($p = strpos($texte, '#')) !== false)
		&& preg_match('/' . NOM_DE_CHAMP . '/S', $texte, $match, PREG_OFFSET_CAPTURE, $p)
	) {
		$poss = array_column($match, 1);
		$match = array_column($match, 0);

		$p = $poss[0];
		if ($p) {
			$debut = substr($texte, 0, $p);
			$result = phraser_polyglotte($debut, $ligne, $result);
			$ligne += public_compte_ligne($debut);
		}

		$champ = new Champ();
		$champ->ligne = $ligne;
		$ligne += public_compte_ligne($match[0]);
		$champ->nom_boucle = $match[2];
		$champ->nom_champ = $match[3];
		$champ->etoile = $match[5];

		// texte après la balise
		$suite = substr($texte, $p + strlen($match[0]));
		if ($suite && str_starts_with($suite, '{')) {
			phraser_arg($suite, '', [], $champ);
			// ce ltrim est une ereur de conception
			// mais on le conserve par souci de compatibilite
			$texte = ltrim((string) $suite);
			// Il faudrait le normaliser dans l'arbre de syntaxe abstraite
			// pour faire sauter ce cas particulier a la decompilation.
			/* Ce qui suit est malheureusement incomplet pour cela:
			if ($n = (strlen($suite) - strlen($texte))) {
				$champ->apres = array(new Texte);
				$champ->apres[0]->texte = substr($suite,0,$n);
			}
			*/
		} else {
			$texte = $suite;
		}

		// reinjecter la chaine de langue si c'est un placeholder
		phraser_memoriser_ou_reinjecter_placeholder($champ);

		phraser_vieux($champ);
		$result[] = $champ;
	}
	if ($texte !== '') {
		$result = phraser_polyglotte($texte, $ligne, $result);
	}

	return $result;
}

/**
 * Phraser les champs etendus
 *
 * @note
 *   C'est `phraser_champs_interieurs()` qui va le faire.
 *   On lui fournit un marqueur `$sep` qui n'est pas contenu dans le texte
 *   et qu'il peut utiliser de manière sûre pour remplacer au fur et à mesure
 *   les champs imbriquées qu'il va trouver
 *
 * @see phraser_champs_interieurs()
 */
function phraser_champs_etendus(string $texte, int $ligne, array $result): array {
	if ($texte === '') {
		return $result;
	}

	$sep = '##';
	while (str_contains($texte, $sep)) {
		$sep .= '#';
	}

	// avant de commencer on repere et convertit les idiomes historiques <:xxx:xxxx{...}:> en #PLACEHOLDER_IDIOME_xxx
	$texte = phraser_preparer_idiomes($texte, $ligne, $sep, $result);


	$champs = phraser_champs_interieurs($texte, $ligne, $sep);
	return array_merge($result, $champs);
}

/**
 * Analyse les filtres d'un champ etendu et affecte le resultat
 * renvoie la liste des lexemes d'origine augmentee
 * de ceux trouves dans les arguments des filtres (rare)
 * sert aussi aux arguments des includes et aux criteres de boucles
 * Tres chevelu
 *
 * @param Champ|Inclure|Idiome|Boucle $pointeur_champ
 */
function phraser_args(string $texte, string $fin, string $sep, array $result, &$pointeur_champ, int &$pos_debut): array {
	$length = strlen($texte);
	while ($pos_debut < $length && trim($texte[$pos_debut]) === '') {
		$pos_debut++;
	}
	while (($pos_debut < $length) && !str_contains($fin, $texte[$pos_debut])) {
		// phraser_arg modifie directement le $texte, on fait donc avec ici en passant par une sous chaine
		$st = substr($texte, $pos_debut);
		$result = phraser_arg($st, $sep, $result, $pointeur_champ);
		$pos_debut = $length - strlen((string) $st);
		while ($pos_debut < $length && trim($texte[$pos_debut]) === '') {
			$pos_debut++;
		}
	}

	return $result;
}

function phraser_arg(&$texte, $sep, $result, &$pointeur_champ) {
	preg_match(',^(\|?[^}{)|]*)(.*)$,ms', (string) $texte, $match);
	$suite = ltrim($match[2]);
	$fonc = trim($match[1]);
	if ($fonc && $fonc[0] == '|') {
		$fonc = ltrim(substr($fonc, 1));
	}
	$res = [$fonc];
	$err_f = '';
	// cas du filtre sans argument ou du critere /
	if (($suite && ($suite[0] != '{')) || ($fonc && $fonc[0] == '/')) {
		// si pas d'argument, alors il faut une fonction ou un double |
		if (!$match[1]) {
			$err_f = ['zbug_erreur_filtre', ['filtre' => $texte]];
			erreur_squelette($err_f, $pointeur_champ);
			$texte = '';
		} else {
			$texte = $suite;
		}
		if ($err_f) {
			$pointeur_champ->param = false;
		} elseif ($fonc !== '') {
			$pointeur_champ->param[] = $res;
		}
		// pour les balises avec faux filtres qui boudent ce dur larbeur
		$pointeur_champ->fonctions[] = [$fonc, ''];

		return $result;
	}
	$args = ltrim(substr($suite, 1)); // virer le '(' initial
	$collecte = [];
	while ($args && $args[0] != '}') {
		if ($args[0] == '"') {
			preg_match('/^(")([^"]*)(")(.*)$/ms', $args, $regs);
		} elseif ($args[0] == "'") {
			preg_match("/^(')([^']*)(')(.*)$/ms", $args, $regs);
		} else {
			preg_match('/^([[:space:]]*)([^,([{}]*([(\[{][^])}]*[])}])?[^,}]*)([,}].*)$/ms', $args, $regs);
			if (!isset($regs[2]) || !strlen($regs[2])) {
				$err_f = ['zbug_erreur_filtre', ['filtre' => $args]];
				erreur_squelette($err_f, $pointeur_champ);
				$champ = new Texte();
				$champ->apres = $champ->avant = $args = '';
				break;
			}
		}
		$arg = $regs[2];
		if (trim($regs[1])) {
			$champ = new Texte();
			$champ->texte = $arg;
			$champ->apres = $champ->avant = $regs[1];
			$result[] = $champ;
			$collecte[] = $champ;
			$args = ltrim($regs[count($regs) - 1]);
		} else {
			if (!preg_match('/' . NOM_DE_CHAMP . '([{|])/', $arg, $r)) {
				// 0 est un aveu d'impuissance. A completer
				$arg = phraser_champs_exterieurs($arg, 0, $sep, $result);

				$args = ltrim($regs[count($regs) - 1]);
				$collecte = array_merge($collecte, $arg);
				$result = array_merge($result, $arg);
			} else {
				$n = strpos($args, (string) $r[0]);
				$pred = substr($args, 0, $n);
				$par = ',}';
				if (preg_match('/^(.*)\($/', $pred, $m)) {
					$pred = $m[1];
					$par = ')';
				}
				if ($pred) {
					$champ = new Texte();
					$champ->texte = $pred;
					$champ->apres = $champ->avant = '';
					$result[] = $champ;
					$collecte[] = $champ;
				}
				$rec = substr($args, $n + strlen($r[0]) - 1);
				$champ = new Champ();
				$champ->nom_boucle = $r[2];
				$champ->nom_champ = $r[3];
				$champ->etoile = $r[5];
				$next = $r[6];
				while ($next == '{') {
					phraser_arg($rec, $sep, [], $champ);
					$args = ltrim((string) $rec);
					$next = $args[0] ?? '';
				}
				while ($next == '|') {
					$pos_apres = 0;
					phraser_args($rec, $par, $sep, [], $champ, $pos_apres);
					$args = substr((string) $rec, $pos_apres);
					$next = $args[0] ?? '';
				}
				// Si erreur de syntaxe dans un sous-argument, propager.
				if ($champ->param === false) {
					$err_f = true;
				} else {
					phraser_vieux($champ);
				}
				if ($par == ')') {
					$args = substr($args, 1);
				}
				$collecte[] = $champ;
				$result[] = $champ;
			}
		}
		if (isset($args[0]) && $args[0] == ',') {
			$args = ltrim(substr($args, 1));
			if ($collecte) {
				$res[] = $collecte;
				$collecte = [];
			}
		}
	}
	if ($collecte) {
		$res[] = $collecte;
		$collecte = [];
	}
	$texte = substr($args, 1);
	$source = substr($suite, 0, strlen($suite) - strlen($texte));
	// propager les erreurs, et ignorer les param vides
	if ($pointeur_champ->param !== false) {
		if ($err_f) {
			$pointeur_champ->param = false;
		} elseif ($fonc !== '' || count($res) > 1) {
			$pointeur_champ->param[] = $res;
		}
	}
	// pour les balises avec faux filtres qui boudent ce dur larbeur
	$pointeur_champ->fonctions[] = [$fonc, $source];

	return $result;
}

/**
 * Reconstruire un tableau de resultat ordonné selon l'ordre d'apparition dans le texte issu de phraser_champs_interieurs()
 * et phraser les inclure sur les morceaux intermédiaires
 */
function phraser_champs_exterieurs(string $texte, int $ligne, string $sep, array $nested_res): array {
	$res = [];
	$preg = ',^%' . preg_quote($sep, ',') . '([0-9]+)(\n*)@,';
	while (($p = strpos($texte, "%$sep")) !== false) {
		$suite = substr($texte, $p);
		if (!preg_match($preg, $suite, $m)) {
			break;
		}
		if ($p) {
			$debut = substr($texte, 0, $p);
			$res = phraser_inclure($debut, $ligne, $res);
			$ligne += public_compte_ligne($debut);
		}
		$res[] = $nested_res[$m[1]];
		$ligne += strlen($m[2]);
		$texte = substr($suite, strlen($m[0]));
	}

	if ($texte !== '') {
		$res = phraser_inclure($texte, $ligne, $res);
	}

	return $res;
}

/**
 * Parser un texte pour trouver toutes les balises complètes `[...(#TRUC)...]` en gérant les imbrications possibles
 *
 * Pour cela on commence par les plus profondes, sans rien dedans,
 * on les remplace par un placehoder inactif `%###N@` ou N indexe un tableau comportant le resultat de leur analyse
 * et on recommence jusqu'à ce qu'on ne trouve plus rien
 *
 * @param string $texte
 * @param int $no_ligne
 * @param string $sep
 * @return array
 */
function phraser_champs_interieurs(string $texte, int $no_ligne, string $sep): array {

	$champs_trouves = [];
	do {
		$parties = [];
		$nbl = $no_ligne;
		$search_pos = 0;

		// trouver tous les champs intérieurs (sans autre champs imbriqués), les analyser, et les remplacer par un placehoder
		// le $texte est découpé en parties qu'on re-parse ensuite jusqu'à ce qu'on ne trouve plus de nouveaux champs
		while (
			(($p = strpos($texte, '[', $search_pos)) !== false)
			&& preg_match(CHAMP_ETENDU, $texte, $match, PREG_OFFSET_CAPTURE, $p)
		) {
			$poss = array_column($match, 1);
			$match = array_column($match, 0);
			// si jamais il y a une sous balise inclue dans la partie 7, alors on est pas dans le champ le plus interieur, on continue le search plus loin
			if (str_contains($match[7], '[') && preg_match(CHAMP_ETENDU, $texte, $r, 0, $poss[7])) {
				$search_pos = $poss[7];
				continue;
			}

			$nbl_debut = 0;
			if ($poss[0]) {
				$nbl_debut = public_compte_ligne($texte, 0, $poss[0]);
				$parties[] = substr($texte, 0, $poss[0]);
			}
			$nbl += $nbl_debut;

			$champ = new Champ();
			$champ->ligne = $nbl;
			$champ->nom_boucle = $match[3];
			$champ->nom_champ = $match[4];
			$champ->etoile = $match[6];
			$nbl_champ = public_compte_ligne($texte, $poss[0], $poss[0] + strlen($match[0]));

			// phraser_args indiquera ou commence apres
			$pos_apres = 0;
			$champs_trouves = phraser_args($match[7], ')', $sep, $champs_trouves, $champ, $pos_apres);
			phraser_vieux($champ);
			$champ->avant =	phraser_champs_exterieurs($match[1], $nbl, $sep, $champs_trouves);
			$apres = substr($match[7], $pos_apres + 1);

			$nbl_debut_champ = 0;
			if (!empty($apres)) {
				$nbl_debut_champ = public_compte_ligne($texte, $poss[0], $poss[7] + $pos_apres + 1);
			}
			$champ->apres = phraser_champs_exterieurs($apres, $nbl + $nbl_debut_champ, $sep, $champs_trouves);

			// reinjecter la boucle ou la chaine de langie si c'est un placeholder
			phraser_memoriser_ou_reinjecter_placeholder($champ);

			$champs_trouves[] = $champ;
			$j = count($champs_trouves) - 1;
			// on remplace ce champ par un placeholder
			// ajouter $nbl_champ retour ligne pour que la partie conserve le nombre de lignes lors des itérations suivantes
			$parties[] = ($t = "%{$sep}{$j}" . str_repeat("\n", $nbl_champ) . '@');
			$nbl += $nbl_champ;

			$texte = substr($texte, $poss[0] + strlen($match[0]));
			$search_pos = 0;
		}

		// si on a trouvé des morceaux, il faut recommencer
		if (count($parties)) {
			// reprenons tous les morceaux qu'on a mis de côté car ne matchant pas (encore)
			$texte = implode('', $parties) . $texte;
		}
	} while (count($parties));

	return phraser_champs_exterieurs($texte, $no_ligne, $sep, $champs_trouves);
}

/**
 * Gerer les derogations de syntaxe historiques
 * Ne concerne plus que #MODELE et <INCLURE> / #INCLURE
 *
 * @param Champ|Inclure $champ
 * @return void
 */
function phraser_vieux(&$champ) {
	$nom = $champ->nom_champ;
	if ($champ->param) {
		if ($nom == 'MODELE') {
			if (!function_exists('phraser_vieux_modele')) {
				include_spip('public/normaliser');
			}
			phraser_vieux_modele($champ);
		} elseif ($nom == 'INCLURE' || $nom == 'INCLUDE') {
			if (!function_exists('phraser_vieux_inclu')) {
				include_spip('public/normaliser');
			}
			phraser_vieux_inclu($champ);
		}
	}
}


/**
 * Analyse les critères de boucle
 *
 * Chaque paramètre de la boucle (tel que {id_article>3}) est analysé
 * pour construire un critère (objet Critere) de boucle.
 *
 * Un critère a une description plus fine que le paramètre original
 * car on en extrait certaines informations tel que la négation et l'opérateur
 * utilisé s'il y a.
 *
 * La fonction en profite pour déclarer des modificateurs de boucles
 * en présence de certains critères (tout, plat) ou initialiser des
 * variables de compilation (doublons)...
 *
 * @param array $params
 *     Tableau de description des paramètres passés à la boucle.
 *     Chaque paramètre deviendra un critère
 * @param Boucle $result
 *     Description de la boucle
 *     Elle sera complété de la liste de ses critères
 * @return void
 **/
function phraser_criteres($params, &$result) {

	$err_ci = ''; // indiquera s'il y a eu une erreur
	$args = [];
	$type = $result->type_requete;
	$doublons = [];
	foreach ($params as $v) {
		$var = $v[1][0];
		$param = ($var->type != 'texte') ? '' : $var->texte;
		if (((is_countable($v) ? count($v) : 0) > 2) && (!preg_match(',[^A-Za-z]IN[^A-Za-z],i', (string) $param))) {
			// plus d'un argument et pas le critere IN:
			// detecter comme on peut si c'est le critere implicite LIMIT debut, fin
			if (
				$var->type != 'texte' || preg_match('/^(n|n-|(n-)?\d+)$/S', (string) $param)
			) {
				$op = ',';
				$not = false;
				$cond = false;
			} else {
				// Le debut du premier argument est l'operateur
				preg_match('/^([!]?)([a-zA-Z]\w*)[[:space:]]*(\??)[[:space:]]*(.*)$/ms', (string) $param, $m);
				$op = $m[2];
				$not = (bool) $m[1];
				$cond = (bool) $m[3];
				// virer le premier argument,
				// et mettre son reliquat eventuel
				// Recopier pour ne pas alterer le texte source
				// utile au debusqueur
				if ($m[4]) {
					// une maniere tres sale de supprimer les "' autour de {critere "xxx","yyy"}
					if (preg_match(',^(["\'])(.*)\1$,', $m[4])) {
						$c = null;
						eval('$c = ' . $m[4] . ';');
						if (isset($c)) {
							$m[4] = $c;
						}
					}
					$texte = new Texte();
					$texte->texte = $m[4];
					$v[1][0] = $texte;
				} else {
					array_shift($v[1]);
				}
			}
			array_shift($v); // $v[O] est vide
			$crit = new Critere();
			$crit->op = $op;
			$crit->not = $not;
			$crit->cond = $cond;
			$crit->exclus = '';
			$crit->param = $v;
			$args[] = $crit;
		} else {
			if ($var->type != 'texte') {
				// cas 1 seul arg ne commencant pas par du texte brut:
				// erreur ou critere infixe "/"
				if (($v[1][1]->type != 'texte') || (trim((string) $v[1][1]->texte) != '/')) {
					$err_ci = [
						'zbug_critere_inconnu',
						['critere' => $var->nom_champ]
					];
					erreur_squelette($err_ci, $result);
				} else {
					$crit = new Critere();
					$crit->op = '/';
					$crit->not = false;
					$crit->exclus = '';
					$crit->param = [[$v[1][0]], [$v[1][2]]];
					$args[] = $crit;
				}
			} else {
				// traiter qq lexemes particuliers pour faciliter la suite
				// les separateurs
				if ($var->apres) {
					$result->separateur[] = $param;
				} elseif ($param == 'tout' || $param == 'tous') {
					$result->modificateur['tout'] = true;
				} elseif ($param == 'plat') {
					$result->modificateur['plat'] = true;
				}

				// Boucle hierarchie, analyser le critere id_rubrique
				// et les autres critères {id_x} pour forcer {tout} sur
				// ceux-ci pour avoir la rubrique mere...
				// Les autres critères de la boucle hierarchie doivent être
				// traités normalement.
				elseif (
					strcasecmp($type, 'hierarchie') == 0
					&& !preg_match(",^id_rubrique\b,", (string) $param)
					&& preg_match(',^id_\w+\s*$,', (string) $param)
				) {
					$result->modificateur['tout'] = true;
				} elseif (strcasecmp($type, 'hierarchie') == 0 && $param == 'id_rubrique') {
					// rien a faire sur {id_rubrique} tout seul
				} else {
					// pas d'emplacement statique, faut un dynamique
					// mais il y a 2 cas qui ont les 2 !
					if (($param == 'unique') || (preg_match(',^!?doublons *,', (string) $param))) {
						// cette variable sera inseree dans le code
						// et son nom sert d'indicateur des maintenant
						$result->doublons = '$doublons_index';
						if ($param == 'unique') {
							$param = 'doublons';
						}
					} elseif ($param == 'recherche') {
						// meme chose (a cause de #nom_de_boucle:URL_*)
						$result->hash = ' ';
					}

					if (preg_match(',^ *([0-9-]+) *(/) *(.+) *$,', (string) $param, $m)) {
						$crit = phraser_critere_infixe($m[1], $m[3], $v, '/', '', '');
					} elseif (
						preg_match(',^([!]?)(' . CHAMP_SQL_PLUS_FONC .
						')[[:space:]]*(\??)(!?)(<=?|>=?|==?|\b(?:IN|LIKE)\b)(.*)$,is', (string) $param, $m)
					) {
						$a2 = trim($m[8]);
						if ($a2 && ($a2[0] == "'" || $a2[0] == '"') && $a2[0] == substr($a2, -1)) {
							$a2 = substr($a2, 1, -1);
						}
						$crit = phraser_critere_infixe(
							$m[2],
							$a2,
							$v,
							(($m[2] == 'lang_select') ? $m[2] : $m[7]),
							$m[6],
							$m[5]
						);
						$crit->exclus = $m[1];
					} elseif (
						preg_match('/^([!]?)\s*(' .
						CHAMP_SQL_PLUS_FONC .
						')\s*(\??)(.*)$/is', (string) $param, $m)
					) {
						// contient aussi les comparaisons implicites !
						// Comme ci-dessus:
						// le premier arg contient l'operateur
						array_shift($v);
						if ($m[6]) {
							$v[0][0] = new Texte();
							$v[0][0]->texte = $m[6];
						} else {
							array_shift($v[0]);
							if (!$v[0]) {
								array_shift($v);
							}
						}
						$crit = new Critere();
						$crit->op = $m[2];
						$crit->param = $v;
						$crit->not = (bool) $m[1];
						$crit->cond = (bool) $m[5];
					} else {
						$err_ci = [
							'zbug_critere_inconnu',
							['critere' => $param]
						];
						erreur_squelette($err_ci, $result);
					}

					if ((!preg_match(',^!?doublons *,', (string) $param)) || $crit->not) {
						$args[] = $crit;
					} else {
						$doublons[] = $crit;
					}
				}
			}
		}
	}

	// les doublons non nies doivent etre le dernier critere
	// pour que la variable $doublon_index ait la bonne valeur
	// cf critere_doublon
	if ($doublons) {
		$args = [...$args, ...$doublons];
	}

	// Si erreur, laisser la chaine dans ce champ pour le HTTP 503
	if (!$err_ci) {
		$result->criteres = $args;
	}
}

function phraser_critere_infixe($arg1, $arg2, $args, $op, $not, $cond) {
	$args[0] = new Texte();
	$args[0]->texte = $arg1;
	$args[0] = [$args[0]];
	$args[1][0] = new Texte();
	$args[1][0]->texte = $arg2;
	$crit = new Critere();
	$crit->op = $op;
	$crit->not = (bool) $not;
	$crit->cond = (bool) $cond;
	$crit->param = $args;

	return $crit;
}

/**
 * Compter le nombre de lignes dans une partie texte
 * @param string $texte
 * @param int $debut
 * @param int|null $fin
 * @return int
 */
function public_compte_ligne($texte, $debut = 0, $fin = null) {
	if (is_null($fin)) {
		return substr_count((string) $texte, "\n", $debut);
	} else {
		return substr_count((string) $texte, "\n", $debut, $fin - $debut);
	}
}


/**
 * Trouver la boucle qui commence en premier dans un texte
 * On repere les boucles via <BOUCLE_xxx(
 * et ensuite on regarde son vrai debut soit <B_xxx> soit <BB_xxx>
 */
function public_trouver_premiere_boucle(string $texte, string $id_parent, array $descr, int $pos_debut_texte = 0): ?array {
	$premiere_boucle = null;
	$pos_derniere_boucle_anonyme = $pos_debut_texte;

	$current_pos = $pos_debut_texte;
	while (($pos_boucle = strpos((string) $texte, BALISE_BOUCLE, $current_pos)) !== false) {
		$current_pos = $pos_boucle + 1;
		$pos_parent = strpos((string) $texte, '(', $pos_boucle);

		$id_boucle = '';
		if ($pos_parent !== false) {
			$id_boucle = trim(substr((string) $texte, $pos_boucle + strlen(BALISE_BOUCLE), $pos_parent - $pos_boucle - strlen(BALISE_BOUCLE)));
		}
		if (
			$pos_parent === false
			|| strlen($id_boucle) && (!is_numeric($id_boucle) && !str_starts_with($id_boucle, '_'))
		) {
			$result = new Boucle();
			$result->id_parent = $id_parent;
			$result->descr = $descr;

			// un id_boucle pour l'affichage de l'erreur
			if (!strlen($id_boucle)) {
				$id_boucle = substr((string) $texte, $pos_boucle + strlen(BALISE_BOUCLE), 15);
			}
			$result->id_boucle = $id_boucle;
			$err_b = ['zbug_erreur_boucle_syntaxe', ['id' => $id_boucle]];
			erreur_squelette($err_b, $result);

			continue;
		}
		else {
			$boucle = [
				'id_boucle' => $id_boucle,
				'id_boucle_err' => $id_boucle,
				'debut_boucle' => $pos_boucle,
				'pos_boucle' => $pos_boucle,
				'pos_parent' => $pos_parent,
				'pos_precond' => false,
				'pos_precond_inside' => false,
				'pos_preaff' => false,
				'pos_preaff_inside' => false,
			];

			// un id_boucle pour l'affichage de l'erreur sur les boucle anonymes
			if (!strlen($id_boucle)) {
				$boucle['id_boucle_err'] = substr((string) $texte, $pos_boucle + strlen(BALISE_BOUCLE), 15);
			}

			// trouver sa position de depart reelle : au <Bxx> ou au <BBxx>
			$precond_boucle = BALISE_PRECOND_BOUCLE . $id_boucle . '>';
			$pos_precond = strpos((string) $texte, $precond_boucle, $id_boucle ? $pos_debut_texte : $pos_derniere_boucle_anonyme);
			if (
				$pos_precond !== false
				&& $pos_precond < $boucle['debut_boucle']
			) {
				$boucle['debut_boucle'] = $pos_precond;
				$boucle['pos_precond'] = $pos_precond;
				$boucle['pos_precond_inside'] = $pos_precond + strlen($precond_boucle);
			}

			$preaff_boucle = BALISE_PREAFF_BOUCLE . $id_boucle . '>';
			$pos_preaff = strpos((string) $texte, $preaff_boucle, $id_boucle ? $pos_debut_texte : $pos_derniere_boucle_anonyme);
			if (
				$pos_preaff !== false
				&& $pos_preaff < $boucle['debut_boucle']
			) {
				$boucle['debut_boucle'] = $pos_preaff;
				$boucle['pos_preaff'] = $pos_preaff;
				$boucle['pos_preaff_inside'] = $pos_preaff + strlen($preaff_boucle);
			}
			if (!strlen($id_boucle)) {
				$pos_derniere_boucle_anonyme = $pos_boucle;
			}

			if (is_null($premiere_boucle) || $premiere_boucle['debut_boucle'] > $boucle['debut_boucle']) {
				$premiere_boucle = $boucle;
			}
		}
	}

	return $premiere_boucle;
}

/**
 * Trouver la fin de la  boucle (balises </B <//B </BB)
 * en faisant attention aux boucles anonymes qui ne peuvent etre imbriquees
 *
 * @param string $texte
 * @param string $id_parent
 * @param array $boucle
 * @param int $pos_debut_texte
 * @param object $result
 * @return array
 *   la description de la boucle dans un tableau associatif
 */
function public_trouver_fin_boucle(string $texte, string $id_parent, array $boucle, int $pos_debut_texte, $result): array {
	$id_boucle = $boucle['id_boucle'];
	$pos_courante = $pos_debut_texte;

	$boucle['pos_postcond'] = false;
	$boucle['pos_postcond_inside'] = false;
	$boucle['pos_altern'] = false;
	$boucle['pos_altern_inside'] = false;
	$boucle['pos_postaff'] = false;
	$boucle['pos_postaff_inside'] = false;

	$pos_anonyme_next = null;
	// si c'est une boucle anonyme, chercher la position de la prochaine boucle anonyme
	if (!strlen((string) $id_boucle)) {
		$pos_anonyme_next = strpos((string) $texte, BALISE_BOUCLE . '(', $pos_courante);
	}

	//
	// 1. Recuperer la partie conditionnelle apres
	//
	$apres_boucle = BALISE_POSTCOND_BOUCLE . $id_boucle . '>';
	$pos_apres = strpos((string) $texte, $apres_boucle, $pos_courante);
	if (
		$pos_apres !== false
		&& (!$pos_anonyme_next || $pos_apres < $pos_anonyme_next)
	) {
		$boucle['pos_postcond'] = $pos_apres;
		$pos_apres += strlen($apres_boucle);
		$boucle['pos_postcond_inside'] = $pos_apres;
		$pos_courante = $pos_apres ;
	}

	//
	// 2. Récuperer la partie alternative apres
	//
	$altern_boucle = BALISE_ALT_BOUCLE . $id_boucle . '>';
	$pos_altern = strpos((string) $texte, $altern_boucle, $pos_courante);
	if (
		$pos_altern !== false
		&& (!$pos_anonyme_next || $pos_altern < $pos_anonyme_next)
	) {
		$boucle['pos_altern'] = $pos_altern;
		$pos_altern += strlen($altern_boucle);
		$boucle['pos_altern_inside'] = $pos_altern;
		$pos_courante = $pos_altern;
	}

	//
	// 3. Recuperer la partie footer non alternative
	//
	$postaff_boucle = BALISE_POSTAFF_BOUCLE . $id_boucle . '>';
	$pos_postaff = strpos((string) $texte, $postaff_boucle, $pos_courante);
	if (
		$pos_postaff !== false
		&& (!$pos_anonyme_next || $pos_postaff < $pos_anonyme_next)
	) {
		$boucle['pos_postaff'] = $pos_postaff;
		$pos_postaff += strlen($postaff_boucle);
		$boucle['pos_postaff_inside'] = $pos_postaff;
		$pos_courante = $pos_postaff ;
	}

	return $boucle;
}


/**
 * @param object|string $champ
 * @param null|object $boucle_ou_champ
 */
function phraser_memoriser_ou_reinjecter_placeholder(&$champ, ?string $nom_champ_placeholder = null, $boucle_ou_champ = null) {
	static $placeholder_connus = [];
	// si c'est un appel pour memoriser une boucle, memorisons la
	if (is_string($champ) && !empty($nom_champ_placeholder) && !empty($boucle_ou_champ)) {
		$placeholder_connus[$nom_champ_placeholder][$champ] = &$boucle_ou_champ;
	}
	else {
		if (!empty($champ->nom_champ) && !empty($placeholder_connus[$champ->nom_champ])) {
			$nom_champ_placeholder = $champ->nom_champ;
			$id = '';
			if(!empty($champ->param[0][1])) {
				$id = reset($champ->param[0][1]);
				$id = $id->texte;
			}
			if (!empty($placeholder_connus[$nom_champ_placeholder][$id])) {
				$champ = $placeholder_connus[$nom_champ_placeholder][$id];
			}
		}
	}
}


/**
 * Generer une balise placeholder qui prend la place de la boucle pour continuer le parsing des balises
 * @param Boucle|Champ $boucle_ou_champ
 * @return string
 */
function public_generer_placeholder(string $nom_structure, &$boucle_ou_champ, string $placeholder_pattern, int $nb_lignes, bool $force_balise_etendue = false): string {
	if ($nb_lignes or $force_balise_etendue) {
		$nom_champ = $placeholder_pattern;
		$placeholder = "[(#{$nom_champ}{" . $nom_structure . '})' . str_pad('', $nb_lignes, "\n") . ']';
		//memoriser la boucle a reinjecter
		phraser_memoriser_ou_reinjecter_placeholder($nom_structure, $nom_champ, $boucle_ou_champ);
	}
	else {
		$placeholder_suite = "_" . strtoupper(md5($nom_structure));
		$nom_champ = "{$placeholder_pattern}{$placeholder_suite}";
		$placeholder = "#{$nom_champ}";
		$nom_structure = '';
		//memoriser e champ a reinjecter
		phraser_memoriser_ou_reinjecter_placeholder($nom_structure, $nom_champ, $boucle_ou_champ);
	}
	return $placeholder;
}

/**
 * Analyseur syntaxique des squelettes HTML SPIP
 * On commence par analyser les boucles, les mémoriser, et les remplacer dans le texte par des placeholder
 * qui ne genent pas la suite de l'analyse des balises et autres
 *
 * @param array<string,Boucle> $boucles
 * @throws JsonException
 */
function public_phraser_html_dist(string $texte, string $id_parent, array &$boucles, array $descr, int $ligne_debut_texte = 1, ?string $boucle_placeholder = null): array {

	$all_res = [];
	// definir un placholder pour les boucles dont on est sur d'avoir aucune occurence dans le squelette
	if (is_null($boucle_placeholder)) {
		do {
			$boucle_placeholder = 'PLACEHOLDER_BOUCLE_' . strtoupper(substr(md5(uniqid()),0,8));
		} while (str_contains((string) $texte, $boucle_placeholder));
	}

	$ligne_debut_initial = $ligne_debut_texte;
	$pos_debut_texte = 0;
	while ($boucle = public_trouver_premiere_boucle($texte, $id_parent, $descr, $pos_debut_texte)) {
		$err_b = ''; // indiquera s'il y a eu une erreur
		$result = new Boucle();
		$result->id_parent = $id_parent;
		$result->descr = $descr;

		$pos_courante = $boucle['pos_boucle'];
		$pos_parent = $boucle['pos_parent'];
		$id_boucle_search = $id_boucle = $boucle['id_boucle'];

		$ligne_preaff = $ligne_avant = $ligne_milieu = $ligne_debut_texte + public_compte_ligne($texte, $pos_debut_texte, $pos_parent);

		// boucle anonyme ?
		if (!strlen((string) $id_boucle)) {
			$id_boucle = '_anon_L' . $ligne_milieu . '_' . substr(md5('anonyme:' . $id_parent . ':' . json_encode($boucle, JSON_THROW_ON_ERROR)), 0, 8);
		}

		$pos_debut_boucle = $pos_courante;

		$pos_milieu = $pos_parent;

		// Regarder si on a une partie conditionnelle avant <B_xxx>
		if ($boucle['pos_precond'] !== false) {
			$pos_debut_boucle = $boucle['pos_precond'];

			$pos_avant = $boucle['pos_precond_inside'];
			$result->avant = substr((string) $texte, $pos_avant, $pos_courante - $pos_avant);
			$ligne_avant = $ligne_debut_texte +  public_compte_ligne($texte, $pos_debut_texte, $pos_avant);
		}

		// Regarder si on a une partie inconditionnelle avant <BB_xxx>
		if ($boucle['pos_preaff'] !== false) {
			$end_preaff = $pos_debut_boucle;

			$pos_preaff = $boucle['pos_preaff_inside'];
			$result->preaff = substr((string) $texte, $pos_preaff, $end_preaff - $pos_preaff);
			$ligne_preaff = $ligne_debut_texte +  public_compte_ligne($texte, $pos_debut_texte, $pos_preaff);
		}

		$result->id_boucle = $id_boucle;

		if (
			!preg_match(SPEC_BOUCLE, (string) $texte, $match, 0, $pos_milieu)
			|| ($pos_match = strpos((string) $texte, (string) $match[0], $pos_milieu)) === false
			|| $pos_match > $pos_milieu
		) {
			$err_b = ['zbug_erreur_boucle_syntaxe', ['id' => $id_boucle]];
			erreur_squelette($err_b, $result);

			$ligne_debut_texte += public_compte_ligne($texte, $pos_debut_texte, $pos_courante + 1);
			$pos_debut_texte = $pos_courante + 1;
			continue;
		}

		$result->type_requete = $match[0];
		$pos_milieu += strlen($match[0]);
		$pos_courante = $pos_milieu; // on s'en sert pour compter les lignes plus precisemment

		$type = $match[1];
		$jointures = trim($match[2]);
		$table_optionnelle = ($match[3]);
		if ($jointures) {
			// on affecte pas ici les jointures explicites, mais dans la compilation
			// ou elles seront completees des jointures declarees
			$result->jointures_explicites = $jointures;
		}

		if ($table_optionnelle) {
			$result->table_optionnelle = true;
			$result->type_table_optionnelle = $type;
		}

		// 1ere passe sur les criteres, vu comme des arguments sans fct
		// Resultat mis dans result->param
		$pos_fin_criteres = $pos_milieu;
		phraser_args($texte, '/>', '', $all_res, $result, $pos_fin_criteres);

		// En 2e passe result->criteres contiendra un tableau
		// pour l'instant on met le source (chaine) :
		// si elle reste ici au final, c'est qu'elle contient une erreur
		$pos_courante = $pos_fin_criteres; // on s'en sert pour compter les lignes plus precisemment
		$result->criteres = substr((string) $texte, $pos_milieu, $pos_fin_criteres - $pos_milieu);
		$pos_milieu = $pos_fin_criteres;

		//
		// Recuperer la fin :
		//
		if ($texte[$pos_milieu] === '/') {
			// boucle autofermante : pas de partie conditionnelle apres
			$pos_courante += 2;
			$result->milieu = '';
		} else {
			$pos_milieu += 1;

			$fin_boucle = BALISE_FIN_BOUCLE . $id_boucle_search . '>';
			$pos_fin = strpos((string) $texte, $fin_boucle, $pos_milieu);
			if ($pos_fin === false) {
				$err_b = [
					'zbug_erreur_boucle_fermant',
					['id' => $id_boucle]
				];
				erreur_squelette($err_b, $result);
				$pos_courante += strlen($fin_boucle);
			}
			else {
				// verifier une eventuelle imbrication d'une boucle homonyme
				// (interdite, generera une erreur plus loin, mais permet de signaler la bonne erreur)
				$search_debut_boucle = BALISE_BOUCLE . $id_boucle_search . '(';
				$search_from = $pos_milieu;
				$nb_open = 1;
				$nb_close = 1;
				$maxiter = 0;
				do {
					while (
						$nb_close < $nb_open
						&& ($p = strpos((string) $texte, $fin_boucle, $pos_fin + 1))
					) {
						$nb_close++;
						$pos_fin = $p;
					}
					// si on a pas trouve assez de boucles fermantes, sortir de la, on a fait de notre mieux
					if ($nb_close < $nb_open) {
						break;
					}
					while (
						($p = strpos((string) $texte, $search_debut_boucle, $search_from))
						&& $p < $pos_fin
					) {
						$nb_open++;
						$search_from = $p + 1;
					}
				} while ($nb_close < $nb_open && $maxiter++ < 5);

				$pos_courante = $pos_fin + strlen($fin_boucle);
			}
			$result->milieu = substr((string) $texte, $pos_milieu, $pos_fin - $pos_milieu);
		}

		$ligne_suite = $ligne_apres = $ligne_debut_texte + public_compte_ligne($texte, $pos_debut_texte, $pos_courante);
		$boucle = public_trouver_fin_boucle($texte, $id_parent, $boucle, $pos_courante, $result);

		//
		// 1. Partie conditionnelle apres ?
		//
		if ($boucle['pos_postcond']) {
			$result->apres = substr((string) $texte, $pos_courante, $boucle['pos_postcond'] - $pos_courante);
			$ligne_suite += public_compte_ligne($texte, $pos_courante, $boucle['pos_postcond_inside']);
			$pos_courante = $boucle['pos_postcond_inside'] ;
		}


		//
		// 2. Partie alternative apres ?
		//
		$ligne_altern = $ligne_suite;
		if ($boucle['pos_altern']) {
			$result->altern = substr((string) $texte, $pos_courante, $boucle['pos_altern'] - $pos_courante);
			$ligne_suite += public_compte_ligne($texte, $pos_courante, $boucle['pos_altern_inside']);
			$pos_courante = $boucle['pos_altern_inside'];
		}

		//
		// 3. Partie footer non alternative ?
		//
		$ligne_postaff = $ligne_suite;
		if ($boucle['pos_postaff']) {
			$result->postaff = substr((string) $texte, $pos_courante, $boucle['pos_postaff'] - $pos_courante);
			$ligne_suite += public_compte_ligne($texte, $pos_courante, $boucle['pos_postaff_inside']);
			$pos_courante = $boucle['pos_postaff_inside'];
		}

		$result->ligne = $ligne_preaff;

		if ($p = strpos($type, ':')) {
			$result->sql_serveur = substr($type, 0, $p);
			$type = substr($type, $p + 1);
		}
		$soustype = strtolower($type);

		if (!isset($GLOBALS['table_des_tables'][$soustype])) {
			$soustype = $type;
		}

		$result->type_requete = $soustype;
		// Lancer la 2e passe sur les criteres si la 1ere etait bonne
		if (!is_array($result->param)) {
			$err_b = true;
		} else {
			phraser_criteres($result->param, $result);
			if (strncasecmp($soustype, TYPE_RECURSIF, strlen(TYPE_RECURSIF)) == 0) {
				$result->type_requete = TYPE_RECURSIF;
				$args = $result->param;
				array_unshift(
					$args,
					substr($type, strlen(TYPE_RECURSIF))
				);
				$result->param = $args;
			}
		}

		$descr['id_mere_contexte'] = $id_boucle;
		$result->milieu = public_phraser_html_dist($result->milieu, $id_boucle, $boucles, $descr, $ligne_milieu, $boucle_placeholder);
		// reserver la place dans la pile des boucles pour compiler ensuite dans le bon ordre
		// ie les boucles qui apparaissent dans les partie conditionnelles doivent etre compilees apres cette boucle
		// si il y a deja une boucle de ce nom, cela declenchera une erreur ensuite
		if (empty($boucles[$id_boucle])) {
			$boucles[$id_boucle] = null;
		}
		$result->preaff = public_phraser_html_dist($result->preaff, $id_parent, $boucles, $descr, $ligne_preaff, $boucle_placeholder);
		$result->avant = public_phraser_html_dist($result->avant, $id_parent, $boucles, $descr, $ligne_avant, $boucle_placeholder);
		$result->apres = public_phraser_html_dist($result->apres, $id_parent, $boucles, $descr, $ligne_apres, $boucle_placeholder);
		$result->altern = public_phraser_html_dist($result->altern, $id_parent, $boucles, $descr, $ligne_altern, $boucle_placeholder);
		$result->postaff = public_phraser_html_dist($result->postaff, $id_parent, $boucles, $descr, $ligne_postaff, $boucle_placeholder);

		// Prevenir le generateur de code que le squelette est faux
		if ($err_b) {
			$result->type_requete = false;
		}

		// Verifier qu'il n'y a pas double definition
		// apres analyse des sous-parties (pas avant).
		if (!empty($boucles[$id_boucle])) {
			if ($boucles[$id_boucle]->type_requete !== false) {
				$err_b_d = [
					'zbug_erreur_boucle_double',
					['id' => $id_boucle]
				];
				erreur_squelette($err_b_d, $result);
				// Prevenir le generateur de code que le squelette est faux
				$boucles[$id_boucle]->type_requete = false;
			}
		} else {
			$boucles[$id_boucle] = $result;
		}

		// remplacer la boucle par un placeholder qui compte le meme nombre de lignes
		$placeholder = public_generer_placeholder($id_boucle, $boucles[$id_boucle], $boucle_placeholder, $ligne_suite - $ligne_debut_texte, true);
		$longueur_boucle = $pos_courante - $boucle['debut_boucle'];
		$texte = substr_replace((string) $texte, $placeholder, $boucle['debut_boucle'], $longueur_boucle);
		$pos_courante = $pos_courante - $longueur_boucle + strlen($placeholder);

		// phraser la partie avant le debut de la boucle
		#$all_res = phraser_champs_etendus(substr($texte, $pos_debut_texte, $boucle['debut_boucle'] - $pos_debut_texte), $ligne_debut_texte, $all_res);
		#$all_res[] = &$boucles[$id_boucle];

		$ligne_debut_texte = $ligne_suite;
		$pos_debut_texte = $pos_courante;
	}

	return phraser_champs_etendus($texte, $ligne_debut_initial, $all_res);
}
