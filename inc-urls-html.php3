<?

// executer une seule fois
if (defined("_INC_URLS2")) return;
define("_INC_URLS2", "1");

function generer_url_article($id_article) {
	$url = "article$id_article.html";
	return $url;
}

function generer_url_rubrique($id_rubrique) {
	$url = "rubrique$id_rubrique.html";
	return $url;
}

function generer_url_breve($id_breve) {
	$url = "breve$id_breve.html";
	return $url;
}

function generer_url_forum($id_forum) {
	$url = "forum$id_forum.html";
	return $url;
}

function generer_url_mot($id_mot) {
	$url = "mot$id_mot.html";
	return $url;
}

function generer_url_auteur($id_auteur) {
	$url = "auteur$id_auteur.html";
	return $url;
}

function generer_url_document($id_document) {
	if ($id_document > 0){
		$query = "SELECT * FROM spip_documents WHERE id_document = $id_document";
		$result = mysql_query($query);
		if ($row = mysql_fetch_array($result)) {
			$url = $row['nom_fichier_doc'];
		}
	}
	return $url;
}

function recuperer_parametres_url($fond, $url) {
	global $contexte;
	return;
}


?>