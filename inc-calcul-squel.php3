<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CALCUL_SQUEL")) return;
define("_INC_CALCUL_SQUEL", "1");

// Fichier principal du compilateur de squelettes, incluant tous les autres.

include_local("inc-bcl-squel.php3");
include_local("inc-arg-squel.php3");
include_local("inc-reqsql-squel.php3");
include_local("inc-champ-squel.php3");
include_local("inc-logo-squel.php3");
include_local("inc-form-squel.php3");
include_local("inc-vrac-squel.php3");
include_local("inc-index-squel.php3");
include_local("inc-text-squel.php3");
include_local("inc-debug.php3");

// Produit le corps PHP d'une boucle Spip,
// essentiellement une boucle while (ou une double en cas de hierarchie)
// remplissant une variable $t0 retourne'e en valeur

function calculer_boucle($id_boucle, &$boucles) {
	global $table_primary, $table_des_tables; 

	$boucle = &$boucles[$id_boucle];
	$type_boucle = $boucle->type_requete;

	list($return,$corps) = $boucle->return;

	// Boucle recursive : simplement appeler la boucle interieure
	if ($type_boucle == 'boucle')
	    return ("$corps\n	return $return;");

	// La boucle doit-elle selectionner la langue ?
	// 1. par defaut 
	$lang_select = (
		$type_boucle == 'articles' OR $type_boucle == 'rubriques'
		OR $type_boucle == 'hierarchie' OR $type_boucle == 'breves'
	);
	// 2. si forcer_lang, le defaut est non
	if ($GLOBALS['forcer_lang']) $lang_select = false;
	// 3. demande explicite
	if ($boucle->lang_select == 'oui') $lang_select = true;
	if ($boucle->lang_select == 'non') $lang_select = false;
	// 4. penser a demander le champ lang
	if ($lang_select)
		$boucle->select[] = (($id_table = $table_des_tables[$type_boucle]) ? $id_table.'.' : '') .'lang';

	// Qui sommes-nous ?
	$primary_key = $table_primary[$type_boucle];

	// Calculer les invalideurs si c'est une boucle non constante
	$constant = ereg("^'[^']*'$",$return);
	if ((!$primary_key) || $constant)
		$invalide = '';
	else {
		$id_table = $table_des_tables[$type_boucle]; 
		$boucle->select[] = "$id_table.$primary_key";

		$invalide = "\n			\$Cache['$primary_key']";
		if ($primary_key != 'id_forum')
			$invalide .= "[\$Pile[\$SP]['$primary_key']] = 1;";
		else
			$invalide .= "[calcul_index_forum(" . 
				// Retournera 4 [$SP] mais force la demande du champ a MySQL
				index_pile($id_boucle, 'id_article', $boucles) . ',' .
				index_pile($id_boucle, 'id_breve', $boucles) .  ',' .
				index_pile($id_boucle, 'id_rubrique', $boucles) .',' .
				index_pile($id_boucle, 'id_syndic', $boucles) .  ")] = 1;";
		$invalide .= ' // invalideurs';
	}


	// Cas {1/3} {1,4} {n-2,1}...
	$flag_parties = ($boucle->partie AND $boucle->total_parties);
	$flag_cpt = $flag_parties || // pas '$compteur' a cause du cas 0
		strpos($corps,'compteur_boucle') ||
		strpos($return,'compteur_boucle');

	//
	// Creer le debut du corps de la boucle :
	//
	if ($flag_cpt)
		$debut = "\n		\$compteur_boucle++;";

	if ($flag_parties)
		$debut .= '
		if ($compteur_boucle >= $debut_boucle
		AND $compteur_boucle <= $fin_boucle) {';
	
	if ($lang_select AND !$constant)
		$debut .= '
			if ($x = $Pile[$SP]["lang"]) $GLOBALS["spip_lang"] = $x; // langue';

	$debut .= $invalide;

	if ($boucle->doublons)
		$debut .= "\n			\$doublons['$type_boucle'] .= ','. " .
		index_pile($id_boucle, $primary_key, $boucles) . "; // doublons";


	//
	// L'ajouter au corps
	//
	$corps = $debut . $corps;

	// Separateur ?
	if ($boucle->separateur) {
		$corps .= "\n			\$t1 = $return;
		\$t0 .= ((\$t1 && \$t0) ? '"
		. $boucle->separateur
		. "' : '')
		. \$t1;";
	} else if ($constant && !$debut) {
		$corps .= $return;
	} else {
		$corps .= "\n			\$t0 .= $return;";
	}

	// Fin de parties
	if ($flag_parties)
		$corps .= "\n		}\n";


	// Gestion de la hierarchie (voir inc-arg-squel)
	if ($boucle->hierarchie)
		$texte .= "\n	".$boucle->hierarchie;

	// hack doublons documents : s'il y a quelque chose dans
	// $GLOBALS['doublons_documents'], c'est que des documents ont
	// ete vus par integre_image() ou autre fournisseur officiel de
	// doublons : on les transfere alors vers la vraie variable
	$texte .= '
	$doublons[\'documents\'] .= $GLOBALS[\'doublons_documents\'];
	unset($GLOBALS[\'doublons_documents\']);';

	// Recherche : recuperer les hash a partir de la chaine de recherche
	if ($boucle->hash) {
		$texte .=  '
	// RECHERCHE
	list($hash_recherche, $hash_recherche_strict) = requete_hash($GLOBALS["recherche"]);';
	}

	// si le corps est une constante, ne plus appeler le serveur
	if (ereg("^'[^']*'$",$corps)) {
		// vide ?
		if ($corps == "''") {
			if (!$boucle->numrows)
				return 'return "";';
			else
				$corps = "";
		} else {
			$boucle->numrows = true;
			$corps = "\n		".'for($x=$Numrows["'.$id_boucle.'"];$x>0;$x--)
			$t0 .= ' . $corps .';';
	    }
	} else {
		$corps = '

	// RESULTATS
	while ($objet = @spip_fetch_array($result)) {'
	. "\n\t\t\$Pile[\$SP] = \$objet;"
	. "\n$corps\n	}\n";

		// Memoriser la langue avant la boucle pour la restituer apres
		if ($lang_select) {
			$texte .= "\n	\$old_lang = \$GLOBALS['spip_lang'];";
			$corps .= "\n	\$GLOBALS['spip_lang'] = \$old_lang;";
		}
	}

	//
	// Requete
	//
	$init = "\n\n	// REQUETE\n	";

	// hack critere recherche : ignorer la requete en cas de hash vide
	if ($boucle->hash)
		$init .= "if (\$hash_recherche) ";

	$init .= "\$result = " . calculer_requete($boucle);
	$init .= "\n	".'$t0 = "";
	$SP++;';
	if ($flag_cpt)
		$init .= "\n	\$compteur_boucle = 0;";


	if ($flag_parties)
		$init .= calculer_parties($boucle->partie,
			$boucle->mode_partie,
			$boucle->total_parties,
			$id_boucle);
	else if ($boucle->numrows)
		$init .= "\n	\$Numrows['$id_boucle'] = @spip_num_rows(\$result);";

	//
	// Conclusion et retour
	//
	$conclusion = "\n	@spip_free_result(\$result);";
	$conclusion .= "\n	return \$t0;";

	return $texte . $init . $corps . $conclusion;
}


function calculer_parties($partie, $mode_partie, $total_parties, $id_boucle) {

	// Notes :
	// $debut_boucle et $fin_boucle sont les indices SQL du premier
	// et du dernier demandes dans la boucle : 0 pour le premier,
	// n-1 pour le dernier ; donc total_boucle = 1 + debut - fin

	// nombre total avant partition
	$retour = "\n\n	// Partition\n	"
	.'$nombre_boucle = @spip_num_rows($result);';

	ereg("([+-/])([+-/])?", $mode_partie, $regs);
	list(,$op1,$op2) = $regs;

	// {1/3}
	if ($op1 == '/') {
		$retour .= "\n	"
			.'$debut_boucle = 1 + ceil(($nombre_boucle * '
			. ($partie - 1) . ')/' . $total_parties . ");\n	"
			. '$fin_boucle = ceil (($nombre_boucle * '
			. $partie . ')/' . $total_parties . ");";
	}

	// {1,x}
	if ($op1 == '+') {
		$retour .= "\n	"
			. '$debut_boucle = ' . $partie . ';';
	}
	// {n-1,x}
	if ($op1 == '-') {
		$retour .= "\n	"
			. '$debut_boucle = $nombre_boucle - ' . $partie . ';';
	}
	// {x,1}
	if ($op2 == '+') {
		$retour .= "\n	"
			. '$fin_boucle = $debut_boucle + ' . $partie . ' - 1;';
	}
	// {x,n-1}
	if ($op2 == '-') {
		$retour .= "\n	"
			. '$fin_boucle = $debut_boucle+($nombre_boucle-'.$partie.')-1;';
	}

	// Rabattre $fin_boucle sur le maximum
	$retour .= "\n	"
		.'$fin_boucle = min($fin_boucle, $nombre_boucle);';

	// calcul du total boucle final
	$retour .= "\n	"
		.'$Numrows[\''.$id_boucle.'\'] = $fin_boucle - $debut_boucle + 1;';

	return $retour;
}


// Production du code PHP a` partir de la se'quence livre'e par le phraseur
// $boucles est passe' par re'fe'rence pour affectation par index_pile.
// Retourne un tableau de 2 e'le'ments: 
// 1. une expression PHP,
// 2. une suite d'instructions PHP a` exe'cuter avant d'e'valuer l'expression.
// si cette suite est vide, on fusionne les se'quences d'expressions
// ce qui doit re'duire la me'moire ne'cessaire au processus
// En de'coule une combinatoire laborieuse mais sans difficulte'

function calculer_liste($tableau, $prefix, $id_boucle, $niv, &$boucles, $id_mere) {
	if ((!$tableau)) return array("''",'');
	$texte = '';
	$exp = "";
	$process_ins = false;
	$firstset = true;
	$t = '$t' . ($niv+1);

	foreach ($tableau as $objet) {

		switch($objet->type) {
		// texte seul
		case 'texte':
			$c = calculer_texte($objet->texte,$id_boucle, $boucles, $id_mere);
			if (!$exp)
				$exp = $c;
			else {
				if ((substr($exp,-1)=="'") && (substr($c,1,1)=="'")) 
					$exp = substr($exp,0,-1) . substr($c,2);
				else
					$exp .= (!$exp ? $c :  (" .\n		$c"));
			}
			if (!(strpos($c,'<'.'?') === false))
				$pi = true;
			$traitement_champ = false;
			break;

		// inclure
		case 'include':
			$c = calculer_inclure($objet->fichier,
				$objet->params,
				$id_boucle,
				$boucles);
			$exp .= (!$exp ? $c : (" .\n		$c"));
			$traitement_champ = false;
			break;

		// boucle
		case 'boucle':
			$nom = $objet->id_boucle;
			list($bc,$bm) = calculer_liste($objet->cond_avant, $prefix,
				$id_boucle, $niv+2, $boucles, $nom);
			list($ac,$am) = calculer_liste($objet->cond_apres, $prefix,
				$id_boucle, $niv+2, $boucles, $nom);
			list($oc,$om) = calculer_liste($objet->cond_altern, $prefix,
				$id_boucle, $niv+1,$boucles, $nom);
			$c = $prefix . ereg_replace("-","_", $nom)
			. '($Cache, $Pile, $doublons, $Numrows, $SP)';
			$m = "";
			$traitement_champ = true;
			$commentaire = "/* BOUCLE$nom */";
			break;

		// balise SPIP
		default: 
			list($c,$m) = calculer_champ($objet->fonctions, 
				$objet->nom_champ,
				$id_boucle,
				$boucles,
				$id_mere,
				$objet->etoile);
			$commentaire = "/* #$objet->nom_champ".($objet->etoile?'*':'')." */";
			list($bc,$bm) = calculer_liste($objet->cond_avant, $prefix,
				$id_boucle, $niv+2,$boucles, $id_mere); 
			list($ac,$am) = calculer_liste($objet->cond_apres, $prefix,
				$id_boucle, $niv+2,$boucles, $id_mere);
			$oc = "''";
			$om = "";
			$traitement_champ = true;
			break;

		} // switch


		// traitement commun des champs et boucles.
		// Produit:
		// m ; if (Tniv+1 = v)
		// { bm; Tniv+1 = $bc . Tniv+1; am; Tniv+1 .= $ac }
		// else { om; $Tniv+1 = $oc }
		// Tniv .= Tniv+1
		// Optimisations si une au moins des 4 se'quences $*m  est vide

		if ($traitement_champ) {
			if ($m) {
				// il faut achever le traitement de l'exp pre'ce'dente
				if ($exp) {
					$texte .= "\n		\$t$niv " .
					(($firstset) ? "=" : ".=") . $exp.';';
					$firstset = false;
					$exp = "";
				}
				$texte .= "\n$commentaire\n$m//\n";
			}

			# alternative a la branche ci-dessus ?
			# if ($m) $c = "eval('".texte_script($m)."').$c";
			# if ($m) $c = "{$m}.$c"; ne marche pas

			// 3 sequences vides: 'if' inutile
			if (!($bm || $am || $om)) {
				if ($exp)
					$exp .= ' . ';
				$a = (($bc == "''") ?  "" : "$bc .") .
				$t .
				(($ac == "''") ?  "" : " . $ac");
				// s'il y a un avant ou un apre`s ou un alternant, il faut '?'
				if (($a != $t) || ($oc != "''"))
					$exp .= "(($t = $c$commentaire) ?\n	($a) : ($oc))";
				else
					$exp .= "$c$commentaire";
			} else {
				// il faut achever le traitement de l'exp pre'ce'dente
				if ($exp) {
					$texte .= "\n		\$t$niv " .
					(($firstset) ? "=" : ".=") .
					"$exp;";
					$firstset = false;
				}
				$exp = (($bc == "''") ? $t : "($bc . $t)");
				$texte .= "\n		if ($t = $c)\n{" . $bm;
				if ($am) {
					$texte .= "$t = $exp;\n	$am";
					$exp = $t;
				}
				if ($ac != "''")
					$exp = "($exp . $ac)";
				$texte .= (($exp == $t) ? '' : ("$t = $exp;")) . ";}";
				if ($om || ($oc != "''"))
					$texte .= " else { $om$t = ($oc);}";
				$exp = $t;
			}
		}
	} // foreach

	if (!$exp)
		$exp ="''";

	return  (!$texte ? array ($exp, "") : 
	array(($firstset ? $exp : ('$t'.$niv. ". $exp")),$texte));
}

// Prend en argument le source d'un squelette, sa grammaire et un nom.
// Retourne une fonction PHP/SQL portant ce nom et calculant une page HTML.
// Pour appeler la fonction produite, lui fournir 2 tableaux de 1 e'le'ment:
// - 1er: element 'cache' => nom (du fichier ou` mettre la page)
// - 2e: element 0 contenant un environnement ('id_article => $id_article, etc)
// Elle retourne alors un tableau de 4 e'le'ments:
// - 'texte' => page HTML, application du squelette a` l'environnement;
// - 'squelette' => le nom du squelette
// - 'process_ins' => 'html' ou 'php' selon la pre'sence de PHP dynamique
// - 'invalideurs' =>  de'pendances de cette page, pour invalider son cache.
// (voir son utilisation, optionnelle, dans invalideur.php)
// En cas d'erreur, elle retourne un tableau des 2 premiers elements seulement

function calculer_squelette($squelette, $nom, $gram, $sourcefile) {

	// Phraser le squelette, selon sa grammaire
	// pour le moment: "html" seul connu (HTML+balises BOUCLE)
	$boucles = '';
	include_local("inc-$gram-squel.php3");
	$racine = parser($squelette, '',$boucles);
	// include_local('inc-debug.php3');
	// afftable($racine);
	// affboucles($boucles);
 
	// Commencer par reperer les boucles appelees explicitement par d'autres
	// car elles indexent leurs arguments de maniere derogatoire

	if ($boucles) foreach($boucles as $id => $boucle) {
		if ($boucle->type_requete == 'boucle') {
			$rec = &$boucles[$boucle->param];
			if (!$rec) {
				return array(_T('info_erreur_squelette'),
				($boucle->param . _L('&nbsp: boucle recursive non definie')));
			} 

			$rec->externe = $id;
			$boucles[$id]->return =
				calculer_liste(array($rec),
					$nom,
					$boucle->param,
					1,
					$boucles,
					$id);
		}
	} 

	if ($boucles) foreach($boucles as $id => $boucle) { 
		if ($boucle->type_requete != 'boucle') {
			$res = calculer_params($id, $boucles);

			// C'est quoi ca ???
			if (is_array($res))
				return $res;

			$boucles[$id]->return = calculer_liste($boucle->milieu,
				$nom,
				$id,
				1,
				$boucles,
				$id);
		}
	}

	// idem pour la racine
	list($return,$corps) = calculer_liste($racine, $nom, '',0, $boucles, '');


	// Corps de toutes les fonctions PHP,
	// en particulier les requetes SQL et TOTAL_BOUCLE
	// de'terminables seulement maintenant
	// Les 3 premiers parame`tres sont passe's par re'fe'rence
	// (sorte d'environnements a` la Lisp 1.5)
	// sauf pour la fonction principale qui recoit les initialisations

	$code = '';
	if ($boucles) {
		foreach($boucles as $id => $boucle)
			$boucles[$id]->return = calculer_boucle($id, $boucles); 

		foreach($boucles as $id => $boucle) {

			// Reproduire la boucle en commentaire
			$pretty = "BOUCLE$id(".strtoupper($boucle->type_requete).")";
			if (is_array($boucle->param))
				$pretty .= " {".join("} {", $boucle->param)."}";
			$pretty = ereg_replace("[\r\n]", " ", $pretty);

			// Puis envoyer son code
			$code .= "\n//\n// <$pretty>\n//\n"
			."function $nom" . ereg_replace("-","_",$id) .
			'(&$Cache, &$Pile, &$doublons, &$Numrows, $SP) {' .
			$boucle->return .
			"\n}\n\n";
		}
	}

	return "
/*
 * Squelette : $sourcefile
 * Date :    ".http_gmoddate(@filemtime($sourcefile))." GMT
 * Compile : ".http_gmoddate(time())." GMT
 * Boucles : ".join (', ', array_keys($boucles))."
 */
$code

//
// Fonction principale du squelette $sourcefile
//
function $nom (\$Cache, \$Pile, \$doublons, \$Numrows='', \$SP=0) {
$corps
\$t0 = $return;

	return array('texte' => \$t0,
	'squelette' => '$nom',
	'process_ins' => ((strpos(\$t0,'<'.'?')=== false) ? 'html' : 'php'),
	'invalideurs' => \$Cache);
}
";

}

?>
