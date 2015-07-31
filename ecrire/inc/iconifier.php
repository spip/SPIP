<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');
global $logo_libelles;
$logo_libelles['article'] = _T('logo_article').aide ("logoart");
$logo_libelles['auteur'] = _T('logo_auteur').aide ("logoart");
$logo_libelles['breve'] = _T('logo_breve').aide ("breveslogo");
$logo_libelles['syndic'] = _T('logo_site')." ".aide ("rublogo");
$logo_libelles['mot'] = _T('logo_mot_cle').aide("breveslogo");
$logo_libelles['groupe'] = _T('logo_groupe').aide("breveslogo");
$logo_libelles['rubrique'] = _T('logo_rubrique')." ".aide ("rublogo");
$logo_libelles['racine'] = _T('logo_standard_rubrique')." ".aide ("rublogo");


// http://doc.spip.org/@inc_iconifier_dist
function inc_iconifier_dist($objet, $id,  $script, $visible=false, $flag_modif=true) {
	global $logo_libelles;
	// compat avec anciens appels
	if (substr($objet,0,3)=='id_')
		$objet = substr($objet,3);

	$chercher_logo = charger_fonction('chercher_logo', 'inc');

	$texteon = $logo_libelles[($id OR $objet != 'rubrique') ? $objet : 'racine'];

	$img = balise_img(chemin_image('image-24.png'), "", 'cadre-icone');
	return recuperer_fond('prive/editer/logo',array('objet'=>$objet,'id_objet'=>$id, 'titre'=>$img.$texteon));

}

?>
