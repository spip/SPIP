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
 * Fonctions de recherche et de reservation dans l'arborescence des boucles
 *
 * @package SPIP\Core\Compilateur\References
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Retrouver l'index de la boucle d'une balise
 *
 * Retrouve à quelle boucle appartient une balise, utile dans le cas
 * où une référence explicite est demandée
 *
 * - `#MABALISE` : l'index est celui de la première boucle englobante
 * - `#_autreboucle:MABALISE` : l'index est celui de la boucle _autreboucle
 *
 * @example
 *     Dans une balise dynamique ou calculée :
 *     ```
 *     $idb = index_boucle($p);
 *     ```
 *
 * @param Champ $p AST au niveau de la balise
 * @return string
 *
 *     - Identifiant de la boucle possédant ce champ.
 *     - '' si une référence explicite incorrecte est envoyée
 */
function index_boucle($p) {
	if (strlen($p->nom_boucle)) {
		// retourne l’index explicite demandé s’il existe
		if (!empty($p->boucles[$p->nom_boucle])) {
			return $p->nom_boucle;
		}
		return '';
	}
	return $p->id_boucle;
}


/**
 * Retrouve la boucle mère d’une balise, sauf si son nom est explicité
 *
 * - `#MABALISE` : l'index sera celui de la boucle parente
 * - `#_autreboucle:MABALISE` : l'index est celui de la boucle _autreboucle, si elle existe
 *
 * @example
 *     Dans une balise dynamique ou calculée :
 *     ```
 *     $idb = index_boucle_mere($p);
 *     ```
 *
 * @param Champ $p AST au niveau de la balise
 * @return string
 *
 *     - Identifiant de la boucle parente possédant ce champ, ou '' si pas de parent.
 *     - '' si une référence explicite incorrecte est envoyée
 */
function index_boucle_mere($p) {
	if (strlen($p->nom_boucle)) {
		// retourne l’index explicite demandé s’il existe
		if (!empty($p->boucles[$p->nom_boucle])) {
			return $p->nom_boucle;
		}
		return '';
	}
	if (!empty($p->descr['id_mere'])) {
		return $p->descr['id_mere'];
	}
	return '';
}

/**
 * Retourne la position dans la pile d'un champ SQL
 *
 * Retourne le code PHP permettant de récupérer un champ SQL dans
 * une boucle parente, en prenant la boucle la plus proche du sommet de pile
 * (indiqué par $idb).
 *
 * Si on ne trouve rien, on considère que ça doit provenir du contexte
 * (par l'URL ou l'include) qui a été recopié dans Pile[0]
 * (un essai d'affinage a débouché sur un bug vicieux)
 *
 * Si ca référence un champ SQL, on le mémorise dans la structure $boucles
 * afin de construire un requête SQL minimale (plutôt qu'un brutal 'SELECT *')
 *
 * @param string $idb Identifiant de la boucle
 * @param string $nom_champ Nom du champ SQL cherché
 * @param array $boucles AST du squelette
 * @param string $explicite
 *     Indique que le nom de la boucle est explicite dans la balise #_nomboucletruc:CHAMP
 * @param null|string $defaut
 *     Code par defaut si le champ n'est pas trouvé dans l'index.
 *     Utilise `($Pile[0][$nom_champ] ?? null)` si non fourni
 * @param bool $remonte_pile
 *     Permettre de remonter la pile des boucles ou non (dans ce cas on
 *     ne cherche que dans la 1ère boucle englobante)
 * @param bool $select
 *     Pour ajouter au select de la boucle, par defaut true
 * @return string
 *     Code PHP pour obtenir le champ SQL
 */
function index_pile(
	$idb,
	$nom_champ,
	&$boucles,
	$explicite = '',
	$defaut = null,
	$remonte_pile = true,
	$select = true
) {
	if (!is_string($defaut)) {
		$defaut = '($Pile[0][\'' . strtolower($nom_champ) . '\'] ?? null)';
	}

	$idb_origine = $idb;
	$nom_champ_origine = $nom_champ;

	$i = 0;
	if (strlen($explicite)) {
		// Recherche d'un champ dans un etage superieur
		while (($idb !== $explicite) && ($idb !== '')) {
			#	spip_log("Cherchexpl: $nom_champ '$explicite' '$idb' '$i'");
			$i++;
			$idb = $boucles[$idb]->id_parent;
		}
	}

	#	spip_log("Cherche: $nom_champ a partir de '$idb'");
	$nom_champ = strtolower($nom_champ);
	$conditionnel = [];
	// attention: entre la boucle nommee 0, "" et le tableau vide,
	// il y a incoherences qu'il vaut mieux eviter
	while (isset($boucles[$idb])) {
		$joker = true;
		// modifie $joker si tous les champs sont autorisés.
		// $t = le select pour le champ, si on l'a trouvé (ou si joker)
		// $c = le nom du champ demandé
		[$t, $c] = index_tables_en_pile($idb, $nom_champ, $boucles, $joker);
		if ($t) {
			if ($select and !in_array($t, $boucles[$idb]->select)) {
				$boucles[$idb]->select[] = $t;
			}
			// renseigner la boucle source de ce champ pour les traitements
			$boucles[$idb_origine]->index_champ[$nom_champ_origine] = $idb;
			$champ = '$Pile[$SP' . ($i ? "-$i" : '') . '][\'' . $c . '\']';
			if (!$joker) {
				return index_compose($conditionnel, $champ);
			}

			// tant que l'on trouve des tables avec joker, on continue
			// avec la boucle parente et on conditionne à l'exécution
			// la présence du champ. Si le champ existe à l'exécution
			// dans une boucle, il est pris, sinon on le cherche dans le parent...
			$conditionnel[] = "isset($champ)?$champ";
		}

		if ($remonte_pile) {
			#	spip_log("On remonte vers $i");
			// Sinon on remonte d'un cran
			$idb = $boucles[$idb]->id_parent;
			$i++;
		} else {
			$idb = null;
		}
	}

	#	spip_log("Pas vu $nom_champ");
	// esperons qu'il y sera
	// ou qu'on a fourni une valeur par "defaut" plus pertinent
	return index_compose($conditionnel, $defaut);
}

/**
 * Reconstuire la cascade de condition de recherche d'un champ
 *
 * On ajoute la valeur finale par défaut pour les balises dont on ne saura
 * qu'à l'exécution si elles sont definies ou non (boucle DATA)
 *
 * @param array $conditionnel Liste de codes PHP pour retrouver un champ
 * @param string $defaut Valeur par défaut si aucun des moyens ne l'a trouvé
 * @return string              Code PHP complet de recherche d'un champ
 */
function index_compose($conditionnel, $defaut) {
	while ($c = array_pop($conditionnel)) {
		// si on passe defaut = '', ne pas générer d'erreur de compilation.
		$defaut = "($c:(" . ($defaut ?: "''") . '))';
	}

	return $defaut;
}

/**
 * Cherche un champ dans une boucle
 *
 * Le champ peut être :
 *
 * - un alias d'un autre : il faut alors le calculer, éventuellement en
 *   construisant une jointure.
 * - présent dans la table : on l'utilise
 * - absent, mais le type de boucle l'autorise (joker des itérateurs DATA) :
 *   on l'utilise et lève le drapeau joker
 * - absent, on cherche une jointure et on l'utilise si on en trouve.
 *
 * @todo
 *     Ici la recherche de jointure sur l'absence d'un champ ne cherche
 *     une jointure que si des jointures explicites sont demandées,
 *     et non comme à d'autres endroits sur toutes les jointures possibles.
 *     Il faut homogénéiser cela.
 *
 *
 * @param string $idb Identifiant de la boucle
 * @param string $nom_champ Nom du champ SQL cherché
 * @param Boucle[] $boucles AST du squelette
 * @param bool $joker
 *     Le champ peut-il être inconnu à la compilation ?
 *     Ce drapeau sera levé si c'est le cas.
 * @return array
 *     Liste (Nom du champ véritable, nom du champ demandé).
 *     Le nom du champ véritable est une expression pour le SELECT de
 *     la boucle tel que "rubriques.titre" ou "mots.titre AS titre_mot".
 *     Les éléments de la liste sont vides si on ne trouve rien.
 **/
function index_tables_en_pile($idb, $nom_champ, &$boucles, &$joker) {

	$r = $boucles[$idb]->type_requete;
	// boucle recursive, c'est foutu...
	if ($r == TYPE_RECURSIF) {
		return [];
	}
	if (!$r) {
		$joker = false; // indiquer a l'appelant
		# continuer pour chercher l'erreur suivante
		return ["'#" . $r . ':' . $nom_champ . "'", ''];
	}

	$desc = $boucles[$idb]->show;
	// le nom du champ est il une exception de la table ? un alias ?
	$excep = $GLOBALS['exceptions_des_tables'][$r] ?? '';
	if ($excep) {
		$excep = $excep[$nom_champ] ?? '';
	}

	// il y a un alias connu pour ce champ
	if ($excep) {
		$joker = false; // indiquer a l'appelant
		return index_exception($boucles[$idb], $desc, $nom_champ, $excep);
	}

	// le champ existe dans la table, on le prend.
	if (isset($desc['field'][$nom_champ])) {
		$t = $boucles[$idb]->id_table;
		$joker = false; // indiquer a l'appelant
		return ["$t.$nom_champ", $nom_champ];
	}

	// Tous les champs sont-ils acceptés ?
	// Si oui, on retourne le champ, et on lève le flag joker
	// C'est le cas des itérateurs DATA qui acceptent tout
	// et testent la présence du champ à l'exécution et non à la compilation
	// car ils ne connaissent pas ici leurs contenus.
	if (
		/*$joker AND */
		isset($desc['field']['*'])
	) {
		$joker = true; // indiquer a l'appelant
		return [$nom_champ, $nom_champ];
	}

	$joker = false; // indiquer a l'appelant

	// la table de jointure est explicitement indiquée (rubrique.titre)
	if (preg_match('/^(.*)\.(.*)$/', $nom_champ, $r)) {
		[, $_table, $_nom_champ] = $r;
		if ($cle = trouver_jointure_champ($_nom_champ, $boucles[$idb], [$_table])) {
			$_alias = $cle . '_' . $_nom_champ;
			return index_exception(
				$boucles[$idb],
				$desc,
				$_alias,
				[$_table, $_nom_champ]
			);
		}
		return ['', ''];
	}

	// pas d'alias, pas de champ, pas de joker...
	// tenter via une jointure...

	// regarder si le champ est deja dans une jointure existante
	// sinon, si il y a des joitures explicites, la construire
	if (!$t = trouver_champ_exterieur($nom_champ, $boucles[$idb]->from, $boucles[$idb])) {
		if ($boucles[$idb]->jointures_explicites) {
			// [todo] Ne pas lancer que lorsque il y a des jointures explicites !!!!
			// fonctionnel, il suffit d'utiliser $boucles[$idb]->jointures au lieu de jointures_explicites
			// mais est-ce ce qu'on veut ?
			$jointures = preg_split('/\s+/', $boucles[$idb]->jointures_explicites);
			if ($cle = trouver_jointure_champ($nom_champ, $boucles[$idb], $jointures)) {
				$t = trouver_champ_exterieur($nom_champ, $boucles[$idb]->from, $boucles[$idb]);
			}
		}
	}

	if ($t) {
		// si on a trouvé une jointure possible, on fait comme
		// si c'était une exception pour le champ demandé
		return index_exception(
			$boucles[$idb],
			$desc,
			$nom_champ,
			[$t[1]['id_table'], reset($t[2])]
		);
	}

	return ['', ''];
}


/**
 * Retrouve un alias d'un champ dans une boucle
 *
 * Référence à une entite SPIP alias d'un champ SQL.
 * Ça peut même être d'un champ dans une jointure qu'il faut provoquer
 * si ce n'est fait
 *
 * @param Boucle $boucle Boucle dont on prend un alias de champ
 * @param array $desc Description de la table SQL de la boucle
 * @param string $nom_champ Nom du champ original demandé
 * @param array $excep
 *     Description de l'exception pour ce champ. Peut être :
 *
 *     - string : nom du champ véritable dans la table
 *     - array :
 *         - liste (table, champ) indique que le véritable champ
 *           est dans une autre table et construit la jointure dessus
 *         - liste (table, champ, fonction) idem, mais en passant un
 *           nom de fonction qui s'occupera de créer la jointure.
 * @return array
 *     Liste (nom du champ alias, nom du champ). Le nom du champ alias
 *     est une expression pour le SELECT de la boucle du style "mots.titre AS titre_mot"
 **/
function index_exception(&$boucle, $desc, $nom_champ, $excep) {
	static $trouver_table;
	if (!$trouver_table) {
		$trouver_table = charger_fonction('trouver_table', 'base');
	}

	if (is_array($excep)) {
		// permettre aux plugins de gerer eux meme des jointures derogatoire ingerables
		$t = null;
		if (count($excep) == 3) {
			$index_exception_derogatoire = array_pop($excep);
			$t = $index_exception_derogatoire($boucle, $desc, $nom_champ, $excep);
		}
		if ($t == null) {
			[$e, $x] = $excep;  #PHP4 affecte de gauche a droite
			$excep = $x;    #PHP5 de droite a gauche !
			$j = $trouver_table($e, $boucle->sql_serveur);
			if (!$j) {
				return ['', ''];
			}
			$e = $j['table'];
			if (!$t = array_search($e, $boucle->from)) {
				$k = $j['key']['PRIMARY KEY'];
				if (strpos($k, ',')) {
					$l = (preg_split('/\s*,\s*/', $k));
					$k = $desc['key']['PRIMARY KEY'];
					if (!in_array($k, $l)) {
						spip_log("jointure impossible $e " . join(',', $l));

						return ['', ''];
					}
				}
				$k = [$boucle->id_table, [$e], $k];
				fabrique_jointures($boucle, [$k]);
				$t = array_search($e, $boucle->from);
			}
		}
	} else {
		$t = $boucle->id_table;
	}
	// demander a SQL de gerer le synonyme
	// ca permet que excep soit dynamique (Cedric, 2/3/06)
	if ($excep != $nom_champ) {
		$excep .= ' AS ' . $nom_champ;
	}

	return ["$t.$excep", $nom_champ];
}

/**
 * Demande le champ '$champ' dans la pile
 *
 * Le champ est cherché dans l'empilement de boucles, sinon dans la valeur
 * par défaut (qui est l'environnement du squelette si on ne la précise pas).
 *
 * @api
 * @param string $champ
 *     Champ recherché
 * @param Champ $p
 *     AST au niveau de la balise
 * @param null|string $defaut
 *     Code de la valeur par défaut si on ne trouve pas le champ dans une
 *     des boucles parentes. Sans précision, il sera pris dans l'environnement
 *     du squelette.
 *     Passer $defaut = '' pour ne pas prendre l'environnement.
 * @param bool $remonte_pile
 *     Permettre de remonter dans la pile des boucles pour trouver le champ
 * @return string
 *     Code PHP pour retrouver le champ
 */
function champ_sql($champ, $p, $defaut = null, $remonte_pile = true) {
	return index_pile($p->id_boucle, $champ, $p->boucles, $p->nom_boucle, $defaut, $remonte_pile);
}


/**
 * Calcule et retourne le code PHP d'exécution d'une balise SPIP et des ses filtres
 *
 * Cette fonction qui sert d'API au compilateur demande à calculer
 * le code PHP d'une balise, puis lui applique les filtres (automatiques
 * et décrits dans le squelette)
 *
 * @uses calculer_balise()
 * @uses applique_filtres()
 *
 * @param Champ $p
 *     AST au niveau de la balise
 * @return string
 *     Code PHP pour d'exécution de la balise et de ses filtres
 **/
function calculer_champ($p) {
	$p = calculer_balise($p->nom_champ, $p);

	return applique_filtres($p);
}


/**
 * Calcule et retourne le code PHP d'exécution d'une balise SPIP
 *
 * Cette fonction qui sert d'API au compilateur demande à calculer
 * le code PHP d'une balise (cette fonction ne calcule pas les éventuels
 * filtres de la balise).
 *
 * Pour une balise nommmée `NOM`, elle demande à `charger_fonction()` de chercher
 * s'il existe une fonction `balise_NOM` ou `balise_NOM_dist`
 * éventuellement en chargeant le fichier `balise/NOM.php.`
 *
 * Si la balise est de la forme `PREFIXE_SUFFIXE` (cf `LOGO_*` et `URL_*`)
 * elle fait de même avec juste le `PREFIXE`.
 *
 * S'il n'y a pas de fonction trouvée, on considère la balise comme une référence
 * à une colonne de table SQL connue, sinon à l'environnement (cf. `calculer_balise_DEFAUT_dist()`).
 *
 * Les surcharges des colonnes SQL via charger_fonction sont donc possibles.
 *
 * @uses calculer_balise_DEFAUT_dist()
 *     Lorsqu'aucune fonction spécifique n'est trouvée, ou que ces fonctions spécifiques ont retourné null.
 * @see charger_fonction()
 *     Pour la recherche des fonctions de balises
 *
 * @param string $nom
 *     Nom de la balise
 * @param Champ $p
 *     AST au niveau de la balise
 * @return Champ
 *     Pile complétée par le code PHP pour l'exécution de la balise et de ses filtres
 **/
function calculer_balise(string $nom, \Champ $p): \Champ {

	// S'agit-t-il d'une balise_XXXX[_dist]() ?
	if ($f = charger_fonction($nom, 'balise', true)) {
		$p->balise_calculee = true;
		$res = $f($p);
		if ($res !== null and is_object($res)) {
			return $res;
		}
	}

	// Certaines des balises comportant un _ sont generiques
	if ($balise_generique = chercher_balise_generique($nom)) {
		$res = $balise_generique['fonction_generique']($p);
		if ($res !== null and is_object($res)) {
			return $res;
		}
	}

	$f = charger_fonction('DEFAUT', 'calculer_balise');

	return $f($nom, $p);
}


/**
 * Calcule et retourne le code PHP d'exécution d'une balise SPIP non déclarée
 *
 * Cette fonction demande à calculer le code PHP d'une balise qui
 * n'a pas de fonction spécifique.
 *
 * On considère la balise comme une référence à une colonne de table SQL
 * connue, sinon à l'environnement.
 *
 * @uses index_pile()
 *     Pour la recherche de la balise comme colonne SQL ou comme environnement
 * @note
 *     Le texte de la balise est retourné si il ressemble à une couleur
 *     et qu'aucun champ correspondant n'a été trouvé, comme `#CCAABB`
 *
 * @param string $nom
 *     Nom de la balise
 * @param Champ $p
 *     AST au niveau de la balise
 * @return string
 *     Code PHP pour d'exécution de la balise et de ses filtres
 **/
function calculer_balise_DEFAUT_dist($nom, $p) {

	// ca pourrait etre un champ SQL homonyme,
	$p->code = index_pile($p->id_boucle, $nom, $p->boucles, $p->nom_boucle);

	// compatibilite: depuis qu'on accepte #BALISE{ses_args} sans [(...)] autour
	// il faut recracher {...} quand ce n'est finalement pas des args
	if ($p->fonctions and (!$p->fonctions[0][0]) and $p->fonctions[0][1]) {
		$code = addslashes($p->fonctions[0][1]);
		$p->code .= " . '$code'";
	}

	// ne pas passer le filtre securite sur les id_xxx
	if (strpos($nom, 'ID_') === 0) {
		$p->interdire_scripts = false;
	}

	// Compatibilite ascendante avec les couleurs html (#FEFEFE) :
	// SI le champ SQL n'est pas trouve
	// ET si la balise a une forme de couleur
	// ET s'il n'y a ni filtre ni etoile
	// ALORS retourner la couleur.
	// Ca permet si l'on veut vraiment de recuperer [(#ACCEDE*)]
	if (
		preg_match('/^[A-F]{1,6}$/i', $nom)
		and !$p->etoile
		and !$p->fonctions
	) {
		$p->code = "'#$nom'";
		$p->interdire_scripts = false;
	}

	return $p;
}


/** Code PHP d'exécution d'une balise dynamique */
define('CODE_EXECUTER_BALISE', "executer_balise_dynamique('%s',
	array(%s%s),
	array(%s%s))");


/**
 * Calcule le code PHP d'exécution d'une balise SPIP dynamique
 *
 * Calcule les balises dynamiques, notamment les `formulaire_*`.
 *
 * Inclut le fichier associé à son nom, qui contient la fonction homonyme
 * donnant les arguments à chercher dans la pile, et qui sont donc compilés.
 *
 * On leur adjoint les arguments explicites de la balise (cf `#LOGIN{url}`)
 * et d'éventuelles valeurs transmises d'autorité par la balise.
 * (cf https://core.spip.net/issues/1728)
 *
 * La fonction `executer_balise_dynamique()` définie par la
 * constante `CODE_EXECUTER_BALISE` recevra à l'exécution la valeur de tout ca.
 *
 * @uses collecter_balise_dynamique()
 *     Qui calcule le code d'exécution de chaque argument de la balise
 * @see  executer_balise_dynamique()
 *     Code PHP produit qui chargera les fonctions de la balise dynamique à l'exécution,
 *     appelée avec les arguments calculés.
 * @param Champ $p
 *     AST au niveau de la balise
 * @param string $nom
 *     Nom de la balise dynamique
 * @param array $l
 *     Liste des noms d'arguments (balises) à collecter
 * @param array $supp
 *     Liste de données supplémentaires à transmettre au code d'exécution.
 * @return Champ
 *     Balise complétée de son code d'exécution
 **/
function calculer_balise_dynamique($p, $nom, $l, $supp = []) {

	if (!balise_distante_interdite($p)) {
		$p->code = "''";

		return $p;
	}
	// compatibilite: depuis qu'on accepte #BALISE{ses_args} sans [(...)] autour
	// il faut recracher {...} quand ce n'est finalement pas des args
	if ($p->fonctions and (!$p->fonctions[0][0]) and $p->fonctions[0][1]) {
		$p->fonctions = [];
	}

	if ($p->param and ($c = $p->param[0])) {
		// liste d'arguments commence toujours par la chaine vide
		array_shift($c);
		// construire la liste d'arguments comme pour un filtre
		$param = compose_filtres_args($p, $c, ',');
	} else {
		$param = '';
	}
	$collecte = collecter_balise_dynamique($l, $p, $nom);

	$p->code = sprintf(
		CODE_EXECUTER_BALISE,
		$nom,
		join(',', $collecte),
		($collecte ? $param : substr($param, 1)), # virer la virgule
		memoriser_contexte_compil($p),
		(!$supp ? '' : (', ' . join(',', $supp)))
	);

	$p->interdire_scripts = false;

	return $p;
}


/**
 * Construction du tableau des arguments d'une balise dynamique.
 *
 * Pour chaque argument (un nom de balise), crée le code PHP qui le calculera.
 *
 * @note
 *     Ces arguments peuvent être eux-même des balises (cf FORMULAIRE_SIGNATURE)
 *     mais gare au bouclage (on peut s'aider de `$nom` pour le réperer au besoin)
 *
 *     En revanche ils n'ont pas de filtres, donc on appelle `calculer_balise()` qui
 *     ne s'occupe pas de ce qu'il y a dans `$p` (mais qui va y ecrire le code)
 *
 * @uses calculer_balise()
 *     Pour obtenir le code d'éxécution de chaque argument.
 *
 * @param array $l
 *     Liste des noms d'arguments (balises) à collecter (chaque argument
 *     de la balise dynamique est considéré comme étant un nom de balise)
 * @param Champ $p
 *     AST au niveau de la balise
 * @param string $nom
 *     Nom de la balise
 * @return array
 *     Liste des codes PHP d'éxecution des balises collectées
 **/
function collecter_balise_dynamique(array $l, \Champ &$p, string $nom): array {
	$args = [];
	foreach ($l as $c) {
		if ($c === null) {
			$args[] = 'null';
		} else {
			$x = calculer_balise($c, $p);
			$args[] = $x->code;
		}
	}

	return $args;
}


/**
 * Récuperer le nom du serveur
 *
 * Mais pas si c'est un serveur spécifique dérogatoire
 *
 * @param Champ $p
 *     AST positionné sur la balise
 * @return string
 *     Nom de la connexion
 **/
function trouver_nom_serveur_distant($p) {
	$nom = $p->id_boucle;
	if (
		$nom
		and isset($p->boucles[$nom])
	) {
		$s = $p->boucles[$nom]->sql_serveur;
		if (
			strlen($s)
			and strlen($serveur = strtolower($s))
			and !in_array($serveur, $GLOBALS['exception_des_connect'])
		) {
			return $serveur;
		}
	}

	return '';
}


/**
 * Teste si une balise est appliquée sur une base distante
 *
 * La fonction loge une erreur si la balise est utilisée sur une
 * base distante et retourne false dans ce cas.
 *
 * @note
 *     Il faudrait savoir traiter les formulaires en local
 *     tout en appelant le serveur SQL distant.
 *     En attendant, cette fonction permet de refuser une authentification
 *     sur quelque-chose qui n'a rien a voir.
 *
 * @param Champ $p
 *     AST positionné sur la balise
 * @return bool
 *
 *     - true : La balise est autorisée
 *     - false : La balise est interdite car le serveur est distant
 **/
function balise_distante_interdite($p) {
	$nom = $p->id_boucle;

	if ($nom and trouver_nom_serveur_distant($p)) {
		spip_log($nom . ':' . $p->nom_champ . ' ' . _T('zbug_distant_interdit'));

		return false;
	}

	return true;
}


//
// Traitements standard de divers champs
// definis par $table_des_traitements, cf. ecrire/public/interfaces
//
function champs_traitements($p) {

	if (isset($GLOBALS['table_des_traitements'][$p->nom_champ])) {
		$ps = $GLOBALS['table_des_traitements'][$p->nom_champ];
	} else {
		// quand on utilise un traitement catch-all *
		// celui-ci ne s'applique pas sur les balises calculees qui peuvent gerer
		// leur propre securite
		if (!$p->balise_calculee) {
			$ps = $GLOBALS['table_des_traitements']['*'];
		} else {
			$ps = false;
		}
	}

	if (is_array($ps)) {
		// Recuperer le type de boucle (articles, DATA) et la table SQL sur laquelle elle porte
		$idb = index_boucle($p);
		// si le champ a ete trouve dans une boucle parente sa source est renseignee ici
		if (!empty($p->boucles[$idb]->index_champ[$p->nom_champ])) {
			$idb = $p->boucles[$idb]->index_champ[$p->nom_champ];
		}

		// mais on peut aussi etre hors boucle. Se mefier.
		$type_requete = $p->boucles[$idb]->type_requete ?? false;
		$table_sql = $p->boucles[$idb]->show['table_sql'] ?? false;

		// bien prendre en compte les alias de boucles (hierarchie => rubrique, syndication => syncdic, etc.)
		if ($type_requete and isset($GLOBALS['table_des_tables'][$type_requete])) {
			$type_alias = $type_requete;
			$type_requete = $GLOBALS['table_des_tables'][$type_requete];
		} else {
			$type_alias = false;
		}

		// le traitement peut n'etre defini que pour une table en particulier "spip_articles"
		if ($table_sql and isset($ps[$table_sql])) {
			$ps = $ps[$table_sql];
		} // ou pour une boucle en particulier "DATA","articles"
		elseif ($type_requete and isset($ps[$type_requete])) {
			$ps = $ps[$type_requete];
		} // ou pour une boucle utilisant un alias ("hierarchie")
		elseif ($type_alias and isset($ps[$type_alias])) {
			$ps = $ps[$type_alias];
		} // ou pour indifféremment quelle que soit la boucle
		elseif (isset($ps[0])) {
			$ps = $ps[0];
		} else {
			$ps = false;
		}
	}

	if (!$ps) {
		return $p->code;
	}

	// Si une boucle DOCUMENTS{doublons} est presente dans le squelette,
	// ou si in INCLURE contient {doublons}
	// on insere une fonction de remplissage du tableau des doublons
	// dans les filtres propre() ou typo()
	// (qui traitent les raccourcis <docXX> referencant les docs)

	if (
		isset($p->descr['documents'])
		and
		$p->descr['documents']
		and (
			(strpos($ps, 'propre') !== false)
			or
			(strpos($ps, 'typo') !== false)
		)
	) {
		$ps = 'traiter_doublons_documents($doublons, ' . $ps . ')';
	}

	// La protection des champs par |safehtml est assuree par les extensions
	// dans la declaration des traitements des champs sensibles

	// Remplacer enfin le placeholder %s par le vrai code de la balise
	return str_replace('%s', $p->code, $ps);
}


//
// Appliquer les filtres a un champ [(#CHAMP|filtre1|filtre2)]
// retourne un code php compile exprimant ce champ filtre et securise
//  - une etoile => pas de processeurs standards
//  - deux etoiles => pas de securite non plus !
//
function applique_filtres($p) {

	// Traitements standards (cf. supra)
	if ($p->etoile == '') {
		$code = champs_traitements($p);
	} else {
		$code = $p->code;
	}

	// Appliquer les filtres perso
	if ($p->param) {
		$code = compose_filtres($p, $code);
	}

	// S'il y a un lien avec la session, ajouter un code qui levera
	// un drapeau dans la structure d'invalidation $Cache
	if (isset($p->descr['session'])) {
		$code = "invalideur_session(\$Cache, $code)";
	}

	$code = sandbox_composer_interdire_scripts($code, $p);

	return $code;
}

// Cf. function pipeline dans ecrire/inc_utils.php
function compose_filtres(&$p, $code) {

	$image_miette = false;
	foreach ($p->param as $filtre) {
		$fonc = array_shift($filtre);
		if (!$fonc) {
			continue;
		} // normalement qu'au premier tour.
		$is_filtre_image = ((substr($fonc, 0, 6) == 'image_') and $fonc != 'image_graver');
		if ($image_miette and !$is_filtre_image) {
			// il faut graver maintenant car apres le filtre en cours
			// on est pas sur d'avoir encore le nom du fichier dans le pipe
			$code = "filtrer('image_graver', $code)";
			$image_miette = false;
		}

		// recuperer les arguments du filtre,
		// a separer par "," ou ":" dans le cas du filtre "?{a,b}"
		$countfiltre = is_countable($filtre) ? count($filtre) : 0;
		if ($fonc !== '?') {
			$sep = ',';
		} else {
			$sep = ':';
			// |?{a,b} *doit* avoir exactement 2 arguments ; on les force
			if ($countfiltre != 2) {
				$filtre = [$filtre[0] ?? '', $filtre[1] ?? ''];
				$countfiltre = 2;
			}
		}
		$arglist = compose_filtres_args($p, $filtre, $sep);
		$logique = filtre_logique($fonc, $code, substr($arglist, 1));
		if ($logique) {
			$code = $logique;
		} else {
			$code = sandbox_composer_filtre($fonc, $code, $arglist, $p, $countfiltre);
			if ($is_filtre_image) {
				$image_miette = true;
			}
		}
	}
	// ramasser les images intermediaires inutiles et graver l'image finale
	if ($image_miette) {
		$code = "filtrer('image_graver',$code)";
	}

	return $code;
}

// Filtres et,ou,oui,non,sinon,xou,xor,and,or,not,yes
// et comparateurs
function filtre_logique($fonc, $code, $arg) {

	switch (true) {
		case in_array($fonc, $GLOBALS['table_criteres_infixes']):
			return "($code $fonc $arg)";
		case ($fonc == 'and') or ($fonc == 'et'):
			return "((($code) AND ($arg)) ?' ' :'')";
		case ($fonc == 'or') or ($fonc == 'ou'):
			return "((($code) OR ($arg)) ?' ' :'')";
		case ($fonc == 'xor') or ($fonc == 'xou'):
			return "((($code) XOR ($arg)) ?' ' :'')";
		case ($fonc == 'sinon'):
			return "(((\$a = $code) OR (is_string(\$a) AND strlen(\$a))) ? \$a : $arg)";
		case ($fonc == 'not') or ($fonc == 'non'):
			return "(($code) ?'' :' ')";
		case ($fonc == 'yes') or ($fonc == 'oui'):
			return "(($code) ?' ' :'')";
	}

	return '';
}

function compose_filtres_args($p, $args, $sep) {
	$arglist = '';
	foreach ($args as $arg) {
		$arglist .= $sep .
			calculer_liste($arg, $p->descr, $p->boucles, $p->id_boucle);
	}

	return $arglist;
}


/**
 * Réserve les champs necessaires à la comparaison avec le contexte donné par
 * la boucle parente.
 *
 * Attention en recursif il faut les réserver chez soi-même ET chez sa maman
 *
 * @param string $idb Identifiant de la boucle
 * @param string $nom_champ
 * @param array $boucles AST du squelette
 * @param null|string $defaut
 * @return
 **/
function calculer_argument_precedent($idb, $nom_champ, &$boucles, $defaut = null) {

	// si recursif, forcer l'extraction du champ SQL mais ignorer le code
	if ($boucles[$idb]->externe) {
		index_pile($idb, $nom_champ, $boucles, '', $defaut);
		// retourner $Pile[$SP] et pas $Pile[0] si recursion en 1ere boucle
		// on ignore le defaut fourni dans ce cas
		$defaut = "(\$Pile[\$SP]['$nom_champ'] ?? null)";
	}

	return index_pile($boucles[$idb]->id_parent, $nom_champ, $boucles, '', $defaut);
}

//
// Rechercher dans la pile des boucles actives celle ayant un critere
// comportant un certain $motif, et construire alors une reference
// a l'environnement de cette boucle, qu'on indexe avec $champ.
// Sert a referencer une cellule non declaree dans la table et pourtant la.
// Par exemple pour la balise #POINTS on produit $Pile[$SP-n]['points']
// si la n-ieme boucle a un critere "recherche", car on sait qu'il a produit
// "SELECT XXXX AS points"
//

function rindex_pile($p, $champ, $motif) {
	$n = 0;
	$b = $p->id_boucle;
	$p->code = '';
	while ($b != '') {
		foreach ($p->boucles[$b]->criteres as $critere) {
			if ($critere->op == $motif) {
				$p->code = '$Pile[$SP' . (($n == 0) ? '' : "-$n") .
					"]['$champ']";
				$b = '';
				break 2;
			}
		}
		$n++;
		$b = $p->boucles[$b]->id_parent;
	}

	// si on est hors d'une boucle de {recherche}, cette balise est vide
	if (!$p->code) {
		$p->code = "''";
	}

	$p->interdire_scripts = false;

	return $p;
}

/**
 * Retourne le nom de la balise indiquée pour les messages d’erreurs
 * @param Pile $p Description de la balise
 * @param string $champ Nom du champ
 * @return string Nom de la balise, avec indication de boucle explicite si présent.
 */
function zbug_presenter_champ($p, $champ = '') {
	$balise = $champ ?: $p->nom_champ;
	$explicite = $p->nom_boucle ? $p->nom_boucle . ':' : '';
	return "#{$explicite}{$balise}";
}
