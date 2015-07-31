<?php

include ("inc_version.php3");
include_ecrire ("inc_connect.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_session.php3");
include_ecrire ("inc_presentation.php3");
include_ecrire ("inc_layer.php3");
include_ecrire ("inc_texte.php3");
include_ecrire ("inc_filtres.php3");
include_ecrire ("inc_admin.php3");

function verifier_base() {
	if (! $res1= mysql_query("SELECT version()"))
		return false;
	if ($tab = mysql_fetch_row($res1))
		gros_titre("Version MySQL = ".$tab[0]);
	else
		return false;

	if (! $res1= mysql_query("SHOW TABLES"))
		return false;

	while ($tab = mysql_fetch_row($res1)) {
		echo "<br><p><b>".$tab[0]."</b> : \n";
		if (! $res = mysql_query("SELECT * FROM ".$tab[0]))
			return false;
		echo mysql_num_rows($res)." &eacute;l&eacute;ment(s).<br><br>\n";

		if (! $res = mysql_query("REPAIR TABLE ".$tab[0]))
			return false;
		while ($row = mysql_fetch_row($res)) {
			if ($row[3] <> 'OK') echo "<font color='red'>";
			while (list(,$val) =each ($row))
				echo "<tt>".htmlentities($val)."</tt><br>\n";
			if ($row[3] <> 'OK') echo "</font>";
		}
	}

	return true;
}


verifier_visiteur();

if (!$auteur_session) {
	$connect_statut = '0minirezo'; // vilain !
	$action = "Tenter une r&eacute;paration de la base de donn&eacute;es";
	debut_admin($action);
}
else {
	include ("inc.php3");

	debut_page("Maintenance technique : v&eacute;rification et r&eacute;paration de la base", "administration", "base");


	echo "<br><br><br>";
	gros_titre("Maintenance technique : v&eacute;rification et r&eacute;paration de la base");
	barre_onglets("administration", "repair");


	debut_gauche();

	debut_boite_info();

	echo propre("{{Cette page est uniquement accessible aux responsables du site.}}<P> Elle donne acc&egrave;s aux diff&eacute;rentes
	fonctions de maintenance technique. Certaines d'entre elles donnent lieu &agrave; un processus d'authentification sp&eacute;cifique, qui
	exige d'avoir un acc&egrave;s FTP au site Web.");

	fin_boite_info();

	debut_droite();

	if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
		echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
		fin_page();
		exit;
	}
}

if ($action OR ($verifier_base == 'oui')) {
	debut_cadre_relief();
	if (! verifier_base())
		echo "<br><br><font color='red'><b><tt>Erreur MySQL ". mysql_errno().": ".mysql_error() ."</tt></b></font><br><br>\n";
	fin_cadre_relief();
	echo "<br>";
}


if (!$action) {
	echo "<br><p>".propre("{{Lorsque certaines requêtes MySQL échouent
	systématiquement et sans raison apparente, il est possible que ce soit à
	cause de la base de données elle-même.}}\n\nOr MySQL, dans ses versions les
	plus récentes, dispose d'une faculté d'auto-réparation de ses tables
	lorsque les index ont été perturbés par accident (suite à des erreurs
	disque, redémarrage violent, etc.) Cliquez sur ce bouton pour tenter
	cette auto-réparation; en cas d'échec, conservez une copie de
	l'affichage, qui contient peut-être des indices de ce qui ne va
	pas...\n\nSi le problème persiste, prenez contact avec votre hébergeur.");

	echo "<form method='post' action='admin_repair.php3'>";
	echo "<input type='hidden' name='verifier_base' value='oui'>";
	echo "<div align='right'><input class='fondo' type='submit' name='ok' value='Tenter une r&eacute;paration de la base de donn&eacute;es'>";
	echo "</div></form>";

	fin_page();
}
else {
	fin_admin($action);
}

?>
