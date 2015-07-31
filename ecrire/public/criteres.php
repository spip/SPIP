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

/**
 * Définition des {criteres} d'une boucle
 *
 * @package SPIP\Compilateur\Criteres
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

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
 * @link http://www.spip.net/@racine
 * 
 * @param string $idb
 *     Identifiant de la boucle
 * @param array $boucles
 *     AST du squelette
 * @param array $crit
 *     Paramètres du critère dans cette boucle
 * @return
 *     AST complété de la gestion du critère
**/
function critere_racine_dist($idb, &$boucles, $crit){
	global $exceptions_des_tables;
	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$id_parent = isset($exceptions_des_tables[$boucle->id_table]['id_parent']) ?
		$exceptions_des_tables[$boucle->id_table]['id_parent'] :
		'id_parent';

	$c = array("'='", "'$boucle->id_table."."$id_parent'", 0);
	$boucle->where[] = ($crit->not ? array("'NOT'", $c) : $c);
}


/**
 * Compile le critère {exclus}
 *
 * Exclut du résultat l’élément dans lequel on se trouve déjà
 * 
 * @link http://www.spip.net/@exclus
 *
 * @param string $idb
 *     Identifiant de la boucle
 * @param array $boucles
 *     AST du squelette
 * @param array $crit
 *     Paramètres du critère dans cette boucle
 * @return
 *     AST complété de la gestion du critère
**/
function critere_exclus_dist($idb, &$boucles, $crit){
	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$id = $boucle->primary;

	if ($not OR !$id)
		return (array('zbug_critere_inconnu', array('critere' => $not.$crit->op)));
	$arg = kwote(calculer_argument_precedent($idb, $id, $boucles));
	$boucle->where[] = array("'!='", "'$boucle->id_table."."$id'", $arg);
}


/**
 * Compile le critère {doublons} ou {unique}
 *
 * Ce critères enlève de la boucle les éléments déjà sauvegardés
 * dans un précédent critère {doublon} sur une boucle de même table.
 *
 * Il est possible de spécifier un nom au doublon tel que {doublons sommaire}
 * 
 * @link http://www.spip.net/@doublons
 * 
 * @param string $idb
 * 		Identifiant de la boucle
 * @param array $boucles
 * 		AST du squelette
 * @param array $crit
 * 		Paramètres du critère dans cette boucle
 * @return
 * 		AST complété de la gestion du critère
**/
function critere_doublons_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];
	$primary = $boucle->primary;

	// la table nécessite une clé primaire, non composée
	if (!$primary OR strpos($primary, ',')){
		return (array('zbug_doublon_sur_table_sans_cle_primaire'));
	}

	$not = ($crit->not ? '' : 'NOT');

	// le doublon s'applique sur un type de boucle (article)
	$nom = "'" . $boucle->type_requete. "'";

	// compléter le nom avec un nom précisé {doublons nom}
	// on obtient $nom = "'article' . 'nom'"
	if (isset($crit->param[0])) {
		$nom .= "." . calculer_liste($crit->param[0], array(), $boucles, $boucles[$idb]->id_parent);
	}

	// code qui déclarera l'index du stockage de nos doublons (pour éviter une notice PHP)
	$init_comment = "\n\n\t// Initialise le(s) critère(s) doublons\n";
	$init_code = "\tif (!isset(\$doublons[\$d = $nom])) { \$doublons[\$d] = ''; }\n";

	// on crée un sql_in avec la clé primaire de la table
	// et la collection des doublons déjà emmagasinés dans le tableau
	// $doublons et son index, ici $nom

	// debut du code "sql_in('articles.id_article', "
	$debut_in = "sql_in('".$boucle->id_table.'.'.$primary."', ";
	// lecture des données du doublon "$doublons[$doublon_index[] = "
	// Attention : boucle->doublons désigne une variable qu'on affecte
	$debut_doub = '$doublons[' . (!$not ? '' : ($boucle->doublons."[]= "));

	// le debut complet du code des doublons
	$debut_doub = $debut_in . $debut_doub;

	// nom du doublon "('article' . 'nom')]"
	$fin_doub = "($nom)]";

	// si on trouve un autre critère doublon,
	// on fusionne pour avoir un seul IN, et on s'en va !
	foreach ($boucle->where as $k => $w) {
		if (strpos($w[0], $debut_doub)===0) {
			// fusionner le sql_in (du where)
			$boucle->where[$k][0] = $debut_doub . $fin_doub.' . '.substr($w[0], strlen($debut_in));
			// fusionner l'initialisation (du hash) pour faire plus joli
			$x = strpos($boucle->hash, $init_comment);
			$len = strlen($init_comment);
			$boucle->hash =
				substr($boucle->hash, 0, $x + $len) . $init_code . substr($boucle->hash, $x + $len);
			return;
		}
	}

	// mettre l'ensemble dans un tableau pour que ce ne soit pas vu comme une constante
	$boucle->where[] = array($debut_doub . $fin_doub.", '".$not."')");

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
 * @param string $idb
 *     Identifiant de la boucle
 * @param array $boucles
 *     AST du squelette
 * @param array $crit
 *     Paramètres du critère dans cette boucle
 * @return
 *     AST complété de la gestion du critère
**/
function critere_lang_select_dist($idb, &$boucles, $crit){
	if (!isset($crit->param[1][0]) OR !($param = $crit->param[1][0]->texte)) $param = 'oui';
	if ($crit->not) $param = ($param=='oui') ? 'non' : 'oui';
	$boucle = &$boucles[$idb];
	$boucle->lang_select = $param;
}

// {debut_xxx}
// http://www.spip.net/@debut_
// http://doc.spip.org/@critere_debut_dist
function critere_debut_dist($idb, &$boucles, $crit){
	list($un, $deux) = $crit->param;
	$un = $un[0]->texte;
	$deux = $deux[0]->texte;
	if ($deux){
		$boucles[$idb]->limit = 'intval($Pile[0]["debut'.
		                        $un.
		                        '"]) . ",'.
		                        $deux.
		                        '"';
	} else calculer_critere_DEFAUT_dist($idb, $boucles, $crit);
}

// {pagination}
// {pagination 20}
// {pagination #ENV{pages,5}} etc
// {pagination 20 #ENV{truc,chose}} pour utiliser la variable debut_#ENV{truc,chose}
// http://www.spip.net/@pagination
// http://doc.spip.org/@critere_pagination_dist
function critere_pagination_dist($idb, &$boucles, $crit){

	$boucle = &$boucles[$idb];
	// definition de la taille de la page
	$pas = !isset($crit->param[0][0]) ? "''"
		: calculer_liste(array($crit->param[0][0]), array(), $boucles, $boucle->id_parent);

	if (!preg_match(_CODE_QUOTE, $pas, $r)){
		$pas = "((\$a = intval($pas)) ? \$a : 10)";
	} else {
		$r = intval($r[2]);
		$pas = strval($r ? $r : 10);
	}
	$type = !isset($crit->param[0][1]) ? "'$idb'"
		: calculer_liste(array($crit->param[0][1]), array(), $boucles, $boucle->id_parent);
	$debut = ($type[0]!=="'") ? "'debut'.$type"
		: ("'debut".substr($type, 1));

	$boucle->modificateur['debut_nom'] = $type;
	$partie =
		// tester si le numero de page demande est de la forme '@yyy'
		'isset($Pile[0]['.$debut.']) ? $Pile[0]['.$debut.'] : _request('.$debut.");\n"
		."\tif(substr(\$debut_boucle,0,1)=='@'){\n"
		."\t\t".'$debut_boucle = $Pile[0]['.$debut.'] = quete_debut_pagination(\''.$boucle->primary.'\',$Pile[0][\'@'.$boucle->primary.'\'] = substr($debut_boucle,1),'.$pas.',$iter);'."\n"
		."\t\t".'$iter->seek(0);'."\n"
		."\t}\n"
		."\t".'$debut_boucle = intval($debut_boucle)';

	$boucle->hash .= '
	$command[\'pagination\'] = array((isset($Pile[0]['.$debut.']) ? $Pile[0]['.$debut.'] : null), ' . $pas . ');';

	$boucle->total_parties = $pas;
	calculer_parties($boucles, $idb, $partie, 'p+');
	// ajouter la cle primaire dans le select pour pouvoir gerer la pagination referencee par @id
	// sauf si pas de primaire, ou si primaire composee
	// dans ce cas, on ne sait pas gerer une pagination indirecte
	$t = $boucle->id_table.'.'.$boucle->primary;
	if ($boucle->primary
	    AND !preg_match('/[,\s]/', $boucle->primary)
	        AND !in_array($t, $boucle->select)
	)
		$boucle->select[] = $t;
}


// {recherche} ou {recherche susan}
// http://www.spip.net/@recherche
// http://doc.spip.org/@critere_recherche_dist
function critere_recherche_dist($idb, &$boucles, $crit){

	$boucle = &$boucles[$idb];

	if (!$boucle->primary OR strpos($boucle->primary, ',')){
		erreur_squelette(_T('zbug_critere_sur_table_sans_cle_primaire',array('critere'=>'recherche')), $boucle);
		return;
	}

	if (isset($crit->param[0]))
		$quoi = calculer_liste($crit->param[0], array(), $boucles, $boucles[$idb]->id_parent);
	else
		$quoi = '(isset($Pile[0]["recherche"])?$Pile[0]["recherche"]:(isset($GLOBALS["recherche"])?$GLOBALS["recherche"]:""))';

	$_modificateur = var_export($boucle->modificateur, true);
	$boucle->hash .= '
	// RECHERCHE'
	                 .($crit->cond ? '
	if (!strlen('.$quoi.')){
		list($rech_select, $rech_where) = array("0 as points","");
	} else' : '').'
	{
		$prepare_recherche = charger_fonction(\'prepare_recherche\', \'inc\');
		list($rech_select, $rech_where) = $prepare_recherche('.$quoi.', "'.$boucle->id_table.'", "'.$crit->cond.'","'.$boucle->sql_serveur.'",'.$_modificateur.',"'.$boucle->primary.'");
	}
	';


	$t = $boucle->id_table.'.'.$boucle->primary;
	if (!in_array($t, $boucles[$idb]->select))
		$boucle->select[] = $t; # pour postgres, neuneu ici
	// jointure uniquement sur le serveur principal
	// (on ne peut joindre une table d'un serveur distant avec la table des resultats du serveur principal)
	if (!$boucle->sql_serveur){
		$boucle->join['resultats'] = array("'".$boucle->id_table."'", "'id'", "'".$boucle->primary."'");
		$boucle->from['resultats'] = 'spip_resultats';
	}
	$boucle->select[] = '$rech_select';
	//$boucle->where[]= "\$rech_where?'resultats.id=".$boucle->id_table.".".$boucle->primary."':''";

	// et la recherche trouve
	$boucle->where[] = '$rech_where?$rech_where:\'\'';
}

// {traduction}
// http://www.spip.net/@traduction
//   (id_trad>0 AND id_trad=id_trad(precedent))
//    OR id_article=id_article(precedent)
// http://doc.spip.org/@critere_traduction_dist
function critere_traduction_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];
	$prim = $boucle->primary;
	$table = $boucle->id_table;
	$arg = kwote(calculer_argument_precedent($idb, 'id_trad', $boucles));
	$dprim = kwote(calculer_argument_precedent($idb, $prim, $boucles));
	$boucle->where[] =
		array("'OR'",
		      array("'AND'",
		            array("'='", "'$table.id_trad'", 0),
		            array("'='", "'$table.$prim'", $dprim)
		      ),
		      array("'AND'",
		            array("'>'", "'$table.id_trad'", 0),
		            array("'='", "'$table.id_trad'", $arg)
		      )
		);
}

// {origine_traduction}
//   (id_trad>0 AND id_article=id_trad) OR (id_trad=0)
// http://www.spip.net/@origine_traduction
// http://doc.spip.org/@critere_origine_traduction_dist
function critere_origine_traduction_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];
	$prim = $boucle->primary;
	$table = $boucle->id_table;

	$c =
		array("'OR'",
		      array("'='", "'$table."."id_trad'", "'$table.$prim'"),
		      array("'='", "'$table.id_trad'", "'0'")
		);
	$boucle->where[] = ($crit->not ? array("'NOT'", $c) : $c);
}

// {meme_parent}
// http://www.spip.net/@meme_parent
// http://doc.spip.org/@critere_meme_parent_dist
function critere_meme_parent_dist($idb, &$boucles, $crit){
	global $exceptions_des_tables;
	$boucle = &$boucles[$idb];
	$arg = kwote(calculer_argument_precedent($idb, 'id_parent', $boucles));
	$id_parent = isset($exceptions_des_tables[$boucle->id_table]['id_parent']) ?
		$exceptions_des_tables[$boucle->id_table]['id_parent'] :
		'id_parent';
	$mparent = $boucle->id_table.'.'.$id_parent;

	if ($boucle->type_requete=='rubriques' OR isset($exceptions_des_tables[$boucle->id_table]['id_parent'])){
		$boucle->where[] = array("'='", "'$mparent'", $arg);

	}
		// le cas FORUMS est gere dans le plugin forum, dans la fonction critere_FORUMS_meme_parent_dist()
	else {
		return (array('zbug_critere_inconnu', array('critere' => $crit->op.' '.$boucle->type_requete)));
	}
}


/**
 * Sélectionne dans une boucle les éléments appartenant à une branche d'une rubrique
 * 
 * Calcule une branche d'une rubrique et conditionne la boucle avec.
 * Cherche l'identifiant de la rubrique dans les boucles parentes ou par jointure
 * et calcule la liste des identifiants de rubrique de toute la branche
 *
 * @link http://www.spip.net/@branche
 * 
 * @param string $idb
 * 		Identifiant de la boucle
 * @param array $boucles
 * 		AST du squelette
 * @param array $crit
 * 		Paramètres du critère dans cette boucle
 * @return
 * 		AST complété de la condition where au niveau de la boucle,
 * 		restreignant celle ci aux rubriques de la branche
**/
function critere_branche_dist($idb, &$boucles, $crit){

	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$arg = calculer_argument_precedent($idb, 'id_rubrique', $boucles);

	//Trouver une jointure
	$champ = "id_rubrique";
	$desc = $boucle->show;
	//Seulement si necessaire
	if (!array_key_exists($champ, $desc['field'])){
		$cle = trouver_jointure_champ($champ, $boucle);
		$trouver_table = charger_fonction("trouver_table", "base");
		$desc = $trouver_table($boucle->from[$cle]);
		if (count(trouver_champs_decomposes($champ, $desc))>1){
			$decompose = decompose_champ_id_objet($champ);
			$champ = array_shift($decompose);
			$boucle->where[] = array("'='", _q($cle.".".reset($decompose)), '"'.sql_quote(end($decompose)).'"');
		}
	}
	else $cle = $boucle->id_table;

	$c = "sql_in('$cle".".$champ', calcul_branche_in($arg)"
	     .($not ? ", 'NOT'" : '').")";
	$boucle->where[] = !$crit->cond ? $c :
		("($arg ? $c : ".($not ? "'0=1'" : "'1=1'").')');
}

// {logo} liste les objets qui ont un logo
// http://doc.spip.org/@critere_logo_dist
function critere_logo_dist($idb, &$boucles, $crit){

	$not = $crit->not;
	$boucle = &$boucles[$idb];

	$c = "sql_in('".
	     $boucle->id_table.'.'.$boucle->primary
	     ."', lister_objets_avec_logos('".$boucle->primary."'), '')";

	if ($crit->cond) $c = "($arg ? $c : 1)";

	if ($not)
		$boucle->where[] = array("'NOT'", $c);
	else
		$boucle->where[] = $c;
}

// c'est la commande SQL "GROUP BY"
// par exemple <boucle(articles){fusion lang}>
// http://doc.spip.org/@critere_fusion_dist
function critere_fusion_dist($idb, &$boucles, $crit){
	if ($t = isset($crit->param[0])){
		$t = $crit->param[0];
		if ($t[0]->type=='texte'){
			$t = $t[0]->texte;
			if (preg_match("/^(.*)\.(.*)$/", $t, $r)){
				$t = table_objet_sql($r[1]);
				$t = array_search($t, $boucles[$idb]->from);
				if ($t) $t .= '.'.$r[2];
			}
		} else {
			$t = '".'
			     .calculer_critere_arg_dynamique($idb, $boucles, $t)
			     .'."';
		}
	}
	if ($t){
		$boucles[$idb]->group[] = $t;
		if (!in_array($t, $boucles[$idb]->select))
			$boucles[$idb]->select[] = $t;
	} else
		return (array('zbug_critere_inconnu', array('critere' => $crit->op.' ?')));
}

// c'est la commande SQL "COLLATE"
// qui peut etre appliquee sur les order by, group by, where like ...
// http://doc.spip.org/@critere_collecte_dist
function critere_collecte_dist($idb, &$boucles, $crit){
	if (isset($crit->param[0])){
		$_coll = calculer_liste($crit->param[0], array(), $boucles, $boucles[$idb]->id_parent);
		$boucle = $boucles[$idb];
		$boucle->modificateur['collate'] = "($_coll ?' COLLATE '.$_coll:'')";
		$n = count($boucle->order);
		if ($n && (strpos($boucle->order[$n-1], 'COLLATE')===false))
			$boucle->order[$n-1] .= " . ".$boucle->modificateur['collate'];
	} else
		return (array('zbug_critere_inconnu', array('critere' => $crit->op." ".count($boucles[$idb]->order))));
}

// http://doc.spip.org/@calculer_critere_arg_dynamique
function calculer_critere_arg_dynamique($idb, &$boucles, $crit, $suffix = ''){
	$boucle = $boucles[$idb];
	$alt = "('".$boucle->id_table.'.\' . $x'.$suffix.')';
	$var = '$champs_'.$idb;
	$desc = (strpos($boucle->in, "static $var =")!==false);
	if (!$desc){
		$desc = $boucle->show['field'];
		$desc = implode(',', array_map('_q', array_keys($desc)));
		$boucles[$idb]->in .= "\n\tstatic $var = array(".$desc.");";
	}
	if ($desc) $alt = "(in_array(\$x, $var)  ? $alt :(\$x$suffix))";
	$arg = calculer_liste($crit, array(), $boucles, $boucle->id_parent);
	return "((\$x = preg_replace(\"/\\W/\",'', $arg)) ? $alt : '')";
}

// Tri : {par xxxx}
// http://www.spip.net/@par
// http://doc.spip.org/@critere_par_dist
function critere_par_dist($idb, &$boucles, $crit){
	return critere_parinverse($idb, $boucles, $crit);
}

// http://doc.spip.org/@critere_parinverse
function critere_parinverse($idb, &$boucles, $crit, $sens = ''){
	global $exceptions_des_jointures;
	$boucle = &$boucles[$idb];
	if ($crit->not) $sens = $sens ? "" : " . ' DESC'";
	$collecte = (isset($boucle->modificateur['collecte'])) ? " . ".$boucle->modificateur['collecte'] : "";

	foreach ($crit->param as $tri){

		$order = $fct = ""; // en cas de fonction SQL
		// tris specifies dynamiquement
		if ($tri[0]->type!='texte'){
			// calculer le order dynamique qui verifie les champs
			$order = calculer_critere_arg_dynamique($idb, $boucles, $tri, $sens);
			// et si ce n'est fait, ajouter un champ 'hasard' 
			// pour supporter 'hasard' comme tri dynamique
			$par = "rand()";
			$parha = $par." AS hasard";
			if (!in_array($parha, $boucle->select))
				$boucle->select[] = $parha;
		} else {
			$par = array_shift($tri);
			$par = $par->texte;
			// par multi champ
			if (preg_match(",^multi[\s]*(.*)$,", $par, $m)){
				$champ = trim($m[1]);
				// par multi L1.champ
				if (strpos($champ, '.')) {
					$cle = '';
				// par multi champ (champ sur une autre table)
				} elseif (!array_key_exists($champ, $boucle->show['field'])){
					$cle = trouver_jointure_champ($champ, $boucle);
				// par multi champ (champ dans la table en cours)
				} else {
					$cle = $boucle->id_table;
				}
				if ($cle) { $cle .= '.'; }
				$texte = $cle.$champ;
				$boucle->select[] = "\".sql_multi('".$texte."', \$GLOBALS['spip_lang']).\"";
				$order = "'multi'";
				// par num champ(, suite)
			} else if (preg_match(",^num (.*)$,m", $par, $m)) {
				$champ = trim($m[1]);
				// par num L1.champ
				if (strpos($champ, '.')) {
					$cle = '';
				// par num champ (champ sur une autre table)
				} elseif (!array_key_exists($champ, $boucle->show['field'])){
					$cle = trouver_jointure_champ($champ, $boucle);
				// par num champ (champ dans la table en cours)
				} else {
					$cle = $boucle->id_table;
				}
				if ($cle) { $cle .= '.'; }
				$texte = '0+'. $cle . $champ;
				$suite = calculer_liste($tri, array(), $boucles, $boucle->id_parent);
				if ($suite!=="''")
					$texte = "\" . ((\$x = $suite) ? ('$texte' . \$x) : '0')"." . \"";
				$as = 'num'.($boucle->order ? count($boucle->order) : "");
				$boucle->select[] = $texte." AS $as";
				$order = "'$as'";
			} else {
				if (!preg_match(",^".CHAMP_SQL_PLUS_FONC.'$,is', $par, $match)){
					return (array('zbug_critere_inconnu', array('critere' => $crit->op." $par")));
				} else {
					if (count($match)>2){
						$par = substr($match[2], 1, -1);
						$fct = $match[1];
					}
					// par hasard
					if ($par=='hasard'){
						$par = "rand()";
						$boucle->select[] = $par." AS alea";
						$order = "'alea'";
					}
						// par titre_mot ou type_mot voire d'autres
					else if (isset($exceptions_des_jointures[$par])){
						list($table, $champ) = $exceptions_des_jointures[$par];
						$order = critere_par_joint($table, $champ, $boucle, $idb);
						if (!$order)
							return (array('zbug_critere_inconnu', array('critere' => $crit->op." $par")));
					}
					else if ($par=='date'
					         AND $desc = $boucle->show
					             AND $desc['date']
					){
						$m = $desc['date'];
						$order = "'".$boucle->id_table.".".$m."'";
					}
						// par champ. Verifier qu'ils sont presents.
					elseif (preg_match("/^([^,]*)\.(.*)$/", $par, $r)) {
						// cas du tri sur champ de jointure explicite
						$t = array_search($r[1], $boucle->from);
						if (!$t){
							$t = trouver_jointure_champ($r[2], $boucle, array($r[1]));
						}
						if (!$t){
							return (array('zbug_critere_inconnu', array('critere' => $crit->op." $par")));
						} else	 $order = "'".$t.'.'.$r[2]."'";
					} else {
						$desc = $boucle->show;
						if ($desc['field'][$par])
							$par = $boucle->id_table.".".$par;
						// sinon tant pis, ca doit etre un champ synthetise (cf points)
						$order = "'$par'";
					}
				}
			}
		}
		if (preg_match('/^\'([^"]*)\'$/', $order, $m)){
			$t = $m[1];
			if (strpos($t, '.') AND !in_array($t, $boucle->select)){
				$boucle->select[] = $t;
			}
		} else $sens = '';

		if ($fct){
			if (preg_match("/^\s*'(.*)'\s*$/", $order, $r))
				$order = "'$fct(".$r[1].")'";
			else $order = "'$fct(' . $order . ')'";
		}
		$t = $order.$collecte.$sens;
		if (preg_match("/^(.*)'\s*\.\s*'([^']*')$/", $t, $r))
			$t = $r[1].$r[2];
		$boucle->order[] = $t;
	}
}

// http://doc.spip.org/@critere_par_joint
function critere_par_joint($table, $champ, &$boucle, $idb){
	$t = array_search($table, $boucle->from);
	if (!$t) $t = trouver_jointure_champ($champ, $boucle);
	return !$t ? '' : ("'".$t.'.'.$champ."'");
}

// {inverse}
// http://www.spip.net/@inverse

// http://doc.spip.org/@critere_inverse_dist
function critere_inverse_dist($idb, &$boucles, $crit){

	$boucle = &$boucles[$idb];
	// Classement par ordre inverse
	if ($crit->not)
		critere_parinverse($idb, $boucles, $crit);
	else
	{
		$order = "' DESC'";
		// Classement par ordre inverse fonction eventuelle de #ENV{...}
		if (isset($crit->param[0])){
			$critere = calculer_liste($crit->param[0], array(), $boucles, $boucles[$idb]->id_parent);
			$order = "(($critere)?' DESC':'')";
		}

		$n = count($boucle->order);
		if (!$n){
			if (isset($boucle->default_order[0]))
				$boucle->default_order[0] .= ' . " DESC"';
			else
				$boucle->default_order[] = ' DESC';
		} else {
			$t = $boucle->order[$n-1]." . $order";
			if (preg_match("/^(.*)'\s*\.\s*'([^']*')$/", $t, $r))
				$t = $r[1].$r[2];
			$boucle->order[$n-1] = $t;
		}
	}
}

// http://doc.spip.org/@critere_agenda_dist
function critere_agenda_dist($idb, &$boucles, $crit){
	$params = $crit->param;

	if (count($params)<1)
		return (array('zbug_critere_inconnu', array('critere' => $crit->op." ?")));

	$parent = $boucles[$idb]->id_parent;

	// les valeurs $date et $type doivent etre connus a la compilation
	// autrement dit ne pas etre des champs

	$date = array_shift($params);
	$date = $date[0]->texte;

	$type = array_shift($params);
	$type = $type[0]->texte;

	$annee = $params ? array_shift($params) : "";
	$annee = "\n".'sprintf("%04d", ($x = '.
	         calculer_liste($annee, array(), $boucles, $parent).
	         ') ? $x : date("Y"))';

	$mois = $params ? array_shift($params) : "";
	$mois = "\n".'sprintf("%02d", ($x = '.
	        calculer_liste($mois, array(), $boucles, $parent).
	        ') ? $x : date("m"))';

	$jour = $params ? array_shift($params) : "";
	$jour = "\n".'sprintf("%02d", ($x = '.
	        calculer_liste($jour, array(), $boucles, $parent).
	        ') ? $x : date("d"))';

	$annee2 = $params ? array_shift($params) : "";
	$annee2 = "\n".'sprintf("%04d", ($x = '.
	          calculer_liste($annee2, array(), $boucles, $parent).
	          ') ? $x : date("Y"))';

	$mois2 = $params ? array_shift($params) : "";
	$mois2 = "\n".'sprintf("%02d", ($x = '.
	         calculer_liste($mois2, array(), $boucles, $parent).
	         ') ? $x : date("m"))';

	$jour2 = $params ? array_shift($params) : "";
	$jour2 = "\n".'sprintf("%02d", ($x = '.
	         calculer_liste($jour2, array(), $boucles, $parent).
	         ') ? $x : date("d"))';

	$boucle = &$boucles[$idb];
	$date = $boucle->id_table.".$date";

	$quote_end = ",'".$boucle->sql_serveur."','text'";
	if ($type=='jour')
		$boucle->where[] = array("'='", "'DATE_FORMAT($date, \'%Y%m%d\')'",
		                         ("sql_quote($annee . $mois . $jour$quote_end)"));
	elseif ($type=='mois')
		$boucle->where[] = array("'='", "'DATE_FORMAT($date, \'%Y%m\')'",
		                         ("sql_quote($annee . $mois$quote_end)"));
	elseif ($type=='semaine')
		$boucle->where[] = array("'AND'",
		                         array("'>='",
		                               "'DATE_FORMAT($date, \'%Y%m%d\')'",
		                               ("date_debut_semaine($annee, $mois, $jour)")),
		                         array("'<='",
		                               "'DATE_FORMAT($date, \'%Y%m%d\')'",
		                               ("date_fin_semaine($annee, $mois, $jour)")));
	elseif (count($crit->param)>2)
		$boucle->where[] = array("'AND'",
		                         array("'>='",
		                               "'DATE_FORMAT($date, \'%Y%m%d\')'",
		                               ("sql_quote($annee . $mois . $jour$quote_end)")),
		                         array("'<='", "'DATE_FORMAT($date, \'%Y%m%d\')'", ("sql_quote($annee2 . $mois2 . $jour2$quote_end)")));
	// sinon on prend tout
}

// http://doc.spip.org/@calculer_critere_parties
function calculer_critere_parties($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];
	$a1 = $crit->param[0];
	$a2 = $crit->param[1];
	$op = $crit->op;

	list($a11, $a12) = calculer_critere_parties_aux($idb, $boucles, $a1);
	list($a21, $a22) = calculer_critere_parties_aux($idb, $boucles, $a2);

	if (($op==',') && (is_numeric($a11) && (is_numeric($a21)))){
		$boucle->limit = $a11.','.$a21;
	}
	else {
		$boucle->total_parties = ($a21!='n') ? $a21 : $a22;
		$partie = ($a11!='n') ? $a11 : $a12;
		$mode = (($op=='/') ? '/' :
			(($a11=='n') ? '-' : '+').(($a21=='n') ? '-' : '+'));
		// cas simple {0,#ENV{truc}} compilons le en LIMIT :
		if ($a11!=='n' AND $a21!=='n' AND $mode=="++" AND $op==','){
			$boucle->limit =
				(is_numeric($a11)?"'$a11'":$a11)
				.".','."
				.(is_numeric($a21)?"'$a21'":$a21);
		}
		else
			calculer_parties($boucles, $idb, $partie, $mode);
	}
}

//
// Code specifique aux criteres {pagination}, {1,n} {n/m} etc
//

function calculer_parties(&$boucles, $id_boucle, $debut, $mode){
	$total_parties = $boucles[$id_boucle]->total_parties;

	preg_match(",([+-/p])([+-/])?,", $mode, $regs);
	list(, $op1, $op2) = array_pad($regs, 3, null);
	$nombre_boucle = "\$Numrows['$id_boucle']['total']";
	// {1/3}
	if ($op1=='/'){
		$pmoins1 = is_numeric($debut) ? ($debut-1) : "($debut-1)";
		$totpos = is_numeric($total_parties) ? ($total_parties) :
			"($total_parties ? $total_parties : 1)";
		$fin = "ceil(($nombre_boucle * $debut )/$totpos) - 1";
		$debut = !$pmoins1 ? 0 : "ceil(($nombre_boucle * $pmoins1)/$totpos);";
	} else {
		// cas {n-1,x}
		if ($op1=='-') $debut = "$nombre_boucle - $debut;";

		// cas {x,n-1}
		if ($op2=='-'){
			$fin = '$debut_boucle + '.$nombre_boucle.' - '
			       .(is_numeric($total_parties) ? ($total_parties+1) :
					($total_parties.' - 1'));
		} else {
			// {x,1} ou {pagination}
			$fin = '$debut_boucle'
			       .(is_numeric($total_parties) ?
					(($total_parties==1) ? "" : (' + '.($total_parties-1))) :
					('+'.$total_parties.' - 1'));
		}

		// {pagination}, gerer le debut_xx=-1 pour tout voir
		if ($op1=='p'){
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
	                                    .'$debut_boucle = '.$debut.";\n	"
	                                    .'$fin_boucle = min('.$fin.", \$Numrows['$id_boucle']['total'] - 1);\n	"
	                                    .'$Numrows[\''.$id_boucle."']['grand_total'] = \$Numrows['$id_boucle']['total'];\n	"
	                                    .'$Numrows[\''.$id_boucle.'\']["total"] = max(0,$fin_boucle - $debut_boucle + 1);'
	                                    ."\n\tif (\$debut_boucle>0 AND \$debut_boucle < \$Numrows['$id_boucle']['grand_total'] AND \$iter->seek(\$debut_boucle,'continue'))\n\t\t\$Numrows['$id_boucle']['compteur_boucle'] = \$debut_boucle;\n\t";

	$boucles[$id_boucle]->partie = "
		if (\$Numrows['$id_boucle']['compteur_boucle'] <= \$debut_boucle) continue;
		if (\$Numrows['$id_boucle']['compteur_boucle']-1 > \$fin_boucle) break;";
}

// http://doc.spip.org/@calculer_critere_parties_aux
function calculer_critere_parties_aux($idb, &$boucles, $param){
	if ($param[0]->type!='texte'){
		$a1 = calculer_liste(array($param[0]), array('id_mere' => $idb), $boucles, $boucles[$idb]->id_parent);
		preg_match(',^ *(-([0-9]+))? *$,', $param[1]->texte, $m);
		return array("intval($a1)", ($m[2] ? $m[2] : 0));
	} else {
		preg_match(',^ *(([0-9]+)|n) *(- *([0-9]+)? *)?$,', $param[0]->texte, $m);
		$a1 = $m[1];
		if (!@$m[3])
			return array($a1, 0);
		elseif ($m[4])
			return array($a1, $m[4]);
		else return array($a1,
		                  calculer_liste(array($param[1]), array(), $boucles[$idb]->id_parent, $boucles));
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
 * - critere_defaut, sinon avec _dist
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
function calculer_criteres($idb, &$boucles){
	$msg = '';
	$boucle = $boucles[$idb];
	$table = strtoupper($boucle->type_requete);
	$serveur = strtolower($boucle->sql_serveur);

	$defaut = charger_fonction('DEFAUT', 'calculer_critere');
	// s'il y avait une erreur de syntaxe, propager cette info
	if (!is_array($boucle->criteres)) return array();

	foreach ($boucle->criteres as $crit){
		$critere = $crit->op;
		// critere personnalise ?
		if (
			(!$serveur OR
			 ((!function_exists($f = "critere_".$serveur."_".$table."_".$critere))
			  AND (!function_exists($f = $f."_dist"))
			      AND (!function_exists($f = "critere_".$serveur."_".$critere))
			          AND (!function_exists($f = $f."_dist"))
			 )
			)
			AND (!function_exists($f = "critere_".$table."_".$critere))
			    AND (!function_exists($f = $f."_dist"))
			        AND (!function_exists($f = "critere_".$critere))
			            AND (!function_exists($f = $f."_dist"))
		){
			// fonction critere standard 
			$f = $defaut;
		}
		// compile le critere
		$res = $f($idb, $boucles, $crit);

		// Gestion centralisee des erreurs pour pouvoir propager
		if (is_array($res)){
			$msg = $res;
			erreur_squelette($msg, $boucle);
		}
	}
	return $msg;
}

/**
 * Madeleine de Proust, revision MIT-1958 sqq, revision CERN-1989
 * hum, c'est kwoi cette fonxion ? on va dire qu'elle desemberlificote les guillemets...
 *
 * http://doc.spip.org/@kwote
 *
 * @param string $lisp
 * @param string $serveur
 * @param string $type
 * @return string
 */
function kwote($lisp, $serveur='', $type=''){
	if (preg_match(_CODE_QUOTE, $lisp, $r))
		return $r[1]."\"".sql_quote(str_replace(array("\\'", "\\\\"), array("'", "\\"), $r[2]),$serveur,$type)."\"";
	else
		return "sql_quote($lisp)";
}

// Si on a une liste de valeurs dans #ENV{x}, utiliser la double etoile
// pour faire par exemple {id_article IN #ENV**{liste_articles}}
// http://doc.spip.org/@critere_IN_dist
function critere_IN_dist($idb, &$boucles, $crit){
	$r = calculer_critere_infixe($idb, $boucles, $crit);
	if (!$r){
		return (array('zbug_critere_inconnu', array('critere' => $crit->op." ?")));
	}
	list($arg, $op, $val, $col, $where_complement) = $r;

	$in = critere_IN_cas($idb, $boucles, $crit->not ? 'NOT' : ($crit->exclus ? 'exclus' : ''), $arg, $op, $val, $col);

	//	inserer la condition; exemple: {id_mot ?IN (66, 62, 64)}
	$where = $in;
	if ($crit->cond){
		$pred = calculer_argument_precedent($idb, $col, $boucles);
		$where = array("'?'", $pred, $where, "''");
		if ($where_complement) // condition annexe du type "AND (objet='article')"
			$where_complement = array("'?'", $pred, $where_complement, "''");
	}
	if ($crit->exclus)
		if (!preg_match(",^L[0-9]+[.],", $arg))
			$where = array("'NOT'", $where);
		else
			// un not sur un critere de jointure se traduit comme un NOT IN avec une sous requete
			// c'est une sous requete identique a la requete principale sous la forme (SELF,$select,$where) avec $select et $where qui surchargent
			$where = array("'NOT'", array("'IN'", "'".$boucles[$idb]->id_table.".".$boucles[$idb]->primary."'", array("'SELF'", "'".$boucles[$idb]->id_table.".".$boucles[$idb]->primary."'", $where)));

	$boucles[$idb]->where[] = $where;
	if ($where_complement) // condition annexe du type "AND (objet='article')"
		$boucles[$idb]->where[] = $where_complement;
}

// http://doc.spip.org/@critere_IN_cas
function critere_IN_cas($idb, &$boucles, $crit2, $arg, $op, $val, $col){
	static $num = array();
	$descr = $boucles[$idb]->descr;
	$cpt = &$num[$descr['nom']][$descr['gram']][$idb];

	$var = '$in'.$cpt++;
	$x = "\n\t$var = array();";
	foreach ($val as $k => $v){
		if (preg_match(",^(\n//.*\n)?'(.*)'$,", $v, $r)){
			// optimiser le traitement des constantes
			if (is_numeric($r[2]))
				$x .= "\n\t$var"."[]= $r[2];";
			else
				$x .= "\n\t$var"."[]= ".sql_quote($r[2]).";";
		} else {
			// Pour permettre de passer des tableaux de valeurs
			// on repere l'utilisation brute de #ENV**{X},
			// c'est-a-dire sa  traduction en ($PILE[0][X]).
			// et on deballe mais en rajoutant l'anti XSS
			$x .= "\n\tif (!(is_array(\$a = ($v))))\n\t\t$var"."[]= \$a;\n\telse $var = array_merge($var, \$a);";
		}
	}

	$boucles[$idb]->in .= $x;

	// inserer le tri par defaut selon les ordres du IN ... 
	// avec une ecriture de type FIELD qui degrade les performances (du meme ordre qu'un rexgexp)
	// et que l'on limite donc strictement aux cas necessaires :
	// si ce n'est pas un !IN, et si il n'y a pas d'autre order dans la boucle
	if (!$crit2){
		$boucles[$idb]->default_order[] = "((!sql_quote($var) OR sql_quote($var)===\"''\") ? 0 : ('FIELD($arg,' . sql_quote($var) . ')'))";
	}

	return "sql_in('$arg',sql_quote($var)".($crit2=='NOT' ? ",'NOT'" : "").")";
}

/**
 * {where}
 * tout simplement, pour faire le pont entre php et squelettes
 *
 * @param <type> $idb
 * @param <type> $boucles
 * @param <type> $crit
 */
function critere_where_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];
	if (isset($crit->param[0]))
		$_where = calculer_liste($crit->param[0], array(), $boucles, $boucle->id_parent);
	else
		$_where = '@$Pile[0]["where"]';

	if ($crit->cond)
		$_where = "(($_where) ? ($_where) : '')";

	if ($crit->not)
		$_where = "array('NOT',$_where)";

	$boucle->where[] = $_where;
}


/**
 * Un critere pour gerer un champ de tri qui peut etre modifie dynamiquement
 * par la balise #TRI
 *
 * {tri [champ_par_defaut][,sens_par_defaut][,nom_variable]}
 * champ_par_defaut : un champ de la table sql
 * sens_par_defaut : -1 ou inverse pour decroissant, 1 ou direct pour croissant
 *   peut etre un tableau pour preciser des sens par defaut associes a chaque champ
 *   exemple : array('titre'=>1,'date'=>-1) pour trier par defaut
 *   les titre croissant et les dates decroissantes
 *   dans ce cas, quand un champ est utilise pour le tri et n'est pas present dans le tableau
 *   c'est la premiere valeur qui est utilisee
 * nom_variable : nom de la variable utilisee (par defaut tri_nomboucle)
 *
 * {tri titre}
 * {tri titre,inverse}
 * {tri titre,-1}
 * {tri titre,-1,truc}
 *
 * le critere {tri} s'utilise conjointement avec la balise #TRI dans la meme boucle
 * pour generer les liens qui permettent de changer le critere de tri et le sens du tri
 *
 * Exemple d'utilisation
 *
 * <B_articles>
 * <p>#TRI{titre,'Trier par titre'} | #TRI{date,'Trier par date'}</p>
 * <ul>
 * <BOUCLE_articles(ARTICLES){tri titre}>
 *	<li>#TITRE - [(#DATE|affdate_jourcourt)]</li>
 * </BOUCLE_articles>
 * </ul>
 * </B_articles>
 *
 * NB :
 * contraitement a {par ...} {tri} ne peut prendre qu'un seul champ,
 * mais il peut etre complete avec {par ...} pour indiquer des criteres secondaires
 *
 * ex :
 * {tri num titre}{par titre} permet de faire un tri sur le rang (modifiable dynamiquement)
 * avec un second critere sur le titre en cas d'egalite des rang
 *
 * @param unknown_type $idb
 * @param unknown_type $boucles
 * @param unknown_type $crit
 */
function critere_tri_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];

	// definition du champ par defaut
	$_champ_defaut = !isset($crit->param[0][0]) ? "''"
		: calculer_liste(array($crit->param[0][0]), array(), $boucles, $boucle->id_parent);
	$_sens_defaut = !isset($crit->param[1][0]) ? "1"
		: calculer_liste(array($crit->param[1][0]), array(), $boucles, $boucle->id_parent);
	$_variable = !isset($crit->param[2][0]) ? "'$idb'"
		: calculer_liste(array($crit->param[2][0]), array(), $boucles, $boucle->id_parent);

	$_tri = "((\$t=(isset(\$Pile[0]['tri'.$_variable]))?\$Pile[0]['tri'.$_variable]:$_champ_defaut)?tri_protege_champ(\$t):'')";

	$_sens_defaut = "(is_array(\$s=$_sens_defaut)?(isset(\$s[\$st=$_tri])?\$s[\$st]:reset(\$s)):\$s)";
	$_sens = "((intval(\$t=(isset(\$Pile[0]['sens'.$_variable]))?\$Pile[0]['sens'.$_variable]:$_sens_defaut)==-1 OR \$t=='inverse')?-1:1)";

	$boucle->modificateur['tri_champ'] = $_tri;
	$boucle->modificateur['tri_sens'] = $_sens;
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
	$boucle->select[] = "\".tri_champ_select(\$tri).\"";
	$boucle->order[] = "tri_champ_order(\$tri,\$command['from']).\$senstri";
}

# Criteres de comparaison

// http://doc.spip.org/@calculer_critere_DEFAUT
function calculer_critere_DEFAUT_dist($idb, &$boucles, $crit){
	// double cas particulier {0,1} et {1/2} repere a l'analyse lexicale
	if (($crit->op==",") OR ($crit->op=='/'))
		return calculer_critere_parties($idb, $boucles, $crit);

	$r = calculer_critere_infixe($idb, $boucles, $crit);
	if (!$r){
	#	// on produit une erreur seulement si le critere n'a pas de '?'
	#	if (!$crit->cond) {
			return (array('zbug_critere_inconnu', array('critere' => $crit->op)));
	#	}
	} else calculer_critere_DEFAUT_args($idb, $boucles, $crit, $r);
}

function calculer_critere_DEFAUT_args($idb, &$boucles, $crit, $args){
	list($arg, $op, $val, $col, $where_complement) = $args;

	$where = array("'$op'", "'$arg'", $val[0]);

	// inserer la negation (cf !...)

	if ($crit->not) $where = array("'NOT'", $where);
	if ($crit->exclus)
		if (!preg_match(",^L[0-9]+[.],", $arg))
			$where = array("'NOT'", $where);
		else
			// un not sur un critere de jointure se traduit comme un NOT IN avec une sous requete
			// c'est une sous requete identique a la requete principale sous la forme (SELF,$select,$where) avec $select et $where qui surchargent
			$where = array("'NOT'", array("'IN'", "'".$boucles[$idb]->id_table.".".$boucles[$idb]->primary."'", array("'SELF'", "'".$boucles[$idb]->id_table.".".$boucles[$idb]->primary."'", $where)));

	// inserer la condition (cf {lang?})
	// traiter a part la date, elle est mise d'office par SPIP,
	if ($crit->cond){
		$pred = calculer_argument_precedent($idb, $col, $boucles);
		if ($col=="date" OR $col=="date_redac"){
			if ($pred=="\$Pile[0]['".$col."']"){
				$pred = "(\$Pile[0]['{$col}_default']?'':$pred)";
			}
		}

		if ($op=='=' AND !$crit->not)
			$where = array("'?'", "(is_array($pred))",
			               critere_IN_cas($idb, $boucles, 'COND', $arg, $op, array($pred), $col),
			               $where);
		$where = array("'?'", "!(is_array($pred)?count($pred):strlen($pred))", "''", $where);
		if ($where_complement) // condition annexe du type "AND (objet='article')"
			$where_complement = array("'?'", "!(is_array($pred)?count($pred):strlen($pred))", "''", $where_complement);
	}

	$boucles[$idb]->where[] = $where;
	if ($where_complement) // condition annexe du type "AND (objet='article')"
		$boucles[$idb]->where[] = $where_complement;
}

// http://doc.spip.org/@calculer_critere_infixe
function calculer_critere_infixe($idb, &$boucles, $crit){

	global $table_criteres_infixes;
	global $exceptions_des_jointures, $exceptions_des_tables;

	$boucle = &$boucles[$idb];
	$type = $boucle->type_requete;
	$table = $boucle->id_table;
	$desc = $boucle->show;
	$col_vraie = null;

	list($fct, $col, $op, $val, $args_sql) =
		calculer_critere_infixe_ops($idb, $boucles, $crit);

	$col_alias = $col;
	$where_complement = false;

	// Cas particulier : id_enfant => utiliser la colonne id_objet
	if ($col=='id_enfant')
		$col = $boucle->primary;

	// Cas particulier : id_parent => verifier les exceptions de tables
	if (in_array($col,array('id_parent','id_secteur'))
	  AND isset($exceptions_des_tables[$table][$col]))
		$col = $exceptions_des_tables[$table][$col];

	// et possibilite de gerer un critere secteur sur des tables de plugins (ie forums)
	else if (($col=='id_secteur') AND ($critere_secteur = charger_fonction("critere_secteur_$type", "public", true))){
		$table = $critere_secteur($idb, $boucles, $val, $crit);
	}

		// cas id_article=xx qui se mappe en id_objet=xx AND objet=article
		// sauf si exception declaree : sauter cette etape
	else if (
		!isset($exceptions_des_jointures[table_objet_sql($table)][$col])
		AND !isset($exceptions_des_jointures[$col])
		    AND count(trouver_champs_decomposes($col, $desc))>1
	){
		$e = decompose_champ_id_objet($col);
		$col = array_shift($e);
		$where_complement = primary_doublee($e, $table);
	}
		// Cas particulier : expressions de date
	else if ($c = calculer_critere_infixe_date($idb, $boucles, $col)){
		list($col,$col_vraie) = $c;
		$table = '';
	}
	else if (preg_match('/^(.*)\.(.*)$/', $col, $r)){
		list(, $table, $col) = $r;
		$col_alias = $col;

		$trouver_table = charger_fonction('trouver_table','base');
		if ($desc = $trouver_table($table, $boucle->sql_serveur)
		  AND isset($desc['field'][$col])
		  AND $cle = array_search($desc['table'],$boucle->from))
			$table = $cle;
		else {
			$table = trouver_jointure_champ($col, $boucle, array($table), ($crit->cond OR $op!='='));
		}
		#$table = calculer_critere_externe_init($boucle, array($table), $col, $desc, ($crit->cond OR $op!='='), true);
		if (!$table) return '';
	}
	elseif (@!array_key_exists($col, $desc['field'])
	         // Champ joker * des iterateurs DATA qui accepte tout
	         AND @!array_key_exists('*', $desc['field'])
	) {
		$r = calculer_critere_infixe_externe($boucle, $crit, $op, $desc, $col, $col_alias, $table);
		if (!$r) return '';
		list($col, $col_alias, $table, $where_complement, $desc) = $r;
	}

	$col_vraie = ($col_vraie?$col_vraie:$col);
	// Dans tous les cas,
	// virer les guillemets eventuels autour d'un int (qui sont refuses par certains SQL) et passer dans sql_quote avec le type si connu
	// et int sinon si la valeur est numerique
	// sinon introduire le vrai type du champ si connu dans le sql_quote (ou int NOT NULL sinon)
	// Ne pas utiliser intval, PHP tronquant les Bigint de SQL
	if ($op=='=' OR in_array($op, $table_criteres_infixes)){

		// defaire le quote des int et les passer dans sql_quote avec le bon type de champ si on le connait, int sinon
		// prendre en compte le debug ou la valeur arrive avec un commentaire PHP en debut
		if (preg_match(",^\\A(\s*//.*?$\s*)?\"'(-?\d+)'\"\\z,ms", $val[0], $r))
			$val[0] = $r[1].'"'.sql_quote($r[2],$boucle->sql_serveur,(isset($desc['field'][$col_vraie])?$desc['field'][$col_vraie]:'int NOT NULL')).'"';

		// sinon expliciter les
		// sql_quote(truc) en sql_quote(truc,'',type)
		// sql_quote(truc,serveur) en sql_quote(truc,serveur,type)
		// sans toucher aux
		// sql_quote(truc,'','varchar(10) DEFAULT \'oui\' COLLATE NOCASE')
		// sql_quote(truc,'','varchar')
		elseif (preg_match('/\Asql_quote[(](.*?)(,[^)]*?)?(,[^)]*(?:\(\d+\)[^)]*)?)?[)]\s*\z/ms', $val[0], $r)
			// si pas deja un type
		  AND (!isset($r[3]) OR !$r[3])) {
			$r = $r[1]
				.((isset($r[2]) AND $r[2]) ? $r[2] : ",''")
				.",'".(isset($desc['field'][$col_vraie])?addslashes($desc['field'][$col_vraie]):'int NOT NULL')."'";
			$val[0] = "sql_quote($r)";
		}
	}
	// Indicateur pour permettre aux fonctionx boucle_X de modifier 
	// leurs requetes par defaut, notamment le champ statut
	// Ne pas confondre champs de la table principale et des jointures
	if ($table===$boucle->id_table){
		$boucles[$idb]->modificateur['criteres'][$col_vraie] = true;
		if ($col_alias!=$col_vraie)
			$boucles[$idb]->modificateur['criteres'][$col_alias] = true;
	}

	// ajout pour le cas special d'une condition sur le champ statut:
	// il faut alors interdire a la fonction de boucle
	// de mettre ses propres criteres de statut
	// http://www.spip.net/@statut (a documenter)
	// garde pour compatibilite avec code des plugins anterieurs, mais redondant avec la ligne precedente
	if ($col=='statut') $boucles[$idb]->statut = true;

	// inserer le nom de la table SQL devant le nom du champ
	if ($table){
		if ($col[0]=="`")
			$arg = "$table.".substr($col, 1, -1);
		else $arg = "$table.$col";
	} else $arg = $col;

	// inserer la fonction SQL
	if ($fct) $arg = "$fct($arg$args_sql)";

	return array($arg, $op, $val, $col_alias, $where_complement);
}

function calculer_critere_infixe_externe($boucle, $crit, $op, $desc, $col, $col_alias, $table){
	global $exceptions_des_jointures;
	$where = '';

	$calculer_critere_externe = 'calculer_critere_externe_init';
	// gestion par les plugins des jointures tordues 
	// pas automatiques mais necessaires
	$table_sql = table_objet_sql($table);
	if (isset($exceptions_des_jointures[$table_sql])
	  AND is_array($exceptions_des_jointures[$table_sql])
	  AND
			(
			isset($exceptions_des_jointures[$table_sql][$col])
			OR
			isset($exceptions_des_jointures[$table_sql][''])
			)
		){
		$t = $exceptions_des_jointures[$table_sql];
		$index = isset($t[$col])
			? $t[$col] : (isset($t['']) ? $t[''] : array());

		if (count($index)==3)
			list($t, $col, $calculer_critere_externe) = $index;
		elseif (count($index)==2) {
			list($t, $col) = $t[$col];
		}
		elseif (count($index)==1) {
			list($calculer_critere_externe) = $index;
			$t = $table;
		}
		else
			$t = ''; // jointure non declaree. La trouver.
	}
	elseif (isset($exceptions_des_jointures[$col]))
		list($t, $col) = $exceptions_des_jointures[$col];
	else
		$t = ''; // jointure non declaree. La trouver.

	// ici on construit le from pour fournir $col en piochant dans les jointures

	// si des jointures explicites sont fournies, on cherche d'abord dans celles ci
	// permet de forcer une table de lien quand il y a ambiguite
	// <BOUCLE_(DOCUMENTS documents_liens){id_mot}>
	// alors que <BOUCLE_(DOCUMENTS){id_mot}> produit la meme chose que <BOUCLE_(DOCUMENTS mots_liens){id_mot}>
	$table = "";
	if ($boucle->jointures_explicites){
		$jointures_explicites = explode(' ', $boucle->jointures_explicites);
		$table = $calculer_critere_externe($boucle, $jointures_explicites, $col, $desc, ($crit->cond OR $op!='='), $t);
	}

	// et sinon on cherche parmi toutes les jointures declarees
	if (!$table) {
		$table = $calculer_critere_externe($boucle, $boucle->jointures, $col, $desc, ($crit->cond OR $op!='='), $t);
	}

	if (!$table) return '';

	// il ne reste plus qu'a trouver le champ dans les from
	list($nom, $desc) = trouver_champ_exterieur($col, $boucle->from, $boucle);

	if (count(trouver_champs_decomposes($col, $desc))>1){
		$col_alias = $col; // id_article devient juste le nom d'origine
		$e = decompose_champ_id_objet($col);
		$col = array_shift($e);
		$where = primary_doublee($e, $table);
	}

	return array($col, $col_alias, $table, $where, $desc);
}

// Ne pas appliquer sql_quote lors de la compilation,
// car on ne connait pas le serveur SQL, donc s'il faut \' ou ''

// http://doc.spip.org/@primary_doublee
function primary_doublee($decompose, $table){
	$e1 = reset($decompose);
	$e2 = "sql_quote('".end($decompose)."')";
	return array("'='", "'$table.".$e1."'", $e2);
}

/**
 * Champ hors table, ca ne peut etre qu'une jointure.
 * On cherche la table du champ et on regarde si elle est deja jointe
 * Si oui et qu'on y cherche un champ nouveau, pas de jointure supplementaire
 * Exemple: criteres {titre_mot=...}{type_mot=...}
 * Dans les 2 autres cas ==> jointure
 * (Exemple: criteres {type_mot=...}{type_mot=...} donne 2 jointures
 * pour selectioner ce qui a exactement ces 2 mots-cles.
 *
 * http://doc.spip.org/@calculer_critere_externe_init
 *
 * @param  $boucle
 * @param  $joints
 * @param  $col
 * @param  $desc
 * @param  $cond
 * @param bool|string $checkarrivee
 * @return mixed|string
 */
function calculer_critere_externe_init(&$boucle, $joints, $col, $desc, $cond, $checkarrivee = false){
	// si on demande un truc du genre spip_mots
	// avec aussi spip_mots_liens dans les jointures dispo
	// et qu'on est la
	// il faut privilegier la jointure directe en 2 etapes spip_mots_liens, spip_mots
	if ($checkarrivee
		AND is_string($checkarrivee)
	    AND $a = table_objet($checkarrivee)
	        AND in_array($a.'_liens', $joints)
	){
		if ($res = calculer_lien_externe_init($boucle, $joints, $col, $desc, $cond, $checkarrivee)) {
			return $res;
		}
	}
	foreach ($joints as $joint){
		if ($arrivee = trouver_champ_exterieur($col, array($joint), $boucle, $checkarrivee)){
			$t = array_search($arrivee[0], $boucle->from);
			// transformer eventuellement id_xx en (id_objet,objet)
			$cols = trouver_champs_decomposes($col, $arrivee[1]);
			if ($t){
				$joindre = false;
				foreach ($cols as $col){
					$c = '/\b'.$t.".$col".'\b/';
					if (trouver_champ($c, $boucle->where)) $joindre = true;
					else {
						// mais ca peut etre dans le FIELD pour le Having
						$c = "/FIELD.$t".".$col,/";
						if (trouver_champ($c, $boucle->select)) $joindre = true;
					}
				}
				if (!$joindre) return $t;
			}
			if ($res = calculer_jointure($boucle, array($boucle->id_table, $desc), $arrivee, $cols, $cond, 1)) {
				return $res;
			}
		}
	}
	return '';

}

/**
 * Generer directement une jointure via une table de lien spip_xxx_liens
 * pour un critere {id_xxx}
 * @param  $boucle
 * @param  $joints
 * @param  $col
 * @param  $desc
 * @param  $cond
 * @param bool $checkarrivee
 * @return string
 */
function calculer_lien_externe_init(&$boucle, $joints, $col, $desc, $cond, $checkarrivee = false){
	$primary_arrivee = id_table_objet($checkarrivee);

	$intermediaire = trouver_champ_exterieur($primary_arrivee, $joints, $boucle, $checkarrivee."_liens");
	$arrivee = trouver_champ_exterieur($col, $joints, $boucle, $checkarrivee);

	if (!$intermediaire OR !$arrivee) return '';

	$res = fabrique_jointures($boucle,
	                          array(
	                               array($boucle->id_table, $intermediaire, array(id_table_objet($desc['table_objet']), 'id_objet', 'objet', $desc['type'])),
	                               array(reset($intermediaire), $arrivee, $primary_arrivee)
	                          )
		, $cond, $desc, $boucle->id_table, array($col));
	return $res;
}


// http://doc.spip.org/@trouver_champ
function trouver_champ($champ, $where){
	if (!is_array($where))
		return preg_match($champ, $where);
	else {
		foreach ($where as $clause){
			if (trouver_champ($champ, $clause)) return true;
		}
		return false;
	}
}


// determine l'operateur et les operandes

// http://doc.spip.org/@calculer_critere_infixe_ops
function calculer_critere_infixe_ops($idb, &$boucles, $crit){
	// cas d'une valeur comparee a elle-meme ou son referent
	if (count($crit->param)==0){
		$op = '=';
		$col = $val = $crit->op;
		if (preg_match('/^(.*)\.(.*)$/', $col, $r)) $val = $r[2];
		// Cas special {lang} : aller chercher $GLOBALS['spip_lang']
		if ($val=='lang')
			$val = array(kwote('$GLOBALS[\'spip_lang\']'));
		else {
			$defaut = null;
			if ($val=='id_parent') {
				// Si id_parent, comparer l'id_parent avec l'id_objet
				// de la boucle superieure.... faudrait verifier qu'il existe
				// pour eviter l'erreur SQL
				$val = $boucles[$idb]->primary;
				// mais si pas de boucle superieure, prendre id_parent dans l'env
				$defaut = "\$Pile[0]['id_parent']";
			}
			elseif ($val=='id_enfant'){
				// Si id_enfant, comparer l'id_objet avec l'id_parent
				// de la boucle superieure
				$val = 'id_parent';
			}
			elseif ($crit->cond AND ($col=="date" OR $col=="date_redac")){
				// un critere conditionnel sur date est traite a part
				// car la date est mise d'office par SPIP,
				$defaut = "(\$Pile[0]['{$col}_default']?'':\$Pile[0]['".$col."'])";
			}

			$val = calculer_argument_precedent($idb, $val, $boucles, $defaut);
			$val = array(kwote($val));
		}
	} else {
		// comparaison explicite
		// le phraseur impose que le premier param soit du texte
		$params = $crit->param;
		$op = $crit->op;
		if ($op=='==') $op = 'REGEXP';
		$col = array_shift($params);
		$col = $col[0]->texte;

		$val = array();
		$desc = array('id_mere' => $idb);
		$parent = $boucles[$idb]->id_parent;

		// Dans le cas {x=='#DATE'} etc, defaire le travail du phraseur,
		// celui ne sachant pas ce qu'est un critere infixe
		// et a fortiori son 2e operande qu'entoure " ou '
		if (count($params)==1
		    AND count($params[0]==3)
		        AND $params[0][0]->type=='texte'
		            AND @$params[0][2]->type=='texte'
		                 AND ($p = $params[0][0]->texte)==$params[0][2]->texte
		                     AND (($p=="'") OR ($p=='"'))
		                         AND $params[0][1]->type=='champ'
		){
			$val[] = "$p\\$p#".$params[0][1]->nom_champ."\\$p$p";
		} else
			foreach ((($op!='IN') ? $params : calculer_vieux_in($params)) as $p){
				$a = calculer_liste($p, $desc, $boucles, $parent);
				if (strcasecmp($op,'IN')==0) $val[] = $a;
				else $val[] = kwote($a, $boucles[$idb]->sql_serveur, 'char'); // toujours quoter en char ici
			}
	}

	$fct = $args_sql = '';
	// fonction SQL ?
	if (preg_match('/^(.*)'.SQL_ARGS.'$/', $col, $m)){
		$fct = $m[1];
		preg_match('/^\(([^,]*)(.*)\)$/', $m[2], $a);
		$col = $a[1];
		if (preg_match('/^(\S*)(\s+AS\s+.*)$/i', $col, $m)){
			$col = $m[1];
			$args_sql = $m[2];
		}
		$args_sql .= $a[2];
		;
	}

	return array($fct, $col, $op, $val, $args_sql);
}

// compatibilite ancienne version

// http://doc.spip.org/@calculer_vieux_in
function calculer_vieux_in($params){
	$deb = $params[0][0];
	$k = count($params)-1;
	$last = $params[$k];
	$j = count($last)-1;
	$last = $last[$j];
	$n = isset($last->texte) ? strlen($last->texte) : 0;

	if (!((isset($deb->texte[0])     AND $deb->texte[0]=='(')
	   && (isset($last->texte[$n-1]) AND $last->texte[$n-1]==')')))
		return $params;
	$params[0][0]->texte = substr($deb->texte, 1);
	// attention, on peut avoir k=0,j=0 ==> recalculer
	$last = $params[$k][$j];
	$n = strlen($last->texte);
	$params[$k][$j]->texte = substr($last->texte, 0, $n-1);
	$newp = array();
	foreach ($params as $v){
		if ($v[0]->type!='texte')
			$newp[] = $v;
		else {
			foreach (explode(',', $v[0]->texte) as $x){
				$t = new Texte;
				$t->texte = $x;
				$newp[] = array($t);
			}
		}
	}
	return $newp;
}

// http://doc.spip.org/@calculer_critere_infixe_date
function calculer_critere_infixe_date($idb, &$boucles, $col){
	if (!preg_match(",^((age|jour|mois|annee)_relatif|date|mois|annee|jour|heure|age)(_[a-z]+)?$,", $col, $regs)) return '';
	$boucle = $boucles[$idb];
	$table = $boucle->show;
	// si c'est une colonne de la table, ne rien faire
	if(isset($table['field'][$col])) return '';

	if (!$table['date'] && !isset($GLOBALS['table_date'][$table['id_table']])) return '';
	$pred = $date_orig = isset($GLOBALS['table_date'][$table['id_table']])? $GLOBALS['table_date'][$table['id_table']] : $table['date'];
	$col = $regs[1];
	if (isset($regs[3]) AND $suite = $regs[3]){
		# Recherche de l'existence du champ date_xxxx,
		# si oui choisir ce champ, sinon choisir xxxx

		if (isset($table['field']["date$suite"]))
			$date_orig = 'date'.$suite;
		else
			$date_orig = substr($suite, 1);
		$pred = $date_orig;
	}
	else
		if (isset($regs[2]) AND $rel = $regs[2]) $pred = 'date';

	$date_compare = "\"' . normaliser_date(".
	                calculer_argument_precedent($idb, $pred, $boucles).
	                ") . '\"";

	$col_vraie = $date_orig;
	$date_orig = $boucle->id_table.'.'.$date_orig;

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
			$col = calculer_param_date("NOW()", $date_orig);
			$col_vraie = "";// comparer a un int (par defaut)
			break;
		case 'age_relatif':
			$col = calculer_param_date($date_compare, $date_orig);
			$col_vraie = "";// comparer a un int (par defaut)
			break;
		case 'jour_relatif':
			$col = "(TO_DAYS(".$date_compare.")-TO_DAYS(".$date_orig."))";
			$col_vraie = "";// comparer a un int (par defaut)
			break;
		case 'mois_relatif':
			$col = "MONTH(".$date_compare.")-MONTH(".
			       $date_orig.")+12*(YEAR(".$date_compare.
			       ")-YEAR(".$date_orig."))";
			$col_vraie = "";// comparer a un int (par defaut)
			break;
		case 'annee_relatif':
			$col = "YEAR(".$date_compare.")-YEAR(".
			       $date_orig.")";
			$col_vraie = "";// comparer a un int (par defaut)
			break;
	}
	return array($col,$col_vraie);
}

// http://doc.spip.org/@calculer_param_date
function calculer_param_date($date_compare, $date_orig){
	if (preg_match(",'\" *\.(.*)\. *\"',", $date_compare, $r)){
		$init = "'\" . (\$x = $r[1]) . \"'";
		$date_compare = '\'$x\'';
	}
	else
		$init = $date_compare;

	return
		"LEAST((UNIX_TIMESTAMP(".
		$init.
		")-UNIX_TIMESTAMP(".
		$date_orig.
		"))/86400,\n\tTO_DAYS(".
		$date_compare.
		")-TO_DAYS(".
		$date_orig.
		"),\n\tDAYOFMONTH(".
		$date_compare.
		")-DAYOFMONTH(".
		$date_orig.
		")+30.4368*(MONTH(".
		$date_compare.
		")-MONTH(".
		$date_orig.
		"))+365.2422*(YEAR(".
		$date_compare.
		")-YEAR(".
		$date_orig.
		")))";
}

/**
 * (DATA){source mode, "xxxxxx", arg, arg, arg}
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DATA_source_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];

	$args = array();
	foreach ($crit->param as &$param)
		array_push($args,
		           calculer_liste($param, array(), $boucles, $boucles[$idb]->id_parent));

	$boucle->hash .= '
	$command[\'sourcemode\'] = '.array_shift($args).";\n";

	$boucle->hash .= '
	$command[\'source\'] = array('.join(', ', $args).");\n";
}


/**
 * (DATA){datasource "xxxxxx", mode}  <= deprecated
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DATA_datasource_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];
	$boucle->hash .= '
	$command[\'source\'] = array('.calculer_liste($crit->param[0], array(), $boucles, $boucles[$idb]->id_parent).');
	$command[\'sourcemode\'] = '.calculer_liste($crit->param[1], array(), $boucles, $boucles[$idb]->id_parent).';';
}


/**
 * (DATA){datacache}
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DATA_datacache_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];
	$boucle->hash .= '
	$command[\'datacache\'] = '.calculer_liste($crit->param[0], array(), $boucles, $boucles[$idb]->id_parent).';';
}


/**
 * Pour passer des arguments a un iterateur non-spip
 * (php:xxxIterator){args argument1, argument2, argument3}
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_php_args_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];
	$boucle->hash .= '$command[\'args\']=array();';
	foreach ($crit->param as $param){
		$boucle->hash .= '
			$command[\'args\'][] = '.calculer_liste($param, array(), $boucles, $boucles[$idb]->id_parent).';';
	}
}

/**
 * Passer une liste de donnees a l'iterateur DATA
 * (DATA){liste X1, X2, X3}
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DATA_liste_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];
	$boucle->hash .= "\n\t".'$command[\'liste\'] = array();'."\n";
	foreach ($crit->param as $param){
		$boucle->hash .= "\t".'$command[\'liste\'][] = '.calculer_liste($param, array(), $boucles, $boucles[$idb]->id_parent).";\n";
	}
}

/**
 * Passer un enum min max a l'iterateur DATA
 * (DATA){enum Xmin, Xmax}
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DATA_enum_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];
	$boucle->hash .= "\n\t".'$command[\'enum\'] = array();'."\n";
	foreach ($crit->param as $param){
		$boucle->hash .= "\t".'$command[\'enum\'][] = '.calculer_liste($param, array(), $boucles, $boucles[$idb]->id_parent).";\n";
	}
}

/**
 * Extraire un chemin d'un tableau de donnees
 * (DATA){datapath query.results}
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DATA_datapath_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];
	foreach ($crit->param as $param){
		$boucle->hash .= '
			$command[\'datapath\'][] = '.calculer_liste($param, array(), $boucles, $boucles[$idb]->id_parent).';';
	}
}


/**
 * le critere {si ...} applicable a toutes les boucles
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_si_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];
	// il faut initialiser 1 fois le tableau a chaque appel de la boucle
	// (par exemple lorsque notre boucle est appelee dans une autre boucle)
	// mais ne pas l'initialiser n fois si il y a n criteres {si } dans la boucle !
	$boucle->hash .= "\n\tif (!isset(\$si_init)) { \$command['si'] = array(); \$si_init = true; }\n";
	if ($crit->param){
		foreach ($crit->param as $param){
			$boucle->hash .= "\t\$command['si'][] = "
					. calculer_liste($param, array(), $boucles, $boucles[$idb]->id_parent) . ";\n";
		}
		// interdire {si 0} aussi !
	} else {
		$boucle->hash .= '$command[\'si\'][] = 0;';
	}
}

/**
 * {tableau #XX} pour compatibilite ascendante boucle POUR
 * ... preferer la notation {datasource #XX,table}
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_POUR_tableau_dist($idb, &$boucles, $crit){
	$boucle = &$boucles[$idb];
	$boucle->hash .= '
	$command[\'source\'] = array('.calculer_liste($crit->param[0], array(), $boucles, $boucles[$idb]->id_parent).');
	$command[\'sourcemode\'] = \'table\';';
}


/**
 * Trouver toutes les objets qui ont des enfants (les noeuds de l'arbre)
 * {noeud}
 * {!noeud} retourne les feuilles
 *
 * @global array $exceptions_des_tables
 * @param string $idb
 * @param array $boucles
 * @param Object $crit
 */
function critere_noeud_dist($idb, &$boucles, $crit){
	global $exceptions_des_tables;
	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$primary = $boucle->primary;

	if (!$primary OR strpos($primary, ',')){
		erreur_squelette(_T('zbug_doublon_sur_table_sans_cle_primaire'), $boucle);
		return;
	}
	$table = $boucle->type_requete;
	$table_sql = table_objet_sql(objet_type($table));

	$id_parent = isset($exceptions_des_tables[$boucle->id_table]['id_parent']) ?
		$exceptions_des_tables[$boucle->id_table]['id_parent'] :
		'id_parent';

	$in = "IN";
	$where = array("'IN'", "'$boucle->id_table."."$primary'", "'('.sql_get_select('$id_parent', '$table_sql').')'");
	if ($not)
		$where = array("'NOT'", $where);

	$boucle->where[] = $where;
}

/**
 * Trouver toutes les objets qui n'ont pas d'enfants (les feuilles de l'arbre)
 * {feuille}
 * {!feuille} retourne les noeuds
 *
 * @global array $exceptions_des_tables
 * @param string $idb
 * @param array $boucles
 * @param Object $crit
 */
function critere_feuille_dist($idb, &$boucles, $crit){
	$not = $crit->not;
	$crit->not = $not ? false : true;
	critere_noeud_dist($idb, $boucles, $crit);
	$crit->not = $not;
}

?>
