<?php

// Definition des classes Boucle, Texte, Inclure, etc.,
// et fonctions de recherche et de reservation
// dans l'arborescence des boucles

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_COMPILO_INDEX")) return;
define("_INC_COMPILO_INDEX", "1");

//
// encodage d'une boucle SPIP en un objet PHP
//
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
	var $type_requete;
	var $code;			// code du calcul
	var $process;		// processeurs standards, exemple 'propre(%s)'
	var $etoile;		// le champ a ete appele avec une etoile (booleen)
	var $type;			// 'num'erique, 'h'=texte (html) ou 'p'=script (php) ?
						// -> definira les pre et post-traitements obligatoires

	function retour() {
		// Annuler les traitements si le champ est etoile
		if ($this->etoile) unset($this->process);

		$code_filtre = applique_filtres(
			$this->fonctions,
			$this->code,
			$this->id_boucle,
			$this->boucles,
			$this->id_mere,
			$this->type,
			$this->process
		);
		return $code_filtre;
	}
}



// index_pile retourne la position dans la pile du champ SQL $nom_champ 
// en prenant la boucle la plus proche du sommet de pile (indique par $idb).
// Si on ne trouve rien, on considere que ca doit provenir du contexte 
// (par l'URL ou l'include) qui a ete recopie dans Pile[0]
// (un essai d'affinage a debouche sur un bug vicieux)
// Si ca reference un champ SQL, on le memorise dans la structure $boucles
// afin de construire un requete SQL minimale (plutot qu'un brutal 'SELECT *')

include_ecrire('inc_serialbase.php3');

function index_pile($idb, $nom_champ, &$boucles) {
	global $exceptions_des_tables, $table_des_tables, $tables_principales;

	// Recherche d'un champ dans un etage superieur
	$i = 0;
	if ($c=strpos($nom_champ, ':')) {
		$idbs = substr($nom_champ, 0, $c);
		$nom_champ = substr($nom_champ, $c+1);
		while (($idb != $idbs) && $idb) {
			$i++;
			$idb = $boucles[$idb]->id_parent;
		}
	}

	$c = strtolower($nom_champ);
	// attention a la boucle nommee 0 ....
	while ($idb!== '') {
		#spip_log("Cherche: $nom_champ '$idb' '$c'");
		$r = $boucles[$idb]->type_requete;
		// indirection (pour les rares cas ou le nom de la table est /= du type)
		$t = $table_des_tables[$r];
		if (!$t)
			$t = $r; // pour les tables non Spip
		// $t est le nom PHP de cette table 
		#spip_log("Go: idb='$idb' r='$r' c='$c' nom='$nom_champ'");
		$desc = $tables_principales[$t];
		if (!$desc) {
			include_local("inc-admin.php3");
			erreur_squelette(_L("Table SQL absente de \$tables_principales dans inc_serialbase"), $r, "'$idb'");
		}
		$excep = $exceptions_des_tables[$r][$c];
		if ($excep) {
			// entite SPIP alias d'un champ SQL
			if (!is_array($excep)) {
				$e = $excep;
			} 
			// entite SPIP alias d'un champ dans une autre table SQL 
			else {
				$t = $excep[0];
				$e = $excep[1];
			}
		}
		else {
			// $e est le type SQL de l'entree (ici utile comme booleen)
			// entite SPIP homonyme au champ SQL
			if ($desc['field'][$c])
				$e = $c;
			else
				$e = '';
		}

		#spip_log("Dans $idb ($t $e): $desc");

		// On l'a trouve
		if ($e) {
			$boucles[$idb]->select[] = $t . "." . $e;
			return '$Pile[$SP' . ($i ? "-$i" : "") . '][\'' . $e . '\']';
		}

		// Sinon on remonte d'un cran
		$idb = $boucles[$idb]->id_parent;
		$i++;
	}

	#spip_log("Pas vu $nom_champ dans les " . count($boucles) . " boucles");
	// esperons qu'il y sera
	return('$Pile[0][\''.$nom_champ.'\']');
}

// cette fonction sert d'API pour demander le champ '$champ' dans la pile
function champ_sql($champ, $p) {
	return index_pile($p->id_boucle, $champ, $p->boucles);
}

# calculer_champ genere le code PHP correspondant a la balise Spip $nom_champ
# Retourne une EXPRESSION php 
function calculer_champ($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere, $etoile = false) {
	// Preparer les parametres
	$p = new ParamChamp;
	$p->fonctions = $fonctions;
	$p->nom_champ = $nom_champ;
	$p->id_boucle = $id_boucle;
	$p->boucles = &$boucles;
	$p->id_mere = $id_mere;
	$p->type = 'html';
	$p->process = '';
	$p->type_requete = $boucles[$id_boucle]->type_requete;

	// regarder s'il existe une fonction personnalisee balise_NOM()
	$f = 'balise_' . $nom_champ;
	if (function_exists($f))
		$p = $f($p);

	else {
	// regarder s'il existe une fonction standard balise_NOM_dist()
	$f = 'balise_' . $nom_champ . '_dist';
	if (function_exists($f))
		$p = $f($p);

	else {
	// S'agit-il d'un logo ? Une fonction speciale les traite tous
	if (ereg('^LOGO_', $nom_champ))
		$p = calcul_balise_logo($p);

	else {
	// On regarde ensuite s'il y a un champ SQL homonyme,
	// et on definit le type et les traitements
	$p->code = champ_sql($nom_champ, $p);
	if (($p->code) && ($p->code != '$Pile[0][\''.$nom_champ.'\']')) {

		// Par defaut basculer en numerique pour les #ID_xxx
		if (substr($nom_champ,0,3) == 'ID_') $p->type = 'num';
	}

	else {
	// si index_pile a ramene le choix par defaut, 
	// ca doit plutot etre un champ SPIP non SQL,
	// ou ni l'un ni l'autre => on le renvoie sous la forme brute '#TOTO'
	$p->code = "'#$nom_champ'";
	$p->type = 'php';	// pas de traitement
	
	}}}}
	
	// Aller chercher les processeurs standards definis dans inc-balises.php3
	if (!$etoile)
		$p->process = champs_traitements($nom_champ);

	// Retourner l'expression php correspondant au champ + ses filtres
	return $p->retour();
}


// Genere l'application d'une liste de filtres
function applique_filtres ($fonctions, $code, $id_boucle, $boucles, $id_mere, $type ='html', $process='') {

	// pretraitements standards
	switch ($type) {
		case 'num':
			$code = "intval($code)";
			break;
		case 'php':
			break;
		case 'html':
		default:
			$code = "trim($code)";
			break;
	}

	// traitements standards
	if (strpos($process, '%s') !== false)
		$code = str_replace('%s', $code, $process);

	// Appliquer les filtres perso
	if ($fonctions) {
		foreach($fonctions as $fonc) {
			if ($fonc) {
				$arglist = '';
				if (ereg('([^\{\}]*)\{(.+)\}$', $fonc, $regs)) {
					$fonc = $regs[1];
					$args = $regs[2];
					while (ereg('([^,]+),?(.*)$', $args, $regs)) {
						$args = $regs[2];
						$arg = trim($regs[1]);
						if ($arg) {
							if ($arg[0] =='#')
								$arg = calculer_champ(array(), substr($arg,1),
									$id_boucle, $boucles, $id_mere);
							else if ($arg[0] =='$')
								$arg = '$Pile[0][\'' . substr($arg,1) . "']";
							$arglist .= ','.$arg;
						}
					}
				}
				if (function_exists($fonc))
					$code = "$fonc($code$arglist)";
				else
					$code = "'".texte_script(
						_T('erreur_filtre', array('filtre' => $fonc))
					)."'";
			}
		}
	}

	// post-traitement securite
	if ($type == 'html')
		$code = "interdire_scripts($code)";

	return $code;
}


//
// Reserve les champs necessaires a la comparaison avec le contexte donne par
// la boucle parente ; attention en recursif il faut les reserver chez soi-meme
// ET chez sa maman
// 
function calculer_argument_precedent($idb, $nom_champ, &$boucles) {

	// recursif ?
	if ($boucles[$idb]->externe)
		index_pile ($idb, $nom_champ, $boucles); // reserver chez soi-meme

	// reserver chez le parent et renvoyer l'habituel $Pile[$SP]['nom_champ']
	return index_pile ($boucles[$idb]->id_parent, $nom_champ, $boucles);
}

?>
