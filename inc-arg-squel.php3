<?php

# Traduction des arguments d'une boucle par affectation du tableau $boucles
# retourne un tableau en cas d'erreur

function calculer_params($idb, &$boucles) {
	global $tables_relations, $table_primary, $table_des_tables, $table_date;
	$boucle = &$boucles[$idb];
	$type = $boucle->type_requete;
	$params = $boucle->param;
	$id_table = $table_des_tables[$type];
	$id_field = $id_table . "." . $table_primary[$type];

	// Cas de la hierarchie : on cree des params supplementaires
	// $hierarchie sera calculee par un ajout dans 
	if ($type == 'hierarchie') {
		$boucle->where[] = 'id_rubrique IN ($hierarchie)';
		$boucle->select[] = 'FIND_IN_SET(id_rubrique, \'$hierarchie\')-1 AS rang';
		if (!$boucle->order)
			$boucle->order = 'rang';

		// Supprimer le parametre id_article/id_rubrique/id_syndic
		$params2 = array();
		foreach($params as $param)
			if (!ereg('^id_(article|syndic|rubrique)$', $param))
				$params2[]=$param;
		$params = $params2;

		$boucle->hierarchie = '$hierarchie = calculer_hierarchie('
		.calculer_argument_precedent($idb, 'id_rubrique', $boucles)
		.', false);';
	}


	if (is_array($params)) {
		foreach($params as $param) {
			if ($param == 'exclus') {
			$boucle->where[] = "$id_field!='\"." .
				calculer_argument_precedent($idb, $table_primary[$type], $boucles) .
				".\"'";
			}
			else if ($param == 'unique' OR $param == 'doublons') {
				$boucle->doublons = true;
				$boucle->where[] = '" .' .
				"calcul_mysql_in('$id_field', \$doublons['$type'], 'NOT') . \"";
			}
			else if (ereg('^(!)? *lang_select(=(oui|non))?$', $param, $match)) {
				if (!$lang_select = $match[3])
					$lang_select = 'oui';
				if ($match[1])
					$lang_select = ($lang_select=='oui')?'non':'oui';
				$boucles[$idb]->lang_select = $lang_select;
			}
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
			else if (ereg('^debut([-_a-zA-Z0-9]+),([0-9]*)$', $param, $match)) {
				$debut_lim = "debut".$match[1];
				$boucle->limit =
					'intval($GLOBALS["'.$debut_lim.'"]).",'.$match[2] .'"' ;
			}
			else if ($param == 'recherche') {
				$boucle->from[] = "index_$id_table AS rec";
				$boucle->select[] = 'SUM(rec.points + 100*(" .' . 
					'calcul_mysql_in("rec.hash",
					calcul_branche($hash_recherche_strict),"") . "))
					AS points';
				# a cause des exceptions forum{s}? et syndic
				# NB: utiliser table_objet() ?
				if (!($r = $table_primary[$id_table]))
				  $r = $table_primary[$type];
				
				$boucle->where[] = "rec.$r=$id_field";
				$boucle->group = $id_field;
				$boucle->where[] = '" .' . 'calcul_mysql_in("rec.hash",
					calcul_branche($hash_recherche),"") . "';
				$boucles[$idb]->hash = true;
			}

			// Classement par ordre inverse
			else if ($param == 'inverse') {
				if ($boucle->order) {
					$boucle->order .= ' DESC';
				} else {
				  return array(_T('info_erreur_squelette'),
					       $idb . (_L("&nbsp: inversion d'un ordre inexistant")));
				}
			}

			// Gerer les traductions
			else if ($param == 'traduction') {
				$boucle->where[] = "$id_table.id_trad > 0
				AND $id_table.id_trad ='\"." .
				calculer_argument_precedent($idb, 'id_trad', $boucles) . ".\"'";
			}
			else if ($param == 'origine_traduction') {
				$boucle->where[] = "$id_table.id_trad = $id_table.id_article";
			}
      
			// Special rubriques
			else if ($param == 'meme_parent') {
				$boucle->where[] = "$id_table.id_parent='\"." .
					calculer_argument_precedent($idb, 'id_parent', $boucles) . ".\"'";
				if ($type == 'forums') {
					$boucle->where[] = "$id_table.id_parent > 0";
					$boucle->plat = true;
				}
			}
			else if ($param == 'racine') {
				$boucle->where[] = "$id_table.id_parent='0'";
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
			// Restriction de valeurs (implicite ou explicite)
			else if (eregi('^([a-z_]+) *(\??)((!?)(<=?|>=?|==?|IN) *"?([^<>=!"]*))?"?$', $param, $match)) {
				// Variable comparee
				$col = $match[1];
				$col_table = $id_table;
				// Valeur de comparaison
				if ($match[3]) {
					$val = calculer_param_dynamique($match[6], $boucles, $idb);
					if (is_array($val)) return $val; #erreur
				} else {
					$val = $match[1];
					// Si id_parent, comparer l'id_parent avec l'id_objet
					// de la boucle superieure
					if ($val == 'id_parent')
						$val = $table_primary[$type];
					// Si id_enfant, comparer l'id_objet avec l'id_parent
					// de la boucle superieure
					else if ($val == 'id_enfant')
						$val = 'id_parent';
					$val = calculer_argument_precedent($idb, $val, $boucles) ;
				}

				if (ereg('^\$',$val))
					$val = '" . addslashes(' . $val . ') . "';
				else
					$val = addslashes($val);

				// Traitement general des relations externes
				if ($s = $tables_relations[$type][$col]) {
					$col_table = $s;
					$boucle->from[] = "$col_table AS $col_table";
					$boucle->where[] = "$id_field=$col_table." . $table_primary[$type];
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
					$boucle->where[] = "$id_field=lien_mot." . $table_primary[$type];
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
					$col = $table_primary[$type];
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
				$op = $match[5];
				if (!$op)
					$op = '=';
				else if ($op == '==')
					$op = 'REGEXP';
				else if (strtoupper($op) == 'IN') {
					// traitement special des valeurs textuelles
					$val2 = split(",", $val);
					foreach ($val2 as $v) {
						$v = trim($v);
						if (ereg("^[0-9]+$",$v))
							$val3[] = $v;
						else
							$val3[] = "'$v'";
					}
					$val = join(',', $val3);
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

				/*
				// Pas bon : les criteres sont des ET logiques
				$vu = 0;
				if (($op == '=') && (!$match[4]) && ($boucle->where)) {
					// reperer un parametre repete - {id_mot=1}{id_mot=2}
					//  pour cre'er une sous-requete
					foreach ($boucle->where as $k => $v) {
						if (ereg("^ *$col *(=|IN) *['\(](.*)['\)]",$v, $m)) {
							$boucle->where[$k] = "$col IN ($m[2],$val)";
							// esperons que c'est le meme !
							$boucle->sous_requete = $col;
							$boucle->compte_requete++;
							$vu=1;
							break;
							}
						}
					}

				if (!$vu) {
				*/

				if ($op) {
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
	if (ereg("^#([A-Za-z0-9_-]+)$", $val, $m)) {
		$c = calculer_champ('',$m[1], $idb, $boucles,$idb);
		if (ereg("[$]Pile[[][^]]+[]][[]'[^]]*'[]]", $c, $v))
			return $v[0];
		else {
			spip_log("champ inexistant ? : $c");
			return $c;
		}
	} else {
		if (ereg('^\$(.*)$',$val,$m))
			return '$Pile[0][\''. $m[1] ."']";
		else
			return $val;
	}
}

//
// Reserve les champs necessaires a la comparaison avec le contexte donne par
// la boucle parente ; attention en recursif il faut les reserver chez soi-meme
// ET chez sa maman
// 
function calculer_argument_precedent($idb, $nom_champ, &$boucles) {

	// recursif ?
	if ($boucles[$idb]->externe)
		index_pile ($idb, $nom_champ, $boucles); // reserver chez soi-meme

	// reserver chez le parent et renvoyer l'habituel $Pile[$SP]['nom_champ']
	return index_pile ($boucles[$idb]->id_parent, $nom_champ, $boucles);
}

?>
