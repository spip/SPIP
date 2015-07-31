<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

// affichage du contenu d'un objet spip (onglet contenu)
// Cas generique, utilise pour tous les objets
function inc_afficher_contenu_objet_dist($type, $id,$row = NULL){
	$table = table_objet($type);
	$key = id_table_objet($type);
	if (!$row) {
		$res = sql_select(array('*'),array("spip_$table"),array("$key="._q($id)));
		$row = sql_fetch($res);
	}
	if (!$row) return "";

	if (isset($row['lang'])) {
		changer_typo($row['lang']);
	}
 	$lang_dir = lang_dir($GLOBALS['lang_objet']);

	// demander un englobant des champs=>libelles a afficher pour cet objet
	$champs_libelles = pipeline(
		'afficher_objet_champs_libelles',
		array(
			'data'=>afficher_objet_champs_libelles($type,$table,$id,$row),
			'args'=>array('type'=>$type,$key=>$id)
		)
	);
	
	// ne considerer que les champs presents en base
	foreach($champs_libelles as $champ=>$libelle)
		if ($champ!='notes' && !isset($row[$champ]))
			unset($champs_libelles[$champ]);

	/* TODO, mais il manque encore des concepts comme la boucle FOR pour y arriver
	$contexte = array($key=>$id,'type'=>$type,'valeurs'=>$row, 'champs'=>$champs_libelles);
	include_spip('inc/assembler');
	$contenu_objet = recuperer_fond('prive/afficher_contenu_objet',$contexte);*/
	
	global  $table_des_traitements;
	include_spip('public/interfaces');
	// afficher chaque champ
	// lui appliquer le traitement public
	foreach($champs_libelles as $champ=>$libelle) {
		if ($champ!='notes') {
			$valeur = $row[$champ];
		}
		else $valeur = $GLOBALS['les_notes'];
		$balise = strtoupper($champ);
		if (isset($table_des_traitements[$balise])) {
			$filtre = end($table_des_traitements[$balise]);
			$filtre = str_replace('%s',"'".addslashes($valeur)."'", $filtre);
			$valeur = eval("return $filtre;");
		}
		if ($champ!='notes' OR strlen($valeur))
			$contenu_objet .= 
				"<span class='contenu_$champ'>"
				. "<span class='label'>$libelle</span>"
				. "<span  dir='$lang_dir' class='$champ crayon $type-$champ-$id'>$valeur</span>"
				. "</span>";

	}

	// il serait temps de s'en debarasser, non ? pas de squelette pour eux ...
	if ($champs_extra AND $row['extra']) {
		include_spip('inc/extra');
		$contenu_objet .= extra_affichage($row['extra'], $table);
	}
	
	// permettre a un plugin de faire des modifs
	$contenu_objet = pipeline(
		'afficher_contenu_objet',
		array(
			'data'=>$contenu_objet,
			'args'=>array('type'=>$type,$key=>$id)
		)
	);
	
	return "<div id='wysiwyg'>$contenu_objet</div>";
}

// donner la liste des champs a afficher dans l'espace prive
// pour un objet
// cette liste peut etre un englobant, les elements non pertinents pour l'objet considere seront enleves
function afficher_objet_champs_libelles($type,$table,$id, $row){
	$liste = array(
		'surtitre' => _T('texte_sur_titre'),
		'titre' => _T('info_titre'),
		'soustitre' => _T('texte_sous_titre'),
		'descriptif' => _T('info_descriptif'),
		'chapo' => _T('info_chapeau'),
		($type == breve ?'lien_titre':'nom_site') => ($type=='site'?_T('form_prop_nom_site'):_T('info_lien_hypertexte')),
		($type == breve ?'lien_url':'url_site') => ($type=='site'?_T('form_prop_nom_site'):_T('info_lien_hypertexte')),
		'texte' => _T('info_texte'),
		'ps' => _T('info_ps'),
		'notes' => _T('info_notes')
	);

	// gerer les champs desactives sur option
	foreach(array_keys($liste) as $champ) {
		if (isset($GLOBALS['meta']["$table_$champ"])
			AND $GLOBALS['meta']["articles_$champ"]=='non'
			AND (!isset($row[$champ]) OR !strlen($row[$champ]))) {
				unset($liste[$champ]);
			}
	}
	
	// TODO , gerer ici des eventuelles autorisation de voir via autoriser ...

	return $liste;
}
	

?>