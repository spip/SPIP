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

// http://doc.spip.org/@afficher_debug_contexte
function afficher_debug_contexte($env) {
	static $n;
	$n++;

	if (is_array($env_tab = @unserialize($env)))
		$env = $env_tab;

	$env_texte="";
	if (count($env)>0) {
		$env_texte="<div class='spip-env'>"
			. "<fieldset><legend>#ENV</legend>\n"
			. "<div><table>\n";
		foreach ($env as $nom => $valeur) {
			$env_texte .= "\n<tr><td><strong>".nl2br(entites_html($nom))
				. "</strong></td>";
			if (is_array($valeur))
			  $valeur = '(' . count($valeur) .' items) [' . join(',', $valeur) . ']';
			$env_texte .= "<td>:&nbsp;".nl2br(entites_html($valeur))
				. "</td></tr>\n";
		}
		$env_texte .= "\n</table></div>\n";
		$env_texte .= "</fieldset></div>\n";
	}
	return $env_texte;
}

// Si le code php produit des erreurs, on les affiche en surimpression
// sauf pour un visiteur non admin (lui ne voit rien de special)
// ajouter &var_mode=debug pour voir les erreurs et en parler sur spip@rezo.net
// http://doc.spip.org/@affiche_erreurs_page
function affiche_erreurs_page($tableau_des_erreurs) {

	$GLOBALS['bouton_admin_debug'] = true;
	$res = '';
	foreach ($tableau_des_erreurs as $err) {
		$res .= "<li>" .$err[0] . ", <small>".$err[1]."</small><br /></li>\n";
	}
	return "\n<div id='spip-debug' style='"
	. "position: absolute; top: 20px; left: 20px; z-index: 1000;"
	. "filter:alpha(opacity=60); -moz-opacity:0.6; opacity: 0.6;"
	. "'><ul><li>"
	. _T('zbug_erreur_squelette')
## aide locale courte a ecrire, avec lien vers une grosse page de documentation
#		aide('erreur_compilation'),
	. "<br /></li>"
	. "<ul>"
	. $res
	. "</ul></ul></div>";
}

//
// Si une boucle cree des soucis, on peut afficher la requete fautive
// avec son code d'erreur
//
// http://doc.spip.org/@erreur_requete_boucle
function erreur_requete_boucle($query, $id_boucle, $type, $errno, $erreur) {

	$GLOBALS['bouton_admin_debug'] = true;

	if (eregi('err(no|code):?[[:space:]]*([0-9]+)', $erreur, $regs))
		$errno = $regs[2];
	else if (($errno == 1030 OR $errno <= 1026)
		AND ereg('[^[:alnum:]]([0-9]+)[^[:alnum:]]', $erreur, $regs))
	$errno = $regs[1];

	// Erreur systeme
	if ($errno > 0 AND $errno < 200) {
		$retour .= "<tt><br /><br /><blink>"
		. _T('info_erreur_systeme', array('errsys'=>$errno))
		. "</blink><br />\n<b>"
		. _T('info_erreur_systeme2',
			array('script' => generer_url_ecrire('admin_repair'))) 
		. '</b><br />';
		spip_log("Erreur systeme $errno");
	}
	// Requete erronee
	else {
		$retour .= "<tt><blink>&lt;BOUCLE".$id_boucle."&gt;("
		. $type . ")</blink><br />\n"
		. "<b>"._T('avis_erreur_mysql')."</b><br />\n"
		. htmlspecialchars($query)
		. "\n<br /><font color='red'><b>".htmlspecialchars($erreur)
		. "</b></font><br />"
		. "<blink>&lt;/BOUCLE".$id_boucle."&gt;</blink></tt>\n";

		include_spip('inc/minipres');
		$retour .= aide('erreur_mysql');
		spip_log("Erreur requete $id_boucle (".$GLOBALS['fond'].".html)");
	}

	erreur_squelette($retour);
}


//
// Erreur de syntaxe des squelettes : memoriser le code fautif
//
// http://doc.spip.org/@erreur_squelette
function erreur_squelette($message='', $lieu='') {
	global $tableau_des_erreurs;
	global $auteur_session;
	static $runs;

	if (is_array($message)) list($message, $lieu) = $message;

	spip_log("Erreur squelette: $message | $lieu ("
		. $GLOBALS['fond'].".html)");
	$GLOBALS['bouton_admin_debug'] = true;
	$tableau_des_erreurs[] = array($message, $lieu);
	// Eviter les boucles infernales
	if (++$runs > 4) {
		if ($_COOKIE['spip_admin'] OR
		$auteur_session['statut'] == '0minirezo' OR
		($GLOBALS['var_mode'] == 'debug')) {
			include_spip('inc/headers');
			lang_select($auteur_session['lang']);
			http_no_cache();
			echo _DOCTYPE_ECRIRE,
			  html_lang_attributes(),
			  "<head>\n<title>",
			  ('Spip ' . $GLOBALS['spip_version_affichee'] . ' ' .
			   _T('admin_debug') . ' ' .
			   supprimer_tags(extraire_multi($GLOBALS['meta']['nom_site']))), 
			  "</title>\n</head><body>",
			  affiche_erreurs_page($tableau_des_erreurs),
			  "</body></html>";
			exit;
		}
	}
}

//
// Le debusqueur version 3
//

// appelee a chaque sortie de boucle (cf compilo.php) et a chaque requete
// dans ce derniers cas on n'a pas le nom du squelette

// http://doc.spip.org/@boucle_debug_resultat
function boucle_debug_resultat ($id, $type, $resultat) {
	global $debug_objets;

	$nom = $debug_objets['courant'];

	if ($type == 'requete') {
	  $debug_objets['requete']["$nom$id"] = $resultat;
	}
	else {
	  // ne pas memoriser plus de 3 tours d'une meme boucle
	  if (count($debug_objets['resultat']["$nom$id"]) < 3)
	    $debug_objets['resultat']["$nom$id"][] = $resultat;
	}
}

// appelee a chaque sortie de sequence (compilo.php)
// http://doc.spip.org/@debug_sequence
function debug_sequence($id, $nom, $niv, $sequence) {
	global $debug_objets;

	if (!$niv)
	  {
	    $debug_objets['sequence'][$nom.$id] = $sequence;
	  }
	$res = "";
	foreach($sequence as $v) if (is_array($v)) $res .= $v[2];
	return $res;	
}

// appelee a chaque compilation de boucle (compilo.php)
// http://doc.spip.org/@boucle_debug_compile
function boucle_debug_compile ($id, $nom, $code) {
	global $debug_objets;

	$debug_objets['code'][$nom.$id] = $code;
}

// appelee a chaque compilation de squelette (compilo.php)
// http://doc.spip.org/@squelette_debug_compile
function squelette_debug_compile($nom, $sourcefile, $code, $squelette) {
	global $debug_objets;

	$debug_objets['squelette'][$nom] = $squelette;
	$debug_objets['sourcefile'][$nom] = $sourcefile;

	if (is_array($GLOBALS['contexte_inclus']))
		$debug_objets['contexte'][$nom] = $GLOBALS['contexte_inclus'];
	else {
	  $debug_objets['contexte'][$nom] = calculer_contexte();
		if (!isset($debug_objets['principal']))
		  $debug_objets['principal'] = $nom;
	}
}

// appelee a chaque analyse syntaxique de squelette
// http://doc.spip.org/@boucle_debug
function boucle_debug ($nom, $id_parent, $id, $type, $crit, $avant, $milieu, $apres, $altern) {
	global $debug_objets;
	$debug_objets['courant'] = $nom;
	$debug_objets['parent'][$nom.$id] = $id_parent;
	$debug_objets['pretty'][$nom.$id] = 
		"BOUCLE$id($type)" . htmlspecialchars(
			preg_replace(",[\r\n],", "\\n", $crit));
	// on synthetise avec la syntaxe standard, mais "<//" pose pb 
	$debug_objets['boucle'][$nom.$id] = 
	  (!$avant ? "" : "<B$id>$avant") . 
	  "<BOUCLE$id($type)$crit>" .
	  $milieu .
	  "</BOUCLE$id>" .
	  (!$apres ? "" : "$apres</B$id>") . 
	  (!$altern ? "" : "$altern<//B$id>");
}

// http://doc.spip.org/@trouve_boucle_debug
function trouve_boucle_debug($n, $nom, $debut=0, $boucle = "")
{
	global $debug_objets;

	$id = $nom . $boucle;
	foreach($debug_objets['sequence'][$id] as $v) {
	  if (!preg_match('/^(.*)(<\?.*\?>)(.*)$/s', $v[2],$r))
	    $y = substr_count($v[2], "\n");
	  else {
	    if ($v[1][0] == '#')
	      // balise dynamique
	      $incl = $debug_objets['resultat'][$v[0]];
	    else
	      // inclusion
	      $incl = $debug_objets['squelette'][trouve_squelette_inclus($v[2])];
	    $y = substr_count($incl, "\n")
	      + substr_count($r[1], "\n") 
	      + substr_count($r[3], "\n");
	  }
	  if ($n <= ($y + $debut)) {
	    if ($v[1][0] == '?')
	      return trouve_boucle_debug($n, $nom, $debut, substr($v[1],1));
	    elseif ($v[1][0] == '!') {
	      if ($incl = trouve_squelette_inclus($v[1]))
		return trouve_boucle_debug($n, $incl, $debut);
	    }
	    return array($nom, $boucle, $v[0]);
	  }
	  $debut += $y;
	}
	return array($nom, $boucle, $n-$debut);
}	  

// http://doc.spip.org/@trouve_squelette_inclus
function trouve_squelette_inclus($script)
{
  global $debug_objets;
  preg_match('/include\(.(.*).php3?.\);/', $script, $reg);
  // si le script X.php n'est pas ecrire/public.php
  // on suppose qu'il prend le squelette X.html (pas sur, mais y a pas mieux)
  if ($reg[1] == 'ecrire/public')
    // si c'est bien ecrire/public on cherche le param 'fond'
    if (!preg_match("/'fond' => '([^']*)'/", $script, $reg))
      // a defaut on cherche le param 'page'
      if (!preg_match("/'param' => '([^']*)'/", $script, $reg))
	$reg[1] = "inconnu";
  $incl = $reg[1] . '.html$';

  foreach($debug_objets['sourcefile'] as $k => $v) {
    if (ereg($incl,$v)) return $k;
  }
  return "";
}

// http://doc.spip.org/@reference_boucle_debug
function reference_boucle_debug($n, $nom, $self)
{
  list($skel, $boucle, $ligne) = trouve_boucle_debug($n, $nom);

  if (!$boucle)
    return !$ligne ? "" :  
      (" (" .
       (($nom != $skel) ? _T('squelette_inclus_ligne') :
	_T('squelette_ligne')) .
	" <a href='$self&amp;var_mode_objet=$skel&amp;var_mode_affiche=squelette&amp;var_mode_ligne=$ligne#L$ligne'>$ligne</a>)");
  else {
  $self .= "&amp;var_mode_objet=$skel$boucle&amp;var_mode_affiche=boucle";

    return !$ligne ? " (boucle\n<a href='$self#$skel$boucle'>$boucle</a>)" :
      " (boucle $boucle ligne\n<a href='$self&amp;var_mode_ligne=$ligne#L$ligne'>$ligne</a>)";
  }
}

// affiche un texte avec numero de ligne et ancre.

// http://doc.spip.org/@ancre_texte
function ancre_texte($texte, $fautifs=array())
{
	global $var_mode_ligne;
	if ($var_mode_ligne) $fautifs[]=$var_mode_ligne;
	$s = highlight_string($texte,true);
	if (substr($s,0,6) == '<code>') { $s=substr($s,6); echo '<code>';}
	$tableau = explode("<br />", $s);

	$n = strlen(count($tableau));
	$format = "<span id='L%d' style='text-align: right;color: black;'>%0"
	. strval($n)
	. "d&nbsp;&nbsp;</span>\n";

	$format10=str_replace('black','pink',$format);
	$formaterr="<span style='background-color: pink'>%s</span>";
	$i=1;

	foreach ($tableau as $ligne) {
		echo "<br />\n",
		  sprintf((($i%10) ? $format :$format10), $i, $i),
		  sprintf(in_array($i, $fautifs) ? $formaterr : '%s', $ligne);
		$i++;
	}
}

// l'environnement graphique du debuggueur 
// http://doc.spip.org/@debug_dumpfile
function debug_dumpfile ($texte, $fonc, $type) {
	global $debug_objets, $var_mode_objet, $var_mode_affiche;
	global $auteur_session;

	$debug_objets[$type][$fonc . 'tout'] = $texte;
	if (!$debug_objets['sourcefile']) return;
	if ($texte && ($var_mode_objet != $fonc || $var_mode_affiche != $type))
		return;
	if (!$fonc) $fonc = $debug_objets['principal'];

	// en cas de squelette inclus,  virer le code de l'incluant:
	// - il contient souvent une Div restreignant la largeur a 3 fois rien
	// - ca fait 2 headers !
	ob_end_clean();

	include_spip('inc/headers');
	include_spip('inc/filtres');
	http_no_cache();
	lang_select($auteur_session['lang']);
	$self = str_replace("\\'", '&#39;', self());
	$self = parametre_url($self,'var_mode', 'debug');
	echo _DOCTYPE_ECRIRE,
	  html_lang_attributes(),
	  "<head>\n<title>",
	  ('Spip ' . $GLOBALS['spip_version_affichee'] . ' ' .
	   _T('admin_debug') . ' ' .
	   supprimer_tags(extraire_multi($GLOBALS['meta']['nom_site']))), 
	  "</title>\n",
	  "<link rel='stylesheet' href='".url_absolue(find_in_path('spip_admin.css'))
	  . "' type='text/css'>",
	  "</head>\n<body style='margin:0 10px;'>",
	  "\n<div id='spip-debug' style='position: absolute; top: 22px; z-index: 1000;height:97%;left:10px;right:10px;'><div id='spip-boucles'>\n"; 

	if ($var_mode_affiche !== 'validation') {
		$self = parametre_url($self,'var_mode', 'debug');
	  foreach ($debug_objets['sourcefile'] as $nom_skel => $sourcefile) {
		echo "<fieldset><legend>",$sourcefile,"&nbsp;: ";
		echo "\n<a href='$self&amp;var_mode_objet=$nom_skel&amp;var_mode_affiche=squelette#$nom_skel'>"._T('squelette')."</a>";
		echo "\n<a href='$self&amp;var_mode_objet=$nom_skel&amp;var_mode_affiche=resultat#$nom_skel'>"._T('zbug_resultat')."</a>";
		echo "\n<a href='$self&amp;var_mode_objet=$nom_skel&amp;var_mode_affiche=code#$nom_skel'>"._T('zbug_code')."</a></legend>";

		if (is_array($contexte = $debug_objets['contexte'][$nom_skel]))
			echo afficher_debug_contexte($contexte);

		$i = 0;
		$colors = array('#e0e0f0', '#f8f8ff');
		$res = "";
		if (is_array($debug_objets['pretty']))
		foreach ($debug_objets['pretty'] as $nom => $pretty)
			if (substr($nom, 0, strlen($nom_skel)) == $nom_skel) {
				$i++;
				$aff = "&lt;".$pretty."&gt;";
				if ($var_mode_objet == $nom)
					$aff = "<b>$aff</b>";
				$res .= "\n<tr bgcolor='" .
				  $colors[$i%2] .
				  "'><td  align='right'>$i</td><td>\n" .
				  "<a  class='debug_link_boucle' href='" .
				  $self .
				  "&amp;var_mode_objet=" .
				  $nom .
				  "&amp;var_mode_affiche=boucle#$nom_skel'>" .
				  _T('zbug_boucle') .
				  "</a></td><td>\n<a class='debug_link_boucle' href='" .
				  $self .
				  "&amp;var_mode_objet=" .
				  $nom .
				  "&amp;var_mode_affiche=resultat#$nom_skel'>" .
				  _T('zbug_resultat') .
				  "</a></td><td>\n<a class='debug_link_resultat' href='" .
				  $self .
				  "&amp;var_mode_objet=" .
				  $nom .
				  "&amp;var_mode_affiche=code#$nom_skel'>" .
				  _T('zbug_code') .
				  "</a></td><td>\n" .
				  $aff .
				  "</td></tr>";
			}
		if ($res) echo "<table width='100%'>\n$res</table>\n";
		echo "</fieldset>\n";
	  }
	  echo "</div>\n<a id='$fonc'></a>\n"; 
	  if ($var_mode_objet && ($res = $debug_objets[$var_mode_affiche][$var_mode_objet])) {
	    echo "<div id=\"debug_boucle\"><fieldset>";
	    if ($var_mode_affiche == 'resultat') {
		echo "<legend>",$debug_objets['pretty'][$var_mode_objet],"</legend>";
		ancre_texte(traite_query($debug_objets['requete'][$var_mode_objet]));
		foreach ($res as $view) 
			if ($view) echo "\n<br /><fieldset>",interdire_scripts($view),"</fieldset>";

	    } else if ($var_mode_affiche == 'code') {
		echo  "<legend>",$debug_objets['pretty'][$var_mode_objet],"</legend>";
		ancre_texte("<"."?php\n".$res."\n?".">");
	    } else if ($var_mode_affiche == 'boucle') {
		echo  "<legend>",$debug_objets['pretty'][$var_mode_objet],"</legend>";
		ancre_texte($res);
	    } else if ($var_mode_affiche == 'squelette') {
		echo  "<legend>",$debug_objets['sourcefile'][$var_mode_objet],"</legend>";
		ancre_texte($debug_objets['squelette'][$var_mode_objet]);
	    }
	    echo "</fieldset></div>";
	  }
	}
	if ($texte) {

	  $ouvrant = $fermant = $err = "";
	  $titre = $GLOBALS['var_mode_affiche'];
	  if ($titre != 'validation') {
	    $titre = 'zbug_' . $titre;
	  }

	  else if ($sax = charger_fonction('sax', 'inc')
	  AND $res = $sax($texte)) {
	     if (ereg("^[[:space:]]*([^<][^0-9]*)([0-9]*)(.*[^0-9])([0-9]*)$", $GLOBALS['xhtml_error'], $r)) {
		$fermant = $r[2];
		$ouvrant = $r[4];
		$rf = reference_boucle_debug($fermant, $fonc, $self);
		$ro = reference_boucle_debug($ouvrant, $fonc, $self);
		$err = ": " . $r[1] .
		  "<a href='#L" . $r[2] . "'>$r[2]</a>$rf" .
		  $r[3] ."<a href='#L" . $r[4] . "'>$r[4]</a>$ro";
	      } else {
		  $err = _T('correcte');
		  $texte = $res;
	      }
	  }
		else
			$err = _T('impossible');

	  echo "<div id=\"debug_boucle\"><fieldset><legend>",
	    _T($titre),	       
	    ' ',
	    $err,
	    "</legend>";
	  ancre_texte($texte, array($ouvrant, $fermant));
	  echo "</fieldset></div>";
	}
	echo "\n</div>";
	include_spip('balise/formulaire_admin');
	echo inclure_balise_dynamique(
		balise_FORMULAIRE_ADMIN_dyn('spip-admin-float', $debug_objets)
	);
	echo '</body></html>';
	exit;
}
?>
