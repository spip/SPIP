<?php
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CALCUL_SQUEL")) return;
define("_INC_CALCUL_SQUEL", "1");

# Fichier principal du compilateur de squelettes, incluant tous les autres.

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

# Produit le corps PHP d'une boucle Spip,
# essentiellement une boucle while (ou une double en cas de hierarchie)
# remplissant une variable $t0 retourne'e en valeur

function calculer_boucle($id_boucle, &$boucles)
{
  global $table_primary, $table_des_tables; 

	$boucle = &$boucles[$id_boucle];
	$type_boucle = $boucle->type_requete;

 	list($return,$corps) = $boucle->return;

	// Boucle recursive : simplement appeler la boucle interieure

	if ($type_boucle == 'boucle')
	  {
	    return ("$corps\n\treturn  $return;");
	}

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

	$flag_h = ($type_boucle == 'hierarchie');
	$flag_parties = ($boucle->partie AND $boucle->total_parties);
	$flag_cpt = $flag_parties || # pas '$compteur' a` cause du cas 0
	  		strpos($corps,'compteur_boucle') ||
	  		strpos($return,'compteur_boucle');
	$primary_key = $table_primary[$type_boucle];

	if ($primary_key) // sinon c'est une boucle hors Spip
	  {
	    // invalidation des caches si la boucle n'est pas vide
	    if ($return == "''")
	      $invalide = '';
	    else
	      {$id_table = $table_des_tables[$type_boucle]; 
		$boucle->select[] = "$id_table.$primary_key";
		$invalide = '
		$Cache["' . $primary_key . '"][$PileRow[$SP]["'  .
		  $primary_key . '"]]=1;';
	      }
	  }
	$corps =
	  ((!$flag_cpt) ? "" : "\n\t\t\$compteur_boucle++;") .
	  ((!$flag_parties) ? "" : '
		if	($compteur_boucle >= $debut_boucle AND 
			 $compteur_boucle <= $fin_boucle) {') .
	  (((!$lang_select)||($return == "''")) ? "" : ('
		if ($x = $PileRow[$SP]["lang"]) $GLOBALS["spip_lang"] = $x;')) .
	  $invalide .
	  ((!$boucle->doublons) ? "" : 
	   ("\n\t\t\$doublons['$type_boucle'] .= ','. \$PileRow[\$SP]['" .
	    $primary_key . "'];")).
	  $corps .
	  (($return == "''") ? "" :
	   ((!$boucle->separateur) ? 
	    ("\n\t\t" . '$t0 .= ' . $return . ";") :
	    ("\n\t\t" . '$t1 = ' . $return . ";\n\t\t" .
	     '$t0 .= (($t1 && $t0) ? \'' . $boucle->separateur .
	     "' : '') . \$t1;"))).
	  ((!$flag_parties) ? "" : "\t\t}\n");

	// Recherche : recuperer les hash a partir de la chaine de recherche

	if ($boucle->hash) {
		$texte =  '
		global $recherche, $hash_recherche, $hash_recherche_strict;
		list($hash_recherche, $hash_recherche_strict) = requete_hash($recherche);';
	}
	else { $texte = ''; }

	if ($flag_h) {
	    $texte .= '
	$hierarchie = ' . ($boucle->tout ?  $boucle->tout : 
			   // sinon,  parame`tre passe' par include.
			   // me^me code, mais a` inexe'cutable a` la compilation:
			   '(($PileRow[0]["id_article"] ||
	      $PileRow[0]["id_syndic"]) ?
	    $PileRow[0]["id_rubrique"] :
	    $PileRow[0]["id_parent"])') .
	      ';
	$h0 = "";
	while ($hierarchie) {';
	}

	if (!($corps || $boucle->numrows))
	  return 'return "";';
	else
	return  ($texte . '
	$result = ' . calculer_requete($boucle) . ';
	$t0 = "";
	$SP++;' .
		 (($flag_parties) ? 
		  calculer_parties($boucle->partie,
				   $boucle->mode_partie,
				   $boucle->total_parties) :
		  ((!$boucle->numrows) ? '' : '
	$PileNum[$SP] = @spip_num_rows($result);')) .
		 ((!$flag_cpt) ? '' : "\n\t\$compteur_boucle = 0;") .
		 ((!$corps) ? "" :
		  (
		   ((!$lang_select) ? "" : '
	$old_lang = $GLOBALS[\'spip_lang\'];') . '
	while ($PileRow[$SP] = @spip_fetch_array($result)) {' .
		((!$flag_h) ? "" : '
		 $hierarchie = $PileRow[$SP][id_parent];') .
		$corps .
		"\n\t}" .
		((!$lang_select) ? "" : '
	$GLOBALS["spip_lang"] = $old_lang;'))) . '
	@spip_free_result($result);' .
		 (!($flag_h) ? '
	return $t0;' : ('
	$h0 = $t0 .' .
		   ((!$boucle->separateur) ? "" :
		    ('(($h0 && $t0) ? \'' . $boucle->separateur . "' : '') .")) .
		   ' $h0;}
	return $h0;')));
}


// une grosse fonction pour un petit cas

function calculer_parties($partie, $mode_partie, $total_parties)
{
     return ('
	$fin_boucle = @spip_num_rows($result);' .
		 (($mode_partie == '/') ?
		  ('
	$debut_boucle = 1+floor(($fin_boucle * ' . 
		   ($partie - 1) .
		   ' + ' .
		   ($total_parties - 1) .
		   ')/' .
		   $total_parties .
		   ");\n\t" .
		   '$fin_boucle = floor(($fin_boucle * ' .
		   $partie .
		   ' + ' .
		   ($total_parties - 1) .
		   ')/' .
		   $total_parties .
		   ");") :
		  (($mode_partie == '+') ?
		   ('
	$debut_boucle = ' . $partie . ';
	$fin_boucle -= ' . 
		   $total_parties) :
		   ('
	$debut_boucle = $fin_boucle - ' . $partie . ';
	$fin_boucle -= ' . 
		    ($partie - $total_parties)) .
		   ';'))  . '
	$PileNum[$SP] = $fin_boucle - $debut_boucle + 1;');
}


# Production du code PHP a` partir de la se'quence livre'e par le phraseur
# $boucles est passe' par re'fe'rence pour affectation par index_pile.
# Retourne un tableau de 2 e'le'ments: 
# 1. une expression PHP,
# 2. une suite d'instructions PHP a` exe'cuter avant d'e'valuer l'expression.
# si cette suite est vide, on fusionne les se'quences d'expression
# ce qui doit re'duire la me'moire ne'cessaire au processus
# En de'coule une combinatoire laborieuse mais sans difficulte'

function calculer_liste($tableau, $prefix, $id_boucle, $niv, $rec, &$boucles, $id_mere)
{
	if ((!$tableau)) return array("''",'');
	$texte = '';
	$exp = "";
	$process_ins = false;
	$firstset = true;
	$t = '$t' . ($niv+1);
	reset($tableau);
	while (list(, $objet) = each($tableau)) {
	  if ($objet->type == 'texte') {
	    $c = calculer_texte($objet->texte,$id_boucle, $boucles, $id_mere);
	    if (!$exp)
	      $exp = $c;
	    else 
	      {if ((substr($exp,-1)=="'") && (substr($c,1,1)=="'")) 
		  $exp = substr($exp,0,-1) . substr($c,2);
		else
		  $exp .= (!$exp ? $c :  (" .\n\t\t$c"));}
	    if (!(strpos($c,'<?') === false)) $pi = true;
	  } else {
	  if ($objet->type == 'include') {
	    $c = calculer_inclure($objet->fichier,$objet->params,
				  $id_boucle,
				  $boucles,
				  $pi);
	    $exp .= (!$exp ? $c : (" .\n\t\t$c"));
	  } else {
	    if ($objet->type ==  'boucle') {
		$nom = $objet->id_boucle;
		list($bc,$bm) = calculer_liste($objet->cond_avant, $prefix,
					       $objet->id_boucle, $niv+2,$rec, $boucles, $id_mere);
		list($ac,$am) = calculer_liste($objet->cond_apres, $prefix,
					       $objet->id_boucle, $niv+2,$rec, $boucles, $id_mere);
		list($oc,$om) = calculer_liste($objet->cond_altern, $prefix,
					       $objet->id_boucle, $niv+1,$rec, $boucles, $id_mere);

	      
	      $c = $prefix .
		ereg_replace("-","_", $nom) .
		'($Cache, $PileRow, $doublons, $PileNum, $SP)';
	      $m = "";
	    } else {
		list($c,$m) = 
		  calculer_champ($objet->fonctions, 
				 $objet->nom_champ,
				 $id_boucle,
				 $boucles,
				 $id_mere);
		list($bc,$bm) = calculer_liste($objet->cond_avant, $prefix, $id_boucle, $niv+2,false, $boucles, $id_mere); 
	      	list($ac,$am) = calculer_liste($objet->cond_apres, $prefix, $id_boucle, $niv+2,false, $boucles, $id_mere);
		$oc = "''";
		$om = "";
	    }
	    // traitement commun des champs et boucles.
	    // Produit:
	    // m ; if (Tniv+1 = v)
	    // { bm; Tniv+1 = $bc . Tniv+1; am; Tniv+1 .= $ac }
	    // else { om; $Tniv+1 = $oc }
	    // Tniv .= Tniv+1
	    // Optimisations si une au moins des 4 se'quences $*m  est vide

	    if ($m) {
	      // il faut achever le traitement de l'exp pre'ce'dente
	      if ($exp) {
		  $texte .= "\n\t\t\$t$niv " .
		    (($firstset) ? "=" : ".=") .
		    "$exp;$m" ;
		  $firstset = false;
		  $exp = "";
	      } else { $texte .= $m;}
	    }
	    if (!($bm || $am || $om)) {
	      // 3 se'quences vides: 'if' inutile
	      $a = (($bc == "''") ?  "" : "$bc .") .
		$t .
		(($ac == "''") ?  "" : " . $ac");
	      // s'il y a un avant ou un apre`s ou un alternant, il faut '?'
	      if (($a != $t) || ($oc != "''"))
		$c = "(($t = $c) ? ($a) : ($oc))";
	      $exp .= (!$exp ? $c : (" .\n\t\t$c"));
	    } else {
	      // il faut achever le traitement de l'exp pre'ce'dente
	      if ($exp) {
		  $texte .= "\n\t\t\$t$niv " .
		    (($firstset) ? "=" : ".=") .
		    "$exp;$m" ;
		  $firstset = false;
		  $exp = "";
	      } else { $texte .= $m;}
	      $texte .= "\n\t\tif ($t = $c) {\n" . $bm;
	      $texte .= "\n\t\t$t = $bc . $t";
	      if (!$am) {
		$texte .= " . $ac";
	      } else $texte .= "; $am $t .=  $ac";
	      $texte .= ";}";
	      if (!$om)
		{ $texte .= " else {" . $om . "$t =  $oc;}"; }
	      $exp = $t;
	    }
	  }
	  }
	} // while
	if (!$exp) $exp ="''";
	return  (!$texte ? array ($exp, "") : 
		 array('$t'.$niv. ". $exp",$texte));
}

# Prend en argument le source d'un squelette, sa grammaire et un nom.
# Retourne une fonction PHP/SQL portant ce nom et calculant une page HTML.
# Pour appeler la fonction produite, lui fournir 2 tableaux de 1 e'le'ment:
# -1er: e'le'ment 'cache' => nom (du fichier ou` mettre la page)
# -2e: e'lement 0 contenant un environnement ('id_article => $id_article, etc)
# Elle retourne alors un tableau de 3 e'le'ments:
# - 'texte' => page HTML, application du squelette a` l'environnement;
# - 'process_ins' => 'html' ou 'php' selon la pre'sence de PHP dynamique
# - 'invalideurs' =>  de'pendances de cette page, pour invalider son cache.
# (voir son utilisation, optionnelle, dans invalideur.php)

function calculer_squelette($squelette, $nom, $gram) {

# Phraser le squelette, selon sa grammaire
# pour le moment: "html" seul connu (HTML+balises BOUCLE)
  $boucles = '';
  include_local("inc-$gram-squel.php3");
  $racine = parser($squelette, '',$boucles);

  // Traduction des se'quences syntaxique des boucles 

  if ($boucles)
    {
      foreach($boucles as $id => $boucle)
	{ 
	  if ($boucle->type_requete == 'boucle')
	    {
	      $rec = $boucles[$boucle->param];
	      if (!$rec)
		{
		  include_local("inc-debug-squel.php3");
		  erreur_squelette(_L('Boucle récursive non définie'), '',
				   $boucle->param);
		  exit;
		  } 
	      $boucles[$id]->return =
		calculer_liste(array($rec),
			       $nom,
			       $boucle->param,
			       1,
			       true,
			       $boucles,
			       $id);
	    }
	} 
      foreach($boucles as $id => $boucle)
	{ 
	  if ($boucle->type_requete != 'boucle') 
	    {
#	  spip_log("calcul_liste $id de type" . $boucle->type_requete);
	  calculer_params($boucle->type_requete, $boucle->param, $id, $boucles);

	  $boucles[$id]->return =
	    calculer_liste($boucle->milieu,
			   $nom,
			   $id,
			   1,
			   false,
			   $boucles,
			   $id);
	    }
	}
    }

  // idem pour la racine

  list($return,$corps) =
    calculer_liste($racine, $nom, '',0, false, $boucles, '');

  // Corps de toutes les fonctions PHP,
  // en particulier les requetes SQL et TOTAL_BOUCLE
  // de'terminables seulement maintenant
  // Les 3 premiers parame`tres sont passe's par re'fe'rence
  // (sorte d'environnements a` la Lisp 1.5)
  // sauf pour la fonction principale qui recoit les initialisations

  $code = '';

  if ($boucles)
    {
      foreach($boucles as $id => $boucle)
	{
	  $boucles[$id]->return = calculer_boucle($id, $boucles); 
	}
      
      foreach($boucles as $id => $boucle) 
	{
	  $code .= "\n\nfunction $nom" . ereg_replace("-","_",$id) .
		'(&$Cache, &$PileRow, &$doublons, &$PileNum, $SP) {' .
	    $boucle->return .
	    "\n}\n";
	}
    }
  return $code . '
function ' . $nom . '($Cache, $PileRow, $doublons, $PileNum="", $SP=0)
 {
' .
    $corps . "\n \$t0 = " . $return . ';
    $Cache["squelette"]= "' . $nom . '";
    return array("texte" => $t0,
	       "process_ins" =>	((strpos($t0,\'<?\')=== false) ? \'html\' : \'php\'),
	       "invalideurs" => $Cache);' .
    "\n}\n" ;
}
?>
