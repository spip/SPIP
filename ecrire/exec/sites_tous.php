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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/sites_voir');

function exec_sites_tous_dist()
{
	global $connect_statut, $options;

	$ajouter_lien = intval(_request('ajouter_lien'));
	$supprimer_lien = intval(_request('supprimer_lien'));
	$supp_syndic = intval(_request('supp_syndic'));

	if ($connect_statut == '0minirezo') {
		if ($supp_syndic)
			spip_query("DELETE FROM spip_syndic WHERE id_syndic=$supp_syndic");

		// Moderation manuelle des liens
		if ($supprimer_lien)
			spip_query("UPDATE spip_syndic_articles SET statut='refuse' WHERE id_syndic_article=$supprimer_lien");
		if ($ajouter_lien)
			spip_query("UPDATE spip_syndic_articles SET statut='publie' WHERE id_syndic_article=$ajouter_lien");
	}

debut_page(_T('titre_page_sites_tous'),"documents","sites");
debut_gauche();
debut_droite();

afficher_sites(_T('titre_sites_tous'), "SELECT * FROM spip_syndic WHERE syndication='non' AND statut='publie' ORDER BY nom_site");

afficher_sites(_T('titre_sites_syndiques'), "SELECT * FROM spip_syndic WHERE (syndication='oui' OR syndication='sus') AND statut='publie' ORDER BY nom_site");

afficher_sites(_T('titre_sites_proposes'), "SELECT * FROM spip_syndic WHERE statut='prop' ORDER BY nom_site");

if ($connect_statut == '0minirezo' OR $GLOBALS['meta']["proposer_sites"] > 0) {
	echo "<div align='right'>";
	icone(_T('icone_referencer_nouveau_site'), generer_url_ecrire('sites_edit'), "site-24.gif", "creer.gif");
	echo "</div>";
}

afficher_sites(_T('avis_sites_probleme_syndication'), "SELECT * FROM spip_syndic WHERE syndication='off' AND statut='publie' ORDER BY nom_site");

if ($options == 'avancees' AND $connect_statut == '0minirezo') {
	afficher_sites(_T('info_sites_refuses'), "SELECT * FROM spip_syndic WHERE statut='refuse' ORDER BY nom_site");
}

afficher_syndic_articles(_T('titre_dernier_article_syndique'),
			 "SELECT * FROM spip_syndic_articles ORDER BY date DESC LIMIT 50",  'afficher site');

fin_page();
}

?>
