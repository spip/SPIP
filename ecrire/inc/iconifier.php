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
$logo_libelles['id_article'] = _T('logo_article').aide ("logoart");
$logo_libelles['id_auteur'] = _T('logo_auteur').aide ("logoart");
$logo_libelles['id_breve'] = _T('logo_breve').aide ("breveslogo");
$logo_libelles['id_syndic'] = _T('logo_site')." ".aide ("rublogo");
$logo_libelles['id_mot'] = _T('logo_mot_cle').aide("breveslogo");
$logo_libelles['id_rubrique'] = _T('logo_rubrique')." ".aide ("rublogo");
$logo_libelles['id_racine'] = _T('logo_standard_rubrique')." ".aide ("rublogo");


// http://doc.spip.org/@inc_iconifier_dist
function inc_iconifier_dist($id_objet, $id,  $script, $visible=false, $flag_modif=true) {
	global $logo_libelles;
	$chercher_logo = charger_fonction('chercher_logo', 'inc');

	$texteon = $logo_libelles[($id OR $id_objet != 'id_rubrique') ? $id_objet : 'id_racine'];
	$objet = substr($id_objet,3);

	$img = balise_img(chemin_image('image-24.png'), "", 'cadre-icone');
	return recuperer_fond('prive/editer/logo',array('objet'=>$objet,'id_objet'=>$id, 'titre'=>$img.$texteon));

}

?>
