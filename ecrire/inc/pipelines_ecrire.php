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
		'javascript/ajaxCallback.js',
		'javascript/jquery.colors.js',
		'javascript/jquery.cookie.js'
	))) as $script)
		if ($script = find_in_path($script))
			$x .= "\n<script src=\"$script\" type=\"text/javascript\"></script>\n";
	$texte = $x.$texte;
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
	$flux['data'] .= recuperer_fond("prive/infos/$type",$args);
	return $flux;
}


/**
 * Branchement automatise de affiche_gauche, affiche_droite, affiche_milieu
 * pour assurer la compat avec les versions precedentes des exec en php
 *
 * Les pipelines ne recevront plus exactement le meme contenu en entree,
 * mais la compat multi vertions pourra etre assuree
 * par une insertion au bon endroit quand le contenu de depart n'est pas vide
 * 
 * @param array $flux
 */
function f_afficher_blocs_ecrire($flux) {
	if (is_string($fond=$flux['args']['fond'])) {
		if (strncmp($fond,"prive/squelettes/navigation/",28)==0)
			$flux['data']['texte'] = pipeline('affiche_gauche',array('args'=>$flux['args']['contexte'],'data'=>$flux['data']['texte']));
		if (strncmp($fond,"prive/squelettes/extra/",23)==0)
			$flux['data']['texte'] = pipeline('affiche_droite',array('args'=>$flux['args']['contexte'],'data'=>$flux['data']['texte']));
		if (strncmp($fond,"prive/squelettes/contenu/",25)==0)
			$flux['data']['texte'] = pipeline('affiche_milieu',array('args'=>$flux['args']['contexte'],'data'=>$flux['data']['texte']));
	}

	return $flux;
}
?>
