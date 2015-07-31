<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CALCUL_SQUEL")) return;
define("_INC_CALCUL_SQUEL", "1");


include_local("inc-champ-squel.php3");


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
	var $lang_select;
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
		include_ecrire ("inc_presentation.php3");
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
						"$table.visites,$table.popularite,$table.statut,$table.accepter_forum,$table.lang,$table.id_trad";
					if (ereg('\#(TEXTE|INTRODUCTION)', $milieu))
						$s .= ",$table.texte";
					if (ereg('\#(CHAPO|INTRODUCTION)', $milieu))
						$s .= ",$table.chapo";
					if (ereg('\#(DESCRIPTIF|INTRODUCTION)', $milieu))
						$s .= ",$table.descriptif";
					if (ereg('\#(PS)', $milieu))
						$s .= ",$table.ps";
					if (ereg('\#(EXTRA)', $milieu))
						$s .= ",$table.extra";
					if (ereg("\#(NOM_SITE|URL_SITE)", $milieu))
						$s .= ",$table.nom_site,$table.url_site";
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
					else if (ereg('^lang_select(=(oui|non))?$', $param, $match)) {
						if (!$lang_select = $match[2]) $lang_select = 'oui';
					}
					else if (ereg('^ *"([^"]*)" *$', $param, $match)) {
						$separateur = ereg_replace("'","\'",$match[1]);
					}
					else if (ereg('^([0-9]+),([0-9]*)', $param, $match)) {
						$req_limit = $match[1].','.$match[2];
					}
					else if (ereg('^debut([-_a-zA-Z0-9]+),([0-9]*)$', $param, $match)) {
						$debut_lim = "debut".$match[1];
						$req_limit = '".intval($GLOBALS[\'HTTP_GET_VARS\'][\''.$debut_lim.'\']).",'.$match[2];
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
					// Gerer les traductions
					else if ($param == 'traduction') {
						$req_where[] = "$table.id_trad > 0 AND $table.id_trad = \$id_trad";
					}
					else if ($param == 'origine_traduction') {
						$req_where[] = "$table.id_trad = $table.id_article";
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
						if (ereg("^(date|mois|annee|age|age_relatif|jour_relatif|mois_relatif|annee_relatif)(_redac)?$", $col, $regs)) {
							$col = $regs[1];
							if ($regs[2]) {
								$date_orig = "$table.date_redac";
								$date_compare = 'date_redac';
							}
							else {
								$date_orig = "$table.$col_date";
								$date_compare = 'date';
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
								$col = "(LEAST((UNIX_TIMESTAMP(now())-UNIX_TIMESTAMP($date_orig))/86400, TO_DAYS(now())-TO_DAYS($date_orig), DAYOFMONTH(now())-DAYOFMONTH($date_orig)+30.4368*(MONTH(now())-MONTH($date_orig))+365.2422*(YEAR(now())-YEAR($date_orig))))";
								$col_table = '';
							}
							else if ($col == 'age_relatif') {
								$col = "LEAST((UNIX_TIMESTAMP('\$$date_compare')-UNIX_TIMESTAMP($date_orig))/86400, TO_DAYS('\$$date_compare')-TO_DAYS($date_orig), DAYOFMONTH('\$$date_compare')-DAYOFMONTH($date_orig)+30.4368*(MONTH('\$$date_compare')-MONTH($date_orig))+365.2422*(YEAR('\$$date_compare')-YEAR($date_orig)))";
								$col_table = '';
							}
							else if ($col == 'jour_relatif') {
								$col = "LEAST(TO_DAYS('\$$date_compare')-TO_DAYS($date_orig), DAYOFMONTH('\$$date_compare')-DAYOFMONTH($date_orig)+30.4368*(MONTH('\$$date_compare')-MONTH($date_orig))+365.2422*(YEAR('\$$date_compare')-YEAR($date_orig)))";
								$col_table = '';
							}
							else if ($col == 'mois_relatif') {
								$col = "(MONTH('\$$date_compare')-MONTH($date_orig)+12*(YEAR('\$$date_compare')-YEAR($date_orig)))";
								$col_table = '';
							}
							else if ($col == 'annee_relatif') {
								$col = "YEAR('\$$date_compare')-YEAR($date_orig)";
								$col_table = '';
							}
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
		$result->lang_select = $lang_select;
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
				if ($fonctions) {
					$fonctions = explode('|', ereg_replace("^\|", "", $fonctions));
					reset($fonctions);
					while (list(, $f) = each($fonctions)) $champ->fonctions[] = $f;
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
	global $racine;

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
	if ($fonctions) {
		while (list(, $fonc) = each($fonctions)) {
			if ($fonc) {
				$arglist = '';
				if (ereg('([^\{\}]*)\{(.+)\}$', $fonc, $regs)) {
					$fonc = $regs[1];
					if (trim($regs[2]))
						$arglist = ','.$regs[2];
				}
				if (function_exists($fonc))
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
	global $flag_pcre;

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

/*	$milieu = '<blink>#'.$champs[$id_champ]->nom_champ.'</blink>'; // pour debugger les squelettes
	$milieu = "	\$$nom_var = '$milieu';\n";*/

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
	case 'LOGO_AUTEUR_NORMAL':
	case 'LOGO_AUTEUR_SURVOL':
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
			list($logon, $logoff) = IMG_image(image_rubrique($contexte["id_rubrique"]));
			';
		}
		else if ($type_logo == 'RUBRIQUE_NORMAL') {
			$milieu .= '
			list($logon,) = IMG_image(image_rubrique($contexte["id_rubrique"]));
			$logoff = "";
			';
		}
		else if ($type_logo == 'RUBRIQUE_SURVOL') {
			$milieu .= '
			list(,$logon) = IMG_image(image_rubrique($contexte["id_rubrique"]));
			$logoff = "";
			';
		}
		else if ($type_logo == 'DOCUMENT'){
			$milieu .= '
			$logon = integre_image($contexte["id_document"],"","fichier_vignette");
			$logoff = "";
			';
		}
		else if ($type_logo == 'AUTEUR') {
			$milieu .= '
			list($logon, $logoff) = IMG_image(image_auteur($contexte["id_auteur"]));
			';
		}
		else if ($type_logo == 'AUTEUR_NORMAL') {
			$milieu .= '
			list($logon,) = IMG_image(image_auteur($contexte["id_auteur"]));
			$logoff = "";
			';
		}
		else if ($type_logo == 'AUTEUR_SURVOL') {
			$milieu .= '
			list(,$logon) = IMG_image(image_auteur($contexte["id_auteur"]));
			$logoff = "";
			';
		}
		else if ($type_logo == 'BREVE') {
			$milieu .= '
			list($logon, $logoff) = IMG_image(image_breve($contexte["id_breve"]));
			';
		}
		else if ($type_logo == 'BREVE_RUBRIQUE') {
		  $milieu .= '
			list($logon, $logoff) = IMG_image(image_breve($contexte["id_breve"]));
			if (!$logon)
				list($logon, $logoff) = IMG_image(image_rubrique($contexte["id_rubrique"]));
		  ';
		}
		else if ($type_logo == 'SITE') {
			$milieu .= '
			list($logon, $logoff) = IMG_image(image_site($contexte["id_syndic"]));
			';
		}
		else if ($type_logo == 'MOT') {
			$milieu .= '
			list($logon, $logoff) = IMG_image(image_mot($contexte["id_mot"]));
			';
		}
		else if ($type_logo == 'ARTICLE') {
			$milieu .= '
			list($logon, $logoff) = IMG_image(image_article($contexte["id_article"]));
			';
		}
		else if ($type_logo == 'ARTICLE_NORMAL') {
			$milieu .= '
			list($logon,) = IMG_image(image_article($contexte["id_article"]));
			$logoff = "";
			';
		}
		else if ($type_logo == 'ARTICLE_SURVOL') {
			$milieu .= '
			list(,$logon) = IMG_image(image_article($contexte["id_article"]));
			$logoff = "";
			';
		}
		else if ($type_logo == 'ARTICLE_RUBRIQUE') {
			$milieu .= '
			list($logon, $logoff) = IMG_image(image_article($contexte["id_article"]));
			if (!$logon)
				list($logon, $logoff) = IMG_image(image_rubrique($contexte["id_rubrique"]));
			';
		}
		if ($flag_fichier)
			$milieu .= "		\$$nom_var = ereg_replace('^/?IMG/','',\$logon);\n"; // compatibilite ascendante : pas de 'IMG/'
		else
			$milieu .= "		\$$nom_var = affiche_logos(\$logon, \$logoff, \$lien, '".addslashes($align)."');\n";
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
		$code = 'calcul_introduction($pile_boucles[$id_instance]->type_requete,
			$pile_boucles[$id_instance]->row[\'texte\'],
			$pile_boucles[$id_instance]->row[\'chapo\'],
			$pile_boucles[$id_instance]->row[\'descriptif\'])';
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

	case 'LANG':
		$code = "lire_meta('langue_site')";
		break;

	case 'LANG_LEFT':
		$code = "lang_dir(\$GLOBALS['spip_lang'],'left','right')";
		break;

	case 'LANG_RIGHT':
		$code = "lang_dir(\$GLOBALS['spip_lang'],'right','left')";
		break;

	case 'LANG_DIR':
		$code = "lang_dir(\$GLOBALS['spip_lang'],'ltr','rtl')";
		break;

	case 'PUCE':
		$code = "propre('- ')";
		break;

	case 'DATE':
		// Uniquement hors-boucles, pour la date passee dans l'URL ou le contexte inclusion
		$code = "\$contexte['date']";
		break;

	case 'DATE_NOUVEAUTES':
		$milieu = "if (lire_meta('quoi_de_neuf') == 'oui' AND lire_meta('majnouv'))
			\$$nom_var = normaliser_date(lire_meta('majnouv'));
		else
			\$$nom_var = \"'0000-00-00'\";
		";
		break;

	case 'URL_ARTICLE':
		$code = "url_var_recherche(generer_url_article(\$contexte['id_article']), \$contexte['activer_url_recherche'])";
		break;

	case 'URL_RUBRIQUE':
		$code = "url_var_recherche(generer_url_rubrique(\$contexte['id_rubrique']), \$contexte['activer_url_recherche'])";
		break;

	case 'URL_BREVE':
		$code = "url_var_recherche(generer_url_breve(\$contexte['id_breve']), \$contexte['activer_url_recherche'])";
		break;

	case 'URL_FORUM':
		$code = "generer_url_forum(\$contexte['id_forum'])";
		break;

	case 'URL_MOT':
		$code = "url_var_recherche(generer_url_mot(\$contexte['id_mot']), \$contexte['activer_url_recherche'])";
		break;

	case 'URL_DOCUMENT':
		$code = "generer_url_document(\$contexte['id_document'])";
		break;

	case 'NOTES':
		$milieu = '$'.$nom_var.' = $GLOBALS["les_notes"];
			$GLOBALS["les_notes"] = "";
			$GLOBALS["compt_note"] = 0;
			$GLOBALS["marqueur_notes"] ++;
			';
		break;

	case 'RECHERCHE':
		$code = 'htmlspecialchars($GLOBALS["recherche"])';
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

	case 'EXTRA':
		$code = 'trim($pile_boucles[$id_instance]->row[\'extra\'])';
		if ($fonctions) {
			// Gerer la notation [(#EXTRA|isbn)]
			include_ecrire("inc_extra.php3");
			reset($fonctions);
			list($key, $champ_extra) = each($fonctions);
			$type_extra = $boucles[$id_boucle]->type_requete;
			if (extra_champ_valide($type_extra, $champ_extra)) {
				unset($fonctions[$key]);
				$code = "extra($code, '".addslashes($champ_extra)."')";
			}
			// Appliquer les filtres definis par le webmestre
			$filtres = extra_filtres($type_extra, $champ_extra);
			if ($filtres) {
				reset($filtres);
				while (list(, $f) = each($filtres)) $code = "$f($code)";
			}
		}
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
			\$rech = _T('info_rechercher');
			\$$nom_var = \"\n<a name='formulaire_recherche'></a>
				<form action='$lien' method='get' name='form_rech'>
				<input type='text' id='formulaire_recherche' name='recherche' value=\\\"\$rech\\\" size='20' class='formrecherche'\";
			\$$nom_var .= \"></form>\";
		}
		";
		break;

	//
	// Formulaire d'inscription comme redacteur
	// (dans inc-formulaires.php3)
	case 'FORMULAIRE_INSCRIPTION':
		$milieu = '
		$request_uri = $GLOBALS["REQUEST_URI"];
		$spip_lang = $GLOBALS["spip_lang"];
		$accepter_inscriptions = lire_meta("accepter_inscriptions");
		if ($accepter_inscriptions == "oui") {
			$'.$nom_var.' = "<"."?php
				include_local(\"inc-formulaires.php3\");
				lang_select(\"$spip_lang\");
				formulaire_inscription(\"redac\");
				lang_dselect(); ?".">";
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
			$spip_lang = $GLOBALS["spip_lang"];
			$'.$nom_var.' = "<'.'?php
				include (\'inc-formulaires.php3\');
				lang_select(\"$spip_lang\");
				formulaire_ecrire_auteur(".$row[\'id_auteur\'].",\'$email\');
				lang_dselect();
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
		$spip_lang = $GLOBALS["spip_lang"];

		$query_petition = "SELECT * FROM spip_petitions WHERE id_article=$contexte[id_article]";
 		$result_petition = spip_query($query_petition);

		if ($row_petition = spip_fetch_array($result_petition)) {
			$'.$nom_var.' = "<"."?php
				include_local(\"inc-formulaires.php3\");
				lang_select(\"$spip_lang\");
				formulaire_signature($contexte[id_article]);
				lang_dselect();
				?".">";
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
		$spip_lang = $GLOBALS["spip_lang"];

		if ($proposer_sites == "2") {
			$'.$nom_var.' = "<"."?php
				include_local(\"inc-formulaires.php3\");
				lang_select(\"$spip_lang\");
				formulaire_site($contexte[id_rubrique]);
				lang_dselect();
				?".">";
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
		$spip_lang = $GLOBALS["spip_lang"];
		$'.$nom_var.' = "<"."?php include_local(\'inc-forum.php3\'); lang_select(\'$spip_lang\'); ";
		$'.$nom_var.' .= "';
		switch ($boucles[$id_boucle]->type_requete) {
		case "articles":
			$milieu .= 'echo retour_forum(0, 0, $contexte[id_article], 0, 0); ';
			break;

		case "breves":
			$milieu .= 'echo retour_forum(0, 0, 0, $contexte[id_breve], 0); ';
			break;

		case "forums":
			$milieu .= 'echo retour_forum(0, $contexte[id_forum], 0, 0, 0); ';
			break;

		case "rubriques":
			$milieu .= 'echo retour_forum($contexte[id_rubrique], 0, 0, 0, 0); ';
			break;

		case "syndication":
			$milieu .= 'echo retour_forum(0, 0, 0, 0, $contexte[id_syndic]); ';
			break;

		default:
			$milieu .= 'echo retour_forum(\'$contexte[id_rubrique]\', \'$contexte[id_forum]\', \'$contexte[id_article]\', \'$contexte[id_breve]\', \'$contexte[id_syndic]\'); ';
			break;
		}
		$milieu .= '"; $'.$nom_var.' .= "lang_dselect(); ?".">";
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
		if ($flag_ob AND $flag_pcre) {
			$milieu = '
				$'.$nom_var.' = "<"."?php if (\$var_recherche) { \$mode_surligne = debut_surligne(\$var_recherche, \$mode_surligne); } ?".">";
			';
		}
		break;
	case 'FIN_SURLIGNE':
		if ($flag_ob AND $flag_pcre) {
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

	default:
		$milieu = '<blink>#'.$champs[$id_champ]->nom_champ.'</blink>'; // pour debugger les squelettes
		$milieu = "	\$$nom_var = '$milieu';\n";
		break;
	} // switch

	if (!$code) $code = "\$$nom_var";

	$code = applique_filtres ($fonctions, $code);

	if ($code != "\$$nom_var") $milieu .= "\t\$$nom_var = $code;\n";

	return $milieu;
}


//
// Generer le code PHP correspondant a une boucle
//

function calculer_boucle($id_boucle, $prefix_boucle)
{
	global $boucles;
	global $tables_code_contexte, $tables_doublons;

	$func = $prefix_boucle.$id_boucle;
	$boucle = $boucles[$id_boucle];

	//
	// Ecrire le debut de la fonction
	//

	$texte .= "function $func".'($contexte) {
	global $pile_boucles, $ptr_pile_boucles, $id_doublons, $rubriques_publiques;

	';

	//
	// Recherche : recuperer les hash a partir de la chaine de recherche
	//
	if (strpos($boucle->requete, '$hash_recherche')) {
		$texte .= '
		global $recherche, $hash_recherche;
		$contexte[\'activer_url_recherche\'] = true;
		if (!$hash_recherche)
			$hash_recherche = requete_hash($recherche);
		';
	} else
		$texte .= '
		$contexte[\'activer_url_recherche\'] = false;
		';

	if (ereg('\$date_redac[^_]', $boucle->requete)) {
		$texte .= '$contexte[\'date_redac\'] = normaliser_date($contexte[\'date_redac\']);
		';
	}
	if (ereg('\$date[^_]', $boucle->requete)) {
		$texte .= '$contexte[\'date\'] = normaliser_date($contexte[\'date\']);
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
	$instance->type_requete = \''.$boucle->type_requete.'\';
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
	$requete = $boucle->requete;
	$doublons = $boucle->doublons;
	$partie = $boucle->partie;
	$total_parties = $boucle->total_parties;
	$lang_select = ($boucle->lang_select != "non") &&
		($type_boucle == 'articles' OR $type_boucle == 'rubriques'
		OR $type_boucle == 'hierarchie' OR $type_boucle == 'breves');

	//
	// Boucle recursive : simplement appeler la boucle interieure
	//
	if ($type_boucle == 'boucle') {
		$texte .= calculer_liste(array($boucles[$boucle->requete]), $prefix_boucle, $id_boucle);
		$texte .= $code_fin;
		return $texte;
	}

	//
	// Boucle 'hierarchie' : preparation de la requete principale
	//
	else if ($type_boucle == 'hierarchie') {
		$texte .= '
		if ($id_article || $id_syndic) $hierarchie = construire_hierarchie($id_rubrique);
		else $hierarchie = construire_hierarchie($id_parent);
		if ($hierarchie) {
			$hierarchie = explode("-", substr($hierarchie, 0, -1));
			$hierarchie = join(",", $hierarchie);
		}
		else $hierarchie = "0";';

		$deb_class = 0;
		$fin_class = 10000;
		if (ereg("([0-9]+),([0-9]*)", $boucle->requete, $match)) {
			$deb_class = $match[1];
			if ($match[2]) $fin_class = $match[2];
		}
		if ($doublons == "oui")
			$requete = "SELECT *, FIELD(id_rubrique, \$hierarchie) AS _field FROM spip_rubriques WHERE id_rubrique IN (\$hierarchie) AND id_rubrique NOT IN (\$id_doublons[rubriques])";
		else
			$requete = "SELECT *, FIELD(id_rubrique, \$hierarchie) AS _field FROM spip_rubriques WHERE id_rubrique IN (\$hierarchie)";
		$requete .= " ORDER BY _field LIMIT $deb_class,$fin_class";
	}


	//
	// Pour les forums, ajouter le code de gestion du cache
	// et de l'activation / desactivation par article
	//
	if ($type_boucle == 'forums') {
		$texte .= '
		global $fichier_cache, $requetes_cache;
		if (!$id_rubrique AND !$id_article AND !$id_breve AND $id_forum)
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

	$texte .= '	$query = "'.$requete.'";
	$result = @spip_query($query);
	if (!$result) {
		$GLOBALS["delais"]=0;
		include_local("inc-debug-squel.php3");
		return erreur_requete_boucle($query, $instance->id_boucle);
	}
	$total_boucle = @spip_num_rows($result);';

	if ($partie AND $total_parties) {
		$flag_parties = true;
		$texte .= '
		$debut_boucle = floor(($total_boucle * ($instance->partie - 1) + $instance->total_parties - 1) / $instance->total_parties) + 1;
		$fin_boucle = floor(($total_boucle * ($instance->partie) + $instance->total_parties - 1) / $instance->total_parties);
		$pile_boucles[$id_instance]->total_boucle = $fin_boucle - $debut_boucle + 1;';
	}
	else {
		$flag_parties = false;
		$texte .= '
		$pile_boucles[$id_instance]->total_boucle = $total_boucle;';
	}

	$texte_debut .= '
	$pile_boucles[$id_instance]->compteur_boucle = 0;
	$compteur_boucle = 0;';

	//
	// Ecrire le code de recuperation des resultats
	//

	if ($lang_select)
		$texte_debut .= "\n\t\$old_lang = \$GLOBALS['spip_lang'];\n";
	$texte_debut .= '
	while ($row = @spip_fetch_array($result)) {';

	if ($flag_parties) {
		$texte_debut .= '
		$compteur_boucle++;
		if ($compteur_boucle >= $debut_boucle AND $compteur_boucle <= $fin_boucle) {';
	}
	$texte_debut .= '
		$pile_boucles[$id_instance]->compteur_boucle++;
		$pile_boucles[$id_instance]->row = $row;';
	if ($boucle->separateur)
		$texte_debut .= '
		if ($retour) $retour .= \''.$boucle->separateur."';";
	if ($lang_select)
		$texte_debut .= '
		if ($row["lang"])
			$GLOBALS["spip_lang"] = $row["lang"];';

	// Traitement different selon le type de boucle
	$texte_debut .= $tables_code_contexte[$type_boucle];
	if ($doublons == "oui")
		$texte_debut .= "\n\t\t\$id_doublons['$type_boucle'] .= ','.\$row['".$tables_doublons[$type_boucle]."'];";

	// Inclusion du code correspondant a l'interieur de la boucle
	$texte_liste = calculer_liste($boucle->milieu, $prefix_boucle, $id_boucle);

	// On n'ecrit la boucle "while" que si elle contient du code utile,
	// sinon on utlise plutot spip_num_rows() pour recuperer le nombre d'iterations
	if ($texte_liste OR $doublons == 'oui') {
		$texte .= $texte_debut . $texte_liste;

		if ($flag_parties) {
			$texte .= "\n\t\t}\n";
		}

		// Fermeture de la boucle spip_fetch_array et liberation des resultats
		$texte .= "\n\t}\n\t@spip_free_result(\$result);\n";
		if ($lang_select)
			$texte .= '	$GLOBALS["spip_lang"] = $old_lang;'."\n";
	}
	else {
		$texte .= '	$pile_boucles[$id_instance]->compteur_boucle = $pile_boucles[$id_instance]->$total_boucle;'."\n";
	}
	$texte .= $code_fin;
	return $texte;
}


//
// Generer le code PHP correspondant a un texte brut
//

function calculer_texte($texte)
{
	global $dossier_squelettes;
	$dossier = ($dossier_squelettes ? $dossier_squelettes.'/' : '');
	$code = "";

	//
	// Reperer les directives d'inclusion de squelette et les balises de traduction <:toto:>
	//
	while (ereg("(<INCLU[DR]E[[:space:]]*\(([-_0-9a-zA-Z./ ]+)\)(([[:space:]]*\{[^}]*\})*)[[:space:]]*>)", $texte, $match)) {
		$s = $match[0];
		$p = strpos($texte, $s);
		$debut = substr($texte, 0, $p);
		$texte = substr($texte, $p + strlen($s));
		if ($debut)
			$code .= "	\$retour .= '".ereg_replace("([\\\\'])", "\\\\1", $debut)."';\n";

		//
		// Traiter la directive d'inclusion
		//
		$fichier = $match[2];
		ereg('^\\{(.*)\\}$', trim($match[3]), $params);
		$code .= "	\$retour .= '<"."?php ';\n";
		$code .= "	\$retour .= 'include_ecrire(\'inc_lang.php3\'); lang_select(lire_meta(\'langue_site\'));';\n";
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

		// inclure en priorite dans le dossier_squelettes
		if ($dossier_squelettes) {
			$code .= "	\$retour .= '
			if (@file_exists(\'$dossier_squelettes/$fichier\')){
				include(\'$dossier_squelettes/$fichier\');
			} else {
				include(\'$fichier\');
			}';\n";
		} else
			$code .= "	\$retour .= 'include(\'$fichier\');';\n";

		$code .= "	\$retour .= 'lang_dselect(); ?".">';\n";
	}
	if ($texte)
		$code .= "	\$retour .= '".ereg_replace("([\\\\'])", "\\\\1", $texte)."';\n";

	//
	// Reperer les balises de traduction <:toto:>
	//
	while (eregi("(<:(([a-z0-9_]+):)?([a-z0-9_]+)(\|[^>]*)?:>)", $code, $match)) {
		//
		// Traiter la balise de traduction multilingue
		//
		$chaine = strtolower($match[4]);
		if (!($module = $match[3]))
			$module = 'local/public/spip';	// ordre des modules a explorer
		$remplace = "_T('$module:$chaine')";
		if ($filtres = $match[5]) {
			$filtres = explode('|',substr($filtres,1));
			$remplace = applique_filtres($filtres, $remplace);
		}
		$code = str_replace($match[1], "'.$remplace.'", $code);
	}

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
			if ($objet->cond_avant || $objet->cond_apres || $objet->cond_altern) {
				$texte .= "	\$$nom_var = $nom_func(\$contexte);\n";
				$texte .= "	if (\$$nom_var) {\n";
				if ($s = $objet->cond_avant) {
					$texte .= calculer_liste($s, $prefix_boucle, $id_boucle);
				}
				$texte .= "	\$retour .= \$$nom_var;\n";
				if ($s = $objet->cond_apres) {
					$texte2 = calculer_liste($s, $prefix_boucle, $id_boucle);
					if (strpos($texte2, '$id_instance_cond')) {
						$texte .= "	\$id_instance_cond++;\n";
						$texte .= $texte2;
						$texte .= "	\$id_instance_cond--;\n";
					}
					else $texte .= $texte2;
				}
				$texte .= "	}\n";
				if ($s = $objet->cond_altern) {
					$texte .= "	else {\n";
					$texte2 = calculer_liste($s, $prefix_boucle, $id_boucle);
					if (strpos($texte2, '$id_instance_cond')) {
						$texte .= "	\$id_instance_cond++;\n";
						$texte .= $texte2;
						$texte .= "	\$id_instance_cond--;\n";
					}
					else $texte .= $texte2;
					$texte .= "	}\n";
				}
			}
			else {
				$texte .= "	\$retour .= $nom_func(\$contexte);\n";
			}
			$nb_milieu--;

			break;


		/////////////////////
		// Champ
		//
		case 'champ':
			$nb_milieu++;
			if ($objet->cond_avant || $objet->cond_apres) {
				$nom_var = "milieu$nb_milieu";
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
			}
			else {
				$nom_var = "milieu$nb_milieu";
				$texte2 = calculer_champ($objet->id_champ, $id_boucle, $nom_var);
				$c = count(explode("\$$nom_var", $texte2));
				if ($c <= 2) {
					$texte2 = str_replace("\$$nom_var = ", "\$retour .= ", $texte2);
					$texte .= $texte2;
				}
				else {
					$texte .= $texte2;
					$texte .= "	\$retour .= \$$nom_var;\n";
				}
			}
			$nb_milieu--;
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
