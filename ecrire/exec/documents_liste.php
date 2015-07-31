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

function exec_documents_liste_dist()
{

//
// Recupere les donnees
//

debut_page(_T('titre_page_documents_liste'), "naviguer", "documents");
debut_gauche();


//////////////////////////////////////////////////////
// Boite "voir en ligne"
//

debut_boite_info();

echo propre(_T('texte_recapitiule_liste_documents'));

fin_boite_info();



debut_droite();

	// recupere les types
	$res = spip_query("SELECT * FROM spip_types_documents");
	while ($row = spip_fetch_array($res))
		$types[$row['id_type']] = $row;

	$result = spip_query("SELECT docs.id_document AS id_doc, docs.id_type AS type, docs.fichier AS fichier, docs.date AS date, docs.titre AS titre, docs.descriptif AS descriptif, lien.id_rubrique AS id_rub, rubrique.titre AS titre_rub FROM spip_documents AS docs, spip_documents_rubriques AS lien, spip_rubriques AS rubrique WHERE docs.id_document = lien.id_document AND rubrique.id_rubrique = lien.id_rubrique AND docs.mode = 'document' ORDER BY docs.date DESC");
	
	while($row=spip_fetch_array($result)){
			$titre=$row['titre'];
			$descriptif=$row['descriptif'];
			$date=$row['date'];
			$id_document=$row['id_doc'];
			$id_rubrique=$row['id_rub'];
			$titre_rub = typo($row['titre_rub']);
			$fichier = $row['fichier'];

			if (!$titre) $titre = _T('info_document').' '.$id_document;
			
			debut_cadre_relief("doc-24.gif");
			echo "<b>$titre</b> (" . $types[$row['type']]['titre'] . ', ' . affdate($date) . ")";
			if ($descriptif)
				echo "<p>".propre($descriptif);
			else
				echo "<p><tt>$fichier</tt>";

			echo "<p>"._T('info_dans_rubrique')." <a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "'>$titre_rub</a>";
			
			fin_cadre_relief();
	}
	

fin_page();
}
?>
