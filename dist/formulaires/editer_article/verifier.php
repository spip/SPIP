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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');
include_spip('inc/editer');

function formulaires_editer_article_verifier_dist($id_article='new', $id_rubrique=0, $lier_trad=0, $retour='', $config_fonc='articles_edit_config', $row=array(), $hidden=''){

	$erreurs = array();
	if (intval($id_article)) {
		$conflits = controler_contenu('article',$id_article);
		if (count($conflits)) {
			foreach($conflits as $champ=>$conflit){
				$erreurs[$champ] .= _L("ATTENTION : Ce champ a &eacute;t&eacute; modifi&eacute; par ailleurs. La valeur actuelle est :<br /><textarea readonly='readonly' class='forml'>".$conflit['base']."</textarea>");
			}
		}
	}
	foreach(array('titre') as $obli){
		if (!_request($obli))
			$erreurs[$obli] .= _L("Cette information est obligatoire");;
	}

	return $erreurs;
}

?>