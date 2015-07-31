<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Ce fichier regroupe la quasi totalité des définitions de `#BALISES` de SPIP.
 * 
 * Pour chaque balise, il est possible de surcharger, dans son fichier
 * mes_fonctions.php, la fonction `balise_TOTO_dist()` par une fonction
 * `balise_TOTO()` respectant la même API : elle reçoit en entrée un objet
 * de classe `Champ`, le modifie et le retourne. Cette classe est définie
 * dans public/interfaces.
 * 
 * @package SPIP\Core\Compilateur\Balises
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Retourne le code PHP d'un argument de balise s'il est présent
 *
 * @example
 *     ```
 *     // Retourne le premier argument de la balise
 *     // #BALISE{premier,deuxieme}
 *     $arg = interprete_argument_balise(1,$p);
 *     ```
 * 
 * @param int $n
 *     Numéro de l'argument
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return string|null
 *     Code PHP si cet argument est présent, sinon null
**/
function interprete_argument_balise($n,$p) {
	if (($p->param) && (!$p->param[0][0]) && (count($p->param[0])>$n))
		return calculer_liste($p->param[0][$n],
			$p->descr,
			$p->boucles,
			$p->id_boucle);
	else
		return NULL;
}
//
// Definition des balises
//
// http://doc.spip.org/@balise_NOM_SITE_SPIP_dist
function balise_NOM_SITE_SPIP_dist($p) {
	$p->code = "\$GLOBALS['meta']['nom_site']";
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_EMAIL_WEBMASTER_dist
function balise_EMAIL_WEBMASTER_dist($p) {
	$p->code = "\$GLOBALS['meta']['email_webmaster']";
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_DESCRIPTIF_SITE_SPIP_dist
function balise_DESCRIPTIF_SITE_SPIP_dist($p) {
	$p->code = "\$GLOBALS['meta']['descriptif_site']";
	#$p->interdire_scripts = true;
	return $p;
}


/**
 * Compile la balise `#CHARSET` qui retourne le nom du jeu de caractères
 * utilisé par le site tel que `utf-8`
 *
 * @balise CHARSET
 * @link http://www.spip.net/4331
 * @example
 *     ```
 *     <meta http-equiv="Content-Type" content="text/html; charset=#CHARSET" />
 *     ```
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_CHARSET_dist($p) {
	$p->code = "\$GLOBALS['meta']['charset']";
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_LANG_LEFT_dist
function balise_LANG_LEFT_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir($_lang, 'left','right')";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_LANG_RIGHT_dist
function balise_LANG_RIGHT_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir($_lang, 'right','left')";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_LANG_DIR_dist
function balise_LANG_DIR_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir($_lang, 'ltr','rtl')";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_PUCE_dist
function balise_PUCE_dist($p) {
	$p->code = "definir_puce()";
	$p->interdire_scripts = false;
	return $p;
}


/**
 * Compile la balise `#DATE` qui retourne la date de mise en ligne
 *
 * Cette balise retourne soit le champ `date` d'une table si elle est
 * utilisée dans une boucle, sinon la date de calcul du squelette.
 *
 * @balise DATE
 * @link http://www.spip.net/4336 Balise DATE
 * @link http://www.spip.net/1971 La gestion des dates
 * @example
 *     ```
 *     <td>[(#DATE|affdate_jourcourt)]</td>
 *     ```
 * 
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 */
function balise_DATE_dist ($p) {
	$d = champ_sql('date', $p);
#	if ($d === "@\$Pile[0]['date']")
#		$d = "isset(\$Pile[0]['date']) ? $d : time()";
	$p->code = $d;
	return $p;
}


/**
 * Compile la balise `#DATE_REDAC` qui retourne la date de première publication
 *
 * Cette balise retourne le champ `date_redac` d'une table
 *
 * @balise DATE_REDAC
 * @link http://www.spip.net/3858 Balises DATE_MODIF et DATE_REDAC
 * @link http://www.spip.net/1971 La gestion des dates
 * @see balise_DATE_MODIF_dist()
 * 
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 */
function balise_DATE_REDAC_dist ($p) {
	$d = champ_sql('date_redac', $p);
#	if ($d === "@\$Pile[0]['date_redac']")
#		$d = "isset(\$Pile[0]['date_redac']) ? $d : time()";
	$p->code = $d;
	$p->interdire_scripts = false;
	return $p;
}

/**
 * Compile la balise `#DATE_MODIF` qui retourne la date de dernière modification
 *
 * Cette balise retourne le champ `date_modif` d'une table
 *
 * @balise DATE_MODIF
 * @link http://www.spip.net/3858 Balises DATE_MODIF et DATE_REDAC
 * @link http://www.spip.net/1971 La gestion des dates
 * @see balise_DATE_REDAC_dist()
 * 
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 */
function balise_DATE_MODIF_dist ($p) {
	$p->code = champ_sql('date_modif', $p);
	$p->interdire_scripts = false;
	return $p;
}

/**
 * Compile la balise `#DATE_NOUVEAUTES` indiquant la date de dernier envoi
 * du mail de nouveautés
 *
 * @balise DATE_NOUVEAUTES
 * @link http://www.spip.net/4337 Balise DATE_NOUVEAUTES
 * @link http://www.spip.net/1971 La gestion des dates
 * @see balise_DATE_REDAC_dist()
 * 
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 */
function balise_DATE_NOUVEAUTES_dist($p) {
	$p->code = "((\$GLOBALS['meta']['quoi_de_neuf'] == 'oui'
	AND isset(\$GLOBALS['meta']['dernier_envoi_neuf'])) ?
	\$GLOBALS['meta']['dernier_envoi_neuf'] :
	\"'0000-00-00'\")";
	$p->interdire_scripts = false;
	return $p;
}


/**
 * Compile la balise `#DOSSIER_SQUELETTE` retournant le chemin vers le
 * répertoire de squelettes actuellement utilisé
 *
 * @balise DOSSIER_SQUELETTE
 * @deprecated Utiliser `#CHEMIN`
 * @link http://www.spip.net/4627
 * @see balise_CHEMIN_dist()
 * 
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 */
function balise_DOSSIER_SQUELETTE_dist($p) {
	$code = substr(addslashes(dirname($p->descr['sourcefile'])), strlen(_DIR_RACINE));
	$p->code = "_DIR_RACINE . '$code'" .
	$p->interdire_scripts = false;
	return $p;
}

/**
 * Compile la balise `#SQUELETTE` retournant le chemin du squelette courant
 *
 * @balise SQUELETTE
 * @link http://www.spip.net/4027
 * 
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 */
function balise_SQUELETTE_dist($p) {
	$code = addslashes($p->descr['sourcefile']);
	$p->code = "'$code'" .
	$p->interdire_scripts = false;
	return $p;
}

/**
 * Compile la balise `#SPIP_VERSION` qui affiche la version de SPIP
 *
 * @balise SPIP_VERSION
 * @see spip_version()
 * @example
 *     ```
 *     <meta name="generator" content="SPIP[ (#SPIP_VERSION)]" />
 *     ```
 * 
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 */
function balise_SPIP_VERSION_dist($p) {
	$p->code = "spip_version()";
	$p->interdire_scripts = false;
	return $p;
}



/**
 * Compile la balise `NOM_SITE` qui affiche le nom du site.
 *
 * Affiche le nom du site ou sinon l'URL ou le titre de l'objet
 * Utiliser `#NOM_SITE*` pour avoir le nom du site ou rien.
 *
 * Cette balise interroge les colonnes `nom_site` ou `url_site`
 * dans la boucle la plus proche.
 *
 * @example
 *     ```
 *     <a href="#URL_SITE">#NOM_SITE</a>
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_NOM_SITE_dist($p) {
	if (!$p->etoile) {
		$p->code = "supprimer_numero(calculer_url(" .
		champ_sql('url_site',$p) ."," .
		champ_sql('nom_site',$p) .
		", 'titre', \$connect, false))";
	} else
		$p->code = champ_sql('nom_site',$p);

	$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_NOTES_dist
function balise_NOTES_dist($p) {
	// Recuperer les notes
	$p->code = 'calculer_notes()';
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_RECHERCHE_dist
function balise_RECHERCHE_dist($p) {
	$p->code = 'entites_html(_request("recherche"))';
	$p->interdire_scripts = false;
	return $p;
}


/**
 * Compile la balise `#COMPTEUR_BOUCLE` qui retourne le numéro de l’itération
 * actuelle de la boucle
 *
 * @balise COMPTEUR_BOUCLE
 * @link http://www.spip.net/4333
 * @see balise_TOTAL_BOUCLE_dist()
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_COMPTEUR_BOUCLE_dist($p) {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];
	if ($b === '') {
		$msg = array('zbug_champ_hors_boucle',
				array('champ' => '#COMPTEUR_BOUCLE')
			  );
		erreur_squelette($msg, $p);
	} else {
		$p->code = "\$Numrows['$b']['compteur_boucle']";
		$p->boucles[$b]->cptrows = true;
		$p->interdire_scripts = false;
		return $p;
	}
}

/**
 * Compile la balise `#TOTAL_BOUCLE` qui retourne le nombre de résultats
 * affichés par la boucle
 *
 * @balise TOTAL_BOUCLE
 * @link http://www.spip.net/4334
 * @see balise_COMPTEUR_BOUCLE_dist()
 * @see balise_GRAND_TOTAL_dist()
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_TOTAL_BOUCLE_dist($p) {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];
	if ($b === '' || !isset($p->boucles[$b])) {
		$msg = array('zbug_champ_hors_boucle',
				array('champ' => "#$b" . 'TOTAL_BOUCLE')
			  );
		erreur_squelette($msg, $p);
	} else {
		$p->code = "\$Numrows['$b']['total']";
		$p->boucles[$b]->numrows = true;
		$p->interdire_scripts = false;
	}
	return $p;
}

// Si on est hors d'une boucle {recherche}, ne pas "prendre" cette balise
// http://doc.spip.org/@balise_POINTS_dist
function balise_POINTS_dist($p) {
	return rindex_pile($p, 'points', 'recherche');
}

// http://doc.spip.org/@balise_POPULARITE_ABSOLUE_dist
function balise_POPULARITE_ABSOLUE_dist($p) {
	$p->code = 'ceil(' .
	champ_sql('popularite', $p) .
	')';
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_POPULARITE_SITE_dist
function balise_POPULARITE_SITE_dist($p) {
	$p->code = 'ceil($GLOBALS["meta"][\'popularite_total\'])';
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_POPULARITE_MAX_dist
function balise_POPULARITE_MAX_dist($p) {
	$p->code = 'ceil($GLOBALS["meta"][\'popularite_max\'])';
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_EXPOSE_dist
function balise_EXPOSE_dist($p) {
	$on = "'on'";
	$off= "''";
	if (($v = interprete_argument_balise(1,$p))!==NULL){
		$on = $v;
		if (($v = interprete_argument_balise(2,$p))!==NULL)
			$off = $v;

	}
	return calculer_balise_expose($p, $on, $off);
}

// #VALEUR renvoie le champ valeur
// #VALEUR{x} renvoie #VALEUR|table_valeur{x}
// #VALEUR{a/b} renvoie #VALEUR|table_valeur{a/b}
// http://doc.spip.org/@balise_VALEUR_dist
function balise_VALEUR_dist($p) {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->id_boucle;
	$p->code = index_pile($p->id_boucle, 'valeur', $p->boucles, $b);;
	if (($v = interprete_argument_balise(1,$p))!==NULL){
		$p->code = 'table_valeur('.$p->code.', '.$v.')';
	}
	$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@calculer_balise_expose
function calculer_balise_expose($p, $on, $off)
{
	$b = $p->nom_boucle ? $p->nom_boucle : $p->id_boucle;
	$key = $p->boucles[$b]->primary;
	$type = $p->boucles[$p->id_boucle]->primary;
	$desc = $p->boucles[$b]->show;
	$connect = sql_quote($p->boucles[$b]->sql_serveur);

	if (!$key) {
		$msg = array('zbug_champ_hors_boucle', array('champ' => '#EXPOSER'));
		erreur_squelette($msg, $p);
	}

	// Ne pas utiliser champ_sql, on jongle avec le nom boucle explicite
	$c = index_pile($p->id_boucle, $type, $p->boucles);

	if (isset($desc['field']['id_parent'])) {
		$parent = 0; // pour if (!$parent) dans calculer_expose
	} elseif (isset($desc['field']['id_rubrique'])) {
		$parent = index_pile($p->id_boucle, 'id_rubrique', $p->boucles, $b);
	} elseif  (isset($desc['field']['id_groupe'])) {
		$parent = index_pile($p->id_boucle, 'id_groupe', $p->boucles, $b);
	} else $parent = "''";

	$p->code = "(calcul_exposer($c, '$type', \$Pile[0], $parent, '$key', $connect) ? $on : $off)";

	$p->interdire_scripts = false;
	return $p;
}


// Debut et fin de surlignage auto des mots de la recherche
// on insere une balise Span avec une classe sans spec:
// c'est transparent s'il n'y a pas de recherche,
// sinon elles seront remplacees par les fontions de inc_surligne

// http://doc.spip.org/@balise_DEBUT_SURLIGNE_dist
function balise_DEBUT_SURLIGNE_dist($p) {
	include_spip('inc/surligne');
	$p->code = "'<!-- " . MARQUEUR_SURLIGNE . " -->'";
	return $p;
}
// http://doc.spip.org/@balise_FIN_SURLIGNE_dist
function balise_FIN_SURLIGNE_dist($p) {
	include_spip('inc/surligne');
	$p->code = "'<!-- " . MARQUEUR_FSURLIGNE . "-->'";
	return $p;
}



/**
 * Compile la balise `#INTRODUCTION`
 *
 * Retourne une introduction d'un objet éditorial, c'est à dire les 600
 * premiers caractères environ du champ 'texte' de l'objet ou le contenu
 * indiqué entre `<intro>` et `</intro>` de ce même champ.
 *
 * Pour les articles, l'introduction utilisée est celle du champ `descriptif`
 * s'il est renseigné, sinon il est pris dans les champs `chapo` et `texte` et
 * est par défaut limité à 500 caractères.
 *
 * Pour les rubriques, l'introduction utilisée est celle du champ `descriptif`
 * s'il est renseigné, sinon du champ texte.
 *
 * La balise accèpte 1 paramètre indiquant la longueur en nombre de caractères
 * de l'introduction.
 *
 * @see filtre_introduction_dist()
 * @example
 *     ```
 *     #INTRODUCTION
 *     #INTRODUCTION{300}
 *     ```
 *
 * @balise introduction
 * @link http://www.spip.net/@introduction
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_INTRODUCTION_dist($p) {

	$type = $p->type_requete;

	$_texte = champ_sql('texte', $p);
	$_descriptif = ($type == 'articles' OR $type == 'rubriques') ? champ_sql('descriptif', $p) : "''";

	if ($type == 'articles') {
		$_chapo = champ_sql('chapo', $p);
		$_texte = "(strlen($_descriptif))
		? ''
		: $_chapo . \"\\n\\n\" . $_texte";
	}

	// longueur en parametre, ou valeur par defaut
	if (($v = interprete_argument_balise(1,$p))!==NULL) {
		$longueur = 'intval('.$v.')';
	} else {
		switch ($type) {
			case 'articles':
				$longueur = '500';
				break;
			case 'breves':
				$longueur = '300';
				break;
			case 'rubriques':
			default:
				$longueur = '600';
				break;
		}
	}

	$f = chercher_filtre('introduction');
	$p->code = "$f($_descriptif, $_texte, $longueur, \$connect)";

	#$p->interdire_scripts = true;
	$p->etoile = '*'; // propre est deja fait dans le calcul de l'intro
	return $p;
}


// #LANG
// affiche la langue de l'objet (ou superieure), et a defaut la langue courante
// (celle du site ou celle qui a ete passee dans l'URL par le visiteur)
// #LANG* n'affiche rien si aucune langue n'est trouvee dans le sql/le contexte
// http://doc.spip.org/@balise_LANG_dist
function balise_LANG_dist ($p) {
	$_lang = champ_sql('lang', $p);
	if (!$p->etoile)
		$p->code = "htmlentities($_lang ? $_lang : \$GLOBALS['spip_lang'])";
	else
		$p->code = "htmlentities($_lang)";
	$p->interdire_scripts = false;
	return $p;
}

/**
 * Compile la balise `#LESAUTEURS` chargée d'afficher la liste des auteurs d'un objet
 * 
 * - Soit le champ `lesauteurs` existe dans la table et à ce moment là,
 *   la balise retourne son contenu,
 * - soit la balise appelle le modele `lesauteurs.html` en lui passant
 *   le couple `objet` et `id_objet` dans son environnement.
 *
 * @balise LESAUTEURS
 * @link http://www.spip.net/3966 Description de la balise
 * @link http://www.spip.net/902 Description de la boucle ARTICLES
 * @link http://www.spip.net/911 Description de la boucle SYNDIC_ARTICLES
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_LESAUTEURS_dist ($p) {
	// Cherche le champ 'lesauteurs' dans la pile
	$_lesauteurs = champ_sql('lesauteurs', $p, false);

	// Si le champ n'existe pas (cas de spip_articles), on applique
	// le modele lesauteurs.html en passant id_article dans le contexte;
	// dans le cas contraire on prend le champ 'lesauteurs'
	// (cf extension sites/)
	if ($_lesauteurs
	AND $_lesauteurs != '@$Pile[0][\'lesauteurs\']') {
		$p->code = "safehtml($_lesauteurs)";
		// $p->interdire_scripts = true;
	} else {
		if(!$p->id_boucle){
			$connect = '';
			$objet = 'article';
			$id_table_objet = 'id_article';
		}
		else{
			$connect = $p->boucles[$p->id_boucle]->sql_serveur;
			$type_boucle = $p->boucles[$p->id_boucle]->type_requete;
			$objet = objet_type($type_boucle);
			$id_table_objet = id_table_objet($type_boucle);
		}
		$c = memoriser_contexte_compil($p);

		$p->code = sprintf(CODE_RECUPERER_FOND, "'modeles/lesauteurs'",
				   "array('objet'=>'".$objet."','id_objet' => ".champ_sql($id_table_objet, $p) .")",
				   "'trim'=>true, 'compil'=>array($c)",
				   _q($connect));
		$p->interdire_scripts = false; // securite apposee par recuperer_fond()
	}

	return $p;
}


/**
 * Compile la balise `#RANG` chargée d'afficher le numéro de l'objet
 * 
 * Affiche le « numero de l'objet ». Soit `1` quand on a un titre `1. Premier article`.
 * 
 * Ceci est transitoire afin de préparer une migration vers un vrai système de
 * tri des articles dans une rubrique (et plus si affinités).
 * La balise permet d'extraire le numero masqué par le filtre `supprimer_numero`.
 * 
 * La balise recupère le champ declaré dans la définition `table_titre`
 * de l'objet, ou à defaut du champ `titre`
 *
 * Si un champ `rang` existe, il est pris en priorité.
 *
 * @balise RANG
 * @link http://www.spip.net/5495
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_RANG_dist($p) {
	$b = index_boucle($p);
	if ($b === '') {
		$msg = array('zbug_champ_hors_boucle',
				array('champ' => '#RANG')
			  );
		erreur_squelette($msg, $p);
	}
	else {
		// chercher d'abord un champ sql rang (mais pas dans le env : defaut '' si on trouve pas de champ sql)
		// dans la boucle immediatement englobante uniquement
		// sinon on compose le champ calcule
		$_rang = champ_sql('rang', $p, '', false);

		// si pas trouve de champ sql rang :
		if (!$_rang OR $_rang == "''"){
			$boucle = &$p->boucles[$b];
			$trouver_table = charger_fonction('trouver_table','base');
			$desc = $trouver_table($boucle->id_table);
			$_titre = ''; # où extraire le numero ?
			if (isset($desc['titre'])){
				$t=$desc['titre'];
			  if (preg_match(';(^|,)([^,]*titre)(,|$);',$t,$m)){
				  $m = preg_replace(",as\s+titre$,i","",$m[2]);
				  $m = trim($m);
				  if ($m!="''"){
					  if (!preg_match(",\W,",$m))
						  $m = $boucle->id_table . ".$m";
					  $m .= " AS titre_rang";

					  $boucle->select[] = $m;
					  $_titre = '$Pile[$SP][\'titre_rang\']';
				  }
			  }
			}
			if (!$_titre)
				$_titre = champ_sql('titre', $p);
			$_rang = "recuperer_numero($_titre)";
		}
		$p->code = $_rang;
		$p->interdire_scripts = false;
	}
	return $p;
}


// #POPULARITE
// http://www.spip.net/fr_article1846.html
// http://doc.spip.org/@balise_POPULARITE_dist
function balise_POPULARITE_dist ($p) {
	$_popularite = champ_sql('popularite', $p);
	$p->code = "(ceil(min(100, 100 * $_popularite
	/ max(1 , 0 + \$GLOBALS['meta']['popularite_max']))))";
	$p->interdire_scripts = false;
	return $p;
}

/**
 * Code de compilation pour la balise `#PAGINATION`
 *
 * Le code produit est trompeur, car les modèles ne fournissent pas Pile[0].
 * On produit un appel à `_request` si on ne l'a pas, mais c'est inexact:
 * l'absence peut-être due à une faute de frappe dans le contexte inclus.
 */
define('CODE_PAGINATION',
	'%s($Numrows["%s"]["grand_total"],
 		%s,
		isset($Pile[0][%4$s])?$Pile[0][%4$s]:intval(_request(%4$s)),
		%5$s, %6$s, %7$s, %8$s, array(%9$s))');

/**
 * Compile la balise `#PAGINATION` chargée d'afficher une pagination
 *
 * Elle charge le modèle `pagination.html` (par défaut), mais un paramètre
 * permet d'indiquer d'autres modèles. `#PAGINATION{nom}` utilisera le
 * modèle `pagination_nom.html`.
 *
 * Cette balise nécessite le critère `pagination` sur la boucle où elle
 * est utilisée.
 *
 * @balise PAGINATION
 * @link http://www.spip.net/3367 Le système de pagination
 * @see filtre_pagination_dist()
 * @see critere_pagination_dist()
 * @see balise_ANCRE_PAGINATION_dist()
 * @example
 *    ```
 *    [<p class="pagination">(#PAGINATION{prive})</p>]
 *    ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @param string $liste
 *     Afficher ou non les liens de pagination (variable de type `string`
 *     car code à faire écrire au compilateur) :
 *     - `true` pour les afficher
 *     - `false` pour afficher uniquement l'ancre.
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_PAGINATION_dist($p, $liste='true') {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];

	// s'il n'y a pas de nom de boucle, on ne peut pas paginer
	if ($b === '') {
		$msg = array('zbug_champ_hors_boucle',
			array('champ' => $liste ? 'PAGINATION' : 'ANCRE_PAGINATION')
			  );
		erreur_squelette($msg, $p);
		return $p;
	}

	// s'il n'y a pas de mode_partie, c'est qu'on se trouve
	// dans un boucle recursive ou qu'on a oublie le critere {pagination}
	if (!$p->boucles[$b]->mode_partie) {
		if (!$p->boucles[$b]->table_optionnelle) {
			$msg = array('zbug_pagination_sans_critere',
					array('champ' => '#PAGINATION')
				  );
			erreur_squelette($msg, $p);
		}
		return $p;
	}

	// a priori true
	// si false, le compilo va bloquer sur des syntaxes avec un filtre sans argument qui suit la balise
	// si true, les arguments simples (sans truc=chose) vont degager
	$_contexte = argumenter_inclure($p->param, true, $p, $p->boucles, $p->id_boucle, false, false);
	if (count($_contexte)){
		list($key,$val) = each($_contexte);
		if (is_numeric($key)){
			array_shift($_contexte);
			$__modele = interprete_argument_balise(1,$p);
		}
	}

	if (count($_contexte)){
		$code_contexte = implode(',',$_contexte);
	}
	else
		$code_contexte = '';

	$connect = $p->boucles[$b]->sql_serveur;
	$pas = $p->boucles[$b]->total_parties;
	$f_pagination = chercher_filtre('pagination');
	$type = $p->boucles[$b]->modificateur['debut_nom'];
	$modif = ($type[0]!=="'") ? "'debut'.$type"
	  : ("'debut" .substr($type,1));

	$p->code = sprintf(CODE_PAGINATION, $f_pagination, $b, $type, $modif, $pas, $liste, ((isset($__modele) and $__modele) ? $__modele : "''"), _q($connect), $code_contexte);

	$p->boucles[$b]->numrows = true;
	$p->interdire_scripts = false;
	return $p;
}



/**
 * Compile la balise `#ANCRE_PAGINATION` chargée d'afficher l'ancre
 * de la pagination
 *
 * Cette ancre peut ainsi être placée au-dessus la liste des éléments
 * de la boucle alors qu'on mettra les liens de pagination en-dessous de
 * cette liste paginée.
 * 
 * Cette balise nécessite le critère `pagination` sur la boucle où elle
 * est utilisée.
 * 
 * @balise ANCRE_PAGINATION
 * @link http://www.spip.net/3367 Le système de pagination
 * @link http://www.spip.net/4328 Balise ANCRE_PAGINATION
 * @see critere_pagination_dist()
 * @see balise_PAGINATION_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_ANCRE_PAGINATION_dist($p) {
	if ($f = charger_fonction('PAGINATION', 'balise', true))
		return $f($p, $liste='false');
	else return NULL; // ou une erreur ?
}


/**
 * Compile la balise `#GRAND_TOTAL` qui retourne le nombre total de résultats
 * d'une boucle
 *
 * Cette balise set équivalente à `#TOTAL_BOUCLE` sauf pour les boucles paginées.
 * Dans ce cas elle indique le nombre total d'éléments répondant aux critères
 * hors pagination.
 *
 * @balise GRAND_TOTAL
 * @see balise_GRAND_TOTAL_dist()
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_GRAND_TOTAL_dist($p) {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];
	if ($b === '' || !isset($p->boucles[$b])) {
		$msg = array('zbug_champ_hors_boucle',
				array('champ' => "#$b" . 'TOTAL_BOUCLE')
			   );
		erreur_squelette($msg, $p);
	} else {
		$p->code = "(isset(\$Numrows['$b']['grand_total'])
			? \$Numrows['$b']['grand_total'] : \$Numrows['$b']['total'])";
		$p->boucles[$b]->numrows = true;
		$p->interdire_scripts = false;
	}
	return $p;
}

// Reference a l'URL de la page courante
// Attention dans un INCLURE() ou une balise dynamique on n'a pas le droit de
// mettre en cache #SELF car il peut correspondre a une autre page (attaque XSS)
// (Dans ce cas faire <INCLURE{self=#SELF}> pour differencier les caches.)
// http://www.spip.net/@self
// http://doc.spip.org/@balise_SELF_dist
function balise_SELF_dist($p) {
	$p->code = 'self()';
	$p->interdire_scripts = false;
	return $p;
}


/**
 * Compile la balise `#CHEMIN` qui cherche un fichier dans les chemins
 * connus de SPIP et retourne son chemin complet depuis la racine
 *
 * Signature : `#CHEMIN{chemin/vers/fichier.ext}`
 *
 * Retourne une chaîne vide si le fichier n'est pas trouvé.
 * 
 * @balise CHEMIN
 * @link http://www.spip.net/4332
 * @see find_in_path() Recherche de chemin
 * @example
 *     ```
 *     [<script type="text/javascript" src="(#CHEMIN{javascript/jquery.flot.js})"></script>]
 *     [<link rel="stylesheet" href="(#CHEMIN{css/perso.css}|direction_css)" type="text/css" />]
 *     ```
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_CHEMIN_dist($p) {
	$arg = interprete_argument_balise(1,$p);
	if (!$arg) {
		$msg = array('zbug_balise_sans_argument',	array('balise' => ' CHEMIN'));
		erreur_squelette($msg, $p);
	} else
	  $p->code = 'find_in_path(' . $arg .')';

	#$p->interdire_scripts = true;
	return $p;
}

/**
 * Compile la balise `#CHEMIN_IMAGE` qui cherche une image dans le thème
 * de l'espace privé utilisé par SPIP et retourne son chemin complet depuis
 * la racine
 * 
 * Signature : `#CHEMIN_IMAGE{image.png}`
 *
 * Retourne une chaîne vide si le fichier n'est pas trouvé.
 * 
 * @balise CHEMIN_IMAGE
 * @see chemin_image()
 * @example
 *     ```
 *     #CHEMIN_IMAGE{article-24.png}
 *     ```
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_CHEMIN_IMAGE_dist($p) {
	$arg = interprete_argument_balise(1,$p);
	if (!$arg) {
		$msg = array('zbug_balise_sans_argument', array('balise' => ' CHEMIN_IMAGE'));
		erreur_squelette($msg, $p);
	} else $p->code = 'chemin_image(' . $arg .')';

	#$p->interdire_scripts = true;
	return $p;
}


/**
 * Compile la balise `#ENV` qui permet de récupérer le contexte d'environnement
 * transmis à un squelette.
 *
 * La syntaxe `#ENV{toto, valeur par defaut}`
 * renverra `valeur par defaut` si `$toto` est vide.
 *
 * La recherche de la clé s'appuyant sur la fonction `table_valeur`
 * il est possible de demander un sous élément d'un tableau :
 * `#ENV{toto/sous/element, valeur par defaut}` retournera l'équivalent de
 * `#ENV{toto}|table_valeur{sous/element}` c'est-à-dire en quelque sorte
 * `$env['toto']['sous']['element']` s'il existe, sinon la valeur par défaut.
 *
 * Si le tableau est vide on renvoie `''` (utile pour `#SESSION`)
 *
 * Enfin, la balise utilisée seule `#ENV` retourne le tableau complet
 * de l'environnement. À noter que ce tableau est retourné sérialisé.
 *
 * En standard est appliqué le filtre `entites_html`, mais si l'étoile est
 * utilisée pour désactiver les filtres par défaut, par exemple avec
 * `[(#ENV*{toto})]` , il *faut* s'assurer de la sécurité
 * anti-javascript, par exemple en filtrant avec `safehtml` : `[(#ENV*{toto}|safehtml)]`
 * 
 *
 * @param Champ $p
 *     Pile ; arbre de syntaxe abstrait positionné au niveau de la balise.
 * @param array $src
 *     Tableau dans lequel chercher la clé demandée en paramètre de la balise.
 *     Par defaut prend dans le contexte du squelette.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
**/
function balise_ENV_dist($p, $src = NULL) {

	// cle du tableau desiree
	$_nom = interprete_argument_balise(1,$p);
	// valeur par defaut
	$_sinon = interprete_argument_balise(2,$p);
	
	// $src est un tableau de donnees sources eventuellement transmis
	// en absence, on utilise l'environnement du squelette $Pile[0]
	
	if (!$_nom) {
		// cas de #ENV sans argument : on retourne le serialize() du tableau
		// une belle fonction [(#ENV|affiche_env)] serait pratique
		if ($src) {
			$p->code = '(is_array($a = ('.$src.')) ? serialize($a) : "")';
		} else {
			$p->code = '@serialize($Pile[0])';
		}
	} else {
		if (!$src) {
			$src = '@$Pile[0]';
		}
		if ($_sinon) {
			$p->code = "sinon(table_valeur($src, (string)$_nom, null), $_sinon)";
		} else {
			$p->code = "table_valeur($src, (string)$_nom, null)";
		}
	}
	#$p->interdire_scripts = true;

	return $p;
}

/**
 * Compile la balise `#CONFIG` qui retourne une valeur de configuration
 *
 * Cette balise appelle la fonction `lire_config()` pour obtenir les
 * configurations du site.
 *
 * Par exemple `#CONFIG{gerer_trad}` donne 'oui ou 'non' selon le réglage.
 *
 * Le 3ème argument permet de contrôler la sérialisation du résultat
 * (mais ne sert que pour le dépot `meta`) qui doit parfois désérialiser,
 * par exemple avec `|in_array{#CONFIG{toto,#ARRAY,1}}`. Ceci n'affecte
 * pas d'autres dépots et `|in_array{#CONFIG{toto/,#ARRAY}}` sera
 * équivalent.
 *
 * Òn peut appeler d'autres tables que `spip_meta` avec un
 * `#CONFIG{/infos/champ,defaut}` qui lit la valeur de `champ`
 * dans une table des meta qui serait `spip_infos`
 *
 * @balise CONFIG
 * @link http://www.spip.net/4335
 * 
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 */
function balise_CONFIG_dist($p) {
	if (!$arg = interprete_argument_balise(1,$p)) {
		$arg = "''";
	}
	$_sinon = interprete_argument_balise(2,$p);
	$_unserialize = sinon(interprete_argument_balise(3,$p),"false");

	$p->code = '(include_spip(\'inc/config\')?lire_config(' . $arg . ',' .
		($_sinon && $_sinon != "''" ? $_sinon : 'null') . ',' . $_unserialize . "):'')";

	return $p;
}


/**
 * Compile la balise `#CONNECT` qui retourne le nom du connecteur
 * de base de données
 *
 * Retourne le nom du connecteur de base de données utilisé (le nom
 * du fichier `config/xx.php` sans l'extension, utilisé pour calculer
 * les données du squelette).
 *
 * Retourne `NULL` si le connecteur utilisé est celui par défaut de SPIP
 * (connect.php), sinon retourne son nom.
 *
 * @balise CONNECT
 * 
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 */
function balise_CONNECT_dist($p) {
	$p->code = '($connect ? $connect : NULL)';
	$p->interdire_scripts = false;
	return $p;
}

//
// #SESSION
// Cette balise est un tableau des donnees du visiteur (nom, email etc)
// Si elle est invoquee, elle leve un drapeau dans le fichier cache, qui
// permet a public/cacher d'invalider le cache si le visiteur suivant n'a
// pas la meme session
// http://doc.spip.org/@balise_SESSION_dist
function balise_SESSION_dist($p) {
	$p->descr['session'] = true;

	$f = function_exists('balise_ENV')
		? 'balise_ENV'
		: 'balise_ENV_dist';

	$p = $f($p, '$GLOBALS["visiteur_session"]');
	return $p;
}

//
// #SESSION_SET{x,y}
// Ajoute x=y dans la session du visiteur
// http://doc.spip.org/@balise_SESSION_SET_dist
function balise_SESSION_SET_dist($p) {
	$_nom = interprete_argument_balise(1,$p);
	$_val = interprete_argument_balise(2,$p);
	if (!$_nom OR !$_val) {
		$err_b_s_a = array('zbug_balise_sans_argument', array('balise' => 'SESSION_SET'));
		erreur_squelette($err_b_s_a, $p);
	} else 	$p->code = '(include_spip("inc/session") AND session_set('.$_nom.','.$_val.'))';

	$p->interdire_scripts = false;

	return $p;
}




//
// #EVAL{...}
// evalue un code php ; a utiliser avec precaution :-)
//
// rq: #EVAL{code} produit eval('return code;')
// mais si le code est une expression sans balise, on se dispense
// de passer par une construction si compliquee, et le code est
// passe tel quel (entre parentheses, et protege par interdire_scripts)
// Exemples : #EVAL**{6+9} #EVAL**{_DIR_IMG_PACK} #EVAL{'date("Y-m-d")'}
// #EVAL{'str_replace("r","z", "roger")'}  (attention les "'" sont interdits)
// http://doc.spip.org/@balise_EVAL_dist
function balise_EVAL_dist($p) {
	$php = interprete_argument_balise(1,$p);
	if ($php) {
		# optimisation sur les #EVAL{une expression sans #BALISE}
		# attention au commentaire "// x signes" qui precede
		if (preg_match(",^([[:space:]]*//[^\n]*\n)'([^']+)'$,ms",
		$php,$r))
			$p->code = /* $r[1]. */'('.$r[2].')';
		else
			$p->code = "eval('return '.$php.';')";
	} else {
		$msg = array('zbug_balise_sans_argument', array('balise' => ' EVAL'));
		erreur_squelette($msg, $p);
	}

	#$p->interdire_scripts = true;

	return $p;
}


/**
 * Compile la balise `#CHAMP_SQL` qui renvoie la valeur d'un champ SQL
 *
 * Signature : `#CHAMP_SQL{champ}`
 *
 * Cette balise permet de récupérer par exemple un champ `notes` dans une table
 * SQL externe (impossible avec la balise `#NOTES` qui est une balise calculée).
 *
 * Ne permet pas de passer une expression comme argument, qui ne peut
 * être qu'un texte statique !
 *
 * @balise CHAMP_SQL
 * @link http://www.spip.net/4041
 * @see champ_sql()
 * @example
 *     ```
 *     #CHAMP_SQL{notes}
 *     ```
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_CHAMP_SQL_dist($p){

	if ($p->param
	AND isset($p->param[0][1][0])
	AND $champ = ($p->param[0][1][0]->texte))
		$p->code = champ_sql($champ, $p);
	else {
		$err_b_s_a = array('zbug_balise_sans_argument', array('balise' => ' URL_'));
		erreur_squelette($err_b_s_a, $p);
	}
	#$p->interdire_scripts = true;
	return $p;
}

// #VAL{x} renvoie 'x' (permet d'appliquer un filtre a une chaine)
// Attention #VAL{1,2} renvoie '1', indiquer #VAL{'1,2'}
// http://doc.spip.org/@balise_VAL_dist
function balise_VAL_dist($p){
	$p->code = interprete_argument_balise(1,$p);
	if (!strlen($p->code))
		$p->code = "''";
	$p->interdire_scripts = false;
	return $p;
}
// #NOOP est un alias pour regler #948, ne pas documenter
// http://doc.spip.org/@balise_NOOP_dist
function balise_NOOP_dist($p) { return balise_VAL_dist($p); }

//
// #REM
// pour les remarques : renvoie toujours ''
//
// http://doc.spip.org/@balise_REM_dist
function balise_REM_dist($p) {
	$p->code="''";
	$p->interdire_scripts = false;
	return $p;
}


//
// #HTTP_HEADER
// pour les entetes de retour http
// Ne fonctionne pas sur les INCLURE !
// #HTTP_HEADER{Content-Type: text/css}
//
// http://doc.spip.org/@balise_HTTP_HEADER_dist
function balise_HTTP_HEADER_dist($p) {

	$header = interprete_argument_balise(1,$p);
	if (!$header) {
		$err_b_s_a = array('zbug_balise_sans_argument', array('balise' => 'HTTP_HEADER'));
		erreur_squelette($err_b_s_a, $p);
	} else 	$p->code = "'<'.'?php header(\"' . "
		. $header
		. " . '\"); ?'.'>'";
	$p->interdire_scripts = false;
	return $p;
}

// Filtre a appliquer a l'ensemble de la page une fois calculee
// (filtrage fait au niveau du squelette, et sans s'appliquer aux <INCLURE>)
// http://doc.spip.org/@balise_FILTRE_dist
function balise_FILTRE_dist($p) {
	if ($p->param) {
		$args = array();
		foreach ($p->param as $i => $ignore)
			$args[] = interprete_argument_balise($i+1,$p);
		$p->code = "'<' . '"
			.'?php header("X-Spip-Filtre: \'.'
				.join('.\'|\'.', $args)
			. " . '\"); ?'.'>'";

		$p->interdire_scripts = false;
		return $p;
	}
}


/**
 * Compile la balise `#CACHE` definissant la durée de validité du cache du squelette
 *
 * Signature : `#CACHE{duree[,type]}`
 *
 * Le premier argument est la durée en seconde du cache. Le second
 * (par défaut `statique`) indique le type de cache :
 *
 * - `cache-client` autorise gestion du IF_MODIFIED_SINCE
 * - `statique` ne respecte pas l'invalidation par modif de la base
 *   (mais s'invalide tout de même à l'expiration du delai)
 *
 * @balise CACHE
 * @see ecrire/public/cacher.php
 * @link http://www.spip.net/4330
 * @example
 *     ```
 *     #CACHE{24*3600}
 *     #CACHE{24*3600, cache-client}
 *     #CACHE{0} pas de cache
 *     ```
 * @note
 *   En absence de cette balise la durée est du cache est donné
 *   par la constante `_DUREE_CACHE_DEFAUT`
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_CACHE_dist($p) {

	if ($p->param) {
		$duree = valeur_numerique($p->param[0][1][0]->texte);

		// noter la duree du cache dans un entete proprietaire

		$code = '\'<'.'?php header("X-Spip-Cache: '
		. $duree
		. '"); ?'.'>\'';

		// Remplir le header Cache-Control
		// cas #CACHE{0}
		if ($duree == 0)
			$code .= '.\'<'
			.'?php header("Cache-Control: no-cache, must-revalidate"); ?'
			.'><'
			.'?php header("Pragma: no-cache"); ?'
			.'>\'';

		// recuperer les parametres suivants
		$i = 1;
		while (isset($p->param[0][++$i])) {
			$pa = ($p->param[0][$i][0]->texte);

			if ($pa == 'cache-client'
			AND $duree > 0) {
				$code .= '.\'<'.'?php header("Cache-Control: max-age='
				. $duree
				. '"); ?'.'>\'';
			// il semble logique, si on cache-client, de ne pas invalider
				$pa = 'statique';
			}

			if ($pa == 'statique'
			AND $duree > 0)
				$code .= '.\'<'.'?php header("X-Spip-Statique: oui"); ?'.'>\'';
		}
	} else $code = "''";
	$p->code = $code;
	$p->interdire_scripts = false;
	return $p;
}


/**
 * Compile la balise `#INSERT_HEAD` permettant d'insérer du contenu dans
 * le `<head>` d'une page HTML
 * 
 * La balise permet aux plugins d'insérer des styles, js ou autre
 * dans l'entête sans modification du squelette.
 * Les css doivent être inserées de préférence par `#INSERT_HEAD_CSS`
 * pour en faciliter la surcharge.
 *
 * On insère ici aussi un morceau de PHP qui verifiera à l'exécution
 * que le pipeline `insert_head_css` a bien été vu
 * et dans le cas contraire l'appelera. Ceal permet de ne pas oublier
 * les css de `#INSERT_HEAD_CSS` même si cette balise n'est pas presente.
 * 
 * Il faut mettre ce php avant le `insert_head` car le compresseur y mets
 * ensuite un php du meme type pour collecter
 * CSS et JS, et on ne veut pas qu'il rate les css insérées en fallback
 * par `insert_head_css_conditionnel`.
 *
 * @link http://www.spip.net/4629
 * @see balise_INSERT_HEAD_CSS_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_INSERT_HEAD_dist($p) {
	$p->code = '\'<'
		.'?php header("X-Spip-Filtre: \'.'
			.'\'insert_head_css_conditionnel\''
		. " . '\"); ?'.'>'";
	$p->code .= ". pipeline('insert_head','<!-- insert_head -->')";
	$p->interdire_scripts = false;
	return $p;
}

/**
 * Compile la balise `#INSERT_HEAD_CSS` homologue de `#INSERT_HEAD` pour les CSS
 * 
 * Et par extension pour le JS inline qui doit préférentiellement
 * être inséré avant les CSS car bloquant sinon.
 *
 * @link http://www.spip.net/4605
 * @see balise_INSERT_HEAD_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_INSERT_HEAD_CSS_dist($p) {
	$p->code = "pipeline('insert_head_css','<!-- insert_head_css -->')";
	$p->interdire_scripts = false;
	return $p;
}
//
// #INCLURE statique
// l'inclusion est realisee au calcul du squelette, pas au service
// ainsi le produit du squelette peut etre utilise en entree de filtres a suivre
// on peut faire un #INCLURE{fichier} sans squelette
// (Incompatible avec les balises dynamiques)
// http://doc.spip.org/@balise_INCLUDE_dist
function balise_INCLUDE_dist($p) {
	if(function_exists('balise_INCLURE'))
		return balise_INCLURE($p);
	else
		return balise_INCLURE_dist($p);
}
// http://doc.spip.org/@balise_INCLURE_dist
function balise_INCLURE_dist($p) {
	$id_boucle = $p->id_boucle;
	// la lang n'est pas passe de facon automatique par argumenter
	// mais le sera pas recuperer_fond, sauf si etoile=>true est passe
	// en option

	$_contexte = argumenter_inclure($p->param, true, $p, $p->boucles, $id_boucle, false, false);

	// erreur de syntaxe = fond absent
	// (2 messages d'erreur SPIP pour le prix d'un, mais pas d'erreur PHP
	if (!$_contexte) $contexte = array();

	if (isset($_contexte['fond'])) {

		$f = $_contexte['fond'];
		// toujours vrai :
		if (preg_match('/^.fond.\s*=>(.*)$/s', $f, $r)) {
			$f = $r[1];
			unset($_contexte['fond']);
		} else spip_log("compilation de #INCLURE a revoir");

		// #INCLURE{doublons}
		if (isset($_contexte['doublons'])) {
			$_contexte['doublons'] = "'doublons' => \$doublons";
		}

		// Critere d'inclusion {env} (et {self} pour compatibilite ascendante)
		if (isset($_contexte['env'])
		|| isset($_contexte['self'])
		) {
			$flag_env = true;
			unset($_contexte['env']);
		} else $flag_env = false;

		$_options = array();
		if (isset($_contexte['ajax'])) {
			$_options[] = preg_replace(",=>(.*)$,ims",'=> ($v=(\\1))?$v:true',$_contexte['ajax']);
			unset($_contexte['ajax']);
		}
		if ($p->etoile) $_options[] = "'etoile'=>true";
		$_options[] = "'compil'=>array(" . memoriser_contexte_compil($p) .")";

		$_l = 'array(' . join(",\n\t", $_contexte) .')';
		if ($flag_env) $_l = "array_merge(\$Pile[0],$_l)";

		$p->code = sprintf(CODE_RECUPERER_FOND, $f, $_l, join(',',$_options),"''");

	} elseif (!isset($_contexte[1])) {
			$msg = array('zbug_balise_sans_argument', array('balise' => ' INCLURE'));
			erreur_squelette($msg, $p);
	} else 		$p->code = '(($c = find_in_path(' . $_contexte[1] . ')) ? spip_file_get_contents($c) : "")';

	$p->interdire_scripts = false; // la securite est assuree par recuperer_fond
	return $p;
}

// Inclure un modele : #MODELE{modele, params}
// http://doc.spip.org/@balise_MODELE_dist
function balise_MODELE_dist($p) {

	$_contexte = argumenter_inclure($p->param, true, $p, $p->boucles, $p->id_boucle, false);

	// erreur de syntaxe = fond absent
	// (2 messages d'erreur SPIP pour le prix d'un, mais pas d'erreur PHP
	if (!$_contexte) $contexte = array();

	if (!isset($_contexte[1])) {
		$msg = array('zbug_balise_sans_argument', array('balise' => ' MODELE'));
		erreur_squelette($msg, $p);
	} else {
		$nom = $_contexte[1];
		unset($_contexte[1]);

		if (preg_match("/^\s*'[^']*'/s", $nom))
			$nom = "'modeles/" . substr($nom,1);
		else $nom = "'modeles/' . $nom";

		// Incoherence dans la syntaxe du contexte. A revoir.
		// Reserver la cle primaire de la boucle courante si elle existe
		if (isset($p->boucles[$p->id_boucle]->primary)) {
			$primary = $p->boucles[$p->id_boucle]->primary;
			if (!strpos($primary,',')) {
				$id = champ_sql($primary, $p);
				$_contexte[] = "'$primary'=>".$id;
				$_contexte[] = "'id'=>".$id;
			}
		}
		$_contexte[] = "'recurs'=>(++\$recurs)";
		$connect = '';
		if (isset($p->boucles[$p->id_boucle]))
			$connect = $p->boucles[$p->id_boucle]->sql_serveur;

		$_options = memoriser_contexte_compil($p);
		$_options = "'compil'=>array($_options), 'trim'=>true";
	  if (isset($_contexte['ajax'])){
		  $_options .= ", ".preg_replace(",=>(.*)$,ims",'=> ($v=(\\1))?$v:true',$_contexte['ajax']);
			unset($_contexte['ajax']);
	  }

		$page = sprintf(CODE_RECUPERER_FOND, $nom, 'array(' . join(',', $_contexte) .')', $_options, _q($connect));

		$p->code = "\n\t(((\$recurs=(isset(\$Pile[0]['recurs'])?\$Pile[0]['recurs']:0))>=5)? '' :\n\t$page)\n";

		$p->interdire_scripts = false; // securite assuree par le squelette
	}

	return $p;
}

//
// #SET
// Affecte une variable locale au squelette
// #SET{nom,valeur}
// la balise renvoie la valeur
// http://doc.spip.org/@balise_SET_dist
function balise_SET_dist($p){
	$_nom = interprete_argument_balise(1,$p);
	$_val = interprete_argument_balise(2,$p);

	if (!$_nom OR !$_val) {
		$err_b_s_a = array('zbug_balise_sans_argument', array('balise' => 'SET'));
		erreur_squelette($err_b_s_a, $p);
	} else 	$p->code = "vide(\$Pile['vars'][(string)$_nom] = $_val)";

	$p->interdire_scripts = false; // la balise ne renvoie rien
	return $p;
}

//
// #GET
// Recupere une variable locale au squelette
// #GET{nom,defaut} renvoie defaut si la variable nom n'a pas ete affectee
//
// http://doc.spip.org/@balise_GET_dist
function balise_GET_dist($p) {
	$p->interdire_scripts = false; // le contenu vient de #SET, donc il est de confiance
	if (function_exists('balise_ENV'))
		return balise_ENV($p, '$Pile["vars"]');
	else
		return balise_ENV_dist($p, '$Pile["vars"]');
}


/**
 * Compile la balise `#DOUBLONS` qui redonne les doublons enregistrés
 * 
 * - `#DOUBLONS{mots}` ou `#DOUBLONS{mots,famille}`
 *   donne l'état des doublons `(MOTS)` à cet endroit
 *   sous forme de tableau d'id_mot comme `array(1,2,3,...)`
 * - `#DOUBLONS` tout seul donne la liste brute de tous les doublons
 * - `#DOUBLONS*{mots}` donne la chaine brute `,1,2,3,...`
 *   (changera si la gestion des doublons evolue)
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_DOUBLONS_dist($p) {
	if ($type = interprete_argument_balise(1,$p)) {
		if ($famille = interprete_argument_balise(2,$p))
			$type .= '.' . $famille;
		$p->code = '$doublons['.$type.']';
		if (!$p->etoile)
			$p->code = 'array_filter(array_map("intval",explode(",",'
				. $p->code . ')))';
	}
	else
		$p->code = '$doublons';

	$p->interdire_scripts = false;

	return $p;
}


//
// #PIPELINE
// pour permettre aux plugins d'inserer des sorties de pipeline dans un squelette
// #PIPELINE{insert_body}
// #PIPELINE{insert_body,flux}
//
// http://doc.spip.org/@balise_PIPELINE_dist
function balise_PIPELINE_dist($p) {
	$_pipe = interprete_argument_balise(1,$p);
	if (!$_pipe) {
		$err_b_s_a = array('zbug_balise_sans_argument', array('balise' => 'PIPELINE'));
		erreur_squelette($err_b_s_a, $p);
	} else {
		$_flux = interprete_argument_balise(2,$p);
		$_flux = $_flux?$_flux:"''";
		$p->code = "pipeline( $_pipe , $_flux )";
		$p->interdire_scripts = false;
	}
	return $p;
}

//
// #EDIT
// une balise qui ne fait rien, pour surcharge par le plugin widgets
//
// http://doc.spip.org/@balise_EDIT_dist
function balise_EDIT_dist($p) {
	$p->code = "''";
	$p->interdire_scripts = false;
	return $p;
}


//
// #TOTAL_UNIQUE
// pour recuperer le nombre d'elements affiches par l'intermediaire du filtre
// |unique
// usage:
// #TOTAL_UNIQUE afiche le nombre de #BALISE|unique
// #TOTAL_UNIQUE{famille} afiche le nombre de #BALISE|unique{famille}
//
// http://doc.spip.org/@balise_TOTAL_UNIQUE_dist
function balise_TOTAL_UNIQUE_dist($p) {
	$_famille = interprete_argument_balise(1,$p);
	$_famille = $_famille ? $_famille : "''";
	$p->code = "unique('', $_famille, true)";
	return $p;
}

/**
 * Compile la balise `#ARRAY` créant un tableau PHP associatif
 *
 * Crée un `array` PHP à partir d'arguments calculés.
 * Chaque paire d'arguments représente la clé et la valeur du tableau.
 * 
 * @balise ARRAY
 * @link http://www.spip.net/4009
 * @example
 *     ```
 *     #ARRAY{key1,val1,key2,val2 ...} retourne
 *     array( key1 => val1, key2 => val2, ...)
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_ARRAY_dist($p) {
	$_code = array();
	$n=1;
	do {
		$_key = interprete_argument_balise($n++,$p);
		$_val = interprete_argument_balise($n++,$p);
		if ($_key AND $_val) $_code[] = "$_key => $_val";
	} while ($_key && $_val);
	$p->code = 'array(' . join(', ',$_code).')';
	$p->interdire_scripts = false;
	return $p;
}

/**
 * Compile la balise `#LISTE` qui crée un tableau PHP avec les valeurs, sans préciser les clés
 *
 * @balise LISTE
 * @link http://www.spip.net/5547
 * @example
 *    ```
 *    #LISTE{a,b,c,d,e} 
 *    ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_LISTE_dist($p) {
	$_code = array();
	$n=1;
	while ($_val = interprete_argument_balise($n++,$p))
		$_code[] = $_val;
	$p->code = 'array(' . join(', ',$_code).')';
	$p->interdire_scripts = false;
	return $p;
}


/**
 * Compile la balise `#AUTORISER` qui teste une autorisation
 *
 * Appelle la fonction `autoriser()` avec les mêmes arguments,
 * et renvoie un espace ' ' si OK (l'action est autorisée),
 * sinon une chaine vide '' (l'action n'est pas autorisée).
 *
 * Cette balise créée un cache par session.
 *
 * Signature : `#AUTORISER{faire[,type[,id[,auteur[,options]]]}`
 * 
 * @note
 *     La priorité des opérateurs exige && plutot que AND
 * 
 * @balise AUTORISER
 * @link http://www.spip.net/3896
 * @see autoriser()
 * @see sinon_interdire_acces()
 * @example
 *    ```
 *    [(#AUTORISER{modifier,rubrique,#ID_RUBRIQUE}) ... ]
 *    [(#AUTORISER{voir,rubrique,#ID_RUBRIQUE}|sinon_interdire_acces)]
 *    ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_AUTORISER_dist($p) {
	$_code = array();
	$p->descr['session'] = true; // faire un cache par session

	$n=1;
	while ($_v = interprete_argument_balise($n++,$p))
		$_code[] = $_v;

	$p->code = '((function_exists("autoriser")||include_spip("inc/autoriser"))&&autoriser(' . join(', ',$_code).')?" ":"")';
	$p->interdire_scripts = false;
	return $p;
}

// Appelle la fonction info_plugin
// Afficher des informations sur les plugins dans le site public
// http://doc.spip.org/@balise_PLUGIN_dist
function balise_PLUGIN_dist($p) {
	$plugin = interprete_argument_balise(1,$p);
	$plugin = isset($plugin) ? str_replace('\'', '"', $plugin) : '""';
	$type_info = interprete_argument_balise(2,$p);
	$type_info = isset($type_info) ? str_replace('\'', '"', $type_info) : '"est_actif"';

	$f = chercher_filtre('info_plugin');
	$p->code = $f.'('.$plugin.', '.$type_info.')';
	return $p;
}

/**
 * Compile la balise `#AIDER` qui permet d’afficher l’icone de l’aide
 * au sein des squelettes.
 *
 * @balise AIDER
 * @see inc_aider_dist()
 * @link http://www.spip.net/4733
 * @example
 *     ```
 *     #AIDER{titre}
 *     ```
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_AIDER_dist($p) {
	$_motif = interprete_argument_balise(1,$p);
	$s = "'" . addslashes($p->descr['sourcefile']) . "'";
	$aider = charger_fonction('aider','inc');
	$p->code = "((\$aider=charger_fonction('aider','inc'))?\$aider($_motif,$s, \$Pile[0]):'')";
	return $p;
}

/**
 * Compile la balise `#ACTION_FORMULAIRE` qui insère le contexte
 * des formulaires charger / vérifier / traiter avec les hidden de
 * l'URL d'action
 *
 * @balise ACTION_FORMULAIRE
 * @example
 *     ```
 *     <form method='post' action='#ENV{action}'><div>
 *     #ACTION_FORMULAIRE{#ENV{action}}
 *     ```
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_ACTION_FORMULAIRE($p){
	if (!$_url = interprete_argument_balise(1,$p))
		$_url = "@\$Pile[0]['action']";
	if (!$_form = interprete_argument_balise(2,$p))
		$_form = "@\$Pile[0]['form']";

	// envoyer le nom du formulaire que l'on traite
	// transmettre les eventuels args de la balise formulaire
	$p->code = "	'<div>' .
	form_hidden($_url) .
	'<input name=\'formulaire_action\' type=\'hidden\'
		value=\'' . $_form . '\' />' .
	'<input name=\'formulaire_action_args\' type=\'hidden\'
		value=\'' . @\$Pile[0]['formulaire_args']. '\' />' .
	(@\$Pile[0]['_hidden']?@\$Pile[0]['_hidden']:'') .
	'</div>'";

	$p->interdire_scripts = false;
	return $p;
}


/**
 * Compile la balise `#BOUTON_ACTION` qui génère un bouton d'action en post, ajaxable
 * 
 * Cette balise s'utilise à la place des liens `action_auteur`, sous la forme
 *
 * - `#BOUTON_ACTION{libelle,url}`
 * - ou `#BOUTON_ACTION{libelle,url,ajax}` pour que l'action soit ajax comme un lien `class='ajax'`
 * - ou `#BOUTON_ACTION{libelle,url,ajax,message_confirmation}` pour utiliser un message de confirmation
 * - ou encore `#BOUTON_ACTION{libelle[,url[,ajax[,message_confirmation[,title[,callback]]]]]}`
 *
 * @balise BOUTON_ACTION
 * @link http://www.spip.net/4583
 * @example
 *     ```
 *     [(#AUTORISER{reparer,base})
 *        [(#BOUTON_ACTION{<:bouton_tenter_recuperation:>,#URL_ECRIRE{base_repair}})]
 *     ]
 *     ```
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_BOUTON_ACTION_dist($p){

	$args = array();
	for ($k=1;$k<=6;$k++){
		$_a = interprete_argument_balise($k,$p);
		if (!$_a) $_a="''";
	  $args[] = $_a;
	}
	// supprimer les args vides
	while(end($args)=="''" AND count($args)>2)
		array_pop($args);
	$args = implode(",",$args);

	$bouton_action = chercher_filtre("bouton_action");
	$p->code = "$bouton_action($args)";
	$p->interdire_scripts = false;
	return $p;
}



function balise_SLOGAN_SITE_SPIP_dist($p) {
	$p->code = "\$GLOBALS['meta']['slogan_site']";
	#$p->interdire_scripts = true;
	return $p;
}

// #HTML5
// Renvoie ' ' si le webmestre souhaite que SPIP genere du code (X)HTML5 sur
// le site public, et '' si le code doit etre strictement compatible HTML4
// http://doc.spip.org/@balise_HTML5_dist
function balise_HTML5_dist($p) {
	$p->code = html5_permis() ? "' '" : "''";
	$p->interdire_scripts = false;
	return $p;
}



/**
 * Compile la balise `#TRI` permettant d'afficher un lien de changement d'ordre de tri
 * d'une colonne de la boucle
 * 
 * La balise `#TRI{champ[,libelle]}` champ prend `>` ou `<` pour afficher
 * le lien de changement de sens croissant ou decroissant (`>` `<` indiquent
 * un sens par une flèche)
 *
 * @balise TRI
 * @example
 *     ```
 *     <th>[(#TRI{titre,<:info_titre:>,ajax})]</th>
 *     ```
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @param string $liste
 *     Inutilisé
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_TRI_dist($p, $liste='true') {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];

	// s'il n'y a pas de nom de boucle, on ne peut pas trier
	if ($b === '') {
		erreur_squelette(
			_T('zbug_champ_hors_boucle',
				array('champ' => '#TRI')
			), $p->id_boucle);
		$p->code = "''";
		return $p;
	}
	$boucle = $p->boucles[$b];

	// s'il n'y a pas de tri_champ, c'est qu'on se trouve
	// dans un boucle recursive ou qu'on a oublie le critere {tri}
	if (!isset($boucle->modificateur['tri_champ'])) {
		erreur_squelette(
			_T('zbug_tri_sans_critere',
				array('champ' => '#TRI')
			), $p->id_boucle);
		$p->code = "''";
		return $p;
	}

	$_champ = interprete_argument_balise(1,$p);
	// si pas de champ, renvoyer le critere de tri utilise
	if (!$_champ){
		$p->code = $boucle->modificateur['tri_champ'];
		return $p;
	}
	// forcer la jointure si besoin, et si le champ est statique
	if (preg_match(",^'([\w.]+)'$,i",$_champ,$m)){
		index_pile($b, $m[1], $p->boucles);
	}

	$_libelle = interprete_argument_balise(2,$p);
	$_libelle = $_libelle?$_libelle:$_champ;

	$_class = interprete_argument_balise(3,$p);
	// si champ = ">" c'est un lien vers le tri croissant : de gauche a droite ==> 1
	// si champ = "<" c'est un lien vers le tri decroissant : (sens inverse) == -1
	$_issens = "in_array($_champ,array('>','<'))";
	$_sens = "(strpos('< >',$_champ)-1)";

	$_variable = "((\$s=$_issens)?'sens':'tri').".$boucle->modificateur['tri_nom'];
	$_url = "parametre_url(self(),$_variable,\$s?$_sens:$_champ)";
	$_on = "\$s?(".$boucle->modificateur['tri_sens']."==$_sens".'):('.$boucle->modificateur['tri_champ']."==$_champ)";

	$p->code = "lien_ou_expose($_url,$_libelle,$_on".($_class?",$_class":"").")";
	//$p->code = "''";
	$p->interdire_scripts = false;
	return $p;
}


/**
 * Compile la balise `#SAUTER{n}` qui permet de sauter en avant n resultats dans une boucle
 * 
 * La balise modifie le compteur courant de la boucle, mais pas les autres
 * champs qui restent les valeurs de la boucle avant le saut. Il est donc
 * preferable d'utiliser la balise juste avant la fermeture `</BOUCLE>`
 *
 * L'argument `n` doit être supérieur à zéro sinon la balise ne fait rien
 *
 * @balise SAUTER
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_SAUTER_dist($p){
	$id_boucle = $p->id_boucle;
	$boucle = $p->boucles[$id_boucle];

	if (!$boucle) {
		$msg = array('zbug_champ_hors_boucle', array('champ' => '#SAUTER'));
		erreur_squelette($msg, $p);
	}
	else {
		$_saut = interprete_argument_balise(1,$p);
		$_compteur = "\$Numrows['$id_boucle']['compteur_boucle']";
		$_total = "\$Numrows['$id_boucle']['total']";

		$p->code = "vide($_compteur=\$iter->skip($_saut,$_total))";
	}
	$p->interdire_scripts = false;
	return $p;
}


/**
 * Compile la balise `#PUBLIE` qui indique si un objet est publié ou non
 *
 * @balise PUBLIE
 * @link http://www.spip.net/5545
 * @see objet_test_si_publie()
 * @example
 *     ```
 *     #PUBLIE : porte sur la boucle en cours
 *     [(#PUBLIE{article, 3}|oui) ... ] : pour l'objet indiqué
 *     ```
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_PUBLIE_dist($p) {
	if (!$_type = interprete_argument_balise(1,$p)){
		$_type = _q($p->type_requete);
		$_id = champ_sql($p->boucles[$p->id_boucle]->primary,$p);
	}
	else
		$_id = interprete_argument_balise(2,$p);

	$connect = $p->boucles[$p->id_boucle]->sql_serveur;

	$p->code = "(objet_test_si_publie(".$_type.",intval(".$_id."),"._q($connect).")?' ':'')";
	$p->interdire_scripts = false;
	return $p;
}

/**
 * Compile la balise `#PRODUIRE` qui génère un fichier statique à partir
 * d'un squelette SPIP
 *
 * Le format du fichier sera extrait de la pre-extension du squelette
 * (typo.css.html, messcripts.js.html)
 * ou par l'argument `format=css` ou `format=js` passé en argument.
 *
 * S'il n'y a pas de format détectable, on utilise `.html`, comme pour les squelettes.
 *
 * La syntaxe de la balise est la même que celle de `#INCLURE`.
 *
 * @balise PRODUIRE
 * @see balise_INCLURE_dist()
 * @link http://www.spip.net/5505
 * @example
 *     ```
 *     <link rel="stylesheet" type="text/css" href="#PRODUIRE{fond=css/macss.css,couleur=ffffff}" />
 *     ```
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_PRODUIRE_dist($p){
	$balise_inclure = charger_fonction('INCLURE','balise');
	$p = $balise_inclure($p);

	$p->code = str_replace('recuperer_fond(','produire_fond_statique(',$p->code);

	return $p;
}

/**
 * Compile la balise `#LARGEUR_ECRAN` qui définit la largeur d'écran
 * dans l'espace privé
 *
 * @balise LARGEUR_ECRAN
 * @example
 *     ```
 *     #LARGEUR_ECRAN{pleine_largeur}
 *     ```
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_LARGEUR_ECRAN_dist($p){
	$_class = interprete_argument_balise(1,$p);
	if (!$_class) $_class='null';
	$p->code = "(is_string($_class)?vide(\$GLOBALS['largeur_ecran']=$_class):(isset(\$GLOBALS['largeur_ecran'])?\$GLOBALS['largeur_ecran']:''))";
	return $p;
}
?>
