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
	global $connect_statut;

	$id_article = intval(_request('id_article'));
	$jour = _request('jour');
	$limit  = _request('limit');

	if (!autoriser('voirstats', $id_article ? 'article':'', $id_article)) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
	if ($id_article){
		$result = spip_query("SELECT titre, visites, popularite FROM spip_articles WHERE statut='publie' AND id_article ='$id_article'");

		if ($row = sql_fetch($result)) {
			$total_absolu = $row['visites'];
		}
	}  else {
		$result = spip_query("SELECT SUM(visites) AS total_absolu FROM spip_visites");

		if ($row = sql_fetch($result)) {
			$total_absolu = $row['total_absolu'];
		}
	}

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_statistiques_referers'), "statistiques_visites", "referers");
	echo "<br /><br /><br />";

	echo gros_titre(_T('titre_liens_entrants'),'', false);

//barre_onglets("statistiques", "referers");

echo debut_gauche('', true);
echo debut_boite_info(true);
echo "<p style='font-size:small; text-align:left;' class='verdana1'>"._T('info_gauche_statistiques_referers')."</p>";
echo fin_boite_info(true);

echo debut_droite('', true);


//
// Affichage des referers
//

// nombre de referers a afficher
$limit = intval($limit);	//secu
if ($limit == 0) $limit = 100;

if ($jour<>'veille')
	$jour='jour';

echo barre_onglets("stat_referers", $jour);


// afficher quels referers ?

 $str_date = (($jour=='jour')?"DATE_FORMAT(NOW(),'%Y-%m-%d')":"DATE_SUB(DATE_FORMAT(NOW(),'%Y-%m-%d'), INTERVAL 1 DAY)");

 $result = spip_query("SELECT J1.referer, visites_$jour AS vis, J2.id_article, J3.titre FROM spip_referers as J1 LEFT JOIN spip_referers_articles AS J2 ON J1.referer_md5 = J2.referer_md5 LEFT JOIN spip_articles AS J3 ON J2.id_article = J3.id_article WHERE visites_$jour>0 AND (J2.maj>=$str_date OR J2.maj IS NULL) ORDER BY vis DESC, id_article LIMIT $limit");


 echo "<br /><div style='font-size:small;' class='verdana1'>";
 echo aff_referers ($result, $limit, generer_url_ecrire('statistiques_referers', ("jour=$jour&limit=" . strval($limit+200))));

 echo "</div><br />";

 echo fin_gauche(), fin_page();
}

?>