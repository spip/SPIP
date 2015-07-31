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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/config');

//
// Gestion des documents joints
//

function configuration_documents_dist(){
	global $spip_lang_left, $spip_lang_right;
	
	$documents_rubrique = $GLOBALS['meta']["documents_rubrique"];
	$documents_article = $GLOBALS['meta']["documents_article"];
	
	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	$res .= "<tr><td class='verdana2'>";
	$res .= _T('texte_documents_joints');
	$res .= _T('texte_documents_joints_2');
	$res .= "</td></tr>";
	
	$res .= "<tr>";
	$res .= "<td align='$spip_lang_left' class='verdana2'>";
	$res .= afficher_choix('documents_article', $documents_article,
		array('oui' => _T('item_autoriser_documents_joints'),
			'non' => _T('item_non_autoriser_documents_joints')), "<br />\n");
	$res .= "<br /><br />\n";
	$res .= afficher_choix('documents_rubrique', $documents_rubrique,
		array('oui' => _T('item_autoriser_documents_joints_rubriques'),
			'non' => _T('item_non_autoriser_documents_joints_rubriques')), "<br />\n");
	$res .= "</td></tr>";
	$res .= "</table>\n";

	$res = debut_cadre_trait_couleur("doc-24.gif", true, "", _T('titre_documents_joints'))
	. ajax_action_post('configurer', 'documents', 'configuration','',$res) 
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-documents', $res);
}
?>
