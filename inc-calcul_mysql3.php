<?php
    
# Ce fichier concentre tous les appels SQL lors de l'execution d'un squelette.

# Cette fonction est syste'matiquement appelee par les squelettes
# pour constuire une requete SQL a` partir de la boucle SPIP originale.
# Elle construit et exe'cute une reque^te SQL correspondant a` une balise Boucle
# Elle notifie une erreur SQL dans le flux de sortie et termine le processus.
# Sinon, retourne la ressource interrogeable par fetch_row ou fetch_array.
# Elle peut etre re'de'finie pour s'interfacer avec d'autres serveurs SQL
# Recoit en argument:
# - le tableau des champs a` ramener
# - le tableau des tables a` consulter
# - le tableau des conditions a` remplir
# - le crite`re de regroupement
# - le crite`re de classement
# - le crite`re de limite
# - une sous-requete e'ventuelle (MySQL > 4.1)
# - un compteur de sous-requete
# - le nom de la table
# - le nom de la boucle (pour le message d'erreur e'ventuel)

# En commentaire, le court-circuit de spip_query,
# avec traitement de table_prefix sans restriction sur son nom

function spip_abstract_select($s, $f, $w='', $g='', $o='', $l='', $sous='', $cpt='', $table='', $id='') {
# if ($GLOBALS["mysql_rappel_connexion"] AND $DB = $GLOBALS["spip_mysql_db"])
#        $DB = "`$DB`";
# $DB .= $GLOBALS["table_prefix"] . '_';
  $DB = 'spip_';
 $q = "\nFROM\t$DB".
    join(",\n\t$DB", $f) .
    ((!$w) ? "" : ("\nWHERE\t".join("\n AND\t", $w))) .
    ($g ? "\nGROUP BY $g" : '') .
    ($o ? "\nORDER BY $o" : '') .
    ($l ? "\nLIMIT $l" : '');
  $q = (!$sous ? 
	("\nSELECT\t". join(",\n\t", $s) . $q) :
	("\nSELECT\tS_" . join(",\n\tS_", $s) .
	 "\nFROM\t(" . join(",\n\t", $s) . " ,\n\tCOUNT(" . $sous .
	 ") AS compteur $q)\n AS S_$table\nWHERE compteur= " . 
	 $cpt));
# spip_log("$id: $q\n");
# if (!($result = @mysql_query($q)))
  if (!($result = @spip_query($q)))
    {
      include_local("inc-debug-squel.php3");
      echo erreur_requete_boucle($q, $id, $table);
      exit;
    }
#  spip_log(spip_num_rows($result));
  return $result;
}

# toutes les fonctions avec requete SQL, necessaires aux squelettes.

function boutons_de_forum_table($idr, $idf, $ida, $idb, $ids, $titre, $table, $forum)
{
  if (($table == 'forums') || !$table)
    {
      $forum = spip_fetch_array(spip_query("
SELECT	accepter_forum
FROM	spip_articles
WHERE	id_article='" . ($ida ? $ida : substr(lire_meta("forums_publics"),0,3)) . "'
")); 
      $forum = ($forum ? $forum['accepter_forum'] : substr(lire_meta("forums_publics"),0,3));
    }
  if ($forum=="non") return '';
  // si FORMULAIRE_FORUM a e'te' employe' hors d'une boucle,
  // on n'a pas pu de'terminer titre et table a` la compil
  if (!$table)
    {
      if ($idf)
	{
	  $r = "SELECT titre FROM spip_forum WHERE id_forum = $idf";
	  $table = "forum";
	}
      else if ($idr)
	{
	  $r = "SELECT titre FROM spip_rubriques WHERE id_rubrique = $idr";
	  $table = "rubriques";
	}
      else if ($ida)
	{
	  $r = "SELECT titre FROM spip_articles WHERE id_article = $ida";
	  $table = "articles";
	}
      else if ($idb)
	{
	  $r = "SELECT titre FROM spip_breves WHERE id_breve = $idb";
	  $table = "breves";
	}
      else if ($ids)
	{
	  $table = "syndic";
	  $r = "SELECT nom_site AS titre FROM spip_syndic WHERE id_syndic = $ids";
	}
      else
	{
	  $r = "SELECT '".addslashes(_T('forum_titre_erreur'))."' AS titre";
	}
      $r = spip_fetch_array(spip_query($r));
      $titre = $r['titre'];
    }
  return array($titre, $table, $forum);
}

function calcul_exposer ($id, $type, $reference) {
	static $exposer;
	static $ref_precedente;

	// Que faut-il exposer ? Tous les elements de $reference
	// ainsi que leur hierarchie ; on ne fait donc ce calcul
	// qu'une fois (par squelette) et on conserve le resultat
	// en static.
	if ($reference<>$ref_precedente) {
		$ref_precedente = $reference;

		$exposer = array();
		foreach ($reference as $element=>$id_element) {
			if ($element == 'id_secteur') $element = 'id_rubrique';
			if (ereg("id_(article|breve|rubrique|syndic)", $element, $regs)) {
				$exposer[$element][$id_element] = true;
				$table = "spip_".table_objet($regs[1]);
				list ($id_rubrique) = spip_fetch_array(spip_query(
				"SELECT id_rubrique FROM $table WHERE $element=$id_element"));
				$hierarchie = substr(calculer_hierarchie($id_rubrique), 2);
				foreach (split(',',$hierarchie) as $id_rubrique)
					$exposer['id_rubrique'][$id_rubrique] = true;
			}
		}
	}

	// And the winner is...
	return $exposer[$type][$id];
}

function calcul_generation ($generation) {
	$lesfils = array();
	$result = spip_abstract_select(array('id_rubrique'),
				       array('rubriques AS rubriques'),
				       array(calcul_mysql_in('id_parent', 
							     $generation,
							     '')),
				       '','','','','','','');
	while ($row = spip_fetch_array($result))
	  $lesfils[] = $row['id_rubrique'];
	return join(",",$lesfils);
}

function calcul_branche ($generation) {
	if (!$generation) 
	  return '0';
	else {
		$branche[] = $generation;
		while ($generation = calcul_generation ($generation))
			$branche[] = $generation;
		return join(",",$branche);
	}
}


# retourne la profondeur d'une rubrique

function sql_profondeur($id)
{
	$n = 0;
	while ($id) {
		$n++;
		$id = sql_parent($id);
	}
	return $n;
}


function sql_parent($id_rubrique)
{
  $row = spip_fetch_array(spip_query("
SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_rubrique'
"));
  return $row['id_parent'];
}

function sql_rubrique($id_article)
{
  $row = spip_fetch_array(spip_query("
SELECT id_rubrique FROM spip_articles WHERE id_article='$id_article'
"));
  return $row['id_rubrique'];
}

function sql_auteurs($id_article)
{
  $auteurs = "";
  if ($id_article)
    {
      $result_auteurs = spip_query("
SELECT	auteurs.nom, auteurs.email 
FROM	spip_auteurs AS auteurs,
	spip_auteurs_articles AS lien
WHERE	lien.id_article=$id_article
 AND	auteurs.id_auteur=lien.id_auteur
");

      while($row_auteur = spip_fetch_array($result_auteurs)) {
	$nom_auteur = typo($row_auteur["nom"]);
	$email_auteur = $row_auteur["email"];
	if ($email_auteur) {
	  $auteurs[] = "<a href=\"mailto:$email_auteur\">$nom_auteur</a>";
	}
	else {
	  $auteurs[] = "$nom_auteur";
	}
      }
    }
  return (!$auteurs) ? "" : join($auteurs, ", ");
}

function sql_petitions($id_article) {
	$q = spip_query("SELECT
	id_article, email_unique, site_obli, site_unique, message, texte
	FROM spip_petitions
	WHERE id_article=".intval($id_article));
	return spip_fetch_array($q);
}

# retourne le chapeau d'un article, et seulement s'il est publie

function sql_chapo($id_article)
{
 return spip_fetch_array(spip_query("
SELECT	chapo
FROM	spip_articles
WHERE	id_article='$id_article' AND statut='publie'
"));
}

// Calcul de la rubrique associee a la requete
// (selection de squelette specifique)

function sql_rubrique_fond($contexte, $lang)
{
  if ($id = $contexte['id_rubrique']) {
    if ($row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique='$id'")))
      if ($row['lang']) $lang = $row['lang'];
    return array($id, $lang);
  }
  if ($id  = $contexte['id_breve']) {
    if ($row = spip_fetch_array(spip_query("
SELECT id_rubrique FROM spip_breves WHERE id_breve='$id'"))) {
      $id_rubrique_fond = $row['id_rubrique'];
      if ($row = spip_fetch_array(spip_query("
SELECT lang FROM spip_rubriques WHERE id_rubrique='$id_rubrique_fond'")))
	if ($row['lang']) $lang = $row['lang'];
    }
    return array($id_rubrique_fond, $lang);
  }
  if ($id = $contexte['id_syndic']) {
    if ($row = spip_fetch_array(spip_query("
SELECT id_rubrique FROM spip_syndic WHERE id_syndic='$id'"))) {
      $id_rubrique_fond = $row['id_rubrique'];
      if ($row = spip_fetch_array(spip_query("
SELECT lang FROM spip_rubriques WHERE id_rubrique='$id_rubrique_fond'")))
	if ($row['lang']) $lang = $row['lang'];
    }
    return array($id_rubrique_fond, $lang);
  }
  if ($id = $contexte['id_article']) {
    if ($row = spip_fetch_array(spip_query("
SELECT id_rubrique,lang FROM spip_articles WHERE id_article='$id'"))) {
      $id_rubrique_fond = $row['id_rubrique'];
      if ($row['lang']) $lang = $row['lang'];
    }
    return array($id_rubrique_fond, $lang);
  }
  return '';
}


?>
