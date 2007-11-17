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
// http://doc.spip.org/@inc_afficher_contenu_objet_dist
function inc_afficher_contenu_objet_dist($type, $id,$row = NULL){	
	include_spip('public/assembler');
	if ($GLOBALS['champs_extra'] AND $row['extra'])
		include_spip('inc/extra');
	$contexte = array('id'=>$id,'champs_extra'=>$GLOBALS['champs_extra']);
	$contenu_objet .= recuperer_fond("prive/contenu/$type",$contexte);
	
	// permettre aux plugin de faire des modifs ou des ajouts
	$contenu_objet = pipeline(
		'afficher_contenu_objet',
		array(
			'data'=>$contenu_objet,
			'args'=>array('type'=>$type,$key=>$id)
		)
	);
	
	return "<div id='wysiwyg'>$contenu_objet</div>";
}


?>