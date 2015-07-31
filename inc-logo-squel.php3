<?php


# Fonctions de traitement de champs Spip homonymes de champs SQL
# mais non e'quivalent


// #EXTRA [(#EXTRA|isbn)]
// Champs extra
// Non documentes, en voie d'obsolescence, cf. ecrire/inc_extra.php3
function balise_EXTRA_dist ($p) {
	$_extra = champ_sql('extra', $p);
	$p->code = $_extra;

	// Gerer la notation [(#EXTRA|isbn)]
	if ($p->fonctions) {
		include_ecrire("inc_extra.php3");
		list ($key, $champ_extra) = each($p->fonctions);	// le premier filtre
		$type_extra = $p->boucles[$p->id_boucle]->type_requete;
			// ci-dessus est sans doute un peu buggue : si on invoque #EXTRA
			// depuis un sous-objet sans champ extra d'un objet a champ extra,
			// on aura le type_extra du sous-objet (!)
		if (extra_champ_valide($type_extra, $champ_extra)) {
			unset($p->fonctions[$key]);
			$p->code = "extra($p->code, '".addslashes($champ_extra)."')";

			// Appliquer les filtres definis par le webmestre
			$filtres = extra_filtres($type_extra, $champ_extra);
			if ($filtres) foreach ($filtres as $f)
				$p->code = "$f($p->code)";
		}
	}

	$p->type = 'html';
	return $p;
}


// #LANG
// non documente ?
function balise_LANG_dist ($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "($_lang ? $_lang : \$GLOBALS['spip_lang'])";
	$p->type = 'php';
	return $p;
}


// #LESAUTEURS
// les auteurs d'un article (ou d'un article syndique)
// http://www.spip.net/fr_article902.html
// http://www.spip.net/fr_article911.html
function balise_LESAUTEURS_dist ($p) {
	// Cherche le champ 'lesauteurs' dans la pile
	$_lesauteurs = champ_sql('lesauteurs', $p);

	// Si le champ n'existe pas (cas de spip_articles), on donne la
	// construction speciale sql_auteurs(id_article) ;
	// dans le cas contraire on prend le champ 'les_auteurs' (cas de
	// spip_syndic_articles)
	if ($_lesauteurs AND $_lesauteurs != '$Pile[0][lesauteurs]') {
		$p->code = $_lesauteurs;
	} else {
		$_id_article = champ_sql('id_article', $p);
		$p->code = "sql_auteurs($_id_article)";
	}

	$p->type = 'html';
	return $p;
}


// #PETITION
// Champ testant la presence d'une petition
// non documente ???
function balise_PETITION_dist ($p) {
	$_id_article = champ_sql('id_article', $p);
	$p->code = 'sql_petitions($_id_article)';
	$p->type = 'php';
	return $p;
}


// #POPULARITE
// http://www.spip.net/fr_article1846.html
function balise_POPULARITE_dist ($p) {
	$_popularite = champ_sql('popularite', $p);
	$p->code = "ceil(min(100, 100 * $_popularite
	/ max(1 , 0 + lire_meta('popularite_max'))))";
	$p->type = 'php';
	return $p;
}


# Fonction commune aux logos (rubriques, articles...)

function calculer_champ_LOGO($p) {

	// analyser la balise LOGO_xxx
	eregi("^LOGO_(([A-Z]+)(_.*)?)", $p->nom_champ, $regs);
	$type_logo = $regs[1];	// ARTICLE_RUBRIQUE
	$type_objet = $regs[2];	// ARTICLE
	$suite_logo = $regs[3];	// _RUBRIQUE
	$_id_objet = champ_sql("id_$type_objet", $p);

	// analyser les filtres
	$flag_fichier = 'false';
	$filtres = '';
	if (is_array($p->fonctions)) {
		foreach($p->fonctions as $nom) {
			if (ereg('^(left|right|center|top|bottom)$', $nom))
				$align = $nom;
			else if ($nom == 'lien') {
				$flag_lien_auto = 'oui';
				$flag_stop = true;
			}
			else if ($nom == 'fichier') {
				$flag_fichier = 'true';
				$flag_stop = true;
			}
			// double || signifie "on passe aux filtres"
			else if ($nom == '')
				$flag_stop = true;
			else if (!$flag_stop) {
				$lien = $nom;
				$flag_stop = true;
			}
			// apres un URL ou || ou |fichier ce sont
			// des filtres (sauf left...lien...fichier)
			else
				$filtres[] = $nom;
		}
		// recuperer les autres filtres s'il y en a
		$p->fonctions = $filtres;
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
		while (ereg("^([^#]*)#([A-Za-z_]+)(.*)$", $code_lien, $match)) {
			list($c,$m) = calculer_champ(array(), $match[2], $p->id_boucle, $p->boucles, $p->id_mere);
			$code_lien = str_replace('#'.$match[2], "'.".$c.".'", $code_lien);
		}
		// supprimer les '' disgracieux
		$code_lien = ereg_replace("^''\.|\.''$", "", $code_lien);
	}
	if (!$code_lien)
		$code_lien = "''";

	switch ($suite_logo) {
		case '_NORMAL':
			$onoff = 'true, false';
			break;
		case '_SURVOL':
			$onoff = 'false, true';
			break;
		case '':
		default:
			$onoff = 'true, true';
			break;
	}

	// cas des documents
	if ($type_objet == 'DOCUMENT')
		$code_logo =
			"array(integre_image($_id_objet,'','fichier_vignette'), '')";
	else
		$code_logo = "cherche_logo_objet('$type_objet',
			$_id_objet, $onoff)";

	// cas des logo #BREVE_RUBRIQUE et #ARTICLE_RUBRIQUE
	if ($suite_logo == '_RUBRIQUE') {
		$_id_rubrique = champ_sql("id_rubrique", $p);
		$code_logo = "(\$logo = $code_logo) ? \$logo : ".
		"cherche_logo_objet('RUBRIQUE', $_id_rubrique, $onoff)";
	}

	$p->code = "affiche_logos($code_logo, $code_lien, '$align', $flag_fichier)";

	$p->type = 'php';
	return $p;
}

?>
