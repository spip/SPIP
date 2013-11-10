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


include_spip('public/forum');

/**
 * Un filtre applique a #PARAMETRES_FORUM, qui donne l'adresse de la page
 * de reponse
 *
 * @param $parametres
 * @return string
 */
function filtre_url_reponse_forum($parametres) {
	if (!$parametres) return '';
	return generer_url_public('forum', $parametres);
}

/**
 * Un filtre qui, etant donne un #PARAMETRES_FORUM, retourne un URL de suivi rss
 * dudit forum
 * Attention applique a un #PARAMETRES_FORUM complexe (id_article=x&id_forum=y)
 * ca retourne un url de suivi du thread y (que le thread existe ou non)
 *
 * @param $param
 * @return string
 */
function filtre_url_rss_forum($param) {
	if (!preg_match(',.*(id_(\w*?))=([0-9]+),S', $param, $regs)) return '';
	list(,$k,$t,$v) = $regs;
	if ($t == 'forum') $k = 'id_' . ($t = 'thread');
	return generer_url_public("rss_forum_$t", array($k => $v));
}

function interdit_html($texte){
	if (defined('_INTERDIRE_TEXTE_HTML'))
		$texte = str_replace("<","&lt;",$texte);
	return $texte;
}
?>