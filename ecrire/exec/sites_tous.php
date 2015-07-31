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

include_spip('inc/presentation');

// http://doc.spip.org/@exec_sites_tous_dist
function exec_sites_tous_dist() {
	global $spip_lang_right;

	pipeline('exec_init',array('args'=>array('exec'=>'sites_tous'),'data'=>''));
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_sites_tous'),"naviguer","sites");
	echo debut_gauche('', true);
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'sites_tous'),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'sites_tous'),'data'=>''));
	echo debut_droite('', true);

	$lister_objets = charger_fonction('lister_objets','inc');
	echo $lister_objets('sites',array('titre'=>_T('titre_sites_tous'),'syndication'=>'non','statut'=>'publie','par'=>"nom_site"));

	echo $lister_objets('sites',array('titre'=>_T('titre_sites_syndiques'),'syndication'=>array('oui','sus'),'statut'=>'publie','par'=>"nom_site"));

	echo $lister_objets('sites',array('titre'=>_T('titre_sites_proposes'),'statut'=>'prop','par'=>"nom_site"));

	if (autoriser('bouton','site_creer')) {
		echo
			icone_inline(_T('icone_referencer_nouveau_site'), generer_url_ecrire('sites_edit'), "site-24.png", "new", $spip_lang_right),
			"<br class='nettoyeur' />";
	}

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'sites_tous'),'data'=>''));


	echo $lister_objets('sites',array('titre'=>_T('avis_sites_probleme_syndication'),'syndication'=>'off','statut'=>'publie','par'=>"nom_site"));

	if ($GLOBALS['visiteur_session']['statut'] == '0minirezo') {
		echo $lister_objets('sites',array('titre'=>_T('info_sites_refuses'),'statut'=>'refuse','par'=>"nom_site"));
	}

	echo $lister_objets('syndic_articles',array('titre'=>_T('titre_dernier_article_syndique'),'par'=>'date'));

	echo fin_gauche(), fin_page();
}

?>
