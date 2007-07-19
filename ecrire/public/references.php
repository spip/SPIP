<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/



// fonctions de recherche et de reservation
// dans l'arborescence des boucles

if (!defined("_ECRIRE_INC_VERSION")) return;

// index_pile retourne la position dans la pile du champ SQL $nom_champ 
// en prenant la boucle la plus proche du sommet de pile (indique par $idb).
// Si on ne trouve rien, on considere que ca doit provenir du contexte 
// (par l'URL ou l'include) qui a ete recopie dans Pile[0]
// (un essai d'affinage a debouche sur un bug vicieux)
// Si ca reference un champ SQL, on le memorise dans la structure $boucles
// afin de construire un requete SQL minimale (plutot qu'un brutal 'SELECT *')

// http://doc.spip.org/@index_pile
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
	$nom_champ = strtolower($nom_champ);
	// attention: entre la boucle nommee 0, "" et le tableau vide,
	// il y a incoherences qu'il vaut mieux eviter
	while (isset($boucles[$idb])) {
		list ($t, $c) = index_tables_en_pile($idb, $nom_champ, $boucles);
		if ($t) {
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
	return('@$Pile[0][\''. strtolower($nom_champ) . '\']');
}

/**
 * retourne la description de la table associee a un type de boucle
 * retourne un tableau avec les entrees field et key (comme dans serial.php)
 * et type = type de boucle, serveur = serveur bdd associe et table = nom de
 * la table concernee
 * retourne null si on ne trouve pas la table
 */
// http://doc.spip.org/@description_type_requete
function description_type_requete($type, $serveur='') {
	global $table_des_tables, $tables_des_serveurs_sql, $tables_auxiliaires;

	if (!$serveur) {
		$s = 'localhost';
	} else $s = $serveur;
	// pour les tables non Spip
	if (isset($table_des_tables[$type])) {
    	// indirection (pour les rares cas ou le nom de la table!=type)
		$t = $table_des_tables[$type];
		$nom_table = 'spip_' . $t;
	} elseif (isset($tables_auxiliaires['spip_' .$type])) {
		$t = $type;
		$nom_table = 'spip_' . $t;
	} else	$nom_table = $t = $type;

	$desc = $tables_des_serveurs_sql[$s][$nom_table];
	if (!isset($desc['field'])) {
		$desc = ($nom_table != $type) ?
			(($GLOBALS['table_prefix'] ? $GLOBALS['table_prefix'] : 'spip')
				. '_' . $t) : $nom_table;

		$desc = spip_abstract_showtable($desc, $serveur);
		if (!isset($desc['field']))
			return null;
		$tables_des_serveurs_sql[$s][$nom_table]= $desc;
	}
	$desc['serveur']= $s;
	$desc['type']= $t;
	$desc['table']= $nom_table;

	return $desc;
}

// http://doc.spip.org/@index_tables_en_pile
function index_tables_en_pile($idb, $nom_champ, &$boucles) {
	global $exceptions_des_tables;

	$r = $boucles[$idb]->type_requete;
	$s = $boucles[$idb]->sql_serveur;

	if ($r == 'boucle') return array();
	$desc= description_type_requete($r, $s);

	if(!$desc) {
		erreur_squelette(_T('zbug_table_inconnue', array('table' => $r)),
				   "'$idb'");
		# continuer pour chercher l'erreur suivante
		return  array("'#" . $r . ':' . $nom_champ . "'",'');
	}

	$t= $desc['type'];
	$excep = isset($exceptions_des_tables[$r]) ? $exceptions_des_tables[$r] : '';
	if ($excep)
		$excep = isset($excep[$nom_champ]) ? $excep[$nom_champ] : '';

	if ($excep) {
	  return index_exception($boucles[$idb], $desc, $nom_champ, $excep);
	} else {
		if (isset($desc['field'][$nom_champ]))
			return array("$t.$nom_champ", $nom_champ);
		else {
		  if ($boucles[$idb]->jointures_explicites) {
		    $t = trouver_champ_exterieur($nom_champ, 
						 $boucles[$idb]->jointures,
						 $boucles[$idb]);
		    if ($t) 
			return index_exception($boucles[$idb], 
					       $desc,
					       $nom_champ,
					       array($t[0], $nom_champ));
		  }
		  return array('','');
		}
	}
}

// Reference a une entite SPIP alias d'un champ SQL
// Ca peut meme etre d'un champ dans une jointure
// qu'il faut provoquer si ce n'est fait

// http://doc.spip.org/@index_exception
function index_exception(&$boucle, $desc, $nom_champ, $excep)
{
	global $tables_des_serveurs_sql;

	if (is_array($excep)) {
		// permettre aux plugins de gerer eux meme des jointures derogatoire ingerables
		$t = NULL;
		if (count($excep)==3){
			$index_exception_derogatoire = array_pop($excep);
			$t = $index_exception_derogatoire($boucle, $desc, $nom_champ, $excep);
		}
		if ($t == NULL) {
			list($e, $x) = $excep;	#PHP4 affecte de gauche a droite
			$excep = $x;		#PHP5 de droite a gauche !
			if (!$t = array_search($e, $boucle->from)) {
				$t = 'J' . count($boucle->from);
				$boucle->from[$t] = $e;
				$j = $tables_des_serveurs_sql[$desc['serveur']][$e];
	# essayer ca un jour: 	list($nom, $j) = trouver_def_table($e, $boucle);
				$j = $j['key']['PRIMARY KEY'];
				$boucle->where[]= array("'='", "'$boucle->id_table." . "$j'", "'$t.$j'");
				}
		}
	} 
	else $t = $desc['type'];
	// demander a SQL de gerer le synonyme
	// ca permet que excep soit dynamique (Cedric, 2/3/06)
	if ($excep != $nom_champ) $excep .= ' AS '. $nom_champ;
	return array("$t.$excep", $nom_champ);
}


// cette fonction sert d'API pour demander le champ '$champ' dans la pile
// http://doc.spip.org/@champ_sql
function champ_sql($champ, $p) {
	return index_pile($p->id_boucle, $champ, $p->boucles, $p->nom_boucle);
}

// cette fonction sert d'API pour demander une balise Spip avec filtres

// http://doc.spip.org/@calculer_champ
function calculer_champ($p) {
	$p = calculer_balise($p->nom_champ, $p);
	return applique_filtres($p);
}

// Cette fonction sert d'API pour demander une balise SPIP sans filtres.
// Pour une balise nommmee NOM, elle demande a charger_fonction de chercher
// s'il existe une fonction balise_NOM ou balise_NOM_dist
// eventuellement en chargeant le fichier balise/NOM.php.
// Si ce n'est pas le cas, hormis le cas historique des balise LOGO_*,
// elle estime que c'est une reference a une colonne de table connue.
// Les surcharges via charger_fonction sont donc possibles.

// http://doc.spip.org/@calculer_balise
function calculer_balise($nom, $p) {

	// S'agit-t-il d'une balise_XXXX[_dist]() ?
	if ($f = charger_fonction($nom, 'balise', true)) {
		$res = $f($p);
		if ($res !== NULL)
			return $res;
	}

	// S'agit-il d'un logo ? Une fonction speciale les traite tous
	if (strncmp('LOGO_', $nom,5)==0) {
		if (!function_exists($f = 'calculer_balise_logo')) $f .= '_dist';
		$res = $f($p);
		if ($res !== NULL)
			return $res;
	}

	// ca pourrait etre un champ SQL homonyme,
	$p->code = index_pile($p->id_boucle, $nom, $p->boucles, $p->nom_boucle);

	// compatibilite: depuis qu'on accepte #BALISE{ses_args} sans [(...)] autour
	// il faut recracher {...} quand ce n'est finalement pas des args
	if ($p->fonctions AND (!$p->fonctions[0][0]) AND $p->fonctions[0][1]) {
		$code = addslashes($p->fonctions[0][1]);
		$p->code .= " . '$code'";
	}

	// ne pas passer le filtre securite sur les id_xxx
	if (strpos($nom, 'ID_') === 0)
		$p->interdire_scripts = false;

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
		$p->interdire_scripts = false;
	}

	return $p;
}

/*

L'appel direct de #ARTICLE_TRADUCTIONS devient #MODELE{article_traductions}

// fonction speciale d'appel a un modele modeles/truc.html pour la balise #TRUC
// exemples : #TRADUCTIONS, #DOC, #IMG...
// http://doc.spip.org/@calculer_balise_modele_dist
function calculer_balise_modele_dist($p){
	$nom = strtolower($p->nom_champ);
	$contexte = array();

	if (isset($p->param[0])){
		while (count($p->param[0])>2){
			$p->param[]=array($p->param[0][0],array_pop($p->param[0]));
		}
	}
print_r($p->param);
	$champ = phraser_arguments_inclure($p, true); 
	// a priori true
	// si false, le compilo va bloquer sur des syntaxes avec un filtre sans argument qui suit la balise
	// si true, les arguments simples (sans truc=chose) vont degager
	$code_contexte = argumenter_inclure($champ, $p->descr, $p->boucles, $p->id_boucle, false);

	// Si le champ existe dans la pile, on le met dans le contexte
	// (a priori c'est du code mort ; il servait pour #LESAUTEURS dans
	// le cas spip_syndic_articles)
	#$code_contexte[] = "'$nom='.".champ_sql($nom, $p);

	// Reserver la cle primaire de la boucle courante
	if ($primary = $p->boucles[$p->id_boucle]->primary) {
		$id = champ_sql($primary, $p);
		$code_contexte[] = "'$primary='.".$id;
	}

#print_r($code_contexte);

	$p->code = "( ((\$recurs=(isset(\$Pile[0]['recurs'])?\$Pile[0]['recurs']:0))<5)?
	recuperer_fond('modeles/".$nom."',
		creer_contexte_de_modele(array(".join(',', $code_contexte).",'recurs='.++\$recurs, \$GLOBALS['spip_lang']))):'')";
	$p->interdire_scripts = false; // securite assuree par le squelette

print $p->code."\n<hr/>\n";

	return $p;
}
*/

//
// Traduction des balises dynamiques, notamment les "formulaire_*"
// Inclusion du fichier associe a son nom.
// Ca donne les arguments a chercher dans la pile,on compile leur localisation
// Ensuite on delegue a une fonction generale definie dans executer_squelette
// qui recevra a l'execution la valeur des arguments, 
// ainsi que les pseudo filtres qui ne sont donc pas traites a la compil
// mais on traite le vrai parametre si present.

// http://doc.spip.org/@calculer_balise_dynamique
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
	$collecte = join(',',collecter_balise_dynamique($l, $p, $nom));
	$p->code = "executer_balise_dynamique('" . $nom . "',\n\tarray("
	  . $collecte
	  . ($collecte ? $param : substr($param,1)) # virer la virgule
	  . "),\n\tarray("
	  . argumenter_balise($p->param, "', '")
	  . "), \$GLOBALS['spip_lang'],"
	  . $p->ligne
	  . ')';
	$p->interdire_scripts = false;
	$p->fonctions = array();
	$p->param = array();

	return $p;
}

// Construction du tableau des arguments d'une balise dynamique.
// Ces arguments peuvent etre eux-meme des balises (cf FORMULAIRE_SIGNATURE)
// mais gare au bouclage (on peut s'aider de $nom pour le reperer au besoin)

// http://doc.spip.org/@collecter_balise_dynamique
function collecter_balise_dynamique($l, &$p, $nom) {
	$args = array();
	foreach($l as $c) { $x = calculer_balise($c, $p); $args[] = $x->code;}
	return $args;
}


// il faudrait savoir traiter les formulaires en local
// tout en appelant le serveur SQL distant.
// En attendant, cette fonction permet de refuser une authentification
// sur qqch qui n'a rien a voir.

// http://doc.spip.org/@balise_distante_interdite
function balise_distante_interdite($p) {
	$nom = $p->id_boucle;
	if ($nom AND $p->boucles[$nom]->sql_serveur) {
		erreur_squelette($p->nom_champ .' '._T('zbug_distant_interdit'), $nom);
	}
}


//
// Traitements standard de divers champs
// definis par $table_des_traitements, cf. inc-compilo-api.php3
//
// http://doc.spip.org/@champs_traitements
function champs_traitements ($p) {
	global $table_des_traitements;

	if (!isset($table_des_traitements[$p->nom_champ]))
		return $p->code;
	$ps = $table_des_traitements[$p->nom_champ];
	if (is_array($ps)) {
	  // new style

		if ($p->nom_boucle)
			$type = $p->boucles[$p->nom_boucle]->type_requete;
		else
			$type = $p->type_requete;
		$ps = $ps[isset($ps[$type]) ? $type : 0];
	}

	if (!$ps) return $p->code;

	// Si une boucle sous-jacente (?) traite les documents, on insere ici
	// une fonction de remplissage du tableau des doublons -- mais seulement
	// si on rencontre le filtre propre (qui traite les
	// raccourcis <docXX> qui nous interessent)
	if (isset($p->descr['documents'])
	AND preg_match(',propre,', $ps))
		$ps = 'traiter_doublons_documents($doublons, '.$ps.')';

	// De meme, en cas de sql_serveur, on supprime les < IMGnnn > tant
	// qu'on ne rapatrie pas les documents distants joints..
	// il faudrait aussi corriger les raccourcis d'URL locales
	if ($p->id_boucle  AND $p->boucles[$p->id_boucle]->sql_serveur)
		$p->code = 'supprime_img(' . $p->code . ')';


	// Passer |safehtml sur les boucles "sensibles"
	// sauf sur les champs dont on est surs
	switch ($p->type_requete) {
		case 'forums':
		case 'signatures':
		case 'syndic_articles':
			$champs_surs = array(
			'date', 'date_heure', 'statut', 'ip', 'url_article', 'maj', 'idx',
			'parametres_forum');
			if (!in_array(strtolower($p->nom_champ), $champs_surs)
			AND !preg_match(',^ID_,', $p->nom_champ))
				$ps = 'safehtml('.$ps.')';
			break;
		default:
			break;
	}

	// Remplacer enfin le placeholder %s par le vrai code de la balise
	return str_replace('%s', $p->code, $ps);
}


//
// Appliquer les filtres a un champ [(#CHAMP|filtre1|filtre2)]
// retourne un code php compile exprimant ce champ filtre et securise
//  - une etoile => pas de processeurs standards
//  - deux etoiles => pas de securite non plus !
//
// http://doc.spip.org/@applique_filtres
function applique_filtres($p) {

	// Traitements standards (cf. supra)
	if ($p->etoile == '')
		$code = champs_traitements($p);
	else
		$code = $p->code;

	// Appliquer les filtres perso
	if ($p->param)
		$code = compose_filtres($p, $code);

	// ramasser les images intermediaires inutiles et graver l'image finale
	if ($p->ramasser_miettes)
		$code = "filtrer('image_graver',$code)";

	// Securite
	if ($p->interdire_scripts
	AND $p->etoile != '**')
		$code = "interdire_scripts($code)";

	return $code;
}

function chercher_filtre($fonc) {
		foreach (
		array('filtre_'.$fonc, 'filtre_'.$fonc.'_dist', $fonc) as $f)
			if (function_exists($f)
			OR (preg_match("/^(\w*)::(\w*)$/", $f, $regs)                            
				AND is_callable(array($regs[1], $regs[2]))
			)) {
				return $f;
			}
		return NULL;
}
// Cf. function pipeline dans ecrire/inc_utils.php
// http://doc.spip.org/@compose_filtres
function compose_filtres(&$p, $code) {
	foreach($p->param as $filtre) {
		$fonc = array_shift($filtre);
		if ($fonc) {
			$is_filtre_image = (substr($fonc,0,6)=='image_') AND ($fonc!='image_graver');
			if ($p->ramasser_miettes AND !$is_filtre_image){
				// il faut graver maintenant car apres le filtre en cours
				// on est pas sur d'avoir encore le nom du fichier dans le pipe
				$code = "filtrer('image_graver',$code)";
				$p->ramasser_miettes = false;
			}
			// recuperer les arguments du filtre, les separer par des virgules
			// *sauf* dans le cas du filtre "?" qui demande un ":"
			if ($fonc == '?') {
				// |?{a,b} *doit* avoir exactement 2 arguments ; on les force
				if (count($filtre) != 2)
					$filtre = array($filtre[0], $filtre[1]);
				$arglist = compose_filtres_args($p, $filtre, ':');
			} else
				$arglist = compose_filtres_args($p, $filtre, ',');

			// le filtre est defini dans la matrice ? il faut alors l'appeler
			// de maniere indirecte, pour charger au prealable sa definition
			if (isset($GLOBALS['spip_matrice'][$fonc])) {
				$code = "filtrer('$fonc',$code$arglist)";
				if ($is_filtre_image) $p->ramasser_miettes = true;
			}
			// est-ce un test ?
			else if (strpos("x < > <= >= == === != !== <> ? ", " $fonc "))
				$code = "($code $fonc " . substr($arglist,1) . ')';
			// le filtre est defini sous forme de fonction ou de methode
			// par ex. dans inc_texte, inc_filtres ou mes_fonctions
			else {
				if($f = chercher_filtre($fonc))
					$code = "$f($code$arglist)";
			}

			if (!isset($code))
				$code = "erreur_squelette('"
				.texte_script(_T('zbug_erreur_filtre', array('filtre'=>$fonc)))
				."','" . $p->id_boucle . "')";
		}
	}
	return $code;
}

// http://doc.spip.org/@compose_filtres_args
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
// http://doc.spip.org/@calculer_argument_precedent
function calculer_argument_precedent($idb, $nom_champ, &$boucles) {

	// si recursif, forcer l'extraction du champ SQL mais ignorer le code
	if ($boucles[$idb]->externe)
		index_pile ($idb, $nom_champ, $boucles); 
	// retourner $Pile[$SP] et pas $Pile[0] (bug recursion en 1ere boucle)
	$prec = $boucles[$idb]->id_parent;
	return (($prec==="") ? ('$Pile[$SP][\''.$nom_champ.'\']') : 
		index_pile($prec, $nom_champ, $boucles));
}

//
// Rechercher dans la pile des boucles actives celle ayant un critere
// comportant un certain $motif, et construire alors une reference
// a l'environnement de cette boucle, qu'on indexe avec $champ.
// Sert a referencer une cellule non declaree dans la table et pourtant la.
// Par exemple pour la balise #POINTS on produit $Pile[$SP-n]['points']
// si la n-ieme boucle a un critere "recherche", car on sait qu'il a produit
// "SELECT XXXX AS points"
//

// http://doc.spip.org/@rindex_pile
function rindex_pile($p, $champ, $motif) 
{
	$n = 0;
	$b = $p->id_boucle;
	$p->code = '';
	while ($b != '') {
		foreach($p->boucles[$b]->criteres as $critere) {
			if ($critere->op == $motif) {
				$p->code = '$Pile[$SP' . (($n==0) ? "" : "-$n") .
					"]['$champ']";
				$b = '';
				break 2;
			}
		}
		$n++;
		$b = $p->boucles[$b]->id_parent;
	}

	// si on est hors d'une boucle de {recherche}, cette balise est vide
	if (!$p->code)
		$p->code = "''";

	$p->interdire_scripts = false;
	return $p;
}

?>
