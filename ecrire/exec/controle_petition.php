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
include_spip('inc/signatures');

function message_de_signature($row)
{
  return propre(echapper_tags($row['message']));
}

function exec_controle_petition_dist()
{
  global $connect_statut, $id_article, $add_petition, $supp_petition, $debut;

	$id_article = intval($id_article);
	$add_petition =  intval($add_petition);
	$supp_petition =  intval($supp_petition);
	$debut =  intval($debut);

	debut_page(_T('titre_page_controle_petition'), "forum", "suivi-petition");
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
		$result_forum = spip_query("UPDATE spip_signatures SET statut='poubelle' WHERE id_signature=$supp_petition");

	}

	if ($add_petition){
		$result_forum = spip_query("UPDATE spip_signatures SET statut='publie' WHERE id_signature=$add_petition");

	}

	// Invalider les pages ayant trait aux petitions
	if ($id_signature = ($add_petition?$add_petition:$supp_petition)) {
		include_spip('inc/invalideur');
		$id_article = spip_fetch_array(spip_query("SELECT id_article FROM spip_signatures WHERE id_signature=$id_signature"));
		$id_article = $id_article['id_article'];
		suivre_invalideur("id='varia/pet$id_article'");
	}

	if (!$debut) $debut = 0;

	# cette requete devrait figurer dans l'optimisation
	spip_query("DELETE FROM spip_signatures WHERE NOT (statut='publie' OR statut='poubelle') AND date_time<DATE_SUB(NOW(),INTERVAL 10 DAY)");

	controle_signatures('controle_petition',
			    $id_article,
			    $debut, 
			    "(statut='publie' OR statut='poubelle')",
			    "date_time DESC",
			    10);

 }
else {
	echo "<B>"._T('avis_non_acces_page')."</B>";
}


echo "</div>";

fin_page();

}
?>
