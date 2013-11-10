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

function action_importer_bookmarks_netscape_dist($fichier_ok,$id_parent,$importer_statut_publie,$importer_tags){

	$nb = 0;
	if (autoriser('importer','_bookmarks')){
		$out = bookmarks_netscape_fast_parse($fichier_ok['contenu']);

		// le premier content ne nous interesse pas
		$out = reset($out['sub']);
		$nb = bookmarks_insert($out,$id_parent,$importer_statut_publie,$importer_tags);
	}
	return $nb;
}

function bookmarks_netscape_fast_parse(&$contenu){
	$out = array();
	#var_dump(">>".substr($contenu,0,200));

	$po=stripos($contenu,"<h3",4);
	$pf=stripos($contenu,"</dl>");
	while ($po OR $pf){
		#var_dump("$po:$pf");
		if ($po>0 AND $po<$pf){
			$out['content'] .= substr($contenu,0,$po);
			$contenu = substr($contenu,$po);
			$out['sub'][] = bookmarks_netscape_fast_parse($contenu);
		}
		else {

			$out['content'] .= substr($contenu,0,$pf);
			$contenu = substr($contenu,$pf+5);
			#var_dump("<<".substr($contenu,0,200));
			$out['content'] = bookmarks_extract_links($out['content']);
			return $out;
		}
		$po=stripos($contenu,"<h3");
		$pf=stripos($contenu,"</dl>");
	}
	$out['content'] = bookmarks_extract_links($out['content']);
	return $out;
}

function bookmarks_extract_links($contenu){
	$out = array();
	$contenu = str_ireplace("<DT>","<dt>",$contenu);
	$contenu = explode("<dt>",$contenu);

	$h3 = array_shift($contenu);
	$h3 = extraire_balise($h3,"h3");
	$out['titre'] = strip_tags($h3);

	foreach($contenu as $item){
		$link = array();
		if ($a = extraire_balise($item,'a')){
			$link['url'] = extraire_attribut($a,'href');
			$link['titre'] = strip_tags($a);
			$link['date'] = extraire_attribut($a,"add_date");
			$link['descriptif'] = "";

			if ($p=stripos($item,"<dd>")){
				$link['descriptif'] = textebrut(substr($item,$p));
			}
			$out['links'][] = $link;
		}
	}
	return $out;
}

function bookmarks_insert($tree,$id_parent,$importer_statut_publie,$importer_tags,$level=0){
	include_spip('action/editer_rubrique');
	include_spip('action/editer_site');

	$nb = 0;
	if (count($tree['content']['links'])
		OR isset($tree['sub'])){

		$titre = ($tree['content']['titre']?$tree['content']['titre']:_T('info_sans_titre'));
		$id_rubrique = Sql::getfetsel('id_rubrique','spip_rubriques','id_parent='.intval($id_parent)." AND titre=".Sql::quote($titre));
		if (!$id_rubrique
			AND $id_rubrique = rubrique_inserer($id_parent)){
			rubrique_modifier($id_rubrique,array('titre'=>$titre));
		}
		if ($id_rubrique){
			$statut = 'prop';
			if ($importer_statut_publie AND autoriser('publierdans','rubrique',$id_rubrique))
				$statut = 'publie';
			$now = time();
			foreach($tree['content']['links'] as $link){
				if (!$id_syndic = Sql::getfetsel('id_syndic',
				                                'spip_syndic',
				                                'id_rubrique='.intval($id_rubrique)." AND url_site=".Sql::quote($link['url']))){
					$id_syndic = site_inserer($id_rubrique);
					$set = array(
									'url_site' => $link['url'],
									'nom_site' => $link['titre'],
									'date' => date('Y-m-d H:i:s',$link['date']?$link['date']:$now),
									'statut' => $statut,
									'descriptif' => $link['descriptif']
								 );
					#echo "creation site $id_syndic ".$set['url_site']." <br />";
					site_modifier($id_syndic,$set);
					$nb ++;
				}
				else {
					#echo "existant site $id_syndic ".$link['url']." <br />";
					$nb ++;
				}
			}
			if ($level<30){
				if (isset($tree['sub']))
					foreach($tree['sub'] as $sub)
						$nb += bookmarks_insert($sub,$id_rubrique,$importer_statut_publie,$importer_tags,$level+1);
			}
		}
	}

	return $nb;
}