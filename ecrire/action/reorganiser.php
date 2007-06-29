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

include_spip('inc/autoriser');

// http://doc.spip.org/@gerer_deplacements
function gerer_deplacements($deplacements){
	$liste_dep = explode("\n",$deplacements);
	if (count($liste_dep)){
		foreach ($liste_dep as $dep){
			$mouvement=explode(":",$dep);
			$quoi=explode("-",$mouvement[0]);
			$cible=explode("-",$mouvement[1]);
			if (in_array($quoi[0],array('article','rubrique')) && $cible[0]=='rubrique'){
				$id_quoi=intval($quoi[1]);$id_cible=intval($cible[1]);
				if (($quoi[0]=='article')&&($id_cible!=0))
					if (autoriser('modifier','rubrique',$id_cible)&& autoriser('modifier','article',$id_quoi))
						spip_query("UPDATE spip_articles SET id_rubrique="._q($id_cible)." WHERE id_article="._q($id_quoi));
				if ($quoi[0]=='rubrique')
					if (autoriser('modifier','rubrique',$id_cible)&& autoriser('modifier','rubrique',$id_quoi))
						spip_query("UPDATE spip_rubriques SET id_parent="._q($id_cible)." WHERE id_rubrique="._q($id_quoi));
			}
		}
		include_spip('inc/rubriques');
		propager_les_secteurs();
		calculer_rubriques();
	}
}

// http://doc.spip.org/@action_reorganiser_dist
function action_reorganiser_dist(){

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$securiser_action();

	if (_request('deplacements')!==NULL)
		  gerer_deplacements(_request('deplacements'));

	$redirect = _request('redirect');
	if ($redirect==NULL) $redirect="";

	redirige_par_entete(str_replace("&amp;","&",urldecode($redirect)));
}


?>
