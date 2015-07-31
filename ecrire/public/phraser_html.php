<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

# Ce fichier doit IMPERATIVEMENT definir la fonction "public_phraser_html"
# qui transforme un squelette en un tableau d'objets de classe Boucle
# il est charge par un include calcule dans inc-calcul-squel
# pour permettre differentes syntaxes en entree

define('BALISE_BOUCLE', '<BOUCLE');
define('BALISE_FIN_BOUCLE', '</BOUCLE');
define('BALISE_PRE_BOUCLE', '<B');
define('BALISE_POST_BOUCLE', '</B');
define('BALISE_ALT_BOUCLE', '<//B');

define('TYPE_RECURSIF', 'boucle');
define('SPEC_BOUCLE','/\s*\(\s*([^\s)]+)(\s*[^)]*)\)/');
define('NOM_DE_BOUCLE', "[0-9]+|[-_][-_.a-zA-Z0-9]*");
# ecriture alambiquee pour rester compatible avec les hexadecimaux des vieux squelettes
define('NOM_DE_CHAMP', "#((" . NOM_DE_BOUCLE . "):)?(([A-F]*[G-Z_][A-Z_0-9]*)|[A-Z_]+)(\*{0,2})");
define('CHAMP_ETENDU', '\[([^]\[]*)\(' . NOM_DE_CHAMP . '([^[)]*\)[^]\[]*)\]');

define('BALISE_INCLURE','<INCLU[DR]E[[:space:]]*(\(([^)]*)\))?');

define('SQL_ARGS', '(\([^)]*\))');
define('CHAMP_SQL_PLUS_FONC', '`?([A-Za-z_][A-Za-z_0-9]*)' . SQL_ARGS . '?`?');

function phraser_inclure($texte, $ligne, $result) {

	while (ereg(BALISE_INCLURE, $texte, $match)) {
		$p = strpos($texte,$match[0]);
		$debut = substr($texte, 0, $p);
		if ($p) $result = phraser_idiomes($debut, $ligne, $result);
		$ligne +=  substr_count($debut, "\n");
		$champ = new Inclure;
		$champ->ligne = $ligne;
		$ligne += substr_count($match[0], "\n");
		$champ->texte = $match[2];
		$texte = substr($texte, $p+strlen($match[0]));
		// on assimile {var=val} a une liste de un argument sans fonction
		phraser_args($texte,">","",$result,$champ);
		foreach ($champ->param as $k => $v) {
		  $var = $v[1][0];
		  if ($var->type != 'texte')
			erreur_squelette(_T('zbug_parametres_inclus_incorrects'),
					 $match[0]);
		  else {
		    ereg("^([^=]*)(=)?(.*)$", $var->texte,$m);
		    if ($m[2]) {
		      $champ->param[$k][0] = $m[1];
		      $val = $m[3];
		      if (ereg('^[\'"](.*)[\'"]$', $val, $m)) $val = $m[1];
		      $champ->param[$k][1][0]->texte = $val;
		    }
		    else
		      $champ->param[$k] = array($m[1]);
		  }
		}
		$texte = substr($champ->apres,1);
		$champ->apres = "";
		$result[] = $champ;
	}
	return (($texte==="") ? $result : phraser_idiomes($texte, $ligne, $result));
}

function phraser_polyglotte($texte,$ligne, $result) {

	if (preg_match_all(",<multi>(.*)</multi>,Uims", $texte, $m, PREG_SET_ORDER))
	foreach ($m as $match) {
		$p = strpos($texte, $match[0]);
		$debut = substr($texte, 0, $p);
		if ($p) {
			$champ = new Texte;
			$champ->texte = $debut;
			$champ->ligne = $ligne;
			$result[] = $champ;
		}

		$champ = new Polyglotte;
		$ligne += substr_count($champ->texte, "\n");
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


function phraser_idiomes($texte,$ligne,$result) {

	// Reperer les balises de traduction <:toto:>
	while (eregi("<:(([a-z0-9_]+):)?([a-z0-9_]+)((\|[^:>]*)?:>)", $texte, $match)) {
		$p = strpos($texte, $match[0]);
		$debut = substr($texte, 0, $p);
		if ($p) $result = phraser_champs($debut, $ligne, $result);
		$champ = new Idiome;
		$ligne += substr_count($debut, "\n");	
		$champ->ligne = $ligne;
		$ligne += substr_count($match[0], "\n");
		$texte = substr($texte,$p+strlen($match[0]));
		$champ->nom_champ = strtolower($match[3]);
		$champ->module = $match[2] ? $match[2] : 'public/spip/ecrire';
		// pas d'imbrication pour les filtres sur langue
		phraser_args($match[5], ":", '', array(), $champ);
		$result[] = $champ;
	}
	if ($texte!=="")  $result = phraser_champs($texte,$ligne,$result);
	return $result;
}

function phraser_champs($texte,$ligne,$result) {
	while (ereg(NOM_DE_CHAMP, $texte, $match)) {
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
		  phraser_arg($suite, '', '', array(), $champ);
		}
		$texte = $suite;
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

function phraser_args($texte, $fin, $sep, $result, &$pointeur_champ) {
  $texte = ltrim($texte);
  while (($texte!=="") && strpos($fin, $texte[0]) === false) {
	$result = phraser_arg($texte, $fin, $sep, $result, $pointeur_champ);
  }
# mettre ici la suite du texte, 
# notamment pour que l'appelant vire le caractere fermant si besoin
  $pointeur_champ->apres = $texte;
  return $result;
}

function phraser_arg(&$texte, $fin, $sep, $result, &$pointeur_champ) {
      preg_match(",^(\|?[^{)|]*)(.*)$,ms", $texte, $match);
      $suite = ltrim($match[2]);
      $fonc = trim($match[1]);
      if ($fonc && $fonc[0] == "|") $fonc = ltrim(substr($fonc,1));
      $res = array($fonc);
      $args = $suite ;
      // cas du filtre sans argument ou du critere /
      if (($suite[0] != '{')  || ($fonc  && $fonc[0] == '/'))
	{ 
	  // si pas d'argument, alors il faut une fonction ou un double |
	  if (!$match[1])
	    erreur_squelette(_T('zbug_info_erreur_squelette'), $texte);
	} else {
	$args = ltrim(substr($suite,1)); 
	$collecte = array();
	while ($args && $args[0] != '}') {
		if ($args[0] == '"')
			preg_match ('/^(")([^"]*)(")(.*)$/ms', $args, $regs);
		else if ($args[0] == "'")
			preg_match ("/^(')([^']*)(')(.*)$/ms", $args, $regs);
		else {
		  preg_match("/^([[:space:]]*)([^,([{}]*([(\[{][^])}]*[])}])?[^$fin,}]*)([,}$fin].*)$/ms", $args, $regs);
		  if (!strlen($regs[2]))
		    {
		      erreur_squelette(_T('zbug_info_erreur_squelette'), $args);
		      $args = '';
		      exit;
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
		  if (!ereg(NOM_DE_CHAMP ."[{|]", $arg, $r)) {
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
		    if (ereg('^(.*)\($', $pred, $m))
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
		    phraser_args($rec, $par, $sep, array(), $champ);
		    $args = $champ->apres ;
		    $champ->apres = '';
		    if ($par==')') $args = substr($args,1);
		    $collecte[] = $champ;
		    $result[] = $champ;
		  }
		}
		if ($args[0] == ',') {
		  $args = ltrim(substr($args,1));
		  if ($collecte)
		    {$res[] = $collecte; $collecte = array();}
		}
	}
	if ($collecte) {$res[] = $collecte; $collecte = array();}
	$args = substr($args,1);
      }
      $n = strlen($suite) - strlen($args);
      if ($fonc || count($res) > 1) $pointeur_champ->param[] = $res;
      // pour les balises avec faux filtres qui boudent ce dur larbeur
      $pointeur_champ->fonctions[] = array($fonc, substr($suite, 0, $n));
      $texte = ltrim($args);
      return $result;
}


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

function phraser_champs_interieurs($texte, $ligne, $sep, $result) {
	$i = 0; // en fait count($result)
	$x = "";

	while (true) {
		$j=$i;
		$n = $ligne;
		while (ereg(CHAMP_ETENDU, $texte, $match)) {
			$p = strpos($texte, $match[0]);
			$debut = substr($texte, 0, $p);
			if ($p) {
				$result[$i] = $debut;
				$i++;
			}
			$champ = new Champ;
			// ca ne marche pas encore en cas de champ imbrique
			$champ->ligne = $x ? 0 :($n+substr_count($debut, "\n"));
			$champ->nom_boucle = $match[3];
			$champ->nom_champ = $match[4];
			$champ->etoile = $match[6];
			// phraser_args indiquera ou commence apres
			$result = phraser_args($match[7], ")", $sep, $result, $champ);
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
		if (ereg(CHAMP_ETENDU, $x))
			$texte = $x;
		else
			return phraser_champs_exterieurs($x, $ligne, $sep, $result);
	}
}

// analyse des criteres de boucle, 

function phraser_criteres($params, &$result) {

	$args = array();
	$type = $result->type_requete;
	$doublons = array();
	foreach($params as $v) {
		$var = $v[1][0];
		$param = ($var->type != 'texte') ? "" : $var->texte;
		if ((count($v) > 2) && (!eregi("[^A-Za-z]IN[^A-Za-z]",$param)))
		  {
// plus d'un argument et pas le critere IN:
// detecter comme on peut si c'est le critere implicite LIMIT debut, fin

			if (($var->type != 'texte') ||
			    (strpos("0123456789-", $param[strlen($param)-1])
			     !== false)) {
			  $op = ',';
			  $not = "";
			} else {
			  preg_match("/^([!]?)([a-zA-Z][a-zA-Z0-9]*)[[:space:]]*(.*)$/ms", $param, $m);
			  $op = $m[2];
			  $not = $m[1];
			  if ($m[3]) $v[1][0]->texte = $m[3]; else array_shift($v[1]);
			}
			array_shift($v);
			$crit = new Critere;
			$crit->op = $op;
			$crit->not = $not;
			$crit->param = $v;
			$args[] = $crit;
		  } else {
		  if ($var->type != 'texte') {
		    // cas 1 seul arg ne commencant pas par du texte brut: 
		    // erreur ou critere infixe "/"
		    if (($v[1][1]->type != 'texte') || (trim($v[1][1]->texte) !='/'))
		      erreur_squelette('criteres',$var->nom_champ);
		    else {
		      $crit = new Critere;
		      $crit->op = '/';
		      $crit->not = "";
		      $crit->param = array(array($v[1][0]),array($v[1][2]));
		      $args[] = $crit;
		    }
		  } else {
	// traiter qq lexemes particuliers pour faciliter la suite
	// les separateurs
			if ($var->apres)
				$result->separateur[] = $param;
			elseif (($param == 'tout') OR ($param == 'tous'))
				$result->tout = true;
			elseif ($param == 'plat') 
				$result->plat = true;

	// Boucle hierarchie, analyser le critere id_article - id_rubrique
	// - id_syndic, afin, dans les cas autres que {id_rubrique}, de
	// forcer {tout} pour avoir la rubrique mere...

			elseif (($type == 'hierarchie') &&
				($param == 'id_article' OR $param == 'id_syndic'))
				$result->tout = true;
			elseif (($type == 'hierarchie') && ($param == 'id_rubrique'))
				{;}
			else { 
			  // pas d'emplacement statique, faut un dynamique
			  /// mais il y a 2 cas qui ont les 2 !
			  if (($param == 'unique') || (ereg('^!?doublons *', $param)))
			    {
			      // cette variable sera inseree dans le code
			      // et son nom sert d'indicateur des maintenant
			      $result->doublons = '$doublons_index';
			      if ($param == 'unique') $param = 'doublons';
			    }
			  elseif ($param == 'recherche')
			    // meme chose (a cause de #nom_de_boucle:URL_*)
			      $result->hash = true;
			  if (ereg('^ *([0-9-]+) *(/) *(.+) *$', $param, $m)) {
			    $crit = phraser_critere_infixe($m[1], $m[3],$v, '/', '', '');
			  } elseif (ereg('^(' . CHAMP_SQL_PLUS_FONC . 
					 ')[[:space:]]*(\??)(!?)(<=?|>=?|==?|IN)(.*)$', $param, $m)) {
			    $a2 = trim($m[7]);
			    if (ereg("^'.*'$", $a2) OR ereg('^".*"$', $a2))
			      $a2 = substr($a2,1,-1);
			    $crit = phraser_critere_infixe($m[1], $a2, $v,
							   (($m[1] == 'lang_select') ? $m[1] : $m[6]),
							   $m[5], $m[4]);
			  } elseif (preg_match("/^([!]?)\s*(" .
					       CHAMP_SQL_PLUS_FONC .
					       ")\s*(\??)(.*)$/ism", $param, $m)) {
		  // contient aussi les comparaisons implicites !

			    array_shift($v);
			    if ($m[6])
			      $v[0][0]->texte = $m[6];
			    else {
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
			    erreur_squelette(_T('zbug_critere_inconnu',
						array('critere' => $param)));
			  }
			  if ((!ereg('^!?doublons *', $param)) || $crit->not)
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
	$result->criteres = $args;
}

function phraser_critere_infixe($arg1, $arg2, $args, $op, $not, $cond)
{
	$args[0] = new Texte;
	$args[0]->texte = $arg1;
	$args[0] = array($args[0]);
	$args[1][0]->texte = $arg2;
	$crit = new Critere;
	$crit->op = $op;
	$crit->not = $not;
	$crit->cond = $cond;
	$crit->param = $args;
	return $crit;
}

function public_phraser_html($texte, $id_parent, &$boucles, $nom, $ligne=1) {

	$all_res = array();

	while (($p = strpos($texte, BALISE_BOUCLE)) !== false) {

		$result = new Boucle;
		$result->id_parent = $id_parent;

# attention: reperer la premiere des 2 balises: pre_boucle ou boucle

		$n = ereg(BALISE_PRE_BOUCLE . '[0-9_]', $texte, $r);
		if ($n) $n = strpos($texte, $r[0]);
		if (($n === false) || ($n > $p)) {
		  $debut = substr($texte, 0, $p);
		  $milieu = substr($texte, $p);
		  $k = strpos($milieu, '(');
		  $id_boucle = trim(substr($milieu,
					   strlen(BALISE_BOUCLE),
					   $k - strlen(BALISE_BOUCLE)));
		  $milieu = substr($milieu, $k);

		  /* a adapter: si $n pointe sur $id_boucle ...
		if (strpos($milieu, $s)) {
			erreur_squelette(_T('zbug_erreur_boucle_syntaxe'),
				$id_boucle . 
				_T('zbug_balise_b_aval'));
		}
		  */
		} else {
		  $debut = substr($texte, 0, $n);
		  $milieu = substr($texte, $n);
		  $k = strpos($milieu, '>');
		  $id_boucle = substr($milieu,
				       strlen(BALISE_PRE_BOUCLE),
				       $k - strlen(BALISE_PRE_BOUCLE));

		  if (!ereg(BALISE_BOUCLE . $id_boucle . "[[:space:]]*\(", $milieu, $r))
		    erreur_squelette((_T('zbug_erreur_boucle_syntaxe')), $id_boucle);
		  $p = strpos($milieu, $r[0]);
		  $result->avant = substr($milieu, $k+1, $p-$k-1);
		  $milieu = substr($milieu, $p+strlen($id_boucle)+strlen(BALISE_BOUCLE));
		}
		$result->id_boucle = $id_boucle;

		preg_match(SPEC_BOUCLE, $milieu, $match);
                $milieu = substr($milieu, strlen($match[0]));
		$type = $match[1];
		$jointures = trim($match[2]);
		if ($jointures) {
			$result->jointures = preg_split("/\s+/",$jointures);
			$result->jointures_explicites = $jointures;
		}

		if ($p = strpos($type, ':'))
		  {
		    $result->sql_serveur = substr($type,0,$p);
		    $soustype = strtolower(substr($type,$p+1));
		  }
		else
		  $soustype = strtolower($type);

		if ($soustype == 'sites') $soustype = 'syndication' ; # alias
		      
		//
		// analyser les criteres et distinguer la boucle recursive
		//
		if (substr($soustype, 0, 6) == TYPE_RECURSIF) {
			$result->type_requete = TYPE_RECURSIF;
			$result->param[0] = substr($type, strlen(TYPE_RECURSIF));
			$milieu = substr($milieu, strpos($milieu, '>')+1);
			$params = "";
		} else {
			$result->type_requete = $soustype;
			phraser_args($milieu,">","",$all_res,$result);
			$params = substr($milieu,0,strpos($milieu,$result->apres));
			$milieu = substr($result->apres,1);
			$result->apres = "";
			phraser_criteres($result->param, $result);
		}

		//
		// Recuperer la fin :
		//
		$s = BALISE_FIN_BOUCLE . $id_boucle . ">";
		$p = strpos($milieu, $s);
		if ($p === false) {
			erreur_squelette(_T('zbug_erreur_boucle_syntaxe'),
					 _T('zbug_erreur_boucle_fermant',
						array('id'=>$id_boucle)));
		}

		$suite = substr($milieu, $p + strlen($s));
		$milieu = substr($milieu, 0, $p);
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

		// envoyer la boucle au debugueur
		if ($GLOBALS['var_mode']== 'debug') {
		  boucle_debug ($nom, $id_parent, $id_boucle, 
				$type . $jointures,
				$params,
				$result->avant,
				$milieu,
				$result->apres,
				$result->altern);
		}

		$result->avant = public_phraser_html($result->avant, $id_parent,$boucles, $nom, $result->ligne);
		$result->apres = public_phraser_html($result->apres, $id_parent,$boucles, $nom, $result->ligne+$b+$m);
		$result->altern = public_phraser_html($result->altern,$id_parent,$boucles, $nom, $result->ligne+$a+$m+$b);
		$result->milieu = public_phraser_html($milieu, $id_boucle,$boucles, $nom, $result->ligne+$b);

		if (isset($boucles[$id_boucle])) {
			erreur_squelette(_T('zbug_erreur_boucle_syntaxe'),
					 _T('zbug_erreur_boucle_double',
					 	array('id'=>$id_boucle)));
		} else
			$boucles[$id_boucle] = $result;
		$all_res = phraser_champs_etendus($debut, $ligne, $all_res);
		$all_res[] = $result;
		$ligne += substr_count(substr($texte, 0, strpos($texte, $suite)), "\n");
		$texte = $suite;
	}

	return phraser_champs_etendus($texte, $ligne, $all_res);
}
?>
