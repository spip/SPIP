<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser
if (!function_exists('generer_url_article')) { // si la place n'est pas prise


####### modifications possibles dans ecrire/mes_options
# on peut indiquer '.html' pour faire joli
define ('_terminaison_urls_page', '');
# ici, ce qu'on veut ou presque (de preference pas de '/')
define ('_separateur_urls_page', '');
# on peut indiquer '' si on a installe le .htaccess
define ('_debut_urls_page', get_spip_script('./').'?');
#######


function composer_url_page($page,$id) {
	return _debut_urls_page . $page . _separateur_urls_page
	. $id . _terminaison_urls_page;
}

function generer_url_article($id_article) {
	return composer_url_page('article', $id_article);
}

function generer_url_rubrique($id_rubrique) {
	return composer_url_page('rubrique', $id_rubrique);
}

function generer_url_breve($id_breve) {
	return composer_url_page('breve', $id_breve);
}

function generer_url_mot($id_mot) {
	return composer_url_page('mot', $id_mot);
}

function generer_url_site($id_syndic) {
	return composer_url_page('site', $id_syndic);
}

function generer_url_auteur($id_auteur) {
	return composer_url_page('auteur', $id_auteur);
}

function generer_url_document($id_document) {
	if (intval($id_document) <= 0)
		return '';
	if (($GLOBALS['meta']["creer_htaccess"]) == 'oui')
		return generer_url_action('autoriser',"arg=$id_document");
	if ($row = @spip_fetch_array(spip_query("SELECT fichier FROM spip_documents WHERE id_document = $id_document")))
		return ($row['fichier']);
	return '';
}

function recuperer_parametres_url(&$fond, $url) {
	global $contexte;

	// Ce bloc gere les urls page et la compatibilite avec les "urls standard"
	if (preg_match(
	',.*([?]|/)(article|rubrique|breve|mot|site|auteur)(\.php3?)?.*?([0-9]+),',
	$url, $regs)) {
		$fond = $regs[2];
		if ($regs[2] == 'site')
			$contexte['id_syndic'] = $regs[4];
		else
			$contexte['id_'.$fond] = $regs[4];

		return;
	}

	/*
	 * Le bloc qui suit sert a faciliter les transitions depuis
	 * le mode 'urls-propres' vers les modes 'urls-standard/page' et 'url-html'
	 * Il est inutile de le recopier si vous personnalisez vos URLs
	 * et votre .htaccess
	 */
	// Si on est revenu en mode page, mais c'est une ancienne url_propre
	// on ne redirige pas, on assume le nouveau contexte (si possible)
	if ($url_propre = $GLOBALS['_SERVER']['REDIRECT_url_propre']
	OR $url_propre = $GLOBALS['HTTP_ENV_VARS']['url_propre']
	AND preg_match(',^(article|breve|rubrique|mot|auteur|site)$,', $fond)) {
		$url_propre = preg_replace('/^[_+-]{0,2}(.*?)[_+-]{0,2}(\.html)?$/',
			'$1', $url_propre);
		if ($r = spip_query("SELECT ".id_table_objet($fond)." AS id
		FROM spip_".table_objet($fond)."
		WHERE url_propre = '".addslashes($url_propre)."'")
		AND $t = spip_fetch_array($r))
			$contexte[id_table_objet($fond)] = $t['id'];
	}
	/* Fin du bloc compatibilite url-propres */

	return;
}

//
// URLs des forums
//

function generer_url_forum($id_forum, $show_thread=false) {
	include_spip('inc/forum');
	return generer_url_forum_dist($id_forum, $show_thread);
}
 }
?>
