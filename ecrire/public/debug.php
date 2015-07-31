<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('public/decompiler');

// http://doc.spip.org/@afficher_debug_contexte
function afficher_debug_contexte($env) {

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
	$anc = '';
	$i = 1;
	foreach ($tableau_des_erreurs as $err) {
		$res .= "<tr id='req$i'><td style='text-align: right'><a href='".quote_amp($GLOBALS['REQUEST_URI'])."#spip-debug'><b>"
		  . $i
		  ."&nbsp;</b></a>\n</td><td>"
		  .join("</td>\n<td>",$err)
		  ."</td></tr>\n";

		$i++;
	}
	$cols = 1+count($err);
	$style = (_DIR_RESTREINT AND headers_sent()) ? " position: absolute; top: 90px; left: 10px; width: 200px; z-index: 1000; filter:alpha(opacity=95); -moz-opacity:0.9; opacity: 0.95;" : '';

	return "\n<table id='spip-debug' cellpadding='2'  border='1'
	style='text-align: left;$style'><tr><th style='text-align: center' colspan='$cols'>"
	. ($message ? $message : _T('zbug_erreur_squelette'))
## aide locale courte a ecrire, avec lien vers une grosse page de documentation
#		aide('erreur_compilation'),
	. "<p style='text-align: left'>$anc</p></th></tr>"
	. $res
	. "</table>";
}

// http://doc.spip.org/@chrono_requete
function chrono_requete($temps)
{
	$total = 0;
	$hors = "<i>" . _T('zbug_hors_compilation') . "</i>";
	$t = $q = $n = $d = array();
	foreach ($temps as $key => $row) {
		list($dt, $nb, $boucle, $query, $explain, $res) = $row;
		$total += $dt;
		$d[$boucle]+= $dt;
		$t[$key] = $dt;
		$q[$key] = $nb;

		$e = "<tr><th colspan='2' style='text-align:center'>"
		. (!$boucle ? $hors :
		   ($boucle . '&nbsp;(' . @++$n[$boucle] . ")"))
		. "</th></tr>"
		.  "<tr><td>Time</td><td>$dt</td></tr>" 
		.  "<tr><td>Order</td><td>$nb</td></tr>" 
		. "<tr><td>Res</td><td>$res</td></tr>" ;

		foreach($explain as $k => $v) {
			$e .= "<tr><td>$k</td><td>"
			  . str_replace(';','<br />',$v)
			  . "</td></tr>";
		}
		$e = "<br /><table border='1'>$e</table>";
		$temps[$key] = array($boucle, $e, $query);
	}
	array_multisort($t, SORT_DESC, $q, $temps);
	arsort($d);
	$i = 1;
	$t = array();
	foreach($temps as $k => $v) {
		$boucle = array_shift($v);
		$temps[$k] = $v;
		$x = "<a style='font-family: monospace' title='"
		  .  textebrut(preg_replace(',</tr>,', "\n",$v[0]))
		  . "' href='".quote_amp($GLOBALS['REQUEST_URI'])."#req$i'>"
		  . str_replace(' ', '&nbsp;', sprintf("%5d",$i))
		  . "</a>";
		if (count($t[$boucle]) % 30 == 29) $x .= "<br />";
		$t[$boucle][] = $x;
		$i++;
	}

	if ($d['']) {
		$d[$hors] = $d[''];
		$n[$hors] = $n[''];
		$t[$hors] = $t[''];
	}
	unset($d['']);
	foreach ($d as $k => $v) {
		$d[$k] =  $n[$k] . "</td><td>$k</td><td>$v</td><td>"
		  . join('',$t[$k]);
	}

	$titre = '<br />'
	  . _T('zbug_statistiques')
	  . '<br />'
	  . "<table style='text-align: left; border: 1px solid;'><tr><td>"
	  . join("</td></tr>\n<tr><td>", $d)
	  . "</td></tr>\n"
	  . (_request('var_mode_objet') ? '' : 
	     ("<tr><td>" .  count($temps) . " </td><td> " . _T('info_total') . '</td><td>' . $total . "</td></td><td></td></tr>"))
	  . "</table>";

	return (_DIR_RESTREINT ? '' : affiche_erreurs_page($GLOBALS['tableau_des_erreurs']))
	. affiche_erreurs_page($temps, $titre);
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
		$err =  "<b>"._T('avis_erreur_mysql')."</b><br /><tt>\n"
		. htmlspecialchars($query)
		. "\n<br /><span style='color: red'><b>"
		. htmlspecialchars($erreur)
		. "</b></span></tt><br />";
		
		if (isset($GLOBALS['debug']['aucasou'])) {
		  list($table, $id, $serveur) = $GLOBALS['debug']['aucasou'];
		  $err = _T('zbug_boucle') . " $id $serveur $table"
		    .   "<br />\n"
		    . $err;
		}
		$retour .=  $err . aide('erreur_mysql');
	}
}

/**
 * Definir le nombre maximal d'erreur possible dans les squelettes
 * au dela, l'affichage est arrete et les erreurs sont affichees.
 * Definir a zero permet de ne jamais bloquer, 
 * mais il faut etre tres prudent avec cette utilisation
 * 
 * Sert pour les tests unitaires
 */
define('_DEBUG_MAX_SQUELETTE_ERREURS', 4);

//
// Erreur de syntaxe des squelettes : memoriser le code fautif
//
// http://doc.spip.org/@erreur_squelette
function erreur_squelette($message='', $lieu='', $quoi='') {
	global $tableau_des_erreurs;

	if (is_array($message)) list($message, $lieu) = $message;
	elseif ($quoi) $message = erreur_requete_boucle($message, $lieu, $quoi);
#	debug_print_backtrace();exit;
	spip_log("Debug: $message | $lieu (" . $GLOBALS['fond'] .")" );
	$GLOBALS['bouton_admin_debug'] = true;
	$tableau_des_erreurs[] = array($message, $lieu);
	// Eviter les boucles infernales
	if (count($tableau_des_erreurs) > _DEBUG_MAX_SQUELETTE_ERREURS AND _DEBUG_MAX_SQUELETTE_ERREURS) {
		debug_dumpfile('','','', affiche_erreurs_page($tableau_des_erreurs));
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
  $incl = $reg[1] . '.' .  _EXTENSION_SQUELETTES . '$';

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

	$format = "<span style='float:left;display:block;width:50px;height:1px'><a id='L%d' style='background-color: white; visibility: " . ($nocpt ? 'hidden' : 'visible') . ";%s' href='#T%s' title=\"%s\">%0" . strval(@strlen(count($tableau))). "d</a></span> %s<br />\n";

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

	$ancre = md5($texte);
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
	  $res .= sprintf((($i%10) ? $format :$format10), $i, $bg, $indexmesg, $err, $i, $ligne);
	  $i++;
	}

	return "<div id='T$ancre'>"
	.'<div onclick="javascript:'
	  . "\$(this).parent().find('a').toggle();"
	  . '" title="'
	  . _T('masquer_colonne')
	  . '" >'
	  . ($nocpt ? '' : _T('info_numero_abbreviation'))
	  . "</div>
	".$res."</div>\n";
}

// l'environnement graphique du debuggueur 
// fin de course pour unhappy-few.
// http://doc.spip.org/@debug_dumpfile
function debug_dumpfile ($texte, $fonc, $type, $corps='') {
	global $debug_objets ;
	include_spip('inc/autoriser');
	if (autoriser('debug')) {
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
		if (ob_get_length()) ob_end_clean();
		$self = str_replace("\\'", '&#39;', self());
		$self = parametre_url($self,'var_mode', 'debug');
		$validation = ($var_mode_affiche == 'validation');
		echo debug_debut($fonc, $corps);
		if (!$validation) {
			echo "<div id='spip-boucles'>\n"; 
			debug_affiche_tables_des_boucles($self);
			echo "</div>";
			echo debug_affiche($fonc, $debug_objets);
		}
		if ($texte) {
			$err = "";
			$titre = $var_mode_affiche;
			if (!$validation) {
			  $titre = 'zbug_' . $titre;
			  $texte = ancre_texte($texte, array('',''));
			} else {
			  $valider = charger_fonction('valider', 'xml');
			  $res = $valider($texte);
		  // Si erreur, signaler leur nombre dans le formulaire admin
			  $debug_objets['validation'] = $res[1] ? count($res[1]):'';
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
	}
	exit;
}

function debug_affiche_tables_des_boucles($self)
{
	global $debug_objets, $spip_lang_right;

	foreach ($debug_objets['sourcefile'] as $nom_skel => $sourcefile) {
		$self2 = parametre_url($self,'var_mode_objet', $nom_skel);
		echo "<fieldset><legend>",_T('squelette'), ' ' , $sourcefile,"&nbsp;: ";
		echo "\n<a href='$self2&amp;var_mode_affiche=squelette#$nom_skel'>"._T('squelette')."</a>";
		echo "\n<a href='$self2&amp;var_mode_affiche=resultat#$nom_skel'>"._T('zbug_resultat')."</a>";
		echo "\n<a href='$self2&amp;var_mode_affiche=code#$nom_skel'>"._T('zbug_code')."</a>";
		echo "\n<a href='", 
		  str_replace('var_mode=','var_profile=', $self), "'>",
		  _T('zbug_calcul')."</a></legend>";
		echo "\n<span style='display:block;float:$spip_lang_right'>"._T('zbug_profile',array('time'=>isset($debug_objets['profile'][$sourcefile])?$debug_objets['profile'][$sourcefile]:0))."</span>";

		if (is_array($contexte = $debug_objets['contexte'][$sourcefile]))
			echo afficher_debug_contexte($contexte);

		if (isset($debug_objets['boucle']) AND is_array($debug_objets['boucle']))
			echo "<table width='100%'>\n",
				debug_affiche_boucles($debug_objets['boucle'], $nom_skel, $self),
				"</table>\n";
		echo "</fieldset>\n";
	  }
}

function debug_affiche_boucles($boucles, $nom_skel, $self)
{
	$i = 0;
	$res = '';
	$var_mode_objet = _request('var_mode_objet');
	foreach ($boucles as $objet => $boucle) {
		if (substr($objet, 0, strlen($nom_skel)) == $nom_skel) {
			$i++;
			$nom = $boucle->id_boucle;
			$req = $boucle->type_requete;
			$self2 = $self .  "&amp;var_mode_objet=" .  $objet;

			$res .= "\n<tr style='background-color: " .
			  ($i%2 ? '#e0e0f0' : '#f8f8ff') .
			  "'><td  align='right'>$i</td><td>\n" .
			  "<a  class='debug_link_boucle' href='" .
			  $self2 .
			  "&amp;var_mode_affiche=boucle#$nom_skel'>" .
			  _T('zbug_boucle') .
			  "</a></td><td>\n<a class='debug_link_boucle' href='" .
			  $self2 .
			  "&amp;var_mode_affiche=resultat#$nom_skel'>" .
			  _T('zbug_resultat') .
			  "</a></td><td>\n<a class='debug_link_resultat' href='" .
			  $self2 .
			  "&amp;var_mode_affiche=code#$nom_skel'>" .
			  _T('zbug_code') .
			  "</a></td><td>\n<a class='debug_link_resultat' href='" .
			  str_replace('var_mode=','var_profile=', $self2) .
			  "'>" .
			  _T('zbug_calcul') .
			  "</a></td><td>\n" .
			  (($var_mode_objet == $objet) ? "<b>$nom</b>" : $nom) .
			  "</td><td>\n" .
			  $req .
			  "</td></tr>";
		}
	}
	return $res;
}

// http://doc.spip.org/@debug_affiche
function debug_affiche($fonc, $tout)
{
	$objet = _request('var_mode_objet');
	$affiche = _request('var_mode_affiche');

	if (!$objet) {if ($affiche == 'squelette') $objet = $fonc;}
	if (!$objet OR !isset($tout[$affiche][$objet]) OR !$quoi = $tout[$affiche][$objet]) return '';
	$nom = $tout['boucle'][$objet]->id_boucle;

	if ($affiche == 'resultat') {
		$res = "<legend>" .$nom ."</legend>";
		$req = $tout['requete'][$objet];
		if (function_exists('traite_query')) {
		  $c = _request('connect');
		  $c = $GLOBALS['connexions'][$c ? $c : 0]['prefixe'];
		  $req = traite_query($req,'', $c);
		}
		$res .= ancre_texte($req, array(), true);
		//  formatage et affichage des resultats bruts de la requete
		$ress_req = spip_query($req);
		$brut_sql = '';
		$num = 1;
		//  eviter l'affichage de milliers de lignes
		//  personnalisation possible dans mes_options
		$max_aff = defined('_MAX_DEBUG_AFF') ? _MAX_DEBUG_AFF : 50;
		while ($retours_sql = sql_fetch($ress_req)) {
			if ($num <= $max_aff) {
				$brut_sql .= "<h3>" .($num == 1 ? $num. " sur " .sql_count($ress_req):$num). "</h3>";
				$brut_sql .= "<p>";
				foreach ($retours_sql as $key => $val) {
					$brut_sql .= "<strong>" .$key. "</strong> => " .htmlspecialchars(couper($val, 150)). "<br />\n";
				}
				$brut_sql .= "</p>";
			}
			$num++;
		}
		$res .= interdire_scripts($brut_sql);
		foreach ($quoi as $view) {
			//  ne pas afficher les $contexte_inclus
			$view = preg_replace(",<\?php.+\?[>],Uims", "", $view);
			if ($view) {
				$res .= "\n<br /><fieldset>" .interdire_scripts($view). "</fieldset>";
			}
		}

	} else if ($affiche == 'code') {
		$res =  "<legend>" .$nom ."</legend>";
		$res .= ancre_texte("<"."?php\n".$quoi."\n?".">");
	} else if ($affiche == 'boucle') {
		$res =  "<legend>" . _T('boucle') . ' ' .  $nom ."</legend>"
		. ancre_texte(decompiler_boucle($quoi));
	} else if ($affiche == 'squelette') {
		$res .=  "<legend>" .$tout['sourcefile'][$objet] ."</legend>";
		$res .= ancre_texte($tout['squelette'][$objet]);
	}

	return "<div id='debug_boucle'><fieldset id='$fonc'>$res</fieldset></div>";
}

// http://doc.spip.org/@debug_debut
function debug_debut($titre, $erreurs='')
{
	global $visiteur_session;
	include_spip('inc/headers');
	include_spip('inc/filtres');
	if (!headers_sent()) {
		http_status(503);
		http_no_cache();
	}
	lang_select($visiteur_session['lang']);
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
	  http_script('', 'jquery.js')
	  . "<link rel='stylesheet' href='".url_absolue(find_in_path('spip_admin.css'))
	  . "' type='text/css' />" .
	  "</head>\n<body style='margin:0 10px;'>" .
	  "\n<div id='spip-debug' style='position: absolute; top: 22px; z-index: 1000;height:97%;left:10px;right:10px;'>" .
	  $erreurs;
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
function emboite_texte($res, $fonc='',$self='')
{
	list($texte, $errs) = $res;
	if (!$texte)
		return array(ancre_texte('', array('','')), false);
	if (!$errs)
		return array(ancre_texte($texte, array('', '')), true);

	if (!isset($GLOBALS['debug_objets'])) {

		$colors = array('#e0e0f0', '#f8f8ff');
		$encore = count_occ($errs);
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
		foreach($errs as $r) {
			$i++;
			list($msg, $ligne, $col) = $r;
			spip_log("$r = list($msg, $ligne, $col");
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
			  . $col
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
		list($msg, $fermant, $ouvrant) = $errs[0];
		$rf = reference_boucle_debug($fermant, $fonc, $self);
		$ro = reference_boucle_debug($ouvrant, $fonc, $self);
		$err = $msg .
		  "<a href='#L" . $fermant . "'>$fermant</a>$rf<br />" .
		  "<a href='#L" . $ouvrant . "'>$ouvrant</a>$ro";
		return array(ancre_texte($texte, array(array($ouvrant), array($fermant))), $err);
	}
}

// http://doc.spip.org/@count_occ
function count_occ($regs)
{
	$encore = array();
	foreach($regs as $r) {
		if (isset($encore[$r[0]]))
			$encore[$r[0]]++;
		else $encore[$r[0]] = 1;
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
		$trace = isset($_GET['var_profile']) AND (autoriser('debug'));
	}
	return  $trace ?  microtime() : 0;
}

// http://doc.spip.org/@trace_query_end
function trace_query_end($query, $start, $result, $err, $serveur='')
{
	global $tableau_des_erreurs;
	if ($start)
		trace_query_chrono($start, microtime(), $query, $result, $serveur);
	if (!($err = sql_errno())) return $result;
	$err .= ' '.sql_error();
	if (autoriser('debug')) {
		include_spip('public/debug');
		$tableau_des_erreurs[] = array(
		_T('info_erreur_requete'). " "  .  htmlentities($query),
		"&laquo; " .  htmlentities($err)," &raquo;");
	}
	return $result;
}

// http://doc.spip.org/@trace_query_chrono
function trace_query_chrono($m1, $m2, $query, $result, $serveur='')
{
	static $tt = 0, $nb=0;
	global $tableau_des_temps;

	$x = _request('var_mode_objet');
	if (isset($GLOBALS['debug']['aucasou'])) {
		list(, $boucle, $serveur) = $GLOBALS['debug']['aucasou'];
		if ($x AND !preg_match("/$boucle\$/", $x))
			return;
		if ($serveur) $boucle .= " ($serveur)";
		$boucle = "<b>$boucle</b>";
	} else {
		if ($x) return;
		$boucle = '';
	}

	list($usec, $sec) = explode(" ", $m1);
	list($usec2, $sec2) = explode(" ", $m2);
 	$dt = $sec2 + $usec2 - $sec - $usec;
	$tt += $dt;
	$nb++;

	$q = preg_replace('/([a-z)`])\s+([A-Z])/', '$1<br />$2',htmlentities($query));
	$e =  sql_explain($query, $serveur);
	$r = str_replace('Resource id ','',(is_object($result)?get_class($result):$result));
	$tableau_des_temps[] = array($dt, $nb, $boucle, $q, $e, $r);
}
?>
