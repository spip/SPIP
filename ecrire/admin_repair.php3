<?php

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
		if ($count>1)
			echo "("._T('texte_compte_elements', array('count' => $count)).")\n";
		if ($count==1)
			echo "("._T('texte_compte_element', array('count' => $count)).")\n";
		else
			echo "("._T('texte_vide').")\n";

		$row = spip_fetch_row($result_repair);
		$ok = ($row[3] == 'OK');

		if (!$ok)
			echo "<pre><font color='red'><b>".htmlentities(join("\n", $row))."</b></font></pre>\n";
		else
			echo _T('texte_table_ok')."<br>\n";

	}

	return true;
}

// verifier version MySQL
if (! $res1= spip_query("SELECT version()"))
	$message = _T('avis_erreur_connexion_mysql');
else {
	$tab = spip_fetch_row($res1);
	$version_mysql = $tab[0];
	if ($version_mysql < '3.23.14')
		$message = _T('avis_version_mysql', array('version_mysql' => $version_mysql));
	else {
		$message = _T('texte_requetes_echouent');
		$ok = true;
	}
}

$action = _T('texte_tenter_reparation');

if ($ok) {
	debut_admin($action, $message);

	install_debut_html(_T('texte_tentative_recuperation'));


	debut_cadre_relief();
	if (! verifier_base())
		echo "<br><br><font color='red'><b><tt>"._T('avis_erreur_mysql').' '.spip_sql_errno().': '.spip_sql_error() ."</tt></b></font><br><br>\n";
	fin_cadre_relief();
	echo "<br>";

	install_fin_html();

	fin_admin($action);
}
else {
	install_debut_html(_T('titre_reparation'));
	echo "<p>$message";
	install_fin_html();
}


?>
