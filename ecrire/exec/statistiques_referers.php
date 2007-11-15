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

include_spip('inc/presentation');
include_spip('inc/statistiques');

// http://doc.spip.org/@exec_statistiques_referers_dist
function exec_statistiques_referers_dist()
{
	$jour = _request('jour');
	$limit  = _request('limit');
// nombre de referers a afficher
	$limit = intval($limit);	//secu
	if (!autoriser('voirstats','article')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

	if ($limit == 0) $limit = 100;
	if ($jour<>'veille') $jour='jour';

	$result = sql_select("referer_md5, referer, SUM(visites_$jour) AS vis", "spip_referers", "visites_$jour>0 ", "referer", "vis DESC", $limit);

	$res = "<br /><div style='font-size:small;' class='verdana1'>"
	. aff_referers ($result, $limit, generer_url_ecrire('statistiques_referers', ("jour=$jour&limit=" . strval($limit+200))))
	. "</div><br />";

	$commencer_page = charger_fonction('commencer_page', 'inc');

	echo $commencer_page(_T('titre_page_statistiques_referers'), "statistiques_visites", "referers");
	echo "<br /><br /><br />";

	echo gros_titre(_T('titre_liens_entrants'),'', false);
	echo debut_gauche('', true);
	echo debut_boite_info(true);

	echo "<p style='font-size:small; text-align:left;' class='verdana1'>"._T('info_gauche_statistiques_referers')."</p>";

	echo fin_boite_info(true);

	echo debut_droite('', true);

	echo barre_onglets("stat_referers", $jour);

	echo $res;

	echo fin_gauche(), fin_page();
	}
}

function barre_onglets_stat_referers() {

	$onglets = array();
	$onglets['jour']=
		  new Bouton(null, 'date_aujourdhui',
			generer_url_ecrire("statistiques_referers",""));
	$onglets['veille']=
		  new Bouton(null, 'date_hier',
			generer_url_ecrire("statistiques_referers","jour=veille"));
	return $onglets;
}

?>
