<?php

// index_pile retourne la position dans la pile du champ SQL $nom_champ 
// en prenant la boucle la plus proche du sommet de pile (indique' par $idb).
// Si on ne trouve rien, on conside`re que c~a doit provenir du contexte 
// (par l'URL ou l'include) qui a e'te' recopie' dans Pile[0]
// (un essai d'affinage a de'bouche' sur un bug vicieux)
// Si c~a re'ference un champ SQL, on le me'morise dans la structure $boucles
// afin de construire un requete SQL minimale (plutot qu'un brutal 'SELECT *')

include_ecrire('inc_serialbase.php3');

function index_pile($idb, $nom_champ, &$boucles)
{
   global $exceptions_des_tables, $table_des_tables, $tables_principales;

  $i = 0;
  if ($c=strpos($nom_champ, ':'))
     {
       $idbs = substr($nom_champ, 0, $c);
       $nom_champ = substr($nom_champ, $c+1);
       while (($idb != $idbs) && $idb)
	 {$i++; $idb = $boucles[$idb]->id_parent;
#       spip_log("Cherche en amont: $nom_champ '$idbs' '$idb' '$c'");
	 }
     }

  $c = strtolower($nom_champ);
  # attention a` la boucle nomme'e 0 ....
  while ($idb!== '') {
#       spip_log("Cherche: $nom_champ '$idb' '$c'");
    $r = $boucles[$idb]->type_requete;
    // indirection (pour les rares cas ou` le nom de la table est /= du type)
    $t = $table_des_tables[$r];
    if (!$t) $t = $r; // pour les tables non Spip
    // $t est le nom PHP de cette table 
    $x = $tables_principales[$t];
    if (!$x) 
    {
      include_local("inc-debug-squel.php3");
      erreur_syntaxe_boucle("Table SQL absente de \$tables_principales dans inc_serialbase", $r, $idb);
    }

    $a = $x['field'];
    $e = $a[$c];
#    spip_log("	Dans $idb ($t $e): $x");    
    // $e est le type SQL de l'entre'e (on s'en sert comme boole'en uniquement)
      if ($e)
      // entite' SPIP homonyme au champ SQL
      { $e = $c; }
    else
      {
      // entite' SPIP alias d'un champ SQL
	$e = $exceptions_des_tables[$r][$c];
	if (is_array($e))
      // entite' SPIP dans une table SQL annexe qu'il faut pre'ciser
	  { $t = $e[0]; $e = $e[1]; } }
    if ($e)
      {
	$boucles[$idb]->select[] = $t . "." . $e;
	return ('$PileRow[$SP' .
		($i ? "-$i" : "") . '][' .
		$e . 
		"]");
	    }
    $idb = $boucles[$idb]->id_parent;
    $i++;
  }
#  spip_log("Pas vu $nom_champ dans les " . count($boucles) . " boucles");
  # espe'rons qu'il y sera
  return('$PileRow[0]['.$nom_champ.']');
}

# calculer_champ genere le code PHP correspondant a la balise Spip $nom_champ
# Retourne un tableau dont le premier e'le'ment est une EXPRESSION php 
# et le deuxie`me une suite d'INSTRUCTIONS a` exe'cuter AVANT de calculer
# l'expression (typiquement: un include ou une affectation d'auxiliaires)
# Ce tableau est e'galement retourne' par la fonction applique_filtres
# qui s'occupe de construire l'application 
# s'il existe une fonction nomme'e "calculer_champ_" suivi du nom du champ,
# on lui passe la main et elle est cense' retourner le tableau ci-dessus
# (Essayer de renvoyer une suite vide, c~a diminue les allocations a` l'exec)

function calculer_champ($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
{
# regarder s'il existe une fonction spe'cifique a` ce nom
  $f = 'calculer_champ_' . $nom_champ;
  if (function_exists($f)) 
    return $f($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere);
  else
    {
    # on regarde ensuite s'il y a un champ SQL homonyme,
      $code = index_pile($id_boucle, $nom_champ, &$boucles);
      if (($code) && ($code != '$PileRow[0]['.$nom_champ.']'))
	  return applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
      else
	{
# si index_pile a ramene' le choix par de'faut, 
# c~a doit plutot e^tre un champ SPIP non SQL, ou ni l'un ni l'autre
	  return calculer_champ_divers($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere);
	}
    }
}


// ge'ne`re l'application d'une liste de filtres

function applique_filtres ($fonctions, $code, $id_boucle, $boucles, $id_mere)
{
  $milieu = '';
  if ($fonctions) {
    while (list(, $fonc) = each($fonctions)) {
      if ($fonc) {
	$arglist = '';
	if (ereg('([^\{\}]*)\{(.+)\}$', $fonc, $regs)) {
	  $fonc = $regs[1];
	  $args = $regs[2];
	  while (ereg('([^,]+),?(.*)$', $args, $regs)) {
	    $args = $regs[2];
	    $arg = trim($regs[1]);
	    if ($arg)
	      {
		if ($arg[0] =='#')
		  {
		    list($arg,$m) = calculer_champ(array(),substr($arg,1),$id_boucle, &$boucles, $id_mere);
		    $milieu .= $m;
		  }
		else {if ($arg[0] =='$')
		    $arg = '$PileRow[0][\'' . substr($arg,1) . "']";}
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
  return array($code,$milieu);
}

?>
