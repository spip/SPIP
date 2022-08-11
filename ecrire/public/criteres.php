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
 * Définition des {criteres} d'une boucle
 *
 * @package SPIP\Core\Compilateur\Criteres
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Une Regexp repérant une chaine produite par le compilateur,
 * souvent utilisée pour faire de la concaténation lors de la compilation
 * plutôt qu'à l'exécution, i.e. pour remplacer 'x'.'y' par 'xy'
 **/
define('_CODE_QUOTE', ",^(\n//[^\n]*\n)? *'(.*)' *$,");


/**
 * Compile le critère {racine}
 *
 * Ce critère sélectionne les éléments à la racine d'une hiérarchie,
 * c'est à dire ayant id_parent=0
 *
 * @link https://www.spip.net/@racine
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 **/
function critere_racine_dist($idb, &$boucles, $crit) {

	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$id_parent = $GLOBALS['exceptions_des_tables'][$boucle->id_table]['id_parent'] ?? 'id_parent';

	$c = ["'='", "'$boucle->id_table." . "$id_parent'", 0];
	$boucle->where[] = ($crit->not ? ["'NOT'", $c] : $c);
}


/**
 * Compile le critère {exclus}
 *
 * Exclut du résultat l’élément dans lequel on se trouve déjà
 *
 * @link https://www.spip.net/@exclus
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void|array
 **/
function critere_exclus_dist($idb, &$boucles, $crit) {
	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$id = $boucle->primary;

	if ($not or !$id) {
		return ['zbug_critere_inconnu', ['critere' => $not . $crit->op]];
	}
	$arg = kwote(calculer_argument_precedent($idb, $id, $boucles));
	$boucle->where[] = ["'!='", "'$boucle->id_table." . "$id'", $arg];
}


/**
 * Compile le critère {doublons} ou {unique}
 *
 * Ce critères enlève de la boucle les éléments déjà sauvegardés
 * dans un précédent critère {doublon} sur une boucle de même table.
 *
 * Il est possible de spécifier un nom au doublon tel que {doublons sommaire}
 *
 * @link https://www.spip.net/@doublons
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void|array
 **/
function critere_doublons_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$primary = $boucle->primary;

	// la table nécessite une clé primaire, non composée
	if (!$primary or strpos($primary, ',')) {
		return ['zbug_doublon_sur_table_sans_cle_primaire'];
	}

	$not = ($crit->not ? '' : 'NOT');

	// le doublon s'applique sur un type de boucle (article)
	$nom = "'" . $boucle->type_requete . "'";

	// compléter le nom avec un nom précisé {doublons nom}
	// on obtient $nom = "'article' . 'nom'"
	if (isset($crit->param[0])) {
		$nom .= '.' . calculer_liste($crit->param[0], $idb, $boucles, $boucles[$idb]->id_parent);
	}

	// code qui déclarera l'index du stockage de nos doublons (pour éviter une notice PHP)
	$init_comment = "\n\n\t// Initialise le(s) critère(s) doublons\n";
	$init_code = "\tif (!isset(\$doublons[\$d = $nom])) { \$doublons[\$d] = ''; }\n";

	// on crée un sql_in avec la clé primaire de la table
	// et la collection des doublons déjà emmagasinés dans le tableau
	// $doublons et son index, ici $nom

	// debut du code "sql_in('articles.id_article', "
	$debut_in = "sql_in('" . $boucle->id_table . '.' . $primary . "', ";
	// lecture des données du doublon "$doublons[$doublon_index[] = "
	// Attention : boucle->doublons désigne une variable qu'on affecte
	$debut_doub = '$doublons[' . (!$not ? '' : ($boucle->doublons . '[]= '));

	// le debut complet du code des doublons
	$debut_doub = $debut_in . $debut_doub;

	// nom du doublon "('article' . 'nom')]"
	$fin_doub = "($nom)]";

	// si on trouve un autre critère doublon,
	// on fusionne pour avoir un seul IN, et on s'en va !
	foreach ($boucle->where as $k => $w) {
		if (strpos($w[0], $debut_doub) === 0) {
			// fusionner le sql_in (du where)
			$boucle->where[$k][0] = $debut_doub . $fin_doub . ' . ' . substr($w[0], strlen($debut_in));
			// fusionner l'initialisation (du hash) pour faire plus joli
			$x = strpos($boucle->hash, $init_comment);
			$len = strlen($init_comment);
			$boucle->hash =
				substr($boucle->hash, 0, $x + $len) . $init_code . substr($boucle->hash, $x + $len);

			return;
		}
	}

	// mettre l'ensemble dans un tableau pour que ce ne soit pas vu comme une constante
	$boucle->where[] = [$debut_doub . $fin_doub . ", '" . $not . "')"];

	// déclarer le doublon s'il n'existe pas encore
	$boucle->hash .= $init_comment . $init_code;


	# la ligne suivante avait l'intention d'eviter une collecte deja faite
	# mais elle fait planter une boucle a 2 critere doublons:
	# {!doublons A}{doublons B}
	# (de http://article.gmane.org/gmane.comp.web.spip.devel/31034)
	#	if ($crit->not) $boucle->doublons = "";
}


/**
 * Compile le critère {lang_select}
 *
 * Permet de restreindre ou non une boucle en affichant uniquement
 * les éléments dans la langue en cours. Certaines boucles
 * tel que articles et rubriques restreignent par défaut sur la langue
 * en cours.
 *
 * Sans définir de valeur au critère, celui-ci utilise 'oui' comme
 * valeur par défaut.
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 **/
function critere_lang_select_dist($idb, &$boucles, $crit) {
	if (!isset($crit->param[1][0]) or !($param = $crit->param[1][0]->texte)) {
		$param = 'oui';
	}
	if ($crit->not) {
		$param = ($param == 'oui') ? 'non' : 'oui';
	}
	$boucle = &$boucles[$idb];
	$boucle->lang_select = $param;
}


/**
 * Compile le critère {debut_xxx}
 *
 * Limite le nombre d'éléments affichés.
 *
 * Ce critère permet de faire commencer la limitation des résultats
 * par une variable passée dans l’URL et commençant par 'debut_' tel que
 * {debut_page,10}. Le second paramètre est le nombre de résultats à
 * afficher.
 *
 * Note : il est plus simple d'utiliser le critère pagination.
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 **/
function critere_debut_dist($idb, &$boucles, $crit) {
	[$un, $deux] = $crit->param;
	$un = $un[0]->texte;
	$deux = $deux[0]->texte;
	if ($deux) {
		$boucles[$idb]->limit =
			'intval($Pile[0]["debut' . $un . '"]) . ",' . $deux . '"';
	} else {
		calculer_critere_DEFAUT_dist($idb, $boucles, $crit);
	}
}


/**
 * Compile le critère `pagination` qui demande à paginer une boucle.
 *
 * Demande à paginer la boucle pour n'afficher qu'une partie des résultats,
 * et gère l'affichage de la partie de page demandée par debut_xx dans
 * dans l'environnement du squelette.
 *
 * Le premier paramètre indique le nombre d'éléments par page, le second,
 * rarement utilisé permet de définir le nom de la variable désignant la
 * page demandée (`debut_xx`), qui par défaut utilise l'identifiant de la boucle.
 *
 * @critere
 * @see balise_PAGINATION_dist()
 * @link https://www.spip.net/3367 Le système de pagination
 * @link https://www.spip.net/4867 Le critère pagination
 * @example
 *     ```
 *     {pagination}
 *     {pagination 20}
 *     {pagination #ENV{pages,5}} etc
 *     {pagination 20 #ENV{truc,chose}} pour utiliser la variable debut_#ENV{truc,chose}
 *     ```
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 **/
function critere_pagination_dist($idb, &$boucles, $crit) {

	$boucle = &$boucles[$idb];
	// definition de la taille de la page
	$pas = !isset($crit->param[0][0]) ? "''"
		: calculer_liste([$crit->param[0][0]], $idb, $boucles, $boucle->id_parent);

	if (!preg_match(_CODE_QUOTE, $pas, $r)) {
		$pas = "((\$a = intval($pas)) ? \$a : 10)";
	} else {
		$r = intval($r[2]);
		$pas = strval($r ?: 10);
	}

	// Calcul du nommage de la pagination si il existe.
	// La nouvelle syntaxe {pagination 20, nom} est prise en compte et privilégiée mais on reste
	// compatible avec l'ancienne car certains cas fonctionnent correctement
	$type = "'$idb'";
	// Calcul d'un nommage spécifique de la pagination si précisé.
	// Syntaxe {pagination 20, nom}
	if (isset($crit->param[0][1])) {
		$type = calculer_liste([$crit->param[0][1]], $idb, $boucles, $boucle->id_parent);
	} // Ancienne syntaxe {pagination 20 nom} pour compatibilité
	elseif (isset($crit->param[1][0])) {
		$type = calculer_liste([$crit->param[1][0]], $idb, $boucles, $boucle->id_parent);
	}

	$debut = ($type[0] !== "'") ? "'debut'.$type" : ("'debut" . substr($type, 1));
	$boucle->modificateur['debut_nom'] = $type;
	$partie =
		// tester si le numero de page demande est de la forme '@yyy'
		'isset($Pile[0][' . $debut . ']) ? $Pile[0][' . $debut . '] : _request(' . $debut . ");\n"
		. "\tif (\$debut_boucle && \$debut_boucle[0] === '@') {\n"
		. "\t\t" . '$debut_boucle = $Pile[0][' . $debut . '] = quete_debut_pagination(\'' . $boucle->primary . '\',$Pile[0][\'@' . $boucle->primary . '\'] = substr($debut_boucle,1),' . $pas . ',$iter);' . "\n"
		. "\t\t" . '$iter->seek(0);' . "\n"
		. "\t}\n"
		. "\t" . '$debut_boucle = intval($debut_boucle)';

	$boucle->hash .= '
	$command[\'pagination\'] = array((isset($Pile[0][' . $debut . ']) ? $Pile[0][' . $debut . '] : null), ' . $pas . ');';

	$boucle->total_parties = $pas;
	calculer_parties($boucles, $idb, $partie, 'p+');
	// ajouter la cle primaire dans le select pour pouvoir gerer la pagination referencee par @id
	// sauf si pas de primaire, ou si primaire composee
	// dans ce cas, on ne sait pas gerer une pagination indirecte
	$t = $boucle->id_table . '.' . $boucle->primary;
	if (
		$boucle->primary
		and !preg_match('/[,\s]/', $boucle->primary)
		and !in_array($t, $boucle->select)
	) {
		$boucle->select[] = $t;
	}
}


/**
 * Compile le critère `recherche` qui permet de sélectionner des résultats
 * d'une recherche.
 *
 * Le texte cherché est pris dans le premier paramètre `{recherche xx}`
 * ou à défaut dans la clé `recherche` de l'environnement du squelette.
 *
 * @critere
 * @link https://www.spip.net/3878
 * @see inc_prepare_recherche_dist()
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 **/
function critere_recherche_dist($idb, &$boucles, $crit) {

	$boucle = &$boucles[$idb];

	if (!$boucle->primary or strpos($boucle->primary, ',')) {
		erreur_squelette(_T('zbug_critere_sur_table_sans_cle_primaire', ['critere' => 'recherche']), $boucle);

		return;
	}

	if (isset($crit->param[0])) {
		$quoi = calculer_liste($crit->param[0], $idb, $boucles, $boucles[$idb]->id_parent);
	} else {
		$quoi = '(isset($Pile[0]["recherche"])?$Pile[0]["recherche"]:(isset($GLOBALS["recherche"])?$GLOBALS["recherche"]:""))';
	}

	$_modificateur = var_export($boucle->modificateur, true);
	$boucle->hash .= '
	// RECHERCHE'
		. ($crit->cond ? '
	if (!strlen(' . $quoi . ')){
		list($rech_select, $rech_where) = array("0 as points","");
	} else' : '') . '
	{
		$prepare_recherche = charger_fonction(\'prepare_recherche\', \'inc\');
		list($rech_select, $rech_where) = $prepare_recherche(' . $quoi . ', "' . $boucle->id_table . '", "' . $crit->cond . '","' . $boucle->sql_serveur . '",' . $_modificateur . ',"' . $boucle->primary . '");
	}
	';


	$t = $boucle->id_table . '.' . $boucle->primary;
	if (!in_array($t, $boucles[$idb]->select)) {
		$boucle->select[] = $t;
	} # pour postgres, neuneu ici
	// jointure uniquement sur le serveur principal
	// (on ne peut joindre une table d'un serveur distant avec la table des resultats du serveur principal)
	if (!$boucle->sql_serveur) {
		$boucle->join['resultats'] = ["'" . $boucle->id_table . "'", "'id'", "'" . $boucle->primary . "'"];
		$boucle->from['resultats'] = 'spip_resultats';
	}
	$boucle->select[] = '$rech_select';
	//$boucle->where[]= "\$rech_where?'resultats.id=".$boucle->id_table.".".$boucle->primary."':''";

	// et la recherche trouve
	$boucle->where[] = '$rech_where?$rech_where:\'\'';
}

/**
 * Compile le critère `traduction`
 *
 * Sélectionne toutes les traductions de l'élément courant (la boucle englobante)
 * en différentes langues (y compris l'élément englobant)
 *
 * Équivalent à `(id_trad>0 AND id_trad=id_trad(precedent)) OR id_xx=id_xx(precedent)`
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 **/
function critere_traduction_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$prim = $boucle->primary;
	$table = $boucle->id_table;
	$arg = kwote(calculer_argument_precedent($idb, 'id_trad', $boucles));
	$dprim = kwote(calculer_argument_precedent($idb, $prim, $boucles));
	$boucle->where[] =
		[
			"'OR'",
			[
				"'AND'",
				["'='", "'$table.id_trad'", 0],
				["'='", "'$table.$prim'", $dprim]
			],
			[
				"'AND'",
				["'>'", "'$table.id_trad'", 0],
				["'='", "'$table.id_trad'", $arg]
			]
		];
}


/**
 * Compile le critère {origine_traduction}
 *
 * Sélectionne les éléments qui servent de base à des versions traduites
 * (par exemple les articles "originaux" sur une boucle articles)
 *
 * Équivalent à (id_trad>0 AND id_xx=id_trad) OR (id_trad=0)
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 **/
function critere_origine_traduction_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$prim = $boucle->primary;
	$table = $boucle->id_table;

	$c =
		[
			"'OR'",
			["'='", "'$table." . "id_trad'", "'$table.$prim'"],
			["'='", "'$table.id_trad'", "'0'"]
		];
	$boucle->where[] = ($crit->not ? ["'NOT'", $c] : $c);
}


/**
 * Compile le critère {meme_parent}
 *
 * Sélectionne les éléments ayant le même parent que la boucle parente,
 * c'est à dire les frères et sœurs.
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void|array
 **/
function critere_meme_parent_dist($idb, &$boucles, $crit) {

	$boucle = &$boucles[$idb];
	$arg = kwote(calculer_argument_precedent($idb, 'id_parent', $boucles));
	$id_parent = $GLOBALS['exceptions_des_tables'][$boucle->id_table]['id_parent'] ?? 'id_parent';
	$mparent = $boucle->id_table . '.' . $id_parent;

	if ($boucle->type_requete == 'rubriques' or isset($GLOBALS['exceptions_des_tables'][$boucle->id_table]['id_parent'])) {
		$boucle->where[] = ["'='", "'$mparent'", $arg];
	} // le cas FORUMS est gere dans le plugin forum, dans la fonction critere_FORUMS_meme_parent_dist()
	else {
		return ['zbug_critere_inconnu', ['critere' => $crit->op . ' ' . $boucle->type_requete]];
	}
}


/**
 * Compile le critère `branche` qui sélectionne dans une boucle les
 * éléments appartenant à une branche d'une rubrique.
 *
 * Cherche l'identifiant de la rubrique en premier paramètre du critère `{branche XX}`
 * s'il est renseigné, sinon, sans paramètre (`{branche}` tout court) dans les
 * boucles parentes. On calcule avec lui la liste des identifiants
 * de rubrique de toute la branche.
 *
 * La boucle qui possède ce critère cherche une liaison possible avec
 * la colonne `id_rubrique`, et tentera de trouver une jointure avec une autre
 * table si c'est nécessaire pour l'obtenir.
 *
 * Ce critère peut être rendu optionnel avec `{branche ?}` en remarquant
 * cependant que le test s'effectue sur la présence d'un champ 'id_rubrique'
 * sinon d'une valeur 'id_rubrique' dans l'environnement (et non 'branche'
 * donc).
 *
 * @link https://www.spip.net/@branche
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 **/
function critere_branche_dist($idb, &$boucles, $crit) {

	$not = $crit->not;
	$boucle = &$boucles[$idb];
	// prendre en priorite un identifiant en parametre {branche XX}
	if (isset($crit->param[0])) {
		$arg = calculer_liste($crit->param[0], $idb, $boucles, $boucles[$idb]->id_parent);
		// sinon on le prend chez une boucle parente
	} else {
		$arg = kwote(calculer_argument_precedent($idb, 'id_rubrique', $boucles), $boucle->sql_serveur, 'int NOT NULL');
	}

	//Trouver une jointure
	$champ = 'id_rubrique';
	$desc = $boucle->show;
	//Seulement si necessaire
	if (!array_key_exists($champ, $desc['field'])) {
		$cle = trouver_jointure_champ($champ, $boucle);
		$trouver_table = charger_fonction('trouver_table', 'base');
		$desc = $trouver_table($boucle->from[$cle]);
		if (count(trouver_champs_decomposes($champ, $desc)) > 1) {
			$decompose = decompose_champ_id_objet($champ);
			$champ = array_shift($decompose);
			$boucle->where[] = ["'='", _q($cle . '.' . reset($decompose)), '"' . sql_quote(end($decompose)) . '"'];
		}
	} else {
		$cle = $boucle->id_table;
	}

	$c = "sql_in('$cle" . ".$champ', calcul_branche_in($arg)"
		. ($not ? ", 'NOT'" : '') . ')';
	$boucle->where[] = !$crit->cond ? $c :
		("($arg ? $c : " . ($not ? "'0=1'" : "'1=1'") . ')');
}

/**
 * Compile le critère `logo` qui liste les objets qui ont un logo
 *
 * @uses lister_objets_avec_logos()
 *     Pour obtenir les éléments qui ont un logo
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 **/
function critere_logo_dist($idb, &$boucles, $crit) {

	$boucle = &$boucles[$idb];
	$not = ($crit->not ? 'NOT' : '');
	$serveur = $boucle->sql_serveur;

	$c = "sql_in('" .
		$boucle->id_table . '.' . $boucle->primary
		. "', lister_objets_avec_logos('" . $boucle->primary . "'), '$not', '$serveur')";

	$boucle->where[] = $c;
}


/**
 * Compile le critère `fusion` qui regroupe les éléments selon une colonne.
 *
 * C'est la commande SQL «GROUP BY»
 *
 * @critere
 * @link https://www.spip.net/5166
 * @example
 *     ```
 *      <BOUCLE_a(articles){fusion lang}>
 *     ```
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void|array
 **/
function critere_fusion_dist($idb, &$boucles, $crit) {
	if ($t = isset($crit->param[0])) {
		$t = $crit->param[0];
		if ($t[0]->type == 'texte') {
			$t = $t[0]->texte;
			if (preg_match('/^(.*)\.(.*)$/', $t, $r)) {
				$t = table_objet_sql($r[1]);
				$t = array_search($t, $boucles[$idb]->from);
				if ($t) {
					$t .= '.' . $r[2];
				}
			}
		} else {
			$t = '".'
				. calculer_critere_arg_dynamique($idb, $boucles, $t)
				. '."';
		}
	}
	if ($t) {
		$boucles[$idb]->group[] = $t;
		if (!in_array($t, $boucles[$idb]->select)) {
			$boucles[$idb]->select[] = $t;
		}
	} else {
		return ['zbug_critere_inconnu', ['critere' => $crit->op . ' ?']];
	}
}

/**
 * Compile le critère `fusion_supprimer` qui supprime toutes les fusions qui le précèdent
 *
 * Ce critère retire toutes les définitions de "group by" qui le précèdent. Par exemple pour en ajouter d'autres ensuite.
 *
 * @critere
 * @example
 *     ```
 *      <BOUCLE_a(ARTICLES){gis}{fusion_supprimer}{fusion ville}
 *     ```
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 **/
function critere_fusion_supprimer_dist($idb, &$boucles, $crit) {
	$boucles[$idb]->group = [];
}

/**
 * Compile le critère `{collecte}` qui permet de spécifier l'interclassement
 * à utiliser pour les tris de la boucle.
 *
 * Cela permet avec le critère `{par}` de trier un texte selon
 * l'interclassement indiqué. L'instruction s'appliquera sur les critères `{par}`
 * qui succèdent ce critère, ainsi qu'au critère `{par}` précédent
 * si aucun interclassement ne lui est déjà appliqué.
 *
 * Techniquement, c'est la commande SQL "COLLATE" qui utilisée.
 * (elle peut être appliquée sur les order by, group by, where, like ...)
 *
 * @example
 *     - `{par titre}{collecte utf8_spanish_ci}` ou `{collecte utf8_spanish_ci}{par titre}`
 *     - `{par titre}{par surtitre}{collecte utf8_spanish_ci}` :
 *        Seul 'surtitre' (`par` précédent) utilisera l'interclassement
 *     - `{collecte utf8_spanish_ci}{par titre}{par surtitre}` :
 *        'titre' et 'surtitre' utiliseront l'interclassement (tous les `par` suivants)
 *
 * @note
 *     Piège sur une éventuelle écriture peu probable :
 *     `{par a}{collecte c1}{par b}{collecte c2}` : le tri `{par b}`
 *     utiliserait l'interclassement c1 (et non c2 qui ne s'applique pas
 *     au `par` précédent s'il a déjà un interclassement demandé).
 *
 * @critere
 * @link https://www.spip.net/4028
 * @see critere_par_dist() Le critère `{par}`
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_collecte_dist($idb, &$boucles, $crit) {
	if (isset($crit->param[0])) {
		$_coll = calculer_liste($crit->param[0], $idb, $boucles, $boucles[$idb]->id_parent);
		$boucle = $boucles[$idb];
		$boucle->modificateur['collate'] = "($_coll ?' COLLATE '.$_coll:'')";
		$n = is_countable($boucle->order) ? count($boucle->order) : 0;
		if ($n && (strpos($boucle->order[$n - 1], 'COLLATE') === false)) {
			// l'instruction COLLATE doit être placée avant ASC ou DESC
			// notamment lors de l'utilisation `{!par xxx}{collate yyy}`
			if (
				(false !== $i = strpos($boucle->order[$n - 1], 'ASC'))
				or (false !== $i = strpos($boucle->order[$n - 1], 'DESC'))
			) {
				$boucle->order[$n - 1] = substr_replace($boucle->order[$n - 1], "' . " . $boucle->modificateur['collate'] . " . ' ", $i, 0);
			} else {
				$boucle->order[$n - 1] .= ' . ' . $boucle->modificateur['collate'];
			}
		}
	} else {
		return (['zbug_critere_inconnu', ['critere' => $crit->op . ' ' . (is_countable($boucles[$idb]->order) ? count($boucles[$idb]->order) : 0)]]);
	}
}

function calculer_critere_arg_dynamique($idb, &$boucles, $crit, $suffix = '') {
	$boucle = $boucles[$idb];
	$alt = "('" . $boucle->id_table . '.\' . $x' . $suffix . ')';
	$var = '$champs_' . $idb;
	$desc = (strpos($boucle->in, (string) "static $var =") !== false);
	if (!$desc) {
		$desc = $boucle->show['field'];
		$desc = implode(',', array_map('_q', array_keys($desc)));
		$boucles[$idb]->in .= "\n\tstatic $var = array(" . $desc . ');';
	}
	if ($desc) {
		$alt = "(in_array(\$x, $var)  ? $alt :(\$x$suffix))";
	}
	$arg = calculer_liste($crit, $idb, $boucles, $boucle->id_parent);

	return "((\$x = preg_replace(\"/\\W/\",'', $arg)) ? $alt : '')";
}

/**
 * Compile le critère `{par}` qui permet d'ordonner les résultats d'une boucle
 *
 * Demande à trier la boucle selon certains champs (en SQL, la commande `ORDER BY`).
 * Si plusieurs tris sont demandés (plusieurs fois le critère `{par x}{par y}` dans une boucle ou plusieurs champs
 * séparés par des virgules dans le critère `{par x, y, z}`), ils seront appliqués dans l'ordre.
 *
 * Quelques particularités :
 * - `{par hasard}` : trie par hasard
 * - `{par num titre}` : trie par numéro de titre
 * - `{par multi titre}` : trie par la langue extraite d'une balise polyglotte `<multi>` sur le champ titre
 * - `{!par date}` : trie par date inverse en utilisant le champ date principal déclaré pour la table (si c'est un objet éditorial).
 * - `{!par points}` : trie par pertinence de résultat de recherche (avec le critère `{recherche}`)
 * - `{par FUNCTION_SQL(n)}` : trie en utilisant une fonction SQL (peut dépendre du moteur SQL utilisé).
 *     Exemple : `{par SUBSTRING_INDEX(titre, ".", -1)}` (tri ~ alphabétique en ignorant les numéros de titres
 *     (exemple erroné car faux dès qu'un titre possède un point.)).
 * - `{par table.champ}` : trie en effectuant une jointure sur la table indiquée.
 * - `{par #BALISE}` : trie sur la valeur retournée par la balise (doit être un champ de la table, ou 'hasard').
 *
 * @example
 *     - `{par titre}`
 *     - `{!par date}`
 *     - `{par num titre, multi titre, hasard}`
 *
 * @critere
 * @link https://www.spip.net/5531
 * @see critere_tri_dist() Le critère `{tri ...}`
 * @see critere_inverse_dist() Le critère `{inverse}`
 *
 * @uses critere_parinverse()
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_par_dist($idb, &$boucles, $crit) {
	return critere_parinverse($idb, $boucles, $crit);
}

/**
 * Calculs pour le critère `{par}` ou `{inverse}` pour ordonner les résultats d'une boucle
 *
 * Les expressions intermédiaires `{par expr champ}` sont calculées dans des fonctions
 * `calculer_critere_par_expression_{expr}()` notamment `{par num champ}` ou `{par multi champ}`.
 *
 * @see critere_par_dist() Le critère `{par}` pour des exemples
 *
 * @uses calculer_critere_arg_dynamique() pour le calcul de `{par #ENV{tri}}`
 * @uses calculer_critere_par_hasard() pour le calcul de `{par hasard}`
 * @uses calculer_critere_par_champ()
 * @see calculer_critere_par_expression_num() pour le calcul de `{par num x}`
 * @see calculer_critere_par_expression_multi() pour le calcul de `{par multi x}`
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_parinverse($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];

	$sens = $collecte = '';
	if ($crit->not) {
		$sens = " . ' DESC'";
	}
	if (isset($boucle->modificateur['collate'])) {
		$collecte = ' . ' . $boucle->modificateur['collate'];
	}

	// Pour chaque paramètre du critère
	foreach ($crit->param as $tri) {
		$order = $fct = '';
		// tris specifiés dynamiquement {par #ENV{tri}}
		if ($tri[0]->type != 'texte') {
			// calculer le order dynamique qui verifie les champs
			$order = calculer_critere_arg_dynamique($idb, $boucles, $tri, $sens);
			// ajouter 'hasard' comme possibilité de tri dynamique
			calculer_critere_par_hasard($idb, $boucles, $crit);
		}
		// tris textuels {par titre}
		else {
			$par = array_shift($tri);
			$par = $par->texte;

			// tris de la forme {par expression champ} tel que {par num titre} ou {par multi titre}
			if (preg_match(',^(\w+)[\s]+(.*)$,', $par, $m)) {
				$expression = trim($m[1]);
				$champ = trim($m[2]);
				if (function_exists($f = 'calculer_critere_par_expression_' . $expression)) {
					$order = $f($idb, $boucles, $crit, $tri, $champ);
				} else {
					return ['zbug_critere_inconnu', ['critere' => $crit->op . " $par"]];
				}

			// tris de la forme {par champ} ou {par FONCTION(champ)}
			} elseif ($boucle->type_requete == 'DATA' or preg_match(',^' . CHAMP_SQL_PLUS_FONC . '$,is', $par, $match)) {
				// {par FONCTION(champ)}
				if (isset($match) and count($match) > 2) {
					$par = substr($match[2], 1, -1);
					$fct = $match[1];
				}
				// quelques cas spécifiques {par hasard}, {par date}
				if ($par == 'hasard') {
					$order = calculer_critere_par_hasard($idb, $boucles, $crit);
				} elseif ($par == 'date' and !empty($boucle->show['date'])) {
					$order = "'" . $boucle->id_table . '.' . $boucle->show['date'] . "'";
				} else {
					// cas général {par champ}, {par table.champ}, ...
					$order = calculer_critere_par_champ($idb, $boucles, $crit, $par);
				}
			}

			// on ne sait pas traiter…
			else {
				return ['zbug_critere_inconnu', ['critere' => $crit->op . " $par"]];
			}

			// En cas d'erreur de squelette retournée par une fonction
			if (is_array($order)) {
				return $order;
			}
		}

		if (preg_match('/^\'([^"]*)\'$/', $order, $m)) {
			$t = $m[1];
			if (strpos($t, '.') and !in_array($t, $boucle->select)) {
				$boucle->select[] = $t;
			}
		} else {
			$sens = '';
		}

		if ($fct) {
			if (preg_match("/^\s*'(.*)'\s*$/", $order, $r)) {
				$order = "'$fct(" . $r[1] . ")'";
			} else {
				$order = "'$fct(' . $order . ')'";
			}
		}
		$t = $order . $collecte . $sens;
		if (preg_match("/^(.*)'\s*\.\s*'([^']*')$/", $t, $r)) {
			$t = $r[1] . $r[2];
		}

		$boucle->order[] = $t;
	}
}

/**
 * Calculs pour le critère `{par hasard}`
 *
 * Ajoute le générateur d'aléatoire au SELECT de la boucle.
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return string Clause pour le Order by
 */
function calculer_critere_par_hasard($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	// Si ce n'est fait, ajouter un champ 'hasard' dans le select
	$parha = 'rand() AS hasard';
	if (!in_array($parha, $boucle->select)) {
		$boucle->select[] = $parha;
	}
	return "'hasard'";
}

/**
 * Calculs pour le critère `{par num champ}` qui extrait le numéro préfixant un texte
 *
 * Tri par numéro de texte (tel que "10. titre"). Le numéro calculé est ajouté au SELECT
 * de la boucle. L'écriture `{par num #ENV{tri}}` est aussi prise en compte.
 *
 * @note
 *     Les textes sans numéro valent 0 et sont donc placés avant les titres ayant des numéros.
 *     Utiliser `{par sinum champ, num champ}` pour avoir le comportement inverse.
 *
 * @see calculer_critere_par_expression_sinum() pour le critère `{par sinum champ}`
 * @uses calculer_critere_par_champ()
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @param array $tri Paramètre en cours du critère
 * @param string $champ Texte suivant l'expression ('titre' dans {par num titre})
 * @return string|array Clause pour le Order by (array si erreur)
 */
function calculer_critere_par_expression_num($idb, &$boucles, $crit, $tri, $champ) {
	$_champ = calculer_critere_par_champ($idb, $boucles, $crit, $champ, true);
	if (is_array($_champ)) {
		return ['zbug_critere_inconnu', ['critere' => $crit->op . " num $champ"]];
	}
	$boucle = &$boucles[$idb];
	$texte = '0+' . $_champ;
	$suite = calculer_liste($tri, $idb, $boucles, $boucle->id_parent);
	if ($suite !== "''") {
		$texte = "\" . ((\$x = $suite) ? ('$texte' . \$x) : '0')" . ' . "';
	}
	$asnum = 'num' . ($boucle->order ? count($boucle->order) : '');
	$boucle->select[] = $texte . " AS $asnum";

	$orderassinum = calculer_critere_par_expression_sinum($idb, $boucles, $crit, $tri, $champ);
	$orderassinum = trim($orderassinum, "'");

	$order = "'$orderassinum, $asnum'";
	return $order;
}

/**
 * Calculs pour le critère `{par sinum champ}` qui ordonne les champs avec numéros en premier
 *
 * Ajoute au SELECT la valeur 'sinum' qui vaut 0 si le champ a un numéro, 1 s'il n'en a pas.
 * Ainsi `{par sinum titre, num titre, titre}` mettra les éléments sans numéro en fin de liste,
 * contrairement à `{par num titre, titre}` seulement.
 *
 * @see calculer_critere_par_expression_num() pour le critère `{par num champ}`
 * @uses calculer_critere_par_champ()
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @param array $tri Paramètre en cours du critère
 * @param string $champ Texte suivant l'expression ('titre' dans {par sinum titre})
 * @return string|array Clause pour le Order by (array si erreur)
 */
function calculer_critere_par_expression_sinum($idb, &$boucles, $crit, $tri, $champ) {
	$_champ = calculer_critere_par_champ($idb, $boucles, $crit, $champ, true);
	if (is_array($_champ)) {
		return ['zbug_critere_inconnu', ['critere' => $crit->op . " sinum $champ"]];
	}
	$boucle = &$boucles[$idb];
	$texte = '0+' . $_champ;
	$suite = calculer_liste($tri, $idb, $boucles, $boucle->id_parent);
	if ($suite !== "''") {
		$texte = "\" . ((\$x = $suite) ? ('$texte' . \$x) : '0')" . ' . "';
	}

	$as = false;
	$select = "CASE ( $texte ) WHEN 0 THEN 1 ELSE 0 END AS ";
	foreach ($boucle->select as $s) {
		if (strpos($s, $select) === 0) {
			$as = trim(substr($s, strlen($select)));
			if (!preg_match(',\W,', $as)) {
				break;
			}
			$as = false;
		}
	}

	if (!$as) {
		$as = 'sinum' . ($boucle->order ? count($boucle->order) : '');
		$boucle->select[] = $select . $as;
	}
	$order = "'$as'";
	return $order;
}


/**
 * Calculs pour le critère `{par multi champ}` qui extrait la langue en cours dans les textes
 * ayant des balises `<multi>` (polyglottes)
 *
 * Ajoute le calcul du texte multi extrait dans le SELECT de la boucle.
 * Il ne peut y avoir qu'un seul critère de tri `multi` par boucle.
 *
 * @uses calculer_critere_par_champ()
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @param array $tri Paramètre en cours du critère
 * @param string $champ Texte suivant l'expression ('titre' dans {par multi titre})
 * @return string|array Clause pour le Order by (array si erreur)
 */
function calculer_critere_par_expression_multi($idb, &$boucles, $crit, $tri, $champ) {
	$_champ = calculer_critere_par_champ($idb, $boucles, $crit, $champ, true);
	if (is_array($_champ)) {
		return ['zbug_critere_inconnu', ['critere' => $crit->op . " multi $champ"]];
	}
	$boucle = &$boucles[$idb];
	$boucle->select[] = "\".sql_multi('" . $_champ . "', \$GLOBALS['spip_lang']).\"";
	$order = "'multi'";
	return $order;
}

/**
 * Retourne le champ de tri demandé en ajoutant éventuellement les jointures nécessaires à la boucle.
 *
 * - si le champ existe dans la table, on l'utilise
 * - si c'est une exception de jointure, on l'utilise (et crée la jointure au besoin)
 * - si c'est un champ dont la jointure est déjà présente on la réutilise
 * - si c'est un champ dont la jointure n'est pas présente, on la crée.
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @param string $par Nom du tri à analyser ('champ' ou 'table.champ')
 * @param bool $raw Retourne le champ pour le compilateur ("'alias.champ'") ou brut ('alias.champ')
 * @return array|string
 */
function calculer_critere_par_champ($idb, &$boucles, $crit, $par, $raw = false) {
	$boucle = &$boucles[$idb];
	$desc = $boucle->show;

	// le champ existe dans la table, pas de souci (le plus commun)
	if (isset($desc['field'][$par])) {
		$par = $boucle->id_table . '.' . $par;
	}
	// le champ est peut être une jointure
	else {
		$table = $table_alias = false; // toutes les tables de jointure possibles
		$champ = $par;

		// le champ demandé est une exception de jointure {par titre_mot}
		if (isset($GLOBALS['exceptions_des_jointures'][$par])) {
			[$table, $champ] = $GLOBALS['exceptions_des_jointures'][$par];
		} // la table de jointure est explicitement indiquée {par truc.muche}
		elseif (preg_match('/^([^,]*)\.(.*)$/', $par, $r)) {
			[, $table, $champ] = $r;
			$table_alias = $table; // c'est peut-être un alias de table {par L1.titre}
			$table = table_objet_sql($table);
		}

		// Si on connait la table d'arrivée, on la demande donc explicitement
		// Sinon on cherche le champ dans les tables possibles de jointures
		// Si la table est déjà dans le from, on la réutilise.
		if ($infos = chercher_champ_dans_tables($champ, $boucle->from, $boucle->sql_serveur, $table)) {
			$par = $infos['alias'] . '.' . $champ;
		} elseif (
			$boucle->jointures_explicites
			and $alias = trouver_jointure_champ($champ, $boucle, explode(' ', $boucle->jointures_explicites), false, $table)
		) {
			$par = $alias . '.' . $champ;
		} elseif ($alias = trouver_jointure_champ($champ, $boucle, $boucle->jointures, false, $table)) {
			$par = $alias . '.' . $champ;
		// en spécifiant directement l'alias {par L2.titre} (situation hasardeuse tout de même)
		} elseif (
			$table_alias
			and isset($boucle->from[$table_alias])
			and $infos = chercher_champ_dans_tables($champ, $boucle->from, $boucle->sql_serveur, $boucle->from[$table_alias])
		) {
			$par = $infos['alias'] . '.' . $champ;
		} elseif ($table) {
			// On avait table + champ, mais on ne les a pas trouvés
			return ['zbug_critere_inconnu', ['critere' => $crit->op . " $par"]];
		} else {
			// Sinon tant pis, ca doit etre un champ synthetise (cf points)
		}
	}

	return $raw ? $par : "'$par'";
}

/**
 * Retourne un champ de tri en créant une jointure
 * si la table n'est pas présente dans le from de la boucle.
 *
 * @deprecated 3.2
 * @param string $table Table du champ désiré
 * @param string $champ Champ désiré
 * @param Boucle $boucle Boucle en cours de compilation
 * @return string Champ pour le compilateur si trouvé, tel que "'alias.champ'", sinon vide.
 */
function critere_par_joint($table, $champ, &$boucle) {
	$t = array_search($table, $boucle->from);
	if (!$t) {
		$t = trouver_jointure_champ($champ, $boucle);
	}
	return !$t ? '' : ("'" . $t . '.' . $champ . "'");
}

/**
 * Compile le critère `{inverse}` qui inverse l'ordre utilisé par le précédent critère `{par}`
 *
 * Accèpte un paramètre pour déterminer le sens : `{inverse #X}` utilisera un tri croissant (ASC)
 * si la valeur retournée par `#X` est considérée vrai (`true`),
 * le sens contraire (DESC) sinon.
 *
 * @example
 *     - `{par date}{inverse}`, équivalent à `{!par date}`
 *     - `{par date}{inverse #ENV{sens}}` utilise la valeur d'environnement sens pour déterminer le sens.
 *
 * @critere
 * @see critere_par_dist() Le critère `{par}`
 * @link https://www.spip.net/5530
 * @uses critere_parinverse()
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_inverse_dist($idb, &$boucles, $crit) {

	$boucle = &$boucles[$idb];
	// Classement par ordre inverse
	if ($crit->not) {
		critere_parinverse($idb, $boucles, $crit);
	} else {
		$order = "' DESC'";
		// Classement par ordre inverse fonction eventuelle de #ENV{...}
		if (isset($crit->param[0])) {
			$critere = calculer_liste($crit->param[0], $idb, $boucles, $boucles[$idb]->id_parent);
			$order = "(($critere)?' DESC':'')";
		}

		$n = is_countable($boucle->order) ? count($boucle->order) : 0;
		if (!$n) {
			if (isset($boucle->default_order[0])) {
				$boucle->default_order[0] .= ' . " DESC"';
			} else {
				$boucle->default_order[] = ' DESC';
			}
		} else {
			$t = $boucle->order[$n - 1] . " . $order";
			if (preg_match("/^(.*)'\s*\.\s*'([^']*')$/", $t, $r)) {
				$t = $r[1] . $r[2];
			}
			$boucle->order[$n - 1] = $t;
		}
	}
}

/**
 * {par_ordre_liste champ,#LISTE{...}} pour trier selon une liste
 * en retournant en premier les éléments de la liste
 * @param $idb
 * @param $boucles
 * @param $crit
 * @return void|array
 */
function critere_par_ordre_liste_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];

	$sens = $collecte = '';
	if ($crit->not) {
		$sens = " . ' DESC'";
	}

	$crit2 = clone $crit;
	$crit2->not = false;
	$crit2->param = [reset($crit->param)];
	$res = critere_parinverse($idb, $boucles, $crit2);

	// erreur ?
	if (is_array($res)) {
		return $res;
	}

	$_order = array_pop($boucle->order);

	$_liste = calculer_liste($crit->param[1], [], $boucles, $boucles[$idb]->id_parent);

	$order = "'-FIELD(' . $_order . ',' . ((\$zl=formate_liste_critere_par_ordre_liste(array_reverse($_liste),'" . $boucle->sql_serveur . "')) ? \$zl : '0').')'$sens";
	$boucle->order[] = $order;
}


function critere_agenda_dist($idb, &$boucles, $crit) {
	$params = $crit->param;

	if ((is_countable($params) ? count($params) : 0) < 1) {
		return ['zbug_critere_inconnu', ['critere' => $crit->op . ' ?']];
	}

	$boucle = &$boucles[$idb];
	$parent = $boucle->id_parent;
	$fields = $boucle->show['field'];

	$date = array_shift($params);
	$type = array_shift($params);

	// la valeur $type doit etre connue a la compilation
	// donc etre forcement reduite a un litteral unique dans le source
	$type = is_object($type[0]) ? $type[0]->texte : null;

	// La valeur date doit designer un champ de la table SQL.
	// Si c'est un litteral unique dans le source, verifier a la compil,
	// sinon synthetiser le test de verif pour execution ulterieure
	// On prendra arbitrairement le premier champ si test negatif.
	if (((is_countable($date) ? count($date) : 0) == 1) and ($date[0]->type == 'texte')) {
		$date = $date[0]->texte;
		if (!isset($fields[$date])) {
			return ['zbug_critere_inconnu', ['critere' => $crit->op . ' ' . $date]];
		}
	} else {
		$a = calculer_liste($date, $idb, $boucles, $parent);
		$noms = array_keys($fields);
		$defaut = $noms[0];
		$noms = join(' ', $noms);
		# bien laisser 2 espaces avant $nom pour que strpos<>0
		$cond = "(\$a=strval($a))AND\nstrpos(\"  $noms \",\" \$a \")";
		$date = "'.(($cond)\n?\$a:\"$defaut\").'";
	}
	$annee = $params ? array_shift($params) : '';
	$annee = "\n" . 'sprintf("%04d", ($x = ' .
		calculer_liste($annee, $idb, $boucles, $parent) .
		') ? $x : date("Y"))';

	$mois = $params ? array_shift($params) : '';
	$mois = "\n" . 'sprintf("%02d", ($x = ' .
		calculer_liste($mois, $idb, $boucles, $parent) .
		') ? $x : date("m"))';

	$jour = $params ? array_shift($params) : '';
	$jour = "\n" . 'sprintf("%02d", ($x = ' .
		calculer_liste($jour, $idb, $boucles, $parent) .
		') ? $x : date("d"))';

	$annee2 = $params ? array_shift($params) : '';
	$annee2 = "\n" . 'sprintf("%04d", ($x = ' .
		calculer_liste($annee2, $idb, $boucles, $parent) .
		') ? $x : date("Y"))';

	$mois2 = $params ? array_shift($params) : '';
	$mois2 = "\n" . 'sprintf("%02d", ($x = ' .
		calculer_liste($mois2, $idb, $boucles, $parent) .
		') ? $x : date("m"))';

	$jour2 = $params ? array_shift($params) : '';
	$jour2 = "\n" . 'sprintf("%02d", ($x = ' .
		calculer_liste($jour2, $idb, $boucles, $parent) .
		') ? $x : date("d"))';

	$date = $boucle->id_table . ".$date";

	$quote_end = ",'" . $boucle->sql_serveur . "','text'";
	if ($type == 'jour') {
		$boucle->where[] = [
			"'='",
			"'DATE_FORMAT($date, \'%Y%m%d\')'",
			("sql_quote($annee . $mois . $jour$quote_end)")
		];
	} elseif ($type == 'mois') {
		$boucle->where[] = [
			"'='",
			"'DATE_FORMAT($date, \'%Y%m\')'",
			("sql_quote($annee . $mois$quote_end)")
		];
	} elseif ($type == 'semaine') {
		$boucle->where[] = [
			"'AND'",
			[
				"'>='",
				"'DATE_FORMAT($date, \'%Y%m%d\')'",
				("date_debut_semaine($annee, $mois, $jour)")
			],
			[
				"'<='",
				"'DATE_FORMAT($date, \'%Y%m%d\')'",
				("date_fin_semaine($annee, $mois, $jour)")
			]
		];
	} elseif ((is_countable($crit->param) ? count($crit->param) : 0) > 2) {
		$boucle->where[] = [
			"'AND'",
			[
				"'>='",
				"'DATE_FORMAT($date, \'%Y%m%d\')'",
				("sql_quote($annee . $mois . $jour$quote_end)")
			],
			["'<='", "'DATE_FORMAT($date, \'%Y%m%d\')'", ("sql_quote($annee2 . $mois2 . $jour2$quote_end)")]
		];
	}
	// sinon on prend tout
}


/**
 * Compile les critères {i,j} et {i/j}
 *
 * Le critère {i,j} limite l'affiche de la boucle en commançant l'itération
 * au i-ème élément, et pour j nombre d'éléments.
 * Le critère {n-i,j} limite en commençant au n moins i-ème élément de boucle
 * Le critère {i,n-j} limite en terminant au n moins j-ème élément de boucle.
 *
 * Le critère {i/j} affiche une part d'éléments de la boucle.
 * Commence à i*n/j élément et boucle n/j éléments. {2/4} affiche le second
 * quart des éléments d'une boucle.
 *
 * Traduit si possible (absence de n dans {i,j}) la demande en une
 * expression LIMIT du gestionnaire SQL
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 **/
function calculer_critere_parties($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$a1 = $crit->param[0];
	$a2 = $crit->param[1];
	$op = $crit->op;

	[$a11, $a12] = calculer_critere_parties_aux($idb, $boucles, $a1);
	[$a21, $a22] = calculer_critere_parties_aux($idb, $boucles, $a2);

	if (($op == ',') && (is_numeric($a11) && (is_numeric($a21)))) {
		$boucle->limit = $a11 . ',' . $a21;
	} else {
		// 3 dans {1/3}, {2,3} ou {1,n-3}
		$boucle->total_parties = ($a21 != 'n') ? $a21 : $a22;
		// 2 dans {2/3}, {2,5}, {n-2,1}
		$partie = ($a11 != 'n') ? $a11 : $a12;
		$mode = (($op == '/') ? '/' :
			(($a11 == 'n') ? '-' : '+') . (($a21 == 'n') ? '-' : '+'));
		// cas simple {0,#ENV{truc}} compilons le en LIMIT :
		if ($a11 !== 'n' and $a21 !== 'n' and $mode == '++' and $op == ',') {
			$boucle->limit =
				(is_numeric($a11) ? "'$a11'" : $a11)
				. ".','."
				. (is_numeric($a21) ? "'$a21'" : $a21);
		} else {
			calculer_parties($boucles, $idb, $partie, $mode);
		}
	}
}

/**
 * Compile certains critères {i,j} et {i/j}
 *
 * Calcule une expression déterminant $debut_boucle et $fin_boucle (le
 * début et la fin des éléments de la boucle qui doivent être affichés)
 * et les déclare dans la propriété «mode_partie» de la boucle, qui se
 * charge également de déplacer le pointeur de boucle sur le premier
 * élément à afficher.
 *
 * Place dans la propriété partie un test vérifiant que l'élément de
 * boucle en cours de lecture appartient bien à la plage autorisée.
 * Trop tôt, passe à l'élément suivant, trop tard, sort de l'itération de boucle.
 *
 * @param array $boucles AST du squelette
 * @param string $id_boucle Identifiant de la boucle
 * @param string $debut Valeur ou code pour trouver le début (i dans {i,j})
 * @param string $mode
 *     Mode (++, p+, +- ...) : 2 signes début & fin
 *     - le signe - indique
 *       -- qu'il faut soustraire debut du total {n-3,x}. 3 étant $debut
 *       -- qu'il faut raccourcir la fin {x,n-3} de 3 elements. 3 étant $total_parties
 *     - le signe p indique une pagination
 * @return void
 **/
function calculer_parties(&$boucles, $id_boucle, $debut, $mode) {
	$total_parties = $boucles[$id_boucle]->total_parties;

	preg_match(',([+-/p])([+-/])?,', $mode, $regs);
	[, $op1, $op2] = array_pad($regs, 3, null);
	$nombre_boucle = "\$Numrows['$id_boucle']['total']";
	// {1/3}
	if ($op1 == '/') {
		$pmoins1 = is_numeric($debut) ? ($debut - 1) : "($debut-1)";
		$totpos = is_numeric($total_parties) ? ($total_parties) :
			"($total_parties ? $total_parties : 1)";
		$fin = "ceil(($nombre_boucle * $debut )/$totpos) - 1";
		$debut = !$pmoins1 ? 0 : "ceil(($nombre_boucle * $pmoins1)/$totpos);";
	} else {
		// cas {n-1,x}
		if ($op1 == '-') {
			$debut = "$nombre_boucle - $debut;";
		}

		// cas {x,n-1}
		if ($op2 == '-') {
			$fin = '$debut_boucle + ' . $nombre_boucle . ' - '
				. (is_numeric($total_parties) ? ($total_parties + 1) :
					($total_parties . ' - 1'));
		} else {
			// {x,1} ou {pagination}
			$fin = '$debut_boucle'
				. (is_numeric($total_parties) ?
					(($total_parties == 1) ? '' : (' + ' . ($total_parties - 1))) :
					('+' . $total_parties . ' - 1'));
		}

		// {pagination}, gerer le debut_xx=-1 pour tout voir
		if ($op1 == 'p') {
			$debut .= ";\n	\$debut_boucle = ((\$tout=(\$debut_boucle == -1))?0:(\$debut_boucle))";
			$debut .= ";\n	\$debut_boucle = max(0,min(\$debut_boucle,floor(($nombre_boucle-1)/($total_parties))*($total_parties)))";
			$fin = "(\$tout ? $nombre_boucle : $fin)";
		}
	}

	// Notes :
	// $debut_boucle et $fin_boucle sont les indices SQL du premier
	// et du dernier demandes dans la boucle : 0 pour le premier,
	// n-1 pour le dernier ; donc total_boucle = 1 + debut - fin
	// Utiliser min pour rabattre $fin_boucle sur total_boucle.

	$boucles[$id_boucle]->mode_partie = "\n\t"
		. '$debut_boucle = ' . $debut . ";\n	"
		. "\$debut_boucle = intval(\$debut_boucle);\n	"
		. '$fin_boucle = min(' . $fin . ", \$Numrows['$id_boucle']['total'] - 1);\n	"
		. '$Numrows[\'' . $id_boucle . "']['grand_total'] = \$Numrows['$id_boucle']['total'];\n	"
		. '$Numrows[\'' . $id_boucle . '\']["total"] = max(0,$fin_boucle - $debut_boucle + 1);'
		. "\n\tif (\$debut_boucle>0"
		. " AND \$debut_boucle < \$Numrows['$id_boucle']['grand_total']"
		. " AND \$iter->seek(\$debut_boucle,'continue'))"
		. "\n\t\t\$Numrows['$id_boucle']['compteur_boucle'] = \$debut_boucle;\n\t";

	$boucles[$id_boucle]->partie = "
		if (\$Numrows['$id_boucle']['compteur_boucle'] <= \$debut_boucle) continue;
		if (\$Numrows['$id_boucle']['compteur_boucle']-1 > \$fin_boucle) break;";
}

/**
 * Analyse un des éléments des critères {a,b} ou {a/b}
 *
 * Pour l'élément demandé (a ou b) retrouve la valeur de l'élément,
 * et de combien il est soustrait si c'est le cas comme dans {a-3,b}
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param array $param Paramètre à analyser (soit a, soit b dans {a,b} ou {a/b})
 * @return array          Valeur de l'élément (peut être une expression PHP), Nombre soustrait
 **/
function calculer_critere_parties_aux($idb, &$boucles, $param) {
	if ($param[0]->type != 'texte') {
		$a1 = calculer_liste([$param[0]], $idb, $boucles, $boucles[$idb]->id_parent);
		if (isset($param[1]->texte)) {
			preg_match(',^\s*(-([0-9]+))?\s*$,', $param[1]->texte, $m);

			return ["intval($a1)", ((isset($m[2]) and $m[2]) ? $m[2] : 0)];
		} else {
			return ["intval($a1)", 0];
		}
	} else {
		preg_match(',^\s*(([0-9]+)|n)\s*(-\s*([0-9]+)?\s*)?$,', $param[0]->texte, $m);
		$a1 = $m[1];
		if (empty($m[3])) {
			return [$a1, 0];
		} elseif (!empty($m[4])) {
			return [$a1, $m[4]];
		} else {
			return [$a1, calculer_liste([$param[1]], $idb, $boucles, $boucles[$idb]->id_parent)];
		}
	}
}


/**
 * Compile les critères d'une boucle
 *
 * Cette fonction d'aiguillage cherche des fonctions spécifiques déclarées
 * pour chaque critère demandé, dans l'ordre ci-dessous :
 *
 * - critere_{serveur}_{table}_{critere}, sinon avec _dist
 * - critere_{serveur}_{critere}, sinon avec _dist
 * - critere_{table}_{critere}, sinon avec _dist
 * - critere_{critere}, sinon avec _dist
 * - calculer_critere_defaut, sinon avec _dist
 *
 * Émet une erreur de squelette si un critère retourne une erreur.
 *
 * @param string $idb
 *     Identifiant de la boucle
 * @param array $boucles
 *     AST du squelette
 * @return string|array
 *     string : Chaine vide sans erreur
 *     array : Erreur sur un des critères
 **/
function calculer_criteres($idb, &$boucles) {
	$msg = '';
	$boucle = $boucles[$idb];
	$table = strtoupper($boucle->type_requete);
	$serveur = strtolower($boucle->sql_serveur);

	$defaut = charger_fonction('DEFAUT', 'calculer_critere');
	// s'il y avait une erreur de syntaxe, propager cette info
	if (!is_array($boucle->criteres)) {
		return [];
	}

	foreach ($boucle->criteres as $crit) {
		$critere = $crit->op;
		// critere personnalise ?
		if (
			(!$serveur or
				((!function_exists($f = 'critere_' . $serveur . '_' . $table . '_' . $critere))
					and (!function_exists($f = $f . '_dist'))
					and (!function_exists($f = 'critere_' . $serveur . '_' . $critere))
					and (!function_exists($f = $f . '_dist'))
				)
			)
			and (!function_exists($f = 'critere_' . $table . '_' . $critere))
			and (!function_exists($f = $f . '_dist'))
			and (!function_exists($f = 'critere_' . $critere))
			and (!function_exists($f = $f . '_dist'))
		) {
			// fonction critere standard
			$f = $defaut;
		}
		// compile le critere
		$res = $f($idb, $boucles, $crit);

		// Gestion centralisee des erreurs pour pouvoir propager
		if (is_array($res)) {
			$msg = $res;
			erreur_squelette($msg, $boucle);
		}
	}

	return $msg;
}

/**
 * Désemberlificote les guillements et échappe (ou fera échapper) le contenu...
 *
 * Madeleine de Proust, revision MIT-1958 sqq, revision CERN-1989
 * hum, c'est kwoi cette fonxion ? on va dire qu'elle desemberlificote les guillemets...
 *
 * @param string $lisp Code compilé
 * @param string $serveur Connecteur de bdd utilisé
 * @param string $type Type d'échappement (char, int...)
 * @return string         Code compilé rééchappé
 */
function kwote($lisp, $serveur = '', $type = '') {
	if (preg_match(_CODE_QUOTE, $lisp, $r)) {
		return $r[1] . '"' . sql_quote(str_replace(["\\'", '\\\\'], ["'", '\\'], $r[2]), $serveur, $type) . '"';
	} else {
		return "sql_quote($lisp, '$serveur', '" . str_replace("'", "\\'", $type) . "')";
	}
}


/**
 * Compile un critère possédant l'opérateur IN : {xx IN yy}
 *
 * Permet de restreindre un champ sur une liste de valeurs tel que
 * {id_article IN 3,4} {id_article IN #LISTE{3,4}}
 *
 * Si on a une liste de valeurs dans #ENV{x}, utiliser la double etoile
 * pour faire par exemple {id_article IN #ENV**{liste_articles}}
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void|array
 **/
function critere_IN_dist($idb, &$boucles, $crit) {
	$r = calculer_critere_infixe($idb, $boucles, $crit);
	if (!$r) {
		return ['zbug_critere_inconnu', ['critere' => $crit->op . ' ?']];
	}
	[$arg, $op, $val, $col, $where_complement] = $r;

	$in = critere_IN_cas($idb, $boucles, $crit->not ? 'NOT' : ($crit->exclus ? 'exclus' : ''), $arg, $op, $val, $col);

	//	inserer la condition; exemple: {id_mot ?IN (66, 62, 64)}
	$where = $in;
	if ($crit->cond) {
		$pred = calculer_argument_precedent($idb, $col, $boucles);
		$where = ["'?'", $pred, $where, "''"];
		if ($where_complement) { // condition annexe du type "AND (objet='article')"
		$where_complement = ["'?'", $pred, $where_complement, "''"];
		}
	}
	if ($crit->exclus) {
		if (!preg_match(',^L[0-9]+[.],', $arg)) {
			$where = ["'NOT'", $where];
		} else // un not sur un critere de jointure se traduit comme un NOT IN avec une sous requete
			// c'est une sous requete identique a la requete principale sous la forme (SELF,$select,$where) avec $select et $where qui surchargent
		{
			$where = [
				"'NOT'",
				[
					"'IN'",
					"'" . $boucles[$idb]->id_table . '.' . $boucles[$idb]->primary . "'",
					["'SELF'", "'" . $boucles[$idb]->id_table . '.' . $boucles[$idb]->primary . "'", $where]
				]
			];
		}
	}

	$boucles[$idb]->where[] = $where;
	if ($where_complement) { // condition annexe du type "AND (objet='article')"
	$boucles[$idb]->where[] = $where_complement;
	}
}

function critere_IN_cas($idb, &$boucles, $crit2, $arg, $op, $val, $col) {
	static $num = [];
	$descr = $boucles[$idb]->descr;
	$cpt = &$num[$descr['nom']][$descr['gram']][$idb];

	$var = '$in' . $cpt++;
	$x = "\n\t$var = array();";
	foreach ($val as $k => $v) {
		if (preg_match(",^(\n//.*\n)?'(.*)'$,", $v, $r)) {
			// optimiser le traitement des constantes
			if (is_numeric($r[2])) {
				$x .= "\n\t$var" . "[]= $r[2];";
			} else {
				$x .= "\n\t$var" . '[]= ' . sql_quote($r[2]) . ';';
			}
		} else {
			// Pour permettre de passer des tableaux de valeurs
			// on repere l'utilisation brute de #ENV**{X},
			// c'est-a-dire sa  traduction en ($PILE[0][X]).
			// et on deballe mais en rajoutant l'anti XSS
			$x .= "\n\tif (!(is_array(\$a = ($v))))\n\t\t$var" . "[]= \$a;\n\telse $var = array_merge($var, \$a);";
		}
	}

	$boucles[$idb]->in .= $x;

	// inserer le tri par defaut selon les ordres du IN ...
	// avec une ecriture de type FIELD qui degrade les performances (du meme ordre qu'un regexp)
	// et que l'on limite donc strictement aux cas necessaires :
	// si ce n'est pas un !IN, et si il n'y a pas d'autre order dans la boucle
	if (!$crit2) {
		$boucles[$idb]->default_order[] = "((!\$zqv=sql_quote($var) OR \$zqv===\"''\") ? 0 : ('FIELD($arg,' . \$zqv . ')'))";
	}

	return "sql_in('$arg', $var" . ($crit2 == 'NOT' ? ",'NOT'" : '') . ')';
}

/**
 * Compile le critère {where}
 *
 * Ajoute une contrainte sql WHERE, tout simplement pour faire le pont
 * entre php et squelettes, en utilisant la syntaxe attendue par
 * la propriété $where d'une Boucle.
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 */
function critere_where_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	if (isset($crit->param[0])) {
		$_where = calculer_liste($crit->param[0], $idb, $boucles, $boucle->id_parent);
	} else {
		$_where = 'spip_sanitize_from_request(@$Pile[0]["where"],"where","vide")';
	}

	if ($crit->cond) {
		$_where = "((\$zzw = $_where) ? \$zzw : '')";
	}

	if ($crit->not) {
		$_where = "array('NOT',$_where)";
	}

	$boucle->where[] = $_where;
}

/**
 * Compile le critère `{id_?}`
 *
 * Ajoute automatiquement à la boucle des contraintes (nommées sélections conditionnelles)
 * équivalentes à `{id_article ?}{id_rubrique ?}...`, adaptées à la table en cours d’utilisation.
 *
 * Les champs sélectionnés par défaut sont :
 * - chaque champ id_xx de la table en cours d’utilisation (par exemple id_secteur sur la boucle ARTICLES)
 * - un champ 'objet', si cette table en dispose
 * - chaque clé primaire des tables des objets éditoriaux, s’ils sont éditables et liables (par exemple id_mot).
 *
 * @example
 *     ```
 *      <BOUCLE_liste_articles(ARTICLES){id_?}{tout}> ...
 *      Est équivalent (selon les plugins actifs) à :
 *      <BOUCLE_liste_articles(ARTICLES){id_article?}{id_rubrique?}{id_secteur?}{id_trad?}{id_mot?}{id_document?} ... {tout}> ...
 *     ```
 *
 * @uses lister_champs_id_conditionnel()
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 */
function critere_id__dist($idb, &$boucles, $crit) {
	/** @var Boucle $boucle */
	$boucle = $boucles[$idb];

	$champs = lister_champs_id_conditionnel(
		$boucle->show['table'],
		$boucle->show,
		$boucle->sql_serveur
	);

	// ne pas tenir compte des critères identiques déjà présents.
	if (!empty($boucle->modificateur['criteres'])) {
		$champs = array_diff($champs, array_keys($boucle->modificateur['criteres']));
	}
	// nous aider en mode debug.
	$boucle->debug[] = 'id_ : ' . implode(', ', $champs);
	$boucle->modificateur['id_'] = $champs;

	// créer un critère {id_xxx?} de chaque champ retenu
	foreach ($champs as $champ) {
		$critere_id_table = new Critere();
		$critere_id_table->op = $champ;
		$critere_id_table->cond = '?';
		$critere_id_table->ligne = $crit->ligne;
		calculer_critere_DEFAUT_dist($idb, $boucles, $critere_id_table);
	}
}

/**
 * Liste les champs qui peuvent servir de selection conditionnelle à une table SQL
 *
 * Retourne, pour la table demandée :
 * - chaque champ id_xx de la table en cours d’utilisation (par exemple id_secteur sur la boucle ARTICLES)
 * - un champ 'objet' si la table le contient (pour les tables avec objet / id_objet par exemple)
 * - chaque clé primaire des tables des objets éditoriaux qui peuvent se lier facilement à cette table,
 * -- soit parce que sa clé primaire de la table demandée est un champ dans la table principale
 * -- soit parce qu’une table de liaison existe, d’un côté ou de l’autre
 *
 * @pipeline_appel exclure_id_conditionnel
 * @param string $table Nom de la table SQL
 * @param array|null $desc Description de la table SQL, si connu
 * @param string $serveur Connecteur sql a utiliser
 * @return array Liste de nom de champs (tel que id_article, id_mot, id_parent ...)
 */
function lister_champs_id_conditionnel($table, $desc = null, $serveur = '') {
	// calculer la description de la table
	if (!is_array($desc)) {
		$desc = description_table($table, $serveur);
	}
	if (!$desc) {
		return [];
	}

	// Les champs id_xx de la table demandée
	$champs = array_filter(
		array_keys($desc['field']),
		fn($champ) => strpos($champ, 'id_') === 0 or (in_array($champ, ['objet']))
	);

	// Si le champ id_rubrique appartient à la liste et si id_secteur n'est pas inclus on le rajoute.
	if (
		in_array('id_rubrique', $champs)
		and !in_array('id_secteur', $champs)
	) {
		$champs[] = 'id_secteur';
	}

	// On ne fera pas mieux pour les tables d’un autre serveur
	if ($serveur) {
		return $champs;
	}

	$primary = false;
	$associable = false;
	include_spip('action/editer_liens');

	if (isset($desc['type'])) {
		$primary = id_table_objet($desc['type']);
		$associable = objet_associable($desc['type']);
	}
	if (isset($desc['field']['id_objet']) and isset($desc['field']['objet'])) {
		$associable = true;
	}

	// liste de toutes les tables principales, sauf la notre
	$tables = lister_tables_objets_sql();
	unset($tables[$table]);

	foreach ($tables as $_table => $_desc) {
		if (
			$associable
			or ($primary and in_array($primary, array_keys($_desc['field'])))
			or objet_associable($_desc['type'])
		) {
			$champs[] = id_table_objet($_table);
		}
	}
	$champs = array_values(array_unique($champs));

	// Exclusions de certains id
	$exclusions = pipeline(
		'exclure_id_conditionnel',
		[
			'args' => [
				'table' => $table,
				'id_table_objet' => $primary,
				'associable' => $associable,
			],
			'data' => [],
		]
	);
	$champs = array_diff($champs, $exclusions);

	return $champs;
}

/**
 * Compile le critère `{tri}` permettant le tri dynamique d'un champ
 *
 * Le critère `{tri}` gère un champ de tri  qui peut être modifié dynamiquement par la balise `#TRI`.
 * Il s'utilise donc conjointement avec la balise `#TRI` dans la même boucle
 * pour génerér les liens qui permettent de changer le critère de tri et le sens du tri
 *
 * @syntaxe `{tri [champ_par_defaut][,sens_par_defaut][,nom_variable]}`
 *
 * - champ_par_defaut : un champ de la table sql
 * - sens_par_defaut : -1 ou inverse pour décroissant, 1 ou direct pour croissant
 *     peut être un tableau pour préciser des sens par défaut associés à chaque champ
 *     exemple : `array('titre' => 1, 'date' => -1)` pour trier par défaut
 *     les titre croissants et les dates décroissantes
 *     dans ce cas, quand un champ est utilisé pour le tri et n'est pas présent dans le tableau
 *     c'est la première valeur qui est utilisée
 * - nom_variable : nom de la variable utilisée (par defaut `tri_{nomboucle}`)
 *
 *     {tri titre}
 *     {tri titre,inverse}
 *     {tri titre,-1}
 *     {tri titre,-1,truc}
 *
 * Exemple d'utilisation :
 *
 *     <B_articles>
 *     <p>#TRI{titre,'Trier par titre'} | #TRI{date,'Trier par date'}</p>
 *     <ul>
 *     <BOUCLE_articles(ARTICLES){tri titre}>
 *      <li>#TITRE - [(#DATE|affdate_jourcourt)]</li>
 *     </BOUCLE_articles>
 *     </ul>
 *     </B_articles>
 *
 * @note
 *     Contraitement à `{par ...}`, `{tri}` ne peut prendre qu'un seul champ,
 *     mais il peut être complété avec `{par ...}` pour indiquer des criteres secondaires
 *
 *     Exemble :
 *     `{tri num titre}{par titre}` permet de faire un tri sur le rang (modifiable dynamiquement)
 *     avec un second critère sur le titre en cas d'égalité des rangs
 *
 * @link https://www.spip.net/5429
 * @see critere_par_dist() Le critère `{par ...}`
 * @see balise_TRI_dist() La balise `#TRI`
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void
 */
function critere_tri_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];

	// definition du champ par defaut
	$_champ_defaut = !isset($crit->param[0][0]) ? "''"
		: calculer_liste([$crit->param[0][0]], $idb, $boucles, $boucle->id_parent);
	$_liste_sens_defaut = !isset($crit->param[1][0]) ? '1'
		: calculer_liste([$crit->param[1][0]], $idb, $boucles, $boucle->id_parent);
	$_variable = !isset($crit->param[2][0]) ? "'$idb'"
		: calculer_liste([$crit->param[2][0]], $idb, $boucles, $boucle->id_parent);

	$_tri = "((\$t=(isset(\$Pile[0]['tri'.$_variable]))?\$Pile[0]['tri'.$_variable]:((strncmp($_variable,'session',7)==0 AND session_get('tri'.$_variable))?session_get('tri'.$_variable):$_champ_defaut))?tri_protege_champ(\$t):'')";

	$_sens_defaut = "(is_array(\$s=$_liste_sens_defaut)?(isset(\$s[\$st=$_tri])?\$s[\$st]:reset(\$s)):\$s)";
	$_sens = "((intval(\$t=(isset(\$Pile[0]['sens'.$_variable]))?\$Pile[0]['sens'.$_variable]:((strncmp($_variable,'session',7)==0 AND session_get('sens'.$_variable))?session_get('sens'.$_variable):$_sens_defaut))==-1 OR \$t=='inverse')?-1:1)";

	$boucle->modificateur['tri_champ'] = $_tri;
	$boucle->modificateur['tri_sens'] = $_sens;
	$boucle->modificateur['tri_liste_sens_defaut'] = $_liste_sens_defaut;
	$boucle->modificateur['tri_nom'] = $_variable;
	// faut il inserer un test sur l'existence de $tri parmi les champs de la table ?
	// evite des erreurs sql, mais peut empecher des tri sur jointure ...
	$boucle->hash .= "
	\$senstri = '';
	\$tri = $_tri;
	if (\$tri){
		\$senstri = $_sens;
		\$senstri = (\$senstri<0)?' DESC':'';
	};
	";
	$boucle->select[] = '".tri_champ_select($tri)."';
	$boucle->order[] = "tri_champ_order(\$tri,\$command['from'],\$senstri)";
}

# Criteres de comparaison

/**
 * Compile un critère non déclaré explicitement
 *
 * Compile les critères non déclarés, ainsi que les parties de boucles
 * avec les critères {0,1} ou {1/2}
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return void|array
 **/
function calculer_critere_DEFAUT_dist($idb, &$boucles, $crit) {
	// double cas particulier {0,1} et {1/2} repere a l'analyse lexicale
	if (($crit->op == ',') or ($crit->op == '/')) {
		return calculer_critere_parties($idb, $boucles, $crit);
	}

	$r = calculer_critere_infixe($idb, $boucles, $crit);
	if (!$r) {
		#	// on produit une erreur seulement si le critere n'a pas de '?'
		#	if (!$crit->cond) {
		return ['zbug_critere_inconnu', ['critere' => $crit->op]];
		#	}
	} else {
		calculer_critere_DEFAUT_args($idb, $boucles, $crit, $r);
	}
}


/**
 * Compile un critère non déclaré explicitement, dont on reçoit une analyse
 *
 * Ajoute en fonction des arguments trouvés par calculer_critere_infixe()
 * les conditions WHERE à appliquer sur la boucle.
 *
 * @see calculer_critere_infixe()
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @param array $args Description du critère
 *                        Cf. retour de calculer_critere_infixe()
 * @return void
 **/
function calculer_critere_DEFAUT_args($idb, &$boucles, $crit, $args) {
	[$arg, $op, $val, $col, $where_complement] = $args;

	$where = ["'$op'", "'$arg'", $val[0]];

	// inserer la negation (cf !...)

	if ($crit->not) {
		$where = ["'NOT'", $where];
	}
	if ($crit->exclus) {
		if (!preg_match(',^L[0-9]+[.],', $arg)) {
			$where = ["'NOT'", $where];
		} else {
			// un not sur un critere de jointure se traduit comme un NOT IN avec une sous requete
			// c'est une sous requete identique a la requete principale sous la forme (SELF,$select,$where) avec $select et $where qui surchargent
			$where = [
				"'NOT'",
				[
					"'IN'",
					"'" . $boucles[$idb]->id_table . '.' . $boucles[$idb]->primary . "'",
					["'SELF'", "'" . $boucles[$idb]->id_table . '.' . $boucles[$idb]->primary . "'", $where]
				]
			];
		}
	}

	// inserer la condition (cf {lang?})
	// traiter a part la date, elle est mise d'office par SPIP,
	if ($crit->cond) {
		$pred = calculer_argument_precedent($idb, $col, $boucles);
		if ($col === 'date' or $col === 'date_redac') {
			if ($pred === "\$Pile[0]['" . $col . "']") {
				$pred = "(\$Pile[0]['{$col}_default']?'':$pred)";
			}
		}

		if ($op === '=' and !$crit->not) {
			$where = [
				"'?'",
				"(is_array($pred))",
				critere_IN_cas($idb, $boucles, 'COND', $arg, $op, [$pred], $col),
				$where
			];
		}
		$where = ["'?'", "!is_whereable($pred)", "''", $where];
		if ($where_complement) {
			// condition annexe du type "AND (objet='article')"
			$where_complement = ["'?'", "!is_whereable($pred)", "''", $where_complement];
		}
	}

	$boucles[$idb]->where[] = $where;
	if ($where_complement) {
		// condition annexe du type "AND (objet='article')"
		$boucles[$idb]->where[] = $where_complement;
	}
}


/**
 * Décrit un critère non déclaré explicitement
 *
 * Décrit un critère non déclaré comme {id_article} {id_article>3} en
 * retournant un tableau de l'analyse si la colonne (ou l'alias) existe vraiment.
 *
 * Ajoute au passage pour chaque colonne utilisée (alias et colonne véritable)
 * un modificateur['criteres'][colonne].
 *
 * S'occupe de rechercher des exceptions, tel que
 * - les id_parent, id_enfant, id_secteur,
 * - des colonnes avec des exceptions déclarées,
 * - des critères de date (jour_relatif, ...),
 * - des critères sur tables jointes explicites (mots.titre),
 * - des critères sur tables de jointure non explicite (id_mot sur une boucle articles...)
 *
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return array|string
 *     Liste si on trouve le champ :
 *     - string $arg
 *         Opérande avant l'opérateur : souvent la colonne d'application du critère, parfois un calcul
 *         plus complexe dans le cas des dates.
 *     - string $op
 *         L'opérateur utilisé, tel que '='
 *     - string[] $val
 *         Liste de codes PHP obtenant les valeurs des comparaisons (ex: id_article sur la boucle parente)
 *         Souvent (toujours ?) un tableau d'un seul élément.
 *     - $col_alias
 *     - $where_complement
 *
 *     Chaîne vide si on ne trouve pas le champ...
 **/
function calculer_critere_infixe($idb, &$boucles, $crit) {

	$boucle = &$boucles[$idb];
	$type = $boucle->type_requete;
	$table = $boucle->id_table ?? '';
	$desc = $boucle->show;
	$col_vraie = null;

	[$fct, $col, $op, $val, $args_sql] =
		calculer_critere_infixe_ops($idb, $boucles, $crit);

	$col_alias = $col;
	$where_complement = false;

	// Cas particulier : id_enfant => utiliser la colonne id_objet
	if ($col == 'id_enfant') {
		$col = $boucle->primary;
	}

	// Cas particulier : id_parent => verifier les exceptions de tables
	if (
		(in_array($col, ['id_parent', 'id_secteur']) and isset($GLOBALS['exceptions_des_tables'][$table][$col]))
		or (isset($GLOBALS['exceptions_des_tables'][$table][$col]) and is_string($GLOBALS['exceptions_des_tables'][$table][$col]))
	) {
		$col = $GLOBALS['exceptions_des_tables'][$table][$col];
	} // et possibilite de gerer un critere secteur sur des tables de plugins (ie forums)
	else {
		if (($col == 'id_secteur') and ($critere_secteur = charger_fonction("critere_secteur_$type", 'public', true))) {
			$table = $critere_secteur($idb, $boucles, $val, $crit);
		}

		// cas id_article=xx qui se mappe en id_objet=xx AND objet=article
		// sauf si exception declaree : sauter cette etape
		else {
			if (
				!isset($GLOBALS['exceptions_des_jointures'][table_objet_sql($table)][$col])
				and !isset($GLOBALS['exceptions_des_jointures'][$col])
				and count(trouver_champs_decomposes($col, $desc)) > 1
			) {
				$e = decompose_champ_id_objet($col);
				$col = array_shift($e);
				$where_complement = primary_doublee($e, $table);
			} // Cas particulier : expressions de date
			else {
				if ($c = calculer_critere_infixe_date($idb, $boucles, $col)) {
					[$col, $col_vraie] = $c;
					$table = '';
				} // table explicitée {mots.titre}
				else {
					if (preg_match('/^(.*)\.(.*)$/', $col, $r)) {
						[, $table, $col] = $r;
						$col_alias = $col;

						$trouver_table = charger_fonction('trouver_table', 'base');
						if (
							$desc = $trouver_table($table, $boucle->sql_serveur)
							and isset($desc['field'][$col])
							and $cle = array_search($desc['table'], $boucle->from)
						) {
							$table = $cle;
						} else {
							$table = trouver_jointure_champ($col, $boucle, [$table], ($crit->cond or $op != '='));
						}
						#$table = calculer_critere_externe_init($boucle, array($table), $col, $desc, ($crit->cond OR $op!='='), true);
						if (!$table) {
							return '';
						}
					}
					// si le champ n'est pas trouvé dans la table,
					// on cherche si une jointure peut l'obtenir
					elseif (@!array_key_exists($col, $desc['field'])) {
						// Champ joker * des iterateurs DATA qui accepte tout
						if (@array_key_exists('*', $desc['field'])) {
							$desc['field'][$col_vraie ?: $col] = ''; // on veut pas de cast INT par defaut car le type peut etre n'importe quoi dans les boucles DATA
						}
						else {
							$r = calculer_critere_infixe_externe($boucle, $crit, $op, $desc, $col, $col_alias, $table);
							if (!$r) {
								return '';
							}
							[$col, $col_alias, $table, $where_complement, $desc] = $r;
						}
					}
				}
			}
		}
	}

	$col_vraie = ($col_vraie ?: $col);
	// Dans tous les cas,
	// virer les guillemets eventuels autour d'un int (qui sont refuses par certains SQL)
	// et passer dans sql_quote avec le type si connu
	// et int sinon si la valeur est numerique
	// sinon introduire le vrai type du champ si connu dans le sql_quote (ou int NOT NULL sinon)
	// Ne pas utiliser intval, PHP tronquant les Bigint de SQL
	if ($op == '=' or in_array($op, $GLOBALS['table_criteres_infixes'])) {
		$type_cast_quote = ($desc['field'][$col_vraie] ?? 'int NOT NULL');
		// defaire le quote des int et les passer dans sql_quote avec le bon type de champ si on le connait, int sinon
		// prendre en compte le debug ou la valeur arrive avec un commentaire PHP en debut
		if (preg_match(",^\\A(\s*//.*?$\s*)?\"'(-?\d+)'\"\\z,ms", $val[0], $r)) {
			$val[0] = $r[1] . '"' . sql_quote($r[2], $boucle->sql_serveur, $type_cast_quote) . '"';
		}
		// sinon expliciter les
		// sql_quote(truc) en sql_quote(truc,'',type)
		// sql_quote(truc,serveur) en sql_quote(truc,serveur,type)
		// sql_quote(truc,serveur,'') en sql_quote(truc,serveur,type)
		// sans toucher aux
		// sql_quote(truc,'','varchar(10) DEFAULT \'oui\' COLLATE NOCASE')
		// sql_quote(truc,'','varchar')
		elseif (
			preg_match('/\Asql_quote[(](.*?)(,[^)]*?)?(,[^)]*(?:\(\d+\)[^)]*)?)?[)]\s*\z/ms', $val[0], $r)
			// si pas deja un type
			and (!isset($r[3]) or !$r[3] or !trim($r[3], ", '"))
		) {
			$r = $r[1]
				. ((isset($r[2]) and $r[2]) ? $r[2] : ",''")
				. ",'" . addslashes($type_cast_quote) . "'";
			$val[0] = "sql_quote($r)";
		}
		elseif (
			strpos($val[0], '@@defaultcast@@') !== false
			and preg_match("/'@@defaultcast@@'\s*\)\s*\z/ms", $val[0], $r)
		) {
			$val[0] = substr($val[0], 0, -strlen($r[0])) . "'" . addslashes($type_cast_quote) . "')";
		}
	}

	if (
		strpos($val[0], '@@defaultcast@@') !== false
		and preg_match("/'@@defaultcast@@'\s*\)\s*\z/ms", $val[0], $r)
	) {
		$val[0] = substr($val[0], 0, -strlen($r[0])) . "'char')";
	}

	// Indicateur pour permettre aux fonctionx boucle_X de modifier
	// leurs requetes par defaut, notamment le champ statut
	// Ne pas confondre champs de la table principale et des jointures
	if ($table === $boucle->id_table) {
		$boucles[$idb]->modificateur['criteres'][$col_vraie] = true;
		if ($col_alias != $col_vraie) {
			$boucles[$idb]->modificateur['criteres'][$col_alias] = true;
		}
	}

	// inserer le nom de la table SQL devant le nom du champ
	if ($table) {
		if ($col[0] == '`') {
			$arg = "$table." . substr($col, 1, -1);
		} else {
			$arg = "$table.$col";
		}
	} else {
		$arg = $col;
	}

	// inserer la fonction SQL
	if ($fct) {
		$arg = "$fct($arg$args_sql)";
	}

	return [$arg, $op, $val, $col_alias, $where_complement];
}


/**
 * Décrit un critère non déclaré explicitement, sur un champ externe à la table
 *
 * Décrit un critère non déclaré comme {id_article} {id_article>3} qui correspond
 * à un champ non présent dans la table, et donc à retrouver par jointure si possible.
 *
 * @param Boucle $boucle Description de la boucle
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @param string $op L'opérateur utilisé, tel que '='
 * @param array $desc Description de la table
 * @param string $col Nom de la colonne à trouver (la véritable)
 * @param string $col_alias Alias de la colonne éventuel utilisé dans le critère ex: id_enfant
 * @param string $table Nom de la table SQL de la boucle
 * @return array|string
 *     Liste si jointure possible :
 *     - string $col
 *     - string $col_alias
 *     - string $table
 *     - array $where
 *     - array $desc
 *
 *     Chaîne vide si on ne trouve pas le champ par jointure...
 **/
function calculer_critere_infixe_externe($boucle, $crit, $op, $desc, $col, $col_alias, $table) {

	$where = '';

	$calculer_critere_externe = 'calculer_critere_externe_init';
	// gestion par les plugins des jointures tordues
	// pas automatiques mais necessaires
	$table_sql = table_objet_sql($table);
	if (
		isset($GLOBALS['exceptions_des_jointures'][$table_sql])
		and is_array($GLOBALS['exceptions_des_jointures'][$table_sql])
		and
		(
			isset($GLOBALS['exceptions_des_jointures'][$table_sql][$col])
			or
			isset($GLOBALS['exceptions_des_jointures'][$table_sql][''])
		)
	) {
		$t = $GLOBALS['exceptions_des_jointures'][$table_sql];
		$index = $t[$col] ?? $t[''] ?? [];

		if ((is_countable($index) ? count($index) : 0) == 3) {
			[$t, $col, $calculer_critere_externe] = $index;
		} elseif ((is_countable($index) ? count($index) : 0) == 2) {
			[$t, $col] = $t[$col];
		} elseif ((is_countable($index) ? count($index) : 0) == 1) {
			[$calculer_critere_externe] = $index;
			$t = $table;
		} else {
			$t = '';
		} // jointure non declaree. La trouver.
	} elseif (isset($GLOBALS['exceptions_des_jointures'][$col])) {
		[$t, $col] = $GLOBALS['exceptions_des_jointures'][$col];
	} else {
		$t = '';
	} // jointure non declaree. La trouver.

	// ici on construit le from pour fournir $col en piochant dans les jointures

	// si des jointures explicites sont fournies, on cherche d'abord dans celles ci
	// permet de forcer une table de lien quand il y a ambiguite
	// <BOUCLE_(DOCUMENTS documents_liens){id_mot}>
	// alors que <BOUCLE_(DOCUMENTS){id_mot}> produit la meme chose que <BOUCLE_(DOCUMENTS mots_liens){id_mot}>
	$table = '';
	if ($boucle->jointures_explicites) {
		$jointures_explicites = explode(' ', $boucle->jointures_explicites);
		$table = $calculer_critere_externe($boucle, $jointures_explicites, $col, $desc, ($crit->cond or $op != '='), $t);
	}

	// et sinon on cherche parmi toutes les jointures declarees
	if (!$table) {
		$table = $calculer_critere_externe($boucle, $boucle->jointures, $col, $desc, ($crit->cond or $op != '='), $t);
	}

	if (!$table) {
		return '';
	}

	// il ne reste plus qu'a trouver le champ dans les from
	[$nom, $desc, $cle] = trouver_champ_exterieur($col, $boucle->from, $boucle);

	if ((is_countable($cle) ? count($cle) : 0) > 1 or reset($cle) !== $col) {
		$col_alias = $col; // id_article devient juste le nom d'origine
		if ((is_countable($cle) ? count($cle) : 0) > 1 and reset($cle) == 'id_objet') {
			$e = decompose_champ_id_objet($col);
			$col = array_shift($e);
			$where = primary_doublee($e, $table);
		} else {
			$col = reset($cle);
		}
	}

	return [$col, $col_alias, $table, $where, $desc];
}


/**
 * Calcule une condition WHERE entre un nom du champ et une valeur
 *
 * Ne pas appliquer sql_quote lors de la compilation,
 * car on ne connait pas le serveur SQL
 *
 * @todo Ce nom de fonction n'est pas très clair ?
 *
 * @param array $decompose Liste nom du champ, code PHP pour obtenir la valeur
 * @param string $table Nom de la table
 * @return string[]
 *     Liste de 3 éléments pour une description where du compilateur :
 *     - operateur (=),
 *     - table.champ,
 *     - valeur
 **/
function primary_doublee($decompose, $table) {
	$e1 = reset($decompose);
	$e2 = "sql_quote('" . end($decompose) . "')";

	return ["'='", "'$table." . $e1 . "'", $e2];
}

/**
 * Champ hors table, ça ne peut être qu'une jointure.
 *
 * On cherche la table du champ et on regarde si elle est déjà jointe
 * Si oui et qu'on y cherche un champ nouveau, pas de jointure supplementaire
 * Exemple: criteres {titre_mot=...}{type_mot=...}
 * Dans les 2 autres cas ==> jointure
 * (Exemple: criteres {type_mot=...}{type_mot=...} donne 2 jointures
 * pour selectioner ce qui a exactement ces 2 mots-cles.
 *
 * @param Boucle $boucle
 *     Description de la boucle
 * @param array $joints
 *     Liste de jointures possibles (ex: $boucle->jointures ou $boucle->jointures_explicites)
 * @param string $col
 *     Colonne cible de la jointure
 * @param array $desc
 *     Description de la table
 * @param bool $cond
 *     Flag pour savoir si le critère est conditionnel ou non
 * @param bool|string $checkarrivee
 *     string : nom de la table jointe où on veut trouver le champ.
 *     n'a normalement pas d'appel sans $checkarrivee.
 * @return string
 *     Alias de la table de jointure (Lx)
 *     Vide sinon.
 */
function calculer_critere_externe_init(&$boucle, $joints, $col, $desc, $cond, $checkarrivee = false) {
	// si on demande un truc du genre spip_mots
	// avec aussi spip_mots_liens dans les jointures dispo
	// et qu'on est la
	// il faut privilegier la jointure directe en 2 etapes spip_mots_liens, spip_mots
	if (
		$checkarrivee
		and is_string($checkarrivee)
		and $a = table_objet($checkarrivee)
		and in_array($a . '_liens', $joints)
	) {
		if ($res = calculer_lien_externe_init($boucle, $joints, $col, $desc, $cond, $checkarrivee)) {
			return $res;
		}
	}
	foreach ($joints as $joint) {
		if ($arrivee = trouver_champ_exterieur($col, [$joint], $boucle, $checkarrivee)) {
			// alias de table dans le from
			$t = array_search($arrivee[0], $boucle->from);
			// recuperer la cle id_xx eventuellement decomposee en (id_objet,objet)
			$cols = $arrivee[2];
			// mais on ignore la 3eme cle si presente qui correspond alors au point de depart
			if ((is_countable($cols) ? count($cols) : 0) > 2) {
				array_pop($cols);
			}
			if ($t) {
				// la table est déjà dans le FROM, on vérifie si le champ est utilisé.
				$joindre = false;
				foreach ($cols as $col) {
					$c = '/\b' . $t . ".$col" . '\b/';
					if (trouver_champ($c, $boucle->where)) {
						$joindre = true;
					} else {
						// mais ca peut etre dans le FIELD pour le Having
						$c = "/FIELD.$t" . ".$col,/";
						if (trouver_champ($c, $boucle->select)) {
							$joindre = true;
						}
					}
				}
				if (!$joindre) {
					return $t;
				}
			}
			array_pop($arrivee);
			if ($res = calculer_jointure($boucle, [$boucle->id_table, $desc], $arrivee, $cols, $cond, 1)) {
				return $res;
			}
		}
	}

	return '';
}

/**
 * Générer directement une jointure via une table de lien spip_xxx_liens
 * pour un critère {id_xxx}
 *
 * @todo $checkarrivee doit être obligatoire ici ?
 *
 * @param Boucle $boucle
 *     Description de la boucle
 * @param array $joints
 *     Liste de jointures possibles (ex: $boucle->jointures ou $boucle->jointures_explicites)
 * @param string $col
 *     Colonne cible de la jointure
 * @param array $desc
 *     Description de la table
 * @param bool $cond
 *     Flag pour savoir si le critère est conditionnel ou non
 * @param bool|string $checkarrivee
 *     string : nom de la table jointe où on veut trouver le champ.
 *     n'a normalement pas d'appel sans $checkarrivee.
 * @return string
 *     Alias de la table de jointure (Lx)
 */
function calculer_lien_externe_init(&$boucle, $joints, $col, $desc, $cond, $checkarrivee = false) {
	$primary_arrivee = id_table_objet($checkarrivee);

	// [FIXME] $checkarrivee peut-il arriver avec false ????
	$intermediaire = trouver_champ_exterieur($primary_arrivee, $joints, $boucle, $checkarrivee . '_liens');
	$arrivee = trouver_champ_exterieur($col, $joints, $boucle, $checkarrivee);

	if (!$intermediaire or !$arrivee) {
		return '';
	}
	array_pop($intermediaire); // enlever la cle en 3eme argument
	array_pop($arrivee); // enlever la cle en 3eme argument

	$res = fabrique_jointures(
		$boucle,
		[
			[
				$boucle->id_table,
				$intermediaire,
				[id_table_objet($desc['table_objet']), 'id_objet', 'objet', $desc['type']]
			],
			[reset($intermediaire), $arrivee, $primary_arrivee]
		],
		$cond,
		$desc,
		$boucle->id_table,
		[$col]
	);

	return $res;
}


/**
 * Recherche la présence d'un champ dans une valeur de tableau
 *
 * @param string $champ
 *     Expression régulière pour trouver un champ donné.
 *     Exemple : /\barticles.titre\b/
 * @param array $where
 *     Tableau de valeurs dans lesquels chercher le champ.
 * @return bool
 *     true si le champ est trouvé quelque part dans $where
 *     false sinon.
 **/
function trouver_champ($champ, $where) {
	if (!is_array($where)) {
		return preg_match($champ, $where);
	} else {
		foreach ($where as $clause) {
			if (trouver_champ($champ, $clause)) {
				return true;
			}
		}

		return false;
	}
}


/**
 * Détermine l'operateur et les opérandes d'un critère non déclaré
 *
 * Lorsque l'opérateur n'est pas explicite comme sur {id_article>0} c'est
 * l'opérateur '=' qui est utilisé.
 *
 * Traite les cas particuliers id_parent, id_enfant, date, lang
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 * @return array
 *     Liste :
 *     - string $fct       Nom d'une fonction SQL sur le champ ou vide (ex: SUM)
 *     - string $col       Nom de la colonne SQL utilisée
 *     - string $op        Opérateur
 *     - string[] $val
 *         Liste de codes PHP obtenant les valeurs des comparaisons (ex: id_article sur la boucle parente)
 *         Souvent un tableau d'un seul élément.
 *     - string $args_sql  Suite des arguments du critère. ?
 **/
function calculer_critere_infixe_ops($idb, &$boucles, $crit) {
	// cas d'une valeur comparee a elle-meme ou son referent
	if (count($crit->param) == 0) {
		$op = '=';
		$col = $val = $crit->op;
		if (preg_match('/^(.*)\.(.*)$/', $col, $r)) {
			$val = $r[2];
		}
		// Cas special {lang} : aller chercher $GLOBALS['spip_lang']
		if ($val == 'lang') {
			$val = [kwote('$GLOBALS[\'spip_lang\']')];
		} else {
			$defaut = null;
			if ($val == 'id_parent') {
				// Si id_parent, comparer l'id_parent avec l'id_objet
				// de la boucle superieure.... faudrait verifier qu'il existe
				// pour eviter l'erreur SQL
				$val = $boucles[$idb]->primary;
				// mais si pas de boucle superieure, prendre id_parent dans l'env
				$defaut = "(\$Pile[0]['id_parent'] ?? null)";
			} elseif ($val == 'id_enfant') {
				// Si id_enfant, comparer l'id_objet avec l'id_parent
				// de la boucle superieure
				$val = 'id_parent';
			} elseif ($crit->cond and ($col == 'date' or $col == 'date_redac')) {
				// un critere conditionnel sur date est traite a part
				// car la date est mise d'office par SPIP,
				$defaut = "(\$Pile[0]['{$col}_default']?'':\$Pile[0]['" . $col . "'])";
			}

			$val = calculer_argument_precedent($idb, $val, $boucles, $defaut);
			$val = [kwote($val)];
		}
	} else {
		// comparaison explicite
		// le phraseur impose que le premier param soit du texte
		$params = $crit->param;
		$op = $crit->op;
		if ($op == '==') {
			$op = 'REGEXP';
		}
		$col = array_shift($params);
		$col = $col[0]->texte;

		$val = [];
		$parent = $boucles[$idb]->id_parent;

		// Dans le cas {x=='#DATE'} etc, defaire le travail du phraseur,
		// celui ne sachant pas ce qu'est un critere infixe
		// et a fortiori son 2e operande qu'entoure " ou '
		if (
			count($params) == 1
			and (is_countable($params[0]) ? count($params[0]) : 0) == 3
			and $params[0][0]->type == 'texte'
			and $params[0][2]->type == 'texte'
			and ($p = $params[0][0]->texte) == $params[0][2]->texte
			and (($p == "'") or ($p == '"'))
			and $params[0][1]->type == 'champ'
		) {
			$val[] = "$p\\$p#" . $params[0][1]->nom_champ . "\\$p$p";
		} else {
			foreach ((($op != 'IN') ? $params : calculer_vieux_in($params)) as $p) {
				$a = calculer_liste($p, $idb, $boucles, $parent);
				if (strcasecmp($op, 'IN') == 0) {
					$val[] = $a;
				} else {
					$val[] = kwote($a, $boucles[$idb]->sql_serveur, '@@defaultcast@@');
				} // toujours quoter en char ici
			}
		}
	}

	$fct = $args_sql = '';
	// fonction SQL ?
	// chercher FONCTION(champ) tel que CONCAT(titre,descriptif)
	if (preg_match('/^(.*)' . SQL_ARGS . '$/', $col, $m)) {
		$fct = $m[1];
		preg_match('/^\(([^,]*)(.*)\)$/', $m[2], $a);
		$col = $a[1];
		if (preg_match('/^(\S*)(\s+AS\s+.*)$/i', $col, $m)) {
			$col = $m[1];
			$args_sql = $m[2];
		}
		$args_sql .= $a[2];
	}

	return [$fct, $col, $op, $val, $args_sql];
}

// compatibilite ancienne version

function calculer_vieux_in($params) {
	$deb = $params[0][0];
	$k = (is_countable($params) ? count($params) : 0) - 1;
	$last = $params[$k];
	$j = (is_countable($last) ? count($last) : 0) - 1;
	$last = $last[$j];
	$n = isset($last->texte) ? strlen($last->texte) : 0;

	if (
		!((isset($deb->texte[0]) and $deb->texte[0] == '(')
		&& (isset($last->texte[$n - 1]) and $last->texte[$n - 1] == ')'))
	) {
		return $params;
	}
	$params[0][0]->texte = substr($deb->texte, 1);
	// attention, on peut avoir k=0,j=0 ==> recalculer
	$last = $params[$k][$j];
	$n = strlen($last->texte);
	$params[$k][$j]->texte = substr($last->texte, 0, $n - 1);
	$newp = [];
	foreach ($params as $v) {
		if ($v[0]->type != 'texte') {
			$newp[] = $v;
		} else {
			foreach (explode(',', $v[0]->texte) as $x) {
				$t = new Texte();
				$t->texte = $x;
				$newp[] = [$t];
			}
		}
	}

	return $newp;
}

/**
 * Calcule les cas particuliers de critères de date
 *
 * Lorsque la colonne correspond à un critère de date, tel que
 * jour, jour_relatif, jour_x, age, age_relatif, age_x...
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param string $col Nom du champ demandé
 * @return string|array
 *     chaine vide si ne correspond pas à une date,
 *     sinon liste
 *     - expression SQL de calcul de la date,
 *     - nom de la colonne de date (si le calcul n'est pas relatif)
 **/
function calculer_critere_infixe_date($idb, &$boucles, $col) {
	if (!preg_match(',^((age|jour|mois|annee)_relatif|date|mois|annee|jour|heure|age)(_[a-z_]+)?$,', $col, $regs)) {
		return '';
	}

	$boucle = $boucles[$idb];
	$table = $boucle->show;

	// si c'est une colonne de la table, ne rien faire
	if (isset($table['field'][$col])) {
		return '';
	}

	// Le type de critère à prendre en compte
	$col = $regs[1];

	// Si on trouve un nom de champ date précis, on l'utilise, pas besoin de déclaration dans l'API objet
	if (isset($regs[3]) and $suite = $regs[3]) {
		# Recherche de l'existence du champ date_xxxx,
		# si oui choisir ce champ, sinon choisir xxxx
		if (isset($table['field']["date$suite"])) {
			$date_orig = 'date' . $suite;
		} else {
			$date_orig = substr($suite, 1);
		}

		$pred = $date_orig;
	} else { // Sinon il FAUT avoir déclaré le champ date officiel dans l'API objet
		// Si aucune déclaration trouvée, on quitte
		if (!$table['date'] && !isset($GLOBALS['table_date'][$table['id_table']])) {
			return '';
		}
		// Par défaut, on prend le champ date déclaré dans l'API
		$pred = $date_orig = $GLOBALS['table_date'][$table['id_table']] ?? $table['date'];

		// Si c'est pour du relatif
		if (isset($regs[2]) and $rel = $regs[2]) {
			$pred = 'date';
		}
	}

	$date_compare = "\"' . normaliser_date(" .
		calculer_argument_precedent($idb, $pred, $boucles) .
		") . '\"";

	$col_vraie = $date_orig;
	$date_orig = $boucle->id_table . '.' . $date_orig;

	switch ($col) {
		case 'date':
			$col = $date_orig;
			break;
		case 'jour':
			$col = "DAYOFMONTH($date_orig)";
			break;
		case 'mois':
			$col = "MONTH($date_orig)";
			break;
		case 'annee':
			$col = "YEAR($date_orig)";
			break;
		case 'heure':
			$col = "DATE_FORMAT($date_orig, \\'%H:%i\\')";
			break;
		case 'age':
			$col = calculer_param_date("\'' . date('Y-m-d H:i:00') . '\'", $date_orig);
			$col_vraie = '';// comparer a un int (par defaut)
			break;
		case 'age_relatif':
			$col = calculer_param_date($date_compare, $date_orig);
			$col_vraie = '';// comparer a un int (par defaut)
			break;
		case 'jour_relatif':
			$col = '(TO_DAYS(' . $date_compare . ')-TO_DAYS(' . $date_orig . '))';
			$col_vraie = '';// comparer a un int (par defaut)
			break;
		case 'mois_relatif':
			$col = 'MONTH(' . $date_compare . ')-MONTH(' .
				$date_orig . ')+12*(YEAR(' . $date_compare .
				')-YEAR(' . $date_orig . '))';
			$col_vraie = '';// comparer a un int (par defaut)
			break;
		case 'annee_relatif':
			$col = 'YEAR(' . $date_compare . ')-YEAR(' .
				$date_orig . ')';
			$col_vraie = '';// comparer a un int (par defaut)
			break;
	}

	return [$col, $col_vraie];
}

/**
 * Calcule l'expression SQL permettant de trouver un nombre de jours écoulés.
 *
 * Le calcul SQL retournera un nombre de jours écoulés entre la date comparée
 * et la colonne SQL indiquée
 *
 * @param string $date_compare
 *     Code PHP permettant d'obtenir le timestamp référent.
 *     C'est à partir de lui que l'on compte les jours
 * @param string $date_orig
 *     Nom de la colonne SQL qui possède la date
 * @return string
 *     Expression SQL calculant le nombre de jours écoulé entre une valeur
 *     de colonne SQL et une date.
 **/
function calculer_param_date($date_compare, $date_orig) {
	if (preg_match(",'\" *\.(.*)\. *\"',", $date_compare, $r)) {
		$init = "'\" . (\$x = $r[1]) . \"'";
		$date_compare = '\'$x\'';
	} else {
		$init = $date_compare;
	}

	return
		// optimisation : mais prevoir le support SQLite avant
		"TIMESTAMPDIFF(HOUR,$date_orig,$init)/24";
}

/**
 * Compile le critère {source} d'une boucle DATA
 *
 * Permet de déclarer le mode d'obtention des données dans une boucle
 * DATA (premier argument) et les données (la suite).
 *
 * @example
 *     (DATA){source mode, "xxxxxx", arg, arg, arg}
 *     (DATA){source tableau, #LISTE{un,deux,trois}}
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_DATA_source_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];

	$args = [];
	foreach ($crit->param as &$param) {
		array_push(
			$args,
			calculer_liste($param, $idb, $boucles, $boucles[$idb]->id_parent)
		);
	}

	$boucle->hash .= '
	$command[\'sourcemode\'] = ' . array_shift($args) . ";\n";

	$boucle->hash .= '
	$command[\'source\'] = array(' . join(', ', $args) . ");\n";
}

/**
 * Compile le critère {datacache} d'une boucle DATA
 *
 * Permet de transmettre une durée de cache (time to live) utilisée
 * pour certaines sources d'obtention des données (par exemple RSS),
 * indiquant alors au bout de combien de temps la donnée est à réobtenir.
 *
 * La durée par défaut est 1 journée.
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_DATA_datacache_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$boucle->hash .= '
	$command[\'datacache\'] = ' . calculer_liste($crit->param[0], $idb, $boucles, $boucles[$idb]->id_parent) . ';';
}


/**
 * Compile le critère {args} d'une boucle PHP
 *
 * Permet de passer des arguments à un iterateur non-spip
 * (PHP:xxxIterator){args argument1, argument2, argument3}
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_php_args_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$boucle->hash .= '$command[\'args\']=array();';
	foreach ($crit->param as $param) {
		$boucle->hash .= '
			$command[\'args\'][] = ' . calculer_liste($param, $idb, $boucles, $boucles[$idb]->id_parent) . ';';
	}
}

/**
 * Compile le critère {liste} d'une boucle DATA
 *
 * Passe une liste de données à l'itérateur DATA
 *
 * @example
 *     (DATA){liste X1, X2, X3}
 *     équivalent à (DATA){source tableau,#LISTE{X1, X2, X3}}
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_DATA_liste_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$boucle->hash .= "\n\t" . '$command[\'liste\'] = array();' . "\n";
	foreach ($crit->param as $param) {
		$boucle->hash .= "\t" . '$command[\'liste\'][] = ' . calculer_liste(
			$param,
			$idb,
			$boucles,
			$boucles[$idb]->id_parent
		) . ";\n";
	}
}

/**
 * Compile le critère {enum} d'une boucle DATA
 *
 * Passe les valeurs de début et de fin d'une énumération, qui seront
 * vues comme une liste d'autant d'éléments à parcourir pour aller du
 * début à la fin.
 *
 * Cela utilisera la fonction range() de PHP.
 *
 * @example
 *     (DATA){enum Xdebut, Xfin}
 *     (DATA){enum a,z}
 *     (DATA){enum z,a}
 *     (DATA){enum 1.0,9.2}
 *
 * @link http://php.net/manual/fr/function.range.php
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_DATA_enum_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$boucle->hash .= "\n\t" . '$command[\'enum\'] = array();' . "\n";
	foreach ($crit->param as $param) {
		$boucle->hash .= "\t" . '$command[\'enum\'][] = ' . calculer_liste(
			$param,
			$idb,
			$boucles,
			$boucles[$idb]->id_parent
		) . ";\n";
	}
}

/**
 * Compile le critère {datapath} d'une boucle DATA
 *
 * Extrait un chemin d'un tableau de données
 *
 * (DATA){datapath query.results}
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_DATA_datapath_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	foreach ($crit->param as $param) {
		$boucle->hash .= '
			$command[\'datapath\'][] = ' . calculer_liste($param, $idb, $boucles, $boucles[$idb]->id_parent) . ';';
	}
}


/**
 * Compile le critère {si}
 *
 * Le critère {si condition} est applicable à toutes les boucles et conditionne
 * l'exécution de la boucle au résultat de la condition. La partie alternative
 * de la boucle est alors affichée si une condition n'est pas remplie (comme
 * lorsque la boucle ne ramène pas de résultat).
 * La différence étant que si la boucle devait réaliser une requête SQL
 * (par exemple une boucle ARTICLES), celle ci n'est pas réalisée si la
 * condition n'est pas remplie.
 *
 * Les valeurs de la condition sont forcément extérieures à cette boucle
 * (sinon il faudrait l'exécuter pour connaître le résultat, qui doit tester
 * si on exécute la boucle !)
 *
 * Si plusieurs critères {si} sont présents, ils sont cumulés :
 * si une seule des conditions n'est pas vérifiée, la boucle n'est pas exécutée.
 *
 * @example
 *     {si #ENV{exec}|=={article}}
 *     {si (#_contenu:GRAND_TOTAL|>{10})}
 *     {si #AUTORISER{voir,articles}}
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_si_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	// il faut initialiser 1 fois le tableau a chaque appel de la boucle
	// (par exemple lorsque notre boucle est appelee dans une autre boucle)
	// mais ne pas l'initialiser n fois si il y a n criteres {si } dans la boucle !
	$boucle->hash .= "\n\tif (!isset(\$si_init)) { \$command['si'] = array(); \$si_init = true; }\n";
	if ($crit->param) {
		foreach ($crit->param as $param) {
			$boucle->hash .= "\t\$command['si'][] = "
				. calculer_liste($param, $idb, $boucles, $boucles[$idb]->id_parent) . ";\n";
		}
		// interdire {si 0} aussi !
	} else {
		$boucle->hash .= '$command[\'si\'][] = 0;';
	}
}

/**
 * Compile le critère {tableau} d'une boucle POUR
 *
 * {tableau #XX} pour compatibilite ascendante boucle POUR
 * ... préférer la notation (DATA){source tableau,#XX}
 *
 * @deprecated 4.0
 * @see Utiliser une boucle (DATA){source tableau,#XX}
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_POUR_tableau_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$boucle->hash .= '
	$command[\'source\'] = array(' . calculer_liste($crit->param[0], $idb, $boucles, $boucles[$idb]->id_parent) . ');
	$command[\'sourcemode\'] = \'table\';';
}


/**
 * Compile le critère {noeud}
 *
 * Trouver tous les objets qui ont des enfants (les noeuds de l'arbre)
 * {noeud}
 * {!noeud} retourne les feuilles
 *
 * @global array $exceptions_des_tables
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_noeud_dist($idb, &$boucles, $crit) {

	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$primary = $boucle->primary;

	if (!$primary or strpos($primary, ',')) {
		erreur_squelette(_T('zbug_doublon_sur_table_sans_cle_primaire'), $boucle);

		return;
	}
	$table = $boucle->type_requete;
	$table_sql = table_objet_sql(objet_type($table));

	$id_parent = $GLOBALS['exceptions_des_tables'][$boucle->id_table]['id_parent'] ?? 'id_parent';

	$in = 'IN';
	$where = ["'IN'", "'$boucle->id_table." . "$primary'", "'('.sql_get_select('$id_parent', '$table_sql').')'"];
	if ($not) {
		$where = ["'NOT'", $where];
	}

	$boucle->where[] = $where;
}

/**
 * Compile le critère {feuille}
 *
 * Trouver tous les objets qui n'ont pas d'enfants (les feuilles de l'arbre)
 * {feuille}
 * {!feuille} retourne les noeuds
 *
 * @global array $exceptions_des_tables
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 */
function critere_feuille_dist($idb, &$boucles, $crit) {
	$not = $crit->not;
	$crit->not = $not ? false : true;
	critere_noeud_dist($idb, $boucles, $crit);
	$crit->not = $not;
}
