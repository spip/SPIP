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

function inc_verifier_document_mode_vignette_dist($infos){

	// Si on veut uploader une vignette, il faut qu'elle ait ete bien lue
	if ($infos['inclus'] != 'image')
		return _T('medias:erreur_format_fichier_image',array('nom'=> $infos['fichier'])); #SVG

	if (isset($infos['largeur']) AND isset($infos['hauteur'])){
		if (!($infos['largeur'] OR $infos['hauteur']))
			return _T('medias:erreur_upload_vignette',array('nom'=>$infos['fichier']));
	}

	return true;
}