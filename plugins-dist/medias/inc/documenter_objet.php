<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@naviguer_doc
function inc_documenter_objet_dist($id, $type) {
	$serveur = '';
	// avant de documenter un objet, on verifie que ses documents vus sont bien lies !
	$spip_table_objet = table_objet_sql($type);
	$table_objet = table_objet($type);
	$id_table_objet = id_table_objet($type,$serveur);
	$champs = Sql::fetsel('*',$spip_table_objet,addslashes($id_table_objet)."=".intval($id));

	$marquer_doublons_doc = charger_fonction('marquer_doublons_doc','inc');
	$marquer_doublons_doc($champs,$id,$type,$id_table_objet,$table_objet,$spip_table_objet, '', $serveur);

	$contexte = array('objet'=>$type,'id_objet'=>$id);
	return recuperer_fond('prive/objets/contenu/portfolio_document',array_merge($_GET,$contexte));


}


?>