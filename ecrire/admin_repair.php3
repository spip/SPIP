<?php


// reparation d'un eventuel inc_connect.php3 version "spip_query"
if (file_exists("inc_connect.php3")
AND ($conn = join('',file("inc_connect.php3")))
AND ereg("@spip_query\('SELECT COUNT", $conn)) {
	include ("inc_presentation.php3");
	$conn = ereg_replace("@spip_query\(", "@spip_query_db(", $conn);
	$myFile = @fopen("inc_connect.php3", "wb");
	if ($myFile) {
		fputs($myFile, $conn);
		fclose($myFile);
		$message = "OK. Le fichier <tt>inc_connect.php3</tt> a &eacute;t&eacute; mis &agrave; jour. Vous pouvez <a href='index.php3'>passer &agrave; la suite...</a>";
	} else
		$message = "Impossible de modifier <tt>inc_connect.php3</tt> : veuillez le supprimer par FTP puis r&eacute;installer la connexion &agrave; la base de donn&eacute;es.
		<p>(Plus technique : vous pouvez aussi tenter de le modifier &laquo;&nbsp;&agrave; la main&nbsp;&raquo;
		en transformant l'appel <tt>@spip_query</tt> en <tt>@spip_query_db</tt>.)";
	
	install_debut_html("R&eacute;paration : fichier ecrire/inc_connect.php3");
	echo "<p>$message";
	install_fin_html();
	exit;
}

include ("inc_version.php3");


include_ecrire ("inc_admin.php3");
include_ecrire ("inc_texte.php3");
include_ecrire ("inc_presentation.php3");


/*
 * REMARQUE IMPORTANTE : SECURITE
 * Ce systeme de reparation doit pouvoir fonctionner meme si
 * la table spip_auteurs est en panne : on n'appelle donc pas
 * inc_auth ; seule l'authentification ftp est exigee
 *
 */

// include_ecrire ("inc_auth.php3");
$connect_statut = '0minirezo';


function verifier_base() {
	if (! $res1= spip_query("SHOW TABLES"))
		return false;

	while ($tab = spip_fetch_row($res1)) {
		echo "<p><b>".$tab[0]."</b> ";

		if (!($result_repair = spip_query("REPAIR TABLE ".$tab[0])))
			return false;

		if (!($result = spip_query("SELECT COUNT(*) FROM ".$tab[0])))
			return false;

		list($count) = spip_fetch_row($result);
		if ($count)
			echo "($count &eacute;l&eacute;ment".($count>1 ? 's':'').")\n";
		else
			echo "(vide)\n";

		$row = spip_fetch_row($result_repair);
		$ok = ($row[3] == 'OK');

		if (!$ok)
			echo "<pre><font color='red'><b>".htmlentities(join("\n", $row))."</b></font></pre>\n";
		else
			echo " : cette table est OK.<br>\n";

	}

	return true;
}

// verifier version MySQL
if (! $res1= spip_query("SELECT version()"))
	$message = "Erreur de connexion MySQL";
else {
	$tab = spip_fetch_row($res1);
	$version_mysql = $tab[0];
	if ($version_mysql < '3.23.14')
		$message = "Votre version de MySQL ($version_mysql) ne permet pas l'auto-r&eacute;paration des tables de la base.";
	else {
		$message = "{{Lorsque certaines requ&ecirc;tes MySQL &eacute;chouent
		syst&eacute;matiquement et sans raison apparente, il est possible
		que ce soit &agrave; cause de la base de donn&eacute;es
		elle-m&ecirc;me.}}\n\n
		MySQL dispose d'une facult&eacute; de r&eacute;paration de ses
		tables lorsqu'elles ont &eacute;t&eacute; endommag&eacute;es par
		accident. Vous pouvez ici tenter cette r&eacute;paration&nbsp;; en
		cas d'&eacute;chec, conservez une copie de l'affichage, qui contient
		peut-&ecirc;tre des indices de ce qui ne va pas...\n\n
		Si le probl&egrave;me persiste, prenez contact avec votre
		h&eacute;bergeur.\n";
		$ok = true;
	}
}

$action = "Tenter une r&eacute;paration de la base de donn&eacute;es";

if ($ok) {
	debut_admin($action, $message);

	install_debut_html("Tentative de r&eacute;paration");


	debut_cadre_relief();
	if (! verifier_base())
		echo "<br><br><font color='red'><b><tt>Erreur MySQL ". spip_sql_errno().": ".spip_sql_error() ."</tt></b></font><br><br>\n";
	fin_cadre_relief();
	echo "<br>";

	install_fin_html();

	fin_admin($action);
}
else {
	install_debut_html("R&eacute;paration");
	echo "<p>$message";
	install_fin_html();
}


?>
