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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');

function exec_breves_dist()
{
	global $spip_lang_left, $spip_lang_right;

	charger_generer_url();
	debut_page(_T('titre_page_breves'), "documents", "breves");
	debut_gauche();
	debut_droite();

 	$result=spip_query("SELECT * FROM spip_rubriques WHERE id_parent=0 ORDER BY 0+titre, titre");

 	while($row=spip_fetch_array($result)){
		$id_rubrique=$row['id_rubrique'];
		$id_parent=$row['id_parent'];
		$titre=typo($row['titre']);
		$descriptif=$row['descriptif'];
		$texte=$row['texte'];
		$editable = ($GLOBALS['connect_statut'] == "0minirezo")
		  && acces_rubrique($id_rubrique);

		$statuts = "'prop', 'publie'" . ($editatble ? ", 'refuse'": "");

		debut_cadre_enfonce("secteur-24.gif", false, '', $titre.aide ("breves"));

		afficher_breves('', array("SELECT" => 'id_rubrique, id_breve, date_heure, titre, statut', "FROM" => 'spip_breves', 'WHERE' => "id_rubrique='$id_rubrique' AND statut IN ($statuts)", 'ORDER BY' => "date_heure DESC"));
		
		if ($editable) {
		  echo "<div align='$spip_lang_right'>";
		  icone(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui&id_rubrique=$id_rubrique"), "breve-24.gif", "creer.gif");
		  echo "</div>";
		}
		fin_cadre_enfonce();
	}

	fin_page();
}

?>
