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
// et en mode validation (fausse erreur "double occurrence insert_head")
// ajouter &var_mode=debug pour voir les erreurs et en parler sur spip@rezo.net
// http://doc.spip.org/@affiche_erreurs_page
function affiche_erreurs_page($tableau_des_erreurs, $message='') {

	if (_request('exec')=='valider_xml' OR !$tableau_des_erreurs)
		return '';
	$GLOBALS['bouton_admin_debug'] = true;
	$res = '';
	$i = 1;
	foreach ($tableau_des_erreurs as $err) {
		$res .= "<tr><td style='text-align: right'>$i</td><td>"
		  .join("</td>\n<td>",$err)
		  ."</td></tr>\n";
		$i++;
	}
	$style = _DIR_RESTREINT ? "position: absolute; top: 90px; left: 10px; width: 200px; z-index: 1000; filter:alpha(opacity=95); -moz-opacity:0.9; opacity: 0.95;" : '';

	return "\n<table border='1' id='spip-debug' 
	style='text-align: left; $style'><tr><th colspan='3'>"
	. ($message ? $message : _T('zbug_erreur_squelette'))
## aide locale courte a ecrire, avec lien vers une grosse page de documentation
#		aide('erreur_compilation'),
	. '</th></tr>'
	. $res
	. "</table>";
}

// http://doc.spip.org/@chrono_requete
function chrono_requete($tableau_des_temps)
{
	$res = _DIR_RESTREINT ? '' :
		affiche_erreurs_page($GLOBALS['tableau_des_erreurs']);

	foreach ($tableau_des_temps as $key => $row) {
		  $t[$key]  = $row[0];
		  $q[$key] = $row[1];
		}
	array_multisort($t, SORT_DESC, $q, $tableau_des_temps);

	return $res . affiche_erreurs_page($tableau_des_temps,
				  _T('zbug_profile', array('time'=>'')));
}

//
// Si une boucle cree des soucis, on peut afficher la requete fautive
// avec son code d'erreur
//
// http://doc.spip.org/@erreur_requete_boucle
function erreur_requete_boucle($query, $errno, $erreur) {

	$GLOBALS['bouton_admin_debug'] = true;

	if (preg_match(',err(no|code):?[[:space:]]*([0-9]+),i', $erreur, $regs))
	  {
		$errno = $regs[2];

	  } else if (($errno == 1030 OR $errno <= 1026)
		AND preg_match(',[^[:alnum:]]([0-9]+)[^[:alnum:]],', $erreur, $regs))
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
		$err =  "<b>"._T('avis_erreur_mysql')."</b><br />\n"
		. htmlspecialchars($query)
		. "\n<br /><span style='color: red'><b>"
		. htmlspecialchars($erreur)
		. "</b></span><br />";
		
		if (isset($GLOBALS['debug']['aucasou'])) {
		  list($table, $id, $serveur) = $GLOBALS['debug']['aucasou'];
		  $err = "<blink>&lt;BOUCLE".$id."&gt;($able)</blink>"
		    .   "<br />\n"
		    . $err
		    . "<blink>&lt;/BOUCLE".$id."&gt;</blink>\n";
		}
		$retour .= "<tt>$err</tt>" . aide('erreur_mysql');
		spip_log("Erreur requete $id (".$GLOBALS['fond'].".html)");
	}

	erreur_squelette($retour);
}

//
// Erreur de syntaxe des squelettes : memoriser le code fautif
//
// http://doc.spip.org/@erreur_squelette
function erreur_squelette($message='', $lieu='') {
	global $tableau_des_erreurs;
	static $runs;

	if (is_array($message)) list($message, $lieu) = $message;

	spip_log("Erreur SQL: $message | $lieu (" . $GLOBALS['fond'] .")");
	$GLOBALS['bouton_admin_debug'] = true;
	$tableau_des_erreurs[] = array($message, $lieu);
	// Eviter les boucles infernales
	if (++$runs > 4) {
		if ($_COOKIE['spip_admin'] OR
		($GLOBALS['var_mode'] == 'debug')) {
			include_spip('inc/minipres');

			$titre = 'SPIP '
				. $GLOBALS['spip_version_affichee']
				. ' '
				. _T('admin_debug')
				. ' '
				. supprimer_tags(extraire_multi($GLOBALS['meta']['nom_site']));
			echo minipres($titre, affiche_erreurs_page($tableau_des_erreurs));
			exit;
		}
	}
}

// appelee a chaque sortie de boucle
// http://doc.spip.org/@boucle_debug_resultat
function boucle_debug_resultat ($id, $type, $resultat) {
	global $debug_objets;

	$nom = $debug_objets['courant'];
	  // ne pas memoriser plus de 3 tours d'une meme boucle
	if (count($debug_objets['resultat']["$nom$id"]) < 3)
	    $debug_objets['resultat']["$nom$id"][] = $resultat;
}

// appelee a chaque requete
// on n'a pas le nom du squelette, d'ailleurs ca n'en vient peut-etre pas

// http://doc.spip.org/@boucle_debug_requete
function boucle_debug_requete ($req) {
	global $debug_objets;

	if (isset($GLOBALS['debug']['aucasou'])) {
		list($table, $id, $serveur) = $GLOBALS['debug']['aucasou'];
		$nom = $debug_objets['courant'];
		$debug_objets['requete']["$nom$id"] = $req;
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

	if (!isset($debug_objets['principal']))
		$debug_objets['principal'] = $nom;
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
	if (is_array($debug_objets['sequence'][$id])) {
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
	    return array($nom, $boucle, $v[0] -1 + $n - $debut );
	  }
	  $debut += $y;
	 }
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
    if (preg_match(",$incl,",$v)) return $k;
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
function ancre_texte($texte, $fautifs=array(), $nocpt=false)
{
	$var_mode_ligne = _request('var_mode_ligne');
	if ($var_mode_ligne) $fautifs[]= array($var_mode_ligne);
	$res ='';

	$s = highlight_string(str_replace('</script>','</@@@@@>',$texte),true);

	$s = str_replace('/@@@@@','/script', // bug de highlight_string
		str_replace('</font>','</span>',
			str_replace('<font color="','<span style="color: ', $s)));
	if (substr($s,0,6) == '<code>') { $s=substr($s,6); $res = '<code>';}

	$s = preg_replace(',<(\w[^<>]*)>([^<]*)<br />([^<]*)</\1>,',
			  '<\1>\2</\1><br />' . "\n" . '<\1>\3</\1>',
			  $s);

	$tableau = explode("<br />", $s);

	$ancre = md5($texte);
	if (!$nocpt) {
		$format = '%0' . strval(@strlen(count($tableau))). 'd';
	} else $format ='%s';
	$format = "<tr id='L%d'><td><a class='sinumerote' style='text-align: right; background-color: white;visibility: " . ($nocpt ? 'hidden' : 'visible') . ";%s' href='#T%s' title=\"%s\">$format</a></td><td style='padding-left:10px'>%s</td></tr>\n";

	$format10=str_replace('white','lightgrey',$format);
	$formaterr="color: red;";
	$i=1;

	$flignes = array();

	$loc = array(0,0);
	foreach ($fautifs as $lc)
	  if (is_array($lc)) {
	    $l = array_shift($lc);
	    $flignes[$l] = $lc;
	  } else $flignes[$lc] = $loc;

	foreach ($tableau as $ligne) {
	  if (isset($flignes[$i])) {
	    $ligne = str_replace('&nbsp;',' ', $ligne);
	    $indexmesg = $flignes[$i][1];
	    $err = textebrut($flignes[$i][2]);
	    // tentative de pointer sur la colonne fautive;
	    // marche pas car highlight_string rajoute des entites. A revoir.
	    // $m = $flignes[$i][0];
	    //  $ligne = substr($ligne, 0, $m-1) .
	    //  sprintf($formaterr, substr($ligne,$m));
	    $bg = $formaterr; 
	  } else {$indexmesg = $ancre; $err= $bg='';}
	  $res .= sprintf((($i%10) ? $format :$format10), $i, $bg, $indexmesg, $err, ($nocpt ? '' : $i), $ligne);
	  $i++;
	}
	$js = "this.style.visibility=this.style.visibility=='visible'?'hidden' : 'visible'";

	return "\n<table id='T$ancre' style='border: 0px;'>"
	  . "\n<tr><th onclick=\"\$('.sinumerote').each(function(){ $js })\" >"
	  . _T('info_numero_abbreviation')
	  . "</th>\n<th style='text-align: center'>"
	  . _T('info_texte')
	  . "</th></tr>"
	  . $res
	  . "</table>";
}

// l'environnement graphique du debuggueur 
// http://doc.spip.org/@debug_dumpfile
function debug_dumpfile ($texte, $fonc, $type) {
	global $debug_objets, $spip_lang_right;
	$var_mode_objet = _request('var_mode_objet');
	$var_mode_affiche = _request('var_mode_affiche');

	$debug_objets[$type][$fonc . 'tout'] = $texte;

	if (!$debug_objets['sourcefile']) return;
	if ($texte && ($var_mode_objet != $fonc || $var_mode_affiche != $type))
		return;
	if (!$fonc) $fonc = $debug_objets['principal'];

	// en cas de squelette inclus,  virer le code de l'incluant:
	// - il contient souvent une Div restreignant la largeur a 3 fois rien
	// - ca fait 2 headers !
	ob_end_clean();
	$self = str_replace("\\'", '&#39;', self());
	$self = parametre_url($self,'var_mode', 'debug');
	echo debug_debut($fonc);

	if ($var_mode_affiche !== 'validation') {
		$self = parametre_url($self,'var_mode', 'debug');
	  foreach ($debug_objets['sourcefile'] as $nom_skel => $sourcefile) {
		echo "<fieldset><legend>",$sourcefile,"&nbsp;: ";
		echo "\n<a href='$self&amp;var_mode_objet=$nom_skel&amp;var_mode_affiche=squelette#$nom_skel'>"._T('squelette')."</a>";
		echo "\n<a href='$self&amp;var_mode_objet=$nom_skel&amp;var_mode_affiche=resultat#$nom_skel'>"._T('zbug_resultat')."</a>";
		echo "\n<a href='$self&amp;var_mode_objet=$nom_skel&amp;var_mode_affiche=code#$nom_skel'>"._T('zbug_code')."</a></legend>";
		echo "\n<span style='display:block;float:$spip_lang_right'>"._T('zbug_profile',array('time'=>$debug_objets['profile'][$sourcefile]))."</span>";

		if (is_array($contexte = $debug_objets['contexte'][$sourcefile]))
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
				$res .= "\n<tr style='background-color: " .
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
	  echo debug_affiche($fonc, $debug_objets, $var_mode_objet, $var_mode_affiche);
	}
	if ($texte) {

		$err = "";
		$titre = _request('var_mode_affiche');
		if ($titre != 'validation') {
			$titre = 'zbug_' . $titre;
			$texte = ancre_texte($texte, array('',''));
		} else {
		  $sax = charger_fonction('valider_xml', 'inc');
		  $res = $sax($texte);
		  list($texte, $err) = emboite_texte($res, $fonc, $self);
			if ($err === false)
				$err = _T('impossible');
			elseif ($err === true)
			  $err = _T('correcte');
			else $err = ": $err";
		}

		echo "<div id=\"debug_boucle\"><fieldset><legend>",
		  _T($titre),	       
		  ' ',
		  $err,
		  "</legend>";
		echo $texte;
		echo "</fieldset></div>";
	}
	debug_fin();
	exit;
}

// http://doc.spip.org/@debug_affiche
function debug_affiche($fonc, $tout, $objet, $affiche)
{
	if (!$objet) {if ($affiche == 'squelette') $objet = $fonc;}
	if (!$objet OR !$quoi = $tout[$affiche][$objet]) return;
	$res = "<div id=\"debug_boucle\"><fieldset>";
	if ($affiche == 'resultat') {
		$res .= "<legend>" .$tout['pretty'][$objet] ."</legend>";
		$req = $tout['requete'][$objet];
		if (function_exists('traite_query'))
			$req = traite_query($req);
		$res .= ancre_texte($req, array(), true);
		foreach ($quoi as $view) 
			if ($view) $res .= "\n<br /><fieldset>" .interdire_scripts($view) ."</fieldset>";

	} else if ($affiche == 'code') {
		$res .=  "<legend>" .$tout['pretty'][$objet] ."</legend>";
		$res .= ancre_texte("<"."?php\n".$quoi."\n?".">");
	} else if ($affiche == 'boucle') {
		$res .=  "<legend>" .$tout['pretty'][$objet] ."</legend>";
		$res .= ancre_texte($quoi);
	} else if ($affiche == 'squelette') {
		$res .=  "<legend>" .$tout['sourcefile'][$objet] ."</legend>";
		$res .= ancre_texte($tout['squelette'][$objet]);
	}
	$res .= "</fieldset></div>";
	return $res;
}

// http://doc.spip.org/@debug_debut
function debug_debut($titre)
{
	global $auteur_session;
	include_spip('inc/headers');
	include_spip('inc/filtres');
	http_no_cache();
	lang_select($auteur_session['lang']);
	return _DOCTYPE_ECRIRE .
	  html_lang_attributes() .
	  "<head>\n<title>" .
	  ('SPIP ' . $GLOBALS['spip_version_affichee'] . ' ' .
	   _T('admin_debug') . ' ' . $titre . ' (' .
	   supprimer_tags(extraire_multi($GLOBALS['meta']['nom_site']))) . 
	  ")</title>\n" .
	  "<meta http-equiv='Content-Type' content='text/html" .
	  (($c = $GLOBALS['meta']['charset']) ? "; charset=$c" : '') .
	  "' />\n" .
	  "\n<script src=\"".generer_url_public('jquery.js')
	  . "\" type=\"text/javascript\"></script>\n" .
	  "<link rel='stylesheet' href='".url_absolue(find_in_path('spip_admin.css'))
	  . "' type='text/css' />" .
	  "</head>\n<body style='margin:0 10px;'>" .
	  "\n<div id='spip-debug' style='position: absolute; top: 22px; z-index: 1000;height:97%;left:10px;right:10px;'><div id='spip-boucles'>\n"; 
}

// http://doc.spip.org/@debug_fin
function debug_fin()
{
	global $debug_objets;

	echo "\n</div>";
	include_spip('balise/formulaire_admin');
	echo inclure_balise_dynamique(
		balise_FORMULAIRE_ADMIN_dyn('spip-admin-float', $debug_objets)
	);
	echo '</body></html>';
}

// http://doc.spip.org/@emboite_texte
function emboite_texte($texte, $fonc='',$self='')
{
	if (!$texte)
		return array(ancre_texte($texte, array('','')), false);
	if (!isset($GLOBALS['xhtml_error']))
		return array(ancre_texte($texte, array('', '')), true);

	if (!isset($GLOBALS['debug_objets'])) {

		preg_match_all(",(.*?)(\d+)(\D+(\d+)<br />),",
				$GLOBALS['xhtml_error'],
				$regs,
			       PREG_SET_ORDER);

		$colors = array('#e0e0f0', '#f8f8ff');
		$encore = count_occ($regs);
		$encore2 = array();
		$fautifs = array();

		$err = '<tr><th>'
		.  _T('numero')
		. "</th><th>"
		. _T('occurrence')
		. "</th><th>"
		. _T('ligne')
		. "</th><th>"
		. _T('colonne')
		. "</th><th>"
		. _T('erreur')
		. "</th></tr>";

		$i = 0;
		foreach($regs as $r) {
			$i++;
			list(,$msg, $ligne, $fin, $col) = $r;
			if (isset($encore2[$msg]))
			  $ref = ++$encore2[$msg];
			else {$encore2[$msg] = $ref = 1;}
			$err .= "<tr  style='background-color: "
			  . $colors[$i%2]
			  . "'><td style='text-align: right'><a href='#debut_err'>"
			  . $i
			  . "</a></td><td  style='text-align: right'>"
			  . "$ref/$encore[$msg]</td>"
			  . "<td  style='text-align: right'><a href='#L"
			  . $ligne
			  . "' id='T$i'>"
			  . $ligne
			  . "</a></td><td  style='text-align: right'>"
			  . $fin
			  . "</td><td>$msg</td></tr>\n";
			$fautifs[]= array($ligne, $col, $i, $msg);
		}
		$err = "<h2 style='text-align: center'>"
		.  $i
		. "<a href='#fin_err'>"
		.  " "._T('erreur_texte')
		.  "</a></h2><table id='debut_err' style='width: 100%'>"
		. $err
		. " </table><a id='fin_err'></a>";
		return array(ancre_texte($texte, $fautifs), $err);
	} else {
		preg_match(",^(.*?)(\d+)(\D+(\d+)<br />),",
		     $GLOBALS['xhtml_error'],
		     $eregs);
		$fermant = $eregs[2];
		$ouvrant = $eregs[4];
		$rf = reference_boucle_debug($fermant, $fonc, $self);
		$ro = reference_boucle_debug($ouvrant, $fonc, $self);
		$err = $eregs[1] .
		  "<a href='#L" . $eregs[2] . "'>$eregs[2]</a>$rf" .
		  $eregs[3] ."<a href='#L" . $eregs[4] . "'>$eregs[4]</a>$ro";
		return array(ancre_texte($texte, array(array($ouvrant), array($fermant))), $err);
	}
}

// http://doc.spip.org/@count_occ
function count_occ($regs)
{
	$encore = array();
	foreach($regs as $r) {
		if (isset($encore[$r[1]]))
			$encore[$r[1]]++;
		else $encore[$r[1]] = 1;
	}
	return $encore;
}

// http://doc.spip.org/@trace_query_start
function trace_query_start()
{
	static $trace = '?';

	if ($trace === '?') {
		include_spip('inc/autoriser');
		// gare au bouclage sur calcul de droits au premier appel
		// A fortiori quand on demande une trace
		$trace = !isset($_GET['var_profile']);
		$trace = autoriser('debug');
	}
	return  $trace ?  microtime() : 0;
}

// http://doc.spip.org/@trace_query_end
function trace_query_end($query, $start, $result, $err)
{
	global $tableau_des_erreurs;
	if ($start) trace_query_chrono($start, microtime(), $query, $result);
	if (!($err = sql_errno())) return $result;
	$err .= ' '.sql_error();
	if (autoriser('voirstats')) {
		include_spip('public/debug');
		$tableau_des_erreurs[] = array(
		_T('info_erreur_requete'). " "  .  htmlentities($query),
		"&laquo; " .  htmlentities($err)," &raquo;");
	}
	return $err;
}

// http://doc.spip.org/@trace_query_chrono
function trace_query_chrono($m1, $m2, $query, $result)
{
	static $tt = 0, $nb=0;
	global $tableau_des_temps;

	list($usec, $sec) = explode(" ", $m1);
	list($usec2, $sec2) = explode(" ", $m2);
 	$dt = $sec2 + $usec2 - $sec - $usec;
	$tt += $dt;
	$nb++;

	$explain = '';
	foreach (sql_explain($query) as $k => $v) {
		$explain .= "<tr><td>$k</td><td>" .str_replace(';','<br />',$v) ."</td></tr>";
	}
	if ($explain) $explain = "<table border='1'>$explain</table>";
	$result = str_replace('Resource id ','',$result);
	$query = preg_replace('/([a-z)`])\s+([A-Z])/', '$1<br />$2',$query);
	$tableau_des_temps[] = array(sprintf("%3f", $dt), 
				     sprintf(" %3de", $nb),
				     $query,
				     $explain,
				     $result);
}
?>
