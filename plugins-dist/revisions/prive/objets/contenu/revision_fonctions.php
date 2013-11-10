<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Trouver le label d'un champ de révision
 * 
 * Quelques champs ont un label dans dans les chaînes de langue de SPIP
 * Pour un champ particulier d'un objet particulier, le pipeline revisions_chercher_label
 * peut être utilisé
 * 
 * @param string $champ
 * 		Le nom du champ révisionné
 * @param string $objet
 * 		Le type d'objet révisionné
 * @return string $label
 * 		Le label du champ
 */
function label_champ($champ,$objet=false){
	$label = "";
	// si jointure: renvoyer le nom des objets joints
	if (strncmp($champ,'jointure_',9)==0)
		return _T(objet_info(objet_type(substr($champ,9)),'texte_objets'));
	
	switch ($champ){
		case 'surtitre':
			$label = "texte_sur_titre";
			break;
		case 'soustitre':
			$label = "texte_sous_titre";
			break;
		case 'nom_site':
			$label = "lien_voir_en_ligne";
			break;
		case 'email':
			$label = "entree_adresse_email_2";
			break;
		case 'login':
			$label = "item_login";
			break;
		case 'chapo':
			$champ = "chapeau";
		default:
			$label = pipeline('revisions_chercher_label',array('args'=>array('champ'=>$champ,'objet'=>$objet),'data' => $label? $label:'info_'.$champ));
			break;
	}
	return $label?_T($label):"";
}

?>