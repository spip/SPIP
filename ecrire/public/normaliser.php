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

// Les fonctions de ce fichier sont appelees en certains points 
// de l'analyseur syntaxique afin de normaliser de vieilles syntaxes,
// pour fournir au compilateur un arbre de syntaxe abstraite homogene

// Cas des pseudos filtres |fichier et |lien qui  donnent le chemin du fichier
// et son URL, remplaces par ** et *: LOGO_XXX** et LOGO_XXX*
// Il y a aussi le futur attribut align et l'ecriture #LOGO|#URL
// qui passent en arguments de la balise: #LOGO{left,#URL...}
// -> http://www.spip.net/fr_article901.html

function phraser_vieux_logos(&$p)
{
	if ($p->param[0][0])
		$args = array('');
	else {
		$args = array_shift($p->param);
	}

	foreach($p->param as $couple) {
		$nom = trim($couple[0]);
		if ($nom == '')  {array_shift($p->param); break;}
		$r = phraser_logo_faux_filtres($nom);
		if ($r === 0) {
			$c = new Texte;
			$c->texte = $nom;
			$args[] = array($c);
			array_shift($p->param);
			spip_log("filtre de logo obsolete $nom", 'vieilles_defs');
		} elseif ($r === 2) {
				$p->etoile = '**';
				array_shift($p->param);
				spip_log("filtre de logo obsolete $nom", 'vieilles_defs');
		} elseif ($r === 1) {
				array_shift($p->param);
				$p->etoile = '*';
				spip_log("filtre de logo obsolete $nom", 'vieilles_defs');

		} elseif (preg_match("/^".NOM_DE_CHAMP.'(.*)$/sS', $nom, $m)) {
				$champ = new Champ();
				$champ->nom_boucle = $m[2];
				$champ->nom_champ = $m[3];
				$champ->etoile = $m[5];
				$champ = array($champ);
				if ($m[6]) {
				  $r = new Texte;
				  $r->texte = $m[6];
				  $champ[]= $r;
				}
				$args[]= $champ;
				array_shift($p->param);
				spip_log("filtre de logo obsolete $nom", 'vieilles_defs');

		} // le cas else est la seule incompatibilite

	}
	array_unshift($p->param, $args);
}


function phraser_logo_faux_filtres($nom)
{
	switch($nom) {
	case 'top':
	case 'left':
	case 'right':
	case 'center':
	case 'bottom':  return 0;
	case 'lien':    return 1;
	case 'fichier': return 2;
	default: return $nom;
	}
}


// La balise embed_document est a present le modele emb

function phraser_vieux_emb(&$p)
{
	if (!is_array($p->param))
		$p->param=array();

	// Produire le premier argument {emb}
	$texte = new Texte;
	$texte->texte='emb';
	$param = array('', array($texte));

	// Transformer les filtres en arguments
	for ($i=0; $i<count($p->param); $i++) {
		if ($p->param[$i][0]) {
			if (!strstr($p->param[$i][0], '='))
				break;# on a rencontre un vrai filtre, c'est fini
			$texte = new Texte;
			$texte->texte=$p->param[$i][0];
			$param[] = array($texte);
		}
		array_shift($p->param);
	}
	array_unshift($p->param, $param);	
	spip_log('balise EMBED_DOCUMENT obsolete', 'vieilles_defs');
	$p->nom_champ = 'MODELE';
}

// Vieux formulaire de recherch

function phraser_vieux_recherche($p)
{
	if ($p->param[0][0]) {
		$c = new Texte;
		$c->texte = $p->param[0][0];
		$p->param[0][1] = array($c);
		$p->param[0][0] = '';
		$p->fonctions = array();
		spip_log('FORMULAIRE_RECHERCHE avec filtre ' . $c->texte, 'vieilles_defs');
	}
}

// Gerer la notation [(#EXPOSER|on,off)]
function phraser_vieux_exposer($p)
{
	if ($a = $p->fonctions) {
		preg_match("#([^,]*)(,(.*))?#", $a[0][0], $regs);
		$args = array();
		if ($regs[1]) {
			$a = new Texte;
			$a->texte = $regs[1];
			$args = array('', array($a));
			if ($regs[3]) {
				$a = new Texte;
				$a->texte = $regs[3];
				$args[] = array($a);
			}
		}
		$p->param[0] = $args;
		$p->fonctions = array();
		$p->nom_champ = 'EXPOSE';
	}
}

function phraser_vieux_modele($p)  {normaliser_args_inclumodel($p);}
function phraser_vieux_inclu($p) {normaliser_args_inclumodel($p);}

function normaliser_args_inclumodel($p)
{
	$params = $p->param;
	if (!$params) return;
	$args = $params[0];
	if ($args[0]) return; // filtre immediat
	array_shift($p->param);
	foreach ($p->param as $l) {
		if (!array_shift($l))  {
			$args = array_merge($args, $l);
			array_shift($p->param);
		} else  break; // filtre
	}
	array_unshift($p->param, $args);
}

function normaliser_inclure($champ)
{
	normaliser_args_inclumodel($champ);
	$l = $champ->param[0];
	if (is_array($l) AND !$l[0]) {
	  foreach ($l as $k => $p) {
	    if ($p AND $p[0]->type == 'texte' AND !strpos($p[0]->texte,'=')) {
	      $p[0]->texte = trim($p[0]->texte);
	      }
	  }
	  foreach ($l as $k => $p) {
		if (!$p OR $p[0]->type != 'texte' OR 
		    !preg_match('/^fond\s*=\s*(.*)$/',$p[0]->texte, $r))
			continue;

		if ($r[1])
			$p[0]->texte = $r[1];
		else unset($p[0]);
		$champ->texte = $p;
		unset($champ->param[0][$k]);
		if (count($champ->param[0]) ==1) 
			array_shift($champ->param);
		return;
	  }
	}
	spip_log("inclure sans fond ni fichier");
}

?>
