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

include_ecrire ("inc_index");
include_ecrire ("inc_logos");
include_ecrire ("inc_presentation");

function index_tous_dist()
{
	global $connect_statut;
	global $INDEX_elements_objet;

	$liste_tables = array();
	$icone_table = array();

	$icone_spec=array('spip_forum'=>'forum-public-24.gif','spip_syndic'=>'site-24.gif','spip_documents'=>'doc-24.gif','spip_mots'=>'mot-cle-24.gif','spip_signatures'=>'suivi-petition-24.gif');

	$liste_tables = liste_index_tables();
	asort($liste_tables);

	foreach($liste_tables as $table){
		$typ = preg_replace("{^spip_}","",$table);
		if (substr($typ,-1,1)=='s')
		  $typ = substr($typ,0,strlen($typ)-1);
		$icone = "$typ-24.gif";
		if (isset($icone_spec[$table]))
			$icone = $icone_spec[$table];
		$icone_table[$table] = $icone;
 	}

	if (isset($_REQUEST['index_table'])) $index_table = $_REQUEST['index_table'];
	if (!isset($index_table)||(in_array($index_table,$liste_tables)==FALSE))
		$index_table='';

	if (isset($_REQUEST['filtre'])) $filtre = $_REQUEST['filtre'];
	if ( (!isset($filtre))||($filtre!=intval($filtre)) )
		$filtre = 10; // nombre d'occurences mini pour l'affichage des mots


	//
	// Recupere les donnees
	//

	debut_page(_L('Moteur de recherche'), "administration", "cache");

	debut_gauche();


	//////////////////////////////////////////////////////
	// Boite "voir en ligne"
	//

	debut_boite_info();

	echo propre(_L('Cette page récapitule la liste des mots indexes sur votre site et de leur occurence.'));

	fin_boite_info();

	debut_raccourcis();
	echo "<p>";
	icone_horizontale (_L('Statut de l\'indexation'), generer_url_ecrire("admin_index"), "tout-site-24.gif");
	echo "</p>";

	icone_horizontale (_L('Tout'), generer_url_ecrire("index_tous.php"), "tout-site-24.gif");

	$link = new Link();
	$link->addVar('filtre',$filtre);
	foreach($liste_tables as $t){
		if (isset($INDEX_elements_objet[$t])){
			$link->addVar('index_table',$t);
			icone_horizontale (_L('Index '.$t), $link->getUrl(), $icone_table[$t]);
		}
	}

	$link = new Link();
	if ($index_table!='')
		$link->addVar('index_table',$index_table);
	echo $link->getForm('get');

	echo _L('Filtrer :') . "<br /><select name='filtre'>" . "\n";
	$filtres=array('1'=>'+ de 1 point','10'=>'+ de 10 points','100'=>'+ de 100 points');
	foreach($filtres as $val=>$string){
		echo "<option value='$val'";
		if ($val == $filtre)
		  echo " selected='selected'";
		echo ">" . _L($string) ."</option>\n";
	}
	echo "</selected>";
	echo "<input type='submit' name='Valider' value='"._T('bouton_valider')."' class='fondo'></div>\n";

	fin_raccourcis();

	debut_droite();

	gros_titre(_L('Moteur de recherche'));

	if ($connect_statut != '0minirezo') {
		echo "<strong>"._T('avis_acces_interdit')."</strong>";
		fin_page();
		exit;
	}

	if ($index_table==''){
		$titre_table=_L("Tous les Mots Indexes");
		$icone = "doc-24.gif";
	}
	else {
		$titre_table=_L("Tous les Mots Indexes : table $index_table");
		$icone = $icone_table[$index_table];
	}


		// recupere les types
		$liste_tables = array_flip($liste_tables);
		$tableau = array();
		$classement = array();

		$vers = spip_fetch_array(spip_query("SELECT VERSION()"));
		if (substr($vers[0], 0, 1) >= 4
		AND substr($vers[0], 2, 1) >= 1 ) {
			$hex_fmt = '';
			$select_hash = 'dic.hash AS h';
		} else {
			$hex_fmt = '0x';
			$select_hash = 'HEX(dic.hash) AS h';
		}

		$clause_filtre = "HAVING total>=$filtre";
		$clause_order = "ORDER BY total DESC";

		if ($index_table=='')
			$requete = "SELECT dic.dico,$select_hash,COUNT(objet.points) AS occurences,SUM(objet.points) AS total FROM spip_index_dico AS dic, spip_index AS objet WHERE dic.hash=objet.hash GROUP BY dic.hash $clause_filtre";
		else{
			$id_table = $liste_tables[$index_table];
			$requete = "SELECT dic.dico,$select_hash,COUNT(objet.points) AS occurences,SUM(objet.points) AS total FROM spip_index_dico AS dic, spip_index AS objet WHERE dic.hash=objet.hash AND objet.id_table=$id_table GROUP BY dic.hash $clause_filtre";
	 	}

		$tranches = afficher_tranches_requete($requete, 3,false, false, 60);
		if (preg_match('{LIMIT}',$requete)==FALSE){
			// pas de limite ajoutee par afficher_tranche
			// mais il nous en faut une car on va remplacer le HAVING par le ORDER
			$res = spip_query($requete);
			$num = spip_num_rows($res);
			$requete .= " LIMIT 0,$num";
	 	}
		$requete = str_replace($clause_filtre,$clause_order,$requete);


		if ($tranches) {
			if ($titre_table) echo "<div style='height: 12px;'></div>";
			echo "<div class='liste'>";
			bandeau_titre_boite2($titre_table, $icone, $couleur_claire, "black");
			echo "<table width='100%' cellpadding='3' cellspacing='0' border='0'>";
			echo $tranches;

		 	$result = spip_query($requete);
			$num_rows = spip_num_rows($result);

			$ifond = 0;
			$premier = true;

			$compteur_liste = 0;
			$vals = '';
			while ($row = spip_fetch_array($result)) {
				$compteur_liste ++;

				$dico = $row['dico'];
				$hash = $hex_fmt.$row['h'];
				$points = $row['total'];
				$occurences = $row['occurences'];

				// le tableau

				// puce et titre
				$s = "";
				if ($occurences) {
					$puce = 'puce-verte-breve.gif';
				}
				else {
					$puce = 'puce-orange-breve.gif';
				}
				$vals[] = "<img src='img_pack/"
				  . $puce 
				  . "' width='7' height='7' style='border:0px;' />["
				  . $points
				  . "] <a href='" 
				  . generer_url_ecrire("recherche", "recherche=" . urlencode($dico))
				  .  "' title='"
				  . $occurences
				  . " occurences'>"
				  .  $dico
				  . "</a>&nbsp;";

				if (fmod($compteur_liste,3)==0){
					$tableau[] = $vals;
					$vals = '';
				}
			}
			spip_free_result($result);
			$largeurs = array('','','','','');
			$styles = array('arial11', 'arial1', 'arial1','arial1','arial1');
			afficher_liste($largeurs, $tableau, $styles);
			echo "</table>";
			echo "</div>\n";
		}
		else echo _L("Aucun mot indexe avec plus de $filtre points");

	fin_page();

}
?>
