<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

function format_boucle_html ($avant, $nom, $type, $crit, $corps, $apres, $altern, $prof)
{
	$avant = $avant ? "<B$nom>$avant" : "";
	$apres = $apres ? "$apres</B$nom>" : "";
	$altern = $altern ? "$altern<//B$nom>" : "";
	if (!$corps) $corps = " />"; else $corps = ">$corps</BOUCLE$nom>";
	return "$avant<BOUCLE$nom($type)$crit$corps$apres$altern";
}

function format_inclure_html ($file, $args, $prof)
{
	if (strpos($file, '#')===false)
	 	$t = $file ? ("(" . $file . ")") : "" ;
	else {
		$t = "{fond=" . $file . '}';
	}
	$args = !$args ? '' : ("{" . join(", ",$args) . "}");
	return ("<INCLURE" . $t . $args  . ">");
}

function format_polyglotte_html ($args, $prof)
{
	$contenu = array(); 
	foreach($args as $l=>$t)
		$contenu[]= ($l ? "[$l]" : '') . $t;
	return ("<multi>" . join(" ", $contenu) . "</multi>");
}

function format_idiome_html ($nom, $module, $args, $filtres, $prof)
{
	foreach ($args as $k => $v) $args[$k] = "$k=$v";
	$args = (!$args ? '' : ('{' . join(',', $args) . '}'));
	return ("<:" . ($module ? "$module:" : "") . $nom . $args . $filtres . ":>");
}

function format_champ_html ($nom, $boucle, $etoile, $avant, $apres, $args, $filtres, $prof)
{
	$nom = "#"
	. ($boucle ? ($boucle . ":") : "")
	. $nom
	. $etoile
	. $args
	. $filtres;

	// Determiner si c'est un champ etendu, 

	$s = ($avant OR $apres OR $filtres
	      OR (strpos($args, '(#') !==false));

	return ($s ? "[$avant($nom)$apres]" : $nom);
}

function format_critere_html ($critere)
{
	foreach ($critere as $k => $crit) {
		$crit_s = '';
		foreach ($crit as $operande) {
			list($type, $valeur) = $operande;
			if ($type == 'champ' AND $valeur[0]=='[') {
			  $valeur = substr($valeur,1,-1);
			  if (preg_match(',^[(](#[^|]*)[)]$,sS', $valeur))
				$valeur = substr($valeur,1,-1);
			}
			$crit_s .= $valeur;
		}
		$critere[$k] = $crit_s;
	}
	return (!$critere ? "" : ("{" . join(",", $critere) . "}"));
}

function format_liste_html ($fonc, $args, $prof)
{
	return ((($fonc!=='') ? "|$fonc" : $fonc)
	. (!$args ? "" : ("{" . join(",", $args) . "}")));
}

// Concatenation sans separateur: verifier qu'on ne cree pas de faux lexemes
function format_suite_html ($args)
{
	for($i=0; $i < count($args)-1; $i++) {
		list($texte, $type) = $args[$i];
		list($texte2, $type2) = $args[$i+1];
		if (!$texte OR !$texte2) continue; 
		$c1 = substr($texte,-1);
		if ($type2 !== 'texte') {
		  // si un texte se termine par ( et est suivi d'un champ
		  // ou assimiles, forcer la notation pleine
			if ($c1 == '(' AND substr($texte2,0,1) == '#')
				$args[$i+1][0] = '[(' . $texte2 . ')]';
		} else {
			if ($type == 'texte') continue;
			// si un champ ou assimiles est suivi d'un texte
			// et si celui-ci commence par un caractere de champ
			// forcer la notation pleine
			if (($c1 == '}' AND substr(ltrim($texte2),0,1) == '|')
			OR (preg_match('/[\w\d_*]/', $c1) AND preg_match('/^[\w\d_*{|]/', $texte2)))
				$args[$i][0] = '[(' . $texte . ')]';
		}
	}
	return join("", array_map('array_shift', $args));
}

function format_texte_html ($texte)
{
	return $texte;
}

?>
