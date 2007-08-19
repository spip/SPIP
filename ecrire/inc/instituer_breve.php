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

function inc_instituer_breve_dist($id_breve, $statut=-1)
{
	if ($statut == -1) return "";

	$liste_statuts = array(
	  // statut => array(titre,image)
		'prop' => array(_T('item_breve_proposee'),''),	
		'publie' => array(_T('item_breve_validee'),''),	
		'refuse' => array(_T('item_breve_refusee'),'')	
	);
	if (!in_array($statut, array_keys($liste_statuts)))
		$liste_statuts[$statut] =  array($statut,'');

	$res =
	  "<ul id='instituer_breve-$id_breve' class='instituer_breve instituer'>" 
	  . "<li>" . _T('entree_breve_publiee') 
	  ."<ul>";
	
	$href = redirige_action_auteur('editer_breve',$id_breve,'breves_voir', "id_breve=$id_breve");
	foreach($liste_statuts as $s=>$affiche){
		$href = parametre_url($href,'statut',$s);
		$sel = ($s==$statut) ? " selected":"";
		$res .= "<li class='$s$sel'><a href='$href'>" . puce_statut($s) . $affiche[0] . '</a></li>';
	}

	$res .= "</ul></li></ul>";
	return $res;
}

?>