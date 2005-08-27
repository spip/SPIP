<?php
/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


include ("inc.php3");
echo "<"."?xml version='1.0' encoding='UTF-8'?>";


	if ($GLOBALS["id_ajax_fonc"]) {
		$res = spip_query("SELECT * FROM spip_ajax_fonc WHERE id_ajax_fonc = $id_ajax_fonc AND id_auteur=$connect_id_auteur");
		if ($row = spip_fetch_array($res)) {
			$fonction = $row["fonction"];
			$variables = $row["variables"];
			
			$variables = unserialize($variables);
			while (list($i, $k) = each($variables)) {
				$$i = $k;
				
			}

			// Appliquer la fonction
			if ($fonction == "afficher_articles") {
				afficher_articles ($titre_table, $requete, $afficher_visites, $afficher_auteurs);
			}
		}

	}



?>