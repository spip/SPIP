<?php

// traduction des arguments d'une boucle par affectation du tableau $boucles

function calculer_params($type, $params, $idb, &$boucles)
{
 global $tables_relations, $table_primary, $table_des_tables, $table_date;
 $boucle = &$boucles[$idb];
 $id_table = $table_des_tables[$type];
 $id_field = $id_table . "." . $table_primary[$type];

  if (is_array($params)) {
    reset($params);
    while (list(, $param) = each($params)) {
      if ($param == 'exclus') {
	$boucle->where[] = "$id_field!='\"." .
	  index_pile($boucles[$idb]->id_parent, $table_primary[$type], $boucles) .
	  ".\"'";  }
      else if ($param == 'unique' OR $param == 'doublons') {
	$boucle->doublons = true;
	$boucle->where[] = "$id_field NOT IN (\" . \$doublons['$type'] . \")";
      }
      else if (ereg('^(!)? *lang_select(=(oui|non))?$', $param, $match)) {
	if (!$lang_select = $match[3]) $lang_select = 'oui';
	if ($match[1]) $lang_select = ($lang_select=='oui')?'non':'oui';
	$boucles[$idb]->lang_select = $lang_select;
      }
      else if (ereg('^([0-9]+)/([0-9]+)$', $param, $match)) {
	$boucle->partie = $match[1];
	$boucle->total_parties = $match[2];
	$boucle->mode_partie = '/';
      }
      else if (ereg('^(([0-9]+)|n)(-([0-9]+))?,(([0-9]+)|n)(-([0-9]+))?$', 
		    $param, $match)) {
	if (($match[2]!='') && ($match[6]!=''))
	  $boucle->limit = $match[2].','.$match[6];
	else
	  {
	    $boucle->partie = 
	      ($match[1] != 'n') ? $match[1] : ($match[4] ? $match[4] : 0);
	    $boucle->total_parties = 
	      ($match[5] != 'n') ? $match[5] : ($match[8] ? $match[8] : 0);
	    $boucle->mode_partie = ($match[1] == 'n') ? '-' : '+';
	  }
      }
      else if (ereg('^debut([-_a-zA-Z0-9]+),([0-9]*)$', $param, $match)) {
	$debut_lim = "debut".$match[1];
	$boucle->limit = '".intval($GLOBALS[\'HTTP_GET_VARS\'][\''.$debut_lim.'\']).",'.$match[2];
      }
      else if ($param == 'recherche') {
	$boucle->from[] = "index_$id_table AS rec";
	$boucle->select[] = "SUM(rec.points + 100*(rec.hash IN (\$hash_recherche_strict))) AS points";
	$boucle->where[] = "rec.". $table_primary[$type] . "=$id_field";
	$boucle->group = "'$id_field'";
	$boucle->where[] = "rec.hash IN (\$hash_recherche)";
	$boucles[$idb]->hash = true;
     }

      // Classement par ordre inverse
      else if ($param == 'inverse') {
	if ($boucle->order != "''") 
	   $boucle->order .= ". ' DESC'";
	else
	  {
	    include_local("inc-debug-squel.php3");
	    erreur_squelette(_L("Inversion d'un ordre inexistant"), $param, $idb);
	  }
      }

      // Gerer les traductions
      else if ($param == 'traduction') {
	$boucle->where[] = "$id_table.id_trad > 0 AND  $id_table.id_trad ='\"." .
	  index_pile($boucles[$idb]->id_parent, 'id_trad', $boucles) . ".\"'";
      }
      else if ($param == 'origine_traduction') {
	$boucle->where[] = "$id_table.id_trad = $id_table.id_article";
      }
      
      // Special rubriques
      else if ($param == 'meme_parent') {
	$boucle->where[] = "$id_table.id_parent='\"." .
	  index_pile($boucles[$idb]->id_parent, 'id_parent', $boucles) . ".\"'";
	if ($type == 'forums') {
	  $boucle->where[] = "$id_table.id_parent > 0";
	  $boucle->plat = true;
	}
      }
      else if ($param == 'racine') {
	$boucle->where[] = "$id_table.id_parent='0'";
      }
      else if (ereg("^branche *(\??)", $param, $regs)) {
	$c = "$id_table.id_rubrique IN (\".calcul_branche(" .
	  index_pile($boucles[$idb]->id_parent, 'id_rubrique', $boucles) .
	  ").\")";
	if (!$regs[1])
	  $boucle->where[] = $c ;
	else
	  $boucle->where[] = "('\$id_rubrique'='' OR $c)";
      }
      else if ($type == 'hierarchie')
	{
	  // Hack spe'cifique; cf comple'ment dans calculer_boucle
	  $boucle->tout = index_pile($boucles[$idb]->id_parent,
				     'id_rubrique',
				     $boucles);
	}
      // Restriction de valeurs (implicite ou explicite)
      else if (ereg('^([a-zA-Z_]+) *(\??)((!?)(<=?|>=?|==?) *"?([^<>=!"]*))?"?$', $param, $match)) {
	// Variable comparee
	$col = $match[1];
	$col_table = $id_table;
	// Valeur de comparaison
	if ($match[3])
	  {
	    $val = calculer_param_dynamique($match[6], $boucles, $idb);
	  }
	else {
	  $val = $match[1];
	  // Si id_parent, comparer l'id_parent avec l'id_objet de la boucle superieure
	  if ($val == 'id_parent')
	    $val = $table_primary[$type];
	  // Si id_enfant, comparer l'id_objet avec l'id_parent de la boucle superieure
	  else if ($val == 'id_enfant')
	    $val = 'id_parent';
	  $val = index_pile($boucles[$idb]->id_parent, $val, $boucles) ;
	}
	if (ereg('^\$',$val))
	  $val = '" . addslashes(' . $val . ') . "';
	else 
	  $val = addslashes($val);

	// operateur optionnel {lang?}
	$ou_rien = ($match[2]) ? "'$val'='' OR " : '';

	// Traitement general des relations externes
	if ($s = $tables_relations[$type][$col]) {
	  $col_table = $s;
	  $boucle->from[] = "$col_table AS $col_table";
	  $boucle->where[] = "$id_field=$col_table." . $table_primary[$type];
	  $boucle->group = "'$id_field'";
	  $boucle->lien = true;
	}
	// Cas particulier pour les raccourcis 'type_mot' et 'titre_mot'
	else if ($type != 'mots' AND ($col == 'type_mot' OR $col == 'titre_mot' OR $col == 'id_groupe')) {
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
	  $boucle->group = "'$id_field'";
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
	if ($type == 'documents' AND $col == 'extension') {
	  $col_table = 'types_documents';
	}
	// HACK : selection des documents selon mode 'image' (a creer en dur dans la base)
	else if ($type == 'documents' AND $col == 'mode' AND $val == 'image') {
	  $val = 'vignette';
	}
	// Cas particulier : lier les articles syndiques au site correspondant
	else if ($type == 'syndic_articles' AND !ereg("^(id_syndic_article|titre|url|date|descriptif|lesauteurs)$",$col))
	  $col_table = 'syndic';
	
	// Cas particulier : id_enfant => utiliser la colonne id_objet
	if ($col == 'id_enfant')
	  $col = $table_primary[$type];
	// Cas particulier : id_secteur = id_rubrique pour certaines tables
	else if (($type == 'breves' OR $type == 'forums') AND $col == 'id_secteur')
	  $col = 'id_rubrique';
	
	// Cas particulier : expressions de date
	if (ereg("^(date|mois|annee|age|age_relatif|jour_relatif|mois_relatif|annee_relatif)(_redac)?$", $col, $regs)) {
	  $col = $regs[1];
	  if ($regs[2]) {
	    $date_orig = $id_table . ".'.'." . '$PileRow[0][date_redac]';
	    $date_compare = '\'" . $PileRow[0][date_redac] . "\'';
	  }
	  else {
	    $date_orig = "$id_table." . $table_date[$type];
	    $date_compare = '\'" . $PileRow[0][date] . "\'';
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
	    $col = calculer_param_date('now()', $date_orig);
	    $col_table = '';
	  }
	  else if ($col == 'age_relatif') {
	    $col = calculer_param_date($date_compare, $date_orig);
	    $col_table = '';
	  }
	  else if ($col == 'jour_relatif') {
	    $col = "LEAST(TO_DAYS(" .$date_compare . ")-TO_DAYS(" .
	      $date_orig .
	      "), DAYOFMONTH(" . $date_compare . ")-DAYOFMONTH(" . $date_orig .
	      ")+30.4368*(MONTH(" . $date_compare . ")-MONTH(" . $date_orig .
	      "))+365.2422*(YEAR(" . $date_compare . ")-YEAR(" . $date_orig . ")))";
	    $col_table = '';
	  }
	  else if ($col == 'mois_relatif') {
	    $col = "MONTH(" . $date_compare . ")-MONTH(" . $date_orig .
	      ")+12*(YEAR(" . $date_compare . ")-YEAR(" . $date_orig . "))";
	    $col_table = '';
	  }
	  else if ($col == 'annee_relatif') {
	    $col = "YEAR(" . $date_compare . ")-YEAR(" . $date_orig . ")";
	    $col_table = '';
	  }
	}
	
	if ($type == 'forums' AND ($col == 'id_parent' OR $col == 'id_forum'))
	  $boucle->plat = true;
	
	// Operateur de comparaison
	$op = $match[5];
	if (!$op) {
	  $op = '=';
	} else {if ($op == '==') $op = 'REGEXP'; }
	
	if ($col_table) $col = "$col_table.$col";

	$vu = 0;
	if (($op == '=') && (!$match[4]) && ($boucle->where))
	  {
	    // repe'rer un parame`tre re'pe'te' comme {id_mot=1}{id_mot=2}
	    //  pour cre'er une sous-requete
	      foreach ($boucle->where as $k => $v)
	      {
		if (ereg("^ *$col *(=|IN) *['\(](.*)['\)]",$v, $m)) {
		  $boucle->where[$k] = "$col IN ($m[2],$val)";
		  // espe'rons que c'est le meme !
		  $boucle->sous_requete = $col;
		  $boucle->compte_requete++;
		  $vu=1;
		  break;}
	      }
	  }
	if (!$vu)
	  {
	    if ($match[4] == '!')
	      $boucle->where[] = "NOT ($ou_rien$col $op'$val')";
	    else
	      $boucle->where[] = "$ou_rien$col $op'$val'";
	  }
      } // fin du if sur les restrictions de valeurs
      
      // Selection du classement
      else if (ereg('^par[[:space:]]+([^}]*)$', $param, $match)) {
	$tri = trim($match[1]);
	if ($tri == 'hasard') { // par hasard
	  $boucle->select[] = "MOD($id_field * UNIX_TIMESTAMP(), 32767) & UNIX_TIMESTAMP() AS alea";
	  $boucle->order = "'alea'";
	}
	else if ($tri == 'titre_mot'){ // par titre_mot
	  $boucle->order= "'mots.titre'";
	}
	else if ($tri == 'type_mot'){ // par type_mot
	  $boucle->order= "'mots.type'";
	}
	else if ($tri == 'points'){ // par points
	  $boucle->order= "'points'";
	}
	else if (ereg("^num[[:space:]]+([^,]*)(,.*)?",$tri, $match2)) { // par num champ
	  $boucle->select[] = "0+$id_table.".$match2[1]." AS num";
	  $boucle->order = "'num".$match2[2]."'";
	}
	else if (ereg("^[a-z0-9]+$", $tri)) { // par champ
	  if ($tri == 'date') $tri = $table_date[$type];
	  $boucle->order = "'$id_table.$tri'";
	}
	else { 
# tris par crite`re dynamique ou bizarres (formule composee, virgules, etc).
	  $boucle->order = calculer_param_dynamique($tri, $boucles, $idb);
	}
      }
# pas de else, c~a a du etre e'vacue' lors du phrase'

    }
  }
}

function calculer_param_date($date_compare, $date_orig)
{
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

function calculer_param_dynamique($val, &$boucles, $idb)
{
  if (ereg("^#(.*)$",$val,$m))
    return index_pile($boucles[$idb]->id_parent, $m[1], $boucles) ;
  else
    {if (ereg('^\$(.*)$',$val,$m))
	return '$PileRow[0][\''. $m[1] ."']";
      else return $val;
    }
}
?>
