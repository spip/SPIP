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
include_spip('inc/presentation');
include_spip('public/debug');

// http://doc.spip.org/@exec_valider_xml_dist
function exec_valider_xml_dist()
{
	if (!autoriser('ecrire')) {
		include_spip('inc/minipres');
		echo minipres();
	} else valider_xml_ok(_request('var_url'), _request('ext'));
}

// http://doc.spip.org/@valider_xml_ok
function valider_xml_ok($url, $req_ext)
{
	$url = urldecode($url);
	$titre = _T('analyse_xml');
	if (!$url) {
		$url_aff = 'http://';
		$onfocus = "this.value='';";
		$texte = $bandeau = $err = '';
	} else {
		include_spip('public/debug');
		include_spip('inc/distant');
		if (is_dir($url)) {
			if (substr($url,-1,1) !== '/') $url .='/';
			$ext = (!$req_ext) ? 'php' : $req_ext;
			$files = preg_files($url,  $ext . '$',200,false);
			if (!$files AND !$req_ext) {
				$ext = 'html';
				$files = preg_files($url, "$ext$", 200,false);
			}
			if ($files)
				$res = valider_dir($files, $ext, $url);
			else $res = _T('texte_vide');
			$bandeau = $url . '*' . $ext;
		} else {
			@list($server, $script) = preg_split('/[?]/', $url);
			if (((!$server) OR ($server == './') 
			    OR strpos($server, url_de_base()) === 0)
			    AND preg_match('/^exec=(\w+)$/', $script, $r)) {
				  $url = $r[1];
			}
			$transformer_xml = charger_fonction('valider_xml', 'inc');
			$onfocus = "this.value='" . addslashes($url) . "';";
			unset($GLOBALS['xhtml_error']);
			if (preg_match(',^[a-z][0-9a-z_]*$,i', $url)) {
				$texte = $transformer_xml(charger_fonction($url, 'exec'), true);
				$url_aff = generer_url_ecrire($url);
			} else {
				$texte = $transformer_xml(recuperer_page($url));
				$url_aff = entites_html($url);
			}
			if (isset($GLOBALS['xhtml_error'])) 
				list($texte, $err) = emboite_texte($texte);
			else {
				$err = '<h3>' . _T('spip_conforme_dtd') . '</h3>';
				list($texte, ) = emboite_texte($texte);
			}

			$res =
			"<div style='text-align: center'>" . $err . "</div>" .
			"<div style='margin: 10px; text-align: left'>" . $texte . '</div>';
			$bandeau = "<a href='$url_aff'>$url</a>";
		}
	}

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page($titre);
	$onfocus = '<input type="text" size="70" value="' .$url_aff .'" name="var_url" id="var_url" onfocus="'.$onfocus . '" />';
	$onfocus = generer_form_ecrire('valider_xml', $onfocus, " method='get'");

	echo "<h1>", $titre, '<br>', $bandeau, '</h1>',
	  "<div style='text-align: center'>", $onfocus, "</div>",
	  $res,
	  fin_page();
}

// http://doc.spip.org/@valider_resultats
function valider_resultats($res, $ext)
{
	foreach($res as $k => $l) {
		$n = preg_match_all(",(.*?)(\d+)(\D+(\d+)<br />),",
			$l[3],
			$regs,
			PREG_SET_ORDER);
		if ($n = intval($n)) {
			$x = trim(substr(textebrut($l[3]),0,16)) .'  ...';
		} else $x = '';
		$res[$k][3] = $x;
		$res[$k][4] = $l[0];
		$res[$k][0] = $n;
	}
	$i = 0;
	$table = '';
	rsort($res);
	foreach($res as $l) {
		$i++;
		$class = 'row_'.alterner($i, 'even', 'odd');
		list($nb, $script, $appel, $erreurs, $texte) = $l;
		if ($texte < 0) {
			$texte = (0- $texte);
			$color = ";color: red";
		} else  {$color = '';}

		$h = (strpos($ext,'php') ===false)
		? ($appel . '&var_mode=debug&var_mode_affiche=validation')
		  : generer_url_ecrire('valider_xml', "var_url=" . urlencode($appel));
		
		$table .= "<tr class='$class'>"
		. "<td style='text-align: right'>$nb</td>"
		. "<td style='text-align: right$color'>$texte</td>"
		. "<td style='text-align: left'>$erreurs</td>"
		. "<td>$script</td>"
		. "<td><a href='$h'>$appel</a></td>";
	}
	return "<table class='spip'>"
	  . "<tr><th>" 
	  . _T('erreur_texte')
	  . "</th><th>" 
	  . _T('taille_octets', array('taille' => ' '))
	  . "</th><th>"
	  . _T('message')
	  . "</th><th>Page</th><th>args"

	  . "</th></tr>"
	  . $table
	  . "</table>";
}

// http://doc.spip.org/@valider_script
function valider_script($transformer_xml, $f, $dir)
{
// ne pas se controler soi-meme ni l'index du repertoire

	$script = basename($f, '.php');
	if ($script == $GLOBALS['exec'] OR $script=='index')
		return array('/', $script,''); 

	$f = charger_fonction($script, $dir, true);
	if(!$f) return false;
	$page = $transformer_xml($f, true);
	$res = strlen($page);
	$appel = '';
	
	// s'il y a l'attribut minipres, le test est non significatif
	// le script necessite peut-etre des arguments, on lui en donne,
	// en appelant la fonction _args associee si elle existe
	// Si ca ne marche toujours pas, les arguments n'étaient pas bons
	// ou c'est une authentification pour action d'administration;
	// tant pis, on signale le cas par un resultat negatif

	if (strpos($page, "id='minipres'")) {
		if (!$g = charger_fonction($script . '_args', $dir, true)) {
			$res = 0 - $res;
		} else {
			unset($GLOBALS['xhtml_error']);
			$args = array(1, 'id_article', 1);
			$page2 = $transformer_xml($f=$g, $args);
			$appel = 'id_article=1&type=id_article&id=1';
			if (strpos($page2, "id='minipres'")) {
				$res = 0 - strlen($page2);
			} else $res = strlen($page2);
		}
	}
	return array($res, $script,
		     generer_url_ecrire($script, $appel, false, true));
}

// http://doc.spip.org/@valider_skel
function valider_skel($transformer_xml, $f, $dir)
{
	if (!lire_fichier ($f, $skel)) return array('/', '/', $f,''); 
	if (!strpos($skel, 'DOCTYPE')) return array('/', 'DOCTYPE?', $f,''); 
	$compiler = charger_fonction('compiler', 'public');
	$skel_code = $compiler($skel, 'tmp', 'html', $f);
	preg_match_all('/(\S*)[$]Pile[[]0[]][[].(\w+).[]]/', $skel_code, $r, PREG_SET_ORDER);
	$contexte= array();
	foreach($r as $v) {
		$val = strpos($v[1],'intval') === false
		  ? 'article'
		  : 1;
		if (!isset($contexte[$v[2]]) OR $val==1)
			$contexte[$v[2]] =  $val;
	}
	$url = '';
	unset($contexte['lang']);
	foreach($contexte as $k => $v) $url .= '&' . $k . '=' . $v;
	$skel = basename($f,'.html');
	$url = generer_url_public($skel, substr($url,1));
	$page = $transformer_xml(recuperer_page($url));
	return array(strlen($page), $skel, $url);
}

// http://doc.spip.org/@valider_dir
function valider_dir($files, $ext, $dir)
{
	$res = array();
	$transformer_xml = charger_fonction('valider_xml', 'inc');
	$valideur = $ext=='html' ? 'valider_skel' : 'valider_script';
	foreach($files as $f) {
		spip_log("valider $f");
		unset($GLOBALS['xhtml_error']);
		$val = $valideur($transformer_xml, $f, $dir);
		$val[]= isset($GLOBALS['xhtml_error']) ? $GLOBALS['xhtml_error'] : '';
		$res[]= $val;
	}
	return valider_resultats($res, $ext);
}
?>
