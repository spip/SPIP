<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

use Spip\Core\Champ;

/**
 * Ce fichier regroupe la quasi totalité des définitions de `#BALISES` de SPIP.
 *
 * Pour chaque balise, il est possible de surcharger, dans son fichier
 * mes_fonctions.php, la fonction `balise_TOTO_dist()` par une fonction
 * `balise_TOTO()` respectant la même API : elle reçoit en entrée un objet
 * de classe `Champ`, le modifie et le retourne. Cette classe est définie
 * dans public/interfaces.
 *
 * Des balises dites «dynamiques» sont également déclarées dans le
 * répertoire ecrire/balise/
 *
 * @package SPIP\Core\Compilateur\Balises
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Retourne le code PHP d'un argument de balise s'il est présent
 *
 * @uses calculer_liste()
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
function interprete_argument_balise(int $n, Champ $p): ?string {
	if (($p->param) && (!$p->param[0][0]) && ((is_countable($p->param[0]) ? count($p->param[0]) : 0) > $n)) {
		return calculer_liste(
			$p->param[0][$n],
			$p->descr,
			$p->boucles,
			$p->id_boucle
		);
	} else {
		return null;
	}
}


//
// Définition des balises
//

/**
 * Compile la balise `#NOM_SITE_SPIP` retournant le nom du site
 *
 * @balise
 * @link https://www.spip.net/4622
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_NOM_SITE_SPIP_dist($p) {
	$p->code = "\$GLOBALS['meta']['nom_site']";

	#$p->interdire_scripts = true;
	return $p;
}

/**
 * Compile la balise `#EMAIL_WEBMASTER` retournant l'adresse courriel
 * du webmestre
 *
 * @balise
 * @link https://www.spip.net/4586
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_EMAIL_WEBMASTER_dist($p) {
	$p->code = "\$GLOBALS['meta']['email_webmaster']";

	#$p->interdire_scripts = true;
	return $p;
}

/**
 * Compile la balise `#DESCRIPTIF_SITE_SPIP` qui retourne le descriptif
 * du site !
 *
 * @balise
 * @link https://www.spip.net/4338
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_DESCRIPTIF_SITE_SPIP_dist($p) {
	$p->code = "\$GLOBALS['meta']['descriptif_site']";

	#$p->interdire_scripts = true;
	return $p;
}


/**
 * Compile la balise `#CHARSET` qui retourne le nom du jeu de caractères
 * utilisé par le site tel que `utf-8`
 *
 * @balise
 * @link https://www.spip.net/4331
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

/**
 * Compile la balise `#LANG_LEFT` retournant 'left' si la langue s'écrit
 * de gauche à droite, sinon 'right'
 *
 * @note
 *     Peut servir à l'écriture de code CSS dans un squelette, mais
 *     pour inclure un fichier css, il vaut mieux utiliser le filtre
 *     `direction_css` si on le souhaite sensible à la langue utilisé.
 *
 * @balise
 * @link https://www.spip.net/4625
 * @see lang_dir()
 * @see balise_LANG_RIGHT_dist()
 * @see balise_LANG_DIR_dist()
 * @see direction_css()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_LANG_LEFT_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir($_lang, 'left','right')";
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Compile la balise `#LANG_RIGHT` retournant 'right' si la langue s'écrit
 * de gauche à droite, sinon 'left'
 *
 * @balise
 * @link https://www.spip.net/4625
 * @see lang_dir()
 * @see balise_LANG_LEFT_dist()
 * @see balise_LANG_DIR_dist()
 * @see direction_css()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_LANG_RIGHT_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir($_lang, 'right','left')";
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Compile la balise `#LANG_DIR` retournant 'ltr' si la langue s'écrit
 * de gauche à droite, sinon 'rtl'
 *
 * @balise
 * @link https://www.spip.net/4625
 * @see lang_dir()
 * @see balise_LANG_LEFT_dist()
 * @see balise_LANG_RIGHT_dist()
 * @example
 *     ```
 *     <html dir="#LANG_DIR" lang="#LANG"
 *         xmlns="http://www.w3.org/1999/xhtml"
 *         xml:lang="#LANG" class="[(#LANG_DIR)][ (#LANG)] no-js">
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_LANG_DIR_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir($_lang, 'ltr','rtl')";
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#PUCE` affichant une puce
 *
 * @balise
 * @link https://www.spip.net/4628
 * @see definir_puce()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_PUCE_dist($p) {
	$p->code = 'definir_puce()';
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#DATE` qui retourne la date de mise en ligne
 *
 * Cette balise retourne soit le champ `date` d'une table si elle est
 * utilisée dans une boucle, sinon la date de calcul du squelette.
 *
 * @balise
 * @link https://www.spip.net/4336 Balise DATE
 * @link https://www.spip.net/1971 La gestion des dates
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
function balise_DATE_dist($p) {
	$p->code = champ_sql('date', $p);

	return $p;
}


/**
 * Compile la balise `#DATE_REDAC` qui retourne la date de première publication
 *
 * Cette balise retourne le champ `date_redac` d'une table
 *
 * @balise
 * @link https://www.spip.net/3858 Balises DATE_MODIF et DATE_REDAC
 * @link https://www.spip.net/1971 La gestion des dates
 * @see balise_DATE_MODIF_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 */
function balise_DATE_REDAC_dist($p) {
	$p->code = champ_sql('date_redac', $p);
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Compile la balise `#DATE_MODIF` qui retourne la date de dernière modification
 *
 * Cette balise retourne le champ `date_modif` d'une table
 *
 * @balise
 * @link https://www.spip.net/3858 Balises DATE_MODIF et DATE_REDAC
 * @link https://www.spip.net/1971 La gestion des dates
 * @see balise_DATE_REDAC_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 */
function balise_DATE_MODIF_dist($p) {
	$p->code = champ_sql('date_modif', $p);
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Compile la balise `#DATE_NOUVEAUTES` indiquant la date de dernier envoi
 * du mail de nouveautés
 *
 * @balise
 * @link https://www.spip.net/4337 Balise DATE_NOUVEAUTES
 * @link https://www.spip.net/1971 La gestion des dates
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
 * répertoire du fichier squelette dans lequel elle est appelee
 * (comme __DIR__ en php)
 *
 * @balise
 * @link https://www.spip.net/4627
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
 * @balise
 * @link https://www.spip.net/4027
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
 * @balise
 * @see spip_version()
 * @example
 *     ```
 *     [<meta name="generator" content="SPIP (#SPIP_VERSION|header_silencieux)" />]
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 */
function balise_SPIP_VERSION_dist($p) {
	$p->code = 'spip_version()';
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#NOM_SITE` qui affiche le nom du site.
 *
 * Affiche le nom du site ou sinon l'URL ou le titre de l'objet
 * Utiliser `#NOM_SITE*` pour avoir le nom du site ou rien.
 *
 * Cette balise interroge les colonnes `nom_site` ou `url_site`
 * dans la boucle la plus proche.
 *
 * @balise
 * @see calculer_url()
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
		$p->code = 'supprimer_numero(calculer_url(' .
			champ_sql('url_site', $p) . ',' .
			champ_sql('nom_site', $p) .
			", 'titre', \$connect, false))";
	} else {
		$p->code = champ_sql('nom_site', $p);
	}

	$p->interdire_scripts = true;

	return $p;
}


/**
 * Compile la balise `#NOTE` qui affiche les notes de bas de page
 *
 * @balise
 * @link https://www.spip.net/3964
 * @see calculer_notes()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_NOTES_dist($p) {
	// Recuperer les notes
	$p->code = 'calculer_notes()';

	#$p->interdire_scripts = true;
	return $p;
}


/**
 * Compile la balise `#RECHERCHE` qui retourne le terme de recherche demandé
 *
 * Retourne un terme demandé en recherche, en le prenant dans _request()
 * sous la clé `recherche`.
 *
 * @balise
 * @example
 *     ```
 *     <h3>Recherche de : #RECHERCHE</h3>
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_RECHERCHE_dist($p) {
	$p->code = 'entites_html(_request("recherche"))';
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#COMPTEUR_BOUCLE` qui retourne le numéro de l’itération
 * actuelle de la boucle
 *
 * @balise
 * @link https://www.spip.net/4333
 * @see balise_TOTAL_BOUCLE_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_COMPTEUR_BOUCLE_dist($p) {
	$b = index_boucle_mere($p);
	if ($b === '') {
		$msg = ['zbug_champ_hors_boucle', ['champ' => zbug_presenter_champ($p)]];
		erreur_squelette($msg, $p);
	} else {
		$p->code = "(\$Numrows['$b']['compteur_boucle'] ?? 0)";
		$p->boucles[$b]->cptrows = true;
		$p->interdire_scripts = false;

		return $p;
	}
}

/**
 * Compile la balise `#TOTAL_BOUCLE` qui retourne le nombre de résultats
 * affichés par la boucle
 *
 * @balise
 * @link https://www.spip.net/4334
 * @see balise_COMPTEUR_BOUCLE_dist()
 * @see balise_GRAND_TOTAL_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_TOTAL_BOUCLE_dist($p) {
	$b = index_boucle_mere($p);
	if ($b === '') {
		$msg = ['zbug_champ_hors_boucle', ['champ' => zbug_presenter_champ($p)]];
		erreur_squelette($msg, $p);
	} else {
		$p->code = "(\$Numrows['$b']['total'] ?? 0)";
		$p->boucles[$b]->numrows = true;
		$p->interdire_scripts = false;
	}

	return $p;
}


/**
 * Compile la balise `#POINTS` qui affiche la pertinence des résultats
 *
 * Retourne le calcul `points` réalisé par le critère `recherche`.
 * Cette balise nécessite donc la présence de ce critère.
 *
 * @balise
 * @link https://www.spip.net/903 boucles et balises de recherche
 * @see critere_recherche_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_POINTS_dist($p) {
	return rindex_pile($p, 'points', 'recherche');
}


/**
 * Compile la balise `#POPULARITE_ABSOLUE` qui affiche la popularité absolue
 *
 * Cela correspond à la popularité quotidienne de l'article
 *
 * @balise
 * @link https://www.spip.net/1846 La popularité
 * @see balise_POPULARITE_dist()
 * @see balise_POPULARITE_MAX_dist()
 * @see balise_POPULARITE_SITE_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_POPULARITE_ABSOLUE_dist($p) {
	$p->code = 'ceil(' .
		champ_sql('popularite', $p) .
		')';
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Compile la balise `#POPULARITE_SITE` qui affiche la popularité du site
 *
 * La popularité du site est la somme de toutes les popularités absolues.
 *
 * @balise
 * @link https://www.spip.net/1846 La popularité
 * @see balise_POPULARITE_ABSOLUE_dist()
 * @see balise_POPULARITE_dist()
 * @see balise_POPULARITE_MAX_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_POPULARITE_SITE_dist($p) {
	$p->code = 'ceil($GLOBALS["meta"][\'popularite_total\'])';
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Compile la balise `#POPULARITE_MAX` qui affiche la popularité maximum
 * parmis les popularités des articles
 *
 * Cela correspond à la popularité quotidienne de l'article
 *
 * @balise
 * @link https://www.spip.net/1846 La popularité
 * @see balise_POPULARITE_ABSOLUE_dist()
 * @see balise_POPULARITE_dist()
 * @see balise_POPULARITE_SITE_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_POPULARITE_MAX_dist($p) {
	$p->code = 'ceil($GLOBALS["meta"][\'popularite_max\'])';
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#VALEUR` retournant le champ `valeur`
 *
 * Utile dans une boucle DATA pour retourner une valeur.
 *
 * @balise
 * @link https://www.spip.net/5546 #CLE et #VALEUR
 * @see table_valeur()
 * @example
 *     ```
 *     #VALEUR renvoie le champ valeur
 *     #VALEUR{x} renvoie #VALEUR|table_valeur{x},
 *        équivalent à #X (si X n'est pas une balise spécifique à SPIP)
 *     #VALEUR{a/b} renvoie #VALEUR|table_valeur{a/b}
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_VALEUR_dist($p) {
	$b = $p->nom_boucle ?: $p->id_boucle;
	$p->code = index_pile($p->id_boucle, 'valeur', $p->boucles, $b);
;
	if (($v = interprete_argument_balise(1, $p)) !== null) {
		$p->code = 'table_valeur(' . $p->code . ', ' . $v . ')';
	}
	$p->interdire_scripts = true;

	return $p;
}

/**
 * Compile la balise `#EXPOSE` qui met en évidence l'élément sur lequel
 * la page se trouve
 *
 * Expose dans une boucle l'élément de la page sur laquelle on se trouve,
 * en retournant `on` si l'élément correspond à la page, une chaîne vide sinon.
 *
 * On peut passer les paramètres à faire retourner par la balise.
 *
 * @example
 *     ```
 *     <a href="#URL_ARTICLE"[ class="(#EXPOSE)"]>
 *     <a href="#URL_ARTICLE"[ class="(#EXPOSE{actif})"]>
 *     <a href="#URL_ARTICLE"[ class="(#EXPOSE{on,off})"]>
 *     ```
 *
 * @balise
 * @link https://www.spip.net/2319 Exposer un article
 * @uses calculer_balise_expose()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_EXPOSE_dist($p) {
	$on = "'on'";
	$off = "''";
	if (($v = interprete_argument_balise(1, $p)) !== null) {
		$on = $v;
		if (($v = interprete_argument_balise(2, $p)) !== null) {
			$off = $v;
		}
	}

	return calculer_balise_expose($p, $on, $off);
}

/**
 * Calcul de la balise expose
 *
 * @see calcul_exposer()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @param string $on
 *     Texte à afficher si l'élément est exposé (code à écrire tel que "'on'")
 * @param string $off
 *     Texte à afficher si l'élément n'est pas exposé (code à écrire tel que "''")
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function calculer_balise_expose($p, $on, $off) {
	$b = index_boucle($p);
	if (empty($p->boucles[$b]->primary)) {
		$msg = ['zbug_champ_hors_boucle', ['champ' => zbug_presenter_champ($p)]];
		erreur_squelette($msg, $p);
	} else {
		$key = $p->boucles[$b]->primary;
		$type = $p->boucles[$p->id_boucle]->primary;
		$desc = $p->boucles[$b]->show;
		$connect = sql_quote($p->boucles[$b]->sql_serveur);

		// Ne pas utiliser champ_sql, on jongle avec le nom boucle explicite
		$c = index_pile($p->id_boucle, $type, $p->boucles);

		if (isset($desc['field']['id_parent'])) {
			$parent = 0; // pour if (!$parent) dans calculer_expose
		} elseif (isset($desc['field']['id_rubrique'])) {
			$parent = index_pile($p->id_boucle, 'id_rubrique', $p->boucles, $b);
		} elseif (isset($desc['field']['id_groupe'])) {
			$parent = index_pile($p->id_boucle, 'id_groupe', $p->boucles, $b);
		} else {
			$parent = "''";
		}

		$p->code = "(calcul_exposer($c, '$type', \$Pile[0], $parent, '$key', $connect) ? $on : $off)";
	}

	$p->interdire_scripts = false;

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
 *     #INTRODUCTION : coupe au nombre par défaut, suite par défaut
 *     #INTRODUCTION{300} : coupe à 300, suite par défaut
 *     #INTRODUCTION{300, ...} : coupe à 300, suite '...'
 *     #INTRODUCTION{...} : coupe au nombre par défaut, suite '...'
 *     ```
 *
 * @balise
 * @link http://www.spip.net/@introduction
 * @uses generer_objet_introduction()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_INTRODUCTION_dist($p) {

	$type_objet = $p->type_requete;
	$cle_objet = id_table_objet($type_objet);
	$_id_objet = champ_sql($cle_objet, $p);

	// Récupérer les valeurs sql nécessaires : descriptif, texte et chapo
	// ainsi que le longueur d'introduction donnée dans la description de l'objet.
	$_introduction_longueur = 'null';
	$_ligne = 'array(';
	$trouver_table = charger_fonction('trouver_table', 'base');
	if ($desc = $trouver_table(table_objet_sql($type_objet))) {
		if (isset($desc['field']['descriptif'])) {
			$_ligne .= "'descriptif' => " . champ_sql('descriptif', $p) . ',';
		}
		if (isset($desc['field']['texte'])) {
			$_ligne .= "'texte' => " . champ_sql('texte', $p) . ',';
		}
		if (isset($desc['field']['chapo'])) {
			$_ligne .= "'chapo' => " . champ_sql('chapo', $p) . ',';
		}
		if (isset($desc['introduction_longueur'])) {
			$_introduction_longueur = "'" . $desc['introduction_longueur'] . "'";
		}
	}
	$_ligne .= ')';

	// Récupérer la longueur et la suite passés en paramètres
	$_longueur_ou_suite = 'null';
	if (($v1 = interprete_argument_balise(1, $p)) !== null) {
		$_longueur_ou_suite = $v1;
	}
	$_suite = 'null';
	if (($v2 = interprete_argument_balise(2, $p)) !== null) {
		$_suite = $v2;
	}

	$p->code = "generer_objet_introduction($_id_objet, '$type_objet', $_ligne, $_introduction_longueur, $_longueur_ou_suite, $_suite, \$connect)";

	#$p->interdire_scripts = true;
	$p->etoile = '*'; // propre est deja fait dans le calcul de l'intro
	return $p;
}


/**
 * Compile la balise `#LANG` qui affiche la langue de l'objet (ou d'une boucle supérieure),
 * et à defaut la langue courante
 *
 * La langue courante est celle du site ou celle qui a été passée dans l'URL par le visiteur.
 * L'étoile `#LANG*` n'affiche rien si aucune langue n'est trouvée dans le SQL ou le contexte.
 *
 * @balise
 * @link https://www.spip.net/3864
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_LANG_dist($p) {
	$_lang = champ_sql('lang', $p);
	if (!$p->etoile) {
		$p->code = "spip_htmlentities($_lang ? $_lang : \$GLOBALS['spip_lang'])";
	} else {
		$p->code = "spip_htmlentities($_lang)";
	}
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
 * @balise
 * @link https://www.spip.net/3966 Description de la balise
 * @link https://www.spip.net/902 Description de la boucle ARTICLES
 * @link https://www.spip.net/911 Description de la boucle SYNDIC_ARTICLES
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_LESAUTEURS_dist($p) {
	// Cherche le champ 'lesauteurs' dans la pile
	$_lesauteurs = champ_sql('lesauteurs', $p, '');

	// Si le champ n'existe pas (cas de spip_articles), on applique
	// le modele lesauteurs.html en passant id_article dans le contexte;
	// dans le cas contraire on prend le champ 'lesauteurs'
	// (cf extension sites/)
	if ($_lesauteurs) {
		$p->code = "safehtml($_lesauteurs)";
		// $p->interdire_scripts = true;
	} else {
		if (!$p->id_boucle) {
			$connect = '';
			$objet = 'article';
			$id_table_objet = 'id_article';
		} else {
			$b = $p->nom_boucle ?: $p->id_boucle;
			$connect = $p->boucles[$b]->sql_serveur;
			$type_boucle = $p->boucles[$b]->type_requete;
			$objet = objet_type($type_boucle);
			$id_table_objet = id_table_objet($type_boucle);
		}
		$c = memoriser_contexte_compil($p);

		$p->code = sprintf(
			CODE_RECUPERER_FOND,
			"'modeles/lesauteurs'",
			"array('objet'=>'" . $objet .
			"','id_objet' => " . champ_sql($id_table_objet, $p) .
			",'$id_table_objet' => " . champ_sql($id_table_objet, $p) .
			($objet == 'article' ? '' : ",'id_article' => " . champ_sql('id_article', $p)) .
			')',
			"'trim'=>true, 'compil'=>array($c)",
			_q($connect)
		);
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
 * @balise
 * @link https://www.spip.net/5495
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_RANG_dist($p) {
	$b = index_boucle($p);
	if ($b === '') {
		$msg = [
			'zbug_champ_hors_boucle',
			['champ' => '#RANG']
		];
		erreur_squelette($msg, $p);
	} else {
		// chercher d'abord un champ sql rang (mais pas dans le env : defaut '' si on trouve pas de champ sql)
		// dans la boucle immediatement englobante uniquement
		// sinon on compose le champ calcule
		$_rang = champ_sql('rang', $p, '', false);

		// si pas trouve de champ sql rang :
		if (!$_rang or $_rang == "''") {
			$boucle = &$p->boucles[$b];

			// on gere le cas ou #RANG est une extraction du numero dans le titre
			$trouver_table = charger_fonction('trouver_table', 'base');
			$desc = $trouver_table($boucle->id_table);
			$_titre = ''; # où extraire le numero ?

			if (isset($desc['titre'])) {
				$t = $desc['titre'];
				if (
					// Soit on trouve avec la déclaration de la lang AVANT
					preg_match(';(?:lang\s*,)\s*(.*?titre)\s*(,|$);', $t, $m)
					// Soit on prend depuis le début
					or preg_match(';^(.*?titre)\s*(,|$);', $t, $m)
				) {
					$m = preg_replace(',as\s+titre$,i', '', $m[1]);
					$m = trim($m);
					if ($m != "''") {
						if (!preg_match(',\W,', $m)) {
							$m = $boucle->id_table . ".$m";
						}

						$m .= ' AS titre_rang';

						$boucle->select[] = $m;
						$_titre = '$Pile[$SP][\'titre_rang\']';
					}
				}
			}

			// si on n'a rien trouvé, on utilise le champ titre classique
			if (!$_titre) {
				$_titre = champ_sql('titre', $p);
			}

			// et on recupere aussi les infos de liaison si on est en train d'editer les liens justement
			// cas des formulaires xxx_lies utilises par #FORMULAIRE_EDITER_LIENS
			$type_boucle = $boucle->type_requete;
			$objet = objet_type($type_boucle);
			$id_table_objet = id_table_objet($type_boucle);
			$_primary = champ_sql($id_table_objet, $p, '', false);
			$_env = '$Pile[0]';

			if (!$_titre) {$_titre = "''";
			}
			if (!$_primary) {$_primary = "''";
			}
			$_rang = "calculer_rang_smart($_titre, '$objet', $_primary, $_env)";
		}

		$p->code = $_rang;
		$p->interdire_scripts = false;
	}

	return $p;
}


/**
 * Compile la balise `#POPULARITE` qui affiche la popularité relative.
 *
 * C'est à dire le pourcentage de la fréquentation de l'article
 * (la popularité absolue) par rapport à la popularité maximum.
 *
 * @balise
 * @link https://www.spip.net/1846 La popularité
 * @see balise_POPULARITE_ABSOLUE_dist()
 * @see balise_POPULARITE_MAX_dist()
 * @see balise_POPULARITE_SITE_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_POPULARITE_dist($p) {
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
define(
	'CODE_PAGINATION',
	'%s($Numrows["%s"]["grand_total"],
 		%s,
		isset($Pile[0][%4$s])?$Pile[0][%4$s]:intval(_request(%4$s)),
		%5$s, %6$s, %7$s, %8$s, array(%9$s))'
);

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
 * @balise
 * @link https://www.spip.net/3367 Le système de pagination
 * @see filtre_pagination_dist()
 * @see critere_pagination_dist()
 * @see balise_ANCRE_PAGINATION_dist()
 * @example
 *    ```
 *    [<nav role="navigation" class="pagination">(#PAGINATION{prive})</nav>]
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
function balise_PAGINATION_dist($p, $liste = 'true') {
	$b = index_boucle_mere($p);

	// s'il n'y a pas de nom de boucle, on ne peut pas paginer
	if ($b === '') {
		$msg = [
			'zbug_champ_hors_boucle',
			['champ' => $liste ? 'PAGINATION' : 'ANCRE_PAGINATION']
		];
		erreur_squelette($msg, $p);

		return $p;
	}

	// s'il n'y a pas de mode_partie, c'est qu'on se trouve
	// dans un boucle recursive ou qu'on a oublie le critere {pagination}
	if (!$p->boucles[$b]->mode_partie) {
		if (!$p->boucles[$b]->table_optionnelle) {
			$msg = [
				'zbug_pagination_sans_critere',
				['champ' => '#PAGINATION']
			];
			erreur_squelette($msg, $p);
		}

		return $p;
	}

	// a priori true
	// si false, le compilo va bloquer sur des syntaxes avec un filtre sans argument qui suit la balise
	// si true, les arguments simples (sans truc=chose) vont degager
	$_contexte = argumenter_inclure($p->param, true, $p, $p->boucles, $p->id_boucle, false, false);
	if (is_countable($_contexte) ? count($_contexte) : 0) {
		$key = key($_contexte);
		if (is_numeric($key)) {
			array_shift($_contexte);
			$__modele = interprete_argument_balise(1, $p);
		}
	}

	if (is_countable($_contexte) ? count($_contexte) : 0) {
		$code_contexte = implode(',', $_contexte);
	} else {
		$code_contexte = '';
	}

	$connect = $p->boucles[$b]->sql_serveur;
	$pas = $p->boucles[$b]->total_parties;
	$f_pagination = chercher_filtre('pagination');
	$type = $p->boucles[$b]->modificateur['debut_nom'];
	$modif = ($type[0] !== "'") ? "'debut'.$type"
		: ("'debut" . substr($type, 1));

	$p->code = sprintf(
		CODE_PAGINATION,
		$f_pagination,
		$b,
		$type,
		$modif,
		$pas,
		$liste,
		((isset($__modele) and $__modele) ? $__modele : "''"),
		_q($connect),
		$code_contexte
	);

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
 * @balise
 * @link https://www.spip.net/3367 Le système de pagination
 * @link https://www.spip.net/4328 Balise ANCRE_PAGINATION
 * @see critere_pagination_dist()
 * @see balise_PAGINATION_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_ANCRE_PAGINATION_dist($p) {
	if ($f = charger_fonction('PAGINATION', 'balise', true)) {
		return $f($p, $liste = 'false');
	} else {
		return null;
	} // ou une erreur ?
}


/**
 * Compile la balise `#GRAND_TOTAL` qui retourne le nombre total de résultats
 * d'une boucle
 *
 * Cette balise set équivalente à `#TOTAL_BOUCLE` sauf pour les boucles paginées.
 * Dans ce cas elle indique le nombre total d'éléments répondant aux critères
 * hors pagination.
 *
 * @balise
 * @see balise_GRAND_TOTAL_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_GRAND_TOTAL_dist($p) {
	$b = index_boucle_mere($p);
	if ($b === '') {
		$msg = ['zbug_champ_hors_boucle', ['champ' => zbug_presenter_champ($p)]];
		erreur_squelette($msg, $p);
	} else {
		$p->code = "(\$Numrows['$b']['grand_total'] ?? \$Numrows['$b']['total'] ?? 0)";
		$p->boucles[$b]->numrows = true;
		$p->interdire_scripts = false;
	}

	return $p;
}


/**
 * Compile la balise `#SELF` qui retourne l’URL de la page appelée.
 *
 * Cette URL est nettoyée des variables propres à l’exécution de SPIP
 * tel que `var_mode`.
 *
 * @note
 *     Attention dans un `INCLURE()` ou une balise dynamique, on n'a pas le droit de
 *     mettre en cache `#SELF` car il peut correspondre à une autre page (attaque XSS)
 *     (Dans ce cas faire <INCLURE{self=#SELF}> pour différencier les caches.)
 *
 * @balise
 * @link https://www.spip.net/4574
 * @example
 *     ```
 *     <a href="[(#SELF|parametre_url{id_mot,#ID_MOT})]">...
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
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
 * @balise
 * @link https://www.spip.net/4332
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
	$arg = interprete_argument_balise(1, $p);
	if (!$arg) {
		$msg = ['zbug_balise_sans_argument', ['balise' => ' CHEMIN']];
		erreur_squelette($msg, $p);
	} else {
		$p->code = 'find_in_path((string)' . $arg . ')';
	}

	$p->interdire_scripts = false;

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
 * @balise
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
	$arg = interprete_argument_balise(1, $p);
	if (!$arg) {
		$msg = ['zbug_balise_sans_argument', ['balise' => ' CHEMIN_IMAGE']];
		erreur_squelette($msg, $p);
	} else {
		$p->code = 'chemin_image((string)' . $arg . ')';
	}

	$p->interdire_scripts = false;
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
function balise_ENV_dist($p, $src = null) {

	// cle du tableau desiree
	$_nom = interprete_argument_balise(1, $p);
	// valeur par defaut
	$_sinon = interprete_argument_balise(2, $p);

	// $src est un tableau de donnees sources eventuellement transmis
	// en absence, on utilise l'environnement du squelette $Pile[0]

	if (!$_nom) {
		// cas de #ENV sans argument : on retourne le serialize() du tableau
		// une belle fonction [(#ENV|affiche_env)] serait pratique
		if ($src) {
			$p->code = '(is_array($a = (' . $src . ')) ? serialize($a) : "")';
		} else {
			$p->code = 'serialize($Pile[0]??[])';
		}
	} else {
		if (!$src) {
			$src = '$Pile[0]??[]';
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
 * @balise
 * @link https://www.spip.net/4335
 *
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 */
function balise_CONFIG_dist($p) {
	if (!$arg = interprete_argument_balise(1, $p)) {
		$arg = "''";
	}
	$_sinon = interprete_argument_balise(2, $p);
	$_unserialize = sinon(interprete_argument_balise(3, $p), 'false');

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
 * @balise
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


/**
 * Compile la balise `#SESSION` qui permet d’accéder aux informations
 * liées au visiteur authentifié et de différencier automatiquement
 * le cache en fonction du visiteur.
 *
 * Cette balise est un tableau des données du visiteur (nom, email etc).
 * Si elle est invoquée, elle lève un drapeau dans le fichier cache, qui
 * permet à public/cacher de créer un cache différent par visiteur
 *
 * @balise
 * @link https://www.spip.net/3979
 * @see balise_AUTORISER_dist()
 * @see balise_SESSION_SET_dist()
 * @example
 *     ```
 *     #SESSION{nom}
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 **/
function balise_SESSION_dist($p) {
	$p->descr['session'] = true;

	$f = function_exists('balise_ENV')
		? 'balise_ENV'
		: 'balise_ENV_dist';

	$p = $f($p, '$GLOBALS["visiteur_session"]??[]');

	return $p;
}


/**
 * Compile la balise `#SESSION_SET` qui d’insérer dans la session
 * des données supplémentaires
 *
 * @balise
 * @link https://www.spip.net/3984
 * @see balise_AUTORISER_dist()
 * @see balise_SESSION_SET_dist()
 * @example
 *     ```
 *     #SESSION_SET{x,y} ajoute x=y dans la session du visiteur
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 **/
function balise_SESSION_SET_dist($p) {
	$_nom = interprete_argument_balise(1, $p);
	$_val = interprete_argument_balise(2, $p);
	if (!$_nom or !$_val) {
		$err_b_s_a = ['zbug_balise_sans_argument', ['balise' => 'SESSION_SET']];
		erreur_squelette($err_b_s_a, $p);
	} else {
		$p->code = '(include_spip("inc/session") AND session_set(' . $_nom . ',' . $_val . '))';
	}

	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#EVAL` qui évalue un code PHP
 *
 * À utiliser avec précautions !
 *
 * @balise
 * @link https://www.spip.net/4587
 * @example
 *     ```
 *     #EVAL{6+9}
 *     #EVAL{'date("Y-m-d")'}
 *     #EVAL{$_SERVER['REQUEST_URI']}
 *     #EVAL{'str_replace("r","z", "roger")'}  (attention les "'" sont interdits)
 *     ```
 *
 * @note
 *     `#EVAL{code}` produit `eval('return code;')`
 *      mais si le code est une expression sans balise, on se dispense
 *      de passer par une construction si compliquée, et le code est
 *      passé tel quel (entre parenthèses, et protégé par interdire_scripts)
 *
 * @param Champ $p
 *     Pile au niveau de la balise.
 * @return Champ
 *     Pile completée du code PHP d'exécution de la balise
 **/
function balise_EVAL_dist($p) {
	$php = interprete_argument_balise(1, $p);
	if ($php) {
		# optimisation sur les #EVAL{une expression sans #BALISE}
		# attention au commentaire "// x signes" qui precede
		if (
			preg_match(
				",^([[:space:]]*//[^\n]*\n)'([^']+)'$,ms",
				$php,
				$r
			)
		) {
			$p->code = /* $r[1]. */
				'(' . $r[2] . ')';
		} else {
			$p->code = "eval('return '.$php.';')";
		}
	} else {
		$msg = ['zbug_balise_sans_argument', ['balise' => ' EVAL']];
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
 * @balise
 * @link https://www.spip.net/4041
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
function balise_CHAMP_SQL_dist($p) {

	if (
		$p->param
		and isset($p->param[0][1][0])
		and $champ = ($p->param[0][1][0]->texte)
	) {
		$p->code = champ_sql($champ, $p);
	} else {
		$err_b_s_a = ['zbug_balise_sans_argument', ['balise' => ' CHAMP_SQL']];
		erreur_squelette($err_b_s_a, $p);
	}

	#$p->interdire_scripts = true;
	return $p;
}

/**
 * Compile la balise `#VAL` qui retourne simplement le premier argument
 * qui lui est transmis
 *
 * Cela permet d'appliquer un filtre à une chaîne de caractère
 *
 * @balise
 * @link https://www.spip.net/4026
 * @example
 *     ```
 *     #VAL retourne ''
 *     #VAL{x} retourne 'x'
 *     #VAL{1,2} renvoie '1' (2 est considéré comme un autre paramètre)
 *     #VAL{'1,2'} renvoie '1,2'
 *     [(#VAL{a_suivre}|bouton_spip_rss)]
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_VAL_dist($p) {
	$p->code = interprete_argument_balise(1, $p);
	if ($p->code === null || !strlen($p->code)) {
		$p->code = "''";
	}
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Compile la balise `#REM` servant à commenter du texte
 *
 * Retourne toujours une chaîne vide.
 *
 * @balise
 * @link https://www.spip.net/4578
 * @example
 *     ```
 *     [(#REM)
 *       Ceci est une remarque ou un commentaire,
 *       non affiché dans le code généré
 *     ]
 *     ```
 *
 * @note
 *     La balise `#REM` n'empêche pas l'exécution des balises SPIP contenues
 *     dedans (elle ne sert pas à commenter du code pour empêcher son
 *     exécution).
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_REM_dist($p) {
	$p->code = "''";
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Une balise #NULL quand on a besoin de passer un argument null sur l'appel d'un filtre ou formulaire
 * (evite un #EVAL{null})
 * @param $p
 * @return mixed
 */
function balise_NULL_dist($p) {
	$p->code = 'null';
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#HTTP_HEADER` envoyant des entêtes de retour HTTP
 *
 * Doit être placée en tête de fichier et ne fonctionne pas dans une
 * inclusion.
 *
 * @balise
 * @link https://www.spip.net/4631
 * @example
 *     ```
 *     #HTTP_HEADER{Content-Type: text/csv; charset=#CHARSET}
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_HTTP_HEADER_dist($p) {

	$header = interprete_argument_balise(1, $p);
	if (!$header) {
		$err_b_s_a = ['zbug_balise_sans_argument', ['balise' => 'HTTP_HEADER']];
		erreur_squelette($err_b_s_a, $p);
	} else {
		$p->code = "'<'.'?php header(' . _q("
			. $header
			. ") . '); ?'.'>'";
	}
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#FILTRE` qui exécute un filtre à l'ensemble du squelette
 * une fois calculé.
 *
 * Le filtrage se fait au niveau du squelette, sans s'appliquer aux `<INCLURE>`.
 * Plusieurs filtres peuvent être indiqués, séparés par des barres verticales `|`
 *
 * @balise
 * @link https://www.spip.net/4894
 * @example
 *     ```
 *     #FILTRE{compacte_head}
 *     #FILTRE{supprimer_tags|filtrer_entites|trim}
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_FILTRE_dist($p) {
	if ($p->param) {
		$args = [];
		foreach ($p->param as $i => $ignore) {
			$args[] = interprete_argument_balise($i + 1, $p);
		}
		$p->code = "'<' . '"
			. '?php header("X-Spip-Filtre: \'.'
			. join('.\'|\'.', $args)
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
 * @balise
 * @see ecrire/public/cacher.php
 * @link https://www.spip.net/4330
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

		$code = "'<'.'" . '?php header("X-Spip-Cache: '
			. $duree
			. '"); ?' . "'.'>'";

		// Remplir le header Cache-Control
		// cas #CACHE{0}
		if ($duree == 0) {
			$code .= ".'<'.'"
				. '?php header("Cache-Control: no-cache, must-revalidate"); ?'
				. "'.'><'.'"
				. '?php header("Pragma: no-cache"); ?'
				. "'.'>'";
		}

		// recuperer les parametres suivants
		$i = 1;
		while (isset($p->param[0][++$i])) {
			$pa = ($p->param[0][$i][0]->texte);

			if (
				$pa == 'cache-client'
				and $duree > 0
			) {
				$code .= ".'<'.'" . '?php header("Cache-Control: max-age='
					. $duree
					. '"); ?' . "'.'>'";
				// il semble logique, si on cache-client, de ne pas invalider
				$pa = 'statique';
			}

			if (
				$pa == 'statique'
				and $duree > 0
			) {
				$code .= ".'<'.'" . '?php header("X-Spip-Statique: oui"); ?' . "'.'>'";
			}
		}
	} else {
		$code = "''";
	}
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
 * @link https://www.spip.net/4629
 * @see balise_INSERT_HEAD_CSS_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_INSERT_HEAD_dist($p) {
	$p->code = "'<'.'"
		. '?php header("X-Spip-Filtre: insert_head_css_conditionnel"); ?'
		. "'.'>'";
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
 * @link https://www.spip.net/4605
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

/**
 * Compile la balise `#INCLUDE` alias de `#INCLURE`
 *
 * @balise
 * @see balise_INCLURE_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_INCLUDE_dist($p) {
	if (function_exists('balise_INCLURE')) {
		return balise_INCLURE($p);
	} else {
		return balise_INCLURE_dist($p);
	}
}

/**
 * Compile la balise `#INCLURE` qui inclut un résultat de squelette
 *
 * Signature : `[(#INCLURE{fond=nom_du_squelette, argument, argument=xx})]`
 *
 * L'argument `env` permet de transmettre tout l'environnement du squelette
 * en cours au squelette inclus.
 *
 * On parle d’inclusion « statique » car le résultat de compilation est
 * ajouté au squelette en cours, dans le même fichier de cache.
 * Cette balise est donc différente d’une inclusion « dynamique » avec
 * `<INCLURE.../>` qui, elle, crée un fichier de cache séparé
 * (avec une durée de cache qui lui est propre).
 *
 * L'inclusion est realisée au calcul du squelette, pas au service
 * ainsi le produit du squelette peut être utilisé en entrée de filtres
 * à suivre. On peut faire un `#INCLURE{fichier}` sans squelette
 * (Incompatible avec les balises dynamiques).
 *
 * @balise
 * @example
 *     ```
 *     [(#INCLURE{fond=inclure/documents,id_article, env})]
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_INCLURE_dist($p) {
	$id_boucle = $p->id_boucle;
	// la lang n'est pas passe de facon automatique par argumenter
	// mais le sera pas recuperer_fond, sauf si etoile=>true est passe
	// en option

	$_contexte = argumenter_inclure($p->param, true, $p, $p->boucles, $id_boucle, false, false);

	// erreur de syntaxe = fond absent
	// (2 messages d'erreur SPIP pour le prix d'un, mais pas d'erreur PHP
	if (!$_contexte) {
		$contexte = [];
	}

	if (isset($_contexte['fond'])) {
		$f = $_contexte['fond'];
		// toujours vrai :
		if (preg_match('/^.fond.\s*=>(.*)$/s', $f, $r)) {
			$f = $r[1];
			unset($_contexte['fond']);
		} else {
			spip_log('compilation de #INCLURE a revoir');
		}

		// #INCLURE{doublons}
		if (isset($_contexte['doublons'])) {
			$_contexte['doublons'] = "'doublons' => \$doublons";
		}

		// Critere d'inclusion {env} (et {self} pour compatibilite ascendante)
		$flag_env = false;
		if (isset($_contexte['env']) or isset($_contexte['self'])) {
			$flag_env = true;
			unset($_contexte['env']);
		}

		$_options = [];
		if (isset($_contexte['ajax'])) {
			$_options[] = preg_replace(',=>(.*)$,ims', '=> ($v=(\\1))?$v:true', $_contexte['ajax']);
			unset($_contexte['ajax']);
		}
		if ($p->etoile) {
			$_options[] = "'etoile'=>true";
		}
		$_options[] = "'compil'=>array(" . memoriser_contexte_compil($p) . ')';

		$_l = 'array(' . join(",\n\t", $_contexte) . ')';
		if ($flag_env) {
			$_l = "array_merge(\$Pile[0],$_l)";
		}

		$p->code = sprintf(CODE_RECUPERER_FOND, $f, $_l, join(',', $_options), "_request('connect') ?? ''");
	} elseif (!isset($_contexte[1])) {
		$msg = ['zbug_balise_sans_argument', ['balise' => ' INCLURE']];
		erreur_squelette($msg, $p);
	} else {
		$p->code = 'charge_scripts(' . $_contexte[1] . ',false)';
	}

	$p->interdire_scripts = false; // la securite est assuree par recuperer_fond
	return $p;
}


/**
 * Compile la balise `#MODELE` qui inclut un résultat de squelette de modèle
 *
 * `#MODELE{nom}` insère le résultat d’un squelette contenu dans le
 * répertoire `modeles/`. L’identifiant de la boucle parente est transmis
 * par défaut avec le paramètre `id` à cette inclusion.
 *
 * Des arguments supplémentaires peuvent être transmis :
 * `[(#MODELE{nom, argument=xx, argument})]`
 *
 * @balise
 * @see balise_INCLURE_dist()
 * @example
 *     ```
 *     #MODELE{article_traductions}
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_MODELE_dist($p) {

	$_contexte = argumenter_inclure($p->param, true, $p, $p->boucles, $p->id_boucle, false);

	// erreur de syntaxe = fond absent
	// (2 messages d'erreur SPIP pour le prix d'un, mais pas d'erreur PHP
	if (!$_contexte) {
		$_contexte = [];
	}

	if (!isset($_contexte[1])) {
		$msg = ['zbug_balise_sans_argument', ['balise' => ' MODELE']];
		erreur_squelette($msg, $p);
	} else {
		$nom = $_contexte[1];
		unset($_contexte[1]);

		if (preg_match("/^\s*'[^']*'/s", $nom)) {
			$nom = "'modeles/" . substr($nom, 1);
		} else {
			$nom = "'modeles/' . $nom";
		}

		$flag_env = false;
		if (isset($_contexte['env'])) {
			$flag_env = true;
			unset($_contexte['env']);
		}

		// Incoherence dans la syntaxe du contexte. A revoir.
		// Reserver la cle primaire de la boucle courante si elle existe
		if (isset($p->boucles[$p->id_boucle]->primary)) {
			$primary = $p->boucles[$p->id_boucle]->primary;
			if (!strpos($primary, ',')) {
				$id = champ_sql($primary, $p);
				$_contexte[] = "'$primary'=>" . $id;
				$_contexte[] = "'id'=>" . $id;
			}
		}
		$_contexte[] = "'recurs'=>(++\$recurs)";
		$connect = '';
		if (isset($p->boucles[$p->id_boucle])) {
			$connect = $p->boucles[$p->id_boucle]->sql_serveur;
		}

		$_options = memoriser_contexte_compil($p);
		$_options = "'compil'=>array($_options), 'trim'=>true";
		if (isset($_contexte['ajax'])) {
			$_options .= ', ' . preg_replace(',=>(.*)$,ims', '=> ($v=(\\1))?$v:true', $_contexte['ajax']);
			unset($_contexte['ajax']);
		}

		$_l = 'array(' . join(",\n\t", $_contexte) . ')';
		if ($flag_env) {
			$_l = "array_merge(\$Pile[0],$_l)";
		}

		$page = sprintf(CODE_RECUPERER_FOND, $nom, $_l, $_options, _q($connect));

		$p->code = "\n\t(((\$recurs=(isset(\$Pile[0]['recurs'])?\$Pile[0]['recurs']:0))>=5)? '' :\n\t$page)\n";

		$p->interdire_scripts = false; // securite assuree par le squelette
	}

	return $p;
}


/**
 * Compile la balise `#SET` qui affecte une variable locale au squelette
 *
 * Signature : `#SET{cle,valeur}`
 *
 * @balise
 * @link https://www.spip.net/3990 Balises #SET et #GET
 * @see balise_GET_dist()
 * @example
 *     ```
 *     #SET{nb,5}
 *     #GET{nb} // affiche 5
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_SET_dist($p) {
	$_nom = interprete_argument_balise(1, $p);
	$_val = interprete_argument_balise(2, $p);

	if (!$_nom or !$_val) {
		$err_b_s_a = ['zbug_balise_sans_argument', ['balise' => 'SET']];
		erreur_squelette($err_b_s_a, $p);
	}
	// affectation $_zzz inutile, mais permet de contourner un bug OpCode cache sous PHP 5.5.4
	// cf https://bugs.php.net/bug.php?id=65845
	else {
		$p->code = "vide(\$Pile['vars'][\$_zzz=(string)$_nom] = $_val)";
	}

	$p->interdire_scripts = false; // la balise ne renvoie rien
	return $p;
}


/**
 * Compile la balise `#GET` qui récupère une variable locale au squelette
 *
 * Signature : `#GET{cle[,defaut]}`
 *
 * La clé peut obtenir des sous clés séparés par des `/`
 *
 * @balise
 * @link https://www.spip.net/3990 Balises #SET et #GET
 * @see balise_SET_dist()
 * @example
 *     ```
 *     #SET{nb,5}
 *     #GET{nb} affiche 5
 *     #GET{nb,3} affiche la valeur de nb, sinon 3
 *
 *     #SET{nb,#ARRAY{boucles,3}}
 *     #GET{nb/boucles} affiche 3, équivalent à #GET{nb}|table_valeur{boucles}
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_GET_dist($p) {
	$p->interdire_scripts = false; // le contenu vient de #SET, donc il est de confiance
	if (function_exists('balise_ENV')) {
		return balise_ENV($p, '$Pile["vars"]??[]');
	} else {
		return balise_ENV_dist($p, '$Pile["vars"]??[]');
	}
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
 * @balise
 * @link https://www.spip.net/4123
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_DOUBLONS_dist($p) {
	if ($type = interprete_argument_balise(1, $p)) {
		if ($famille = interprete_argument_balise(2, $p)) {
			$type .= '.' . $famille;
		}
		$p->code = '(isset($doublons[' . $type . ']) ? $doublons[' . $type . '] : "")';
		if (!$p->etoile) {
			$p->code = 'array_filter(array_map("intval",explode(",",'
				. $p->code . ')))';
		}
	} else {
		$p->code = '$doublons';
	}

	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#PIPELINE` pour permettre d'insérer des sorties de
 * pipeline dans un squelette
 *
 * @balise
 * @see pipeline()
 * @example
 *     ```
 *     #PIPELINE{nom}
 *     #PIPELINE{nom,données}
 *     #PIPELINE{boite_infos,#ARRAY{data,'',args,#ARRAY{type,rubrique,id,#ENV{id_rubrique}}}}
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_PIPELINE_dist($p) {
	$_pipe = interprete_argument_balise(1, $p);
	if (!$_pipe) {
		$err_b_s_a = ['zbug_balise_sans_argument', ['balise' => 'PIPELINE']];
		erreur_squelette($err_b_s_a, $p);
	} else {
		$_flux = interprete_argument_balise(2, $p);
		$_flux = $_flux ?: "''";
		$p->code = "pipeline( $_pipe , $_flux )";
		$p->interdire_scripts = false;
	}

	return $p;
}


/**
 * Compile la balise `#EDIT` qui ne fait rien dans SPIP
 *
 * Cette balise ne retourne rien mais permet d'indiquer, pour certains plugins
 * qui redéfinissent cette balise, le nom du champ SQL (ou le nom d'un contrôleur)
 * correspondant à ce qui est édité. Cela sert particulièrement au plugin Crayons.
 * Ainsi en absence du plugin, la balise est toujours reconnue (mais n'a aucune action).
 *
 * @balise
 * @link https://www.spip.net/4584
 * @example
 *     ```
 *     [<div class="#EDIT{texte} texte">(#TEXTE)</div>]
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_EDIT_dist($p) {
	$p->code = "''";
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#TOTAL_UNIQUE` qui récupère le nombre d'éléments
 * différents affichés par le filtre `unique`
 *
 * @balise
 * @link https://www.spip.net/4374
 * @see unique()
 * @example
 *     ```
 *     #TOTAL_UNIQUE affiche le nombre de #BALISE|unique
 *     #TOTAL_UNIQUE{famille} afiche le nombre de #BALISE|unique{famille}
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_TOTAL_UNIQUE_dist($p) {
	$_famille = interprete_argument_balise(1, $p);
	$_famille = $_famille ?: "''";
	$p->code = "unique('', $_famille, true)";

	return $p;
}

/**
 * Compile la balise `#ARRAY` créant un tableau PHP associatif
 *
 * Crée un `array` PHP à partir d'arguments calculés.
 * Chaque paire d'arguments représente la clé et la valeur du tableau.
 *
 * @balise
 * @link https://www.spip.net/4009
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
	$_code = [];
	$n = 1;
	do {
		$_key = interprete_argument_balise($n++, $p);
		$_val = interprete_argument_balise($n++, $p);
		if ($_key and $_val) {
			$_code[] = "$_key => $_val";
		}
	} while ($_key && $_val);
	$p->code = 'array(' . join(', ', $_code) . ')';
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Compile la balise `#LISTE` qui crée un tableau PHP avec les valeurs, sans préciser les clés
 *
 * @balise
 * @link https://www.spip.net/5547
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
	$_code = [];
	$n = 1;
	while ($_val = interprete_argument_balise($n++, $p)) {
		$_code[] = $_val;
	}
	$p->code = 'array(' . join(', ', $_code) . ')';
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
 * @balise
 * @link https://www.spip.net/3896
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
	$_code = [];
	$p->descr['session'] = true; // faire un cache par session

	$n = 1;
	while ($_v = interprete_argument_balise($n++, $p)) {
		$_code[] = $_v;
	}

	$p->code = '((function_exists("autoriser")||include_spip("inc/autoriser"))&&autoriser(' . join(
		', ',
		$_code
	) . ')?" ":"")';
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#PLUGIN` qui permet d’afficher les informations d'un plugin actif
 *
 * @balise
 * @see filtre_info_plugin_dist()
 * @link https://www.spip.net/4591
 * @example
 *     ```
 *     #PLUGIN Retourne la liste sérialisée des préfixes de plugins actifs
 *     #PLUGIN{prefixe} Renvoie true si le plugin avec ce préfixe est actif
 *     #PLUGIN{prefixe, x} Renvoie l'information x du plugin (s'il est actif)
 *     #PLUGIN{prefixe, tout} Renvoie toutes les informations du plugin (s'il est actif)
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_PLUGIN_dist($p) {
	$plugin = interprete_argument_balise(1, $p);
	$plugin = isset($plugin) ? str_replace('\'', '"', $plugin) : '""';
	$type_info = interprete_argument_balise(2, $p);
	$type_info = isset($type_info) ? str_replace('\'', '"', $type_info) : '"est_actif"';

	$f = chercher_filtre('info_plugin');
	$p->code = $f . '(' . $plugin . ', ' . $type_info . ')';

	return $p;
}

/**
 * Compile la balise `#AIDER` qui permet d’afficher l’icone de l’aide
 * au sein des squelettes.
 *
 * @balise
 * @see inc_aide_dist()
 * @link https://www.spip.net/4733
 * @example
 *     ```
 *     #AIDER{raccourcis}
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_AIDER_dist($p) {
	$_motif = interprete_argument_balise(1, $p);
	$p->code = "((\$aider=charger_fonction('aide','inc',true))?\$aider($_motif):'')";
	return $p;
}

/**
 * Compile la balise `#ACTION_FORMULAIRE` qui insère le contexte
 * des formulaires charger / vérifier / traiter avec les hidden de
 * l'URL d'action
 *
 * Accèpte 2 arguments optionnels :
 * - L'url de l'action (par défaut `#ENV{action}`
 * - Le nom du formulaire (par défaut `#ENV{form}`
 *
 * @balise
 * @see form_hidden()
 * @example
 *     ```
 *     <form method='post' action='#ENV{action}'><div>
 *     #ACTION_FORMULAIRE
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_ACTION_FORMULAIRE($p) {
	if (!$_url = interprete_argument_balise(1, $p)) {
		$_url = "(\$Pile[0]['action'] ?? '')";
	}
	if (!$_form = interprete_argument_balise(2, $p)) {
		$_form = "(\$Pile[0]['form'] ?? '')";
	}

	// envoyer le nom du formulaire que l'on traite
	// transmettre les eventuels args de la balise formulaire
	$p->code = "	'<span class=\"form-hidden\">' .
	form_hidden($_url) .
	'<input name=\'formulaire_action\' type=\'hidden\'
		value=\'' . $_form . '\' />' .
	'<input name=\'formulaire_action_args\' type=\'hidden\'
		value=\'' . (\$Pile[0]['formulaire_args'] ?? '') . '\' />' .
	'<input name=\'formulaire_action_sign\' type=\'hidden\'
		value=\'' . (\$Pile[0]['formulaire_sign'] ?? '') . '\' />' .
	(\$Pile[0]['_hidden'] ?? '') .
	'</span>'";

	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#BOUTON_ACTION` qui génère un bouton d'action en post, ajaxable
 *
 * Cette balise s'utilise à la place des liens `action_auteur`, sous la forme
 * `#BOUTON_ACTION{libelle[,url[,class[,confirm[,title[,callback]]]]]}`
 *
 * - libelle  : Texte du bouton
 * - url      : URL d’action sécurisée
 * - class    : Classes à ajouter au bouton, à l'exception de `ajax` qui est placé sur le formulaire.
 *              Pour d'autres classes sur le formulaire, utiliser le filtre `ajouter_class`
 * - confirm  : message de confirmation oui/non avant l'action
 * - title    : attribut title à ajouter au bouton
 * - callback : callback js a appeler lors de l'évènement action et avant execution de l'action
 *               (ou après confirmation éventuelle si $confirm est non vide).
 *               Si la callback renvoie false, elle annule le déclenchement de l'action.
 *
 * @balise
 * @uses bouton_action()
 * @link https://www.spip.net/4583
 * @example
 *     ```
 *     [(#AUTORISER{reparer,base}|oui)
 *        [(#BOUTON_ACTION{
 *            <:bouton_tenter_recuperation:>,
 *            #URL_ECRIRE{base_repair},
 *            "ajax btn_large",
 *        })]
 *     ]
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_BOUTON_ACTION_dist($p) {

	$args = [];
	for ($k = 1; $k <= 6; $k++) {
		$_a = interprete_argument_balise($k, $p);
		if (!$_a) {
			$_a = "''";
		}
		$args[] = $_a;
	}
	// supprimer les args vides
	while (end($args) == "''" and count($args) > 2) {
		array_pop($args);
	}
	$args = implode(',', $args);

	$bouton_action = chercher_filtre('bouton_action');
	$p->code = "$bouton_action($args)";
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#SLOGAN_SITE_SPIP` qui retourne le slogan du site
 *
 * @balise
 * @example
 *     ```
 *     [<p id="slogan">(#SLOGAN_SITE_SPIP)</p>]
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_SLOGAN_SITE_SPIP_dist($p) {
	$p->code = "\$GLOBALS['meta']['slogan_site']";

	#$p->interdire_scripts = true;
	return $p;
}


/**
 * Compile la balise `#HTML5` indiquant si l'espace public peut utiliser du HTML5
 *
 * Renvoie `' '` si le webmestre souhaite que SPIP génère du code (X)HTML5 sur
 * le site public, et `''` si le code doit être strictement compatible HTML4
 *
 * @balise
 * @uses html5_permis()
 * @example
 *     ```
 *     [(#HTML5) required="required"]
 *     <input[ (#HTML5|?{type="email",type="text"})] ... />
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
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
 * @balise
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
function balise_TRI_dist($p, $liste = 'true') {
	$b = index_boucle_mere($p);
	// s'il n'y a pas de nom de boucle, on ne peut pas trier
	if ($b === '') {
		$msg = ['zbug_champ_hors_boucle', ['champ' => zbug_presenter_champ($p)]];
		erreur_squelette($msg, $p);
		$p->code = "''";

		return $p;
	}
	$boucle = $p->boucles[$b];

	// s'il n'y a pas de tri_champ, c'est qu'on se trouve
	// dans un boucle recursive ou qu'on a oublie le critere {tri}
	if (!isset($boucle->modificateur['tri_champ'])) {
		$msg = ['zbug_champ_hors_critere', [
			'champ' => zbug_presenter_champ($p),
			'critere' => 'tri'
		]];
		erreur_squelette($msg, $p);
		$p->code = "''";

		return $p;
	}

	$_champ = interprete_argument_balise(1, $p);
	// si pas de champ, renvoyer le critere de tri utilise
	if (!$_champ) {
		$p->code = $boucle->modificateur['tri_champ'];

		return $p;
	}
	// forcer la jointure si besoin, et si le champ est statique
	if (preg_match(",^'([\w.]+)'$,i", $_champ, $m)) {
		index_pile($b, $m[1], $p->boucles, '', null, true, false);
	}

	$_libelle = interprete_argument_balise(2, $p);
	$_libelle = $_libelle ?: $_champ;

	$_class = interprete_argument_balise(3, $p);
	// si champ = ">" c'est un lien vers le tri croissant : de gauche a droite ==> 1
	// si champ = "<" c'est un lien vers le tri decroissant : (sens inverse) == -1
	$_issens = "in_array($_champ,array('>','<'))";
	$_sens = "(strpos('< >',$_champ)-1)";

	$_variable = "((\$s=$_issens)?'sens':'tri')." . $boucle->modificateur['tri_nom'];
	$_url = "parametre_url(self(),$_variable,\$s?$_sens:$_champ)";
	$_url = "parametre_url($_url,'var_memotri',strncmp(" . $boucle->modificateur['tri_nom'] . ",'session',7)==0?$_variable:'')";
	$_on = '$s?(' . $boucle->modificateur['tri_sens'] . "==$_sens" . '):(' . $boucle->modificateur['tri_champ'] . "==$_champ)";

	$p->code = "lien_ou_expose($_url,$_libelle,$_on" . ($_class ? ",$_class" : '') . ')';
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
 * @balise
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_SAUTER_dist($p) {
	$id_boucle = $p->id_boucle;

	if (empty($p->boucles[$id_boucle])) {
		$msg = ['zbug_champ_hors_boucle', ['champ' => '#SAUTER']];
		erreur_squelette($msg, $p);
	} else {
		$_saut = interprete_argument_balise(1, $p);
		$_compteur = "\$Numrows['$id_boucle']['compteur_boucle']";
		$_total = "(\$Numrows['$id_boucle']['total'] ?? null)";

		$p->code = "vide($_compteur=\$iter->skip($_saut,$_total))";
	}
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#PUBLIE` qui indique si un objet est publié ou non
 *
 * @balise
 * @link https://www.spip.net/5545
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
	if (!$_type = interprete_argument_balise(1, $p)) {
		$_type = _q($p->type_requete);
		$_id = champ_sql($p->boucles[$p->id_boucle]->primary, $p);
	} else {
		$_id = interprete_argument_balise(2, $p);
	}

	$connect = '';
	if (isset($p->boucles[$p->id_boucle])) {
		$connect = $p->boucles[$p->id_boucle]->sql_serveur;
	}

	$p->code = '(objet_test_si_publie(' . $_type . ',intval(' . $_id . '),' . _q($connect) . ")?' ':'')";
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
 * @balise
 * @see balise_INCLURE_dist()
 * @link https://www.spip.net/5505
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
function balise_PRODUIRE_dist($p) {
	$balise_inclure = charger_fonction('INCLURE', 'balise');
	$p = $balise_inclure($p);

	$p->code = str_replace('recuperer_fond(', 'produire_fond_statique(', $p->code);

	return $p;
}

/**
 * Compile la balise `#LARGEUR_ECRAN` qui définit la largeur d'écran
 * dans l'espace privé
 *
 * @balise
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
function balise_LARGEUR_ECRAN_dist($p) {
	$_class = interprete_argument_balise(1, $p);
	if (!$_class) {
		$_class = 'null';
	}
	$p->code = "(is_string($_class)?vide(\$GLOBALS['largeur_ecran']=$_class):(isset(\$GLOBALS['largeur_ecran'])?\$GLOBALS['largeur_ecran']:''))";

	return $p;
}


/**
 * Compile la balise `#CONST` qui retourne la valeur de la constante passée en argument
 *
 * @balise
 * @example `#CONST{_DIR_IMG}`
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_CONST_dist($p) {
	$_const = interprete_argument_balise(1, $p);
	if (!strlen($_const ?? '')) {
		$p->code = "''";
	}
	else {
		$p->code = "(defined($_const)?constant($_const):'')";
	}
	$p->interdire_scripts = false;

	return $p;
}
