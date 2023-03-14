<?php

use Spip\Compilateur\Noeud\Champ;
use Spip\Compilateur\Noeud\Texte;

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

// Les fonctions de ce fichier sont appelees en certains points
// de l'analyseur syntaxique afin de normaliser de vieilles syntaxes,
// pour fournir au compilateur un arbre de syntaxe abstraite homogene

// Cas des pseudos filtres |fichier et |lien qui  donnent le chemin du fichier
// et son URL, remplaces par ** et *: LOGO_XXX** et LOGO_XXX*
// Il y a aussi le futur attribut align et l'ecriture #LOGO|#URL
// qui passent en arguments de la balise: #LOGO{left,#URL...}
// -> https://www.spip.net/fr_article901.html

function phraser_vieux_logos(&$p) {
	$args = $p->param[0][0] ? [''] : array_shift($p->param);

	foreach ($p->param as $couple) {
		$nom = trim($couple[0]);
		if ($nom == '') {
			array_shift($p->param);
			break;
		}
		$r = phraser_logo_faux_filtres($nom);
		if ($r === 0) {
			$c = new Texte();
			$c->texte = $nom;
			$args[] = [$c];
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
		} elseif (preg_match('/^' . NOM_DE_CHAMP . '(.*)$/sS', $nom, $m)) {
			$champ = new Champ();
			$champ->nom_boucle = $m[2];
			$champ->nom_champ = $m[3];
			$champ->etoile = $m[5];
			$champ = [$champ];
			if ($m[6]) {
				$r = new Texte();
				$r->texte = $m[6];
				$champ[] = $r;
			}
			$args[] = $champ;
			array_shift($p->param);
			spip_log("filtre de logo obsolete $nom", 'vieilles_defs');
		} // le cas else est la seule incompatibilite
	}
	array_unshift($p->param, $args);
}


function phraser_logo_faux_filtres($nom) {
	return match ($nom) {
		'top', 'left', 'right', 'center', 'bottom' => 0,
		'lien' => 1,
		'fichier' => 2,
		default => $nom,
	};
}


// La balise embed_document est a present le modele emb

function phraser_vieux_emb(&$p) {
	if (!is_array($p->param)) {
		$p->param = [];
	}

	// Produire le premier argument {emb}
	$texte = new Texte();
	$texte->texte = 'emb';
	$param = ['', [$texte]];

	// Transformer les filtres en arguments
	$paramCount = is_countable($p->param) ? count($p->param) : 0;
	for ($i = 0; $i < $paramCount; $i++) {
		if ($p->param[$i][0]) {
			if (!strstr($p->param[$i][0], '=')) {
				break;
			}# on a rencontre un vrai filtre, c'est fini
			$texte = new Texte();
			$texte->texte = $p->param[$i][0];
			$param[] = [$texte];
		}
		array_shift($p->param);
	}
	array_unshift($p->param, $param);
	spip_log('balise EMBED_DOCUMENT obsolete', 'vieilles_defs');
	$p->nom_champ = 'MODELE';
}

// Vieux formulaire de recherch

function phraser_vieux_recherche($p) {
	if ($p->param[0][0]) {
		$c = new Texte();
		$c->texte = $p->param[0][0];
		$p->param[0][1] = [$c];
		$p->param[0][0] = '';
		$p->fonctions = [];
		spip_log('FORMULAIRE_RECHERCHE avec filtre ' . $c->texte, 'vieilles_defs');
	}
}

// Gerer la notation [(#EXPOSER|on,off)]
function phraser_vieux_exposer($p) {
	if ($a = $p->fonctions) {
		preg_match('#([^,]*)(,(.*))?#', $a[0][0], $regs);
		$args = [];
		if ($regs[1]) {
			$a = new Texte();
			$a->texte = $regs[1];
			$args = ['', [$a]];
			if ($regs[3]) {
				$a = new Texte();
				$a->texte = $regs[3];
				$args[] = [$a];
			}
		}
		$p->param[0] = $args;
		$p->fonctions = [];
		$p->nom_champ = 'EXPOSE';
	}
}

function phraser_vieux_modele($p) {
 normaliser_args_inclumodel($p);
}

function phraser_vieux_inclu($p) {
 normaliser_args_inclumodel($p);
}

function normaliser_args_inclumodel($p) {
	$params = $p->param;
	if (!$params) {
		return;
	}
	$args = $params[0];
	if ($args[0]) {
		return;
	} // filtre immediat
	array_shift($p->param);
	foreach ($p->param as $l) {
		if (!array_shift($l)) {
			$args = array_merge($args, $l);
			array_shift($p->param);
		} else {
			break;
		} // filtre
	}
	array_unshift($p->param, $args);
}

function normaliser_inclure($champ) {
	normaliser_args_inclumodel($champ);
	$l = $champ->param[0];
	if (is_array($l) && !$l[0]) {
		foreach ($l as $k => $p) {
			if ($p && $p[0]->type == 'texte' && !strpos($p[0]->texte, '=')) {
				$p[0]->texte = trim($p[0]->texte);
			}
		}
		foreach ($l as $k => $p) {
			if (
				!$p || $p[0]->type != 'texte'
				|| !preg_match('/^fond\s*=\s*(.*)$/', $p[0]->texte, $r)
			) {
				continue;
			}

			if ($r[1]) {
				$p[0]->texte = $r[1];
			} else {
				unset($p[0]);
			}
			$champ->texte = $p;
			unset($champ->param[0][$k]);
			if ((is_countable($champ->param[0]) ? count($champ->param[0]) : 0) == 1) {
				array_shift($champ->param);
			}

			return;
		}
	}
	spip_log('inclure sans fond ni fichier');
}
