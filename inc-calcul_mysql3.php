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

function spip_abstract_select($s, $f, $w, $g, $o, $l, $sous, $cpt, $table, $id) {
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
# if (!($result = @mysql_query($q)))
  if (!($result = @spip_query($q)))
    {
      include_local("inc-debug-squel.php3");
      echo erreur_requete_boucle($q, $id, $table);
      exit;
    }
#  spip_log("$id: $q\n" . spip_num_rows($result));
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

# Index arbitraire mais ressemblant aux md5 utilisés ailleurs
function calcul_index_forum($id_article, $id_breve, $id_rubrique, $id_syndic)
{
  return 
    'a' . ($id_article ? $id_article : '0') .
    'b' . ($id_breve ? $id_breve : '0') .
    'c' . ($id_rubrique ? $id_rubrique : '0') . 
    'd' . ($id_syndic ? $id_syndic : '0');
}

function calcul_exposer ($pile, $reference) {
	static $hierarchie;
	static $ref_precedente;

	if ($reference<>$ref_precedente) {
		$ref_precedente = $reference;
		if ($id = $reference['id_article'])
			$base = 'articles';
		else if ($id = $reference['id_breve'])
			$base = 'breves';
		else if ($id = $reference['id_syndic'])
			$base = 'syndic';
		else if ($id = $reference['id_rubrique'])
			$base = 'rubriques';
		else if ($id = $reference['id_secteur'])
			$base = 'rubriques';

		unset ($hierarchie);
		if ($base) {
			if ($base != 'rubriques') {
				$hierarchie[$base][$id] = true;
				$id_element = 'id_'.ereg_replace('s$', '', $base);
				$s = spip_fetch_array(spip_query(
				"SELECT id_rubrique FROM spip_$base WHERE $id_element=$id"));
				$id = $s['id_rubrique'];
			}

			$hierarchie['rubriques'][$id] = true;

			while (true) {
				$s = spip_fetch_array(spip_query(
				"SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$id"));
				if ($id = $s['id_parent'])
					$hierarchie['rubriques'][$id] = true;
				else
					break;
			}
		}
	}

	if ($id = $pile['id_article'])
		return $hierarchie['articles'][$id];
	else if ($id = $pile['id_breve'])
		return $hierarchie['breves'][$id];
	else if ($id = $pile['id_syndic'])
		return $hierarchie['syndic'][$id];
	else if ($id = $pile['id_rubrique'])
		return $hierarchie['rubriques'][$id];
	else if ($id = $pile['id_secteur'])
		return $hierarchie['rubriques'][$id];

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

function query_parent($id_rubrique)
{
  $row = spip_fetch_array(spip_query("
SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_rubrique'
"));
  return $row['id_parent'];
}

function query_auteurs($larticle)
{
  $auteurs = "";
  if ($larticle)
    {
      $result_auteurs = spip_query("
SELECT	auteurs.nom, auteurs.email 
FROM	spip_auteurs AS auteurs,
	spip_auteurs_articles AS lien
WHERE	lien.id_article=$larticle
 AND	auteurs.id_auteur=lien.id_auteur
");

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
    }
  return (!$auteurs) ? "" : join($auteurs, ", ");
}

function query_petitions($id_article)
{
  return spip_fetch_array(spip_query("
SELECT	id_article
FROM	spip_petitions
WHERE	id_article='$id_article'"));
    }

# retourne le chapeau d'un article, et seulement s'il est publie

function query_chapo($id_article)
{
 return spip_fetch_array(spip_query("
SELECT	chapo
FROM	spip_articles
WHERE	id_article='$id_article' AND statut='publie'
"));
}

// Calcul de la rubrique associee a la requete
// (selection de squelette specifique)

function cherche_rubrique_fond($contexte, $lang)
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
