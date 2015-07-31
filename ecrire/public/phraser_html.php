<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined('_ECRIRE_INC_VERSION')) return;

# Ce fichier transforme un squelette en un tableau d'objets de classe Boucle
# il est charge par un include calcule 
# pour permettre differentes syntaxes en entree

define('BALISE_BOUCLE', '<BOUCLE');
define('BALISE_FIN_BOUCLE', '</BOUCLE');
define('BALISE_PRE_BOUCLE', '<B');
define('BALISE_POST_BOUCLE', '</B');
define('BALISE_ALT_BOUCLE', '<//B');

define('TYPE_RECURSIF', 'boucle');
define('SPEC_BOUCLE','/\s*\(\s*([^\s?)]+)(\s*[^)?]*)([?]?)\)/');
define('NOM_DE_BOUCLE', "[0-9]+|[-_][-_.a-zA-Z0-9]*");
# ecriture alambiquee pour rester compatible avec les hexadecimaux des vieux squelettes
define('NOM_DE_CHAMP', "#((" . NOM_DE_BOUCLE . "):)?(([A-F]*[G-Z_][A-Z_0-9]*)|[A-Z_]+)(\*{0,2})");
define('CHAMP_ETENDU', '/\[([^]\[]*)\(' . NOM_DE_CHAMP . '([^[)]*\)[^]\[]*)\]/S');

define('BALISE_INCLURE','/<INCLU[DR]E[[:space:]]*(\(([^)]*)\))?/S');
define('BALISE_POLYGLOTTE',',<multi>(.*)</multi>,Uims');
define('BALISE_IDIOMES',',<:(([a-z0-9_]+):)?([a-z0-9_]+)({([^\|=>]*=[^\|>]*)})?((\|[^>]*)?:>),iS');

define('SQL_ARGS', '(\([^)]*\))');
define('CHAMP_SQL_PLUS_FONC', '`?([A-Z_][A-Z_0-9.]*)' . SQL_ARGS . '?`?');

// http://doc.spip.org/@phraser_inclure
function phraser_inclure($texte, $ligne, $result) {

	while (preg_match(BALISE_INCLURE, $texte, $match)) {
		$p = strpos($texte,$match[0]);
		$debut = substr($texte, 0, $p);
		if ($p) $result = phraser_idiomes($debut, $ligne, $result);
		$ligne +=  substr_count($debut, "\n");
		$champ = new Inclure;
		$champ->ligne = $ligne;
		$ligne += substr_count($match[0], "\n");
		$fichier = @$match[2];
		# assurer ici la migration .php3 => .php
		# et de l'ancienne syntaxe INCLURE(page.php3) devenue surperflue
		if (preg_match(',^(.*[.]php)3$,', $fichier, $r)) {
			$fichier = $r[1];
		}
		$champ->texte = ($fichier !== 'page.php') ? $fichier : '';
		$texte = substr($texte, $p+strlen($match[0]));
		// on assimile {var=val} a une liste de un argument sans fonction
		phraser_args($texte,"/>","",$result,$champ);
		if (!$champ->texte OR count($champ->param) > 1) {
			include_spip('public/normaliser');
			normaliser_inclure($champ);
		}
		$texte = substr($champ->apres, strpos($champ->apres, '>')+1);
		$champ->apres = "";
		$texte = preg_replace(',^</INCLU[DR]E>,', '', $texte);
		$result[] = $champ;
	}
	return (($texte==="") ? $result : phraser_idiomes($texte, $ligne, $result));
}

// http://doc.spip.org/@phraser_polyglotte
function phraser_polyglotte($texte,$ligne, $result) {

	if (preg_match_all(BALISE_POLYGLOTTE, $texte, $m, PREG_SET_ORDER))
	foreach ($m as $match) {
		$p = strpos($texte, $match[0]);
		$debut = substr($texte, 0, $p);
		if ($p) {
			$champ = new Texte;
			$champ->texte = $debut;
			$champ->ligne = $ligne;
			$result[] = $champ;
			$ligne += substr_count($champ->texte, "\n");
		}

		$champ = new Polyglotte;
		$champ->ligne = $ligne;
		$ligne += substr_count($match[0], "\n");
		$lang = '';
		$bloc = $match[1];
		$texte = substr($texte,$p+strlen($match[0]));
		while (preg_match("/^[[:space:]]*([^[{]*)[[:space:]]*[[{]([a-z_]+)[]}](.*)$/si", $bloc, $regs)) {
		  $trad = $regs[1];
		  if ($trad OR $lang) 
			$champ->traductions[$lang] = $trad;
		  $lang = $regs[2];
		  $bloc = $regs[3];
		}
		$champ->traductions[$lang] = $bloc;
		$result[] = $champ;
	}
	if ($texte!=="") {
			$champ = new Texte;
			$champ->texte = $texte;
			$champ->ligne = $ligne;
			$result[] = $champ;
	}
	return $result;
}


// http://doc.spip.org/@phraser_idiomes
function phraser_idiomes($texte,$ligne,$result) {

	// Reperer les balises de traduction <:module:chaine{argument1=texte,argument2=#BALISE}|filtre1{texte,#BALISE}|filtre2:>
	while (preg_match(BALISE_IDIOMES, $texte, $match)) {
		$p = strpos($texte, $match[0]);
		$debut = substr($texte, 0, $p);
		if ($p) $result = phraser_champs($debut, $ligne, $result);
		$champ = new Idiome;
		$ligne += substr_count($debut, "\n");	
		$champ->ligne = $ligne;
		$ligne += substr_count($match[0], "\n");
		$texte = substr($texte,$p+strlen($match[0]));
		// Stocker les arguments de la balise de traduction
		$args=array();
		$vals=preg_split('/,\s*/',$match[5]);
		foreach ($vals as $val) {
			$arg=preg_split('/\s*=/',$val);
			if ($arg[0])
			  $args[$arg[0]]=phraser_champs($arg[1], 0, array());	
		}
		$champ->arg=$args;
		$champ->nom_champ = strtolower($match[3]);
		$champ->module = $match[2] ? $match[2] : MODULES_IDIOMES;
		// pas d'imbrication pour les filtres sur langue
		phraser_args(@$match[7], ":", '', array(), $champ);
		$result[] = $champ;
	}
	if ($texte!=="")  $result = phraser_champs($texte,$ligne,$result);
	return $result;
}

// http://doc.spip.org/@phraser_champs
function phraser_champs($texte,$ligne,$result) {
	while (preg_match("/".NOM_DE_CHAMP."/S", $texte, $match)) {
	  $p = strpos($texte, $match[0]);
	  $suite = substr($texte,$p+strlen($match[0]));
	  if ($match[5] || (strpos($suite[0], "[0-9]") === false)) {
		$debut = substr($texte, 0, $p);
		if ($p)	$result = phraser_polyglotte($debut, $ligne, $result);
		$ligne += substr_count($debut, "\n");
		$champ = new Champ;
		$champ->ligne = $ligne;
		$ligne += substr_count($match[0], "\n");
		$champ->nom_boucle = $match[2];
		$champ->nom_champ = $match[3];
		$champ->etoile = $match[5];

		if ($suite[0] == '{') {
			phraser_arg($suite, '', array(), $champ);
		// ce ltrim est une ereur de conception
		// mais on le conserve par souci de compatibilite
			$texte = ltrim($suite);
		// Il faudrait le normaliser dans l'arbre de syntaxe abstraite
		// pour faire sauter ce cas particulier a la decompilation.
		/* Ce qui suit est malheureusement incomplet pour cela:
			if ($n = (strlen($suite) - strlen($texte))) {
				$champ->apres = array(new Texte);
				$champ->apres[0]->texte = substr($suite,0,$n);
			}
		*/
		} else $texte = $suite;
		phraser_vieux($champ);
		$result[] = $champ;
	  } else {
	    // faux champ
	    $result = phraser_polyglotte (substr($texte, 0, $p+1), $ligne, $result);
	    $texte = (substr($texte, $p+1));
	  }
	}
	if ($texte!=="") $result = phraser_polyglotte($texte, $ligne, $result);
	return $result;
}

// Gestion des imbrications:
// on cherche les [..] les plus internes et on les remplace par une chaine
// %###N@ ou N indexe un tableau comportant le resultat de leur analyse
// on recommence tant qu'il y a des [...] en substituant a l'appel suivant

// http://doc.spip.org/@phraser_champs_etendus
function phraser_champs_etendus($texte, $ligne,$result) {
	if ($texte==="") return $result;
	$sep = '##';
	while (strpos($texte,$sep)!== false)
		$sep .= '#';
	return array_merge($result, phraser_champs_interieurs($texte, $ligne, $sep, array()));
}

//  Analyse les filtres d'un champ etendu et affecte le resultat
// renvoie la liste des lexemes d'origine augmentee
// de ceux trouves dans les arguments des filtres (rare)
// sert aussi aux arguments des includes et aux criteres de boucles
// Tres chevelu

// http://doc.spip.org/@phraser_args
function phraser_args($texte, $fin, $sep, $result, &$pointeur_champ) {
  $texte = ltrim($texte);
  while (($texte!=="") && strpos($fin, $texte[0]) === false) {
	$result = phraser_arg($texte, $sep, $result, $pointeur_champ);
	$texte = ltrim($texte);
  }
# mettre ici la suite du texte, 
# notamment pour que l'appelant vire le caractere fermant si besoin
  $pointeur_champ->apres = $texte;
  return $result;
}

// http://doc.spip.org/@phraser_arg
function phraser_arg(&$texte, $sep, $result, &$pointeur_champ) {
	preg_match(",^(\|?[^}{)|]*)(.*)$,ms", $texte, $match);
	$suite = ltrim($match[2]);
	$fonc = trim($match[1]);
	if ($fonc && $fonc[0] == "|") $fonc = ltrim(substr($fonc,1));
	$res = array($fonc);
	$err_f = '';
	// cas du filtre sans argument ou du critere /
	if (($suite && ($suite[0] != '{')) || ($fonc  && $fonc[0] == '/')) {
		// si pas d'argument, alors il faut une fonction ou un double |
		if (!$match[1]) {
			$err_f = array('zbug_erreur_filtre', array('filtre' => $texte));
			erreur_squelette($err_f, $pointeur_champ);
			$texte = '';
		} else 	$texte = $suite;
		if ($err_f) $pointeur_champ->param = false;
		elseif ($fonc!=='') $pointeur_champ->param[] = $res;
		// pour les balises avec faux filtres qui boudent ce dur larbeur
		$pointeur_champ->fonctions[] = array($fonc, '');
		return $result;
	}
	$args = ltrim(substr($suite,1)); // virer le '(' initial
	$collecte = array();
	while ($args && $args[0] != '}') {
		if ($args[0] == '"')
			preg_match ('/^(")([^"]*)(")(.*)$/ms', $args, $regs);
		else if ($args[0] == "'")
			preg_match ("/^(')([^']*)(')(.*)$/ms", $args, $regs);
		else {
		  preg_match("/^([[:space:]]*)([^,([{}]*([(\[{][^])}]*[])}])?[^,}]*)([,}].*)$/ms", $args, $regs);
		  if (!strlen($regs[2]))
		    {
			$err_f = array('zbug_erreur_filtre', array('filtre' => $args));
			erreur_squelette($err_f, $pointeur_champ);
			$champ->apres = $champ->avant = $args = "";
			break;
		      }   
		}
		$arg = $regs[2];
		if (trim($regs[1])) {
			$champ = new Texte;
			$champ->texte = $arg;
			$champ->apres = $champ->avant = $regs[1];
			$result[] = $champ;
			$collecte[] = $champ;
			$args = ltrim($regs[count($regs)-1]);
		} else {
			if (!preg_match("/".NOM_DE_CHAMP ."([{|])/", $arg, $r)) {
				// 0 est un aveu d'impuissance. A completer
				$arg = phraser_champs_exterieurs($arg, 0, $sep, $result);

				$args = ltrim($regs[count($regs)-1]);
				$collecte = array_merge($collecte, $arg);
				$result = array_merge($result, $arg);
			}
			else {
				$n = strpos($args,$r[0]);
				$pred = substr($args, 0, $n);
				$par = ',}';
				if (preg_match('/^(.*)\($/', $pred, $m))
					{$pred = $m[1]; $par =')';}
				if ($pred) {
					$champ = new Texte;
					$champ->texte = $pred;
					$champ->apres = $champ->avant = "";
					$result[] = $champ;
					$collecte[] = $champ;
				}
				$rec = substr($args, $n + strlen($r[0]) -1);
				$champ = new Champ;
				$champ->nom_boucle = $r[2];
				$champ->nom_champ = $r[3];
				$champ->etoile = $r[5];
				$next = $r[6];
				while ($next=='{') {
					phraser_arg($rec, $sep, array(), $champ);
					$args = ltrim($rec) ;
					$next = $args[0];
				}
				while ($next=='|') {
					phraser_args($rec, $par, $sep, array(), $champ);
					$args = $champ->apres ;
					$champ->apres = '';
					$next = $args[0];
				}
				// Si erreur de syntaxe dans un sous-argument, propager.
				if ($champ->param === false)
					$err_f = true;
				else phraser_vieux($champ);
				if ($par==')') $args = substr($args,1);
				$collecte[] = $champ;
				$result[] = $champ;
			}
		}
		if ($args[0] == ',') {
			$args = ltrim(substr($args,1));
			if ($collecte) {$res[] = $collecte; $collecte = array();}
		}
	}
	if ($collecte) {$res[] = $collecte; $collecte = array();}
	$texte = substr($args,1);
	$source = substr($suite, 0, strlen($suite) - strlen($texte));
	// propager les erreurs, et ignorer les param vides
	if ($pointeur_champ->param !== false) {
		if ($err_f)
			$pointeur_champ->param = false;
		elseif ($fonc!=='' || count($res) > 1)
			$pointeur_champ->param[] = $res;
	}
	// pour les balises avec faux filtres qui boudent ce dur larbeur
	$pointeur_champ->fonctions[] = array($fonc, $source);
	return $result;
}


// http://doc.spip.org/@phraser_champs_exterieurs
function phraser_champs_exterieurs($texte, $ligne, $sep, $nested) {
	$res = array();
	while (($p=strpos($texte, "%$sep"))!==false) {
		if (!preg_match(',^%'.preg_quote($sep).'([0-9]+)@,', substr($texte,$p), $m))
			break;
		$debut = substr($texte,0,$p);
		$texte = substr($texte, $p+strlen($m[0]));
		if ($p)
			$res = phraser_inclure($debut, $ligne, $res);
		$ligne += substr_count($debut, "\n");
		$res[]= $nested[$m[1]];
	}
	return (($texte==='') ? $res : phraser_inclure($texte, $ligne, $res));
}

// http://doc.spip.org/@phraser_champs_interieurs
function phraser_champs_interieurs($texte, $ligne, $sep, $result) {
	$i = 0; // en fait count($result)
	$x = "";

	while (true) {
		$j=$i;
		$n = $ligne;
		while (preg_match(CHAMP_ETENDU, $texte, $match)) {
			$p = strpos($texte, $match[0]);
			$debut = substr($texte, 0, $p);
			if ($p) {
				$result[$i] = $debut;
				$i++;
			}
			$nom = $match[4];
			$champ = new Champ;
			// ca ne marche pas encore en cas de champ imbrique
			$champ->ligne = $x ? 0 :($n+substr_count($debut, "\n"));
			$champ->nom_boucle = $match[3];
			$champ->nom_champ = $nom;
			$champ->etoile = $match[6];
			// phraser_args indiquera ou commence apres
			$result = phraser_args($match[7], ")", $sep, $result, $champ);
			phraser_vieux($champ);
			$champ->avant =
				phraser_champs_exterieurs($match[1],$n,$sep,$result);
			$debut = substr($champ->apres,1);
			$n += substr_count(substr($texte, 0, strpos($texte, $debut)), "\n");
			$champ->apres = phraser_champs_exterieurs($debut,$n,$sep,$result);

			$result[$i] = $champ;
			$i++;
			$texte = substr($texte,$p+strlen($match[0]));
		}
		if ($texte!=="") {$result[$i] = $texte; $i++;}
		$x ='';

		while($j < $i) {
			$z= $result[$j]; 
			// j'aurais besoin de connaitre le nombre de lignes...
			if (is_object($z))
				$x .= "%$sep$j@";
			else
				$x.=$z;
			$j++;
		}
		if (preg_match(CHAMP_ETENDU, $x))
			$texte = $x;
		else
			return phraser_champs_exterieurs($x, $ligne, $sep, $result);
	}
}

function phraser_vieux(&$champ)
{
	$nom = $champ->nom_champ;
	if ($nom == 'EMBED_DOCUMENT') {
		include_spip('public/normaliser');
		phraser_vieux_emb($champ);
	} elseif ($nom == 'EXPOSER') {
		include_spip('public/normaliser');
		phraser_vieux_exposer($champ);
	} elseif ($champ->param) {
		if ($nom == 'FORMULAIRE_RECHERCHE') {
			include_spip('public/normaliser');
			phraser_vieux_recherche($champ);
		} elseif (preg_match(",^LOGO_[A-Z]+,", $nom)) {
			include_spip('public/normaliser');
			phraser_vieux_logos($champ);
		} elseif ($nom == 'MODELE') {
			include_spip('public/normaliser');
			phraser_vieux_modele($champ);
		} elseif ($nom == 'INCLURE' OR $nom == 'INCLUDE') {
			include_spip('public/normaliser');
			phraser_vieux_inclu($champ);
		}
	}
}

// analyse des criteres de boucle, 

// http://doc.spip.org/@phraser_criteres
function phraser_criteres($params, &$result) {

	$err_ci = ''; // indiquera s'il y a eu une erreur
	$args = array();
	$type = $result->type_requete;
	$doublons = array();
	foreach($params as $v) {
		$var = $v[1][0];
		$param = ($var->type != 'texte') ? "" : $var->texte;
		if ((count($v) > 2) && (!preg_match(",[^A-Za-z]IN[^A-Za-z],i",$param)))
		  {
// plus d'un argument et pas le critere IN:
// detecter comme on peut si c'est le critere implicite LIMIT debut, fin

			if (($var->type != 'texte') ||
			    (strpos("0123456789-", $param[strlen($param)-1])
			     !== false)) {
			  $op = ',';
			  $not = "";
			} else {
			  // Le debut du premier argument est l'operateur
			  preg_match("/^([!]?)([a-zA-Z][a-zA-Z0-9]*)[[:space:]]*(.*)$/ms", $param, $m);
			  $op = $m[2];
			  $not = $m[1];
			  // virer le premier argument,
			  // et mettre son reliquat eventuel
			  // Recopier pour ne pas alterer le texte source
			  // utile au debusqueur

			  if ($m[3]) {
			    $texte = new Texte;
			    $texte->texte = $m[3]; 
			    $v[1][0]= $texte;
			  } else array_shift($v[1]);
			}
			array_shift($v); // $v[O] est vide
			$crit = new Critere;
			$crit->op = $op;
			$crit->not = $not;
			$crit->exclus ="";
			$crit->param = $v;
			$args[] = $crit;
		  } else {
		  if ($var->type != 'texte') {
		    // cas 1 seul arg ne commencant pas par du texte brut: 
		    // erreur ou critere infixe "/"
		    if (($v[1][1]->type != 'texte') || (trim($v[1][1]->texte) !='/')) {
			$err_ci = array('zbug_critere_inconnu', 
					array('critere' => $var->nom_champ));
			erreur_squelette($err_ci, $result);
		    } else {
		      $crit = new Critere;
		      $crit->op = '/';
		      $crit->not = "";
		      $crit->exclus ="";
		      $crit->param = array(array($v[1][0]),array($v[1][2]));
		      $args[] = $crit;
		    }
		  } else {
	// traiter qq lexemes particuliers pour faciliter la suite
	// les separateurs
			if ($var->apres)
				$result->separateur[] = $param;
			elseif (($param == 'tout') OR ($param == 'tous'))
				$result->modificateur['tout'] = true;
			elseif ($param == 'plat') 
				$result->modificateur['plat'] = true;

	// Boucle hierarchie, analyser le critere id_article - id_rubrique
	// - id_syndic, afin, dans les cas autres que {id_rubrique}, de
	// forcer {tout} pour avoir la rubrique mere...

			elseif (!strcasecmp($type, 'hierarchie') AND
				($param == 'id_article' OR $param == 'id_syndic'))
				$result->modificateur['tout'] = true;
			elseif (!strcasecmp($type, 'hierarchie') AND ($param == 'id_rubrique'))
				{;}
			else {
			  // pas d'emplacement statique, faut un dynamique
			  /// mais il y a 2 cas qui ont les 2 !
			  if (($param == 'unique') || (preg_match(',^!?doublons *,', $param)))
			    {
			      // cette variable sera inseree dans le code
			      // et son nom sert d'indicateur des maintenant
			      $result->doublons = '$doublons_index';
			      if ($param == 'unique') $param = 'doublons';
			    }
			  elseif ($param == 'recherche')
			    // meme chose (a cause de #nom_de_boucle:URL_*)
			      $result->hash = ' ';
			  if (preg_match(',^ *([0-9-]+) *(/) *(.+) *$,', $param, $m)) {
			    $crit = phraser_critere_infixe($m[1], $m[3],$v, '/', '', '');
			  } elseif (preg_match(',^([!]?)(' . CHAMP_SQL_PLUS_FONC . 
					 ')[[:space:]]*(\??)(!?)(<=?|>=?|==?|\b(?:IN|LIKE)\b)(.*)$,is', $param, $m)) {
			    $a2 = trim($m[8]);
			    if ($a2 AND ($a2[0]=="'" OR $a2[0]=='"') AND ($a2[0]==substr($a2,-1)))
			      $a2 = substr($a2,1,-1);
			    $crit = phraser_critere_infixe($m[2], $a2, $v,
							   (($m[2] == 'lang_select') ? $m[2] : $m[7]),
							   $m[6], $m[5]);
					$crit->exclus = $m[1];
			  } elseif (preg_match("/^([!]?)\s*(" .
					       CHAMP_SQL_PLUS_FONC .
					       ")\s*(\??)(.*)$/is", $param, $m)) {
		  // contient aussi les comparaisons implicites !
			    // Comme ci-dessus: 
			    // le premier arg contient l'operateur
			    array_shift($v);
			    if ($m[6]) {
			      $v[0][0] = new Texte;
			      $v[0][0]->texte = $m[6];
			    } else {
			      array_shift($v[0]);
			      if (!$v[0]) array_shift($v);
			    }
			    $crit = new Critere;
			    $crit->op = $m[2];
			    $crit->param = $v;
			    $crit->not = $m[1];
			    $crit->cond = $m[5];
			  }
			  else {
			 	$err_ci = array('zbug_critere_inconnu', 
					array('critere' => $param));
				erreur_squelette($err_ci, $result);
			  }
			  if ((!preg_match(',^!?doublons *,', $param)) || $crit->not)
			    $args[] = $crit;
			  else 
			    $doublons[] = $crit;
			}
		  }
		}
	}
	// les doublons non nies doivent etre le dernier critere
	// pour que la variable $doublon_index ait la bonne valeur
	// cf critere_doublon
	if ($doublons) $args= array_merge($args, $doublons);
	// Si erreur, laisser la chaine dans ce champ pour le HTTP 503
	if (!$err_ci) $result->criteres = $args;
}

// http://doc.spip.org/@phraser_critere_infixe
function phraser_critere_infixe($arg1, $arg2, $args, $op, $not, $cond)
{
	$args[0] = new Texte;
	$args[0]->texte = $arg1;
	$args[0] = array($args[0]);
	$args[1][0] = new Texte;
	$args[1][0]->texte  = $arg2;
	$crit = new Critere;
	$crit->op = $op;
	$crit->not = $not;
	$crit->cond = $cond;
	$crit->param = $args;
	return $crit;
}

function public_phraser_html_dist($texte, $id_parent, &$boucles, $descr, $ligne=1) {

	$all_res = array();

	while (($pos_boucle = strpos($texte, BALISE_BOUCLE)) !== false) {

		$err_b = ''; // indiquera s'il y a eu une erreur
		$result = new Boucle;
		$result->id_parent = $id_parent;
		$result->descr = $descr;
# attention: reperer la premiere des 2 balises: pre_boucle ou boucle

		if (!preg_match(",".BALISE_PRE_BOUCLE . '[0-9_],', $texte, $r)
			OR ($n = strpos($texte, $r[0]))===false
			OR ($n > $pos_boucle) ) {
		  $debut = substr($texte, 0, $pos_boucle);
		  $milieu = substr($texte, $pos_boucle);
		  $k = strpos($milieu, '(');
		  $id_boucle = trim(substr($milieu,
					   strlen(BALISE_BOUCLE),
					   $k - strlen(BALISE_BOUCLE)));
		  $milieu = substr($milieu, $k);

		} else {
		  $debut = substr($texte, 0, $n);
		  $milieu = substr($texte, $n);
		  $k = strpos($milieu, '>');
		  $id_boucle = substr($milieu,
				       strlen(BALISE_PRE_BOUCLE),
				       $k - strlen(BALISE_PRE_BOUCLE));

		  if (!preg_match(",".BALISE_BOUCLE . $id_boucle . "[[:space:]]*\(,", $milieu, $r)) {
			$err_b = array('zbug_erreur_boucle_syntaxe', array('id' => $id_boucle));
			erreur_squelette($err_b, $result);
		  }
		  $pos_boucle = $n;
		  $n = strpos($milieu, $r[0]);
		  $result->avant = substr($milieu, $k+1, $n-$k-1);
		  $milieu = substr($milieu, $n+strlen($id_boucle)+strlen(BALISE_BOUCLE));
		}
		$result->id_boucle = $id_boucle;

		preg_match(SPEC_BOUCLE, $milieu, $match);
		$result->type_requete = $match[0];
                $milieu = substr($milieu, strlen($match[0]));
		$type = $match[1];
		$jointures = trim($match[2]);
		$table_optionnelle = ($match[3]);
		if ($jointures) {
			// on affecte pas ici les jointures explicites, mais dans la compilation
			// ou elles seront completees des jointures declarees
			$result->jointures_explicites = $jointures;
		}
		
		if ($table_optionnelle){
			$result->table_optionnelle = $type;
		}
		
		// 1ere passe sur les criteres, vu comme des arguments sans fct
		// Resultat mis dans result->param
		phraser_args($milieu,"/>","",$all_res,$result);

		// En 2e passe result->criteres contiendra un tableau
		// pour l'instant on met le source (chaine) :
		// si elle reste ici au final, c'est qu'elle contient une erreur
		$result->criteres =  substr($milieu,0,@strpos($milieu,$result->apres));
		$milieu = $result->apres;
		$result->apres = "";

		//
		// Recuperer la fin :
		//
		if ($milieu[0] === '/') {
			$suite = substr($milieu,2);
			$milieu = '';
		} else {
			$milieu = substr($milieu,1);
			$s = BALISE_FIN_BOUCLE . $id_boucle . ">";
			$p = strpos($milieu, $s);
			if ($p === false) {
				$err_b = array('zbug_erreur_boucle_fermant',
					array('id' => $id_boucle));
				erreur_squelette($err_b, $result);
			}

			$suite = substr($milieu, $p + strlen($s));
			$milieu = substr($milieu, 0, $p);
		}

		$result->milieu = $milieu;

		//
		// 1. Recuperer la partie conditionnelle apres
		//
		$s = BALISE_POST_BOUCLE . $id_boucle . ">";
		$p = strpos($suite, $s);
		if ($p !== false) {
			$result->apres = substr($suite, 0, $p);
			$suite = substr($suite, $p + strlen($s));
		}

		//
		// 2. Recuperer la partie alternative
		//
		$s = BALISE_ALT_BOUCLE . $id_boucle . ">";
		$p = strpos($suite, $s);
		if ($p !== false) {
			$result->altern = substr($suite, 0, $p);
			$suite = substr($suite, $p + strlen($s));
		}
		$result->ligne = $ligne + substr_count($debut, "\n");
		$m = substr_count($milieu, "\n");
		$b = substr_count($result->avant, "\n");
		$a = substr_count($result->apres, "\n");

		if ($p = strpos($type, ':')) {
			$result->sql_serveur = substr($type,0,$p);
			$type = substr($type,$p+1);
		}
		$soustype = strtolower($type);
		if ($soustype == 'sites') $soustype = 'syndication' ; # alias

		if (!isset($GLOBALS["table_des_tables"][$soustype]))
			$soustype = $type;

		$result->type_requete = $soustype;
		// Lancer la 2e passe sur les criteres si la 1ere etait bonne
		if (!is_array($result->param))
			$err_b = true;
		else {
			phraser_criteres($result->param, $result);
			if (strncasecmp($soustype, TYPE_RECURSIF, strlen(TYPE_RECURSIF)) == 0) {
				$result->type_requete = TYPE_RECURSIF;
				$args = $result->param;
				array_unshift($args,
					substr($type, strlen(TYPE_RECURSIF)));
				$result->param = $args;
			}
		}

		$result->avant = public_phraser_html_dist($result->avant, $id_parent,$boucles, $descr, $result->ligne);
		$result->apres = public_phraser_html_dist($result->apres, $id_parent,$boucles, $descr, $result->ligne+$b+$m);
		$result->altern = public_phraser_html_dist($result->altern,$id_parent,$boucles, $descr, $result->ligne+$a+$m+$b);
		$result->milieu = public_phraser_html_dist($milieu, $id_boucle,$boucles, $descr, $result->ligne+$b);

		// Prevenir le generateur de code que le squelette est faux
		if ($err_b) $result->type_requete = false;

		// Verifier qu'il n'y a pas double definition
		// apres analyse des sous-parties (pas avant).
		
		if (isset($boucles[$id_boucle])) {
			$err_b_d = array('zbug_erreur_boucle_double',
				 	array('id'=>$id_boucle));
			erreur_squelette($err_b_d, $result);
		// Prevenir le generateur de code que le squelette est faux
			$boucles[$id_boucle]->type_requete = false;
		} else
			$boucles[$id_boucle] = $result;
		$all_res = phraser_champs_etendus($debut, $ligne, $all_res);
		$all_res[] = &$boucles[$id_boucle];
		$ligne += substr_count(substr($texte, 0, strpos($texte, $suite)), "\n");
		$texte = $suite;
	}

	return phraser_champs_etendus($texte, $ligne, $all_res);
}
?>
