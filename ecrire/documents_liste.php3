<?php

include ("inc.php3");
include_ecrire ("inc_index.php3");
include_ecrire ("inc_logos.php3");




//
// Recupere les donnees
//

debut_page("Les documents des rubriques", "documents", "documents");
debut_gauche();


//////////////////////////////////////////////////////
// Boite "voir en ligne"
//

debut_boite_info();

echo propre("Cette page r&eacute;capitule la liste des documents que vous avez plac&eacute; dans les rubriques. Pour modifier les informations de chaque document, suivez le lien vers la page de sa rubrique.");

fin_boite_info();



debut_droite();

	$query = "SELECT docs.id_document AS id_doc, docs.date AS date, docs.titre AS titre, docs.descriptif AS descriptif, lien.id_rubrique AS id_rub, rubrique.titre AS titre_rub FROM spip_documents AS docs, spip_documents_rubriques AS lien, spip_rubriques AS rubrique WHERE docs.id_document = lien.id_document AND rubrique.id_rubrique = lien.id_rubrique AND docs.mode = 'document' ORDER BY docs.date DESC";
	$result = spip_query($query);
	
	while($row=spip_fetch_array($result)){
			$titre=$row['titre'];
			$descriptif=$row['descriptif'];
			$date=$row['date'];
			$id_document=$row['id_doc'];
			$id_rubrique=$row['id_rub'];
			$titre_rub=$row['titre_rub'];
			
			if (strlen($titre) == 0) $titre = "Document $id_document";
			// echo "<li>$date : $titre / $descriptif / <a href='naviguer.php3?coll=$id_rubrique'>$titre_rub</a>";
			
			debut_cadre_relief("doc-24.gif");
			echo "<b>$titre</b><br>";
			echo affdate($date);
			if (strlen($descriptif)>0) echo "<p>".propre($descriptif);
			
			echo "<p>Dans la rubrique : <a href='naviguer.php3?coll=$id_rubrique'>$titre_rub</a>";
			
			fin_cadre_relief();
	}
	

fin_page();

?>
