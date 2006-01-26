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

include_ecrire ("inc_index");
include_ecrire ("inc_presentation");

function jauge($couleur,$pixels) {
	if ($pixels)
	  echo http_img_pack("jauge-$couleur.gif", $couleur, "height='10' width='$pixels'");
}

function admin_index_dist()
{
  global $connect_statut, $connect_toutes_rubriques, $couleur_claire, $forcer_indexation, $forcer_reindexation, $mise_a_jour, $purger,$INDEX_elements_objet;

debut_page(_L('Moteur de recherche'), "administration", "cache");

debut_gauche();

debut_boite_info();
echo propre(_L('Cette page récapitule l\'avancement de l\'indexation du site.'));
fin_boite_info();

debut_raccourcis();
echo "<p>";
 icone_horizontale (_L('Voir le vocabulaire indexe'),  generer_url_ecrire("index_tous"), "statistiques-24.gif");
echo "</p>";

 icone_horizontale (_L('Mettre &agrave; jour les infos d\'indexation du site'), generer_url_ecrire("admin_index", "mise_a_jour=oui"), "cache-24.gif");
 icone_horizontale (_L('Forcer l\'indexation du site'), generer_url_ecrire("admin_index", "forcer_indexation=20"), "cache-24.gif");
 icone_horizontale (_L('Relancer l\'indexation du site sans purger les donn&eacute;es.'), generer_url_ecrire("admin_index", "forcer_indexation=oui"), "cache-24.gif");
echo "<div style='width: 100%; border-top: solid 1px white;background: url(img_pack/rayures-danger.png);'>";
 icone_horizontale (_L('Cliquez ici pour purger les tables d\'indexation.'), generer_url_ecrire("admin_index", "purger=oui"), "effacer-cache-24.gif");
echo "</div>";

fin_raccourcis();


debut_droite();
gros_titre(_L('Moteur de recherche'));

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}

include_ecrire('inc_index');


if ($forcer_indexation = intval($forcer_indexation))
	effectuer_une_indexation ($forcer_indexation);

if ($forcer_reindexation == 'oui')
	creer_liste_indexation();

if ($purger == 'oui') {
	purger_index();
	creer_liste_indexation();
}

$liste_tables = array();
$icone_type = array();
update_index_tables();
$liste_tables = liste_index_tables();
asort($liste_tables);

$icone_spec=array('spip_forum'=>'forum-public-24.gif','spip_syndic'=>'site-24.gif','spip_documents'=>'doc-24.gif','spip_mots'=>'mot-cle-24.gif','spip_signatures'=>'suivi-petition-24.gif');

foreach($liste_tables as $table){
	$typ = preg_replace("{^spip_}","",$table);
	if (substr($typ,-1,1)=='s')
	  $typ = substr($typ,0,strlen($typ)-1);
	$icone = "$typ-24.gif";
	if (isset($icone_spec[$table]))
		$icone = $icone_spec[$table];
	$icone_table[$table] = $icone;
}

// graphe des objets indexes
foreach($liste_tables as $table){
	$table_index = 'spip_index';
	$critere = critere_indexation($table);
	$id_table = id_index_table($table);
	$col_id = primary_index_table($table);

	// mise a jour des idx='' en fonction du contenu de la table d'indexation
	if ($mise_a_jour) {
		$vus='';
		$s = spip_query("SELECT DISTINCT(id_objet) FROM $table_index WHERE id_table=$id_table");
		while ($t = spip_fetch_array($s))
			$vus.=','.$t[0];
		if ($vus)
			spip_query("UPDATE $table SET idx='oui' WHERE $col_id IN (0$vus) AND $critere AND idx=''");
	}

	// 
	$s = spip_query("SELECT idx,COUNT(*) FROM $table WHERE $critere GROUP BY idx");
	while ($t = spip_fetch_array($s)) {
		$indexes[$table][$t[0]] = $t[1];
		$index_total[$table] += $t[1];
	}
}

debut_cadre_relief();

echo "<table>";
foreach($liste_tables as $table){
		if ($ifond==0){
			$ifond=1;
			$couleur="$couleur_claire";
		}else{
			$ifond=0;
			$couleur="#FFFFFF";
		}
		echo "<tr style='background-color:$couleur;'>";
		echo "<td style='width:100;'>";
		echo "<span style='font:arial,helvetica,sans-serif;font-size:small;'>";
		echo $table;
		echo "</span><td>";
		if (isset($INDEX_elements_objet[$table])){
			if ($index_total[$table]>0) {
				if ($index_total[$table]>0) {
					jauge('rouge', $a = floor(300*$indexes[$table]['non']/$index_total[$table]));
					jauge('vert', $b = ceil(300*$indexes[$table]['oui']/$index_total[$table]));
					jauge('fond', 300-$a-$b);
				}
			}
			else{
				echo _L("Aucun &eacute;l&eacute;ment &agrave; indexer");
			}
		}
		else{
			echo _L("Indexation de la table non configur&eacute;e");
		}
		echo "</td><td>";
		if ($index_total[$table]>0) {
			echo "<span style='font:arial,helvetica,sans-serif;font-size:small;'>";
			if (($n = $indexes[$table]['oui'])!='')
			  echo $n;
			else
			  echo '0';
			echo "/" . $index_total[$table];
			if (($n = $indexes[$table]['non'])!='')
				echo "[-" . $indexes[$table]['non'] . "]";
			echo "</span>";
		}
		echo "</td></tr>\n";
}
echo "</table>";

fin_cadre_relief();


echo "<BR>";

fin_page();
}
?>
