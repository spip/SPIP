<?php

# Retourne l'appel a` spip_abstract_select de'finie dans inc-calcul,
# cense'e construire et effecteur la reque^te SQL de'termine'e 
# par les infos mises dans $boucles.
# A ce stade, on explicite les conditions exprime'es par les crite`res.
# L'utilisation de x AS x n'est pas une redondance:
# spip_abstract_select accolera au premier x la valeur de table_prefix
# qui n'a pas de raison d'apparai^tre de`s cette e'tape.

function calculer_requete(&$boucle) {
 global $table_primary, $table_des_tables, $table_date;

 $type = $boucle->type_requete;
 $id_table = $table_des_tables[$type];
 $id_field = $id_table . "." . $table_primary[$type];
  switch($type) {
  case 'articles':
    $boucle->from[] =  "articles AS $id_table";
	if (!$GLOBALS['var_preview']) {
		$boucle->where[] = "$id_table.statut='publie'";
		if (lire_meta("post_dates") == 'non')
			$boucle->where[] = "$id_table.date < NOW()";
	} else
		$boucle->where[] = "$id_table.statut IN ('publie','prop')";
    break;

  case 'auteurs':
    $boucle->from[] =  "auteurs AS $id_table";
    // Si pas de lien avec un article, selectionner
    // uniquement les auteurs d'un article publie
	if (!$GLOBALS['var_preview'])
    if (!$boucle->lien AND !$boucle->tout) {
      $boucle->from[] =  "auteurs_articles AS lien";
      $boucle->from[] =  "articles AS articles";
      $boucle->where[] = "lien.id_auteur=$id_table.id_auteur";
      $boucle->where[] = 'lien.id_article=articles.id_article';
      $boucle->where[] = "articles.statut='publie'";
      $boucle->group =  "$id_field";
    }
    // pas d'auteurs poubellises
    $boucle->where[] = "NOT($id_table.statut='5poubelle')";
    break;
    
  case 'breves':
    $boucle->from[] =  "breves AS $id_table";
	if (!$GLOBALS['var_preview'])
		$boucle->where[] = "$id_table.statut='publie'";
	else
		$boucle->where[] = "$id_table.statut IN ('publie','prop')";
	break;
    
  case 'forums':
    $boucle->from[] =  "forum AS $id_table";
    // Par defaut, selectionner uniquement les forums sans pere
    if (!$boucle->tout AND !$boucle->plat) 
      {
	$boucle->where[] = "$id_table.id_parent=0";
      }
    $boucle->where[] = "$id_table.statut='publie'";
   break;
    
  case 'signatures':
    $boucle->from[] =  "signatures AS $id_table";
    $boucle->from[] =  "petitions AS petitions";
    $boucle->from[] =  "articles articles";
    $boucle->where[] = "petitions.id_article=articles.id_article";
    $boucle->where[] = "petitions.id_article=$id_table.id_article";
    $boucle->where[] = "$id_table.statut='publie'";
    $boucle->group = "$id_field";
    break;
    
  case 'documents':
    $boucle->from[] =  "documents AS $id_table";
    $boucle->from[] =  "types_documents AS types_documents";
    $boucle->where[] = "$id_table.id_type=types_documents.id_type";
    $boucle->where[] = "$id_table.taille > 0";
    break;
    
  case 'types_documents':
    $boucle->from[] =  "types_documents AS $id_table";
    break;
    
  case 'groupes_mots':
    $boucle->from[] =  "groupes_mots AS $id_table";
    break;
    
  case 'mots':
    $boucle->from[] =  "mots AS $id_table";
    break;
    
  case 'rubriques':
    $boucle->from[] =  "rubriques AS $id_table";
	if (!$GLOBALS['var_preview'])
    if (!$boucle->tout) $boucle->where[] = "$id_table.statut='publie'";
    break;
    
  case 'hierarchie':
    $boucle->from[] =  "rubriques AS $id_table";
    break;
    
  case 'syndication':
    $boucle->from[] =  "syndic AS $id_table";
    $boucle->where[] = "$id_table.statut='publie'";
    break;
    
  case 'syndic_articles':
    $boucle->from[] =  "syndic_articles  AS $id_table";
    $boucle->from[] =  "syndic AS syndic";
    $boucle->where[] = "$id_table.id_syndic=syndic.id_syndic";
    $boucle->where[] = "$id_table.statut='publie'";
    $boucle->where[] = "syndic.statut='publie'";
    $boucle->select[]='syndic.nom_site AS nom_site'; # derogation zarbi
    $boucle->select[]='syndic.url_site AS url_site'; # idem
    break;

  default: // table hors Spip, pourquoi pas
    $boucle->from[] =  "$type AS $type";
    $id_field = '*'; // utile a` TOTAL_BOUCLE seulement
  } // fin du switch

	// En absence de champ c'est un decompte : on prend la primary pour
	// avoir qqch (le marteau-pilon * est trop couteux, et le COUNT
	// incompatible avec le cas general)
	return "spip_abstract_select(\n\t\tarray(\"". 
		((!$boucle->select) ? $id_field :
		join("\",\n\t\t\"", array_unique($boucle->select))) .
		'"), # SELECT
		array("' .
		join('","', array_unique($boucle->from)) .
		'"), # FROM
		array(' .
		(!$boucle->where ? '' : ( '"' . join('","', $boucle->where) . '"')) .
		"), # WHERE
		'".addslashes($boucle->group)."', # GROUP
		'".addslashes($boucle->order)."', # ORDER
		" . (strpos($boucle->limit, 'intval') === false ?
			"'$boucle->limit'" :
			$boucle->limit). ", # LIMIT
		'".$boucle->sous_requete."', # sous
		".$boucle->compte_requete.", # compte
		'".$id_table."', # table
		'".$boucle->id_boucle."'); # boucle";
}

?>
