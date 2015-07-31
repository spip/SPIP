<?php

// encodage d'une boucle SPIP en un objet PHP

class Boucle {
	var $type = 'boucle';
	var $id_boucle, $id_parent;
	var $avant, $cond_avant, $milieu, $cond_apres, $cond_altern, $apres;
	var $commande;
	var $lang_select;
	var $type_requete;
	var $param;
	var $separateur;
	var $doublons;
	var $partie, $total_parties,$mode_partie;
	// champs pour la construction de la requete SQL
	var $tout = false;
	var $plat = false;
	var $select;
	var $from;
	var $where;
	var $limit;
	var $group = "''";
	var $order = "''";
	var $date = "date" ;
	var $hash = false ;
	var $lien = false;
	var $sous_requete = false;
	var $compte_requete = 1;
	// champs pour la construction du corps PHP
	var $return;
	var $numrows = false; 
}

class Texte {
	var $type = 'texte';
	var $texte;
}

class Inclure {
	var $type = 'include';
	var $fichier;
	var $params;
}

class Champ {
	var $type = 'champ';
	var $nom_champ;
	var $cond_avant, $cond_apres; // tableaux d'objets
	var $fonctions;
}
?>
