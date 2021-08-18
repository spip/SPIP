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

/**
 * Fonctions génériques pour les balises `#URL_XXXX`
 *
 * Les balises `URL_$type` sont génériques, sauf quelques cas particuliers.
 *
 * @package SPIP\Core\Compilateur\Balises
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Génère le code compilé des balises d'URL
 *
 * Utilise le premier paramètre de la balise d'URL comme identifiant d'objet
 * s'il est donné, sinon le prendra dans un champ d'une boucle englobante.
 *
 * @uses generer_generer_url_arg()
 * @param string $type
 *     Type d'objet
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return string
 *     Code compilé
 **/
function generer_generer_url($type, $p) {
	$_id = interprete_argument_balise(1, $p);

	if (!$_id) {
		$primary = id_table_objet($type);
		$_id = champ_sql($primary, $p);
	}

	return generer_generer_url_arg($type, $p, $_id);
}

/**
 * Génère le code compilé des balises d'URL (en connaissant l'identifiant)
 *
 * - Si ces balises sont utilisées pour la base locale,
 *   production des appels à `generer_url_entite(id-courant, entite)`
 * - Si la base est externe et sous SPIP, on produit
 *
 *   - l'URL de l'objet si c'est une pièce jointe, ou sinon
 *   - l'URL du site local appliqué sur l'objet externe,
 *     ce qui permet de le voir à travers les squelettes du site local
 *
 * On communique le type-url distant à `generer_url_entite` mais il ne sert pas
 * car rien ne garantit que le .htaccess soit identique. À approfondir.
 *
 * @see generer_url_entite()
 *
 * @param string $type
 *     Type d'objet
 * @param Champ $p
 *     Pile au niveau de la balise
 * @param string $_id
 *     Code compilé permettant d'obtenir l'identifiant de l'objet
 * @return string
 *     Code compilé
 **/
function generer_generer_url_arg($type, $p, $_id) {
	if ($s = trouver_nom_serveur_distant($p)) {
		// si une fonction de generation des url a ete definie pour ce connect l'utiliser
		if (function_exists($f = 'generer_generer_url_' . $s)) {
			return $f($type, $_id, $s);
		}
		if (!$GLOBALS['connexions'][strtolower($s)]['spip_connect_version']) {
			return null;
		}
		$s = _q($s);
		# exception des urls de documents sur un serveur distant...
		if ($type == 'document') {
			return
				"quete_meta('adresse_site', $s) . '/' .\n\t" .
				"quete_meta('dir_img', $s) . \n\t" .
				"quete_fichier($_id,$s)";
		}
		$s = ", '', '', $s, quete_meta('type_urls', $s)";
	} else {
		$s = ", '', '', true";
	}

	return "urlencode_1738(generer_url_entite($_id, '$type'$s))";
}


/**
 * Compile la balise générique `#URL_xxx` qui génère l'URL d'un objet
 *
 * S'il existe une fonction spécifique de calcul d'URL pour l'objet demandé,
 * tel que `balise_URL_ARTICLE_dist()`, la fonction l'utilisera. Sinon,
 * on calcule une URL de façon générique.
 *
 * @balise
 * @uses generer_generer_url()
 * @example
 *     ```
 *     #URL_ARTICLE
 *     #URL_ARTICLE{3}
 *     ```
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_URL__dist($p) {

	$nom = $p->nom_champ;
	if ($nom === 'URL_') {
		$msg = array('zbug_balise_sans_argument', array('balise' => ' URL_'));
		erreur_squelette($msg, $p);
		$p->interdire_scripts = false;

		return $p;
	} elseif ($f = charger_fonction($nom, 'balise', true)) {
		return $f($p);
	} else {
		$nom = strtolower($nom);
		$code = generer_generer_url(substr($nom, 4), $p);
		$code = champ_sql($nom, $p, $code);
		$p->code = $code;
		if (!$p->etoile) {
			$p->code = "vider_url($code)";
		}
		$p->interdire_scripts = false;

		return $p;
	}
}

/**
 * Compile la balise `#URL_ARTICLE` qui génère l'URL d'un article
 *
 * Retourne l'URL (locale) d'un article mais retourne dans le cas
 * d'un article syndiqué (boucle SYNDIC_ARTICLES), son URL distante d'origine.
 *
 * @balise
 * @uses generer_generer_url()
 * @link https://www.spip.net/3963
 * @example
 *     ```
 *     #URL_ARTICLE
 *     #URL_ARTICLE{3}
 *     ```
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_URL_ARTICLE_dist($p) {

	// Cas particulier des boucles (SYNDIC_ARTICLES)
	if ($p->type_requete == 'syndic_articles') {
		$code = champ_sql('url', $p);
	} else {
		$code = generer_generer_url('article', $p);
	}

	$p->code = $code;
	if (!$p->etoile) {
		$p->code = "vider_url($code)";
	}
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Compile la balise `#URL_SITE` qui génère l'URL d'un site ou de cas spécifiques
 *
 * Génère une URL spécifique si la colonne SQL `url_site` est trouvée
 * (par exemple lien hypertexte d'un article), sinon l'URL d'un site syndiqué
 *
 * @balise
 * @uses generer_generer_url()
 * @see  calculer_url()
 * @link https://www.spip.net/3861
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_URL_SITE_dist($p) {
	$code = champ_sql('url_site', $p);
	if (strpos($code, '@$Pile[0]') !== false) {
		$code = generer_generer_url('site', $p);
		if ($code === null) {
			return null;
		}
	} else {
		if (!$p->etoile) {
			$code = "calculer_url($code,'','url', \$connect)";
		}
	}
	$p->code = $code;
	$p->interdire_scripts = false;

	return $p;
}

// Autres balises URL_*, qui ne concernent pas une table
// (historique)

/**
 * Compile la balise `#URL_SITE_SPIP` qui retourne l'URL du site
 * telle que définie dans la configuration
 *
 * @balise
 * @link https://www.spip.net/4623
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_URL_SITE_SPIP_dist($p) {
	$p->code = "sinon(\$GLOBALS['meta']['adresse_site'],'.')";
	$p->code = 'spip_htmlspecialchars(' . $p->code . ')';
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#URL_PAGE` qui retourne une URL de type « page »
 *
 * - `#URL_PAGE{nom}` génère l'url pour la page `nom`
 * - `#URL_PAGE{nom,param=valeur}` génère l'url pour la page `nom` avec des paramètres
 * - `#URL_PAGE` sans argument retourne l'URL courante.
 * - `#URL_PAGE*` retourne l'URL sans convertir les `&` en `&amp;`
 *
 * @balise
 * @link https://www.spip.net/4630
 * @see generer_url_public()
 * @example
 *     ```
 *     #URL_PAGE{backend} produit ?page=backend
 *     #URL_PAGE{backend,id_rubrique=1} est équivalent à
 *     [(#URL_PAGE{backend}|parametre_url{id_rubrique,1})]
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_URL_PAGE_dist($p) {

	$code = interprete_argument_balise(1, $p);
	$args = interprete_argument_balise(2, $p);
	if ($args == null) {
		$args = "''";
	}

	if ($s = trouver_nom_serveur_distant($p)) {
		// si une fonction de generation des url a ete definie pour ce connect l'utiliser
		// elle devra aussi traiter le cas derogatoire type=page
		if (function_exists($f = 'generer_generer_url_' . $s)) {
			if ($args and $args !== "''") {
				$code .= ", $args";
			}
			$code = $f('page', $code, $s);
			$p->code = $code;
			return $p;
		}
		$s = 'connect=' . addslashes($s);
		$args = (($args and $args !== "''") ? "$args . '&$s'" : "'$s'");
	}

	if (!$code) {
		$noentities = $p->etoile ? "'&'" : '';
		$code = "url_de_base() . preg_replace(',^./,', '', self($noentities))";
	} else {
		if (!$args) {
			$args = "''";
		}
		$noentities = $p->etoile ? ', true' : '';
		$code = "generer_url_public($code, $args$noentities)";
	}
	$p->code = $code;
	spip_log("Calcul url page : connect vaut $s ca donne :" . $p->code . " args $args", _LOG_INFO);

	#$p->interdire_scripts = true;
	return $p;
}


/**
 * Compile la balise `#URL_ECRIRE` qui retourne une URL d'une page de l'espace privé
 *
 * - `#URL_ECRIRE{nom}` génère l'url pour la page `nom` de l'espace privé
 * - `#URL_ECRIRE{nom,param=valeur}` génère l'url pour la page `nom` avec des paramètres
 * - `#URL_ECRIRE` génère l'url pour la page d'accueil de l'espace privé
 * - `#URL_ECRIRE*` retourne l'URL sans convertir les `&` en `&amp;`
 *
 * @balise
 * @link https://www.spip.net/5566
 * @see generer_url_ecrire()
 * @example
 *     ```
 *     #URL_ECRIRE{rubriques} -> ecrire/?exec=rubriques
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_URL_ECRIRE_dist($p) {

	$code = interprete_argument_balise(1, $p);
	if (!$code) {
		$fonc = "''";
	} else {
		$fonc = $code;
		$args = interprete_argument_balise(2, $p);
		if ($args === null) {
			$args = "''";
		}
		$noentities = $p->etoile ? ', true' : '';
		if (($args != "''") or $noentities) {
			$fonc .= ",$args$noentities";
		}
	}
	$p->code = 'generer_url_ecrire(' . $fonc . ')';
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#URL_ACTION_AUTEUR` qui retourne une URL d'action
 * sécurisée pour l'auteur en cours
 *
 * La balise accepte 3 paramètres. Les 2 premiers sont obligatoires :
 *
 * - le nom de l'action
 * - l'argument transmis à l'action (une chaîne de caractère)
 * - une éventuelle URL de redirection qui sert une fois l'action réalisée
 *
 * @balise
 * @see generer_action_auteur()
 * @example
 *     `#URL_ACTION_AUTEUR{converser,arg,redirect}` pourra produire
 *     `ecrire/?action=converser&arg=arg&hash=xxx&redirect=redirect`
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_URL_ACTION_AUTEUR_dist($p) {
	$p->descr['session'] = true;

	$p->code = interprete_argument_balise(1, $p);

	$args = interprete_argument_balise(2, $p);
	if ($args != "''" && $args !== null) {
		$p->code .= ',' . $args;
	}
	$redirect = interprete_argument_balise(3, $p);
	if ($redirect != "''" && $redirect !== null) {
		if ($args == "''" || $args === null) {
			$p->code .= ",''";
		}
		$p->code .= ',' . $redirect;
	}

	$p->code = 'generer_action_auteur(' . $p->code . ')';
	$p->interdire_scripts = false;

	return $p;
}
