<?php

//
// Definition des {criteres} d'une boucle
//

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CRITERES")) return;
define("_INC_CRITERES", "1");


// {racine}
// http://www.spip.net/@racine
function critere_racine_dist($idb, &$boucles, $param, $not) {
	$boucle = &$boucles[$idb];

	if ($param != 'racine' OR $not)
		erreur_squelette(_T('info_erreur_squelette'), $param);

	$boucle->where[] = $boucle->id_table.".id_parent='0'";

}

// {exclus}
// http://www.spip.net/@exclus
function critere_exclus_dist($idb, &$boucles, $param, $not) {
	$boucle = &$boucles[$idb];
	$id = $boucle->primary;

	if ($param != 'exclus' OR $not OR !$id)
		erreur_squelette(_T('info_erreur_squelette'), $param);

	$arg = calculer_argument_precedent($idb,$id, $boucles);
	$boucle->where[] = $boucle->id_table . '.' . $id."!='\"." . $arg . ".\"'";

}

// {doublons} ou {unique}
// http://www.spip.net/@doublons
function critere_doublons_dist($idb, &$boucles, $param, $not) {
	$boucle = &$boucles[$idb];

	if (!preg_match("/(doublons|unique)[[:space:]]*([a-z_0-9]*)/i",
	$param, $match))
		erreur_squelette(_T('info_erreur_squelette'), $param);

	$boucle->doublons = $boucle->type_requete . $match[2];
	$boucle->where[] = '" .' .
		"calcul_mysql_in('".$boucle->id_table . '.' . $boucle->primary."', "
		.'"0".$doublons[\''.$boucle->doublons."'], 'NOT') . \"";
}

// {lang_select}
// http://www.spip.net/@lang_select
function critere_lang_select_dist($idb, &$boucles, $param, $not) {
	$boucle = &$boucles[$idb];
	if (preg_match('/lang_select(=(oui|non))?$/i', $param, $match)) {
		if (!$lang_select = $match[2])
			$lang_select = 'oui';
		if ($not)
			$lang_select = ($lang_select=='oui')?'non':'oui';
		$boucle->lang_select = $lang_select;
	}
	else erreur_squelette(_T('info_erreur_squelette'), $param);
}

// {debut_xxx}
// http://www.spip.net/@debut_
function critere_debut_dist($idb, &$boucles, $param, $not) {
	$boucle = &$boucles[$idb];
	if (ereg('^debut([-_a-zA-Z0-9]+),([0-9]*)$', $param, $match)) {
		$debut_lim = "debut".$match[1];
		$boucle->limit =
			'intval($GLOBALS["'.$debut_lim.'"]).",'.$match[2] .'"' ;
	}
	else erreur_squelette(_T('info_erreur_squelette'), $param);
}

// {recherche}
// http://www.spip.net/@recherche
function critere_recherche_dist($idb, &$boucles, $param, $not) {
	$boucle = &$boucles[$idb];

	$table = $boucle->id_table;	#articles
	$id_table = 'id_'.preg_replace('/s$/', '', $table); 	#id_article

	// horrible hack du aux id_forum = spip_forum et id_article=spip_articleS
	// en fait il faudrait la fonction inverse de table_objet()
	$id = 'id_'.preg_replace('/s$/', '', $boucle->id_table);

	$boucle->select[] = $boucle->id_table . '.' . $boucle->primary; # pour postgres, neuneu ici
	$boucle->select[] = '$rech_select'; # pour les ... as points

	// et la recherche trouve
	$boucle->where[] = '$rech_where';
}

// {inverse}
// http://www.spip.net/@inverse
function critere_inverse_dist($idb, &$boucles, $param, $not) {
	$boucle = &$boucles[$idb];
	// Classement par ordre inverse
	if ($param == 'inverse' AND !$not) {
		if ($boucle->order)
			$boucle->order .= ".' DESC'";
		else 
			erreur_squelette(_L("inversion d'un ordre inexistant"), 
			"BOUCLE" . $idb);
	} else
		erreur_squelette(_T('info_erreur_squelette'), "{$param} BOUCLE$idb");
}

// {traduction}
// http://www.spip.net/@traduction
//   (id_trad>0 AND id_trad=id_trad(precedent))
//    OR id_article=id_article(precedent)
function critere_traduction_dist($idb, &$boucles, $param, $not) {
	$boucle = &$boucles[$idb];
	if ($param == 'traduction') {
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
	} else
		erreur_squelette(_T('info_erreur_squelette'), $param);
}

// {origine_traduction}
// http://www.spip.net/@origine_traduction
function critere_origine_traduction_dist($idb, &$boucles, $param, $not) {
	$boucle = &$boucles[$idb];
	if ($param == 'origine_traduction')
		$boucle->where[] = $boucle->id_table.".id_trad = "
		. $boucle->id_table . '.' . $boucle->primary;
	else
		erreur_squelette(_T('info_erreur_squelette'), $param);
}


// {meme_parent}
// http://www.spip.net/@meme_parent
function critere_meme_parent_dist($idb, &$boucles, $param, $not) {
	$boucle = &$boucles[$idb];
	if ($param != 'meme_parent')
		erreur_squelette(_T('info_erreur_squelette'), $param);
	else {
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
		} else
			erreur_squelette(_L("{meme_parent} ne s'applique qu'aux boucles (FORUMS) ou (RUBRIQUES)"), "BOUCLE" . $idb);
	}
}

// {branche ?}
// http://www.spip.net/@branche
function critere_branche_dist($idb, &$boucles, $param, $not) {
	$boucle = &$boucles[$idb];
	if (preg_match('/branche[[:space:]]*([?])?$/i', $param, $regs)) {
		$c = "calcul_mysql_in('".$boucle->id_table.".id_rubrique',
		calcul_branche(" . calculer_argument_precedent($idb,
		'id_rubrique', $boucles) . "), '')";
		if (!$regs[1])
			$where = "\". $c .\"" ;
		else
			$where = "\".("
			. calculer_argument_precedent($idb, 'id_rubrique',
			$boucles)."? $c : 1).\"";

		if ($not)
			$boucle->where[] = "NOT($where)";
		else
			$boucle->where[] = $where;
	} else
		erreur_squelette(_T('info_erreur_squelette'), $param);
}

// Tri : {par xxxx}
// http://www.spip.net/@par
function critere_par_dist($idb, &$boucles, $param, $not) {
	$boucle = &$boucles[$idb];
	if ($not)
		erreur_squelette(_T('info_erreur_squelette'), $param);

	$param = substr($param,3);

	while ($param) {
	  spip_log($param);
		preg_match('/[[:space:]]*([^,]*)[[:space:]]*,?(.*)/ims',
			   $param, $regs);
		$param = $regs[2];
		$tri = trim($regs[1]);

	// par hasard
		if ($tri == 'hasard') {
		// tester si cette version de MySQL accepte la commande RAND()
		// sinon faire un gloubi-boulga maison avec de la mayonnaise.
		  if (spip_query("SELECT RAND()"))
			$boucle->select[] = "RAND() AS alea";
		  else
			$boucle->select[] = "MOD(".$boucle->id_table.'.'.$boucle->primary
			." * UNIX_TIMESTAMP(),32767) & UNIX_TIMESTAMP() AS alea";

		$order = "'alea'";
		}

	// par titre_mot
		else if ($tri == 'titre_mot') {
		  $order= "'mots.titre'";
		}

	// par type_mot
		else if ($tri == 'type_mot'){
		  $order= "'mots.type'";
		}
	// par num champ(, suite)
		else if (ereg("^num[[:space:]]+(.*)$",$tri, $match2)) {
		  $boucle->select[] = "0+".$boucle->id_table.".".$match2[1]." AS num";
		  $order = "'num'";
	}
	// par champ. Verifier qu'ils sont presents.
		else if (ereg("^[a-z][a-z0-9]*$", $tri)) {
		  if ($tri == 'date')
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
			if ($desc['field'][$tri])
				$order = "'".$boucle->id_table.".".$tri."'";
			else {
			  // tri sur les champs synthetises (cf points)
				$order = "'".$tri."'";
			}
		  }
		}
	// tris specifies par l'URL ?
		else {
		  $order = simplifie_param_dynamique($tri, $boucles, $idb);
		  if ($order == $tri) $order = "'" . $order . "'";
		}
	// au final, gestion des tris multiples
		if ($order) {
		  if ($boucle->order) $boucle->order .= '.",".';
		  $boucle->order .= $order;

		}
	}
}



function calculer_critere_parties($idb, &$boucles, $param, $not, $match) {
  global $tables_relations, $table_date, $tables_des_serveurs_sql;

	$boucle = &$boucles[$idb];
	list(,$a1,$op,$a2) = $match;
	list($a11,$a12) = calculer_critere_parties_aux($idb, $boucles, $a1);
	list($a21,$a22) = calculer_critere_parties_aux($idb, $boucles, $a2);

	if (($op== ',')&&(is_numeric($a11) && (is_numeric($a21))))
		$boucle->limit = $a11 .',' . $a21;
	else {
	  $boucle->partie =	($a11 != 'n') ? $a11 : $a12;
		$boucle->total_parties =  ($a21 != 'n') ? $a21 : $a22;
		$boucle->mode_partie = (($op == '/') ? '/' :
				(($a1=='n')?'-':'+').(($a2=='n')?'-':'+'));
	}
}

function calculer_critere_parties_aux($idb, &$boucles, $param) {
	ereg('^(([0-9]+)|n|(#.*))(-([0-9]+))?$', $param, $m);
	if ($m[1] == 'n') 
	  $a = 'n';
	else {
	  $a = simplifie_param_dynamique($m[1], $boucles, $idb);
	}
	return array($a, ($m[5] ? $m[5] : 0));
}

//
// La fonction d'aiguillage sur le nom du criteres
//

function calculer_criteres ($idb, &$boucles) {

	foreach($boucles[$idb]->param as $param) {
		// Analyse du critere
		preg_match("/^([!]?)[[:space:]]*(debut|([a-z_]+))/ism",
			$param, $match);
		$critere = $match[2];
		$not = ($match[1] == '!');

		// critere personnalise ?
		$f = "critere_".$critere;
		if (!function_exists($f))
			$f .= '_dist';

		// fonction critere standard ?
		if (!function_exists($f))
			$f = 'calculer_critere_DEFAUT';

		// Applique le critere
		$res = $f($idb, $boucles, $param, $not);

		// gestion d'erreur
		if (is_array($res)) erreur_squelette($res);
	}
}

# Criteres numeriques et de comparaison

function calculer_critere_DEFAUT($idb, &$boucles, $param, $not) {
	global $table_date, $tables_des_serveurs_sql;

	$boucle = &$boucles[$idb];
	$type = $boucle->type_requete;
	$id_table = $boucle->id_table;
	$primary = $boucle->primary;
	$id_field = $id_table . '.' . $primary; 

	if (ereg('^([0-9a-zA-Z#_\{\}]+)([,/])([0-9a-zA-Z#_\{\}]+)$', $param, $match))
	  calculer_critere_parties($idb, $boucles, $param, $not, $match);

		// Restriction de valeurs (implicite ou explicite)
	else if (eregi('^([a-z_]+\(?[a-z_]*\)?) *(\??)((!?)(<=?|>=?|==?|IN) *"?([^<>=!"]*))?"?$', $param, $match)) {
	  		$op = $match[5] ? $match[5] : '=';
			// Variable comparee
			$col = $match[1];
			// fonction SQL
			$fct = '';
			if (ereg("([a-z_]+)\(([a-z_]+)\)", $col,$match3)) {
				$col = $match3[2];
				$fct = $match3[1];
			}
			$col_table = $id_table;
			// Valeur de comparaison
			if ($match[3]) {
				if (strtoupper($op) != 'IN') {
					$val = calculer_param_dynamique($match[6], $boucles, $idb);
					// gestion d'erreur
					if (is_array($val)) erreur_squelette($val);
				}
				else {
				// traitement special des valeurs textuelles
				  $val = calculer_params_dynamiques($match[6], $boucles, $idb);
				}
			}
			
			else {
				$val = $match[1];

				// Cas special {lang} : aller chercher $GLOBALS['spip_lang']
				if ($val == 'lang')
					$val = '".$GLOBALS[\'spip_lang\']."';
				else {
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

			}

			if ($s = calculer_critere_externe($boucle, $id_field,$col, $type))
				$col_table = $s;

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
				$boucle->from[] = "spip_mots_$col_lien AS lien_mot";
				$boucle->from[] = 'spip_mots AS mots';
				$boucle->where[] = "$id_field=lien_mot." . $primary;
				$boucle->where[] = 'lien_mot.id_mot=mots.id_mot';
				$boucle->group = $id_field;
				$boucle->select[] = $id_field; # pour postgres, neuneu ici
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
			if ($col_table)
				$col = "$col_table.$col";

			if (strtoupper($op) == 'IN') {
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
			} else {
				if ($op == '==') $op = 'REGEXP';
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

			}
			$boucle->where[] = $where;
	} // fin du if sur les restrictions de valeurs
	else erreur_squelette(_L("critere inconnu $param"));
}

// traitement des relations externes par une jointure.
// tant qu'a faire eviter de dupliquer sa declaration,
// mais c'est une goutte d'eau dans la mer

function calculer_critere_externe(&$boucle, $id_field, $col, $type)
{
	global $tables_relations;
	if ($col_table =  $tables_relations[$type][$col]) {
		$externe = "$id_field=$col_table." . $boucle->primary;
		if (!$boucle->where || (!in_array($externe, $boucle->where))) {

			$boucle->lien = true;
			$boucle->from[] = "spip_$col_table AS $col_table";
			$boucle->where[] = $externe;
			$boucle->group = $id_field;
		// postgres exige que le champ pour GROUP soit dans le SELECT
			$boucle->select[] = $id_field;
		}
	}
	return $col_table;
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
#	if (ereg('^ *\((.*)) *$', $val, $m)) $val = $m[1]; # si on veut (#...)
	if (ereg(NOM_DE_CHAMP . "(\{[^}]*\})?", $val, $regs)) {
	  	$champ = new Champ;
		$champ->nom_boucle = $regs[2];
		$champ->nom_champ = $regs[3];
		$champ->etoile = $regs[4];
		$champ->fonctions = $regs[5] ? array($regs[5]) : '';
		$champ->id_boucle = $boucles[$idb]->id_parent;
		$champ->boucles = &$boucles;
		$champ->id_mere = $idb;
		$champ = calculer_champ($champ);
		return '" . addslashes(' . $champ . ') . "';

	} else {
	  if ($val[0]== '%') {
	    spip_log($val .
		     " est obsolete; utiliser HTTP_VARS{" .  substr($val,1) . "}");
		  return '" . addslashes($Pile[0][\''. substr($val,1)  ."']) . \"";
	  }
		else
		  return addslashes($val);
	}
}

function calculer_params_dynamiques($liste, &$boucles, $idb) {
	ereg("^ *\(?(.*[^)])\)? *$",$liste, $reg);
	$res = array();
	foreach (split(" *, *", $reg[1]) as $v) {
	  $v = calculer_param_dynamique($v, $boucles, $idb);
	  if (is_array($v)) erreur_squelette($v);
	  if (strpos('0123456789',$v[0]) !== false)
	    $res[] = $v;
	  else if ($v[0]=='"')
	    $res[] = "'" . $v . "'";
	  else
	    $res[] = "'$v'";
	}
	return join(',', $res);
}

function simplifie_param_dynamique($val, &$boucles, $idb)
{
	$a = calculer_param_dynamique($val, $boucles, $idb);
	if (!ereg('" \. *(.*)\. "', $a, $m)) return $a;
	return $m[1];
}	
?>
