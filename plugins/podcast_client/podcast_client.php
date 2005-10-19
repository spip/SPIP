<?php

// Syndication : ce plugin permet d'integrer les <enclosure>
// des flux RSS sous la forme de documents distants dans la
// table spip_documents
// (par defaut, on se contente de conserver une trace de ces
// documents dans le champ #TAGS de l'article syndique).


//
// recupere les donnees du point d'entree 'post_syndication'
//
function podcast_client() {
	list($le_lien, $id_syndic, $data) = func_get_arg(0);
	traiter_les_enclosures_rss($data['enclosures'],$id_syndic,$le_lien);
}

//
// Inserer les references aux fichiers joints
// presentes sous la forme microformat <a rel="enclosure">
//
function traiter_les_enclosures_rss($enclosures,$id_syndic,$le_lien) {
	if (!preg_match_all(
	',<a([[:space:]][^>]*)?[[:space:]]rel=[\'"]enclosure[^>]*>,',
	$enclosures, $regs, PREG_PATTERN_ORDER))
		return false;
	$enclosures = $regs[0];
	include_ecrire('inc_filtres.php3'); # pour extraire_attribut

	list($id_syndic_article) = spip_fetch_array(spip_query(
	"SELECT id_syndic_article FROM spip_syndic_articles
	WHERE id_syndic=$id_syndic AND url='".addslashes($le_lien)."'"));

	// Attention si cet article est deja vu, ne pas doubler les references
	spip_query("DELETE FROM spip_documents_syndic
	WHERE id_syndic_article=$id_syndic_article");

	// Integrer les enclosures
	foreach ($enclosures as $enclosure) {

		// href et type sont obligatoires
		if ($enc_regs_url = extraire_attribut($enclosure,'href')
		AND $enc_regs_type = extraire_attribut($enclosure,'type')) {

			$url = substr(urldecode($enc_regs_url), 0,255);
			$url = addslashes(abs_url($url, $le_lien));
			$type = $enc_regs_type;

			// Verifier que le content-type nous convient
			list($id_type) = spip_fetch_array(spip_query("SELECT id_type
			FROM spip_types_documents WHERE mime_type='$type'"));
			if (!$id_type) {
				spip_log("podcast_client: enclosure inconnue ($type) $url");
				list($id_type) = spip_fetch_array(spip_query("SELECT id_type
				FROM spip_types_documents WHERE extension='bin'"));
				// si les .bin ne sont pas autorises, on ignore ce document
				if (!$id_type) continue;
			}

			// length : optionnel (non bloquant)
			$taille = intval(extraire_attribut($enclosure, 'length'));

			// Inserer l'enclosure dans la table spip_documents
			if ($t = spip_fetch_array(spip_query("SELECT id_document FROM
			spip_documents WHERE fichier='$url' AND distant='oui'")))
				$id_document = $t['id_document'];
			else {
				spip_query("INSERT INTO spip_documents
				(id_type, titre, fichier, date, distant, taille, mode)
				VALUES ($id_type,'','$url',NOW(),'oui',$taille, 'document')");
				$id_document = spip_insert_id();
				spip_log("podcast_client: '$url' => id_document=$id_document");
			}

			// lier avec l'article syndique
			spip_query("INSERT INTO spip_documents_syndic
			(id_document, id_syndic, id_syndic_article)
			VALUES ($id_document, $id_syndic, $id_syndic_article)");

			$n++;
		}
	}

	return $n; #nombre d'enclosures integrees
}

?>
