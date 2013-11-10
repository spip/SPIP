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

function action_supprimer_tous_orphelins()
{
	$securiser_action = charger_fonction('securiser_action','inc');
	$arg = $securiser_action();

	//on recupere le contexte pour ne supprimer les orphelins que de ce dernier
	list($media,$distant,$statut,$sanstitre) = explode('/',$arg);
	
	//critere sur le media
	if($media)
		$select = "media=".Sql::quote($media);

	//critere sur le distant
	if($distant)
		$where[] = "distant=".Sql::quote($distant);

	//critere sur le statut
	if($statut)
		$where[] = "statut REGEXP ".Sql::quote("($statut)");

	//critere sur le sanstitre
	if($sanstitre)
		$where[] = "titre=''";

	//on isole les orphelins
	$select = Sql::get_select("DISTINCT id_document","spip_documents_liens as oooo");
	$cond = "spip_documents.id_document NOT IN ($select)";
	$where[] = $cond;

	$ids_doc_orphelins = Sql::select( "id_document", "spip_documents", $where );

	$supprimer_document = charger_fonction('supprimer_document','action');
	while ($row = Sql::fetch($ids_doc_orphelins)) {
		$supprimer_document($row['id_document']); // pour les orphelins du contexte, on traite avec la fonction existante
	}
}

?>