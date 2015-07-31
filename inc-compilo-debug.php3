<?php

//
// Outils pour debugguer le compilateur (pas inclus)
//

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_COMPILO_DEBUG")) return;
define("_INC_COMPILO_DEBUG", "1");

//
// Fonctions debug
//

function affval($val) {

	echo "&ldquo;" . entites_html($val) . "&rdquo;";

}

function afftable($table) {

	if (!$table) return;
	reset($table);
	echo "<UL>";
	while (list($key, $val) = each($table)) {
		echo "<LI>";
		affobject($val);
		echo "</LI>";
	}
	echo "</UL>\n";
}


function affobject($val)
{
  if (!is_object($val))
    affval($val);
  else
    switch ($val->type) {
		case 'boucle':
			echo "<font color='red'><b>Boucle".$val->id_boucle."</b>";
			echo "<br><i><small>".affval($val->requete)."</small></i></font>";
			break;
		case 'texte':
			echo affval($val->texte);
			break;
		case 'include':
			echo affval($val->fichier);
			afftable($params);
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
}


function affboucle($val) {
	echo "<hr><ul>";
	foreach(get_object_vars($val) as $k => $v)
	  {
	    echo "<li><b>$k : </b>";
	    if (is_array($v)) 
	      if (!$v) echo "<i>Tableau vide</i>"; else afftable($v); 
	    elseif (is_object($v))
	      echo afftable($v);
	    else affval($v);
	    echo  "</li>"; }
	echo "</ul>\n";
}

function affboucles($boucles) {
  while (list($key, $val) = each($boucles)) affboucle($val);
}

?>
