<?php

//
// Des fonctions diverses utilisees lors du calcul d'une page ; ces fonctions
// bien pratiques n'ont guere de logique organisationnelle ; elles sont
// appelees par certaines balises au moment du calcul des pages. (Peut-on
// trouver un modele de donnees qui les associe physiquement au fichier
// definissant leur balise ???
//

// ON TROUVERA EN QUEUE DE FICHIER LES FONCTIONS FAISANT DES APPELS SQL


// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CALCUL_OUTILS")) return;
define("_INC_CALCUL_OUTILS", "1");

// Pour les documents comme pour les logos, le filtre |fichier donne
// le chemin du fichier apres 'IMG/' ;  peut-etre pas d'une purete
// remarquable, mais a conserver pour compatibilite ascendante.
// -> http://www.spip.net/fr_article901.html
function calcule_fichier_logo($on) {
	$r = ereg_replace("^" . _DIR_IMG, "", $on);
	return $r;
}

// Renvoie le code html pour afficher un logo, avec ou sans survol, lien, etc.
// utilise la globale ci-dessous pour les attributs hspace & vspace

tester_variable('espace_logos',3);

function affiche_logos($logos, $lien, $align) {
	static $num_survol=0;
	global $espace_logos;
	list ($arton, $artoff) = $logos;

	if (!$arton) return $artoff;

	$num_survol++;
	$milieu = "<img src='$arton'"
		. ($align ? " align='$align' " : '') 
		. " name='image$num_survol' border='0' "
		. "alt='image$num_survol'"
		. " hspace='$espace_logos' vspace='$espace_logos' class='spip_logos' />";

	if (!$artoff) return ($lien ? http_href($lien, $milieu) : $milieu);

	$att =	"onmouseover=\"image$num_survol.src='$artoff'\" 
		onmouseout=\"image$num_survol.src='$arton'\"";

	return ($lien ? "<a href='$lien' $att>$milieu</a>" : "<div $att>$milieu</div>");

}

//
// Retrouver le logo d'un objet (et son survol)
//

function calcule_logo($type, $onoff, $id, $id_rubrique, $ff) {
	include_ecrire('inc_logos.php3');

	$table_logos = array (
	'ARTICLE' => 'art',
	'AUTEUR' =>  'aut',
	'BREVE' =>  'breve',
	'MOT' => 'mot',
	'RUBRIQUE' => 'rub',
	'SITE' => 'site'
	);
	$type = $table_logos[$type];
	$nom = strtolower($onoff);
	# attention au cas $id = '0' pour LOGO_SITE_SPIP : utiliser intval()
	while (1) {
		$on = cherche_image_nommee($type . $nom . intval($id));
		if ($on) {
			if ($ff)
			  return  (array('', "$on[1].$on[2]"));
			else {
				$off = ($onoff != 'ON') ? '' :
					cherche_image_nommee($type . 'off' . $id);
				return array ("$on[0]$on[1].$on[2]",
					      ($off ? ("$off[0]$off[1].$off[2]") : ''));
			}
		}
		else if ($id_rubrique) {
			$type = 'rub';
			$id = $id_rubrique;
			$id_rubrique = 0;
		} else if ($id AND $type == 'rub')
			$id = sql_parent($id);
		else return array('','');
	}
}

//
// fonction standard de calcul de la balise #INTRODUCTION
// on peut la surcharger en definissant dans mes_fonctions.php3 :
// function introduction($type,$texte,$chapo,$descriptif) {...}
//
function calcul_introduction ($type, $texte, $chapo='', $descriptif='') {
	if (function_exists("introduction"))
		return introduction ($type, $texte, $chapo, $descriptif);

	switch ($type) {
		case 'articles':
			if ($descriptif)
				return propre($descriptif);
			else if (substr($chapo, 0, 1) == '=')	// article virtuel
				return '';
			else
				return PtoBR(propre(supprimer_tags(couper_intro($chapo."\n\n\n".$texte, 500))));
			break;
		case 'breves':
			return PtoBR(propre(supprimer_tags(couper_intro($texte, 300))));
			break;
		case 'forums':
			return PtoBR(propre(supprimer_tags(couper_intro($texte, 600))));
			break;
		case 'rubriques':
			if ($descriptif)
				return propre($descriptif);
			else
				return PtoBR(propre(supprimer_tags(couper_intro($texte, 600))));
			break;
	}
}

function executer_balise_dynamique($nom, $args, $filtres) {
	$file = 'inc-' . strtolower($nom) . _EXTENSION_PHP;
	include_local($file);
	$f = 'balise_' . $nom . '_stat';
	$r = $f($args, $filtres);
	if (!is_array($r))
		return $r;
	else { 
		return
		('<'.'?php 
include_ecrire(\'inc_lang.php3\');
lang_select($GLOBALS["spip_lang"]);
include_local("'
		. $file
		. '");
inclure_balise_dynamique(balise_'
		. $nom
		. '_dyn(\''
		. join("', '", array_map("texte_script", $r))
		. '\'));
	lang_dselect();
?'
		.">");
	}
}

//
// FONCTIONS FAISANT DES APPELS SQL
//

# NB : a l'exception des fonctions pour les balises dynamiques

function calcul_exposer ($id, $type, $reference) {
	static $exposer;
	static $ref_precedente;

	// Que faut-il exposer ? Tous les elements de $reference
	// ainsi que leur hierarchie ; on ne fait donc ce calcul
	// qu'une fois (par squelette) et on conserve le resultat
	// en static.
	if ($reference<>$ref_precedente) {
		$ref_precedente = $reference;

		$exposer = array();
		foreach ($reference as $element=>$id_element) {
			if ($element == 'id_secteur') $element = 'id_rubrique';
			if ($x = table_from_primary($element)) {
				list($table,$hierarchie) = $x;
				$exposer[$element][$id_element] = true;
				if ($hierarchie) {
					list ($id_rubrique) = spip_abstract_fetsel(
array('id_rubrique'), 
array($table),
array("$element=$id_element"));
				$hierarchie = substr(calculer_hierarchie($id_rubrique), 2);
				foreach (split(',',$hierarchie) as $id_rubrique)
					$exposer['id_rubrique'][$id_rubrique] = true;
				}
			}
		}
	}

	// And the winner is...
	return $exposer[$type][$id];
}

function table_from_primary($id) {
	global $tables_principales;
	include_ecrire('inc_serialbase.php3');
	foreach ($tables_principales as $k => $v) {
		if ($v['key']['PRIMARY KEY'] == $id)
			return array($k, array_key_exists('id_rubrique', $v['field']));
	}
	return '';
}

function calcul_generation ($generation) {
	$lesfils = array();
	$result = spip_abstract_select(array('id_rubrique'),
				array('rubriques AS rubriques'),
				array(calcul_mysql_in('id_parent', 
					$generation,
					'')),
				'','','','','','','');
	while ($row = spip_abstract_fetch($result))
		$lesfils[] = $row['id_rubrique'];
	return join(",",$lesfils);
}

function calcul_branche ($generation) {
	if (!$generation) 
		return '0';
	else {
		$branche[] = $generation;
		while ($generation = calcul_generation ($generation))
			$branche[] = $generation;
		return join(",",$branche);
	}
}

// fonction appelee par la balise #LOGO_DOCUMENT
function calcule_document($id_document, $doubdoc, &$doublons) {
	if (!$id_document) return '';
	if ($doubdoc && $id_document) $doublons["documents"] .= ', ' . $id_document;

	if (!($row = spip_abstract_select(array('id_type', 'id_vignette', 'fichier', 'mode'), array('documents AS documents'), array("id_document = $id_document"))))
// pas de document. Ne devrait pas arriver
		return ''; 

	list($id_type, $id_vignette, $fichier, $mode) = spip_abstract_fetch($row);
	if ($id_vignette) {
		if ($res = spip_abstract_select(array('fichier'), array('documents AS documents'), array("id_document = $id_vignette"))) {
			list($vignette) = spip_abstract_fetch($res);
			if (@file_exists($vignette))
				return generer_url_document($id_vignette);
				# return ($fichier); # en std g_u_d fait ca
		}
	} else if ($mode == 'vignette') 
		return generer_url_document($id_document);
		# return $fichier; # en std g_u_d fait ca

// calcul de l'extension par tous les moyens
	if ($id_type) {
		list($ext) = spip_abstract_fetch(spip_abstract_select(array('extension'), array('types_documents AS documents'), array("id_type = " . intval($id_type))));
	} else {
		eregi('\.([a-z0-9]+)$', $fichier, $regs);
		$ext = $regs[1];
	}
// Pas de vignette mais une extension:
// prendre la vignette de celle-ci dans IMG/icones sauf si on peut faire mieux
	$formats = ','.lire_meta('formats_graphiques').',';
	if ((strpos($formats, ",$ext,") === false) OR
	!$fichier OR (lire_meta("creer_preview") != 'oui')) {
		return vignette_par_defaut($ext ? $ext : 'txt', false);
	}
// on peut faire mieux dans le cas des images: une previsualisation
// on devrait verifier que le fichier existe dans IMG/vignette
// et sinon lancer creer_vignette (qui fera un UPDATE sur spip_documents)
// mais on risque de dépasser le temps alloue au processus
	return 'spip_image.php3?vignette='.rawurlencode(
		str_replace('../', '', $fichier));
}


// fonction appelee par la balise #EMBED
function calcule_embed_document($id_document, $filtres, &$doublons, $doubdoc) {
	if ($doubdoc && $id_document) $doublons["documents"] .= ', ' . $id_document;
	return embed_document($id_document, $filtres, false);
}

// fonction appelee par la balise #NOTES
function calculer_notes() {
	$r = $GLOBALS["les_notes"];
	$GLOBALS["les_notes"] = "";
	$GLOBALS["compt_note"] = 0;
	$GLOBALS["marqueur_notes"] ++;
	return $r;
}

# retourne la profondeur d'une rubrique

function sql_profondeur($id) {
	$n = 0;
	while ($id) {
		$n++;
		$id = sql_parent($id);
	}
	return $n;
}


function sql_parent($id_rubrique) {
	$row = spip_abstract_fetsel(array(id_parent), 
			array('rubriques'), 
			array("id_rubrique=" . intval($id_rubrique)));
	return $row['id_parent'];
}

function sql_rubrique($id_article) {
	$row = spip_abstract_fetsel(array('id_rubrique'),
			array('articles'),
			array("id_article=" . intval($id_article)));
	return $row['id_rubrique'];
}

function sql_auteurs($id_article, $table, $id_boucle, $serveur='') {
	$auteurs = "";
	if ($id_article) {
		$result_auteurs = spip_abstract_select(array('auteurs.nom', 'auteurs.email'),
			array('auteurs AS auteurs',
				'auteurs_articles AS lien'), 
			array("lien.id_article=$id_article",
				"auteurs.id_auteur=lien.id_auteur"),
			'','','','',1, 
			$table, $id_boucle, $serveur);

		while($row_auteur = spip_abstract_fetch($result_auteurs, $serveur)) {
			$nom_auteur = typo($row_auteur["nom"]);
			$email_auteur = $row_auteur["email"];
			if ($email_auteur) {
				$auteurs[] = "<a href=\"mailto:$email_auteur\">$nom_auteur</a>";
			} else {
				$auteurs[] = "$nom_auteur";
			}
		}
	}
	return (!$auteurs) ? "" : join($auteurs, ", ");
}

function sql_petitions($id_article, $table, $id_boucle, $serveur, &$Cache) {
	$retour = spip_abstract_fetsel(
			array('texte'),
			array('petitions'),
			array("id_article=".intval($id_article)),
			'','','','',1, 
			$table, $id_boucle, $serveur);

	if (!$retour) return '';
	# cette page est invalidee par toute petition
	if ($Cache) $Cache['petition']['petition'] = 1;
	# ne pas retourner '' car le texte sert aussi de présence
	return ($retour['texte'] ? $retour['texte'] : ' ');
}

# retourne le chapeau d'un article, et seulement s'il est publie

function sql_chapo($id_article) {
	if ($id_article)
	return spip_abstract_fetsel(array('chapo'),
		array('articles'),
		array("id_article=".intval($id_article),
		"statut='publie'"));
}

# retourne le champ 'accepter_forum' d'un article
function sql_accepter_forum($id_article) {
	static $cache = array();

	if (!$id_article) return;

	if (!isset($cache[$id_article]))
		$cache[$id_article] = spip_abstract_fetsel(array('accepter_forum'),
			array('articles'),
			array("id_article=".intval($id_article)));

	return $cache[$id_article];
}


// Calcul de la rubrique associee a la requete
// (selection de squelette specifique par id_rubrique & lang)

function sql_rubrique_fond($contexte, $lang) {

	if ($id = intval($contexte['id_rubrique'])) {
		$row = spip_abstract_fetsel(array('lang'),
					    array('rubriques'),
					    array("id_rubrique=$id"));
		if ($row['lang'])
			$lang = $row['lang'];
		return array ($id, $lang);
	}

	if ($id  = intval($contexte['id_breve'])) {
		$row = spip_abstract_fetsel(array('id_rubrique', 'lang'),
			array('breves'), 
			array("id_breve=$id"));
		$id_rubrique_fond = $row['id_rubrique'];
		if ($row['lang'])
			$lang = $row['lang'];
		return array($id_rubrique_fond, $lang);
	}

	if ($id = intval($contexte['id_syndic'])) {
		$row = spip_abstract_fetsel(array('id_rubrique'),
			array('syndic'),
			array("id_syndic=$id"));
		$id_rubrique_fond = $row['id_rubrique'];
		$row = spip_abstract_fetsel(array('lang'),
			array('rubriques'),
			array("id_rubrique='$id_rubrique_fond'"));
		if ($row['lang'])
			$lang = $row['lang'];
		return array($id_rubrique_fond, $lang);
	}

	if ($id = intval($contexte['id_article'])) {
		$row = spip_abstract_fetsel(array('id_rubrique', 'lang'),
			array('articles'),
			array("id_article=$id"));
		$id_rubrique_fond = $row['id_rubrique'];
		if ($row['lang'])
			$lang = $row['lang'];
		return array($id_rubrique_fond, $lang);
	}
}

?>