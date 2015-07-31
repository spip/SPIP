<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/



include ("inc.php3");
include_ecrire("inc_presentation.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_urls.php3");
include_ecrire("inc_rubriques.php3");

include_ecrire ("inc_sites.php3");

if ($connect_statut == '0minirezo' AND $supp_syndic) {
	$query="DELETE FROM spip_syndic WHERE id_syndic=".intval($supp_syndic);
	$result=spip_query($query);
}


debut_page(_T('titre_page_sites_tous'),"documents","sites");
debut_gauche();



debut_droite();



$proposer_sites=$GLOBALS['meta']["proposer_sites"];




afficher_sites(_T('titre_sites_tous'), "SELECT * FROM spip_syndic WHERE syndication='non' AND statut='publie' ORDER BY nom_site");


afficher_sites(_T('titre_sites_syndiques'), "SELECT * FROM spip_syndic WHERE (syndication='oui' OR syndication='sus') AND statut='publie' ORDER BY nom_site");


afficher_sites(_T('titre_sites_proposes'), "SELECT * FROM spip_syndic WHERE statut='prop' ORDER BY nom_site");

if ($connect_statut == '0minirezo' OR $proposer_sites > 0) {
	echo "<div align='right'>";
	$link = new Link('sites_edit.php3');
	$link->addVar('target', 'sites.php3');
	$link->addVar('redirect', $clean_link->getUrl());
	icone(_T('icone_referencer_nouveau_site'), $link->getUrl(), "site-24.gif", "creer.gif");
	echo "</div>";
}



afficher_sites(_T('avis_sites_probleme_syndication'), "SELECT * FROM spip_syndic WHERE syndication='off' AND statut='publie' ORDER BY nom_site");

if ($options == 'avancees' AND $connect_statut == '0minirezo') {
	afficher_sites(_T('info_sites_refuses'), "SELECT * FROM spip_syndic WHERE statut='refuse' ORDER BY nom_site");
}

afficher_syndic_articles(_T('titre_dernier_article_syndique'),
			 "SELECT * FROM spip_syndic_articles ORDER BY date DESC LIMIT 50",  'afficher site');

fin_page();

?>

