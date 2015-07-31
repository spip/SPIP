<?php

function barre_typo($id,$lang=''){
	include_spip('inc/barre');
	return '<div>' . afficher_barre("document.getElementById('$id')",false,$lang) . '</div>';
}

?>