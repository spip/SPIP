<?

if (!file_exists("inc_connect.php3")) {
	@header("Location: install.php3");
	exit;
}

include ("inc_version.php3");

include_local ("inc_presentation.php3");
include_local ("inc_connect.php3");
include_local ("inc_meta.php3");
include_local ("inc_auth.php3");
include_local ("inc_texte.php3");
include_local ("inc_urls.php3");
include_local ("inc_mail.php3");
include_local ("inc_admin.php3");
include_local ("inc_layer.php3");
include_local ("inc_sites.php3");
include_local ("inc_index.php3");

if (!file_exists("inc_meta_cache.php3")) ecrire_metas();


//
// Cookies de presentation
//

$options = $HTTP_COOKIE_VARS['spip_options'];
$graphisme = $HTTP_COOKIE_VARS['spip_graphisme'];

if (!$graphisme) $graphisme="0";

$fond = substr($graphisme,0,1);


if ($set_fond) {
	$fond = floor($set_fond);
	setcookie('spip_graphisme', $fond, time()+(3600*24*365));
}

if ($set_survol) {
	setcookie('spip_survol', $set_survol, time()+(3600*24*365));
	$spip_survol=$set_survol;
}

if ($set_couleur) {
	$couleur= floor($set_couleur);
	setcookie('spip_couleur', $couleur, time()+(3600*24*365));
	$spip_couleur=$couleur;
}

if ($set_options == 'avancees') {
	setcookie('spip_options', 'avancees', time()+(3600*24*365));
	$options = 'avancees';
}
if ($set_options == 'basiques') {
	setcookie('spip_options', 'basiques', time()+(3600*24*365));
	$options = 'basiques';
}


//
// Gestion de la configuration globale du site
//

if ($envoi_now) {
	effacer_meta('majnouv');
}

if (!$adresse_site) {
	$nom_site_spip = lire_meta("nom_site");
	$adresse_site = lire_meta("adresse_site");
	$activer_breves = lire_meta("activer_breves");
	$activer_statistiques = lire_meta("activer_statistiques");
	$articles_mots = lire_meta("articles_mots");
}

if (!$nom_site_spip) {
	$nom_site_spip = "Mon site SPIP";
	ecrire_meta("nom_site", $nom_site_spip);
	ecrire_metas();
}

if (!$adresse_site) {
	$adresse_site = "http://$HTTP_HOST".substr($REQUEST_URI, 0, strpos($REQUEST_URI, "/ecrire"));
	ecrire_meta("adresse_site", $adresse_site);
	ecrire_metas();
}


function tester_rubrique_vide($id_rubrique) {
	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent='$id_rubrique' LIMIT 0,1";
	list($n) = mysql_fetch_row(mysql_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_article FROM spip_articles WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prepa' OR statut='prop') LIMIT 0,1";
	list($n) = mysql_fetch_row(mysql_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_breve FROM spip_breves WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prop') LIMIT 0,1";
	list($n) = mysql_fetch_row(mysql_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_syndic FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prop') LIMIT 0,1";
	list($n) = mysql_fetch_row(mysql_query($query));
	if ($n > 0) return false;

	return true;
}


//
// Recuperation du cookie
//

$cookie_admin = $HTTP_COOKIE_VARS["spip_admin"];


//
// Gestion de version
//

$version_installee = (double) lire_meta("version_installee");
if ($version_installee < $spip_version) {
	debut_page();
	if (!$version_installee) $version_installee = "ant&eacute;rieure";
	echo "<h4>Message technique : la proc&eacute;dure de mise &agrave; jour doit &ecirc;tre lanc&eacute;e afin d'adapter
	la base de donn&eacute;es &agrave; la nouvelle version de SPIP.</h4>
	Si vous &ecirc;tes administrateur du site, veuillez <a href='upgrade.php3'>cliquer sur ce lien</a>.<p>";
	fin_page();
	exit;
}


//
// Ajouter un message de forum
//

if ($ajout_forum AND strlen($texte)>10 AND strlen($titre)>2) {
	$titre = addslashes($titre);
	$texte = addslashes($texte);
	$nom_site = addslashes($nom_site);
	$auteur = addslashes($auteur);
	$query_forum = "INSERT INTO spip_forum (id_parent, id_rubrique, id_article, id_breve, id_message, id_syndic, date_heure, titre, texte, nom_site, url_site, auteur, email_auteur, statut, id_auteur) VALUES ('$forum_id_parent','$forum_id_rubrique','$forum_id_article','$forum_id_breve','$forum_id_message', '$forum_id_syndic', NOW(),\"$titre\",\"$texte\",\"$nom_site\",\"$url_site\",\"$auteur\",\"$email_auteur\",\"$forum_statut\",\"$connect_id_auteur\")";
	$result_forum = mysql_query($query_forum);
		
}


//
// Supprimer forum
//

if ($supp_forum AND $connect_statut == "0minirezo") {

	$query_forum = "SELECT * FROM spip_forum WHERE id_forum=\"$supp_forum\"";
 	$result_forum = mysql_query($query_forum);

 	while($row=mysql_fetch_array($result_forum)){
		$id_forum=$row[0];
		$forum_id_parent=$row[1];
		$forum_id_rubrique=$row[2];
		$forum_id_article=$row[3];
		$forum_id_breve=$row[4];
		$forum_id_syndic=$row["id_syndic"];
		$forum_date_heure=$row[5];
		$forum_titre=$row[6];
		$forum_texte=$row[7];
		$forum_auteur=$row[8];
		$forum_email_auteur=$row[9];
		$forum_nom_site=$row[10];
		$forum_url_site=$row[11];
		$forum_stat=$row[12];
		$forum_ip=$row[13];
	}
	$query_forum = "UPDATE spip_forum SET id_parent='$forum_id_parent', id_rubrique='$forum_id_rubrique', id_article='$forum_id_article', id_breve='$forum_id_breve', id_syndic='$forum_id_syndic' WHERE id_parent=$supp_forum AND statut!='off'";
	$result_forum = mysql_query($query_forum);

	unset($where);
	if ($forum_id_article) $where[] = "id_article=$forum_id_article";
	if ($forum_id_rubrique) $where[] = "id_rubrique=$forum_id_rubrique";
	if ($forum_id_breve) $where[] = "id_breve=$forum_id_breve";
	if ($forum_id_syndic) $where[] = "id_syndic=$forum_id_syndic";
	if ($forum_id_parent) $where[] = "id_forum=$forum_id_parent";
	if ($where) {
		$query = "SELECT fichier FROM spip_forum_cache WHERE ".join(' OR ', $where);
		$result = mysql_query($query);
		unset($fichiers);
		if ($result) while ($row = mysql_fetch_array($result)) {
			$fichier = $row[0];
			@unlink("../CACHE/$fichier");
			$fichiers[] = $fichier;
		}
		if ($fichiers) {
			$fichiers = join(',', $fichiers);
			$query = "DELETE FROM spip_forum_cache WHERE fichier IN ($fichiers)";
			mysql_query($query);
		}
	}
	$query_forum = "UPDATE spip_forum SET statut='off' WHERE id_forum=$supp_forum";
	$result_forum = mysql_query($query_forum);
}


//
// Valider un forum
//

if ($valid_forum AND $connect_statut == "0minirezo") {

	$query_forum = "SELECT * FROM spip_forum WHERE id_forum=\"$valid_forum\"";
 	$result_forum = mysql_query($query_forum);

 	while($row=mysql_fetch_array($result_forum)){
		$id_forum=$row[0];
		$forum_id_parent=$row[1];
		$forum_id_rubrique=$row[2];
		$forum_id_article=$row[3];
		$forum_id_breve=$row[4];
		$forum_id_syndic=$row["id_syndic"];
		$forum_date_heure=$row[5];
		$forum_titre=$row[6];
		$forum_texte=$row[7];
		$forum_auteur=$row[8];
		$forum_email_auteur=$row[9];
		$forum_nom_site=$row[10];
		$forum_url_site=$row[11];
		$forum_stat=$row[12];
		$forum_ip=$row[13];
	}

	unset($where);
	if ($forum_id_article) $where[] = "id_article=$forum_id_article";
	if ($forum_id_rubrique) $where[] = "id_rubrique=$forum_id_rubrique";
	if ($forum_id_breve) $where[] = "id_breve=$forum_id_breve";
	if ($forum_id_syndic) $where[] = "id_syndic=$forum_id_syndic";
	if ($forum_id_parent) $where[] = "id_forum=$forum_id_parent";
	if ($where) {
		$query = "SELECT fichier FROM spip_forum_cache WHERE ".join(' OR ', $where);
		$result = mysql_query($query);
		unset($fichiers);
		if ($result) while ($row = mysql_fetch_array($result)) {
			$fichier = $row[0];
			@unlink("../CACHE/$fichier");
			$fichiers[] = $fichier;
		}
		if ($fichiers) {
			$fichiers = join(',', $fichiers);
			$query = "DELETE FROM spip_forum_cache WHERE fichier IN ($fichiers)";
			mysql_query($query);
		}
	}
	$query_forum = "UPDATE spip_forum SET statut='publie' WHERE id_forum=$valid_forum";
	$result_forum = mysql_query($query_forum);
}


//
// Recalculer les secteurs de chaque article, rubrique, syndication
//

function calculer_secteurs() {
	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent=0";
	$result = mysql_query($query);

	while ($row = mysql_fetch_array($result)) $secteurs[] = $row[0];
	if (!$secteurs) return;

	while (list(, $id_secteur) = each($secteurs)) {
		$rubriques = "$id_secteur";
		$rubriques_totales = $rubriques;
		while ($rubriques) {
			$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent IN ($rubriques)";
			$result = mysql_query($query);

			unset($rubriques);
			while ($row = mysql_fetch_array($result)) $rubriques[] = $row[0];
			if ($rubriques) {
				$rubriques = join(',', $rubriques);
				$rubriques_totales .= ",".$rubriques;
			}
		}
		$query = "UPDATE spip_articles SET id_secteur=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = mysql_query($query);
		$query = "UPDATE spip_breves SET id_rubrique=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = mysql_query($query);
		$query = "UPDATE spip_rubriques SET id_secteur=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = mysql_query($query);
		$query = "UPDATE spip_syndic SET id_secteur=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = mysql_query($query);
	}
}


function calculer_dates_rubriques($id_parent="0", $date_parent="0000-00-00"){

	
	$query = "SELECT MAX(date_heure) FROM spip_breves WHERE id_rubrique = '$id_parent' GROUP BY id_rubrique";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$date_breves = $row[0];
		if ($date_breves > $date_parent) $date_parent = $date_breves;
	}
	
	$query = "SELECT MAX(date) FROM spip_syndic WHERE id_rubrique = '$id_parent' GROUP BY id_rubrique";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$date_syndic = $row[0];
		if ($date_syndic > $date_parent) $date_parent = $date_syndic;
	}
	
	
	
	if ($post_dates != "non") {
		$query = "SELECT rubrique.id_rubrique,  MAX(articles.date) FROM spip_rubriques AS rubrique, spip_articles AS articles WHERE rubrique.id_parent='$id_parent' AND articles.id_rubrique=rubrique.id_rubrique AND articles.statut = 'publie' GROUP BY rubrique.id_rubrique";
	}
	else {
		$query = "SELECT rubrique.id_rubrique,  MAX(articles.date) FROM spip_rubriques AS rubrique, spip_articles AS articles WHERE rubrique.id_parent='$id_parent' AND articles.id_rubrique=rubrique.id_rubrique AND articles.statut = 'publie' AND articles.date < NOW() GROUP BY rubrique.id_rubrique";
	}
	$result = mysql_query($query);
	
	while ($row = mysql_fetch_array($result)) {
		$id_rubrique = $row[0];
		$date_rubrique = $row[1];
		
		$date_rubrique = calculer_dates_rubriques($id_rubrique,$date_rubrique);
		
		if ($date_rubrique > $date_parent) $date_parent = $date_rubrique;
	}


	mysql_query("UPDATE spip_rubriques SET date='$date_parent' WHERE id_rubrique='$id_parent'");

	return $date_parent;


}

//calculer_dates_rubriques();

function calculer_rubriques_publiques()
{
	$post_dates = lire_meta("post_dates");

	if ($post_dates != "non") {
		$query = "SELECT DISTINCT id_rubrique FROM spip_articles WHERE statut = 'publie'";
	}
	else {
		$query = "SELECT DISTINCT id_rubrique FROM spip_articles WHERE statut = 'publie' AND date < NOW()";
	}
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		if ($row[0]) $rubriques[] = $row[0];
	}
	$query = "SELECT DISTINCT id_rubrique FROM spip_breves WHERE statut = 'publie'";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		if ($row[0]) $rubriques[] = $row[0];
	}
	$query = "SELECT DISTINCT id_rubrique FROM spip_syndic WHERE statut = 'publie'";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		if ($row[0]) $rubriques[] = $row[0];
	}

	while ($rubriques) {
		$rubriques = join(",", $rubriques);
		if ($rubriques_publiques) $rubriques_publiques .= ",$rubriques";
		else $rubriques_publiques = $rubriques;
		$query = "SELECT DISTINCT id_parent FROM spip_rubriques WHERE (id_rubrique IN ($rubriques)) AND (id_parent NOT IN ($rubriques_publiques))";
		$result = mysql_query($query);
		unset($rubriques);
		while ($row = mysql_fetch_array($result)) {
			if ($row[0]) $rubriques[] = $row[0];
		}
	}
	$query = "UPDATE spip_rubriques SET statut='prive' WHERE id_rubrique NOT IN ($rubriques_publiques)";
	mysql_query($query);
	$query = "UPDATE spip_rubriques SET statut='publie' WHERE id_rubrique IN ($rubriques_publiques)";
	mysql_query($query);

	calculer_dates_rubriques();

}


//
// Recalculer l'ensemble des donnees associees a l'arborescence des rubriques
// (cette fonction est a appeler a chaque modification sur les rubriques)
//

function calculer_rubriques()
{
	calculer_secteurs();
	calculer_rubriques_publiques();
}

// Supprimer rubrique
if ($supp_rubrique = intval($supp_rubrique) AND $connect_statut == '0minirezo' AND acces_rubrique($supp_rubrique)) {
	$query = "DELETE FROM spip_rubriques WHERE id_rubrique=$supp_rubrique";
	$result = mysql_query($query);

	calculer_rubriques();
}

?>
