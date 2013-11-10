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

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Afficher la puce statut d'un document :
 * en fait juste une icone independante du statut
 *
 * @param int $id
 * @param string $statut
 * @param int $id_rubrique
 * @param string $type
 * @param string $ajax
 * @return string
 */
function puce_statut_document_dist($id, $statut, $id_rubrique, $type, $ajax='') {
	return "<img src='" . chemin_image("attachment-16.png") . "' width='16' height='16' alt=''  />";
}
