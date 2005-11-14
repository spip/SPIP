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

include_ecrire("inc_presentation.php3");
include ("inc_signatures.php3");

function message_de_signature($row)
{
  return propre(echapper_tags($row['message']));
}


function controle_petition($id_article, $add_petition, $supp_petition, $debut)
{
	global $connect_statut;
	debut_page(_T('titre_page_controle_petition'), "redacteurs", "suivi-petition");
	debut_gauche();

//
// Raccourcis
//
/*
	debut_raccourcis();
	// rien
	fin_raccourcis();
*/

debut_droite();

  
echo "<div class='serif2'>";
 
if ($connect_statut == "0minirezo") {
	gros_titre(_T('titre_suivi_petition'));

	if ($supp_petition){
		$query_forum = "UPDATE spip_signatures SET statut='poubelle' WHERE id_signature=$supp_petition";
 		$result_forum = spip_query($query_forum);
	}

	if ($add_petition){
		$query_forum = "UPDATE spip_signatures SET statut='publie' WHERE id_signature=$add_petition";
 		$result_forum = spip_query($query_forum);
	}

	// Invalider les pages ayant trait aux petitions
	if ($id_signature = ($add_petition?$add_petition:$supp_petition)) {
		include_ecrire('inc_invalideur.php3');
		list ($id_article) = spip_fetch_array(spip_query("SELECT id_article
			FROM spip_signatures WHERE id_signature=$id_signature"));
		suivre_invalideur("id='varia/pet$id_article'");
	}

	if (!$debut) $debut = 0;

	spip_query("DELETE FROM spip_signatures WHERE NOT (statut='publie' OR statut='poubelle') AND date_time<DATE_SUB(NOW(),INTERVAL 10 DAY)");

	controle_signatures('controle_petition.php3',
			    $id_article,
			    $debut, 
			    "(statut='publie' OR statut='poubelle')",
			    "date_time DESC");

 }
else {
	echo "<B>"._T('avis_non_acces_page')."</B>";
}


echo "</div>";

fin_page();

}
?>

