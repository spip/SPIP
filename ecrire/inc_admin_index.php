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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire("inc_presentation.php3");

function jauge($couleur,$pixels) {
	if ($pixels)
	  echo http_img_pack("jauge-$couleur.gif", $couleur, "height='10' width='$pixels'");
}

function admin_index_dist()
{
  global $connect_statut, $connect_toutes_rubriques, $couleur_claire, $forcer_indexation, $forcer_reindexation, $mise_a_jour, $purger;

debut_page(_L('Moteur de recherche'), "administration", "cache");


echo "<br><br><br>";
gros_titre(_L('Moteur de recherche'));


debut_gauche();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}

include_ecrire('inc_index.php3');


if ($forcer_indexation = intval($forcer_indexation))
	effectuer_une_indexation ($forcer_indexation);

if ($forcer_reindexation == 'oui')
	creer_liste_indexation();

if ($purger == 'oui') {
	purger_index();
	creer_liste_indexation();
}

echo "<a href='admin_index.php3?mise_a_jour=oui'>"._L('Cliquez ici pour mettre &agrave; jour les infos d\'indexation du site').'</a><br />';
echo "<a href='admin_index.php3?forcer_indexation=20'>"._L('Cliquez ici pour forcer l\'indexation du site').'</a><br />';
echo "<a href='admin_index.php3?forcer_reindexation=oui'>"._L('Cliquez ici pour relancer l\'indexation du site sans purger les donn&eacute;es.').'</a><br />';
echo "<a href='admin_index.php3?purger=oui'>"._L('Cliquez ici pour purger les tables d\'indexation.').'</a><br />';





// graphe des objets indexes
$types = array('article','auteur','breve','mot','rubrique','syndic','forum','signature','document');
while (list(,$type) = each($types)) {
	$table = 'spip_'.table_objet($type);
	$table_index = 'spip_index_'.table_objet($type);
	$critere = critere_indexation($type);

	// mise a jour des idx='' en fonction du contenu de la table d'indexation
	if ($mise_a_jour) {
		$vus='';
		$s = spip_query("SELECT DISTINCT(id_$type) FROM $table_index");
		while ($t = spip_fetch_array($s))
			$vus.=','.$t[0];
		if ($vus)
			spip_query("UPDATE $table SET idx='oui' WHERE id_$type IN (0$vus) AND $critere AND idx=''");
	}

	// 
	$s = spip_query("SELECT idx,COUNT(*) FROM $table WHERE $critere GROUP BY idx");
	while ($t = spip_fetch_array($s)) {
		$indexes[$type][$t[0]] = $t[1];
		$index_total[$type] += $t[1];
	}
}


debut_cadre_relief();


echo "<table>";
reset ($types);
while (list(,$type) = each($types)) if ($index_total[$type]>0) {
				if ($ifond==0){
					$ifond=1;
					$couleur="$couleur_claire";
				}else{
					$ifond=0;
					$couleur="#FFFFFF";
				}
	echo "<TR BGCOLOR='$couleur' BACKGROUND='" . _DIR_RESTREINT . "rien.gif'><TD WIDTH=\"100\">";
	echo "<FONT FACE='arial,helvetica,sans-serif' SIZE=2>";	
	echo $type;
	echo "</FONT><TD>";
	jauge('rouge', $a = floor(300*$indexes[$type]['non']/$index_total[$type]));
	jauge('vert', $b = ceil(300*$indexes[$type]['oui']/$index_total[$type]));
	jauge('fond', 300-$a-$b);
	echo "</TD></TR>\n";
}
echo "</table>";

fin_cadre_relief();


echo "<BR>";

fin_page();
}
?>
