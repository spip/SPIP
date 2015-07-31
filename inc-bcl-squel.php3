<?php

// encodage d'une boucle SPIP en un objet PHP

class Boucle {
	var $type = 'boucle';
	var $id_boucle, $id_parent;
	var $cond_avant, $milieu, $cond_apres, $cond_altern;
	var $lang_select;
	var $type_requete;
	var $param;
	var $separateur;
	var $doublons;
	var $partie, $total_parties,$mode_partie;
	var $externe = ''; # appel a partir d'une autre boucle (recursion)
	// champs pour la construction de la requete SQL
	var $tout = false;
	var $plat = false;
	var $select;
	var $from;
	var $where;
	var $limit;
	var $group = '';
	var $order = '';
	var $date = 'date' ;
	var $hash = false ;
	var $lien = false;
	var $sous_requete = false;
	var $compte_requete = 1;
	var $hierarchie = '';
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

//
// Structure de donnees pour parler aux fonctions calcul_champ_TOTO
//
class ParamChamp {
	var $fonctions;
	var $nom_champ;
	var $id_boucle;
	var $boucles;
	var $id_mere;
	var $code;

	function retour() {
		return applique_filtres($this->fonctions, $this->code, $this->id_boucle, $this->boucles, $this->id_mere);
	}
}

?>
