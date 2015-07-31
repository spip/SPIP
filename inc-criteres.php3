<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Definition des {criteres} d'une boucle
//

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CRITERES")) return;
define("_INC_CRITERES", "1");


// {racine}
// http://www.spip.net/@racine
function critere_racine_dist($idb, &$boucles, $crit) {
	$not = $crit->not;
	$boucle = &$boucles[$idb];

	if ($not)
		erreur_squelette(_T('zbug_info_erreur_squelette'), $param);

	$boucle->where[] = $boucle->id_table.".id_parent='0'";

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
	  $boucle->doublons .
	  " = ('" .
	  $boucle->type_requete . 
	  "' . " .
	  calculer_liste($crit->param[0], array(), $boucles, $boucles[$idb]->id_parent) .
	  ')], \'' . 
	  ($crit->not ? '' : 'NOT') .
	  "') . \"";
	if ($crit->not) $boucle->doublons = "";
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

	$boucle = &$boucles[$idb];

	// Ne pas executer la requete en cas de hash vide
	$boucle->hash =  '
	// RECHERCHE
	list($rech_select, $rech_where) = prepare_recherche($GLOBALS["recherche"], "'.$boucle->primary.'", "'.$boucle->id_table.'");
	if ($rech_where) ';

	$boucle->select[]= $boucle->id_table . '.' . $boucle->primary; # pour postgres, neuneu ici
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
	$boucle->where[] = $boucle->id_table.".id_trad = "
	  . $boucle->id_table . '.' . $boucle->primary;
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

	$boucle = &$boucles[$idb];
	if ($crit->not) $sens = $sens ? "" : " . ' DESC'";

	foreach ($crit->param as $tri) {

	// tris specifies dynamiquement
	  if ($tri[0]->type != 'texte') {
	      $order = 
		calculer_liste($tri, array(), $boucles, $boucles[$idb]->id_parent);
	      $order =
		"((\$x = preg_replace(\"/\\W/\",'',$order)) ? ('$boucle->id_table.' . \$x$sens) : '')";
	  }
	    else {
	      $par = array_shift($tri);
	      $par = $par->texte;
	// par hasard
		if ($par == 'hasard') {
		// tester si cette version de MySQL accepte la commande RAND()
		// sinon faire un gloubi-boulga maison avec de la mayonnaise.
		  if (spip_query("SELECT RAND()"))
			$par = "RAND()";
		  else
			$par = "MOD(".$boucle->id_table.'.'.$boucle->primary
			  ." * UNIX_TIMESTAMP(),32767) & UNIX_TIMESTAMP()";
		  $boucle->select[]= $par . " AS alea";
		  $order = "'alea'";
		}

	// par titre_mot
		else if ($par == 'titre_mot') {
		  $order= "'mots.titre'";
		}

	// par type_mot
		else if ($par == 'type_mot'){
		  $order= "'mots.type'";
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
	// par champ. Verifier qu'ils sont presents.
		elseif (ereg("^[a-z][a-z0-9_]*$", $par)) {
		    if ($par == 'date')
		      $order = "'".$boucle->id_table.".".
			$GLOBALS['table_date'][$boucle->type_requete]
			."'";
		    else {
			global $table_des_tables, $tables_des_serveurs_sql;
			$r = $boucle->type_requete;
			$s = $boucles[$idb]->sql_serveur;
			if (!$s) $s = 'localhost';
			$t = $table_des_tables[$r];
			// pour les tables non Spip
			if (!$t) $t = $r; else $t = "spip_$t";
			$desc = $tables_des_serveurs_sql[$s][$t];
			if ($desc['field'][$par])
				$order = "'".$boucle->id_table.".".$par."'";
			else {
			  // tri sur les champs synthetises (cf points)
				$order = "'".$par."'";
			}
		    }
		} else
		    erreur_squelette(_T('zbug_info_erreur_squelette'), "{par $par} BOUCLE$idb");
	    }

	    if ($order)
	      $boucle->order[] = $order . (($order[0]=="'") ? $sens : "");
	  }
}


// {inverse}
// http://www.spip.net/@inverse
// obsolete. utiliser {!par ...}
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
	      erreur_squelette(_T('zbug_info_erreur_squelette'), "{inverse ?} BOUCLE$idb");
	  }
}

function critere_agenda($idb, &$boucles, $crit)
{
	$params = $crit->param;

	if (count($params) < 1)
	      erreur_squelette(_T('zbug_info_erreur_squelette'),
			       "{agenda ?} BOUCLE$idb");

	$parent = $boucles[$idb]->id_parent;

	// les valeur $date et $type doivent etre connus à la compilation
	// autrement dit ne pas être des champs

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

function calculer_critere_DEFAUT($idb, &$boucles, $crit) {
	
	global $table_date, $table_des_tables;
	global $tables_relations;

	$boucle = &$boucles[$idb];
	$type = $boucle->type_requete;
	$col_table = $id_table = $boucle->id_table;
	$primary = $boucle->primary;
	$id_field = $id_table . '.' . $primary; 
	$fct = '';

	// cas d'une valeur comparee a elle-meme ou son referent
	if (count($crit->param) ==0)
	  { $op = '=';
	    $col = $crit->op;
	    $val = $crit->op;
	    // Cas special {lang} : aller chercher $GLOBALS['spip_lang']
	    if ($val == 'lang')
	      $val = array('$GLOBALS[\'spip_lang\']');
	    else {
	    // Si id_parent, comparer l'id_parent avec l'id_objet
	    // de la boucle superieure.... faudrait verifier qu'il existe
	      // pour eviter l'erreur SQL
	      if ($val == 'id_parent')
		$val = $primary;
	      // Si id_enfant, comparer l'id_objet avec l'id_parent
	      // de la boucle superieure
	      else if ($val == 'id_enfant')
		$val = 'id_parent';
	      $val = array("addslashes(" .calculer_argument_precedent($idb, $val, $boucles) .")");
	    }
	  }
	else
	  {
	    // comparaison explicite
	    // le phraseur impose que le premier param soit du texte
	    $params = $crit->param;
	    $op = $crit->op;

	    $col = array_shift($params);
	    $col = $col[0]->texte;
	    // fonction SQL ?
	    if (ereg("([A-Za-z_]+)\(([a-z_]+)\)", $col,$match3)) {
	      $col = $match3[2];
	      $fct = $match3[1];
	    }

	    $val = array();
	    foreach ((($op != 'IN') ? $params : calculer_vieux_in($params)) as $p) {
	      $val[] = "addslashes(" .
		calculer_liste($p, array(), $boucles, $boucles[$idb]->id_parent) .
		")";
	    }
	  }

	  // cas special: statut=
	  // si on l'invoque dans une boucle il faut interdire
	  // a la boucle de mettre ses propres criteres de statut
	  // http://www.spip.net/@statut (a documenter)
	if ($col == 'statut')
		  $boucle->where['statut'] = '1';

	// reperer les champs n'appartenant pas a la table de la boucle

	if ($ext_table =  $tables_relations[$type][$col])
		$col_table = $ext_table . 
		  calculer_critere_externe($boucle, $id_field, $ext_table, $type, $col);
	// Cas particulier pour les raccourcis 'type_mot' et 'titre_mot'
	elseif ($type != 'mots' AND $table_des_tables[$type]
			AND ($col == 'type_mot' OR $col == 'titre_mot'
			OR $col == 'id_groupe')) {
		if ($type == 'forums')
		  $lien = "mots_forum";
		else if ($type == 'syndication')
		  $lien = "mots_syndic";
		else
		  $lien = "mots_$type";
		
		// jointure nouvelle a chaque comparaison
		$num_lien = calculer_critere_externe($boucle, $id_field, $lien, $type, $col);
		// jointure pour lier la table principale et la nouvelle
		$boucle->from[] = "spip_mots AS l_mots$num_lien";
		$boucle->where[] = "$lien$num_lien.id_mot=l_mots$num_lien.id_mot";
		$col_table = "l_mots$num_lien";

		if ($col == 'type_mot')
		  $col = 'type';
		else if ($col == 'titre_mot')
		  $col = 'titre';
	}

	// Cas particulier : selection des documents selon l'extension
	if ($type == 'documents' AND $col == 'extension')
		$col_table = 'types_documents';
	// HACK : selection des documents selon mode 'image'
	// => on cherche en fait 'vignette'
	else if ($type == 'documents' AND $col == 'mode')
		$val[0] = str_replace('image', 'vignette', $val[0]);
	// Cas particulier : lier les articles syndiques
	// au site correspondant
	else if ($type == 'syndic_articles' AND
		 !ereg("^(id_syndic_article|titre|url|date|descriptif|lesauteurs|id_document)$",$col))
	  $col_table = 'syndic';

	// Cas particulier : id_enfant => utiliser la colonne id_objet
	if ($col == 'id_enfant')
	  $col = $primary;
	// Cas particulier : id_secteur = id_rubrique pour certaines tables

	if (($type == 'breves' OR $type == 'forums') AND $col == 'id_secteur')
	  $col = 'id_rubrique';

	// Cas particulier : expressions de date
	if (ereg("^(date|mois|annee|heure|age|age_relatif|jour_relatif|mois_relatif|annee_relatif)(_redac)?$", $col, $regs)) {
	  $col = $regs[1];
	  if ($regs[2]) {
	    $date_orig = $id_table . ".date_redac";
	    $date_compare = '\'" . normaliser_date(' .
	      calculer_argument_precedent($idb, 'date_redac', $boucles) .
	      ') . "\'';
	  }
	  else {
	    $date_orig = "$id_table." . $table_date[$type];
	    $date_compare = '\'" . normaliser_date(' .
	      calculer_argument_precedent($idb, 'date', $boucles) .
	      ') . "\'';
	  }

	  if ($col == 'date') {
			$col = $date_orig;
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
	}

	if ($type == 'forums' AND
	($col == 'id_parent' OR $col == 'id_forum'))
		$boucle->plat = true;

	// Rajouter le nom de la table SQL devant le nom du champ
	if ($col_table) {
		if ($col[0] == "`") 
		  $ct = "$col_table." . substr($col,1,-1);
		else $ct = "$col_table.$col";
	} else $ct = $col;

	// fonction SQL
	if ($fct) $ct = "$fct($ct)";

	//	if (($op != '=') || !calculer_critere_repete($boucle, $ct, $val[0])) # a revoir
	if (strtoupper($op) == 'IN') {
	  
	      $where = "$ct IN ('\" . " . join(" .\n\"','\" . ", $val) . " . \"')";
	      if ($crit->not) {
		$where = "NOT ($where)";
	      } else {
			$boucle->default_order = array('rang');
			$boucle->select[]= "FIND_IN_SET($ct, '\" . " . 
			  join(" .\n\",\" . ", $val) . ' . "\') AS rang';
	      }
	} else {
		  if ($op == '==') $op = 'REGEXP';
		  $where = "($ct $op '\" . " . $val[0] . ' . "\')';
		  if ($crit->not) $where = "NOT $where";

		// operateur optionnel {lang?}
		  if ($crit->cond) {
		    $champ = calculer_argument_precedent($idb, $col, $boucles) ;
		    $where = "\".($champ ? \"$where\" : 1).\"";
		  }
	    }
	$boucle->where[] = $where;
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
	      // compatibilité ancienne version

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
// traitement des relations externes par DES jointures.

function calculer_critere_externe(&$boucle, $id_field, $lien, $type, $col) {

	global $tables_relations_keys;
	static $num;

	$num++;
	$ref = $tables_relations_keys[$type][$col];
	$boucle->lien = true;
	$boucle->from[] = "spip_$lien AS $lien$num";
	$boucle->where[] = "$id_field=$lien$num." .
	  ($ref ? $ref : $boucle->primary);
	$boucle->group = $id_field;
	// postgres exige que le champ pour GROUP soit dans le SELECT
	$boucle->select[] = $id_field;
	return $num;
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

?>
