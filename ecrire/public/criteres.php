<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
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
// http://doc.spip.org/@critere_racine_dist
function critere_racine_dist($idb, &$boucles, $crit) {
	global $exceptions_des_tables;
	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$id_parent = isset($exceptions_des_tables[$boucle->id_table]['id_parent']) ?
		$exceptions_des_tables[$boucle->id_table]['id_parent'] :
		'id_parent';

	if ($not)
		erreur_squelette(_T('zbug_info_erreur_squelette'), $crit->op);

	$boucle->where[]= array("'='", "'$boucle->id_table." . "$id_parent'", 0);
}

// {exclus}
// http://www.spip.net/@exclus
// http://doc.spip.org/@critere_exclus_dist
function critere_exclus_dist($idb, &$boucles, $crit) {
	$param = $crit->op;
	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$id = $boucle->primary;

	if ($not OR !$id)
		erreur_squelette(_T('zbug_info_erreur_squelette'), $param);

	$arg = kwote(calculer_argument_precedent($idb, $id, $boucles));
	$boucle->where[]= array("'!='", "'$boucle->id_table." . "$id'", $arg);
}

// {doublons} ou {unique}
// http://www.spip.net/@doublons
// attention: boucle->doublons designe une variable qu'on affecte
// http://doc.spip.org/@critere_doublons_dist
function critere_doublons_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$primary = $boucle->primary;

	if (!$primary OR strpos($primary,',')) {
		erreur_squelette(_T('zbug_doublon_sur_table_sans_cle_primaire'), "BOUCLE$idb");
		return;
	}

	$not = ($crit->not ? '' : 'NOT');

	$nom = !isset($crit->param[0]) ? "''" : calculer_liste($crit->param[0], array(), $boucles, $boucles[$idb]->id_parent);
	// mettre un tableau pour que ce ne soit pas vu comme une constante

	$nom = "'" .
	  $boucle->type_requete . 
	  "'" .
	  ($nom == "''" ? '' : " . $nom");

	$debutdoub = '$doublons['
	.  (!$not ? '' : ($boucle->doublons . "[]= "));

	$findoub = "($nom)]"; 

	$debin = "sql_in('" . $boucle->id_table . '.' . $primary . "', ";

	$suitin = $debin . $debutdoub;

	// si autre critere doublon, fusionner pour avoir un seul In
	foreach ($boucle->where as $k => $w) {
		if (strpos($w[0], $suitin) ===0) {
			$boucle->where[$k][0] = $debin . $debutdoub . $findoub . ' . ' . substr($w[0],strlen($debin));
			return;
		}
	}
	$boucle->where[]= array($suitin . $findoub . ", '" . $not . "')");




# la ligne suivante avait l'intention d'eviter une collecte deja faite
# mais elle fait planter une boucle a 2 critere doublons:
# {!doublons A}{doublons B}
# (de http://article.gmane.org/gmane.comp.web.spip.devel/31034)
#	if ($crit->not) $boucle->doublons = "";
}

// {lang_select}
// http://www.spip.net/@lang_select
// http://doc.spip.org/@critere_lang_select_dist
function critere_lang_select_dist($idb, &$boucles, $crit) {
	if (!($param = $crit->param[1][0]->texte)) $param = 'oui';
	if ($crit->not)	$param = ($param=='oui') ? 'non' : 'oui';
	$boucle = &$boucles[$idb];
	$boucle->lang_select = $param;
}

// {debut_xxx}
// http://www.spip.net/@debut_
// http://doc.spip.org/@critere_debut_dist
function critere_debut_dist($idb, &$boucles, $crit) {
	list($un, $deux) = $crit->param;
	$un = $un[0]->texte;
	$deux = $deux[0]->texte;
	if ($deux) {
		$boucles[$idb]->limit = 'intval($Pile[0]["debut' .
		  $un .
		  '"]) . ",' .
		  $deux .
		  '"' ;
	} else calculer_critere_DEFAUT($idb, $boucles, $crit);
}

// {pagination}
// {pagination 20}
// {pagination #ENV{pages,5}} etc
// {pagination 20 #ENV{truc,chose}} pour utiliser la variable debut_#ENV{truc,chose}
// http://www.spip.net/@pagination
// http://doc.spip.org/@critere_pagination_dist
function critere_pagination_dist($idb, &$boucles, $crit) {

	// definition de la taille de la page
	$pas = !isset($crit->param[0][0]) ? "''" : calculer_liste(array($crit->param[0][0]), array(), $boucles, $boucles[$idb]->id_parent);
	if (!preg_match("/^'([^']*)'$/", $pas, $r)) {
		$pas = "((\$a = intval($pas)) ? \$a : 10)";
	} else {
		$r = intval($r[1]);
		$pas = strval($r ? $r : 10);
	}
	$debut = !isset($crit->param[0][1]) ? "'$idb'" : calculer_liste(array($crit->param[0][1]), array(), $boucles, $boucles[$idb]->id_parent);

	$boucle = &$boucles[$idb];
	$boucle->mode_partie = 'p+';
	$boucle->partie = 'intval(isset($Pile[0][\'debut\'.'.$debut.']) ? $Pile[0][\'debut\'.'.$debut.'] : _request(\'debut\'.'.$debut.'))';
	$boucle->modificateur['debut_nom'] = $debut;
	$boucle->total_parties = $pas;
}

// {recherche} ou {recherche susan}
// http://www.spip.net/@recherche
// http://doc.spip.org/@critere_recherche_dist
function critere_recherche_dist($idb, &$boucles, $crit) {

	$boucle = &$boucles[$idb];

	if (isset($crit->param[0]))
		$quoi = calculer_liste($crit->param[0], array(), $boucles, $boucles[$idb]->id_parent);
	else
		$quoi = '@$Pile[0]["recherche"]';

	$boucle->hash .= '
	// RECHERCHE
	$prepare_recherche = charger_fonction(\'prepare_recherche\', \'inc\');
	list($rech_select, $rech_where) = $prepare_recherche('.$quoi.', "'.$boucle->id_table.'", "'.$crit->cond.'","' . $boucle->sql_serveur . '");
	';

	$t = $boucle->id_table . '.' . $boucle->primary;
	if (!in_array($t, $boucles[$idb]->select))
	  $boucle->select[]= $t; # pour postgres, neuneu ici
	$boucle->join['resultats']=array("'".$boucle->id_table."'","'id'","'".$boucle->primary."'");
	$boucle->from['resultats']='spip_resultats';
	$boucle->select[]= '$rech_select';
	//$boucle->where[]= "\$rech_where?'resultats.id=".$boucle->id_table.".".$boucle->primary."':''";

	// et la recherche trouve
	$boucle->where[]= '$rech_where?$rech_where:\'\'';
}

// {traduction}
// http://www.spip.net/@traduction
//   (id_trad>0 AND id_trad=id_trad(precedent))
//    OR id_article=id_article(precedent)
// http://doc.spip.org/@critere_traduction_dist
function critere_traduction_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$prim = $boucle->primary;
	$table = $boucle->id_table;
	$arg = kwote(calculer_argument_precedent($idb, 'id_trad', $boucles));
	$dprim = kwote(calculer_argument_precedent($idb, $prim, $boucles));
	$boucle->where[]=
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
function critere_origine_traduction_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$prim = $boucle->primary;
	$table = $boucle->id_table;

	$c =
	array("'OR'",
		array("'='", "'$table." . "id_trad'", "'$table.$prim'"),
		array("'='", "'$table.id_trad'", "'0'")
	);
	$boucle->where[]= ($crit->not ? array("'NOT'", $c) : $c);
}

// {meme_parent}
// http://www.spip.net/@meme_parent
// http://doc.spip.org/@critere_meme_parent_dist
function critere_meme_parent_dist($idb, &$boucles, $crit) {
	global $exceptions_des_tables;
	$boucle = &$boucles[$idb];
	$arg = kwote(calculer_argument_precedent($idb, 'id_parent', $boucles));
	$id_parent = isset($exceptions_des_tables[$boucle->id_table]['id_parent']) ?
		$exceptions_des_tables[$boucle->id_table]['id_parent'] :
		'id_parent';
	$mparent = $boucle->id_table . '.' . $id_parent;

	if ($boucle->type_requete == 'rubriques' OR isset($exceptions_des_tables[$boucle->id_table]['id_parent'])) {
		$boucle->where[]= array("'='", "'$mparent'", $arg);

	} else if ($boucle->type_requete == 'forums') {
			$boucle->where[]= array("'='", "'$mparent'", $arg);
			$boucle->where[]= array("'>'", "'$mparent'", 0);
			$boucle->modificateur['plat'] = true;
	} else erreur_squelette(_T('zbug_info_erreur_squelette'), "{meme_parent} BOUCLE$idb");
}

// {branche ?}
// http://www.spip.net/@branche
// http://doc.spip.org/@critere_branche_dist
function critere_branche_dist($idb, &$boucles, $crit) {

	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$arg = calculer_argument_precedent($idb, 'id_rubrique', $boucles);

	//Trouver une jointure
	$desc = $boucle->show;
	//Seulement si necessaire
	if (!array_key_exists('id_rubrique', $desc['field'])) {
		$cle = trouver_jointure_champ('id_rubrique', $boucle);
	} else $cle = $boucle->id_table;

	$c = "sql_in('$cle" . ".id_rubrique', calcul_branche_in($arg)"
	  . ($not ? ", 'NOT'" : '') . ")";
	$boucle->where[]= !$crit->cond ? $c :
	  ("($arg ? $c : " . ($not ? "'0=1'" : "'1=1'") .')');
}

// {logo} liste les objets qui ont un logo
// http://doc.spip.org/@critere_logo_dist
function critere_logo_dist($idb, &$boucles, $crit) {

	$not = $crit->not;
	$boucle = &$boucles[$idb];

	$c = "sql_in('" .
	  $boucle->id_table . '.' . $boucle->primary
	  . "', lister_objets_avec_logos('". $boucle->primary ."'), '')";
	if ($crit->cond) $c = "($arg ? $c : 1)";

	if ($not)
		$boucle->where[]= array("'NOT'", $c);
	else
		$boucle->where[]= $c;
}

// c'est la commande SQL "GROUP BY"
// par exemple <boucle(articles){fusion lang}>
// http://doc.spip.org/@critere_fusion_dist
function critere_fusion_dist($idb,&$boucles, $crit) {
	if ($t = isset($crit->param[0])) {
		$t = $crit->param[0];
		if ($t[0]->type == 'texte') {
			$t = $t[0]->texte;
			if (preg_match("/^(.*)\.(.*)$/", $t, $r)) {
				$t = table_objet_sql($r[1]);
				$t = array_search($t, $boucles[$idb]->from);
				if ($t) $t .= '.' . $r[2];
			}
		} else { $t = '".'
		    . calculer_critere_arg_dynamique($idb, $boucles, $t)
		    . '."';
		}
	}
	if ($t) {
		$boucles[$idb]->group[] = $t; 
		if (!in_array($t, $boucles[$idb]->select))
		    $boucles[$idb]->select[] = $t;
	} else 
		erreur_squelette(_T('zbug_info_erreur_squelette'),
			"{groupby ?} BOUCLE$idb");
}

// c'est la commande SQL "COLLATE"
// qui peut etre appliquee sur les order by, group by, where like ...
// http://doc.spip.org/@critere_collecte_dist
function critere_collecte_dist($idb,&$boucles, $crit) {
	if (isset($crit->param[0])) {
		$_coll = calculer_liste($crit->param[0], array(), $boucles, $boucles[$idb]->id_parent);
		$boucle = $boucles[$idb];
		$boucle->modificateur['collate'] = "($_coll ?' COLLATE '.$_coll:'')";
    $n = count($boucle->order);
    if ($n && (strpos($boucle->order[$n-1],'COLLATE')===false))
    	$boucle->order[$n-1] .= " . " . $boucle->modificateur['collate'];
	} else
		erreur_squelette(_T('zbug_info_erreur_squelette'),
			"{collecte ?} BOUCLE$idb");
}

// http://doc.spip.org/@calculer_critere_arg_dynamique
function calculer_critere_arg_dynamique($idb, &$boucles, $crit, $suffix='')
{
	$boucle = $boucles[$idb];
	$alt = "('" . $boucle->id_table . '.\' . $x' . $suffix . ')';
	$var = '$champs_' . $idb;
	$desc = (strpos($boucle->in, "static $var =") !== false);
	if (!$desc) {
		$desc = $boucle->show['field'];
		$desc = implode(',',array_map('_q',array_keys($desc)));
		$boucles[$idb]->in .= "\n\tstatic $var = array(" . $desc .");";
	}
	if ($desc) $alt = "(in_array(\$x, $var)  ? $alt :(\$x$suffix))";
	$arg = calculer_liste($crit, array(), $boucles, $boucle->id_parent);
	return	"((\$x = preg_replace(\"/\\W/\",'', $arg)) ? $alt : '')";
}

// Tri : {par xxxx}
// http://www.spip.net/@par
// http://doc.spip.org/@critere_par_dist
function critere_par_dist($idb, &$boucles, $crit) {
	critere_parinverse($idb, $boucles, $crit) ;
}

// http://doc.spip.org/@critere_parinverse
function critere_parinverse($idb, &$boucles, $crit, $sens='') {
	global $exceptions_des_jointures;
	$boucle = &$boucles[$idb];
	if ($crit->not) $sens = $sens ? "" : " . ' DESC'";
	$collecte = (isset($boucle->modificateur['collecte']))?" . ".$boucle->modificateur['collecte']:"";

	foreach ($crit->param as $tri) {

	  $order = $fct = ""; // en cas de fonction SQL
	// tris specifies dynamiquement
	  if ($tri[0]->type != 'texte') {
	  	// calculer le order dynamique qui verifie les champs
			$order = calculer_critere_arg_dynamique($idb, $boucles, $tri, $sens);
			// et si ce n'est fait, ajouter un champ 'hasard' 
			// pour supporter 'hasard' comme tri dynamique
			$par = "rand()";
			$parha = $par . " AS hasard";
			if (!in_array($parha, $boucle->select))
				$boucle->select[]= $parha;
	  } else {
	      $par = array_shift($tri);
	      $par = $par->texte;
    // par multi champ
	      if (preg_match(",^multi[\s]*(.*)$,",$par, $m)) {
		  $texte = $boucle->id_table . '.' . trim($m[1]);
		  $boucle->select[] =  "\".sql_multi('".$texte."', \$GLOBALS['spip_lang']).\"" ;
		  $order = "'multi'";
	// par num champ(, suite)
	      }	else if (preg_match(",^num (.*)$,m",$par, $m)) {
		  $texte = '0+' . $boucle->id_table . '.' . trim($m[1]);
		  $suite = calculer_liste($tri, array(), $boucles, $boucle->id_parent);
		  if ($suite !== "''")
		    $texte = "\" . ((\$x = $suite) ? ('$texte' . \$x) : '0')" . " . \"";
		  $as = 'num' .($boucle->order ? count($boucle->order) : "");
		  $boucle->select[] = $texte . " AS $as";
		  $order = "'$as'";
	      } else {
	      if (!preg_match(",^" . CHAMP_SQL_PLUS_FONC . '$,is', $par, $match)) 
		erreur_squelette(_T('zbug_info_erreur_squelette'), "{par $par} BOUCLE$idb");
	      else {
		if (count($match)>2) { $par = substr($match[2],1,-1); $fct = $match[1]; }
	// par hasard
		if ($par == 'hasard') {
			$par = "rand()";
			$boucle->select[]= $par . " AS alea";
			$order = "'alea'";
		}
	// par titre_mot ou type_mot voire d'autres
		else if (isset($exceptions_des_jointures[$par])) {
			list($table, $champ) =  $exceptions_des_jointures[$par];
			$order = critere_par_joint($table, $champ, $boucle, $idb);
		}
		else if ($par == 'date'
		AND isset($GLOBALS['table_date'][$boucle->type_requete])) {
			$m = $GLOBALS['table_date'][$boucle->type_requete];
			$order = "'".$boucle->id_table ."." . $m . "'";
		}
		// par champ. Verifier qu'ils sont presents.
		elseif (preg_match("/^([^,]*)\.(.*)$/", $par, $r)) {
		  // cas du tri sur champ de jointure explicite
			$t = array_search($r[1], $boucle->from);
			if (!$t) {
				$t = trouver_champ_exterieur($r[2], array($r[1]), $boucle);
				$t = array_search(@$t[0], $boucle->from);
			}
			if (!$t) {
				erreur_squelette(_T('zbug_info_erreur_squelette'),  "{par $par } BOUCLE$idb");
				$order = '';
			} else 	$order = "'" . $t . '.' . $r[2] . "'";
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
	  if (preg_match('/^\'([^"]*)\'$/', $order, $m)) {
	      $t = $m[1];
	      if (strpos($t,'.') AND !in_array($t, $boucle->select)) {
		$boucle->select[] = $t;
	      }
	  } else $sens ='';

	  if ($fct) {
	    if (preg_match("/^\s*'(.*)'\s*$/", $order, $r))
	      $order = "'$fct(" . $r[1] . ")'";
	    else $order = "'$fct(' . $order . ')'";
	  }
	  $t = $order . $collecte . $sens;
	  if (preg_match("/^(.*)'\s*\.\s*'([^']*')$/", $t, $r))
	      $t = $r[1] . $r[2];
	  $boucle->order[] = $t;
	}
}

// http://doc.spip.org/@critere_par_joint
function critere_par_joint($table, $champ, &$boucle, $idb)
{
	$t = array_search($table, $boucle->from);
	if (!$t) $t = trouver_jointure_champ($champ, $boucle);
	if (!$t) erreur_squelette(_T('zbug_info_erreur_squelette'),  "{par ?} BOUCLE$idb");
	return "'" . $t . '.' . $champ . "'";
}

// {inverse}
// http://www.spip.net/@inverse

// http://doc.spip.org/@critere_inverse_dist
function critere_inverse_dist($idb, &$boucles, $crit) {

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
		if (!$n)
			$boucle->default_order[] =  ' DESC';
		else {
			$t = $boucle->order[$n-1] . " . $order";
			if (preg_match("/^(.*)'\s*\.\s*'([^']*')$/", $t, $r))
				$t = $r[1] . $r[2];
			$boucle->order[$n-1] = $t;
		}
	  }
}

// http://doc.spip.org/@critere_agenda_dist
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
		$boucle->where[]= array("'='", "'DATE_FORMAT($date, \'%Y%m%d\')'",
					("$annee . $mois . $jour"));
	elseif ($type == 'mois')
		$boucle->where[]= array("'='", "'DATE_FORMAT($date, \'%Y%m\')'",
					("$annee . $mois"));
	elseif ($type == 'semaine')
		$boucle->where[]= array("'AND'", 
					array("'>='",
					     "'DATE_FORMAT($date, \'%Y%m%d\')'", 
					      ("date_debut_semaine($annee, $mois, $jour)")),
					array("'<='",
					      "'DATE_FORMAT($date, \'%Y%m%d\')'",
					      ("date_fin_semaine($annee, $mois, $jour)")));
	elseif (count($crit->param) > 2) 
		$boucle->where[]= array("'AND'",
					array("'>='",
					      "'DATE_FORMAT($date, \'%Y%m%d\')'",
					      ("$annee . $mois . $jour")),
					array("'<='", "'DATE_FORMAT($date, \'%Y%m%d\')'", ("$annee2 . $mois2 . $jour2")));
	// sinon on prend tout
}

// http://doc.spip.org/@calculer_critere_parties
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

// http://doc.spip.org/@calculer_critere_parties_aux
function calculer_critere_parties_aux($idb, &$boucles, $param) {
	if ($param[0]->type != 'texte')
	  {
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

//
// La fonction d'aiguillage sur le nom du critere
//

// http://doc.spip.org/@calculer_criteres
function calculer_criteres ($idb, &$boucles) {

	foreach($boucles[$idb]->criteres as $crit) {
		$critere = $crit->op;
		// critere personnalise ?
		if (
		  (!function_exists($f="critere_".strtoupper($boucles[$idb]->id_table)."_".$critere))
		AND (!function_exists($f=$f."_dist"))
		AND (!function_exists($f="critere_".$critere))
		AND (!function_exists($f=$f."_dist"))	) {
		  // fonction critere standard ?
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

// Madeleine de Proust, revision MIT-1958 sqq, revision CERN-1989
// hum, c'est kwoi cette fonxion ?
// http://doc.spip.org/@kwote
function kwote($lisp)
{
	if (preg_match(",^(\n//[^\n]*\n)? *'(.*)' *$,", $lisp, $r))
		return $r[1] . "\"" . sql_quote(str_replace(array("\\'","\\\\"),array("'","\\"),$r[2])) . "\"" ;
	else
		return "sql_quote($lisp)"; 
}

// Si on a une liste de valeurs dans #ENV{x}, utiliser la double etoile
// pour faire par exemple {id_article IN #ENV**{liste_articles}}
// http://doc.spip.org/@critere_IN_dist
function critere_IN_dist ($idb, &$boucles, $crit)
{

	list($arg, $op, $val, $col, $where_complement)= calculer_critere_infixe($idb, $boucles, $crit);
	$in = critere_IN_cas($idb, $boucles, $crit->not ? 'NOT' : '', $arg, $op, $val, $col);
//	inserer la condition; exemple: {id_mot ?IN (66, 62, 64)}
	$where = $in;
	if ($crit->cond) {
		$pred = calculer_argument_precedent($idb, $col, $boucles);
		$where = array("'?'",$pred,	$where,"''");
		if ($where_complement) // condition annexe du type "AND (objet='article')"
			$where_complement = array("'?'",$pred,	$where_complement,"''");
	}
	if ($crit->exclus)
		if (!preg_match(",^L[0-9]+[.],",$arg))
			$where = array("'NOT'", $where);
		else
			// un not sur un critere de jointure se traduit comme un NOT IN avec une sous requete
			$where = array("'NOT'",array("'IN'","'".$boucles[$idb]->id_table.".".$boucles[$idb]->primary."'" ,array("'SELF'","'".$boucles[$idb]->id_table.".".$boucles[$idb]->primary."'",$where)));

	$boucles[$idb]->where[] = $where;
	if ($where_complement) // condition annexe du type "AND (objet='article')"
		$boucles[$idb]->where[]= $where_complement;
}

// http://doc.spip.org/@critere_IN_cas
function critere_IN_cas ($idb, &$boucles, $crit2, $arg, $op, $val, $col)
{
	static $cpt = 0;
	$var = '$in' . $cpt++;
	$x= "\n\t$var = array();";
	foreach ($val as $k => $v) {
		if (preg_match(",^(\n//.*\n)?'(.*)'$,", $v, $r)) {
		  // optimiser le traitement des constantes
			if (is_numeric($r[2]))
				$x .= "\n\t$var" . "[]= $r[2];";
			else
				$x .= "\n\t$var" . "[]= " . sql_quote($r[2]) . ";";
		} else {
		  // Pour permettre de passer des tableaux de valeurs
		  // on repere l'utilisation brute de #ENV**{X}, 
		  // c'est-a-dire sa  traduction en ($PILE[0][X]).
		  // et on deballe mais en rajoutant l'anti XSS
		  $x .= "\n\tif (!(is_array(\$a = ($v))))\n\t\t$var" ."[]= \$a;\n\telse $var = array_merge($var, \$a);";
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
	
	return "sql_in('$arg',sql_quote($var)".($crit2=='NOT'?",'NOT'":"").")";
}


# Criteres de comparaison

// http://doc.spip.org/@calculer_critere_DEFAUT
function calculer_critere_DEFAUT($idb, &$boucles, $crit)
{
	list($arg, $op, $val, $col, $where_complement)= calculer_critere_infixe($idb, $boucles, $crit);

	$where = array("'$op'", "'$arg'", $val[0]);

	// inserer la negation (cf !...)

	if ($crit->not) $where = array("'NOT'", $where);
	if ($crit->exclus) 
		if (!preg_match(",^L[0-9]+[.],",$arg))
			$where = array("'NOT'", $where);
		else
			// un not sur un critere de jointure se traduit comme un NOT IN avec une sous requete
			$where = array("'NOT'",array("'IN'","'".$boucles[$idb]->id_table.".".$boucles[$idb]->primary."'" ,array("'SELF'","'".$boucles[$idb]->id_table.".".$boucles[$idb]->primary."'",$where)));

	// inserer la condition (cf {lang?})
	// traiter a part la date, elle est mise d'office par SPIP,
	if ($crit->cond) {
		$pred = calculer_argument_precedent($idb, $col, $boucles);
    if ($col == "date" OR $col == "date_redac") {
      if($pred == "\$Pile[0]['".$col."']") {
        $pred = "(\$Pile[0]['{$col}_default']?'':$pred)";
      }
    }

		if ($op == '=' AND !$crit->not)
		  $where = array("'?'", "(is_array($pred))", 
				 critere_IN_cas ($idb, $boucles, 'COND', $arg, $op, array($pred), $col), 
				 $where);
		$where = array("'?'", "!$pred","''", $where);
		if ($where_complement) // condition annexe du type "AND (objet='article')"
			$where_complement = array("'?'", "!$pred","''", $where_complement);
	}

	$boucles[$idb]->where[]= $where;
	if ($where_complement) // condition annexe du type "AND (objet='article')"
		$boucles[$idb]->where[]= $where_complement;
}

// http://doc.spip.org/@calculer_critere_infixe
function calculer_critere_infixe($idb, &$boucles, $crit) {

	global $table_criteres_infixes;
	global $exceptions_des_jointures, $exceptions_des_tables;

	$boucle = &$boucles[$idb];
	$type = $boucle->type_requete;
	$table = $boucle->id_table;
	$desc = $boucle->show;
	$date = array();

	list($fct, $col, $op, $val, $args_sql) =
	  calculer_critere_infixe_ops($idb, $boucles, $crit);
	$col_alias = $col;
	$where_complement =false;
//var_dump(array($fct, $col, $op, $val, $args_sql));
	// Cas particulier : id_enfant => utiliser la colonne id_objet
	if ($col == 'id_enfant')
	  $col = $boucle->primary;

	// Cas particulier : id_parent => verifier les exceptions de tables
	if ($col == 'id_parent')
	  $col = isset($exceptions_des_tables[$table]['id_parent']) ?
		$exceptions_des_tables[$table]['id_parent'] :
		'id_parent';

	// Cas particulier : id_secteur pour certaines tables
	else if (($col == 'id_secteur')&&($type == 'breves')) {
		$col = 'id_rubrique';
	}
	else if (($col == 'id_secteur')&& ($type == 'forums')) {
		$table = critere_secteur_forum($idb, $boucles, $val, $crit);
	}
	
	// cas id_article=xx qui se mappe en id_objet=xx AND objet=article
	else if (count(trouver_champs_decomposes($col,$desc))>1){
		$e = decompose_champ_id_objet($col);
		$col = array_shift($e);
		$where_complement = primary_doublee($e, $table);

	}
	// Cas particulier : expressions de date
	else if ($date = tester_param_date($boucle->type_requete, $col)) {
		list($col, $table) =
		calculer_critere_infixe_date($idb, $boucles, $date);
	}
	else if (preg_match('/^(.*)\.(.*)$/', $col, $r)) {
		  list(,$table, $col) = $r;
		  $col_alias = $col;
		  $table = calculer_critere_externe_init($boucle, array($table), $col, $desc, ($crit->cond OR $op !='='), true);
	}
	else {
		if (@!array_key_exists($col, $desc['field'])) {
	  	$calculer_critere_externe = 'calculer_critere_externe_init';
			// gestion par les plugins des jointures tordues pas automatiques mais necessaires
			if (isset($exceptions_des_jointures[$table][$col])){
				if (count($exceptions_des_jointures[$table][$col])==3)
					list($t, $col, $calculer_critere_externe) = $exceptions_des_jointures[$table][$col];
				else
					list($t, $col) = $exceptions_des_jointures[$table][$col];
			}
			else if (isset($exceptions_des_jointures[$col]))
				list($t, $col) = $exceptions_des_jointures[$col];
			else $t =''; // jointure non declaree. La trouver.
			$table = $calculer_critere_externe($boucle, $boucle->jointures, $col, $desc, ($crit->cond OR $op !='='), $t);
			list($nom, $desc) = trouver_champ_exterieur($col, $boucle->jointures, $boucle);
			if (count(trouver_champs_decomposes($col,$desc))>1){
				$col_alias = $col; // id_article devient juste le nom d'origine
				$e = decompose_champ_id_objet($col);
				$col = array_shift($e);
				$where_complement = primary_doublee($e, $table);
			}
		}
	}
	// Si la colonne SQL est numerique ou le critere est une date relative
	// forcer une conversion pour eviter un erreur au niveau SQL

	// Si la colonne SQL est numerique ou le critere est une date relative
	// virer les guillemets eventuels qui sont refuses par certains SQL
	// Ne pas utiliser intval, PHP tronquant les Bigint de SQL

	if (($op == '=' OR in_array($op, $table_criteres_infixes))
	AND preg_match("/^\"'(-?\d+)'\"$/", $val[0], $r)
	AND (($desc AND isset($desc['field'][$col]) AND sql_test_int($desc['field'][$col]))
	     OR ($date AND strpos($date[0], '_relatif'))))
			  $val[0] = $r[1];

	// Indicateur pour permettre aux fonctionx boucle_X de modifier 
	// leurs requetes par defaut, notamment le champ statut
	// Ne pas confondre champs de la table principale et des jointures
	if ($table === $boucle->id_table) {
		$boucles[$idb]->modificateur['criteres'][$col] = true;
		if ($col_alias!=$col)
			$boucles[$idb]->modificateur['criteres'][$col_alias] = true;
	}
	
	// ajout pour le cas special d'une condition sur le champ statut:
	// il faut alors interdire a la fonction de boucle
	// de mettre ses propres criteres de statut
	// http://www.spip.net/@statut (a documenter)
	// garde pour compatibilite avec code des plugins anterieurs, mais redondant avec la ligne precedente
	if ($col == 'statut') $boucles[$idb]->statut = true;

	// ajout pour le cas special des forums
	// il faut alors interdire a la fonction de boucle sur forum
	// de selectionner uniquement les forums sans pere

	elseif ($boucles[$idb]->type_requete == 'forums' AND
		($col == 'id_parent' OR $col == 'id_forum'))
		$boucles[$idb]->modificateur['plat'] = true;
	// inserer le nom de la table SQL devant le nom du champ
	if ($table) {
		if ($col[0] == "`") 
		  $arg = "$table." . substr($col,1,-1);
		else $arg = "$table.$col";
	} else $arg = $col;

	// inserer la fonction SQL
	if ($fct) $arg = "$fct($arg$args_sql)";

	return array($arg, $op, $val, $col_alias, $where_complement);
}

// Ne pas appliquer sql_quote lors de la compilation,
// car on ne connait pas le serveur SQL, donc s'il faut \' ou ''

// http://doc.spip.org/@primary_doublee
function primary_doublee($decompose, $table)
{
	$e1 = reset($decompose);
	$e2 = "sql_quote('" . end($decompose) ."')";
	return array("'='","'$table.". $e1 ."'",$e2);
}

// Faute de copie du champ id_secteur dans la table des forums,
// faut le retrouver par jointure
// Pour chaque Row il faudrait tester si le forum est 
// d'article, de breve, de rubrique, ou de syndication.
// Pour le moment on ne traite que les articles,
// les 3 autres cas ne marcheront donc pas: ca ferait 4 jointures
// qu'il faut traiter optimalement ou alors pas du tout.

// http://doc.spip.org/@critere_secteur_forum
function critere_secteur_forum($idb, &$boucles, $val, $crit)
{
	static $trouver_table;
	if (!$trouver_table)
		$trouver_table = charger_fonction('trouver_table', 'base');

	$desc = $trouver_table('articles', $boucles[$idb]->sql_serveur);
	return calculer_critere_externe_init($boucles[$idb], array($desc['table']), 'id_secteur', $desc, $crit->cond, true);
}

// Champ hors table, ca ne peut etre qu'une jointure.
// On cherche la table du champ et on regarde si elle est deja jointe
// Si oui et qu'on y cherche un champ nouveau, pas de jointure supplementaire
// Exemple: criteres {titre_mot=...}{type_mot=...}
// Dans les 2 autres cas ==> jointure 
// (Exemple: criteres {type_mot=...}{type_mot=...} donne 2 jointures
// pour selectioner ce qui a exactement ces 2 mots-cles.

// http://doc.spip.org/@calculer_critere_externe_init
function calculer_critere_externe_init(&$boucle, $joints, $col, $desc, $eg, $checkarrivee = false)
{
	$cle = trouver_champ_exterieur($col, $joints, $boucle, $checkarrivee);
	if ($cle) {
		$t = array_search($cle[0], $boucle->from);
		// transformer eventuellement id_xx en (id_objet,objet)
		$cols = trouver_champs_decomposes($col,$cle[1]); 
		if ($t) {
			$joindre = false;
			foreach($cols as $col){
			  $c = '/\b' . $t  . ".$col" . '\b/';
			  if (trouver_champ($c, $boucle->where)) $joindre = true;
			  else {
			    // mais ca peut etre dans le FIELD pour le Having
			    $c = "/FIELD.$t" .".$col,/";
			    if (trouver_champ($c, $boucle->select)) $joindre = true;
			  }
			}
		  if (!$joindre) return $t;
		}
		$cle = calculer_jointure($boucle, array($boucle->id_table, $desc), $cle, $cols, $eg);
		if ($cle) return $cle;
	}

	erreur_squelette(_T('zbug_info_erreur_squelette'),
			_T('zbug_boucle') .
			" $idb " .
			_T('zbug_critere_inconnu', 
			    array('critere' => $col)));
}

// http://doc.spip.org/@trouver_champ
function trouver_champ($champ, $where)
{
  if (!is_array($where))
 	return preg_match($champ,$where);
  else {
   	 foreach ($where as $clause) {
	   if (trouver_champ($champ, $clause)) return true;
	 }
	 return false;
  }
}


// determine l'operateur et les operandes

// http://doc.spip.org/@calculer_critere_infixe_ops
function calculer_critere_infixe_ops($idb, &$boucles, $crit)
{
	// cas d'une valeur comparee a elle-meme ou son referent
	if (count($crit->param) == 0)
	  { $op = '=';
	    $col = $val = $crit->op;
	    if (preg_match('/^(.*)\.(.*)$/', $col, $r)) $val = $r[2];
	    // Cas special {lang} : aller chercher $GLOBALS['spip_lang']
	    if ($val == 'lang')
	      $val = array(kwote('$GLOBALS[\'spip_lang\']'));
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
	// un critere conditionnel sur date est traite a part
	// car la date est mise d'office par SPIP, 
	      $val = calculer_argument_precedent($idb, $val, $boucles);
        if ($crit->cond AND ($col == "date" OR $col == "date_redac")) {
		      if($val == "\$Pile[0]['".$col."']") {
            $val = "(\$Pile[0]['{$col}_default']?'':$val)";
          }
		    }
	      $val = array(kwote($val));
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
	    $desc = array('id_mere' => $idb);
	    $parent = $boucles[$idb]->id_parent;

			// Dans le cas {x=='#DATE'} etc, defaire le travail du phraseur, 
			// celui ne sachant pas ce qu'est un critere infixe
			// et a fortiori son 2e operande qu'entoure " ou '
			if (count($params)==1
					AND count($params[0]==3)
					AND $params[0][0]->type == 'texte' 
					AND @$params[0][2]->type == 'texte' 
					AND ($p=$params[0][0]->texte) == $params[0][2]->texte
					AND (($p == "'") OR ($p == '"'))
					AND $params[0][1]->type == 'champ' ) {
				$val[]= "$p\\$p#" . $params[0][1]->nom_champ . "\\$p$p";
			}
			else 
				foreach ((($op != 'IN') ? $params : calculer_vieux_in($params)) as $p) {
					$a = calculer_liste($p, $desc, $boucles, $parent);
					if ($op == 'IN') $val[]= $a;
					else $val[]=kwote($a);
				}
	}

	$fct = $args_sql = '';
	// fonction SQL ?
	if (preg_match('/^(.*)' . SQL_ARGS . '$/', $col, $m)) {
	  $fct = $m[1];
	  preg_match('/^\(([^,]*)(.*)\)$/', $m[2], $a);
	  $col = $a[1];
	  if (preg_match('/^(\S*)(\s+AS\s+.*)$/i', $col, $m)) {
	    $col=$m[1];
	    $args_sql = $m[2];
	  }
	  $args_sql .= $a[2];;
	}
//var_dump(array($fct,$col,$op,$val,$args_sql));
	return array($fct, $col, $op, $val, $args_sql);
}

// compatibilite ancienne version

// http://doc.spip.org/@calculer_vieux_in
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

// http://doc.spip.org/@calculer_critere_infixe_date
function calculer_critere_infixe_date($idb, &$boucles, $regs)
{
	global $table_date; 
	$boucle = $boucles[$idb];
	list(,$col, $rel, $suite) = $regs;
	$date_orig = $pred = isset($table_date[$boucle->type_requete])?$table_date[$boucle->type_requete]:'date';
	if ($suite) {
	# Recherche de l'existence du champ date_xxxx,
	# si oui choisir ce champ, sinon choisir xxxx
		$t = $boucle->show;
		if ($t['field']["date$suite"])
			$date_orig = 'date'.$suite;
		else
			$date_orig = substr($suite, 1);
		$pred = $date_orig;
	} else if ($rel) $pred = 'date';

	$date_compare = "\"' . normaliser_date(" .
	      calculer_argument_precedent($idb, $pred, $boucles) .
	      ") . '\"";
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

// http://doc.spip.org/@calculer_param_date
function calculer_param_date($date_compare, $date_orig) {
	if (preg_match(",'\" *\.(.*)\. *\"',", $date_compare, $r)) {
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

// http://doc.spip.org/@tester_param_date
function tester_param_date($type, $col)
{
	global $table_date;
	if (isset($table_date[$type]) 
	AND preg_match(",^((age|jour|mois|annee)_relatif|date|mois|annee|jour|heure|age)(_[a-z]+)?$,", $col, $regs))
	  return $regs;
	else return false;
}

?>
