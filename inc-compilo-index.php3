<?php


// cfonctions de recherche et de reservation
// dans l'arborescence des boucles

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_COMPILO_INDEX")) return;
define("_INC_COMPILO_INDEX", "1");

// index_pile retourne la position dans la pile du champ SQL $nom_champ 
// en prenant la boucle la plus proche du sommet de pile (indique par $idb).
// Si on ne trouve rien, on considere que ca doit provenir du contexte 
// (par l'URL ou l'include) qui a ete recopie dans Pile[0]
// (un essai d'affinage a debouche sur un bug vicieux)
// Si ca reference un champ SQL, on le memorise dans la structure $boucles
// afin de construire un requete SQL minimale (plutot qu'un brutal 'SELECT *')

function index_pile($idb, $nom_champ, &$boucles, $explicite='') {
  global $exceptions_des_tables, $table_des_tables, $tables_des_serveurs_sql;

	$i = 0;
	if (strlen($explicite)) {
	// Recherche d'un champ dans un etage superieur
	  while (($idb != $explicite) && ($idb !='')) {
#		spip_log("Cherchexpl: $nom_champ '$explicite' '$idb' '$i'");
			$i++;
			$idb = $boucles[$idb]->id_parent;
		}
	}

	$c = strtolower($nom_champ);
	// attention: entre la boucle nommee 0, "" et le tableau vide,
	// il y a incohérences qu'il vaut mieux éviter
	while ($boucles[$idb]) {
#		spip_log("Cherche: $nom_champ '$idb' '$c'");
		$r = $boucles[$idb]->type_requete;
		$s = $boucles[$idb]->sql_serveur;
		if (!$s) 
		  { $s = 'localhost';
    // indirection (pour les rares cas ou le nom de la table!=type)
		    $t = $table_des_tables[$r];
		  }
		if (!$t) $t = $r; // pour les tables non Spip
		// $t est le nom PHP de cette table 
#		spip_log("Go: idb='$idb' r='$r' c='$c' nom='$nom_champ' s=$s");
		$desc = $tables_des_serveurs_sql[$s][$t];
		if (!$desc) {
			include_local("inc-admin.php3");
			erreur_squelette(_L("Table SQL \"$r\" inconnue"), "'$idb'");
			# continuer pour chercher l'erreur suivante
			return  "'#" . $r . ':' . $nom_champ . "'";
		}
		$excep = $exceptions_des_tables[$r][$c];
		if ($excep) {
			// entite SPIP alias d'un champ SQL
			if (!is_array($excep)) {
				$e = $excep;
				$c = $excep;
			} 
			// entite SPIP alias d'un champ dans une autre table SQL
			else {
				$t = $excep[0];
				$e = $excep[1].' AS '.$c;
			}
		}
		else {
			// $e est le type SQL de l'entree
			// entite SPIP homonyme au champ SQL
			if ($desc['field'][$c])
				$e = $c;
			else
				unset($e);
		}

#		spip_log("Dans $idb ('$t' '$e'): $desc");

		// On l'a trouve
		if ($e) {
			$boucles[$idb]->select[] = $t . "." . $e;
			return '$Pile[$SP' . ($i ? "-$i" : "") . '][\'' . $c . '\']';
		}

		// Sinon on remonte d'un cran
		$idb = $boucles[$idb]->id_parent;
		$i++;
	}

#	spip_log("Pas vu $nom_champ dans les " . count($boucles) . " boucles");
	// esperons qu'il y sera
	return('$Pile[0][\''.$nom_champ.'\']');
}

// cette fonction sert d'API pour demander le champ '$champ' dans la pile
function champ_sql($champ, $p) {
	return index_pile($p->id_boucle, $champ, $p->boucles, $p->nom_boucle);
}

# calculer_champ genere le code PHP correspondant a la balise Spip $nom_champ
# Retourne une EXPRESSION php 
function calculer_champ($p) {

	$nom_champ = $p->nom_champ;

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
	$p->code = index_pile($p->id_boucle, $nom_champ, $p->boucles, $p->nom_boucle);
	if (($p->code) && ($p->code != '$Pile[0][\''.$nom_champ.'\']')) {

		// Par defaut basculer en numerique pour les #ID_xxx
		if (substr($nom_champ,0,3) == 'ID_') $p->statut = 'num';
	}

	else {
	// si index_pile a ramene le choix par defaut, 
	// ca doit plutot etre un champ SPIP non SQL,
	// ou ni l'un ni l'autre => on le renvoie sous la forme brute '#TOTO'
	$p->code = "'#" . $nom_champ . "'";
	$p->statut = 'php';	// pas de traitement
	
	}}}}

	// Retourner l'expression php correspondant au champ + ses filtres
	return applique_filtres($p);
}


// Genere l'application d'une liste de filtres
function applique_filtres($p) {
	$statut = $p->statut;
	$fonctions = $p->fonctions;
	$p->fonctions = ''; # pour réutiliser la structure si récursion

	// pretraitements standards
	switch ($statut) {
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

//  processeurs standards (cf inc-balises.php3)
	$code = ($p->etoile ? $p->code : champs_traitements($p));
	// Appliquer les filtres perso
	if ($fonctions) {
		foreach($fonctions as $fonc) {
			if ($fonc) {
				$arglist = '';
				if (ereg('([^\{\}]*)\{(.+)\}$', $fonc, $regs)) {
					$fonc = $regs[1];
				        $arglist = filtres_arglist($regs[2],$p);
				}
				if (!function_exists($fonc))
					$code = "spip_error_handler(1,'".
					  texte_script(
						_T('erreur_filtre', array('filtre' => $fonc))
					)."','','','')";
				else $code = "$fonc($code$arglist)";
			}
		}
	}

	// post-traitement securite
	if ($statut == 'html')
		$code = "interdire_scripts($code)";
	return $code;
}


function filtres_arglist($args, $p) {
	$arglist ='';;
	while (ereg('([^,]+),?(.*)$', $args, $regs)) {
		$arg = trim($regs[1]);
		if ($arg) {
			if ($arg[0] =='#') {
				$p->nom_champ = substr($arg,1);
				$arg = calculer_champ($p);
			} else if ($arg[0] =='$')
				$arg = '$Pile[0][\'' . substr($arg,1) . "']";
			$arglist .= ','.$arg;
		}
		$args=$regs[2];
	}
	return $arglist;
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
#	spip_log("boucles[$idb]->id_parent " . $boucles[$idb]->id_parent);
	return index_pile ($boucles[$idb]->id_parent, $nom_champ, $boucles);
}

?>
