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



// fonctions de recherche et de reservation
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

#	spip_log("Cherche: $nom_champ a partir de '$idb'");
	$c = strtolower($nom_champ);
	// attention: entre la boucle nommee 0, "" et le tableau vide,
	// il y a incoherences qu'il vaut mieux eviter
	while ($boucles[$idb]) {
		$r = $boucles[$idb]->type_requete;
		$s = $boucles[$idb]->sql_serveur;
		if (!$s) 
		  { $s = 'localhost';
    // indirection (pour les rares cas ou le nom de la table!=type)
		    $t = $table_des_tables[$r];
		  }
		// pour les tables non Spip
		if (!$t) {$nom_table = $t = $r; }
		else $nom_table = 'spip_' . $t;

#		spip_log("Go: idb='$idb' r='$r' c='$c' nom='$nom_champ' s=$s t=$t");
		$desc = $tables_des_serveurs_sql[$s][$nom_table];
		if (!$desc) {
			erreur_squelette(_T('zbug_table_inconnue', array('table' => $r)),
				"'$idb'");
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

#		spip_log("Dans $idb ('$t' '$e' '$c'): $desc");

		// On l'a trouve
		if ($e) {
		  $t .= ".$e";
		  if (!in_array($t, $boucles[$idb]->select))
		    $boucles[$idb]->select[] = $t;
		  return '$Pile[$SP' . ($i ? "-$i" : "") . '][\'' . $c . '\']';
		}
#		spip_log("On remonte vers $i");
		// Sinon on remonte d'un cran
		$idb = $boucles[$idb]->id_parent;
		$i++;
	}

#	spip_log("Pas vu $nom_champ");
	// esperons qu'il y sera
	return('$Pile[0][\''. strtolower($nom_champ) . '\']');
}

// cette fonction sert d'API pour demander le champ '$champ' dans la pile
function champ_sql($champ, $p) {
	return index_pile($p->id_boucle, $champ, $p->boucles, $p->nom_boucle);
}

// cette fonction sert d'API pour demander une balise Spip avec filtres

function calculer_champ($p) {
	$p = calculer_balise($p->nom_champ, $p);
	return applique_filtres($p);
}

// cette fonction sert d'API pour demander une balise Spip sans filtres
function calculer_balise($nom, $p) {

	// regarder s'il existe une fonction personnalisee balise_NOM()
	$f = 'balise_' . $nom;
	if (function_exists($f))
		return $f($p);

	// regarder s'il existe une fonction standard balise_NOM_dist()
	$f = 'balise_' . $nom . '_dist';
	if (function_exists($f))
		return $f($p);

	// regarder s'il existe un fichier d'inclusion au nom de la balise
	// contenant une fonction balise_NOM_collecte
	$file = 'inc-' . strtolower($nom) . _EXTENSION_PHP;
	if ($file = find_in_path($file)) {
		include_local($file);
		# une globale ?? defined ou function_exists(..._dyn) serait mieux ?
		$f = $GLOBALS['balise_' . $nom . '_collecte'];
		if (is_array($f))
			return calculer_balise_dynamique($p, $nom, $f);
	}

	// S'agit-il d'un logo ? Une fonction speciale les traite tous
	if (ereg('^LOGO_', $nom))
		return calculer_balise_logo($p);

	// ca pourrait etre un champ SQL homonyme,
	$p->code = index_pile($p->id_boucle, $nom, $p->boucles, $p->nom_boucle);

	if (strpos($nom, 'ID_') === 0) $p->statut = 'num';

	// Compatibilite ascendante avec les couleurs html (#FEFEFE) :
	// SI le champ SQL n'est pas trouve
	// ET si la balise a une forme de couleur
	// ET s'il n'y a ni filtre ni etoile
	// ALORS retourner la couleur.
	// Ca permet si l'on veut vraiment de recuperer [(#ACCEDE*)]
	if (preg_match("/^[A-F]{1,6}$/i", $nom)
	AND !$p->etoile
	AND !$p->fonctions) {
		$p->code = "'#$nom'";
		$p->statut = 'php';
	}

	return $p;
}

//
// Traduction des balises dynamiques, notamment les "formulaire_*"
// Inclusion du fichier associe a son nom.
// Ca donne les arguments a chercher dans la pile,on compile leur localisation
// Ensuite on delegue a une fonction generale definie dans inc-calcul-outils
// qui recevra a l'execution la valeur des arguments, 
// ainsi que les pseudo filtres qui ne sont donc pas traites a la compil
// mais on traite le vrai parametre si present.

function calculer_balise_dynamique($p, $nom, $l) {
	balise_distante_interdite($p);
	$param = "";
	if ($a = $p->param) {
		$c = array_shift($a);
		if  (!array_shift($c)) {
		  $p->fonctions = $a;
		  array_shift( $p->param );
		  $param = compose_filtres_args($p, $c, ',');
		}
	}
	$collecte = join(',',collecter_balise_dynamique($l, $p));
	$p->code = "executer_balise_dynamique('" . $nom . "',\n\tarray("
	  . $collecte
	  . ($collecte ? $param : substr($param,1)) # virer la virgule
	  . "),\n\tarray("
	  . argumenter_balise($p->param, "', '")
	  . "), \$GLOBALS['spip_lang'],"
	  . $p->ligne
	  . ')';
	$p->statut = 'php';
	$p->fonctions = array();
	$p->param = array();

	// Cas particulier de #FORMULAIRE_FORUM : inserer l'invalideur
	if ($nom == 'FORMULAIRE_FORUM')
		$p->code = code_invalideur_forums($p, $p->code);

	return $p;
}

// construire un tableau des valeurs interessant un formulaire

function collecter_balise_dynamique($l, $p) {
	$args = array();
	foreach($l as $c) { $x = calculer_balise($c, $p); $args[] = $x->code;}
	return $args;
}

function applique_filtres($p) {

	// pretraitements standards (explication dans inc-compilo-index)
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
	if ($p->param) $code = compose_filtres($p, $code);
	// post-traitement securite
	if ($p->statut == 'html') $code = "interdire_scripts($code)";
	return $code;
}

function compose_filtres($p, $code)
{
  foreach($p->param as $filtre) {
    $fonc = array_shift($filtre);
    if ($fonc) {
      $arglist = compose_filtres_args($p, $filtre, ($fonc == '?' ? ':' : ','));
      if (function_exists($fonc))
	$code = "$fonc($code$arglist)";
      else if (strpos("x < > <= >= == === != !== <> ? ", " $fonc "))
	$code = "($code $fonc " . substr($arglist,1) . ')';
      else 
	$code = "erreur_squelette('"
	  . texte_script(_T('zbug_erreur_filtre', array('filtre' => $fonc)))
	  ."','" . $p->id_boucle . "')";
    }
  }
  return $code;
}

function compose_filtres_args($p, $args, $sep)
{
	$arglist = "";
	foreach ($args as $arg) {
		$arglist .= $sep . 
		  calculer_liste($arg, $p->descr, $p->boucles, $p->id_boucle);
	}
	return $arglist;
}

//
// Reserve les champs necessaires a la comparaison avec le contexte donne par
// la boucle parente ; attention en recursif il faut les reserver chez soi-meme
// ET chez sa maman
// 
function calculer_argument_precedent($idb, $nom_champ, &$boucles) {

	// si recursif, forcer l'extraction du champ SQL mais ignorer le code
	if ($boucles[$idb]->externe)
		index_pile ($idb, $nom_champ, $boucles); 
	// retourner $Pile[$SP] et pas $Pile[0] (bug recursion en 1ere boucle)
	$prec = $boucles[$idb]->id_parent;
	return (!$prec ? ('$Pile[$SP][\''.$nom_champ.'\']') : 
		index_pile($prec, $nom_champ, $boucles));
}

function rindex_pile($p, $champ, $motif) 
{
	$n = 0;
	$b = $p->id_boucle;
	$p->code = '';
	while ($b != '') {
	if ($s = $p->boucles[$b]->param) {
	  foreach($s as $v) {
		if (strpos($v[1][0]->texte,$motif) !== false) {
		  $p->code = '$Pile[$SP' . (($n==0) ? "" : "-$n") .
			"]['$champ']";
		  $b = '';
		  break;
		}
	  }
	}
	$n++;
	$b = $p->boucles[$b]->id_parent;
	}
	if (!$p->code) {
		erreur_squelette(_T('zbug_champ_hors_motif',
			array('champ' => '#' . strtoupper($champ),
				'motif' => $motif)
		), $p->id_boucle);
	}
	$p->statut = 'php';
	return $p;
}

?>
