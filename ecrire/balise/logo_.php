<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
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
	$type_objet = $regs[1];
	$suite_logo = @$regs[2];	

	// cas de #LOGO_SITE_SPIP
	if (preg_match(",^_SPIP(.*)$,", $suite_logo, $regs)) {
		$type_objet = 'SITE';
		$suite_logo = $regs[1];
		$_id_objet = "\"'0'\"";
		$id_objet = 'id_syndic'; # parait faux mais donne bien "siteNN"
	} else {
		if ($type_objet == 'SITE')
			$id_objet = "id_syndic";
		else
			$id_objet = "id_".strtolower($type_objet);
		$_id_objet = champ_sql($id_objet, $p);
	}

	// analyser les faux filtres
	$flag_fichier = $flag_stop = $flag_lien_auto = $code_lien = $filtres = $align = $lien = $params = '';

	if (is_array($p->fonctions)) {
		foreach($p->fonctions as $couple) {
			if (!$flag_stop) {
				$nom = trim($couple[0]);

				// double || signifie "on passe aux vrais filtres"
				if ($nom == '') {
					if ($couple[1]) {
						$params = $couple[1]; // recuperer #LOGO_DOCUMENT{20,30}
						array_shift($p->param);
					}
					else
						$flag_stop = true;
				} else {
					// faux filtres
					array_shift($p->param);
					switch($nom) {
						case 'left':
						case 'right':
						case 'center':
						case 'top':
						case 'bottom':
							$align = $nom;
							break;
						
						case 'lien':
							$flag_lien_auto = 'oui';
							$flag_stop = true; # apres |lien : vrais filtres
							break;

						case 'fichier':
							$flag_fichier = 1;
							$flag_stop = true; # apres |fichier : vrais filtres
							break;

						default:
							$lien = $nom;
							$flag_stop = true; # apres |#URL... : vrais filtres
							break;
					}
				}
			}
		}
	}

	//
	// Preparer le code du lien
	//
	// 1. filtre |lien

	if ($flag_lien_auto AND !$lien)
		$code_lien = '($lien = generer_url_'.$type_objet.'('.$_id_objet.')) ? $lien : ""';
	// 2. lien indique en clair (avec des balises : imprimer#ID_ARTICLE.html)
	else if ($lien) {
		$code_lien = "'".texte_script(trim($lien))."'";
		while (preg_match(",^([^#]*)#([A-Za-z_]+)(.*)$,", $code_lien, $match)) {
			$c = new Champ();
			$c->nom_champ = $match[2];
			$c->id_boucle = $p->id_boucle;
			$c->boucles = &$p->boucles;
			$c->descr = $p->descr;
			$c = calculer_champ($c);
			$code_lien = str_replace('#'.$match[2], "'.".$c.".'", $code_lien);
		}
		// supprimer les '' disgracieux
		$code_lien = preg_replace("@^''\.|\.''$@", "", $code_lien);
	}

	if ($flag_fichier)
		$code_lien = "'',''" ; 
	else {
		if (!$code_lien)
			$code_lien = "''";
		$code_lien .= ", '". $align . "'";
	}

	if ($p->id_boucle AND $p->boucles[$p->id_boucle]->sql_serveur) {
		$p->code = "''";
		spip_log("Logo distant indisponible");
	// cas des documents
	} elseif ($type_objet == 'DOCUMENT') {
		$p->code = "calcule_logo_document($_id_objet, '" .
			$p->descr['documents'] .
			'\', $doublons, '. intval($flag_fichier).", $code_lien, '".
			// #LOGO_DOCUMENT{x,y} donne la taille maxi
			texte_script($params)
			."')";
	}
	else {
		$p->code = "affiche_logos(calcule_logo('$id_objet', '" .
			(($suite_logo == '_SURVOL') ? 'off' : 
			(($suite_logo == '_NORMAL') ? 'on' : 'ON')) .
			"', $_id_objet," .
			(($suite_logo == '_RUBRIQUE') ? 
			champ_sql("id_rubrique", $p) :
			(($type_objet == 'RUBRIQUE') ? "quete_parent($_id_objet)" : "''")) .
			",  '$flag_fichier'), $code_lien)";
	}

	$p->interdire_scripts = false;
	return $p;
}
?>
