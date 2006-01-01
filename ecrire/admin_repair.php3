<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


include ("inc_version.php3");

include_ecrire ("inc_admin");
include_ecrire ("inc_texte");
include_ecrire ("inc_minipres");


/*
 * REMARQUE IMPORTANTE : SECURITE
 * Ce systeme de reparation doit pouvoir fonctionner meme si
 * la table spip_auteurs est en panne : on n'appelle donc pas
 * inc_auth ; seule l'authentification ftp est exigee
 *
 */

$connect_statut = '0minirezo';

function verifier_base() {
	if (! $res1= spip_query("SHOW TABLES"))
		return false;

	$res = "";
	while ($tab = spip_fetch_array($res1)) {
		$res .= "<p><b>".$tab[0]."</b> ";

		if (!($result_repair = spip_query("REPAIR TABLE ".$tab[0])))
			return false;

		if (!($result = spip_query("SELECT COUNT(*) FROM ".$tab[0])))
			return false;

		list($count) = spip_fetch_array($result);
		if ($count>1)
			$res .= "("._T('texte_compte_elements', array('count' => $count)).")\n";
		else if ($count==1)
			$res .= "("._T('texte_compte_element', array('count' => $count)).")\n";
		else
			$res .= "("._T('texte_vide').")\n";

		$row = spip_fetch_array($result_repair);
		$ok = ($row[3] == 'OK');

		if (!$ok)
			$res .= "<pre><font color='red'><b>".htmlentities(join("\n", $row))."</b></font></pre>\n";
		else
			$res .= " "._T('texte_table_ok')."<br>\n";

	}

	return $res;
}

// verifier version MySQL
if (! $res1= spip_query("SELECT version()"))
	$message = _T('avis_erreur_connexion_mysql');
else {
	$tab = spip_fetch_array($res1);
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

	if (! $res = verifier_base())
	  $res = "<br><br><font color='red'><b><tt>"._T('avis_erreur_mysql').' '.spip_sql_errno().': '.spip_sql_error() ."</tt></b></font><br /><br /><br />\n";
	install_debut_html(_T('texte_tentative_recuperation'));echo $res; install_fin_html();

	fin_admin($action);
}
else {
	install_debut_html(_T('titre_reparation'));echo "<p>$message</p>";install_fin_html();
}


?>
