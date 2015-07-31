<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Fonctions d'appel aux serveurs SQL presentes dans le code compile
//

# NB : a l'exception des fonctions pour les balises dynamiques

include_spip('base/abstract_sql');

# retourne le chapeau d'un article, et seulement s'il est publie

// http://doc.spip.org/@quete_chapo
function quete_chapo($id_article, $connect) {
	return sql_getfetsel('chapo', 'spip_articles', array("id_article=".intval($id_article), "statut='publie'"), '','','','',$connect);
}

# retourne le parent d'une rubrique

// http://doc.spip.org/@quete_parent
function quete_parent($id_rubrique, $connect='') {
	if (!$id_rubrique = intval($id_rubrique))
		return 0;

	return intval(sql_getfetsel('id_parent','spip_rubriques',"id_rubrique=" . $id_rubrique, '','','','',$connect));
}

# retourne la profondeur d'une rubrique

// http://doc.spip.org/@quete_profondeur
function quete_profondeur($id, $connect='') {
	$n = 0;
	while ($id) {
		$n++;
		$id = quete_parent($id, $connect);
	}
	return $n;
}


# retourne le fichier d'un document

// http://doc.spip.org/@quete_fichier
function quete_fichier($id_document, $serveur) {
	return sql_getfetsel('fichier', 'spip_documents', ("id_document=" . intval($id_document)),	'',array(), '', '', $serveur);
}

// http://doc.spip.org/@quete_petitions
function quete_petitions($id_article, $table, $id_boucle, $serveur, &$cache) {
	$retour = sql_getfetsel('texte', 'spip_petitions',("id_article=".intval($id_article)),'',array(),'','', $serveur);

	if ($retour === NULL) return '';
	# cette page est invalidee par toute petition
	$cache['varia']['pet'.$id_article] = 1;
	# ne pas retourner '' car le texte sert aussi de presence
	return $retour ? $retour : ' ';
}

# retourne le champ 'accepter_forum' d'un article
// http://doc.spip.org/@quete_accepter_forum
function quete_accepter_forum($id_article) {
	// si la fonction est appelee en dehors d'une boucle
	// article (forum de breves), $id_article est nul
	// mais il faut neanmoins accepter l'affichage du forum
	// d'ou le 0=>'' (et pas 0=>'non').
	static $cache = array(0 => '');
	
	$id_article = intval($id_article);

	if (isset($cache[$id_article]))	return $cache[$id_article];

	return $cache[$id_article] = sql_getfetsel('accepter_forum','spip_articles',"id_article=$id_article");
}

// recuperer une meta sur un site distant (en local il y a plus simple)
// http://doc.spip.org/@quete_meta
function quete_meta($nom, $serveur) {
	return sql_getfetsel("valeur", "spip_meta", "nom=" . sql_quote($nom),
			     '','','','',$serveur);
}

# retourne la rubrique d'un article

// http://doc.spip.org/@quete_rubrique
function quete_rubrique($id_article, $serveur) {
	return sql_getfetsel('id_rubrique', 'spip_articles',"id_article=" . intval($id_article),	'',array(), '', '', $serveur);
}

// http://doc.spip.org/@calcul_exposer
function calcul_exposer ($id, $prim, $reference, $parent, $type, $connect='') {
	static $exposer = array();
	static $ref_precedente =-1;

	// Que faut-il exposer ? Tous les elements de $reference
	// ainsi que leur hierarchie ; on ne fait donc ce calcul
	// qu'une fois (par squelette) et on conserve le resultat
	// en static.
	if (!isset($exposer[$m=md5(serialize($reference))][$prim])) {
		$principal = $reference[$type];
		if (!$principal) { // regarder si un enfant est dans le contexte, auquel cas il expose peut etre le parent courant
			$enfants = array('id_rubrique'=>array('id_article'),'id_groupe'=>array('id_mot'));
			if (isset($enfants[$type]))
				foreach($enfants[$type] as $t)
					if (isset($reference[$t])) {
						$type = $t;
						$principal = $reference[$type];
						$parent=0;
						continue;
					}
		}
		$exposer[$m][$type] = array();
		$parent = intval($parent);
		if ($principal) {
			$exposer[$m][$type][$principal] = true;
			if ($type == 'id_mot'){
				if (!$parent) {
					$parent = sql_fetsel('id_groupe','spip_mots',"id_mot=" . $principal, '','','','',$connect);
					$parent = $parent['id_groupe'];
				}
				if ($parent)
					$exposer[$m]['id_groupe'][$parent] = true;
			}
			else if ($type != 'id_groupe') {
			  if (!$parent) {
			  	if ($type == 'id_rubrique')
			  		$parent = $principal;
			  	if ($type == 'id_article') {
						$parent = sql_fetsel('id_rubrique','spip_articles',"id_article=" . $principal, '','','','',$connect);
						$parent = $parent['id_rubrique'];
			  	}
			  }
			  do { $exposer[$m]['id_rubrique'][$parent] = true; }
			  while ($parent = quete_parent($parent, $connect));
			}
		}
	}
	// And the winner is...
	return isset($exposer[$m][$prim]) ? isset($exposer[$m][$prim][$id]) : '';
}

// fonction appelee par la balise #LOGO_DOCUMENT
// http://doc.spip.org/@calcule_logo_document
function calcule_logo_document($id_document, $doubdoc, &$doublons, $flag_fichier, $lien, $align, $params, $connect='') {
	include_spip('inc/documents');

	if (!$id_document) return '';
	if ($doubdoc) $doublons["documents"] .= ','.$id_document;

	if (!($row = sql_fetsel('extension, id_vignette, fichier, mode', 'spip_documents', ("id_document = $id_document"),'','','','',$connect))) {
		// pas de document. Ne devrait pas arriver
		spip_log("Erreur du compilateur doc $id_document inconnu");
		return ''; 
	}

	$extension = $row['extension'];
	$id_vignette = $row['id_vignette'];
	$fichier = $row['fichier'];
	$mode = $row['mode'];
	$logo = '';

	// Y a t il une vignette personnalisee ?
	// Ca va echouer si c'est en mode distant. A revoir.
	if ($id_vignette) {
		$vignette = sql_fetsel('fichier','spip_documents',("id_document = $id_vignette"), '','','','',$connect);
		if (@file_exists(get_spip_doc($vignette['fichier'])))
		  $logo = generer_url_entite($id_vignette, 'document');
	} else if ($mode == 'vignette') {
		$logo = generer_url_entite($id_vignette, 'document');
		if (!@file_exists($logo))
			$logo = '';
	}

	// taille maximum [(#LOGO_DOCUMENT{300,52})]
	if ($params
	AND preg_match('/{\s*(\d+),\s*(\d+)\s*}/', $params, $r)) {
		$x = intval($r[1]);
		$y = intval($r[2]);
	}

	if ($logo AND @file_exists($logo)) {
		if ($x OR $y)
			$logo = reduire_image($logo, $x, $y);
		else {
			$size = @getimagesize($logo);
			$logo = "<img src='$logo' ".$size[3]." />";
		}
	}
	else {
		// Pas de vignette, mais un fichier image -- creer la vignette
		if (strpos($GLOBALS['meta']['formats_graphiques'], $extension)!==false) {
		  if ($img = _DIR_RACINE.copie_locale(get_spip_doc($fichier))
			AND @file_exists($img)) {
				if (!$x AND !$y) {
					$logo = reduire_image($img);
				} else {
					# eviter une double reduction
					$size = @getimagesize($img);
					$logo = "<img src='$img' ".$size[3]." />";
				}
		  }
		  // cas de la vignette derriere un htaccess
		} elseif ($logo) $logo = "<img src='$logo'>";

		// Document sans vignette ni image : vignette par defaut
		if (!$logo) {
			$img = vignette_par_defaut($extension, false);
			$size = @getimagesize($img);
			$logo = "<img src='$img' ".$size[3]." />";
		}
	}

	// Reduire si une taille precise est demandee
	if ($x OR $y)
		$logo = reduire_image($logo, $x, $y);

	// flag_fichier : seul le fichier est demande
	if ($flag_fichier)
		return set_spip_doc(extraire_attribut($logo, 'src'));

	// Calculer le code html complet (cf. calcule_logo)
	$logo = inserer_attribut($logo, 'alt', '');
	$logo = inserer_attribut($logo, 'class', 'spip_logos');
	if ($align)
		$logo = inserer_attribut($logo, 'align', $align);

	if ($lien) {
		$mime = sql_getfetsel('mime_type','spip_types_documents', "extension = " . sql_quote($extension));
		$logo = "<a href='$lien' type='$mime'>$logo</a>";
	}
	return $logo;
}

// Ajouter "&lang=..." si la langue du forum n'est pas celle du site.
// Si le 2e parametre n'est pas une chaine, c'est qu'on n'a pas pu
// determiner la table a la compil, on le fait maintenant.
// Il faudrait encore completer: on ne connait pas la langue
// pour une boucle forum sans id_article ou id_rubrique donn� par le contexte
// et c'est signale par un message d'erreur abscons: "table inconnue forum".
// 
// http://doc.spip.org/@lang_parametres_forum
function lang_parametres_forum($qs, $lang) {
	if (is_array($lang) AND preg_match(',id_(\w+)=([0-9]+),', $qs, $r)) {
		$id = 'id_' . $r[1];
		if ($t = $lang[$id])
			$lang = sql_getfetsel('lang', $t, "$id=" . $r[2]);
	}
  // Si ce n'est pas la meme que celle du site, l'ajouter aux parametres

	if ($lang AND $lang <> $GLOBALS['meta']['langue_site'])
		return $qs . "&lang=" . $lang;

	return $qs;
}
?>
