<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_DEBUG")) return;
define("_INC_DEBUG", "1");

//
// Fonctions debug
//

function affval($val) {

	echo htmlspecialchars($val);

}

function afftable($table) {

	if (!$table) return;
	reset($table);
	echo "<UL>";
	while (list($key, $val) = each($table)) {
		echo "<LI>";
		switch ($val->type) {
		case 'boucle':
			echo "<font color='red'><b>Boucle".$val->id_boucle."</b>: ".htmlspecialchars($val->commande);
			echo "<br><i><small>".htmlspecialchars($val->requete)."</small></i></font>";
			break;
		case 'texte':
			echo htmlspecialchars($val->texte);
			break;
		case 'champ':
			echo "<font color='blue'><i>#".$val->nom_champ;
			if ($val->fonctions) echo " <small>(".join(',', $val->fonctions).")</small>";
			echo "</i></font>";
			echo "<ul><li>";
			echo afftable($val->cond_avant);
			echo "</li><li>";
			echo afftable($val->cond_apres);
			echo "</li></ul>";
			break;
		}
		echo "</LI>";
	}
	echo "</UL>\n";
}

function affboucle($val) {
	echo "<hr>";
	echo "<b>Boucle".$val->id_boucle."</b>";
	echo "<ul><li>";
	echo afftable($val->avant);
	echo "</li><li>";
	echo afftable($val->cond_avant);
	echo "</li><li>";
	echo afftable($val->milieu);
	echo "</li><li>";
	echo afftable($val->cond_apres);
	echo "</li><li>";
	echo afftable($val->cond_altern);
	echo "</li><li>";
	echo affval($val->fin);
	echo "</li></ul>";
	echo "\n";
}

function affboucles() {
	global $boucles;
	reset($boucles);
	while (list($key, $val) = each($boucles)) affboucle($val);
}

afftable($GLOBALS['racine']);
affboucles();


?>