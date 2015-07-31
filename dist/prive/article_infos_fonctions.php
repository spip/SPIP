<?php
function instituer_article($id_article, $id_rubrique, $statut=-1){
	$statut_rubrique = autoriser('publierdans', 'rubrique', $id_rubrique);
	if ($statut_rubrique) {
		$instituer_article = charger_fonction('instituer_article', 'inc');
		return $instituer_article($id_article,$statut);
	}
	return "";
}
?>