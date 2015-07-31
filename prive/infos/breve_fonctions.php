<?php
function instituer_breve($id_breve, $id_rubrique, $statut=-1){
	$statut_rubrique = autoriser('publierdans', 'rubrique', $id_rubrique);
	if ($statut_rubrique) {
		$instituer_breve = charger_fonction('instituer_breve', 'inc');
		return $instituer_breve($id_breve,$statut);
	}
	return "";
}
?>