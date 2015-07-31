<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@verifier_base
function base_admin_repair_dist() {
	$res1= spip_query("SHOW TABLES");

	$res = "";
	if ($res1) { while ($tab = spip_fetch_array($res1,SPIP_NUM)) {
		$res .= "<p><b>".$tab[0]."</b> ";

		$result_repair = spip_query("REPAIR TABLE ".$tab[0]);
		if (!$result_repair) return false;

		$result = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM ".$tab[0]));
		if (!$result) return false;

		$count = $result['n'];
		if ($count>1)
			$res .= "("._T('texte_compte_elements', array('count' => $count)).")\n";
		else if ($count==1)
			$res .= "("._T('texte_compte_element', array('count' => $count)).")\n";
		else
			$res .= "("._T('texte_vide').")\n";

		$row = spip_fetch_array($result_repair,SPIP_NUM);
		$ok = ($row[3] == 'OK');

		if (!$ok)
			$res .= "<pre><span style='color: red; font-weight: bold;'>".htmlentities(join("\n", $row))."</span></pre>\n";
		else
			$res .= " "._T('texte_table_ok')."<br />\n";
	  }
	}

	if (!$res) {
		$res = "<br /><br /><span style='color: red; font-weight: bold;'><tt>"._T('avis_erreur_mysql').' '.spip_sql_errno().': '.spip_sql_error() ."</tt></span><br /><br /><br />\n";
	}
	echo minipres(_T('texte_tentative_recuperation'), $res);
}
?>
