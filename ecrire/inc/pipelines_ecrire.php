<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// Inserer jQuery pour ecrire/
// http://doc.spip.org/@f_jQuery
function f_jQuery_prive ($texte) {
	$x = '';
	foreach (array_unique(pipeline('jquery_plugins',
	array(
		'javascript/jquery.js',
		'javascript/jquery.form.js',
		'javascript/jquery.autosave.js',
		'javascript/jquery.placeholder-label.js',
		'javascript/ajaxCallback.js',
		'javascript/jquery.colors.js',
		'javascript/jquery.cookie.js',
		'javascript/spip_barre.js',
	))) as $script)
		if ($script = find_in_path($script))
			$x .= "\n<script src=\"$script\" type=\"text/javascript\"></script>\n";
	// inserer avant le premier script externe ou a la fin
	if (preg_match(",<script[^><]*src=,",$texte,$match)
	  AND $p = strpos($texte,$match[0])){
	  $texte = substr_replace($texte,$x,$p,0);
	}
	else
		$texte .= $x;
	return $texte;
}

/**
 * Ajout automatique du title dans les pages du prive en squelette
 * appelle dans le pipeline affichage_final_prive
 *
 * @param string $texte
 * @return string
 */
function affichage_final_prive_title_auto($texte){
	if (strpos($texte,'<title>')===false
	  AND
			(preg_match(",<h1[^>]*>(.+)</h1>,Uims", $texte, $match)
		   OR preg_match(",<h[23][^>]*>(.+)</h[23]>,Uims", $texte, $match))
		AND $match = textebrut(trim($match[1]))
		AND ($p = strpos($texte,'<head>'))!==FALSE) {
		if (!$nom_site_spip = textebrut(typo($GLOBALS['meta']["nom_site"])))
			$nom_site_spip=  _T('info_mon_site_spip');

		$titre = "<title>["
			. $nom_site_spip
			. "] ". $match
		  ."</title>";

		$texte = substr_replace($texte, $titre, $p+6,0);
	}
	return $texte;
}


// Fonction standard pour le pipeline 'boite_infos'
// http://doc.spip.org/@f_boite_infos
function f_boite_infos($flux) {
	$args = $flux['args'];
	$type = $args['type'];
	unset($args['row']);
	$flux['data'] .= recuperer_fond("prive/objets/infos/$type",$args);
	return $flux;
}


/**
 * pipeline recuperer_fond
 * Branchement automatise de affiche_gauche, affiche_droite, affiche_milieu
 * pour assurer la compat avec les versions precedentes des exec en php
 * Branche de affiche_objet
 * 
 * Les pipelines ne recevront plus exactement le meme contenu en entree,
 * mais la compat multi vertions pourra etre assuree
 * par une insertion au bon endroit quand le contenu de depart n'est pas vide
 * 
 * @param array $flux
 */
function f_afficher_blocs_ecrire($flux) {
	if (is_string($fond=$flux['args']['fond'])) {
		$exec = _request('exec');
		if ($fond == "prive/squelettes/navigation/$exec"){
			$flux['data']['texte'] = pipeline('affiche_gauche',array('args'=>$flux['args']['contexte'],'data'=>$flux['data']['texte']));
		}
		if ($fond=="prive/squelettes/extra/$exec") {
			include_spip('inc/presentation_mini');
			$flux['data']['texte'] = pipeline('affiche_droite',array('args'=>$flux['args']['contexte'],'data'=>$flux['data']['texte'])).liste_articles_bloques();
		}
		if ($fond=="prive/squelettes/contenu/$exec"){
			if (!strpos($flux['data']['texte'],"<!--affiche_milieu-->"))
				$flux['data']['texte'] = preg_replace(',<div id=["\']wysiwyg,',"<!--affiche_milieu-->\\0",$flux['data']['texte']);
			$flux['data']['texte'] = pipeline('afficher_fiche_objet',array(
																					'args'=>array(
																						'contexte'=>$flux['args']['contexte'],
																						'type'=>$flux['args']['contexte']['exec'],
																						'id'=>$flux['args']['contexte'][id_table_objet($flux['args']['contexte']['exec'])]),
																					'data'=>$flux['data']['texte']));
			$flux['data']['texte'] = pipeline('affiche_milieu',array('args'=>$flux['args']['contexte'],'data'=>$flux['data']['texte']));
		}
		if (strncmp($fond,"prive/objets/contenu/",21)==0
		  AND $objet=basename($fond)
			AND $objet==substr($fond,21)){
			$flux['data']['texte'] = pipeline('afficher_contenu_objet',array('args'=>array('type'=>$objet,'id_objet'=>$flux['args']['contexte']['id'],'contexte'=>$flux['args']['contexte']),'data'=>$flux['data']['texte']));
		}
	}

	return $flux;
}
?>
