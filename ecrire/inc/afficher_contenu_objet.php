<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

// affichage du contenu d'un objet spip (onglet contenu)
// Cas generique, utilise pour tous les objets
// http://doc.spip.org/@inc_afficher_contenu_objet_dist
function inc_afficher_contenu_objet_dist($type, $id, $id_rubrique){	
	include_spip('public/assembler');
	$contexte = array('id' => $id, 'id_rubrique' => $id_rubrique);
	
	// permettre aux plugin de faire des modifs ou des ajouts
	$contenu_objet = pipeline('afficher_contenu_objet',
		array(
			'data'=> recuperer_fond("prive/contenu/$type",$contexte),
			'args'=>array('type'=>$type,'id'=>$id)
		)
	);
	
	return "<div id='wysiwyg'>$contenu_objet</div>";
}


?>
