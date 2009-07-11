<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function format_boucle_html($avant, $nom, $type, $crit, $corps, $apres, $altern, $prof)
{
	$avant = $avant ? "<B$nom>avant" : "";
	$apres = $apres ? "$apres</B$nom>" : "";
	$altern = $altern ? "$altern<//B$nom>" : "";
	if (!$corps) $corps = " />"; else $corps = ">$corps</BOUCLE$nom>";
	return "$avant<BOUCLE$nom($type)$crit$corps$apres$altern";
}

function format_include_html($file, $fond, $args, $prof)
{
 	$t = $file ? ("(" . $file . ")") : "" ;
	if ($fond) array_unshift($args, "fond=" . $fond);
	if ($args) $args = "{" . join(", ",$args) . "}";
	return "<INCLURE" . $t . $args  . ">";
}

function format_polyglotte_html($args, $prof)
{
	return "<multi>" . join(" ", $args) . "</multi>";
}

function format_idiome_html($nom, $module, $args, $s, $prof)
{
	return "<:"  . ($module ? "$module:" : "") . $nom . $args . ":>";
}

function format_champ_html($nom, $boucle, $etoile, $avant, $apres, $args, $filtres, $next, $prof)
{
	$nom = "#"
	. ($boucle ? ($boucle . ":") : "")
	. $nom
	. $etoile
	. $args
	. $filtres;

	// Determiner si c'est un champ etendu, 
	// notamment pour éviter que le lexeme suivant s'agrege au champ
	// si pas d'etoile terminale, pas d'arg et suivi d'une ambiguite

	$s = ($avant OR $apres OR $filtres
	      OR ($prof < 0) 
	      OR (strpos($args, '(#') !==false)
	      OR (!$args
		    AND !$etoile
		    AND	$next
		    AND ($next->type == 'texte')
		  AND preg_match(',^[\w\d|{*],', $next->texte)));

	return $s ? "[$avant($nom)$apres]" : $nom;
}

function format_liste_html($fonc, $args, $prof)
{
  return ($fonc ? "|$fonc" : $fonc)
	. (!$args ? "" : ("{" . join(",", $args) . "}"));
}

function format_critere_html($args, $prof)
{
  return (!$args ? "" : ("{" . join(",", $args) . "}"));
}

