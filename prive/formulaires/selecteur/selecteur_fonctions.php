<?php


/**
 * Transformer un tableau d'entrees array("rubrique|9","article|8",...)
 * en un tableau contenant uniquement les identifiants d'un type donne.
 * Accepte aussi que les valeurs d'entrees soient une chaine brute
 * "rubrique|9,article|8,..."
 *
 * @param array/string $selected liste des entrees : tableau ou chaine separee par des virgules
 * @param string $type type de valeur a recuperer ('rubrique', 'article')
 *
 * @return array liste des identifiants trouves.
**/
function picker_selected($selected, $type){
	$select = array();
	$type = preg_replace(',\W,','',$type);

	if ($selected and !is_array($selected))
		$selected = explode(',', $selected);

	if (is_array($selected))
		foreach($selected as $value)
			if (preg_match(",".$type."[|]([0-9]+),",$value,$match)
			  AND strlen($v=intval($match[1])))
			  $select[] = $v;
	return $select;
}

function picker_identifie_id_rapide($ref,$rubriques=0,$articles=0){
	include_spip("inc/json");
	include_spip("inc/lien");
	if (!($match = typer_raccourci($ref)))
		return json_export(false);
	@list($type,,$id,,,,) = $match;
	if (!in_array($type,array($rubriques?'rubrique':'x',$articles?'article':'x')))
		return json_export(false);
	$table_sql = table_objet_sql($type);
	$id_table_objet = id_table_objet($type);
	if (!$titre = sql_getfetsel('titre',$table_sql,"$id_table_objet=".intval($id)))
		return json_export(false);
	$titre = attribut_html(extraire_multi($titre));
	return json_export(array('type'=>$type,'id'=>"$type|$id",'titre'=>$titre));
}

?>