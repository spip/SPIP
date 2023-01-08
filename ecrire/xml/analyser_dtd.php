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

include_spip('xml/interfaces');

function charger_dtd($grammaire, $avail, $rotlvl) {
	$r = null;
	static $dtd = []; # cache bien utile pour le validateur en boucle

	if (isset($dtd[$grammaire])) {
		return $dtd[$grammaire];
	}

	if ($avail == 'SYSTEM') {
		$grammaire = find_in_path($grammaire);
	}

	$file = _DIR_CACHE_XML . preg_replace('/[^\w.]/', '_', $rotlvl) . '.gz';

	if (lire_fichier($file, $r)) {
		if (!$grammaire) {
			return [];
		}
		if (($avail == 'SYSTEM') and filemtime($file) < filemtime($grammaire)) {
			$r = false;
		}
	}

	if ($r) {
		$dtc = unserialize($r);
	} else {
		spip_timer('dtd');
		$dtc = new DTC();
		// L'analyseur retourne un booleen de reussite et modifie $dtc.
		// Retourner vide en cas d'echec
		if (!analyser_dtd($grammaire, $avail, $dtc)) {
			$dtc = [];
		} else {
			// tri final pour presenter les suggestions de corrections
			foreach ($dtc->peres as $k => $v) {
				asort($v);
				$dtc->peres[$k] = $v;
			}

			spip_log("Analyser DTD $avail $grammaire (" . spip_timer('dtd') . ') ' . (is_countable($dtc->macros) ? count($dtc->macros) : 0) . ' macros, ' . (is_countable($dtc->elements) ? count($dtc->elements) : 0) . ' elements, ' . (is_countable($dtc->attributs) ? count($dtc->attributs) : 0) . " listes d'attributs, " . (is_countable($dtc->entites) ? count($dtc->entites) : 0) . ' entites');
			#	$r = $dtc->regles; ksort($r);foreach($r as $l => $v) {$t=array_keys($dtc->attributs[$l]);echo "<b>$l</b> '$v' ", count($t), " attributs: ", join (', ',$t);$t=$dtc->peres[$l];echo "<br />",count($t), " peres: ", @join (', ',$t), "<br />\n";}exit;
			ecrire_fichier($file, serialize($dtc), true);
		}
	}
	$dtd[$grammaire] = $dtc;

	return $dtc;
}

// Compiler une regle de production en une Regexp qu'on appliquera sur la
// suite des noms de balises separes par des espaces. Du coup:
// supprimer #PCDATA etc, ca ne sert pas pour le controle des balises;
// supprimer les virgules (les sequences sont implicites dans une Regexp)
// conserver | + * ? ( ) qui ont la meme signification en DTD et en Regexp;
// faire suivre chaque nom d'un espace (et supprimer les autres) ...
// et parentheser le tout pour que  | + * ? s'applique dessus.

function compilerRegle($val) {
	$x = str_replace(
		'()',
		'',
		preg_replace(
			'/\s*,\s*/',
			'',
			preg_replace(
				'/(\w+)\s*/',
				'(?:\1 )',
				preg_replace(
					'/\s*\)/',
					')',
					preg_replace(
						'/\s*([(+*|?])\s*/',
						'\1',
						preg_replace('/\s*#\w+\s*[,|]?\s*/', '', $val)
					)
				)
			)
		)
	);

	return $x;
}


function analyser_dtd($loc, $avail, &$dtc) {
	// creer le repertoire de cache si ce n'est fait
	// (utile aussi pour le resultat de la compil)
	$file = sous_repertoire(_DIR_CACHE_XML);
	// si DTD locale, ignorer ce repertoire pour le moment
	if ($avail == 'SYSTEM') {
		$file = $loc;
		if (_DIR_RACINE and strncmp($file, _DIR_RACINE, strlen(_DIR_RACINE)) == 0) {
			$file = substr($file, strlen(_DIR_RACINE));
		}
		$file = find_in_path($file);
	} else {
		$file .= preg_replace('/[^\w.]/', '_', $loc);
	}

	$dtd = '';
	if (@is_readable($file)) {
		lire_fichier($file, $dtd);
	} else {
		if ($avail == 'PUBLIC') {
			include_spip('inc/distant');
			$dtd = recuperer_url($loc);
			$dtd = trim($dtd['page'] ?? '');
			if ($dtd) {
				ecrire_fichier($file, $dtd, true);
			}
		}
	}

	$dtd = ltrim($dtd);
	if (!$dtd) {
		spip_log("DTD '$loc' ($file) inaccessible");

		return false;
	} else {
		spip_log("analyse de la DTD $loc ");
	}

	while ($dtd) {
		if ($dtd[0] != '<') {
			$r = analyser_dtd_lexeme($dtd, $dtc, $loc);
		} elseif ($dtd[1] != '!') {
			$r = analyser_dtd_pi($dtd, $dtc, $loc);
		} elseif ($dtd[2] == '[') {
			$r = analyser_dtd_data($dtd, $dtc, $loc);
		} else {
			switch ($dtd[3]) {
				case '%':
					$r = analyser_dtd_data($dtd, $dtc, $loc);
					break;
				case 'T':
					$r = analyser_dtd_attlist($dtd, $dtc, $loc);
					break;
				case 'L':
					$r = analyser_dtd_element($dtd, $dtc, $loc);
					break;
				case 'N':
					$r = analyser_dtd_entity($dtd, $dtc, $loc);
					break;
				case 'O':
					$r = analyser_dtd_notation($dtd, $dtc, $loc);
					break;
				case '-':
					$r = analyser_dtd_comment($dtd, $dtc, $loc);
					break;
				default:
					$r = -1;
			}
		}
		if (!is_string($r)) {
			spip_log("erreur $r dans la DTD  " . substr($dtd, 0, 80) . '.....');

			return false;
		}
		$dtd = $r;
	}

	return true;
}

function analyser_dtd_comment($dtd, &$dtc, $grammaire) {
	// ejecter les commentaires, surtout quand ils contiennent du code.
	// Option /s car sur plusieurs lignes parfois

	if (!preg_match('/^<!--.*?-->\s*(.*)$/s', $dtd, $m)) {
		return -6;
	}

	return $m[1];
}

function analyser_dtd_pi($dtd, &$dtc, $grammaire) {
	if (!preg_match('/^<\?.*?>\s*(.*)$/s', $dtd, $m)) {
		return -10;
	}

	return $m[1];
}

function analyser_dtd_lexeme($dtd, &$dtc, $grammaire) {

	if (!preg_match(_REGEXP_ENTITY_DEF, $dtd, $m)) {
		return -9;
	}

	[, $s] = $m;
	$n = $dtc->macros[$s];

	if (is_array($n)) {
		// en cas d'inclusion, l'espace de nom est le meme
		// mais gaffe aux DTD dont l'URL est relative a l'engloblante
		if (
			($n[0] == 'PUBLIC')
			and !tester_url_absolue($n[1])
		) {
			$n[1] = substr($grammaire, 0, strrpos($grammaire, '/') + 1) . $n[1];
		}
		analyser_dtd($n[1], $n[0], $dtc);
	}

	return ltrim(substr($dtd, strlen($m[0])));
}

// il faudrait gerer plus proprement les niveaux d'inclusion:
// ca ne depasse pas 3 ici.

function analyser_dtd_data($dtd, &$dtc, $grammaire) {

	if (!preg_match(_REGEXP_INCLUDE_USE, $dtd, $m)) {
		return -11;
	}
	if (
		!preg_match(
			'/^((\s*<!(\[\s*%\s*[^;]*;\s*\[([^]<]*<[^>]*>)*[^]<]*\]\]>)|([^]>]*>))*[^]<]*)\]\]>\s*/s',
			$m[2],
			$r
		)
	) {
		return -12;
	}

	if ($dtc->macros[$m[1]] == 'INCLUDE') {
		$retour = $r[1] . substr($m[2], strlen($r[0]));
	} else {
		$retour = substr($m[2], strlen($r[0]));
	}

	return $retour;
}

function analyser_dtd_notation($dtd, &$dtc, $grammaire) {
	if (!preg_match('/^<!NOTATION.*?>\s*(.*)$/s', $dtd, $m)) {
		return -8;
	}
	spip_log('analyser_dtd_notation a ecrire');

	return $m[1];
}

function analyser_dtd_entity($dtd, &$dtc, $grammaire) {
	if (!preg_match(_REGEXP_ENTITY_DECL, $dtd, $m)) {
		return -2;
	}

	[$t, $term, $nom, $type, $k1, $k2, $k3, $k4, $k5, $k6, $c, $q, $alt, $dtd] = $m;

	if (isset($dtc->macros[$nom]) and $dtc->macros[$nom]) {
		return $dtd;
	}
	if (isset($dtc->entites[$nom])) {
		spip_log("redefinition de l'entite $nom");
	}
	if ($k6) {
		return $k6 . $dtd;
	} // cas du synonyme complet
	$val = expanserEntite(($k2 ? $k3 : ($k4 ? $k5 : $k6)), $dtc->macros);

	// cas particulier double evaluation: 'PUBLIC "..." "...."'
	if (preg_match('/(PUBLIC|SYSTEM)\s+"([^"]*)"\s*("([^"]*)")?\s*$/s', $val, $r)) {
		[$t, $type, $val, $q, $alt] = $r;
	}

	if (!$term) {
		$dtc->entites[$nom] = $val;
	} elseif (!$type) {
		$dtc->macros[$nom] = $val;
	} else {
		if (($type == 'SYSTEM') and !$alt) {
			$alt = $val;
		}
		if (!$alt) {
			$dtc->macros[$nom] = $val;
		} else {
			if (
				($type == 'PUBLIC')
				and (strpos($alt, '/') === false)
			) {
				$alt = preg_replace(',/[^/]+$,', '/', $grammaire)
					. $alt;
			}
			$dtc->macros[$nom] = [$type, $alt];
		}
	}

	return $dtd;
}

// Dresser le tableau des filles potentielles de l'element
// pour traquer tres vite les illegitimes.
// Si la regle a au moins une sequence (i.e. une virgule)
// ou n'est pas une itération (i.e. se termine par * ou +)
// en faire une RegExp qu'on appliquera aux balises rencontrees.
// Sinon, conserver seulement le type de l'iteration car la traque
// aura fait l'essentiel du controle sans memorisation des balises.
// Fin du controle en finElement

function analyser_dtd_element($dtd, &$dtc, $grammaire) {
	if (!preg_match('/^<!ELEMENT\s+([^>\s]+)([^>]*)>\s*(.*)$/s', $dtd, $m)) {
		return -3;
	}

	[, $nom, $contenu, $dtd] = $m;
	$nom = expanserEntite($nom, $dtc->macros);

	if (isset($dtc->elements[$nom])) {
		spip_log("redefinition de l'element $nom dans la DTD");

		return -4;
	}
	$filles = [];
	$contenu = expanserEntite($contenu, $dtc->macros);
	$val = $contenu ? compilerRegle($contenu) : '(?:EMPTY )';
	if ($val == '(?:EMPTY )') {
		$dtc->regles[$nom] = 'EMPTY';
	} elseif ($val == '(?:ANY )') {
		$dtc->regles[$nom] = 'ANY';
	} else {
		$last = substr($val, -1);
		if (
			preg_match('/ \w/', $val)
			or (!empty($last) and !str_contains('*+?', $last))
		) {
			$dtc->regles[$nom] = "/^$val$/";
		} else {
			$dtc->regles[$nom] = $last;
		}
		$filles = array_values(preg_split('/\W+/', $val, -1, PREG_SPLIT_NO_EMPTY));

		foreach ($filles as $k) {
			if (!isset($dtc->peres[$k])) {
				$dtc->peres[$k] = [];
			}
			if (!in_array($nom, $dtc->peres[$k])) {
				$dtc->peres[$k][] = $nom;
			}
		}
	}
	$dtc->pcdata[$nom] = (strpos($contenu, '#PCDATA') === false);
	$dtc->elements[$nom] = $filles;

	return $dtd;
}


function analyser_dtd_attlist($dtd, &$dtc, $grammaire) {
	if (!preg_match('/^<!ATTLIST\s+(\S+)\s+([^>]*)>\s*(.*)/s', $dtd, $m)) {
		return -5;
	}

	[, $nom, $val, $dtd] = $m;
	$nom = expanserEntite($nom, $dtc->macros);
	$val = expanserEntite($val, $dtc->macros);
	if (!isset($dtc->attributs[$nom])) {
		$dtc->attributs[$nom] = [];
	}

	if (preg_match_all("/\s*(\S+)\s+(([(][^)]*[)])|(\S+))\s+([^\s']*)(\s*'[^']*')?/", $val, $r2, PREG_SET_ORDER)) {
		foreach ($r2 as $m2) {
			$v = preg_match('/^\w+$/', $m2[2]) ? $m2[2]
				: ('/^' . preg_replace('/\s+/', '', $m2[2]) . '$/');
			$m21 = expanserEntite($m2[1], $dtc->macros);
			$m25 = expanserEntite($m2[5], $dtc->macros);
			$dtc->attributs[$nom][$m21] = [$v, $m25];
		}
	}

	return $dtd;
}


/**
 * Remplace dans la chaîne `$val` les sous-chaines de forme `%NOM;`
 * par leur definition dans le tableau `$macros`
 *
 * Si le premier argument n'est pas une chaîne,
 * retourne les statistiques (pour debug de DTD, inutilise en mode normal)
 *
 * @param string $val
 * @param array $macros
 * @return string|array
 **/
function expanserEntite($val, $macros = []) {
	static $vu = [];
	if (!is_string($val)) {
		return $vu;
	}

	if (preg_match_all(_REGEXP_ENTITY_USE, $val, $r, PREG_SET_ORDER)) {
		foreach ($r as $m) {
			$ent = $m[1];
			// il peut valoir ""
			if (!isset($macros[$ent])) {
				spip_log("Entite $ent inconnu");
			} else {
				if (!isset($vu[$ent])) {
					$vu[$ent] = 0;
				}
				++$vu[$ent];
				$val = str_replace($m[0], $macros[$ent], $val);
			}
		}
	}

	return trim(preg_replace('/\s+/', ' ', $val));
}
