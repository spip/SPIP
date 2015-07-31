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

// Decompilation de l'arbre de syntaxe abstraite d'un squelette SPIP

function decompiler_boucle($struct, $fmt, $prof=0, $next='')
{
	$nom = $struct->id_boucle;
	$avant = public_decompiler($struct->avant, $fmt, $prof);
	$apres = public_decompiler($struct->apres, $fmt, $prof);
	$altern = public_decompiler($struct->altern, $fmt, $prof);
	$milieu = public_decompiler($struct->milieu, $fmt, $prof);

	$type = $struct->sql_serveur ? "$struct->sql_serveur:" : '';
	$type .= strtoupper($struct->type_requete ? $struct->type_requete :
			    $struct->table_optionnelle);

	if ($struct->jointures_explicites)
	  $type .= " " . $struct->jointures_explicites;
	if ($struct->table_optionnelle)
	  $type .= "?";
	// Revoir le cas de la boucle recursive
	$crit = $struct->param;
	if ($crit AND !is_array($crit[0])) {
		$type = strtolower($type) . array_shift($crit);
	}
	$crit = decompiler_criteres($crit, $struct->criteres, $fmt, $prof) ;

	$f = 'format_boucle_' . ($fmt ? $fmt : _EXTENSION_SQUELETTES);
	return $f($avant, $nom, $type, $crit, $milieu, $apres, $altern, $prof);
}
	
function decompiler_include($struct, $fmt, $prof=0, $next='')
{
	$res = array();
	$fond = '';
	foreach($struct->param as $couple) {
		array_shift($couple);
		foreach($couple as $v) {
			$a = public_decompiler($v, $fmt, $prof);
			if (preg_match(',^fond=(.*)$,sS', $a, $r))
			    $fond = $r[1];
			else $res[]= $a;
		}
	}
	$f = 'format_include_' . ($fmt ? $fmt : _EXTENSION_SQUELETTES);
	return $f($struct->texte, $fond, $res, $prof);
}

function decompiler_texte($struct, $fmt, $prof=0, $next='')
{
	return $struct->texte;
}

function decompiler_polyglotte($struct, $fmt, $prof=0, $next='')
{
	$f = 'format_polyglotte_' . ($fmt ? $fmt : _EXTENSION_SQUELETTES);
	return $f($struct->traductions, $prof);
}

function decompiler_idiome($struct, $fmt, $prof=0, $next='')
{
	$module = ($struct->module == 'public/spip/ecrire')? ''
	  : $struct->module;

	$args = array();
	foreach ($struct->arg as $k => $v) {
		if ($k) $args[$k]= public_decompiler($v, $fmt, $prof);
	}

	$filtres =  decompiler_liste($struct->param, $fmt, $prof);

	$f = 'format_idiome_' . ($fmt ? $fmt : _EXTENSION_SQUELETTES);
	return $f($struct->nom_champ, $module, $args, $filtres, $next, $prof);
}

function decompiler_champ($struct, $fmt, $prof=0, $next='')
{
	$avant = public_decompiler($struct->avant, $fmt, $prof);
	$apres = public_decompiler($struct->apres, $fmt, $prof);
	$args = $filtres = '';
	if ($p = $struct->param) {
		if ($p[0][0]==='')
		  $args = decompiler_liste(array(array_shift($p)), $fmt, $prof);
		$filtres = decompiler_liste($p, $fmt, $prof);
	}

	$f = 'format_champ_' . ($fmt ? $fmt : _EXTENSION_SQUELETTES);
	return $f($struct->nom_champ, $struct->nom_boucle, $struct->etoile, $avant, $apres, $args, $filtres, $next, $prof);
}

function decompiler_liste($sources, $fmt, $prof=0) {
	if (!is_array($sources)) return '';
	$f = 'format_liste_' . ($fmt ? $fmt : _EXTENSION_SQUELETTES);
	$res = '';
	foreach($sources as $arg) {
		if (!is_array($arg))  {
		  continue; // ne devrait pas arriver.
		} else {$r = array_shift($arg);}
		$args = array();
		foreach($arg as $v) {
		  // cas des arguments entoures de ' ou "
			if ((count($v) == 1) 
			AND $v[0]->type=='texte'
			AND (strlen($v[0]->apres) == 1)
			AND $v[0]->apres == $v[0]->avant)
			  $args[]= $v[0]->avant . $v[0]->texte . $v[0]->apres;
			else $args[]= public_decompiler($v, $fmt, 0-$prof);
		}
		if (($r!=='') OR $args) $res .= $f($r, $args, $prof);
	}
	return $res;
}

// Decompilation des criteres: on triche et on deroge:
// - le phraseur fournit un bout du source en plus de la compil
// - le champ apres signale le critere {"separateur"} ou {'separateur'}
// - les champs sont implicitement etendus (crochets implicites mais interdits)
function decompiler_criteres($sources, $comp, $fmt='', $prof=0) {
	$res = '';
	$f = 'format_critere_' . ($fmt ? $fmt : _EXTENSION_SQUELETTES);

	foreach($sources as $crit) {
		if (!is_array($crit)) continue; // boucle recursive
		array_shift($crit);
		$args = array();
		foreach($crit as $v) {
		  if ((count($v) == 1) 
		      AND $v[0]->type=='texte'
		      AND $v[0]->apres)
		    $args[]= ( $v[0]->apres . $v[0]->texte . $v[0]->apres);
		  else {
		    $res2 = '';
		    foreach($v as $k => $p) {
		      if (!isset($p->type)) continue; #??????
		      $d = 'decompiler_' . $p->type;
		      $r = $d($p, $fmt, (0-$prof), @$v[$k+1]);
		      if ($p->type == 'champ' AND $r[0]=='[') {
			$r = substr($r,1,-1);
			if (preg_match(',^[(](#[^|]*)[)]$,sS', $r))
			  $r = substr($r,1,-1);
		      }
		      $res2 .= $r;
		    }
		    $args[]= $res2;
		  }
		}
		$res .= $f($args, $prof);
	}
	return $res;
}

function public_decompiler($liste, $fmt='', $prof=0, $suite=' ')
{
	include_spip('public/format_' . ($fmt ? $fmt : _EXTENSION_SQUELETTES));
	$contenu = '';
	$prof2 = ($prof < 0) ? ($prof-1) : ($prof+1);
	if (is_array($liste)) foreach($liste as $k => $p) {
	    if (!isset($p->type)) continue; #??????
	    $d = 'decompiler_' . $p->type;
	    $next = isset($liste[$k+1]) ? $liste[$k+1] : false;
	  // Forcer le champ etendu si son source (pas les reecritures)
	  // contenait des args et s'il est suivi d'espaces, 
	  // le champ simple les eliminant est un bug helas perenne.

	    if ($next 
		AND ($next->type == 'texte')
		AND $p->type == 'champ' 
		AND !$p->apres 
		AND !$p->avant
		AND $p->fonctions) {
	      $n = strlen($next->texte) - strlen(ltrim($next->texte));
	      if ($n)  {
		$p->apres = substr($next->texte, 0, $n);
		$next->texte = substr($next->texte, $n);
	      }
	      }
	    // preciser le suivant pour permettre le raccourci [(#X)] ==> #X
	    $contenu .= $d($p, $fmt, $prof2, $next);

	}

	return $contenu;
}
?>
