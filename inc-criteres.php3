<?php

//
// Definition des {criteres} d'une boucle
//

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CRITERES")) return;
define("_INC_CRITERES", "1");


// {racine}
// http://www.spip.net/@racine
function critere_racine_dist($param, $not, &$boucle, $infos) {
	global $table_des_tables;

	if ($param != 'racine' OR $not)
		return array(_T('info_erreur_squelette'), $param);

	$boucle->where[] = $infos['id_table'].".id_parent='0'";

}

// {exclus}
// http://www.spip.net/@exclus
function critere_exclus_dist($param, $not, &$boucle, $infos) {

	if ($param != 'exclus' OR $not)
		return array(_T('info_erreur_squelette'), $param);

	$boucle->where[] = $infos['id_field']."!='\"."
	. calculer_argument_precedent($infos['idb'],
		$infos['primary'], $infos['boucles']) . ".\"'";

}

// {doublons} ou {unique}
// http://www.spip.net/@doublons
function critere_doublons_dist($param, $not, &$boucle, $infos) {

	if (!preg_match("/(doublons|unique)[[:space:]]*([a-z_0-9]*)/i",
	$param, $match))
		return array(_T('info_erreur_squelette'), $param);

	$boucle->doublons = $infos['type'].$match[2];
	$boucle->where[] = '" .' .
		"calcul_mysql_in('".$infos['id_field']."', "
		.'"0".$doublons[\''.$boucle->doublons."'], 'NOT') . \"";
}

// {lang_select}
// http://www.spip.net/@lang_select
function critere_lang_select_dist($param, $not, &$boucle, $infos) {
	if (preg_match('/lang_select(=(oui|non))?$/i', $param, $match)) {
		if (!$lang_select = $match[2])
			$lang_select = 'oui';
		if ($not)
			$lang_select = ($lang_select=='oui')?'non':'oui';
		$boucle->lang_select = $lang_select;
	}
	else return array(_T('info_erreur_squelette'), $param);
}

// {debut_xxx}
// http://www.spip.net/@debut_
function critere_debut_dist($param, $not, &$boucle, $infos) {
	if (ereg('^debut([-_a-zA-Z0-9]+),([0-9]*)$', $param, $match)) {
		$debut_lim = "debut".$match[1];
		$boucle->limit =
			'intval($GLOBALS["'.$debut_lim.'"]).",'.$match[2] .'"' ;
	}
	else return array(_T('info_erreur_squelette'), $param);
}

// {recherche}
// http://www.spip.net/@recherche
function critere_recherche_dist($param, $not, &$boucle, $infos) {
	$boucle->from[] = "index_".$infos['id_table']." AS rec";
	$boucle->select[] = 'SUM(rec.points + 100*(" .' . 
		'calcul_mysql_in("rec.hash",
		calcul_branche($hash_recherche_strict),"") . "))
		AS points';

	// horrible hack du aux id_forum = spip_forum et id_article=spip_articleS
	// en fait il faudrait la fonction inverse de table_objet()
	$id = 'id_'.preg_replace('/s$/', '', $infos['id_table']);

	// rec.id_article = articles.id_article
	$boucle->where[] = "rec.".$id
	. "=" . $infos['id_table'].'.'.$id;

	// group by articles.id_article
	$boucle->group = $infos['id_field'];

	// et la recherche trouve
	$boucle->where[] = '" .' . 'calcul_mysql_in("rec.hash",
		calcul_branche($hash_recherche),"") . "';

	// oui cette boucle est une boucle recherche, le noter dans la pile
	// (certes, c'est un peu lourd comme ecriture)
	$infos['boucles'][$infos['idb']]->hash = true;
}

// {inverse}
// http://www.spip.net/@inverse
function critere_inverse_dist($param, $not, &$boucle, $infos) {
	// Classement par ordre inverse
	if ($param == 'inverse' AND !$not) {
		if ($boucle->order)
			$boucle->order .= ".' DESC'";
		else 
			return array(_L("inversion d'un ordre inexistant"), 
				     "BOUCLE" . $infos['idb']);
	} else
		return array(_T('info_erreur_squelette'), $param);
}

// {traduction}
// http://www.spip.net/@traduction
function critere_traduction_dist($param, $not, &$boucle, $infos) {
	if ($param == 'traduction') {
		$boucle->where[] = $infos['id_table'].".id_trad > 0";
		$boucle->where[] = $infos['id_table'].".id_trad ='\"."
		. calculer_argument_precedent($infos['idb'], 'id_trad',
			$infos['boucles'])
		. ".\"'";

	} else
		return array(_T('info_erreur_squelette'), $param);
}

// {origine_traduction}
// http://www.spip.net/@origine_traduction
function critere_origine_traduction_dist($param, $not, &$boucle, $infos) {
	if ($param == 'origine_traduction')
		$boucle->where[] = $infos['id_table'].".id_trad = "
		. $infos['id_field'];
	else
		return array(_T('info_erreur_squelette'), $param);
}


// {meme_parent}
// http://www.spip.net/@meme_parent
function critere_meme_parent_dist($param, $not, &$boucle, $infos) {
	if ($param != 'meme_parent')
		return array(_T('info_erreur_squelette'), $param);
	else {
		if ($infos['type'] == 'rubriques') {
			$boucle->where[] = $infos['id_table'].".id_parent='\"."
			. calculer_argument_precedent($infos['idb'], 'id_parent',
			$infos['boucles'])
			. ".\"'";
		} else if ($infos['type'] == 'forums') {
			$boucle->where[] = $infos['id_table'].".id_parent='\"."
			. calculer_argument_precedent($infos['idb'], 'id_parent',
			$infos['boucles'])
			. ".\"'";
			$boucle->where[] = $infos['id_table'].".id_parent > 0";
			$boucle->plat = true;
		} else
			return array(_L("{meme_parent} ne s'applique qu'aux boucles (FORUMS) ou (RUBRIQUES)"), "BOUCLE" . $infos['idb']);
	}
}

// {branche ?}
// http://www.spip.net/@branche
function critere_branche_dist($param, $not, &$boucle, $infos) {
	if (preg_match('/branche[[:space:]]*([?])?$/i', $param, $regs)) {
		$c = "calcul_mysql_in('".$infos['id_table'].".id_rubrique',
		calcul_branche(" . calculer_argument_precedent($infos['idb'],
		'id_rubrique', $infos['boucles']) . "), '')";
		if (!$regs[1])
			$where = "\". $c .\"" ;
		else
			$where = "\".("
			. calculer_argument_precedent($infos['idb'], 'id_rubrique',
			$infos['boucles'])."? $c : 1).\"";

		if ($not)
			$boucle->where[] = "NOT($where)";
		else
			$boucle->where[] = "$where";
	} else
		return array(_T('info_erreur_squelette'), $param);
}

// Tri : {par xxxx}
// http://www.spip.net/@par
function critere_par_dist($param, $not, &$boucle, $infos) {
	if ($not)
		return array(_T('info_erreur_squelette'), $param);

	preg_match('/par[[:space:]]*(.*)/ims', $param, $regs);
	$tri = trim($regs[1]);

	// par hasard
	if ($tri == 'hasard') {
		// on pourrait peut-etre passer a "RAND() AS alea" ?
		$boucle->select[] = "MOD(".$infos['id_field']." * UNIX_TIMESTAMP(),
		32767) & UNIX_TIMESTAMP() AS alea";
		$boucle->order = "'alea'";
	}

	// par titre_mot
	else if ($tri == 'titre_mot') {
		$boucle->order= "'mots.titre'";
	}

	// par type_mot
	else if ($tri == 'type_mot'){
		$boucle->order= "'mots.type'";
	}
	// par points
	else if ($tri == 'points'){
		$boucle->order= "'points'";
	}
	// par num champ(, suite)
	else if (ereg("^num[[:space:]]+([^,]*)(,.*)?",$tri, $match2)) {
		$boucle->select[] = "0+".$infos['id_table'].".".$match2[1]." AS num";
		$boucle->order = "'num".texte_script($match2[2])."'";
	}
	// par champ
	else if (ereg("^[a-z][a-z0-9]*$", $tri)) {
		if ($tri == 'date')
			$tri = $GLOBALS['table_date'][$infos['type']];
		$boucle->order = "'".$infos['id_table'].".".$tri."'";
	}
	// tris par critere bizarre
	// (formule composee, virgules, etc).
	// autoriser le hack {par $GLOBALS["tri"]}
	else
		$boucle->order = "\"$tri\"";
}




//
// Construire un tableau des tables de relations,
// Ex: gestion du critere {id_mot} dans la boucle(ARTICLES)
//
function relations_externes ($type, $col) {
	static $tables_relations = array(
	'articles' => array (
		'id_mot' => 'mots_articles',
		'id_auteur' => 'auteurs_articles',
		'id_document' => 'documents_articles'
		),

	'auteurs' => array (
		'id_article' => 'auteurs_articles'
		),

	'breves' => array (
		'id_mot' => 'mots_breves',
		'id_document' => 'documents_breves'
		),

	'documents' => array (
		'id_article' => 'documents_articles',
		'id_rubrique' => 'documents_rubriques',
		'id_breve' => 'documents_breves'
		),

	'forums' => array (
		'id_mot' => 'mots_forum',
		),

	'mots' => array (
		'id_article' => 'mots_articles',
		'id_breve' => 'mots_breves',
		'id_forum' => 'mots_forum',
		'id_rubrique' => 'mots_rubriques',
		'id_syndic' => 'mots_syndic'
		),

	'groupes_mots' => array (
		'id_groupe' => 'mots'
		),

	'rubriques' => array (
		'id_mot' => 'mots_rubriques',
		'id_document' => 'documents_rubriques'
		),

	'syndication' => array (
		'id_mot' => 'mots_syndic'
		)
	);

	return $tables_relations[$type][$col];
}




//
// La fonction qui appelle les criteres_xxx et traite les cas specifiques
// comme {1,4}, etc.
//
function calculer_criteres ($idb, &$boucles) {
  global $tables_relations, $table_primary, $table_des_tables, $table_date, $tables_des_serveurs_sql;
	$boucle = &$boucles[$idb];				# nom de la boucle
	$params = $boucle->param;
	if (!is_array($params)) return;	// rien a faire

	$type = $boucle->type_requete;			# articles
	$serveur = $boucle->sql_serveur;
	$id_table = $table_des_tables[$type];	# articles ->   'table';
	if ($id_table) {
		$primary = $table_primary[$type];		# id_article -> 'id'
	} else { // table non Spip. Pas mal l'indexation, hein ?
		$id_table = $type;
		$primary = $tables_des_serveurs_sql[$serveur ? $serveur : 'localhost'][$type]['key']["PRIMARY KEY"]; 
	}
	$id_field = $id_table . "." . $primary; # articles.id_article -> 'table_id'
	// les infos complementaires a passer aux fonctions critere_xxx
	$infos = array(
		'type' => $type,			# (articles)
		'id_table' => $id_table,	# 'table'
		'id_field' => $id_field,	# 'table_id'
		'primary' => $primary,		# 'id'
		'boucles' => &$boucles,		# boucles
		'idb' => $idb				# 'nom_boucle'
	);


	// Boucle hierarchie, supprimer le critere id_article/id_rubrique/id_syndic
	// qui est superfetatoire (mais indique dans la doc)
	if ($type == 'hierarchie') {
		$params2 = array();
		foreach($params as $param)
			if (!ereg('^id_(article|syndic|rubrique)$', $param))
				$params2[]=$param;
		$params = $params2;
	}

	//
	// Traitement de la liste des criteres
	//
	foreach($params as $param) {

		// Analyse du critere
		preg_match("/^([!]?)[[:space:]]*(debut|([a-z_]+))/ism",
			$param, $match);
		$critere = $match[2];
		$not = ($match[1] == '!');

		// synonymes ?
		$synonymes = array('unique'=>'doublons');
		if ($synonymes[$critere]) $critere = $synonymes[$critere];


		// critere personnalise ?
		$f = "critere_".$critere;
		if (!function_exists($f))
			$f .= '_dist';

		// fonction critere standard ?
		if (function_exists($f)) {
			$res = $f($param, $not, $boucle, $infos);
			if (is_array($res)) return $res; # erreur
		}
		
		# Criteres a passer en fonction critere_xxx_dist
		else if (ereg('^([0-9]+)/([0-9]+)$', $param, $match)) {
			$boucle->partie = $match[1];
			$boucle->total_parties = $match[2];
			$boucle->mode_partie = '/';
		}
		else if (ereg('^(([0-9]+)|n)(-([0-9]+))?,(([0-9]+)|n)(-([0-9]+))?$', $param, $match)) {
			if (($match[2]!='') && ($match[6]!=''))
				$boucle->limit = $match[2].','.$match[6];
			else {
				$boucle->partie =
					($match[1] != 'n') ? $match[1] :
					($match[4] ? $match[4] : 0);
				$boucle->total_parties =
					($match[5] != 'n') ? $match[5] :
					($match[8] ? $match[8] : 0);
				$boucle->mode_partie =
				(($match[1]=='n')?'-':'+').(($match[5]=='n')?'-':'+');
			}
		}

		// Restriction de valeurs (implicite ou explicite)
		else if (eregi('^([a-z_]+\(?[a-z_]*\)?) *(\??)((!?)(<=?|>=?|==?|IN) *"?([^<>=!"]*))?"?$', $param, $match)) {
			$op = $match[5];
			// Variable comparee
			$col = $match[1];
			$fct = '';
			if (ereg("([a-z_]+)\(([a-z_]+)\)", $col,$match3))
			  {
				$col = $match3[2];
				$fct = $match3[1];
			  }
			$col_table = $id_table;
			// Valeur de comparaison
			if ($match[3]) {
				if (strtoupper($op) != 'IN') {
					$val = calculer_param_dynamique($match[6], $boucles, $idb);
					if (is_array($val)) return $val;
				}
				else {
				// traitement special des valeurs textuelles
					ereg("^ *\(?(.*[^)])\)? *$",$match[6], $val2);
					$val2 = split(" *, *", $val2[1]);
					foreach ($val2 as $v) {
						$v = calculer_param_dynamique($v, $boucles, $idb);
						if (is_array($v)) return $v;
						if (strpos('"0123456789',$v[0]) !== false)
							$val3[] = $v;
						else
							$val3[] = "'$v'";
					}
					$val = join(',', $val3);
				}
			}
			
			else {
				$val = $match[1];
				// Si id_parent, comparer l'id_parent avec l'id_objet
				// de la boucle superieure
				if ($val == 'id_parent')
					$val = $primary;
				// Si id_enfant, comparer l'id_objet avec l'id_parent
				// de la boucle superieure
				else if ($val == 'id_enfant')
					$val = 'id_parent';
				$val = calculer_argument_precedent($idb, $val, $boucles) ;
				if (ereg('^\$',$val))
					$val = '" . addslashes(' . $val . ') . "';
				else
					$val = addslashes($val);

			}

			// Traitement general des relations externes
			if ($s = relations_externes($type, $col)) {
				$col_table = $s;
				$boucle->from[] = "$col_table AS $col_table";
				$boucle->where[] = "$id_field=$col_table." . $primary;
				$boucle->group = $id_field;
				$boucle->lien = true;
			}
			// Cas particulier pour les raccourcis 'type_mot' et 'titre_mot'
			else if ($type != 'mots'
			AND ($col == 'type_mot' OR $col == 'titre_mot'
			OR $col == 'id_groupe')) {
				if ($type == 'forums')
					$col_lien = "forum";
				else if ($type == 'syndication')
					$col_lien = "syndic";
				else
					$col_lien = $type;
				$boucle->from[] = "mots_$col_lien AS lien_mot";
				$boucle->from[] = 'mots AS mots';
				$boucle->where[] = "$id_field=lien_mot." . $primary;
				$boucle->where[] = 'lien_mot.id_mot=mots.id_mot';
				$boucle->group = $id_field;
				$col_table = 'mots';

				$boucle->lien = true;
				if ($col == 'type_mot')
					$col = 'type';
				else if ($col == 'titre_mot')
					$col = 'titre';
				else if ($col == 'id_groupe')
					$col = 'id_groupe';
			}

			// Cas particulier : selection des documents selon l'extension
			if ($type == 'documents' AND $col == 'extension')
				$col_table = 'types_documents';
			// HACK : selection des documents selon mode 'image'
			// (a creer en dur dans la base)
			else if ($type == 'documents' AND $col == 'mode'
			AND $val == 'image')
				$val = 'vignette';
			// Cas particulier : lier les articles syndiques
			// au site correspondant
			else if ($type == 'syndic_articles' AND
			!ereg("^(id_syndic_article|titre|url|date|descriptif|lesauteurs)$",$col))
				$col_table = 'syndic';

			// Cas particulier : id_enfant => utiliser la colonne id_objet
			if ($col == 'id_enfant')
				$col = $primary;
			// Cas particulier : id_secteur = id_rubrique pour certaines tables
			if (($type == 'breves' OR $type == 'forums') AND $col == 'id_secteur')
				$col = 'id_rubrique';

			// Cas particulier : expressions de date
			if (ereg("^(date|mois|annee|age|age_relatif|jour_relatif|mois_relatif|annee_relatif)(_redac)?$", $col, $regs)) {
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

				if ($col == 'date')
					$col = $date_orig;
				else if ($col == 'mois') {
					$col = "MONTH($date_orig)";
					$col_table = '';
				}
				else if ($col == 'annee') {
					$col = "YEAR($date_orig)";
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

			// Operateur de comparaison

			if (!$op)
				$op = '=';
			else if ($op == '==')
				$op = 'REGEXP';
			else if (strtoupper($op) == 'IN') {
				// traitement special des valeurs textuelles
				$where = "$col IN ($val)";
				if ($match[4] == '!') {
					$where = "NOT ($where)";
				} else {
					if (!$boucle->order) {
						$boucle->order = 'rang';
						$boucle->select[] =
						"FIND_IN_SET($col, \\\"$val\\\") AS rang";
					}
				}
				$boucle->where[] = $where;
				$op = '';
			}

			if ($col_table)
				$col = "$col_table.$col";

			if ($op) {
				if ($fct) $col = "$fct($col)";
				if ($match[4] == '!')
					$where = "NOT ($col $op '$val')";
				else
					$where = "($col $op '$val')";

				// operateur optionnel {lang?}
				if ($match[2]) {
					$champ = calculer_argument_precedent($idb, $match[1], $boucles) ;
					$where = "\".($champ ? \"$where\" : 1).\"";
				}

				$boucle->where[] = $where;
			}

		} // fin du if sur les restrictions de valeurs

		// Special rubriques
		else if ($param == 'meme_parent') {
			$boucle->where[] = "$id_table.id_parent='\"." .
				calculer_argument_precedent($idb, 'id_parent', $boucles) . ".\"'";
			if ($type == 'forums') {
				$boucle->where[] = "$id_table.id_parent > 0";
				$boucle->plat = true;
			}
		}
		else if (ereg("^branche *(\??)", $param, $regs)) {
			$c = "calcul_mysql_in('$id_table.id_rubrique',
			calcul_branche(" . calculer_argument_precedent($idb, 'id_rubrique',
			$boucles) . "), '')";
			if (!$regs[1])
				$boucle->where[] = "\". $c .\"" ;
			else
				$boucle->where[] = "\".(".calculer_argument_precedent($idb, 'id_rubrique', $boucles)."? $c : 1).\"";
		}
		// Selection du classement
		else if (ereg('^par[[:space:]]+([^}]*)$', $param, $match)) {
			$tri = trim($match[1]);
			if ($tri == 'hasard') { // par hasard
				$boucle->select[] = "MOD($id_field * UNIX_TIMESTAMP(),
				32767) & UNIX_TIMESTAMP() AS alea";
				$boucle->order = 'alea';
			}
			else if ($tri == 'titre_mot') { // par titre_mot
				$boucle->order= 'mots.titre';
			}
			else if ($tri == 'type_mot'){ // par type_mot
				$boucle->order= 'mots.type';
			}
			else if ($tri == 'points'){ // par points
				$boucle->order= 'points';
			}
			else if (ereg("^num[[:space:]]+([^,]*)(,.*)?",$tri, $match2)) {
				// par num champ
				$boucle->select[] = "0+$id_table.".$match2[1]." AS num";
				$boucle->order = "num".$match2[2];
			}
			else if (ereg("^[a-z0-9]+$", $tri)) { // par champ
				if ($tri == 'date')
					$tri = $table_date[$type];
				$boucle->order = "$id_table.$tri";
			}
			else { 
				// tris par critere bizarre
				// (formule composee, virgules, etc).
				$boucle->order = $tri;
			}
		}
	}
}


function calculer_param_date($date_compare, $date_orig) {
	return
	"LEAST((UNIX_TIMESTAMP(" .
	$date_compare .
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

//
// Calculer les parametres
//
function calculer_param_dynamique($val, &$boucles, $idb) {
	if (ereg(NOM_DE_CHAMP, $val, $regs)) {
	  	$champ = new Champ;
		$champ->nom_boucle = $regs[2];
		$champ->nom_champ = $regs[3];
		$champ->etoile = $regs[4];
		$champ->id_boucle = $idb;
		$champ->boucles = &$boucles;
		$champ->id_mere = $idb;
		$champ = calculer_champ($champ);
		return '" . addslashes(' . $champ . ') . "';
/*
		if (ereg("[$]Pile[[][^]]+[]][[]'[^]]*'[]]", $champ, $v))
			return $v[0];
			else return $val; */
	} else {
		if (ereg('^\$(.*)$',$val,$regs))
		  return '" . addslashes($Pile[0][\''. $regs[1] ."']) . \"";
		else
		  return addslashes($val);
	}
}


?>
