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

//
// Fonction des balises #LOGO_XXXX
// (les balises portant ce type de nom sont traitees en bloc ici)
//

// http://doc.spip.org/@balise_LOGO__dist
function balise_LOGO__dist ($p) {

	preg_match(",^LOGO_([A-Z]+)(_.*)?$,i", $p->nom_champ, $regs);
	$type = strtolower($regs[1]);
	$suite_logo = @$regs[2];	

	// cas de #LOGO_SITE_SPIP
	if (preg_match(",^_SPIP(.*)$,", $suite_logo, $regs)) {
		$type = 'site';
		$suite_logo = $regs[1];
		$_id_objet = "\"'0'\"";
		$id_objet = 'id_syndic'; # parait faux mais donne bien "siteNN"
	} else {
		if ($type == 'site')
			$id_objet = "id_syndic";
		else
			$id_objet = "id_". $type;
		$_id_objet = champ_sql($id_objet, $p);
	}

	$fichier = ($p->etoile === '**') ? -1 : 0;
	$lien = ($p->etoile === '*') ? ' ' : '';
	$coord = array();
	$align = $params = '';
	$mode_logo = '';

	if ($p->param AND !$p->param[0][0]) {
		$params = array_shift($p->param);
		array_shift($params);
		foreach($params as $a) {
			if ($a[0]->type === 'texte') {
				$n = $a[0]->texte;
				if (is_numeric($n))
					$coord[]= $n;
				elseif (in_array($n,array('top','left','right','center','bottom')))
					$align = $n;
				elseif (in_array($n,array('auto','icone','apercu','vignette')))
					$mode_logo = $n;
			}
			else
				$lien = $a[0];
		}
	}

	$coord_x = !$coord  ? 0 : intval(array_shift($coord));
	$coord_y = !$coord  ? 0 : intval(array_shift($coord));
	
	// Bloc de compatibilite SPIP <= 2.0
	// Ne pas chercher a comprendre.
	foreach($p->param as $couple) {
		$nom = trim($couple[0]);
		if ($nom == '')  break;
		$r = logo_faux_filtres($nom);
		if ($r === 0) {
			$align = $nom;
			array_shift($p->param);
			spip_log('filtre de logo obsolete', 'vieilles_defs');
		} else {
			if ($r === 2) {
				$fichier = -1;
				array_shift($p->param);
				spip_log('filtre de logo obsolete', 'vieilles_defs');
			} elseif ($r === 1) {
				$lien = ' ';
				array_shift($p->param);
				spip_log('filtre de logo obsolete', 'vieilles_defs');
			} elseif (ltrim($nom[0])=='#') {
				array_shift($p->param);
				$lien = $nom;
				// le cas else est la seule incompatibilite
				spip_log('filtre de logo obsolete', 'vieilles_defs');
			}
			break;
		}
	}
	// Fin du bloc
	// mais reste a traiter les cas ou $lien est une chaine
	// (ecriture [(#LOGO|#URL...)] 

	if ($lien) {
		$x = is_string($lien);
		if ($x) $x = !preg_match(",^[^#]*#([A-Za-z_]+),", $lien, $r);
		if ($x)  {
			include_spip('balise/url_');
			$lien = generer_generer_url_arg($type, $p, $_id_objet);
		} else {
			if (is_string($lien)) {
				$c = new Champ();
				$c->nom_champ = $r[1];
				$c->id_boucle = $p->id_boucle;
				$c->boucles = &$p->boucles;
				$c->descr = $p->descr;
				$lien = $c;
			}
			$lien = calculer_liste(array($lien), $p->descr, $p->boucles, $p->id_boucle);
		}
	}

	$connect = $p->id_boucle ?$p->boucles[$p->id_boucle]->sql_serveur :'';
	if ($type == 'document') {
		$qconnect = _q($connect);
		$doc = "quete_document($_id_objet, $qconnect)";
		if ($fichier)
			$code = "quete_logo_file($doc, $qconnect)";
		else $code = "quete_logo_document($doc, " . ($lien ? $lien : "''") . ", '$align', '$mode_logo', $coord_x, $coord_y, $qconnect)";
		// (x=non-faux ? y : '') pour affecter x en retournant y
		if ($p->descr['documents'])
		  $code = '(($doublons["documents"] .= ",". '
		    . $_id_objet
		    . ") ? $code : '')";
	}
	elseif ($connect) {
		$code = "''";
		spip_log("Les logos distants ne sont pas prevus");
	} else {
		$code = logo_survol($id_objet, $_id_objet, $type, $align, $fichier, $lien, $p, $suite_logo);
	}
	$p->code = $code;
	$p->interdire_scripts = false;
	return $p;
}

function logo_survol($id_objet, $_id_objet, $type, $align, $fichier, $lien, $p, $suite)
{
	$code = "quete_logo('$id_objet', '" .
		(($suite == '_SURVOL') ? 'off' : 
		(($suite == '_NORMAL') ? 'on' : 'ON')) .
		"', $_id_objet," .
		(($suite == '_RUBRIQUE') ? 
		champ_sql("id_rubrique", $p) :
		(($type == 'rubrique') ? "quete_parent($_id_objet)" : "''")) .
		", " . intval($fichier) . ")";

	if ($fichier) return $code;

	$code = "\n((!is_array(\$l = $code)) ? '':\n (" .
		     '"<img class=\"spip_logos\" alt=\"\"' .
		    ($align ? " align=\\\"$align\\\"" : '')
		    . ' src=\"$l[0]\"" . $l[3] .  ($l[1] ? " onmouseover=\"this.src=\'$l[1]\'\" onmouseout=\"this.src=\'$l[0]\'\"" : "") . \' />\'))';

	if (!$lien) return $code;

	return ('\'<a href="\' .' . $lien . ' . \'"> \' . ' . $code . " . '</a>'");

}

// Les pseudos filtres |fichier et |lien pour les balises LOGO_XXX
// donnent le chemin du fichier et son URL.
// Ecritures obsolete remplacees par ** et *: LOGO_XXX** et LOGO_XXX*
// Conservees pour compatibilite ascendante mais ne plus utiliser
// Idem pour le positionnement.
// -> http://www.spip.net/fr_article901.html

function logo_faux_filtres($nom)
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

?>
