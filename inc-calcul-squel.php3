<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CALCUL_SQUEL")) return;
define("_INC_CALCUL_SQUEL", "1");


//////////////////////////////////////////////////////////////////////////////
//
//              Parsing des squelettes
//
//////////////////////////////////////////////////////////////////////////////


class Texte {
	var $type = 'texte';
	var $texte;
}

class Champ {
	var $type = 'champ';
	var $nom_champ, $id_champ;
	var $cond_avant, $cond_apres; // tableaux d'objets
	var $fonctions;
}

class Boucle {
	var $type = 'boucle';
	var $id_boucle, $id_parent;
	var $avant, $cond_avant, $milieu, $cond_apres, $cond_altern, $apres; // tableaux d'objets
	var $commande;
	var $requete;
	var $type_requete;
	var $separateur;
	var $doublons;
	var $partie, $total_parties;
}


function parser_boucle($texte, $id_parent) {
	global $rubriques_publiques;
	global $recherche;
	global $tables_relations;

	//
	// Detecter et parser la boucle
	//

	$p = strpos($texte, '<BOUCLE');
	if (!$p && (substr($texte, 0, strlen('<BOUCLE')) != '<BOUCLE')) {
		$result = new Texte;
		$result->texte = $texte;
		return $result;
	}

	$result = new Boucle;

	$debut = substr($texte, 0, $p);
	$milieu = substr($texte, $p);

	if (!ereg("^(<BOUCLE([0-9]+|[-_][-_.a-zA-Z0-9]*)[[:space:]]*(\([^)]*\)([[:space:]]*\{[^}]*\})*)[[:space:]]*>)", $milieu, $match)) {
		include_local ("ecrire/inc_presentation.php3");
		install_debut_html("Syntaxe boucle incorrecte");
		echo '<p>La boucle ' . entites_html($milieu) . ' est incorrecte.';
		install_fin_html();
		exit;
	}

	$commande = $match[1];
	$id_boucle = $match[2];
	$suite_commande = $match[3];

	//
	// Decomposer les structures conditionnelles
	//

	$s = "<B$id_boucle>";

	$p = strpos($debut, $s);
	if ($p || (substr($debut, 0, strlen($s)) == $s)) {
		$cond_avant = substr($debut, $p + strlen($s));
		$debut = substr($debut, 0, $p);
	}

	$milieu = substr($milieu, strlen($commande));
	$s = "</BOUCLE$id_boucle>";
	$p = strpos($milieu, $s);
	if ((!$p) && (substr($milieu, 0, strlen($s)) != $s)) die("<h2>BOUCLE$id_boucle: tag fermant manquant</h2>");

	$fin = substr($milieu, $p + strlen($s));
	$milieu = substr($milieu, 0, $p);

	$s = "</B$id_boucle>";
	$p = strpos($fin, $s);
	if ($p || (substr($fin, 0, strlen($s)) == $s)) {
		$cond_fin = substr($fin, 0, $p);
		$fin = substr($fin, $p + strlen($s));
	}

	$s = "<//B$id_boucle>";
	$p = strpos($fin, $s);
	if ($p || (substr($fin, 0, strlen($s)) == $s)) {
		$cond_altern = substr($fin, 0, $p);
		$fin = substr($fin, $p + strlen($s));
	}

	$id_boucle = ereg_replace("-","_",$id_boucle);

	//
	// Parser la commande de la boucle
	//

	if (ereg('\(([^)]*)\)', $suite_commande, $regs)) {
		$_type = $regs[1];
		$s = "($_type)";
		$p = strpos($suite_commande, $s);

		// Exploser les parametres
		$params = substr($suite_commande, $p + strlen($s));
		if (ereg('^[[:space:]]*\{(.*)\}[[:space:]]*$', $params, $match)) $params = $match[1];
		$params = split('\}[[:space:]]*\{', $params);
		$type = strtolower($_type);

		//
		// Type boucle (recursion)
		//
		
		if ($type == 'sites') $type = 'syndication';
		
		if (substr($type, 0, 6) == 'boucle') {
			$requete = substr($_type, 6);
			$type = 'boucle';
		}
		else {
			//
			// Initialisation separee par type
			//
			
			switch($type) {
			case 'articles':
				$table = "articles";
				$req_from[] = "spip_articles AS $table";
				$id_objet = "id_article";
				break;

			case 'auteurs':
				$table = "auteurs";
				$req_from[] = "spip_auteurs AS $table";
				$id_objet = "id_auteur";
				break;

			case 'breves':
				$table = "breves";
				$req_from[] = "spip_breves AS $table";
				$id_objet = "id_breve";
				$col_date = "date_heure";
				break;

			case 'forums':
				$table = "forums";
				$req_from[] = "spip_forum AS $table";
				$id_objet = "id_forum";
				$col_date = "date_heure";
				break;

			case 'signatures':
				$table = "signatures";
				$req_from[] = "spip_signatures AS $table";
				$id_objet = "id_signature";
				$col_date = "date_time";
				break;

			case 'documents':
				$table = "documents";
				$req_select[] = "$table.*";
				$req_select[] = "types_d.titre AS type_document";
				$req_select[] = "types_d.extension AS extension_document";
				$req_from[] = "spip_documents AS $table";
				$req_from[] = "spip_types_documents AS types_d";
				$req_where[] = "$table.id_type = types_d.id_type";
				$id_objet = "id_document";
				break;

			case 'types_documents':
				$table = "types_documents";
				$req_from[] = "spip_types_documents AS $table";
				$id_objet = "id_type";
				break;

			case 'groupes_mots':
				$table = "groupes_mots";
				$req_from[] = "spip_groupes_mots AS $table";
				$id_objet = "id_groupe";
				break;

			case 'mots':
				$table = "mots";
				$req_from[] = "spip_mots AS $table";
				$id_objet = "id_mot";
				$req_where[] = "$table.titre<>'kawax'";
				break;

			case 'rubriques':
				$table = "rubriques";
				$req_from[] = "spip_rubriques AS $table";
				$id_objet = "id_rubrique";
				break;

			case 'syndication':
				$table = "syndic";
				$req_from[] = "spip_syndic AS $table";
				$req_where[] = "$table.statut='publie'";
				$id_objet = "id_syndic";
				break;

			case 'syndic_articles':
				$table = "articles";
				$req_from[] = "spip_syndic_articles AS $table";
				$req_from[] = "spip_syndic AS source";
				$req_where[] = "$table.id_syndic=source.id_syndic";
				$req_where[] = "$table.statut='publie'";
				$req_where[] = "source.statut='publie'";
				$id_objet = "id_syndic_article";
				break;
			}
			if ($table) {
				if ($type == 'articles') {
					$s = "$table.id_article,$table.id_rubrique,$table.id_secteur,".
						"$table.surtitre,$table.titre,$table.soustitre,$table.date,$table.date_redac,$table.date_modif,".
						"$table.visites,$table.popularite,$table.statut,$table.accepter_forum";
					if (ereg('\#(TEXTE|INTRODUCTION)', $milieu)) {
						$s .= ",$table.texte";
					}
					if (ereg('\#(CHAPO|INTRODUCTION)', $milieu)) {
						$s .= ",$table.chapo";
					}
					if (ereg('\#(DESCRIPTIF|INTRODUCTION)', $milieu)) {
						$s .= ",$table.descriptif";
					}
					if (ereg('\#(PS)', $milieu)) {
						$s .= ",$table.ps";
					}
					
					$req_select[] = $s;
				}
				else $req_select[] = "$table.*";
			}
			if (!$col_date) $col_date = "date";

			//
			// Parametres : premiere passe
			//
			unset($params2);
			if ($params) {
				reset($params);
				while (list(, $param) = each($params)) {
					$param = trim($param);
					if ($param == 'exclus') {
						$req_where[] = "$table.$id_objet!=\$$id_objet";
					}
					else if ($param == 'tout' OR $param == 'plat') {
						$$param = true;
					}
					else if ($param == 'unique' OR $param == 'doublons') {
						$doublons = 'oui';
						$req_where[] = "$table.$id_objet NOT IN (\$id_doublons[$type])";
					}
					else if (ereg('^ *"([^"]*)" *$', $param, $match)) {
						$separateur = ereg_replace("'","\'",$match[1]);
					}
					else if (ereg('^([0-9]+),([0-9]*)', $param, $match)) {
						$req_limit = $match[1].','.$match[2];
					}
					else if (ereg('^debut([-_a-zA-Z0-9]+),([0-9]*)$', $param, $match)) {
						$debut_lim = "debut".$match[1];
						$req_limit = '".intval($GLOBALS[\''.$debut_lim.'\']).",'.$match[2];
					}
					else if (ereg('^([0-9]+)/([0-9]+)$', $param, $match)) {
						$partie = $match[1];
						$total_parties = $match[2];
					}
					else if ($param == 'recherche') {
						if ($type == 'syndication') $req_from[] = "spip_index_syndic AS idx";
						else $req_from[] = "spip_index_$type AS idx";
						$req_select[] = "SUM(idx.points) AS points";
						$req_where[] = "idx.$id_objet=$table.$id_objet";
						$req_group = " GROUP BY $table.$id_objet";
						$req_where[] = "idx.hash IN (\$hash_recherche)";
					}
					else $params2[] = $param;
				}
			}
			$params = $params2;

			//
			// Parametres : deuxieme passe
			//
			if ($params) {
				reset($params);
				while (list(, $param) = each($params)) {

					// Classement par ordre inverse
					if ($param == 'inverse') {
						if ($req_order) $req_order .= ' DESC';
					}
	
					// Special rubriques
					else if ($param == 'meme_parent') {
						$req_where[] = "$table.id_parent=\$id_parent";
						if ($type == 'forums') {
							$req_where[] = "$table.id_parent > 0";
							$plat = true;
						}
					}
					else if ($param == 'racine') {
						$req_where[] = "$table.id_parent=0";
					}
					else if ($param == 'branche') {
						$req_where[] = "$table.id_rubrique IN (\".calcul_branche(\$id_rubrique).\")";
 					}
	
					// Restriction de valeurs (implicite ou explicite)
					else if (ereg('^([a-zA-Z_]+) *((!?)(<=?|>=?|==?) *"?([^<>=!"]*))?"?$', $param, $match)) {
						// Variable comparee
						$col = $match[1];
						$col_table = $table;

						// Valeur de comparaison
						if ($match[2])
							$val = $match[5];
						else {
							$val = $match[1];
							// Si id_parent, comparer l'id_parent avec l'id_objet de la boucle superieure
							if ($val == 'id_parent')
								$val = $id_objet;
							// Si id_enfant, comparer l'id_objet avec l'id_parent de la boucle superieure
							else if ($val == 'id_enfant')
								$val = 'id_parent';
							$val = '$'.$val;
						}

						// Traitement general des relations externes
						if ($s = $tables_relations[$type][$col]) {
							$col_table = "rel_$type";
							$req_from[] = "$s AS $col_table";
							$req_where[] = "$table.$id_objet=$col_table.$id_objet";
							$req_group = " GROUP BY $table.$id_objet";
							$flag_lien = true;
						}
						// Cas particulier pour les raccourcis 'type_mot' et 'titre_mot'
						else if ($type != 'mots' AND ($col == 'type_mot' OR $col == 'titre_mot' OR $col == 'id_groupe')) {
							if ($type == 'forums')
								$col_lien = "spip_mots_forum";
							else if ($type == 'syndication')
								$col_lien = "spip_mots_syndic";
							else
								$col_lien = 'spip_mots_'.$type;
							$req_from[] = "$col_lien AS lien_mot";
							$req_from[] = 'spip_mots AS mots';
							$req_where[] = "$table.$id_objet=lien_mot.$id_objet";
							$req_where[] = "lien_mot.id_mot=mots.id_mot";
							$req_group = " GROUP BY $table.$id_objet";
							$col_table = 'mots';
							$flag_lien = true;
							if ($col == 'type_mot')
								$col = 'type';
							else if ($col == 'titre_mot')
								$col = 'titre';
							else if ($col == 'id_groupe')
								$col = 'id_groupe';
						}

						// Cas particulier : selection des documents selon l'extension
						if ($type == 'documents' AND $col == 'extension') {
							$col_table = 'types_d';
						}
						// HACK : selection des documents selon mode 'image' (a creer en dur dans la base)
						else if ($type == 'documents' AND $col == 'mode' AND $val == 'image') {
							$val = 'vignette';
						}
						// Cas particulier : lier les articles syndiques au site correspondant
						else if ($type == 'syndic_articles' AND $col<>'id_syndic_article')
							$col_table = 'source';

						// Cas particulier : id_enfant => utiliser la colonne id_objet
						if ($col == 'id_enfant')
							$col = $id_objet;
						// Cas particulier : id_secteur = id_rubrique pour certaines tables
						else if (($type == 'breves' OR $type == 'forums') AND $col == 'id_secteur')
							$col = 'id_rubrique';
						// Cas particulier : expressions de date
						else if ($col == 'date')
							$col = $table.$col_date;
						else if ($col == 'mois') {
							$col = "MONTH($table.$col_date)";
							$col_table = '';
						}
						else if ($col == 'mois_redac') {
							$col = "MONTH($table.date_redac)";
							$col_table = '';
						}
						else if ($col == 'annee') {
							$col = "YEAR($table.$col_date)";
							$col_table = '';
						}
						else if ($col == 'annee_redac') {
							$col = "YEAR($table.date_redac)";
							$col_table = '';
						}
						else if ($col == 'age') {
							$col = "(LEAST((TO_DAYS(now())-TO_DAYS($table.$col_date)),(DAYOFMONTH(now())-DAYOFMONTH($table.$col_date))+30.4368*(MONTH(now())-MONTH($table.$col_date))+365.2422*(YEAR(now())-YEAR($table.$col_date))))";
							$col_table = '';
						}
						else if ($col == 'age_relatif') {
							$date_prec = "($"."date)";
							$col = "(LEAST((TO_DAYS('$date_prec')-TO_DAYS($table.$col_date)),(DAYOFMONTH('$date_prec')-DAYOFMONTH($col_date))+30.4368*(MONTH('$date_prec')-MONTH($table.$col_date))+365.2422*(YEAR('$date_prec')-YEAR($table.$col_date))))";
							$col_table = '';
						}
						else if ($col == 'age_redac') {
							$col = "(LEAST((TO_DAYS(now())-TO_DAYS(date_redac)),(DAYOFMONTH(now())-DAYOFMONTH(date_redac))+30.4368*(MONTH(now())-MONTH(date_redac))+365.2422*(YEAR(now())-YEAR(date_redac))))";
							$col_table = '';
						}

						if ($type == 'forums' AND ($col == 'id_parent' OR $col == 'id_forum'))
							$plat = true;

						// Operateur de comparaison
						if ($match[4]) {
							$op = $match[4];
							if ($op == '==') $op = ' REGEXP ';
						}
						else {
							$op = '=';
						}

						if ($col_table) $col_table .= '.';
						$where = "$col_table$col$op'".addslashes($val)."'";
						if ($match[3] == '!') $where = "NOT ($where)";
						$req_where[] = $where;
					}
	
					// Selection du classement
					else if (ereg('^par[[:space:]]+([^}]*)$', $param, $match)) {
						$tri = trim($match[1]);
						if ($tri == 'hasard') { // par hasard
							$req_select[] = "MOD($table.$id_objet * UNIX_TIMESTAMP(), 32767) & UNIX_TIMESTAMP() AS alea";
							$req_order = " ORDER BY alea";
						}
						else if ($tri == 'titre_mot'){ // par titre_mot
							$req_order= " ORDER BY mots.titre";
						}
						else if ($tri == 'type_mot'){ // par type_mot
							$req_order= " ORDER BY mots.type";
						}
						else if ($tri == 'points'){ // par points
							$req_order= " ORDER BY points";
						}
						else if (ereg("^num[[:space:]]+([^,]*)(,.*)?",$tri, $match2)) { // par num champ
							$req_select[] = "0+$table.".$match2[1]." AS num";
							$req_order = " ORDER BY num".$match2[2];
						}
						else if (ereg("^[a-z0-9]+$", $tri)) { // par champ
							$col = $tri;
							if ($col == 'date') $col = $col_date;
							$req_order = " ORDER BY $table.$col";
						}
						else { // tris bizarres, par formule composee, virgules, etc.
							$req_order = " ORDER BY ".$tri;
						}
					}
				}
			}

			//
			// Post-traitement separe par type
			//

			switch($type) {
			case 'articles':
				$post_dates = lire_meta("post_dates");
				if ($post_dates == 'non') $req_where[] = "$table.date<NOW()";
				$req_where[] = "$table.statut='publie'";
				break;

			case 'groupes_mots':
				// pas de restriction sur les groupes de_mots
				break;

			case 'mots':
				// pas de restriction sur les mots
				break;

			case 'breves':
				$req_where[] = "$table.statut='publie'";
				break;

			case 'rubriques':
				$req_where[] = "$table.statut='publie'";
				break;

			case 'forums':
				// Par defaut, selectionner uniquement les forums sans pere
				if (!$plat) $req_where[] = "$table.id_parent=0";
				$req_where[] = "$table.statut='publie'";
				break;

			case 'signatures':
				$req_from[] = 'spip_petitions AS petitions';
				$req_from[] = 'spip_articles AS articles';
				$req_where[] = "petitions.id_article=articles.id_article";
				$req_where[] = "petitions.id_article=$table.id_article";

				$req_where[] = "$table.statut='publie'";
				$req_group = " GROUP BY $table.$id_objet";
				break;
			
			case 'syndic_articles':
				$req_select[]='syndic.nom_site AS nom_site';
				$req_select[]='syndic.url_site AS url_site';
				$req_from[]='spip_syndic AS syndic';
				$req_where[] = "syndic.id_syndic=$table.id_syndic";
				break;

			case 'documents':
				$req_where[] = "$table.taille > 0";
				break;

			case 'auteurs':
				// Si pas de lien avec un article, selectionner
				// uniquement les auteurs d'un article publie
				if (!$tout AND !$flag_lien) {
					$req_from[] = 'spip_auteurs_articles AS lien';
					$req_from[] = 'spip_articles AS articles';
					$req_where[] = "lien.id_auteur=$table.id_auteur";
					$req_where[] = "lien.id_article=articles.id_article";
					$req_where[] = "articles.statut='publie'";
					$req_group = " GROUP BY $table.$id_objet";
				}
				// pas d'auteurs poubellises
				$req_where[] = "NOT($table.statut='5poubelle')";
				break;
			}
		}

		//
		// Construire la requete
		//
		if ($type == 'hierarchie')
			$requete = $req_limit;
		else if ($req_select) {
			$requete = 'SELECT '.join(',', $req_select).' FROM '.join(',', $req_from);
			if ($req_where) $requete .= ' WHERE '.join(' AND ', $req_where);
			$requete .= $req_group;
			$requete .= $req_order;
			if ($req_limit) $requete .= ' LIMIT '.$req_limit;
		}
		$result->type_requete = $type;
		$result->requete = $requete;
		$result->doublons = $doublons;
		$result->separateur = $separateur;
	}


	//
	// Stocker le tout dans le resultat de la fonction
	//

	$result->id_boucle = $id_boucle;
	$result->id_parent = $id_parent;
	$result->commande = $commande;
	$result->avant = $debut;
	$result->cond_avant = parser_texte($cond_avant, $id_parent);
	$result->cond_apres = parser_texte($cond_fin, $id_parent);
	$result->cond_altern = parser_texte($cond_altern, $id_parent);
	$result->milieu = parser_texte($milieu, $id_boucle);
	$result->apres = $fin;
	$result->partie = $partie;
	$result->total_parties = $total_parties;
	
	return $result;
}



function parser_champs($texte) {
	global $champs;
	global $champs_count;
	global $champs_valides;
	global $champs_traitement;
	global $champs_pretraitement;
	global $champs_posttraitement;

	$debut = '';
	$result=Array();
	while ($texte) {
		$r = ereg('(#([a-zA-Z_]+)(\*?))', $texte, $regs);
		if ($r) {
			unset($champ);
			$nom_champ = $regs[2];
			$flag_brut = $regs[3];
			$s = $regs[1];
			$p = strpos($texte, $s);
			if ($champs_valides[$nom_champ]) {
				$debut .= substr($texte, 0, $p);
				if ($debut) {
					$champ = new Texte;
					$champ->texte = $debut;
					$result[] = $champ;
				}
				$champ = new Champ;
				$champ->nom_champ = $nom_champ;
				$champ->fonctions = $champs_pretraitement[$nom_champ];
				if (!$flag_brut AND $champs_traitement[$nom_champ]) {
					reset($champs_traitement[$nom_champ]);
					while (list(, $f) = each($champs_traitement[$nom_champ])) {
						$champ->fonctions[] = $f;
					}
				}
				if ($champs_posttraitement[$nom_champ]) {
					reset($champs_posttraitement[$nom_champ]);
					while (list(, $f) = each($champs_posttraitement[$nom_champ])) {
						$champ->fonctions[] = $f;
					}
				}
				$champs_count++;
				$champ->id_champ = $champs_count;
				$champs[$champs_count] = $champ;
				$result[] = $champ;
				$debut = '';
			}
			else {
				$debut .= substr($texte, 0, $p + strlen($s));
			}
			$texte = substr($texte, $p + strlen($s));
		}
		else {
			$champ = new Texte;
			$champ->texte = $debut.$texte;
			if ($champ->texte) $result[] = $champ;
			break;
		}
	}
	return $result;
}


function parser_champs_etendus($texte) {
	global $champs;
	global $champs_count;
	global $champs_valides;
	global $champs_traitement;
	global $champs_pretraitement;
	global $champs_posttraitement;

	$debut = '';
	while ($texte) {
		$r = ereg('(\[([^\[]*)\(#([a-zA-Z_]+)(\*?)([^])]*)\)([^]]*)\])', $texte, $regs);

		if ($r) {
			$cond_avant = $regs[2];
			$nom_champ = $regs[3];
			$flag_brut = $regs[4];
			$fonctions = $regs[5];
			$cond_apres = $regs[6];
			$s = $regs[1];
			$p = strpos($texte, $s);
			if ($champs_valides[$nom_champ]) {
				$debut .= substr($texte, 0, $p);
				if ($debut) {
					$c = parser_champs($debut);
					reset($c);
					while (list(, $val) = each($c)) $result[] = $val;
				}
				$champ = new Champ;
				$champ->nom_champ = $nom_champ;
				$champ->cond_avant = parser_champs($cond_avant);
				$champ->cond_apres = parser_champs($cond_apres);
				$champ->fonctions = $champs_pretraitement[$nom_champ];
				if (!$flag_brut AND $champs_traitement[$nom_champ]) {
					reset($champs_traitement[$nom_champ]);
					while (list(, $f) = each($champs_traitement[$nom_champ])) {
						$champ->fonctions[] = $f;
					}
				}
				if ($champs_posttraitement[$nom_champ]) {
					reset($champs_posttraitement[$nom_champ]);
					while (list(, $f) = each($champs_posttraitement[$nom_champ])) {
						$champ->fonctions[] = $f;
					}
				}
				
				if ($fonctions) {
					$fonctions = explode('|', substr($fonctions, 1));
					reset($fonctions);
					while (list(, $f) = each($fonctions)) $champ->fonctions[] = $f;
				}
				$champs_count++;
				$champ->id_champ = $champs_count;
				$champs[$champs_count] = $champ;
				$result[] = $champ;
				$debut = '';
			}
			else {
				$debut .= substr($texte, 0, $p + strlen($s));
			}
			$texte = substr($texte, $p + strlen($s));
		}
		else {
			$c = parser_champs($debut.$texte);
			reset($c);
			while (list(, $val) = each($c)) $result[] = $val;
			break;
		}
	}
	return $result;	
}

function parser_texte($texte, $id_boucle) {
	global $boucles;

	$i = 0;

	while ($texte) {
		$boucle = parser_boucle($texte, $id_boucle);
		if ($boucle->type == 'texte') {
			if ($c = parser_champs_etendus($boucle->texte)) {
				reset($c);
				while (list(, $val) = each($c)) {
					$result[$i] = $val;
					$i++;
				}
			}
			$texte = '';
		}
		else {
			if ($c = parser_champs_etendus($boucle->avant)) {
				reset($c);
				while (list(, $val) = each($c)) {
					$result[$i] = $val;
					$i++;
				}
			}
			$texte = $boucle->apres;
			$boucle->avant = '';
			$boucle->apres = '';
			$result[$i] = $boucle;
			$i++;
			if (!$boucles[$boucle->id_boucle])
				$boucles[$boucle->id_boucle] = $boucle;
			else die ('<h2>BOUCLE'.$boucle->id_boucle.': double definition</h2>');
		}
	}

	return $result;
}


function parser($texte) {

	global $champs_valides;
	global $champs_traitement, $champs_pretraitement, $champs_posttraitement;

	global $rows_articles;
	global $rows_signatures;
	global $rows_syndication;
	global $rows_syndic_articles;
	global $rows_documents;
	global $rows_types_documents;
	global $rows_rubriques;
	global $rows_forums;
	global $rows_breves;
	global $rows_auteurs;
	global $rows_hierarchie;
	global $rows_mots;
	global $rows_groupes_mots;

	global $tables_relations;

	global $racine;

	//
	// Construire un tableau des tables de relations
	//

	$tables_relations = '';

	$tables_relations['articles']['id_mot'] = 'spip_mots_articles';
	$tables_relations['articles']['id_auteur'] = 'spip_auteurs_articles';
	$tables_relations['articles']['id_document'] = 'spip_documents_articles';
	$tables_relations['rubriques']['id_document'] = 'spip_documents_rubriques';

	$tables_relations['auteurs']['id_article'] = 'spip_auteurs_articles';

	$tables_relations['breves']['id_mot'] = 'spip_mots_breves';

	$tables_relations['documents']['id_article'] = 'spip_documents_articles';
	$tables_relations['documents']['id_rubrique'] = 'spip_documents_rubriques';
	$tables_relations['documents']['id_breve'] = 'spip_documents_breves';

	$tables_relations['mots']['id_article'] = 'spip_mots_articles';
	$tables_relations['mots']['id_breve'] = 'spip_mots_breves';
	$tables_relations['mots']['id_forum'] = 'spip_mots_forum';
	$tables_relations['mots']['id_rubrique'] = 'spip_mots_rubriques';
	$tables_relations['mots']['id_syndic'] = 'spip_mots_syndic';

	$tables_relations['groupes_mots']['id_groupe'] = 'spip_mots';

	$tables_relations['rubriques']['id_mot'] = 'spip_mots_rubriques';
	$tables_relations['forums']['id_mot'] = 'spip_mots_forum';

	$tables_relations['syndication']['id_mot'] = 'spip_mots_syndic';


	//
	// Construire un tableau associatif des codes de champ utilisables
	//

	$c = array('NOM_SITE_SPIP', 'URL_SITE_SPIP', 'EMAIL_WEBMASTER', 'CHARSET',
		'ID_ARTICLE', 'ID_RUBRIQUE', 'ID_BREVE', 'ID_FORUM', 'ID_PARENT', 'ID_SECTEUR', 'ID_DOCUMENT', 'ID_TYPE',
		'ID_AUTEUR', 'ID_MOT', 'ID_SYNDIC_ARTICLE', 'ID_SYNDIC', 'ID_SIGNATURE', 'ID_GROUPE',
		'TITRE', 'SURTITRE', 'SOUSTITRE', 'DESCRIPTIF', 'CHAPO', 'TEXTE', 'PS', 'NOTES', 'INTRODUCTION', 'MESSAGE',
		'DATE', 'DATE_REDAC', 'DATE_MODIF', 'INCLUS',
		'LESAUTEURS', 'EMAIL', 'NOM_SITE', 'LIEN_TITRE', 'URL_SITE', 'LIEN_URL', 'NOM', 'BIO', 'TYPE', 'PGP',
		'FORMULAIRE_ECRIRE_AUTEUR', 'FORMULAIRE_FORUM', 'FORMULAIRE_SITE', 'PARAMETRES_FORUM', 'FORMULAIRE_RECHERCHE', 'FORMULAIRE_INSCRIPTION', 'FORMULAIRE_SIGNATURE',
		'LOGO_MOT', 'LOGO_RUBRIQUE', 'LOGO_RUBRIQUE_NORMAL', 'LOGO_RUBRIQUE_SURVOL', 'LOGO_AUTEUR', 'LOGO_SITE',  'LOGO_BREVE', 'LOGO_BREVE_RUBRIQUE',  'LOGO_DOCUMENT', 'LOGO_ARTICLE', 'LOGO_ARTICLE_RUBRIQUE', 'LOGO_ARTICLE_NORMAL', 'LOGO_ARTICLE_SURVOL',
		'URL_ARTICLE', 'URL_RUBRIQUE', 'URL_BREVE', 'URL_FORUM', 'URL_SYNDIC', 'URL_MOT', 'URL_DOCUMENT', 'EMBED_DOCUMENT',
		'IP', 'VISITES', 'POPULARITE', 'POPULARITE_ABSOLUE', 'POPULARITE_MAX', 'POPULARITE_SITE', 'POINTS', 'COMPTEUR_BOUCLE', 'TOTAL_BOUCLE', 'PETITION',
		'LARGEUR', 'HAUTEUR', 'TAILLE', 'EXTENSION',
		'DEBUT_SURLIGNE', 'FIN_SURLIGNE', 'TYPE_DOCUMENT', 'EXTENSION_DOCUMENT',
		'FORMULAIRE_ADMIN', 'LOGIN_PRIVE', 'LOGIN_PUBLIC', 'URL_LOGOUT', 'PUCE'
	);
	reset($c);
	while (list(, $val) = each($c)) {
		unset($champs_traitement[$val]);
		unset($champs_pretraitement[$val]);
		unset($champs_posttraitement[$val]);
		$champs_valides[$val] = $val;
	}


	//
	// Construire un tableau associatif des pre-traitements de champs
	//

	// Textes utilisateur : ajouter la securite anti-script
	$c = array('NOM_SITE_SPIP', 'URL_SITE_SPIP', 'EMAIL_WEBMASTER', 'CHARSET',
		'TITRE', 'SURTITRE', 'SOUSTITRE', 'DESCRIPTIF', 'CHAPO', 'TEXTE', 'PS', 'NOTES', 'INTRODUCTION', 'MESSAGE',
		'LESAUTEURS', 'EMAIL', 'NOM_SITE', 'LIEN_TITRE', 'URL_SITE', 'LIEN_URL', 'NOM', 'IP', 'BIO', 'TYPE', 'PGP'
	);
	reset($c);
	while (list(, $val) = each($c)) {
		$champs_pretraitement[$val][] = 'trim';
		$champs_posttraitement[$val][] = 'interdire_scripts';
	}

	// Textes courts : ajouter le traitement typographique
	$c = array('NOM_SITE_SPIP', 'SURTITRE', 'TITRE', 'SOUSTITRE', 'NOM_SITE', 'LIEN_TITRE', 'NOM');
	reset($c);
	while (list(, $val) = each($c)) {
		$champs_traitement[$val][] = 'typo';
	}

	// Chapo : ne pas l'afficher si article virtuel
	$c = array('CHAPO');
	reset($c);
	while (list(, $val) = each($c)) {
		$champs_traitement[$val][] = 'nettoyer_chapo';
	}

	// Textes longs : ajouter le traitement typographique + mise en forme
	$c = array('DESCRIPTIF', 'CHAPO', 'TEXTE', 'PS', 'BIO', 'MESSAGE');
	reset($c);
	while (list(, $val) = each($c)) {
		$champs_traitement[$val][] = 'traiter_raccourcis';
	}

	// Dates : ajouter le vidage des dates egales a 00-00-0000
	$c = array('DATE', 'DATE_REDAC', 'DATE_MODIF');
	reset($c);
	while (list(, $val) = each($c)) {
		$champs_traitement[$val][] = 'vider_date';
	}

	// URL_SITE : vider les url == 'http://'
	$c = array('URL_SITE_SPIP', 'URL_SITE', 'LIEN_URL');
	reset($c);
	while (list(, $val) = each($c)) {
		$champs_traitement[$val][] = 'vider_url';
	}

	// URLs : remplacer les & par &amp;
	$c = array('URL_SITE_SPIP', 'URL_SITE', 'LIEN_URL', 'PARAMETRES_FORUM',
		'URL_ARTICLE', 'URL_RUBRIQUE', 'URL_BREVE', 'URL_FORUM', 'URL_SYNDIC', 'URL_MOT', 'URL_DOCUMENT');
	reset($c);
	while (list(, $val) = each($c)) {
		$champs_traitement[$val][] = 'htmlspecialchars';
	}

	//
	// Construire un tableau associatif des champs de chaque type
	// avec l'intitule de la colonne mysql correspondante
	//

	$rows_articles = array(
		'ID_ARTICLE' => 'id_article',
		'ID_RUBRIQUE' => 'id_rubrique',
		'ID_SECTEUR' => 'id_secteur',
		'SURTITRE' => 'surtitre',
		'TITRE' => 'titre',
		'SOUSTITRE' => 'soustitre',
		'DESCRIPTIF' => 'descriptif',
		'CHAPO' => 'chapo',
		'TEXTE' => 'texte',
		'PS' => 'ps',
		'DATE' => 'date',
		'DATE_REDAC' => 'date_redac',
		'DATE_MODIF' => 'date_modif',
		'VISITES' => 'visites',
		'POINTS' => 'points'
	);
	$rows_auteurs = array(
		'ID_AUTEUR' => 'id_auteur',
		'NOM' => 'nom',
		'BIO' => 'bio',
		'EMAIL' => 'email',
		'NOM_SITE' => 'nom_site',
		'URL_SITE' => 'url_site',
		'PGP' => 'pgp',
		'POINTS' => 'points'
	);
	$rows_breves = array(
		'ID_BREVE' => 'id_breve',
		'ID_RUBRIQUE' => 'id_rubrique',
		'ID_SECTEUR' => 'id_rubrique',
		'DATE' => 'date_heure',
		'TITRE' => 'titre',
		'TEXTE' => 'texte',
		'NOM_SITE' => 'lien_titre',
		'URL_SITE' => 'lien_url',
		'LIEN_TITRE' => 'lien_titre',
		'LIEN_URL' => 'lien_url',
		'POINTS' => 'points'
	);
	$rows_forums = array(
		'ID_FORUM' => 'id_forum',
		'ID_PARENT' => 'id_parent',
		'ID_BREVE' => 'id_breve',
		'ID_RUBRIQUE' => 'id_rubrique',
		'ID_ARTICLE' => 'id_article',
		'TITRE' => 'titre',
		'TEXTE' => 'texte',
		'DATE' => 'date_heure',
		'NOM' => 'auteur',
		'EMAIL' => 'email_auteur',
		'NOM_SITE' => 'nom_site',
		'URL_SITE' => 'url_site',
		'IP' => 'ip'
	);
	$rows_documents = array(
		'ID_DOCUMENT' => 'id_document',
		'ID_VIGNETTE' => 'id_vignette',
		'ID_TYPE' => 'id_type',
		'TITRE' => 'titre',
		'DESCRIPTIF' => 'descriptif',
		'LARGEUR' => 'largeur',
		'HAUTEUR' => 'hauteur',
		'TAILLE' => 'taille',
		'TYPE_DOCUMENT' => 'type_document',
		'EXTENSION_DOCUMENT' => 'extension_document'
	);
	$rows_types_documents = array(
		'ID_TYPE' => 'id_type',
		'TITRE' => 'titre',
		'DESCRIPTIF' => 'descriptif',
		'EXTENSION' => 'extension'
	);
	$rows_mots = array(
		'ID_MOT' => 'id_mot',
		'TYPE' => 'type',
		'TITRE' => 'titre',
		'DESCRIPTIF' => 'descriptif',
		'TEXTE' => 'texte',
		'POINTS' => 'points',
		'ID_GROUPE' => 'id_groupe'
	);
	$rows_groupes_mots = array(
		'ID_GROUPE' => 'id_groupe',
		'TITRE' => 'titre'
	);
	$rows_rubriques = array(
		'ID_RUBRIQUE' => 'id_rubrique',
		'ID_PARENT' => 'id_parent',
		'ID_SECTEUR' => 'id_secteur',
		'TITRE' => 'titre',
		'DESCRIPTIF' => 'descriptif',
		'TEXTE' => 'texte',
		'DATE' => 'date',
		'POINTS' => 'points'
	);
	$rows_hierarchie = $rows_rubriques;

	$rows_signatures = array(
		'ID_SIGNATURE' => 'id_signature',
		'ID_ARTICLE' => 'id_article',
		'DATE' => 'date_time',
		'NOM' => 'nom_email',
		'EMAIL' => 'ad_email',
		'NOM_SITE' => 'nom_site',
		'URL_SITE' => 'url_site',
		'MESSAGE' => 'message'
	);

	$rows_syndication = array(
		'ID_SYNDIC' => 'id_syndic',
		'ID_RUBRIQUE' => 'id_rubrique',
		'ID_SECTEUR' => 'id_secteur',
		'NOM_SITE' => 'nom_site',
		'URL_SITE' => 'url_site',
		'URL_SYNDIC' => 'url_syndic',
		'DESCRIPTIF' => 'descriptif',
		'DATE' => 'date'
	);
	$rows_syndic_articles = array(
		'ID_SYNDIC_ARTICLE' => 'id_syndic_article',
		'ID_SYNDIC' => 'id_syndic',
		'TITRE' => 'titre',
		'URL_ARTICLE' => 'url',
		'DATE' => 'date',
		'LESAUTEURS' => 'lesauteurs',
		'DESCRIPTIF' => 'descriptif',
		'NOM_SITE' => 'nom_site',
		'URL_SITE' => 'url_site'
	);


	// Parser le texte et retourner le tableau racine

	$racine = parser_texte($texte, '');
}




//////////////////////////////////////////////////////////////////////////////
//
//              Calcul des squelettes
//
//////////////////////////////////////////////////////////////////////////////

//
// appliquer les filtres a un champ
//
function applique_filtres ($fonctions, $code) {
	global $flag_function_exists;

	if ($fonctions) {
		while (list(, $fonc) = each($fonctions)) {
			if ($fonc) {
				$arglist = '';
				if (ereg('([^\{\}]*)\{(.+)\}$', $fonc, $regs)) {
					$fonc = $regs[1];
					if (trim($regs[2]))
						$arglist = ','.$regs[2];
				}
				if ((!$flag_function_exists) OR function_exists($fonc))
					$code = "$fonc($code$arglist)";
				else
					$code = "'Erreur : filtre <b>&laquo; $fonc &raquo;</b> non d&eacute;fini'";
			}
		}
	}
	return $code;
}


//
// Generer le code PHP correspondant a un champ SPIP
//

function calculer_champ($id_champ, $id_boucle, $nom_var)
{
	global $les_notes;
	global $boucles;
	global $champs;
	global $flag_ob;
	global $flag_preg_replace;

	$idb = $id_boucle;

	//
	// Calculer $id_row en prenant la boucle la plus proche
	// (i.e. la plus profonde) qui autorise le champ demande
	//

	$offset_boucle = 0;
	while (strlen($idb)) {
		// $rows_articles, etc. : tables pregenerees contenant les correspondances
		// (nom du champ -> numero de colonne mysql) en fonction du type de requete
		$id_row = $GLOBALS['rows_'.$boucles[$idb]->type_requete][$champs[$id_champ]->nom_champ];
		if ($id_row) break;
		$idb = $boucles[$idb]->id_parent;
		$offset_boucle++;
	}

	//
	// Si cas general (le plus simple), generation
	// du code php effectuant le calcul du champ
	//

	if ($id_row) {
		$fonctions = $champs[$id_champ]->fonctions;

		if ($offset_boucle) $code = "\$pile_boucles[\$id_instance-$offset_boucle]->row[$id_row]";
		else $code = "\$row[$id_row]";

		$code = applique_filtres ($fonctions, $code);

		return "	\$$nom_var = $code;\n";
	}


	//
	// Ici traitement des cas particuliers
	//

	$milieu = '<blink>#'.$champs[$id_champ]->nom_champ.'</blink>'; // pour debugger les squelettes
	$milieu = "	\$$nom_var = '$milieu';\n";

	$fonctions = $champs[$id_champ]->fonctions;
	switch($nom_champ = $champs[$id_champ]->nom_champ) {

	//
	// Les logos (rubriques, articles...)
	//

	case 'LOGO_ARTICLE':
	case 'LOGO_ARTICLE_NORMAL':
	case 'LOGO_ARTICLE_RUBRIQUE':
	case 'LOGO_ARTICLE_SURVOL':
	case 'LOGO_AUTEUR':
	case 'LOGO_SITE':
	case 'LOGO_BREVE':
	case 'LOGO_BREVE_RUBRIQUE':
	case 'LOGO_MOT':
	case 'LOGO_RUBRIQUE':
	case 'LOGO_RUBRIQUE_NORMAL':
	case 'LOGO_RUBRIQUE_SURVOL':
	case 'LOGO_DOCUMENT':
		$milieu = '';
		ereg("^LOGO_(([a-zA-Z]+).*)$", $nom_champ, $regs);
		$type_logo = $regs[1];
		$type_objet = strtolower($regs[2]);
		$filtres = '';
		if ($fonctions) {
			while (list(, $nom) = each($fonctions)) {
				if (ereg('^(left|right|center|top|bottom)$', $nom))
					$align = $nom;
				else if ($nom == 'lien') {
					$flag_lien_auto = 'oui';
					$flag_stop = true;
				}
				else if ($nom == 'fichier') {
					$flag_fichier = 'oui';
					$flag_stop = true;
				}
				else if ($nom == '')	// double || signifie "on passe aux filtres"
					$flag_stop = true;
				else if (!$flag_stop) {
					$lien = $nom;
					$flag_stop = true;
				}
				else // apres un URL ou || ou |fichier ce sont des filtres (sauf left...lien...fichier)
					$filtres[] = $nom;
			}
			// recuperer les filtres s'il y en a
			$fonctions = $filtres;
		}
		if ($flag_lien_auto && !$lien) {
			$milieu .= "
			\$lien = generer_url_$type_objet(\$contexte['id_$type_objet']);
			";
		}
		else {
			$milieu .= '
			$lien = transformer_lien_logo($contexte, \''.addslashes($lien).'\');
			';
		}

		if ($type_logo == 'RUBRIQUE') {
			$milieu .= '
			$image = image_rubrique($contexte["id_rubrique"]);
			$logon = $image[0];
			$logoff = $image[1];
			';
		}
		else if ($type_logo == 'RUBRIQUE_NORMAL') {
			$milieu .= '
			$image = image_rubrique($contexte["id_rubrique"]);
			$logon = $image[0];
			$logoff = "";
			';
		}
		else if ($type_logo == 'RUBRIQUE_SURVOL') {
			$milieu .= '
			$image = image_rubrique($contexte["id_rubrique"]);
			$logon = $image[1];
			$logoff = "";
			';
		}
		else if ($type_logo == 'DOCUMENT'){
			$milieu .= '
			$image = image_document($contexte["id_document"]);
			$logon = $image[0];
			$logoff = "";
			';
		}
		else if ($type_logo == 'AUTEUR') {
			$milieu .= '
			$image = image_auteur($contexte["id_auteur"]);
			$logon = $image[0];
			$logoff = $image[1];
			';
		}
		else if ($type_logo == 'BREVE') {
			$milieu .= '
			$image = image_breve($contexte["id_breve"]);
			$logon = $image[0];
			$logoff = $image[1];
			';
		}
		else if ($type_logo == 'BREVE_RUBRIQUE') {
		  $milieu .= '
		  $image = image_breve($contexte["id_breve"]);
		  $logon = $image[0];
		  $logoff = $image[1];
		  if (!$logon) {
			$image = image_rubrique($contexte["id_rubrique"]);
			$logon = $image[0];
			$logoff = $image[1];
		  }
		  ';
		}
		else if ($type_logo == 'SITE') {
			$milieu .= '
			$image = image_site($contexte["id_syndic"]);
			$logon = $image[0];
			$logoff = $image[1];
			';
		}
		else if ($type_logo == 'MOT') {
			$milieu .= '
			$image = image_mot($contexte["id_mot"]);
			$logon = $image[0];
			$logoff = $image[1];
			';
		}
		else if ($type_logo == 'ARTICLE') {
			$milieu .= '
			$image = image_article($contexte["id_article"]);
			$logon = $image[0];
			$logoff = $image[1];
			';
		}
		else if ($type_logo == 'ARTICLE_NORMAL') {
			$milieu .= '
			$image = image_article($contexte["id_article"]);
			$logon = $image[0];
			$logoff = "";
			';
		}
		else if ($type_logo == 'ARTICLE_SURVOL') {
			$milieu .= '
			$image = image_article($contexte["id_article"]);
			$logon = $image[1];
			$logoff = "";
			';
		}
		else if ($type_logo == 'ARTICLE_RUBRIQUE') {
			$milieu .= '
			$image = image_article($contexte["id_article"]);
			$logon = $image[0];
			$logoff = $image[1];
			if (!$logon) {
				$image = image_rubrique($contexte["id_rubrique"]);
				$logon = $image[0];
				$logoff = $image[1];
			}
			';
		}
		if ($flag_fichier) $milieu .= "		\$$nom_var = \$logon;\n";
		else $milieu .= "		\$$nom_var = affiche_logos(\$logon, \$logoff, \$lien, '".addslashes($align)."');\n";
		break;

	//
	// Liste des auteurs d'un article
	//


	case 'LESAUTEURS':
		$milieu = '
		if ($i = $contexte["id_article"]) {
			$query_auteurs = "SELECT auteurs.nom, auteurs.email FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien WHERE lien.id_article=$i AND auteurs.id_auteur=lien.id_auteur";
			$result_auteurs = spip_query($query_auteurs);
			$auteurs = "";
			while($row_auteur = spip_fetch_array($result_auteurs)) {
				$nom_auteur = typo($row_auteur["nom"]);
				$email_auteur = $row_auteur["email"];
				if ($email_auteur) {
					$auteurs[] = "<A HREF=\"mailto:$email_auteur\">$nom_auteur</A>";
				}
				else {
					$auteurs[] = "$nom_auteur";
				}
			}
			if ($auteurs) $'.$nom_var.' = join($auteurs, ", ");
			else $'.$nom_var.' = "";
		}
		';
		break;

	//
	// Introduction (d'un article, d'une breve ou d'un message de forum)
	//

	case 'INTRODUCTION':
		switch ($boucles[$id_boucle]->type_requete) {
		case 'articles':
			$milieu = 'if (strlen($pile_boucles[$id_instance]->row["descriptif"]) > 0) {
				$'.$nom_var.' = propre($pile_boucles[$id_instance]->row["descriptif"]);
				}
				else {
				$'.$nom_var.' = PtoBR(propre(supprimer_tags(couper_intro($pile_boucles[$id_instance]->row["chapo"]."\n\n\n".$pile_boucles[$id_instance]->row["texte"], 500))));
				}';
			break;
		case 'breves':
			$code = "PtoBR(propre(supprimer_tags(couper_intro(\$pile_boucles[\$id_instance]->row['texte'], 300))))";
			break;
		case 'forums':
			$code = "PtoBR(propre(supprimer_tags(couper_intro(\$pile_boucles[\$id_instance]->row['texte'], 600))))";
			break;
		}
		break;

	//
	// Divers types de champs
	//

	case 'URL_SITE_SPIP':
		$code = "lire_meta('adresse_site')";
		break;

	case 'NOM_SITE_SPIP':
		$code = "lire_meta('nom_site')";
		break;

	case 'EMAIL_WEBMASTER':
		$code = "lire_meta('email_webmaster')";
		break;

	case 'CHARSET':
		$code = "lire_meta('charset')";
		break;

	case 'PUCE':
		$code = "propre('- ')";
		break;

	case 'URL_ARTICLE':
		$code = "generer_url_article(\$contexte['id_article'])";
		break;

	case 'URL_RUBRIQUE':
		$code = "generer_url_rubrique(\$contexte['id_rubrique'])";
		break;

	case 'URL_BREVE':
		$code = "generer_url_breve(\$contexte['id_breve'])";
		break;

	case 'URL_FORUM':
		$code = "generer_url_forum(\$contexte['id_forum'])";
		break;

	case 'URL_MOT':
		$code = "generer_url_mot(\$contexte['id_mot'])";
		break;

	case 'URL_DOCUMENT':
		$code = "generer_url_document(\$contexte['id_document'])";
		break;

	case 'NOTES':
		$code = '$GLOBALS["les_notes"]';
		break;

	case 'COMPTEUR_BOUCLE':
		$code = '$pile_boucles[$id_instance]->compteur_boucle';
		break;

	case 'TOTAL_BOUCLE':
		$code = '$pile_boucles[$id_instance_cond]->total_boucle';
		break;

	case 'POPULARITE':
		$code = 'ceil(min(100, 100 * $pile_boucles[$id_instance]->row[\'popularite\'] / max(1 , 0 + lire_meta(\'popularite_max\'))))';
		break;

	case 'POPULARITE_ABSOLUE':
		$code = 'ceil($pile_boucles[$id_instance]->row[\'popularite\'])';
		break;

	case 'POPULARITE_SITE':
		$code = 'ceil(lire_meta(\'popularite_total\'))';
		break;

	case 'POPULARITE_MAX':
		$code = 'ceil(lire_meta(\'popularite_max\'))';
		break;

	//
	// Inserer directement un document dans le squelette
	//
	case 'EMBED_DOCUMENT':
		if ($fonctions) $fonctions = join($fonctions, "|");
		$milieu = "
			include_ecrire('inc_documents.php3');
			\$$nom_var = embed_document(\$contexte['id_document'], '$fonctions', false) ;
		";
		$fonctions = "";
		break;


	//
	// Formulaire de recherche sur le site
	//
	case 'FORMULAIRE_RECHERCHE':
		if ($fonctions) {
			list(, $lien) = each($fonctions);	// le premier est un url
			while (list(, $filtre) = each($fonctions)) {
				$filtres[] = $filtre;			// les suivants sont des filtres
			}
			$fonctions = $filtres;
		}
		if (!$lien) $lien = 'recherche.php3';
		$milieu = "
		if (lire_meta('activer_moteur') != 'oui') {
			\$$nom_var = '';
		}
		else {
			\$$nom_var = \"\n<a name='formulaire_recherche'></a>\n\";
			\$$nom_var .= \"\n<form action='$lien' method='get'>\";
			\$$nom_var .= \"\n<label for='formulaire_recherche' style='display: none'>Rechercher dans le site&nbsp;: </label>\";
			\$$nom_var .= \"\n<input type='text' id='formulaire_recherche' name='recherche' value='Rechercher' size='20' class='formrecherche'>\";
			\$$nom_var .= \"\n</form>\";
		}
		";
		break;

	//
	// Formulaire d'inscription comme redacteur
	// (dans inc-formulaires.php3)
	case 'FORMULAIRE_INSCRIPTION':
		$milieu = '
		$request_uri = $GLOBALS["REQUEST_URI"];
		$accepter_inscriptions = lire_meta("accepter_inscriptions");

		if ($accepter_inscriptions == "oui") {
			$'.$nom_var.' = "<"."?php include_local(\"inc-formulaires.php3\"); formulaire_inscription(\"redac\"); ?".">";
		}
		else {
			$'.$nom_var.' = "";
		}
		';

		break;

	//
	// Formulaire pour ecrire a l'auteur
	//
	case 'FORMULAIRE_ECRIRE_AUTEUR':

		$milieu = '
		if (email_valide($row[\'email\'])) {
			$email = trim($row[\'email\']);
			$'.$nom_var.' = "<'.'?php
				include (\'inc-formulaires.php3\'); formulaire_ecrire_auteur(".$row[\'id_auteur\'].",\'$email\');
			?'.'>";
		}
		';

		break;

	//
	// Formulaire de signature d'une petition
	//
	case 'FORMULAIRE_SIGNATURE':

		$milieu = '
		$request_uri = $GLOBALS["REQUEST_URI"];
		$accepter_inscriptions = lire_meta("accepter_inscriptions");

		$query_petition = "SELECT * FROM spip_petitions WHERE id_article=$contexte[id_article]";
 		$result_petition = spip_query($query_petition);

		if ($row_petition = spip_fetch_array($result_petition)) {
			$'.$nom_var.' = "<"."?php include_local(\"inc-formulaires.php3\"); formulaire_signature($contexte[id_article]); ?".">";
		}
		else {
			$'.$nom_var.' = "";
		}
		';

		break;

	//
	// Formulaire de referencement d'un site
	//
	case 'FORMULAIRE_SITE':

		$milieu = '
		$request_uri = $GLOBALS["REQUEST_URI"];
		$proposer_sites = lire_meta("proposer_sites");

		if ($proposer_sites == "2") {
			$'.$nom_var.' = "<"."?php include_local(\"inc-formulaires.php3\"); formulaire_site($contexte[id_rubrique]); ?".">";
		}
		else {
			$'.$nom_var.' = "";
		}
		';

		break;

	//
	// Champ testant la presence d'une petition
	//
	case 'PETITION':
		$milieu = '
		$query_petition = "SELECT id_article FROM spip_petitions WHERE id_article=$contexte[id_article]";
		$result_petition = spip_query($query_petition);
		if (spip_num_rows($result_petition) > 0) $'.$nom_var.' = " ";
		else $'.$nom_var.' = "";
		';
		break;

	//
	// Formulaire de reponse a un forum
	//
	case 'FORMULAIRE_FORUM':
		$milieu = '
		switch ($pile_boucles[$id_instance]->type_requete) {
		case "articles":
			$'.$nom_var.' = retour_forum(0, 0, $contexte["id_article"], 0, 0);
			break;

		case "breves":
			$'.$nom_var.' = retour_forum(0, 0, 0, $contexte["id_breve"], 0);
			break;

		case "forums":
			$'.$nom_var.' = retour_forum($contexte["id_rubrique"], $contexte["id_forum"], $contexte["id_article"], $contexte["id_breve"], $contexte["id_syndic"]);
			break;

		case "rubriques":
			$'.$nom_var.' = retour_forum($contexte["id_rubrique"], 0, 0, 0, 0);
			break;

		case "syndication":
			$'.$nom_var.' = retour_forum(0, 0, 0, 0, $contexte["id_syndic"]);
			break;

		default:
			$'.$nom_var.' = retour_forum($contexte["id_rubrique"], $contexte["id_forum"], $contexte["id_article"], $contexte["id_breve"], $contexte["id_syndic"]);
			break;
		}
		';
		break;

	//
	// Parametres d'appel du formulaire de reponse a un forum
	//
	case 'PARAMETRES_FORUM':
		$milieu = '
		$request_uri = $GLOBALS["REQUEST_URI"];
		$http_get_vars = $GLOBALS["HTTP_GET_VARS"];
		$forums_publics = lire_meta("forums_publics");
		if (($contexte["accepter_forum"] == "" AND $forums_publics != "non") OR ($contexte["accepter_forum"] != "" AND $contexte["accepter_forum"] != "non")) {
			$lien = substr($request_uri, strrpos($request_uri, "/") + 1);
			if (!$lien_retour = $http_get_vars["retour"])
				$lien_retour = $lien;
			$lien_retour = rawurlencode($lien_retour);

			switch ($pile_boucles[$id_instance]->type_requete) {
			case "articles":
				$'.$nom_var.' = "id_article=$contexte[id_article]";
				break;

			case "breves":
				$'.$nom_var.' = "id_breve=$contexte[id_breve]";
				break;

			case "rubriques":
				$'.$nom_var.' = "id_rubrique=$contexte[id_rubrique]";
				break;

			case "syndication":
				$'.$nom_var.' = "id_syndic=$contexte[id_syndic]";
				break;

			case "forums":
			default:
				$liste_champs = array ("id_article","id_breve","id_rubrique","id_syndic","id_forum");
				unset($element);
				while (list(,$champ) = each ($liste_champs)) {
					if ($contexte[$champ]) $element[] = "$champ=$contexte[$champ]";
				}
				if ($element) $'.$nom_var.' = join("&",$element);
				break;

			}
			$'.$nom_var.' .= "&retour=$lien_retour";
		}
		else {
			$'.$nom_var.' = "";
		}
		';
		break;

	//
	// Debut et fin de surlignage auto des mots de la recherche
	//
	case 'DEBUT_SURLIGNE':
		if ($flag_ob AND $flag_preg_replace) {
			$milieu = '
				$'.$nom_var.' = "<"."?php if (\$var_recherche) { \$mode_surligne = debut_surligne(\$var_recherche, \$mode_surligne); } ?".">";
			';
		}
		break;
	case 'FIN_SURLIGNE':
		if ($flag_ob AND $flag_preg_replace) {
			$milieu = '
				$'.$nom_var.' = "<"."?php if (\$var_recherche) { \$mode_surligne = fin_surligne(\$var_recherche, \$mode_surligne); } ?".">";
			';
		}
		break;

	//
	// Formulaires de login
	//
	case 'LOGIN_PRIVE':
		$milieu = '
			$'.$nom_var.' = "<"."?php include_local (\'inc-login.php3\');
				login (\'\', \'prive\'); ?".">";
			';
		break;

	case 'LOGIN_PUBLIC':
		$lacible = '\$GLOBALS[\'clean_link\']';
		if ($fonctions) {
			$filtres = array();
			while (list(, $nom) = each($fonctions))
				$lacible = "new Link('".$nom."')";
			$fonctions = $filtres;
		}
		$milieu = '
			$'.$nom_var.' = "<"."?php include_local (\'inc-login.php3\');
				\$cible = ' . $lacible . ';
				login (\$cible, false); ?".">";
			';
		break;

	case 'URL_LOGOUT':
		if ($fonctions) {
			$url = "&url=".$fonctions[0];
			$fonctions = array();
		} else {
			$url = '&url=\'.urlencode(\$clean_link->getUrl()).\'';
		}
		$milieu = '
			$'.$nom_var.' = "<"."?php
				if (\$GLOBALS[\'auteur_session\'][\'login\']) {
					echo \'spip_cookie.php3?logout_public=\'.\$GLOBALS[\'auteur_session\'][\'login\'].\'' . $url . '\';
				} ?".">";
			';
		break;


	//
	// Boutons d'administration
	//
	case 'FORMULAIRE_ADMIN':
		$milieu = '
			$'.$nom_var.' = "<"."?php \$GLOBALS[\"flag_boutons_admin\"] = true;
				if (\$GLOBALS[\"HTTP_COOKIE_VARS\"][\"spip_admin\"]) {
					include_local(\"inc-admin.php3\");
					afficher_boutons_admin();
				} ?".">";
		';
		break;

	} // switch

	if (!$code) $code = "\$$nom_var";

	$code = applique_filtres ($fonctions, $code);

	$milieu .= "	\$$nom_var = $code;\n";

	return $milieu;
}


//
// Generer le code PHP correspondant a une boucle
//

function calculer_boucle($id_boucle, $prefix_boucle)
{
	global $boucles;

	$func = $prefix_boucle.$id_boucle;
	$boucle = $boucles[$id_boucle];

	//
	// Ecrire le debut de la fonction
	//

	$texte .= "function $func".'($contexte) {
	global $pile_boucles, $ptr_pile_boucles, $id_doublons, $fichier_cache, $requetes_cache, $syn_rubriques, $rubriques_publiques, $id_article_img;

	';

	//
	// Recherche : recuperer les hash a partir de la chaine de recherche
	//

	if (strpos($boucle->requete, '$hash_recherche')) {
		$texte .= '
		global $recherche, $hash_recherche;
		if (!$hash_recherche) {
			$s = nettoyer_chaine_indexation(urldecode($recherche));
			$regs = separateurs_indexation()." ";
			$s = split("[$regs]+", $s);

			unset($dico);
			unset($h);
			while (list(, $val) = each($s)) {
				if (strlen($val) > 3) {
					$dico[] = "dico LIKE \"$val%\"";
				}
			}
			if ($dico) {
				// le hex est indispensable : apparemment bug de mysql
				// sur output decimal 64 bits (a cause du unsigned ?)
				$query2 = "SELECT HEX(hash) AS hx FROM spip_index_dico WHERE ".join(" OR ", $dico);
				$result2 = spip_query($query2);
				while ($row2 = spip_fetch_array($result2)) {
					$h[] = "0x".$row2["hx"];
				}
			}
			if ($h) $hash_recherche = join(",", $h);
			else $hash_recherche = "0";
		}
		';
	}

	//
	// Recuperation du contexte et creation de l'instance de boucle
	//

	$texte .= '
	if ($contexte) {
		reset($contexte);
		while (list($key, $val) = each($contexte)) $$key = addslashes($val);
	}

	$id_instance = $ptr_pile_boucles++;
	$id_instance_cond = $id_instance;

	$instance = new InstanceBoucle;

	$instance->id_boucle = \''.$boucle->id_boucle.'\';
	$instance->requete = "'.$boucle->requete.'";
	$instance->type_requete = \''.$boucle->type_requete.'\';
	$instance->separateur = \''.$boucle->separateur.'\';
	$instance->doublons = \''.$boucle->doublons.'\';
	$instance->partie = \''.$boucle->partie.'\';
	$instance->total_parties = \''.$boucle->total_parties.'\';

	$instance->id_instance = $id_instance;

	$pile_boucles[$id_instance] = $instance;

	$retour = "";
	';

	//
	// Preparation du code de fermeture
	//

	$code_fin = "
	\$ptr_pile_boucles--;
	return \$retour;\n}\n";

	$type_boucle = $boucle->type_requete;
	$doublons = $boucle->doublons;
	$partie = $boucle->partie;
	$total_parties = $boucle->total_parties;

	//
	// Boucle recursive : simplement appeler la boucle interieure
	//
	if ($type_boucle == 'boucle') {
		$texte .= calculer_liste(array($boucles[$boucle->requete]), $prefix_boucle, $id_boucle);
		$texte .= $code_fin;
		return $texte;
	}

	//
	// Boucle 'hierarchie' : code specifique
	//
	else if ($type_boucle == 'hierarchie') {
	
		$texte .= '
		if ($id_article || $id_syndic) $hierarchie = construire_hierarchie($id_rubrique);
		else $hierarchie = construire_hierarchie($id_parent);
		if ($hierarchie) {
			$hierarchie = explode("-", substr($hierarchie, 0, -1));
			$deb_class = 0;
			if (ereg("([0-9]+),([0-9]*)", $instance->requete, $match)){
				$deb_class = $match[1];
				if ($match[2]) $fin_class = $match[2] + $deb_class;
			}
			if (!$fin_class OR $fin_class > sizeof($hierarchie)) $fin_class = sizeof($hierarchie);
	
			$hierarchie = join(",", $hierarchie);
			$query = "SELECT *, FIELD(id_rubrique, $hierarchie) AS _field FROM spip_rubriques WHERE id_rubrique IN ($hierarchie)";
			if ($instance->doublons == "oui") $query .= " AND id_rubrique NOT IN ($id_doublons[rubriques])";
			$query .= " ORDER BY _field LIMIT $deb_class, ".($fin_class - $deb_class);
			$result = spip_query($query);

			if ($result) while ($row = spip_fetch_array($result)) {

				$boucles[$id_boucle]->row = $row;
				if ($retour) $retour .= $instance->separateur;

				$contexte["id_rubrique"] = $row["id_rubrique"];
				$contexte["id_parent"] = $row["id_parent"];
				$contexte["id_secteur"] = $row["id_secteur"];
				$contexte["date"] = $row["date"];
	
				if ($doublons == "oui") {
					$id_doublons["rubriques"] .= ",".$row["id_rubrique"];
				}

		';
		$texte .= calculer_liste($boucle->milieu, $prefix_boucle, $id_boucle);
		$texte .= '
				} // if
//			} // for
		} // if
		';
		$texte .= $code_fin;
		return $texte;
	}


	//
	// Pour les forums, ajouter le code de gestion du cache
	// et de l'activation / desactivation par article
	//
	if ($type_boucle == 'forums') {

		$texte .= '
		if (!$id_rubrique AND !$id_article AND !$id_breve)
			$my_id_forum = $id_forum;
		else
			$my_id_forum = 0;
		if (!$id_article) $id_article = 0;
		if (!$id_rubrique) $id_rubrique = 0;
		if (!$id_breve) $id_breve = 0;
		$valeurs = "$id_article, $id_rubrique, $id_breve, $my_id_forum, \'$fichier_cache\'";
		
		if (!$requetes_cache[$valeurs]) {
			$query_cache = "INSERT INTO spip_forum_cache (id_article, id_rubrique, id_breve, id_forum, fichier) VALUES ($valeurs)";
			spip_query($query_cache);
			$requetes_cache[$valeurs] = 1;
		}
		';

	} // forums



	//
	// Ecrire le code d'envoi de la requete, de recuperation du nombre
	// de resultats et de traitement des boucles par parties (e.g. 1/2)
	//

	$texte .= '	$query = $instance->requete;
	$result = @spip_query($query);
	if (!$result) {
		include_ecrire("inc_presentation.php3");
		$retour .= "<tt><br><br><blink>&lt;BOUCLE'.$id_boucle.'&gt;</blink><br>\n".
		"<b>Erreur dans la requ&ecirc;te envoy&eacute;e &agrave; MySQL :</b><br>\n".
		htmlspecialchars($query)."<br>\n<font color=\'red\'><b>&gt; ".
		spip_sql_error()."</b></font><br>\n".
		"<blink>&lt;/BOUCLE'.$id_boucle.'&gt;</blink></tt>\n";
		$retour .= "<" ."?php
			if (\$GLOBALS[\'spip_admin\']) {
			include_ecrire(\'inc_presentation.php3\');
			echo aide(\'erreur_mysql\');
		} ?".">";
		$retour .= "<br><br>\n"; // debugger les squelettes
	}
	$total_boucle = @spip_num_rows($result);
	$pile_boucles[$id_instance]->num_rows = $total_boucle;
	';

	if ($partie AND $total_parties) {
		$flag_parties = true;
		$texte .= '
		$debut_boucle = floor(($total_boucle * ($instance->partie - 1) + $instance->total_parties - 1) / $instance->total_parties) + 1;
		$fin_boucle = floor(($total_boucle * ($instance->partie) + $instance->total_parties - 1) / $instance->total_parties);
		$pile_boucles[$id_instance]->total_boucle = $fin_boucle - $debut_boucle + 1;
		';
	}
	else {
		$flag_parties = false;
		$texte .= '
		$pile_boucles[$id_instance]->total_boucle = $total_boucle;
		';
	}

	$texte .= '	
	$pile_boucles[$id_instance]->compteur_boucle = 0;
	$compteur_boucle = 0;
	';

	//
	// Ecrire le code de recuperation des resultats
	//

	$texte .= '
	while ($row = @spip_fetch_array($result)) {
	$compteur_boucle++;
	';

	if ($flag_parties) {
		$texte .= '
		if ($compteur_boucle >= $debut_boucle AND $compteur_boucle <= $fin_boucle) {
		';
	}
	$texte .= '
	$pile_boucles[$id_instance]->compteur_boucle++;
	$pile_boucles[$id_instance]->row = $row;
	if ($retour) $retour .= $instance->separateur;
	';

	//
	// Traitement different selon le type de boucle
	//

	switch($type_boucle) {

	case 'articles':
		$texte .= '
		$contexte["id_article"] = $row["id_article"];
		$contexte["id_rubrique"] = $row["id_rubrique"];
		$contexte["id_secteur"] = $row["id_secteur"];
		$contexte["date"] = $row["date"];
		$contexte["accepter_forum"] = $row["accepter_forum"];
		if ($instance->doublons == "oui") $id_doublons["articles"] .= ",".$row["id_article"];
		';
		break;

	case 'breves':
		$texte .= '
		$contexte["id_breve"] = $row["id_breve"];
		$contexte["id_rubrique"] = $row["id_rubrique"];
		$contexte["id_secteur"] = $row["id_rubrique"];
		$contexte["date"] = $row["date_heure"];
		if ($instance->doublons == "oui") $id_doublons["breves"] .= ",".$row["id_breve"];
		';
		break;

	case 'syndication':
		$texte .= '
		$contexte["id_syndic"] = $row["id_syndic"];
		$contexte["id_rubrique"] = $row["id_rubrique"];
		$contexte["id_secteur"] = $row["id_secteur"];
		$contexte["url_site"] = $row["url_site"];
		$contexte["date"] = $row["date"];
		if ($instance->doublons == "oui") $id_doublons["syndication"] .= ",".$row["id_syndic"];
		';
		break;
		
	case 'documents':
		$texte .= '
		$contexte["id_document"] = $row["id_document"];
		$contexte["id_vignette"] = $row["id_vignette"];
		$contexte["id_type"] = $row["id_type"];
		if ($instance->doublons == "oui") $id_doublons["documents"] .= ",".$row["id_document"];
		';
		break;

	case 'types_documents':
		$texte .= '
		$contexte["id_type"] = $row["id_type"];
		if ($instance->doublons == "oui") $id_doublons["documents"] .= ",".$row["id_document"];
		';
		break;

	case 'syndic_articles':
		$texte .= '
		$contexte["id_syndic"] = $row["id_syndic"];
		$contexte["id_syndic_article"] = $row["id_syndic_article"];
		$contexte["date"] = $row["date"];
		if ($instance->doublons == "oui") $id_doublons["syndic_articles"] .= ",".$row["syndic_articles"];
		';
		break;

	case 'rubriques':
		$texte .= '
		$contexte["id_rubrique"] = $row["id_rubrique"];
		$contexte["id_parent"] = $row["id_parent"];
		$contexte["id_secteur"] = $row["id_secteur"];
		$contexte["date"] = $row["date"];
		if ($instance->doublons == "oui") $id_doublons["rubriques"] .= ",".$row["id_rubrique"];
		$syn_rubrique .= ",".$row["id_rubrique"].",";
		';
		break;

	case 'forums':
		$texte .= '
		$contexte["id_forum"] = $row["id_forum"];
		$contexte["id_rubrique"] = $row["id_rubrique"];
		$contexte["id_article"] = $row["id_article"];
		$contexte["id_breve"] = $row["id_breve"];
		$contexte["id_parent"] = $row["id_parent"];
		$contexte["date"] = $row["date_heure"];
		if ($instance->doublons == "oui") $id_doublons["forums"] .= ",".$row["id_forum"];
		';
		break;

	case 'auteurs':
		$texte .= '
		$contexte["id_auteur"] = $row["id_auteur"];
		if ($instance->doublons == "oui") $id_doublons["auteurs"] .= ",".$row["id_auteur"];
		';
		break;

	case 'signatures':
		$texte .= '
		$contexte["id_signature"] = $row["id_signature"];
		$contexte["date"] = $row["date_time"];
		if ($instance->doublons == "oui") $id_doublons["signatures"] .= ",".$row["id_signature"];
		';
		break;

	case 'mots':
		$texte .= '
		$contexte["id_mot"] = $row["id_mot"];
		$contexte["type"] = $row["type"];
		$contexte["id_groupe"] = $row["id_groupe"];
		if ($instance->doublons == "oui") $id_doublons["mots"] .= ",".$row["id_mot"];
		';
		break;

	case 'groupes_mots':
		$texte .= '
		$contexte["id_groupe"] = $row["id_groupe"];
		if ($instance->doublons == "oui") $id_doublons["groupes_mots"] .= ",".$row["id_groupe"];
		';
		break;
	}

	//
	// Inclusion du code correspondant a l'interieur de la boucle
	//
	$texte .= calculer_liste($boucle->milieu, $prefix_boucle, $id_boucle);

	//
	// Fermeture de la boucle spip_fetch_array et liberation des resultats
	//
	
	if ($flag_parties) {
		$texte .= '
		}
		';
	}
	$texte .= '
	}
	@spip_free_result($result);
';
	$texte .= $code_fin;
	return $texte;
}


//
// Generer le code PHP correspondant a un texte brut
//

function calculer_texte($texte)
{
	$code = "";

	// Reperer les directives d'inclusion de squelette
	while (ereg("<INCLU[DR]E[[:space:]]*\(([-_0-9a-zA-Z. ]+)\)(([[:space:]]*\{[^}]*\})*)[[:space:]]*>", $texte, $match)) {
		$s = $match[0];
		$p = strpos($texte, $s);
		$debut = substr($texte, 0, $p);
		$texte = substr($texte, $p + strlen($s));
		if ($debut)
			$code .= "	\$retour .= '".ereg_replace("([\\\\'])", "\\\\1", $debut)."';\n";

		// Traiter la directive d'inclusion
		$fichier = $match[1];
		ereg('^\\{(.*)\\}$', trim($match[2]), $params);
		$code .= "	\$retour .= '<"."?php ';\n";
		$code .= "	\$retour .= '\$contexte_inclus = \'\'; ';\n";

		if ($params) {
			// Traiter chaque parametre de contexte
			$params = split("\}[[:space:]]*\{", $params[1]);
			reset($params);
			while (list(, $param) = each($params)) {
				if (ereg("^([_0-9a-zA-Z]+)[[:space:]]*(=[[:space:]]*([^}]+))?$", $param, $args)) {
					$var = $args[1];
					$val = $args[3];
					if ($val) 
						$code .= "	\$retour .= '\$contexte_inclus[$var] = \'".addslashes($val)."\'; ';\n";
					else
						$code .= "	\$retour .= '\$contexte_inclus[$var] = \''.addslashes(\$contexte[$var]).'\'; ';\n";
				}
			}
		}
		$code .= "	\$retour .= 'include(\'$fichier\'); ?".">';\n";
	}

	if ($texte)
		$code .= "	\$retour .= '".ereg_replace("([\\\\'])", "\\\\1", $texte)."';\n";

	return $code;
}


//
// Generer le code PHP correspondant a une liste d'objets syntaxiques
//

function calculer_liste($tableau, $prefix_boucle, $id_boucle)
{
	global $boucles;
	global $champs;
	global $nb_milieu;

	$texte = '';
	if (!$tableau) return $texte;

	reset($tableau);
	while (list(, $objet) = each($tableau)) {
		$milieu = '';
		switch($objet->type) {

		/////////////////////
		// Texte
		//
		case 'texte':
			$texte .= calculer_texte($objet->texte);
			break;


		/////////////////////
		// Boucle
		//
		case 'boucle':
			$nb_milieu++;
			$nom_var = "milieu$nb_milieu";
			$nom_func = $prefix_boucle.$objet->id_boucle;
			$texte .= "	\$$nom_var = $nom_func(\$contexte);\n";
			$texte .= "	if (\$$nom_var) {\n";
			if ($s = $objet->cond_avant) {
				$texte .= calculer_liste($s, $prefix_boucle, $id_boucle);
			}
			$texte .= "	\$retour .= \$$nom_var;\n";
			if ($s = $objet->cond_apres) {
				$texte .= "	\$id_instance_cond++;\n";
				$texte .= calculer_liste($s, $prefix_boucle, $id_boucle);
				$texte .= "	\$id_instance_cond--;\n";
			}
			$texte .= "	}\n";
			if ($s = $objet->cond_altern) {
				$texte .= "	else {\n";
				$texte .= "	\$id_instance_cond++;\n";
				$texte .= calculer_liste($s, $prefix_boucle, $id_boucle);
				$texte .= "	\$id_instance_cond--;\n";
				$texte .= "	}\n";
			}
			$texte .= "	unset(\$$nom_var);\n";

			break;


		/////////////////////
		// Champ
		//
		case 'champ':
			$nb_milieu++;
			$nom_var = "milieu$nb_milieu";
			$texte .= "	\$id_article_img = \$contexte[\"id_article\"];\n";
			$texte .= calculer_champ($objet->id_champ, $id_boucle, $nom_var);

			$texte .= "	if (\$$nom_var) {\n";
			if ($s = $objet->cond_avant) {
				$texte .= calculer_liste($s, $prefix_boucle, $id_boucle);
			}
			$texte .= "	\$retour .= \$$nom_var;\n";
			if ($s = $objet->cond_apres) {
				$texte .= calculer_liste($s, $prefix_boucle, $id_boucle);
			}
			$texte .= "	}\n";
			$texte .= "	unset(\$$nom_var);\n";
			break;

		} // switch

	} // while

	return $texte;
}


//
// Calculer le squelette : i.e. generer le fichier PHP correspondant
//

function calculer_squelette($squelette, $fichier) {
	global $racine;
	global $boucles;

	$boucles = '';
	$racine = '';

	$html = join(file("$squelette.html"), "");
	parser($html);

	$squelette_nom = ereg_replace("[^a-zA-Z0-9_]", "_", $squelette);
	$func = 'squelette_'.$squelette_nom.'_executer';
	$prefix = $func.'_boucle';
	$define = strtoupper("_SKEL_$squelette_nom");

	// Debut du fichier
	$texte .= "<"."?php\n\n";
	$texte .= "\$func_squelette_executer = '$func';\n\n";
	$texte .= "if (defined(\"$define\")) return;\n";
	$texte .= "define(\"$define\", \"1\");\n\n\n";

	// Calculer le code PHP des boucles
	if ($boucles) {
		reset($boucles);
		while (list($id_boucle, ) = each($boucles)) {
			$texte .= calculer_boucle($id_boucle, $prefix);
			$texte .= "\n\n";
		}
	}
	
	// Calculer le code PHP de la racine
	$texte .= "function $func(\$contexte) {\n";
	$texte .= " global \$pile_boucles, \$id_instance_cond;\n \$pile_boucles = Array();\n \$id_instance_cond = -1;\n"; // pour #TOTAL_BOUCLE
	$texte .= calculer_liste($racine, $prefix, '');
	$texte .= "	return \$retour;\n";
	$texte .= "}\n\n";

	// Fin du fichier
	$texte .= '?'.'>';

	$f = fopen($fichier, "wb");
	fwrite($f, $texte);
	fclose($f);
}


?>
