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

include_spip('inc/presentation');

// http://doc.spip.org/@exec_documents_liste_dist
function exec_documents_liste_dist()
{

//
// Recupere les donnees
//

$commencer_page = charger_fonction('commencer_page', 'inc');
echo $commencer_page(_T('titre_page_documents_liste'), "naviguer", "documents");
echo debut_gauche('', true);


//////////////////////////////////////////////////////
// Boite "voir en ligne"
//

echo debut_boite_info(true);

echo propre(_T('texte_recapitiule_liste_documents'));

echo fin_boite_info(true);



echo debut_droite('', true);

	// recupere les titres des types
	$res = spip_query("SELECT * FROM spip_types_documents");
	while ($row = sql_fetch($res))
		$types[$row['extension']] = $row;

	$result = spip_query("SELECT docs.id_document AS id_doc, docs.extension AS extension, docs.fichier AS fichier, docs.date AS date, docs.titre AS titre, docs.descriptif AS descriptif, lien.id_rubrique AS id_rub, rubrique.titre AS titre_rub FROM spip_documents AS docs, spip_documents_rubriques AS lien, spip_rubriques AS rubrique WHERE docs.id_document = lien.id_document AND rubrique.id_rubrique = lien.id_rubrique AND docs.mode = 'document' ORDER BY docs.date DESC");

	while ($row=sql_fetch($result)){
		$titre=$row['titre'];
		$descriptif=$row['descriptif'];
		$date=$row['date'];
		$id_document=$row['id_doc'];
		$id_rubrique=$row['id_rub'];
		$titre_rub = typo($row['titre_rub']);
		$fichier = $row['fichier'];

		if (!$titre) $titre = _T('info_document').' '.$id_document;

		debut_cadre_relief("doc-24.gif");
		echo "<b>$titre</b> (" . $types[$row['extension']]['titre'] . ', ' . affdate($date) . ")";
		if ($descriptif)
			echo propre($descriptif);
		else
			echo "<p><tt>$fichier</tt>" . '</p>';

		echo "<p>"._T('info_dans_rubrique')." <a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "'>$titre_rub</a></p>";
		echo fin_cadre_relief(true);
	}

	echo fin_gauche(), fin_page();
}

?>
