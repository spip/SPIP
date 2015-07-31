<?php

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
			include_local("inc-debug-squel.php3");
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

# calculer_champ genere le code PHP correspondant a la balise Spip $nom_champ
# Retourne un tableau dont le premier element est une EXPRESSION php 
# et le deuxieme une suite d'INSTRUCTIONS a executer AVANT de calculer
# l'expression (typiquement: un include ou une affectation d'auxiliaires)
# Ce tableau est egalement retourne par la fonction applique_filtres
# qui s'occupe de construire l'application 
# s'il existe une fonction nommee "calculer_champ_" suivi du nom du champ,
# on lui passe la main et elle est cense retourner le tableau ci-dessus
# (Essayer de renvoyer une suite vide, ca diminue les allocations a l'exec)

// cette fonction sert d'API pour demander le champ '$champ' dans la pile
function champ_sql($champ, $p) {
	return index_pile($p->id_boucle, $champ, $p->boucles);
}

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

	# $p->type_requete = $boucles[$id_boucle]->type_requete; # A AJOUTER

	// regarder s'il existe une fonction personnalisee balise_NOM()
	$f = 'balise_' . $nom_champ;
	if (function_exists($f) AND $p = $f($p))
		return $p->retour();

	// regarder s'il existe une fonction standard balise_NOM_dist()
	$f = 'balise_' . $nom_champ . '_dist';
	if (function_exists($f) AND $p = $f($p))
		return $p->retour();

	# A SUPPRIMER, cf. inc-form-squel.php3
	// regarder s'il existe une fonction old style calculer_champ_NOM()
	$f = 'calculer_champ_' . $nom_champ;
	if (function_exists($f)) {
		return $f($fonctions, $nom_champ, $id_boucle, $boucles, $id_mere);
	}

	// On regarde ensuite s'il y a un champ SQL homonyme,
	// et on definit le type et les traitements
	$code = index_pile($id_boucle, $nom_champ, $boucles);
	if (($code) && ($code != '$Pile[0][\''.$nom_champ.'\']')) {

		// Par defaut basculer en numerique pour les #ID_xxx
		if (substr($nom_champ,0,3) == 'ID_') $p->type = 'num';

		// Aller chercher les processeurs standards (cas des #TITRE, qui
		// ne necessitent pas une fonction balise_TITRE)
		if (!$etoile)
			$p->process = champs_traitements($nom_champ);

		return applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere, $p->type, $p->process);
	}

	// si index_pile a ramene le choix par defaut, 
	// ca doit plutot etre un champ SPIP non SQL,
	// ou ni l'un ni l'autre => on le renvoie sous la forme brute '#TOTO'
	$code = "'#$nom_champ'";
	$p->type = 'php';	// pas de traitement
	return applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere, $p->type);
}


// Genere l'application d'une liste de filtres
function applique_filtres ($fonctions, $code, $id_boucle, $boucles, $id_mere, $type ='html', $process='') {
	$milieu = '';

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
							if ($arg[0] =='#') {
								list($arg,$m) = calculer_champ(array(),substr($arg,1),$id_boucle, $boucles, $id_mere);
								$milieu .= $m;
							}
							else {
								if ($arg[0] =='$')
									$arg = '$Pile[0][\'' . substr($arg,1) . "']";
							}
							$arglist .= ','.$arg;
						}
					}
				}
				if (function_exists($fonc))
					$code = "$fonc($code$arglist)";
				else
					$code = "'"._T('erreur_filtre', array('filtre' => $fonc))."'";
			}
		}
	}

	// post-traitement securite
	if ($type == 'html')
		$code = "interdire_scripts($code)";

	return array($code, $milieu);
}

?>
