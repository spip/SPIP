<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Definition des {criteres} d'une boucle
//

if (!defined("_ECRIRE_INC_VERSION")) return;


// {racine}
// http://www.spip.net/@racine
function critere_racine_dist($idb, &$boucles, $crit) {
	$not = $crit->not;
	$boucle = &$boucles[$idb];

	if ($not)
		erreur_squelette(_T('zbug_info_erreur_squelette'), $crit->op);

	$boucle->where[] = $boucle->id_table.".id_parent=0";

}

// {exclus}
// http://www.spip.net/@exclus
function critere_exclus_dist($idb, &$boucles, $crit) {
	$param = $crit->op;
	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$id = $boucle->primary;

	if ($not OR !$id)
		erreur_squelette(_T('zbug_info_erreur_squelette'), $param);

	$arg = calculer_argument_precedent($idb,$id, $boucles);
	$boucle->where[] = $boucle->id_table . '.' . $id."!='\"." . $arg . ".\"'";
}

// {doublons} ou {unique}
// http://www.spip.net/@doublons
// attention: boucle->doublons designe une variable qu'on affecte
function critere_doublons_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$boucle->where[] = '" .' .
	  "calcul_mysql_in('".$boucle->id_table . '.' . $boucle->primary .
	  "', " .
	  '"0".$doublons[' . 
	  ($crit->not ? '' : ($boucle->doublons . "[]= ")) .
	  "('" .
	  $boucle->type_requete . 
	  "' . " .
	  calculer_liste($crit->param[0], array(), $boucles, $boucles[$idb]->id_parent) .
	  ')], \'' . 
	  ($crit->not ? '' : 'NOT') .
	  "') . \"";
# la ligne suivante avait l'intention d'éviter une collecte deja faite
# mais elle fait planter une boucle a 2 critere doublons:
# {!doublons A}{doublons B}
# (de http://article.gmane.org/gmane.comp.web.spip.devel/31034)
#	if ($crit->not) $boucle->doublons = "";
}

// {lang_select}
// http://www.spip.net/@lang_select
function critere_lang_select_dist($idb, &$boucles, $crit) {
	if (!($param = $crit->param[1][0]->texte)) $param = 'oui';
	if ($crit->not)	$param = ($param=='oui') ? 'non' : 'oui';
	$boucle = &$boucles[$idb];
	$boucle->lang_select = $param;
}

// {debut_xxx}
// http://www.spip.net/@debut_
function critere_debut_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$boucle->limit = 'intval($GLOBALS["debut' .
	  $crit->param[0][0]->texte .
	  '"]) . ",' .
	  $crit->param[1][0]->texte .
	  '"' ;
}

// {recherche}
// http://www.spip.net/@recherche
function critere_recherche_dist($idb, &$boucles, $crit) {
	global $table_des_tables;
	$boucle = &$boucles[$idb];
	$t = $boucle->id_table;
	if (in_array($t,$table_des_tables))
		$t = "spip_$t";

	// Ne pas executer la requete en cas de hash vide
	$boucle->hash = '
	// RECHERCHE
	list($rech_select, $rech_where) = prepare_recherche($GLOBALS["recherche"], "'.$boucle->primary.'", "'.$boucle->id_table.'", "'.$t.'", "'.$crit->cond.'");
	';

	// Sauf si le critere est conditionnel {recherche ?}
	if (!$crit->cond)
		$boucle->hash .= '
	if ($rech_where) ';

	$t = $boucle->id_table . '.' . $boucle->primary;
	if (!in_array($t, $boucles[$idb]->select))
	  $boucle->select[]= $t; # pour postgres, neuneu ici
	$boucle->select[]= '$rech_select as points';

	// et la recherche trouve
	$boucle->where[] = '$rech_where';

}

// {traduction}
// http://www.spip.net/@traduction
//   (id_trad>0 AND id_trad=id_trad(precedent))
//    OR id_article=id_article(precedent)
function critere_traduction_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$boucle->where[] = "((".$boucle->id_table.".id_trad > 0 AND "
			. $boucle->id_table.".id_trad ='\"."
			. calculer_argument_precedent($idb, 'id_trad',
				$boucles)
			. ".\"')
		OR
			(" . $boucle->id_table.".".$boucle->primary." ='\"."
			. calculer_argument_precedent($idb, $boucle->primary,
				$boucles)
			. ".\"'))";
}

// {origine_traduction}
// http://www.spip.net/@origine_traduction
function critere_origine_traduction_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$where = $boucle->id_table.".id_trad = "
	  . $boucle->id_table . '.' . $boucle->primary;
	if ($crit->not)
		$where = "NOT($where)";
	$boucle->where[] = $where;
}


// {meme_parent}
// http://www.spip.net/@meme_parent
function critere_meme_parent_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	if ($boucle->type_requete == 'rubriques') {
			$boucle->where[] = $boucle->id_table.".id_parent='\"."
			. calculer_argument_precedent($idb, 'id_parent',
			$boucles)
			. ".\"'";
		} else if ($boucle->type_requete == 'forums') {
			$boucle->where[] = $boucle->id_table.".id_parent='\"."
			. calculer_argument_precedent($idb, 'id_parent',
			$boucles)
			. ".\"'";
			$boucle->where[] = $boucle->id_table.".id_parent > 0";
			$boucle->plat = true;
	}
}

// {branche ?}
// http://www.spip.net/@branche
function critere_branche_dist($idb, &$boucles, $crit) {
	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$c = "calcul_mysql_in('".$boucle->id_table.".id_rubrique',
		calcul_branche(" . calculer_argument_precedent($idb,
		'id_rubrique', $boucles) . "), '')";
	if (!$crit->cond)
			$where = "\". $c .\"" ;
	else
			$where = "\".("
			. calculer_argument_precedent($idb, 'id_rubrique',
			$boucles)."? $c : 1).\"";

	if ($not)
			$boucle->where[] = "NOT($where)";
	else
			$boucle->where[] = $where;
}

// Tri : {par xxxx}
// http://www.spip.net/@par
function critere_par_dist($idb, &$boucles, $crit) {
  critere_parinverse($idb, $boucles, $crit, '') ;
}

function critere_parinverse($idb, &$boucles, $crit, $sens) {
	global  $exceptions_des_jointures;
	$boucle = &$boucles[$idb];
	if ($crit->not) $sens = $sens ? "" : " . ' DESC'";

	foreach ($crit->param as $tri) {

	// tris specifies dynamiquement
	  if ($tri[0]->type != 'texte') {
	      $order = 
		calculer_liste($tri, array(), $boucles, $boucles[$idb]->id_parent);
	      $order =
		"((\$x = preg_replace(\"/\\W/\",'',$order)) ? ('$boucle->id_table.' . \$x$sens) : '')";
	  } else {
	      $par = array_shift($tri);
	      $par = $par->texte;
	// par hasard
		if ($par == 'hasard') {
		// tester si cette version de MySQL accepte la commande RAND()
		// sinon faire un gloubi-boulga maison avec de la mayonnaise.
		  if (spip_abstract_select(array("RAND()")))
			$par = "RAND()";
		  else
			$par = "MOD(".$boucle->id_table.'.'.$boucle->primary
			  ." * UNIX_TIMESTAMP(),32767) & UNIX_TIMESTAMP()";
		  $boucle->select[]= $par . " AS alea";
		  $order = "'alea'";
		}

	// par date_thread
		else if ($par == 'date_thread') {
			//date_thread est la date la plus recente d'un message dans un fil de discussion
			$boucle->select[] = "MAX(".$boucle->id_table.".".
				$GLOBALS['table_date'][$boucle->type_requete]
				.") AS date_thread";
			$boucle->group[] = $boucle->id_table.".id_thread";
			$order = "'date_thread'";
			$boucle->plat = true;
		}
	
	// par titre_mot ou type_mot voire d'autres
		else if ($m = ($exceptions_des_jointures[$par])) {
		  $order = critere_par_jointure($boucle, $m);
			 }
    // par multi champ
		else if (ereg("^multi[[:space:]]*(.*)$",$par, $m)) {
		  $texte = $boucle->id_table . '.' . trim($m[1]);
		  $boucle->select[] =  " \".creer_objet_multi('".$texte."', \$GLOBALS['spip_lang']).\"" ;
		  $order = "multi";
		}
	// par num champ(, suite)
		else if (ereg("^num[[:space:]]*(.*)$",$par, $m)) {
		  $texte = '0+' . $boucle->id_table . '.' . trim($m[1]);
		  $suite = calculer_liste($tri, array(), $boucles, $boucle->id_parent);
		  if ($suite !== "''")
		    $texte = "\" . ((\$x = $suite) ? ('$texte' . \$x) : '0')" . " . \"";
		  $as = 'num' .($boucle->order ? count($boucle->order) : "");
		  $boucle->select[] = $texte . " AS $as";
		  $order = "'$as'";
		}
		else if ($par == 'date'
		AND isset($GLOBALS['table_date'][$boucle->type_requete])) {
			$order = "'".$boucle->id_table."."
			.$GLOBALS['table_date'][$boucle->type_requete]
			."'";
		}
		// par champ. Verifier qu'ils sont presents.
		else if (ereg("^" . CHAMP_SQL_PLUS_FONC . '$', $par, $match)) {
		  if ($match[2]) $par = $match[2];
		  global $table_des_tables, $tables_des_serveurs_sql;
		  $r = $boucle->type_requete;
		  $s = $boucles[$idb]->sql_serveur;
		  if (!$s) $s = 'localhost';
		  $t = $table_des_tables[$r];
		  // pour les tables non Spip
		  if (!$t) $t = $r; else $t = "spip_$t";
		  $desc = $tables_des_serveurs_sql[$s][$t];
		  if ($desc['field'][$par])
		    $par = $boucle->id_table.".".$par;
		  // sinon, tri sur les champs synthetises (cf points)
		  $order = (!$match[2]) ? $par : ($match[1] . "($par)");
		  $order = "'$order'";
		}
		else
		  erreur_squelette(_T('zbug_info_erreur_squelette'), "{par $par} BOUCLE$idb");
	  }
	  if ($order)
	      $boucle->order[] = $order . (($order[0]=="'") ? $sens : "");
	}
}

function critere_par_jointure(&$boucle, $champ)
{
  global $table_des_tables;
  $t = array_search('spip_mots', $boucle->from);
  if (!$t) {
    $type = $boucle->type_requete;
    $nom = $table_des_tables[$type];
    list($nom, $desc) = trouver_def_table($nom ? $nom : $type, $boucle);

    $cle = trouver_champ_exterieur($champ, $boucle->jointures, $boucle);
    if ($cle) 
      $cle = calculer_jointure($boucle, array($boucle->id_table, $desc), $cle);
    if ($cle) $t = "L$cle"; // sinon erreur
  }
  return "'" . $t . '.' . $champ . "'";
}

// {inverse}
// http://www.spip.net/@inverse

function critere_inverse_dist($idb, &$boucles, $crit) {

	$boucle = &$boucles[$idb];
	// Classement par ordre inverse

	if ($crit->not || $crit->param)
		critere_parinverse($idb, $boucles, $crit, " . ' DESC'");
	else
	  {
	    $n = count($boucle->order);
	    if ($n)
	      $boucle->order[$n-1] .= " . ' DESC'";
	    else
	      $boucle->default_order[] =  ' DESC';
	  }
}

function critere_agenda_dist($idb, &$boucles, $crit)
{
	$params = $crit->param;

	if (count($params) < 1)
	      erreur_squelette(_T('zbug_info_erreur_squelette'),
			       "{agenda ?} BOUCLE$idb");

	$parent = $boucles[$idb]->id_parent;

	// les valeurs $date et $type doivent etre connus a la compilation
	// autrement dit ne pas etre des champs

	$date = array_shift($params);
	$date = $date[0]->texte;

	$type = array_shift($params);
	$type = $type[0]->texte;

	$annee = $params ? array_shift($params) : "";
	$annee = "\n" . 'sprintf("%04d", ($x = ' .
		calculer_liste($annee, array(), $boucles, $parent) .
		') ? $x : date("Y"))';

	$mois =  $params ? array_shift($params) : "";
	$mois = "\n" . 'sprintf("%02d", ($x = ' .
		calculer_liste($mois, array(), $boucles, $parent) .
		') ? $x : date("m"))';

	$jour =  $params ? array_shift($params) : "";
	$jour = "\n" . 'sprintf("%02d", ($x = ' .
		calculer_liste($jour, array(), $boucles, $parent) .
		') ? $x : date("d"))';

	$annee2 = $params ? array_shift($params) : "";
	$annee2 = "\n" . 'sprintf("%04d", ($x = ' .
		calculer_liste($annee2, array(), $boucles, $parent) .
		') ? $x : date("Y"))';

	$mois2 =  $params ? array_shift($params) : "";
	$mois2 = "\n" . 'sprintf("%02d", ($x = ' .
		calculer_liste($mois2, array(), $boucles, $parent) .
		') ? $x : date("m"))';

	$jour2 =  $params ? array_shift($params) : "";
	$jour2 = "\n" .  'sprintf("%02d", ($x = ' .
		calculer_liste($jour2, array(), $boucles, $parent) .
		') ? $x : date("d"))';

	$boucle = &$boucles[$idb];
	$date = $boucle->id_table . ".$date";
	if ($type == 'jour')
	  $boucle->where[] =  "DATE_FORMAT($date, '%Y%m%d') = '\" .  $annee . $mois . $jour .\"'";
	elseif ($type == 'mois')
	  $boucle->where[] =  "DATE_FORMAT($date, '%Y%m') = '\" .  $annee . $mois .\"'";
	elseif ($type == 'semaine')
	  $boucle->where[] = 
	  "DATE_FORMAT($date, '%Y%m%d') >= '\" . 
		date_debut_semaine($annee, $mois, $jour) . \"' AND
	  DATE_FORMAT($date, '%Y%m%d') <= '\" .
		date_fin_semaine($annee, $mois, $jour) . \"'";
	elseif (count($crit->param) > 2) 
	  $boucle->where[] = 
	  "DATE_FORMAT($date, '%Y%m%d') >= '\" . $annee . $mois . $jour .\"' AND
	  DATE_FORMAT($date, '%Y%m%d') <= '\" . $annee2 . $mois2 . $jour2 .\"'";
	// sinon on prend tout
}



function calculer_critere_parties($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$a1 = $crit->param[0];
	$a2 = $crit->param[1];
	$op = $crit->op;
	list($a11,$a12) = calculer_critere_parties_aux($idb, $boucles, $a1);
	list($a21,$a22) = calculer_critere_parties_aux($idb, $boucles, $a2);
	if (($op== ',')&&(is_numeric($a11) && (is_numeric($a21))))
	    $boucle->limit = $a11 .',' . $a21;
	else {
		$boucle->partie = ($a11 != 'n') ? $a11 : $a12;
		$boucle->total_parties =  ($a21 != 'n') ? $a21 : $a22;
		$boucle->mode_partie = (($op == '/') ? '/' :
			(($a11=='n') ? '-' : '+').(($a21=='n') ? '-' : '+'));
	}
}

function calculer_critere_parties_aux($idb, &$boucles, $param) {
	if ($param[0]->type != 'texte')
	  {
	  $a1 = calculer_liste(array($param[0]), array(), $boucles[$idb]->id_parent, $boucles);
	  ereg('^ *(-([0-9]+))? *$', $param[1]->texte, $m);
	  return array("intval($a1)", ($m[2] ? $m[2] : 0));
	  } else {
	    ereg('^ *(([0-9]+)|n) *(- *([0-9]+)? *)?$', $param[0]->texte, $m);
	    $a1 = $m[1];
	    if (!$m[3])
	      return array($a1, 0);
	    elseif ($m[4])
	      return array($a1, $m[4]);
	    else return array($a1, 
			      calculer_liste(array($param[1]), array(), $boucles[$idb]->id_parent, $boucles));
	}
}

//
// La fonction d'aiguillage sur le nom du critere
//

function calculer_criteres ($idb, &$boucles) {

	foreach($boucles[$idb]->criteres as $crit) {
		$critere = $crit->op;

		// critere personnalise ?
		$f = "critere_".$critere;
		if (!function_exists($f))
			$f .= '_dist';

		// fonction critere standard ?
		if (!function_exists($f)) {
		  // double cas particulier repere a l'analyse lexicale
		  if (($critere == ",") OR ($critere == '/'))
		    $f = 'calculer_critere_parties';
		  else	$f = 'calculer_critere_DEFAUT';
		}
		// Applique le critere
		$res = $f($idb, $boucles, $crit);

		// Gestion d'erreur
		if (is_array($res)) erreur_squelette($res);
	}
}

# Criteres de comparaison

function calculer_critere_DEFAUT($idb, &$boucles, $crit)
{
	list($fct, $col, $op, $val, $table, $args_sql) =
	  calculer_critere_infixe($idb, $boucles, $crit);

	// ajout pour le cas special d'une condition sur le champ statut:
	// il faut alors interdire a la fonction de boucle
	// de mettre ses propres criteres de statut
	// http://www.spip.net/@statut (a documenter)

	if ($col == 'statut') $boucles[$idb]->statut = true;

	// ajout pour le cas spécial des forums
	// il faut alors interdire a la fonction de boucle sur forum
	// de selectionner uniquement les forums sans pere

	elseif ($boucles[$idb]->type_requete == 'forums' AND
		($col == 'id_parent' OR $col == 'id_forum'))
	  $boucles[$idb]->plat = true;

	// inserer le nom de la table SQL devant le nom du champ
	if ($table) {
		if ($col[0] == "`") 
		  $ct = "$table." . substr($col,1,-1);
		else $ct = "$table.$col";
	} else $ct = $col;

	// inserer la fonction SQL
	if ($fct) $ct = "$fct($ct$args_sql)";

	// inserer la negation (cf !...)
	if (strtoupper($op) == 'IN') {
	  
	      $where = "$ct IN ('\" . " . join(" .\n\"','\" . ", $val) . " . \"')";
	      if ($crit->not) {
		$where = "NOT ($where)";
	      } else {
			$boucles[$idb]->default_order[] = "'cpt'";
			$boucles[$idb]->select[]= "FIND_IN_SET($ct, '\" . " . 
			  join(" .\n\",\" . ", $val) . ' . "\') AS cpt';
	      }
	} else {
		$val = ereg("^'[^']*'$", $val[0]) ? $val[0] :
		  ("'\" . " . $val[0] . ' . "\'');
		$where = "$ct $op $val";
		if ($crit->not) $where = "NOT ($where)";
	}

	 // inserer la condition (cf {lang?}) et c'est fini

	$boucles[$idb]->where[] = 
	  (!$crit->cond) ?
	  $where :
	 ("\".(" . 
	  calculer_argument_precedent($idb, $col, $boucles) .
	  "? (\"$where\") : '').\"");
}

function calculer_critere_infixe($idb, &$boucles, $crit) {

	global $table_des_tables, $tables_principales, $table_date;
	global $exceptions_des_jointures;
	$boucle = &$boucles[$idb];
	$type = $boucle->type_requete;
	$col_table = $boucle->id_table;

	list($fct, $col, $op, $val, $args_sql) =
	  calculer_critere_infixe_ops($idb, $boucles, $crit);

	// Cas particulier : id_enfant => utiliser la colonne id_objet
	if ($col == 'id_enfant')
	  $col = $boucle->primary;

	// Cas particulier : id_secteur = id_rubrique pour certaines tables
	else if (($type == 'breves' OR $type == 'forums') AND $col == 'id_secteur')
	  $col = 'id_rubrique';

	// Cas particulier : expressions de date
	else if ($table_date[$type]
	AND preg_match(",^((age|jour|mois|annee)_relatif|"
	."date|mois|annee|jour|heure|age)(_[a-z]+)?$,",
	$col, $regs)) {
		list($col, $col_table) =
		calculer_critere_infixe_date($idb, $boucles, $regs[1], $regs[3]);
	}

	// HACK : selection des documents selon mode 'image'
	// => on cherche en fait 'vignette'
	else if ($type == 'documents' AND $col == 'mode')
		$val[0] = str_replace('image', 'vignette', $val[0]);

	else  {
	  $nom = $table_des_tables[$type];
	  list($nom, $desc) = trouver_def_table($nom ? $nom : $type, $boucle);
	  if (@!array_key_exists($col, $desc['field'])) {
		if ($exceptions_des_jointures[$col])
			$col = $exceptions_des_jointures[$col];
		$col_table = calculer_critere_externe_init($boucle, $col, $desc, $crit);
	  }
	}
	return array($fct, $col, $op, $val, $col_table, $args_sql);
}

// Champ hors table, ca ne peut etre qu'une jointure.
// On cherche la table du champ et on regarde si elle est deja jointe
// Si oui et qu'on y cherche un champ nouveau, pas de jointure supplementaire
// Exemple: criteres {titre_mot=...}{type_mot=...}
// Dans les 2 autres cas ==> jointure 
// (Exemple: criteres {type_mot=...}{type_mot=...} donne 2 jointures
// pour selectioner ce qui a exactement ces 2 mots-cles.

function calculer_critere_externe_init(&$boucle, $col, $desc, $crit)
{
	$cle = trouver_champ_exterieur($col, $boucle->jointures, $boucle);
	if ($cle) {
		$t = array_search($cle[0], $boucle->from);
		if ($t) {
			$tc = '/\b' . $t  . ".$col" . '\b/';
			foreach ($boucle->where as $v) {
				if (preg_match($tc,$v)) {$t = false; break;}
			}
			if ($t)	return $t;
		}
		$cle = calculer_jointure($boucle, array($boucle->id_table, $desc), $cle, $col);
		if ($cle) return "L$cle";
	}

	erreur_squelette(_T('zbug_info_erreur_squelette'),
			_T('zbug_boucle') .
			" $idb " .
			_T('zbug_critere_inconnu', 
			    array('critere' => $crit->op)));
}

// deduction automatique des jointures 
// une jointure sur une table avec primary key doit se faire sur celle-ci. 

function calculer_jointure(&$boucle, $depart, $arrivee, $col='')
{
  static $num=0;
  $res = calculer_chaine_jointures($boucle, $depart, $arrivee);
  if (!$res) return "";

  list($dnom,$ddesc) = $depart;
  $id_primary = $ddesc['key']['PRIMARY KEY'];
  $id_field = $dnom . '.' . $id_primary;
  $id_table = "";

  foreach($res as $r) {
    list($d, $a, $j) = $r;
    $num++;
    $boucle->join[]= ($id_table ? $id_table : $d) . ".$j=L$num." . $j;
    $boucle->from[$id_table = "L$num"] = $a[0];    
  }

  // pas besoin de group by 
  // si une seule jointure et sur une table primary key formee
  // de l'index principal et de l'index de jointure
  // cf http://article.gmane.org/gmane.comp.web.spip.devel/30555
  // 
  if ($pk = (count($res) == 1)) {
    if ($pk = $a[1]['key']['PRIMARY KEY']) {
	$pk=preg_match("/^$id_primary, *$col$/", $pk) OR
	  preg_match("/^$col, *$id_primary$/", $pk);
    }
  }
  // la clause Group by est en conflit avec ORDER BY, a completer

  if (!$pk && !in_array($id_field, $boucle->group)) {
	  $boucle->group[] = $id_field;
	// postgres exige que le champ pour GROUP soit dans le SELECT
	  if (!in_array($id_field, $boucle->select))
	    $boucle->select[] = $id_field;
  }

  $boucle->lien = true;
  return $num;
}

function calculer_chaine_jointures(&$boucle, $depart, $arrivee, $vu=array())
{
  list($dnom,$ddesc) = $depart;
  list($anom,$adesc) = $arrivee;
  $prim = $ddesc['key']['PRIMARY KEY'];
  $v = array_intersect($prim ? split(', *',$prim): $ddesc['key'], $adesc['key']);
  if ($v)
    return array(array($dnom, $arrivee, array_shift($v)));
   else    {
      $new = $vu;
      foreach($boucle->jointures as $v) {
	if ($v && (!in_array($v,$vu)) && 
	    ($def = trouver_def_table($v, $boucle))) {
	  list($table,$join) = $def;
	  $milieu = array_intersect($ddesc['key'], trouver_cles_table($join['key']));
	  foreach ($milieu as $k)
	    {
	      $new[] = $v;
	      $r = calculer_chaine_jointures($boucle, array($table, $join), $arrivee, $new);
	      if ($r)
		{
		  array_unshift($r, array($dnom, $def, $k));
		  return $r;
		}
	    }
	}
      }
    }
  return array();
}

// applatit les cles multiples

function trouver_cles_table($keys)
{
  $res =array();
  foreach ($keys as $v) {
    if (!strpos($v,","))
      $res[$v]=1; 
    else {
      foreach (split(" *, *", $v) as $k) {
	$res[$k]=1;
      }
    }
  }
  return array_keys($res);
}

// Trouve la description d'une table dans les globales de Spip
// (le prefixe des tables y est toujours 'spip_', son chgt est ulterieur)
// Si on ne la trouve pas, on demande au serveur SQL (marche pas toujours)

function trouver_def_table($nom, &$boucle)
{
	global $tables_principales, $tables_auxiliaires, $table_des_tables, $tables_des_serveurs_sql;

	$nom_table = $nom;
	$s = $boucle->sql_serveur;
	if (!$s) {
		$s = 'localhost';
		if (in_array($nom, $table_des_tables))
		   $nom_table = 'spip_' . $nom;
	}

	$desc = $tables_des_serveurs_sql[$s][$nom_table];

	if ($desc)
		return array($nom_table, $desc);

	include_spip('base/auxiliaires');
	$nom_table = 'spip_' . $nom;
	if ($desc = $tables_auxiliaires[$nom_table])
		return array($nom_table, $desc);

	if ($desc = spip_abstract_showtable($nom, $boucle->sql_serveur))
	  if (isset($desc['field'])) {
      // faudrait aussi prevoir le cas du serveur externe
	    $tables_principales[$nom] = $desc;
	    return array($nom, $desc);
	  }
	erreur_squelette(_T('zbug_table_inconnue', array('table' => $nom)),
			 $boucle->id_boucle);
	}

function trouver_champ_exterieur($cle, $joints, &$boucle)
{
  foreach($joints as $k => $join) {
    if ($join && $table = trouver_def_table($join, $boucle)) {
      if (array_key_exists($cle, $table[1]['field'])) 
	return  $table;
    }
  }
  return "";
}

// determine l'operateur et les operandes

function calculer_critere_infixe_ops($idb, &$boucles, $crit)
{
	// cas d'une valeur comparee a elle-meme ou son referent
	if (count($crit->param) == 0)
	  { $op = '=';
	    $col = $val = $crit->op;
	    // Cas special {lang} : aller chercher $GLOBALS['spip_lang']
	    if ($val == 'lang')
	      $val = array('$GLOBALS[\'spip_lang\']');
	    else {
	    // Si id_parent, comparer l'id_parent avec l'id_objet
	    // de la boucle superieure.... faudrait verifier qu'il existe
	      // pour eviter l'erreur SQL
	      if ($val == 'id_parent')
		$val = $boucles[$idb]->primary;
	      // Si id_enfant, comparer l'id_objet avec l'id_parent
	      // de la boucle superieure
	      else if ($val == 'id_enfant')
		$val = 'id_parent';
	      $val = array("addslashes(" .calculer_argument_precedent($idb, $val, $boucles) .")");
	    }
	  } else {
	    // comparaison explicite
	    // le phraseur impose que le premier param soit du texte
	    $params = $crit->param;
	    $op = $crit->op;
	    if ($op == '==') $op = 'REGEXP';
	    $col = array_shift($params);
	    $col = $col[0]->texte;

	    $val = array();
	    foreach ((($op != 'IN') ? $params : calculer_vieux_in($params)) as $p) {
	      $v = calculer_liste($p, array('id_mere' => $idb), $boucles, $boucles[$idb]->id_parent);
	      $val[] = (preg_match(",^(\n//[^\n]*\n)?'(.*)'$,", $v, $r) ? 
			($r[1] . "'" . addslashes($r[2]) . "'") :
			("addslashes(" . $v . ")"));
	    }
	}

	$fct = $args = '';
	// fonction SQL ?
	if (preg_match('/^(.*)' . SQL_ARGS . '$/', $col, $m)) {
	  $fct = $m[1];
	  preg_match('/^\(([^,]*)(.*)\)$/', $m[2], $a);
	  $col = $a[1];
	  $args_sql = $a[2];
	}

	return array($fct, $col, $op, $val, $args_sql);
}

// compatibilite ancienne version

function calculer_vieux_in($params)
{
	      $deb = $params[0][0];
	      $k = count($params)-1;
	      $last = $params[$k];
	      $j = count($last)-1;
	      $last = $last[$j];
	      $n = strlen($last->texte);

	      if (!(($deb->texte[0] == '(') && ($last->texte[$n-1] == ')')))
		return $params;
	      $params[0][0]->texte = substr($deb->texte,1);
	      // attention, on peut avoir k=0,j=0 ==> recalculer
	      $last = $params[$k][$j];
	      $n = strlen($last->texte);
	      $params[$k][$j]->texte = substr($last->texte,0,$n-1);
	      $newp = array();
	      foreach($params as $v) {
		    if ($v[0]->type != 'texte')
		      $newp[] = $v;
		    else {
		      foreach(split(',', $v[0]->texte) as $x) {
			$t = new Texte;
			$t->texte = $x;
			$newp[] = array($t);
		      }
		    }
	      }
	      return  $newp;
}

function calculer_critere_infixe_date($idb, &$boucles, $col, $suite)
{
	global $table_date; 
	$boucle = $boucles[$idb];

	$date_orig = $table_date[$boucle->type_requete];

	if ($suite) {
	# NOTE : A transformer en recherche de l'existence du champ date_xxxx,
	# si oui choisir ce champ, sinon choisir xxxx
		if ($suite =='_redac' OR $suite=='_modif')
			$date_orig = 'date'.$suite;
		else
			$date_orig = substr($suite, 1);
	}

	$date_compare = '\'" . normaliser_date(' .
	      calculer_argument_precedent($idb, $date_orig, $boucles) .
	      ') . "\'';
	$date_orig = $boucle->id_table . '.' . $date_orig;

	if ($col == 'date') {
			$col = $date_orig;
			$col_table = '';
		}
	else if ($col == 'jour') {
			$col = "DAYOFMONTH($date_orig)";
			$col_table = '';
		}
	else if ($col == 'mois') {
			$col = "MONTH($date_orig)";
			$col_table = '';
		}
	else if ($col == 'annee') {
			$col = "YEAR($date_orig)";
			$col_table = '';
		}
	else if ($col == 'heure') {
			$col = "DATE_FORMAT($date_orig, '%H:%i')";
			$col_table = '';
		}
	else if ($col == 'age') {
			$col = calculer_param_date("now()", $date_orig);
			$col_table = '';
		}
	else if ($col == 'age_relatif') {
			$col = calculer_param_date($date_compare, $date_orig);
			$col_table = '';
		}
	else if ($col == 'jour_relatif') {
			$col = "LEAST(TO_DAYS(" .$date_compare . ")-TO_DAYS(" .
			$date_orig . "), DAYOFMONTH(" . $date_compare .
			")-DAYOFMONTH(" . $date_orig . ")+30.4368*(MONTH(" .
			$date_compare . ")-MONTH(" . $date_orig .
			"))+365.2422*(YEAR(" . $date_compare . ")-YEAR(" .
			$date_orig . ")))";
			$col_table = '';
		}
	else if ($col == 'mois_relatif') {
			$col = "MONTH(" . $date_compare . ")-MONTH(" .
			$date_orig . ")+12*(YEAR(" . $date_compare .
			")-YEAR(" . $date_orig . "))";
			$col_table = '';
		}
	else if ($col == 'annee_relatif') {
			$col = "YEAR(" . $date_compare . ")-YEAR(" .
			$date_orig . ")";
			$col_table = '';
		}
	return array($col, $col_table);
}

function calculer_param_date($date_compare, $date_orig) {
	if (ereg("'\" *\.(.*)\. *\"'", $date_compare, $r)) {
	  $init = "'\" . (\$x = $r[1]) . \"'";
	  $date_compare = '\'$x\'';
	}
	else
	  $init = $date_compare;

	return
	"LEAST((UNIX_TIMESTAMP(" .
	$init .
	")-UNIX_TIMESTAMP(" .
	$date_orig .
	"))/86400,\n\tTO_DAYS(" .
	$date_compare .
	")-TO_DAYS(" .
	$date_orig .
	"),\n\tDAYOFMONTH(" .
	$date_compare .
	")-DAYOFMONTH(" .
	$date_orig .
	")+30.4368*(MONTH(" .
	$date_compare .
	")-MONTH(" .
	$date_orig .
	"))+365.2422*(YEAR(" .
	$date_compare .
	")-YEAR(" .
	$date_orig .
	")))";
}

// fonction provisoirement inutilisee
// reperer des repetitions comme {id_mot=1}{id_mot=2}
//  pour creer une clause HAVING
/*
function calculer_critere_repete(&$boucle, $col, $val)
{
	foreach ($boucle->where as $k => $v)  {
        	if (ereg(" *$col *(=|IN) *\(?'(.*)(\".*)[')]$",$v, $m)) {
                  $boucle->where[$k] = "$col IN ('$m[2] \"','\" . $val . $m[3])";
                  // esperons que c'est le meme !
                  $boucle->having++;
		  return true;}
              }
	return false;
}
*/
?>
