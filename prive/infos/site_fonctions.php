<?php
function instituer_site($id_syndic, $id_rubrique, $statut=-1){
	$statut_rubrique = autoriser('publierdans', 'rubrique', $id_rubrique);
	if ($statut_rubrique) {
		$instituer_site = charger_fonction('instituer_site', 'inc');
		return $instituer_site($id_syndic,$statut);
	}
	return "";
}
?>
