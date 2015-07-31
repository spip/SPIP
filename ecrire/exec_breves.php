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

include_ecrire("inc_presentation");
include_ecrire("inc_texte");
include_ecrire("inc_urls");
include_ecrire("inc_rubriques");

function enfant_breves($leparent){
	global $spip_lang_left, $spip_lang_right;

 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY 0+titre, titre";
 	$result=spip_query($query);

 	while($row=spip_fetch_array($result)){
		$id_rubrique=$row['id_rubrique'];
		$id_parent=$row['id_parent'];
		$titre=typo($row['titre']);
		$descriptif=$row['descriptif'];
		$texte=$row['texte'];
		$editable = ($GLOBALS['connect_statut'] == "0minirezo")
		  && acces_rubrique($id_rubrique);

		$statuts = "'prop', 'publie'" . ($editatble ? ", 'refuse'": "");

		$query = "SELECT id_breve, date_heure, titre, statut FROM spip_breves ".
			"WHERE id_rubrique='$id_rubrique' AND statut IN ($statuts) ORDER BY date_heure DESC";
		
		debut_cadre_enfonce("secteur-24.gif", false, '', $titre.aide ("breves"));
		afficher_breves('', $query);
						 
		if ($editable) {
		  echo "<div align='$spip_lang_right'>";
		  icone(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui&id_rubrique=$id_rubrique"), "breve-24.gif", "creer.gif");
		  echo "</div>";
		}
		fin_cadre_enfonce();
	}
}

function breves_dist()
{
	global $connect_statut,$id_breve, $statut, $id_rubrique;
	if ($statut AND $connect_statut == "0minirezo") {
	 	$cond = "WHERE id_breve=" . intval($id_breve);
		list($statut_ancien) = spip_fetch_array(spip_query("SELECT statut FROM spip_breves $cond"));
		if ($statut != $statut_ancien) {
			spip_query("UPDATE spip_breves SET date_heure=NOW(), statut='$statut'" . $cond);
			include_ecrire("inc_rubriques");
			calculer_rubriques();
		}
		redirige_par_entete(generer_url_ecrire("naviguer"),"?id_rubrique=$id_rubrique");
	} else {

		debut_page(_T('titre_page_breves'), "documents", "breves");
		debut_gauche();
		debut_droite();
		enfant_breves(0);
		fin_page();
	}
}

?>
