<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/rechercher');
include_spip('base/abstract_sql');

function inclure_liste_recherche_par_id($table,$id,$statut,$env){
	if (is_string($env))
		$env = unserialize($env);
	$env[id_table_objet($table)]=$id;
	if ($statut)
		$env['statut'] = $statut;
	unset($env['recherche']);
	return recuperer_fond("prive/objets/liste/$table",$env);
}
?>