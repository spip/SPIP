<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

//
// Fonction des balises #LOGO_XXXX
// (les balises portant ce type de nom sont traitees en bloc ici)
//

// http://doc.spip.org/@balise_LOGO__dist
function balise_LOGO__dist ($p) {

	preg_match(",^LOGO_([A-Z_]+?)(|_NORMAL|_SURVOL|_RUBRIQUE)$,i", $p->nom_champ, $regs);
	$type = strtolower($regs[1]);
	$suite_logo = $regs[2];

	// cas de #LOGO_SITE_SPIP
	if ($type == 'site_spip') {
		$type = 'site';
		$_id_objet = "\"'0'\"";
	}

	$id_objet = id_table_objet($type);
	if (!isset($_id_objet) OR !$_id_objet)
		$_id_objet = champ_sql($id_objet, $p);

	$fichier = ($p->etoile === '**') ? -1 : 0;
	$coord = array();
	$align = $lien = '';
	$mode_logo = '';

	if ($p->param AND !$p->param[0][0]) {
		$params = $p->param[0];
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
			else $lien =  calculer_liste($a, $p->descr, $p->boucles, $p->id_boucle);

		}
	}

	$coord_x = !$coord  ? 0 : intval(array_shift($coord));
	$coord_y = !$coord  ? 0 : intval(array_shift($coord));
	
	if ($p->etoile === '*') {
		include_spip('balise/url_');
		$lien = generer_generer_url_arg($type, $p, $_id_objet);
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

	// demande de reduction sur logo avec ecriture spip 2.1 : #LOGO_xxx{200, 0}
	if ($coord_x OR $coord_y) {
		$code = "filtrer('image_graver',filtrer('image_reduire',".$code.", '$coord_x', '$coord_y'))"; 
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
		    . ' src=\"$l[0]\"" . $l[2] .  ($l[1] ? " onmouseover=\"this.src=\'$l[1]\'\" onmouseout=\"this.src=\'$l[0]\'\"" : "") . \' />\'))';

	if (!$lien) return $code;

	return ('(strlen($logo='.$code.')?\'<a href="\' .' . $lien . ' . \'">\' . $logo . \'</a>\':\'\')');

}



?>
