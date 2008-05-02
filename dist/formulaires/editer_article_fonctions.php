<?php

function chercher_rubrique($msg, $id_rubrique, $id_secteur, $restreint){
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');
	$opt = $chercher_rubrique($id_rubrique, 'article', $restreint);

	if ($id_rubrique == 0) $logo = "racine-site-24.gif";
	elseif ($id_secteur == $id_rubrique) $logo = "secteur-24.gif";
	else $logo = "rubrique-24.gif";

	include_spip('inc/presentation');
	return debut_cadre_couleur($logo, true, "", $msg) . $opt .fin_cadre_couleur(true);
}

function barre_typo($id,$lang=''){
	include_spip('inc/barre');
	return '<div>' . afficher_barre("document.getElementById('$id')",false,$lang) . '</div>';
}

?>