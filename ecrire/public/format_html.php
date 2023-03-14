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

function format_boucle_html($preaff, $avant, $nom, $type, $crit, $corps, $apres, $altern, $postaff, $prof) {
	$preaff = $preaff ? "<BB$nom>$preaff" : '';
	$avant = $avant ? "<B$nom>$avant" : '';
	$apres = $apres ? "$apres</B$nom>" : '';
	$altern = $altern ? "$altern<//B$nom>" : '';
	$postaff = $postaff ? "$postaff</BB$nom>" : '';
	$corps = $corps ? ">$corps</BOUCLE$nom>" : ' />';

	return "$preaff$avant<BOUCLE$nom($type)$crit$corps$apres$altern$postaff";
}

function format_inclure_html($file, $args, $prof) {
	if (!str_contains($file, '#')) {
		$t = $file ? ('(' . $file . ')') : '';
	} else {
		$t = '{fond=' . $file . '}';
	}
	$args = $args ? '{' . implode(', ', $args) . '}' : ('');

	return ('<INCLURE' . $t . $args . '>');
}

function format_polyglotte_html($args, $prof) {
	$contenu = [];
	foreach ($args as $l => $t) {
		$contenu[] = ($l ? "[$l]" : '') . $t;
	}

	return ('<multi>' . implode(' ', $contenu) . '</multi>');
}

function format_idiome_html($nom, $module, $args, $filtres, $prof) {
	foreach ($args as $k => $v) {
		$args[$k] = "$k=$v";
	}
	$args = ($args ? '{' . implode(',', $args) . '}' : (''));

	return ('<:' . ($module ? "$module:" : '') . $nom . $args . $filtres . ':>');
}

function format_champ_html($nom, $boucle, $etoile, $avant, $apres, $args, $filtres, $prof) {
	$nom = '#'
		. ($boucle ? ($boucle . ':') : '')
		. $nom
		. $etoile
		. $args
		. $filtres;

	// Determiner si c'est un champ etendu,

	$s = ($avant || $apres || $filtres || str_contains($args, '(#'));

	return ($s ? "[$avant($nom)$apres]" : $nom);
}

function format_critere_html($critere) {
	foreach ($critere as $k => $crit) {
		$crit_s = '';
		foreach ($crit as $operande) {
			[$type, $valeur] = $operande;
			if ($type == 'champ' && $valeur[0] == '[') {
				$valeur = substr($valeur, 1, -1);
				if (preg_match(',^[(](#[^|]*)[)]$,sS', $valeur)) {
					$valeur = substr($valeur, 1, -1);
				}
			}
			$crit_s .= $valeur;
		}
		$critere[$k] = $crit_s;
	}

	return ($critere ? '{' . implode(',', $critere) . '}' : (''));
}

function format_liste_html($fonc, $args, $prof) {
	return ((($fonc !== '') ? "|$fonc" : $fonc)
		. ($args ? '{' . implode(',', $args) . '}' : ('')));
}

// Concatenation sans separateur: verifier qu'on ne cree pas de faux lexemes
function format_suite_html($args) {
	$argsCount = count($args);
	for ($i = 0; $i < (is_countable($args) ? $argsCount : 0) - 1; $i++) {
		[$texte, $type] = $args[$i];
		[$texte2, $type2] = $args[$i + 1];
		if (!$texte || !$texte2) {
			continue;
		}
		$c1 = substr($texte, -1);
		if ($type2 !== 'texte') {
			// si un texte se termine par ( et est suivi d'un champ
			// ou assimiles, forcer la notation pleine
			if ($c1 == '(' && str_starts_with($texte2, '#')) {
				$args[$i + 1][0] = '[(' . $texte2 . ')]';
			}
		} else {
			if ($type == 'texte') {
				continue;
			}
			// si un champ ou assimiles est suivi d'un texte
			// et si celui-ci commence par un caractere de champ
			// forcer la notation pleine
			if (
				$c1 == '}' && str_starts_with(ltrim($texte2), '|')
				|| preg_match('/[\w\d_*]/', $c1) && preg_match('/^[\w\d_*{|]/', $texte2)
			) {
				$args[$i][0] = '[(' . $texte . ')]';
			}
		}
	}

	return implode('', array_map(fn($arg) => reset($arg), $args));
}

function format_texte_html($texte) {
	return $texte;
}
