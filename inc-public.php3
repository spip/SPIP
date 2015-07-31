<?php

$dir_ecrire = 'ecrire/';
include ("ecrire/inc_version.php3");
include_local ("inc-cache.php3");

//
// Ajouter un forum
//

if ($ajout_forum) {
	include_local ("inc-forum.php3");
	ajout_forum();
}


//
// Calcul du nom du fichier cache
//

$fichier_requete = $REQUEST_URI;
$fichier_requete = strtr($fichier_requete, '?', '&');
$fichier_requete = eregi_replace('&(submit|valider|(var_[^=&]*)|recalcul)=[^&]*', '', $fichier_requete);

$md_cache = md5($fichier_requete);

$fichier_cache = ereg_replace('^/+', '', $fichier_requete);
$fichier_cache = ereg_replace('\.[a-zA-Z0-9]*', '', $fichier_cache);
$fichier_cache = ereg_replace('&[^&]+=([^&]+)', '&\1', $fichier_cache);
$fichier_cache = rawurlencode(strtr($fichier_cache, '/&-', '--_'));
if (strlen($fichier_cache) > 24)
	$fichier_cache = substr(ereg_replace('([a-zA-Z]{1,3})[^-]*-', '\1-', $fichier_cache), -24);

if (!$fichier_cache)
	$fichier_cache = 'sommaire-';
$fichier_cache .= '.'.substr($md_cache, 1, 6);

$subdir_cache = substr($md_cache, 0, 1);

if (creer_repertoire("CACHE", $subdir_cache))
	$fichier_cache = "$subdir_cache/$fichier_cache";

$chemin_cache = "CACHE/$fichier_cache";


//
// Doit-on recalculer le cache ?
//

$use_cache = true;

if (file_exists($chemin_cache)) {
	$lastmodified = filemtime($chemin_cache);
	$ledelais = time() - $lastmodified;
	$use_cache &= ($ledelais < $delais AND $ledelais > 0);
}
else {
	$use_cache = false;
}

$use_cache &= ($recalcul != 'oui');
$use_cache &= empty($HTTP_POST_VARS);

if (!$use_cache) {
	include_ecrire("inc_connect.php3");
	if (!$db_ok) $use_cache = true;
}

if ($use_cache) {
	if (file_exists("ecrire/inc_meta_cache.php3")) {
		include_ecrire("inc_meta_cache.php3");
	}
	else {
		include_ecrire("inc_connect.php3");
		include_ecrire("inc_meta.php3");
	}
}
else {
	$lastmodified = time();
	include_local ("ecrire/inc_meta.php3");
	$t = time();
	if (($t - lire_meta('date_purge_cache')) > 24 * 3600) {
		ecrire_meta('date_purge_cache', $t);
		$f = fopen('CACHE/.purge', 'w');
		fclose($f);
	}

	//
	// Recalculer le cache
	//

	$calculer_cache = true;

	if ($id_rubrique) {
		$id_rubrique_fond = $id_rubrique;
	}
	else if ($id_breve) {
		$query = "SELECT id_rubrique FROM spip_breves WHERE id_breve='$id_breve'";
		$result = mysql_query($query);
		while($row = mysql_fetch_array($result)) {
			$id_rubrique_fond = $row[0];
		}
	}
	else if ($id_syndic) {
		$query = "SELECT id_rubrique FROM spip_syndic WHERE id_syndic='$id_syndic'";
		$result = mysql_query($query);
		while($row = mysql_fetch_array($result)) {
			$id_rubrique_fond = $row[0];
		}
	}
	else if ($id_article) {
		$query = "SELECT id_rubrique, chapo FROM spip_articles WHERE id_article='$id_article'";
		$result = mysql_query($query);
		while($row = mysql_fetch_array($result)) {
			$id_rubrique_fond = $row[0];
			$chapo = $row[1];
		}
		if (substr($chapo, 0, 1) == '=') {
			$url = substr($chapo, 1);
			$texte = "<?php @header (\"Location: $url\"); ?".">";
			$calculer_cache = false;
			$file = fopen($chemin_cache, "wb");
			fwrite($file, $texte);
			fclose($file);
		}
	}
	else {
		$id_rubrique_fond = 0;
	}
	if ($calculer_cache) {
		include_local ("inc-calcul.php3");
		$file = fopen($chemin_cache, "wb");
		fwrite($file, calculer_page($fond));
		fclose($file);
	}
}


//
// Inclusion du cache pour envoyer la page au client
//

if (file_exists($chemin_cache)) {
	@Header ("Last-Modified: ".gmdate("D, d M Y H:i:s T", $lastmodified));
	include ($chemin_cache);
	if ($flag_apc) {
		apc_rm($chemin_cache);
	}
}
@flush();
if (!$delais) @unlink($chemin_cache);


//
// Verifier la presence du .htaccess dans le cache, sinon le generer
//

if (!file_exists("CACHE/.htaccess")) {
	$f = fopen("CACHE/.htaccess", "w");
	fputs($f, "deny from all\n");
	fclose($f);
}


//
// Gerer l'indexation automatique
//

if (lire_meta('activer_moteur') == 'oui') {
	$fichier_index = 'CACHE/.index';
	if ($db_ok) {
		include_ecrire("inc_texte.php3");
		include_ecrire("inc_filtres.php3");
		include_ecrire("inc_index.php3");
		$s = '';
		if ($id_article AND !deja_indexe('article', $id_article))
			$s .= "article $id_article\n";
		if ($id_auteur AND !deja_indexe('auteur', $id_auteur))
			$s .= "auteur $id_auteur\n";
		if ($id_breve AND !deja_indexe('breve', $id_breve))
			$s .= "breve $id_breve\n";
		if ($id_mot AND !deja_indexe('mot', $id_mot))
			$s .= "mot $id_mot\n";
		if ($id_rubrique AND !deja_indexe('rubrique', $id_rubrique))
			$s .= "rubrique $id_rubrique\n";
		if ($s) {
			$f = fopen($fichier_index, 'a');
			fputs($f, $s);
			fclose($f);
		}
	}
	if ($use_cache AND file_exists($fichier_index) AND $size = filesize($fichier_index)) {
		include_ecrire("inc_connect.php3");
		if ($db_ok) {
			include_ecrire("inc_texte.php3");
			include_ecrire("inc_filtres.php3");
			include_ecrire("inc_index.php3");
			$f = fopen($fichier_index, 'r');
			$s = fgets($f, 100);
			$suite = fread($f, $size);
			fclose($f);
			$f = fopen($fichier_index, 'w');
			fwrite($f, $suite);
			fclose($f);
			$s = explode(' ', $s);
			indexer_objet($s[0], $s[1], false);
		}
	}
}


//
// Faire du menage dans le cache
// (effacer les fichiers tres anciens)
// Se declenche une fois par jour quand le cache n'est pas recalcule
//

if ($use_cache && file_exists('CACHE/.purge2')) {
	include_ecrire("inc_connect.php3");
	if ($db_ok) {
		unlink('CACHE/.purge2');
		$query = "SELECT fichier FROM spip_forum_cache WHERE maj < DATE_SUB(NOW(), INTERVAL 14 DAY)";
		$result = mysql_query($query);
		unset($fichiers);
		while ($row = mysql_fetch_array($result)) {
			$fichier = $row[0];
			if (!file_exists("CACHE/$fichier")) $fichiers[] = "'$fichier'";
		}
		if ($fichiers) {
			$query = "DELETE FROM spip_forum_cache WHERE fichier IN (".join(',', $fichiers).")";
			mysql_query($query);
		}
	}
}

if ($use_cache && file_exists('CACHE/.purge')) {
	include_ecrire("inc_connect.php3");
	if ($db_ok) {
		unlink('CACHE/.purge');
		$f = fopen('CACHE/.purge2', 'w');
		fclose($f);
		include_local ("inc-cache.php3");
		purger_repertoire('CACHE', 14 * 24 * 3600);
	}
}

// ---------------------------------------------------------------------------------------

//include_local ("inc-debug.php3");

//
// Afficher un bouton 
//

function bouton($titre, $lien) {
	$lapage=substr($lien, 0, strpos($lien,"?"));
	$lesvars=substr($lien, strpos($lien,"?") + 1, strlen($lien));

	echo "\n<FORM ACTION='$lapage' METHOD='get'>\n";
	$lesvars=explode("&",$lesvars);
	
	for($i=0;$i<count($lesvars);$i++){
		$var_loc=explode("=",$lesvars[$i]);
		if ($var_loc[0] != "Submit")
			echo "<INPUT TYPE='Hidden' NAME='$var_loc[0]' VALUE='$var_loc[1]'>\n";
	}
	echo "<INPUT TYPE='submit' NAME='Submit' VALUE='$titre' CLASS='spip_bouton'>\n";
	echo "</FORM>";
}


//
// Fonctionnalites administrateur (declenchees par le cookie)
//

$cookie_admin = $HTTP_COOKIE_VARS['spip_admin'];
$admin_ok = ($cookie_admin == 'admin');

if ($admin_ok AND !$flag_preserver) {
	if ($id_article) {
		bouton("Modifier cet article ($id_article)", "./ecrire/articles.php3?id_article=$id_article");
	}
	else if ($id_breve) {
		bouton("Modifier cette br&egrave;ve ($id_breve)", "./ecrire/breves_voir.php3?id_breve=$id_breve");
	}
	else if ($id_rubrique) {
		bouton("Modifier cette rubrique ($id_rubrique)", "./ecrire/naviguer.php3?coll=$id_rubrique");
	}
	else if ($id_mot) {
		bouton("Modifier ce mot-cl&eacute; ($id_mot)", "./ecrire/mots_edit.php3?id_mot=$id_mot");
	}
	else if ($id_auteur) {
		bouton("Modifier cet auteur ($id_auteur)", "./ecrire/auteurs_edit.php3?id_auteur=$id_auteur");
	}

	$link = new Link;
	$link->addVar('recalcul', 'oui');
	echo $link->getForm('GET');
	echo "<input type='submit' class='spip_bouton' name='submit' value='Recalculer cette page'>";
	echo "</form>\n";
}


//
// Gestion des statistiques par article
//

if ($id_article AND lire_meta("activer_statistiques") != "non" AND !$flag_preserver) {
	include_ecrire("inc_connect.php3");
	include_local ("inc-stats.php3");
	if ($db_ok) ecrire_stats();
}


//
// Mise a jour d'un (ou de zero) site syndique
//

if ($db_ok AND lire_meta("activer_syndic") != "non") {
	include_ecrire("inc_texte.php3");
	include_ecrire("inc_filtres.php3");
	include_ecrire("inc_sites.php3");
	include_ecrire("inc_index.php3");

	executer_une_syndication();
	executer_une_indexation_syndic();
}

?>
