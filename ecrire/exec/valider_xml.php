<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
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
	if (!autoriser('sauvegarder')) {
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
			$dir = (substr($url,-1,1) === '/') ? $url : "$url/";
			$ext = (!$req_ext) ? 'php' : $req_ext;
			$files = preg_files($dir,  $ext . '$',200,false);
			if (!$files AND !$req_ext) {
				$ext = 'html';
				$files = preg_files($dir, "$ext$", 200,false);
			}
			if ($files)
				$res = valider_dir($files, $ext, $url);
			else $res = _T('texte_vide');
			$bandeau = $dir . '*' . $ext;
		} else {
			if (preg_match('/^([^?]*)[?]([0-9a-z_]+)=(.*)$/', $url, $r)) {
				list(,$server, $dir, $script) = $r;
				if (((!$server) OR ($server == './') 
				    OR strpos($server, url_de_base()) === 0)
				    AND is_dir($dir))
				  $url = $script;
			} else { $dir = 'exec'; $script = $url;}
			$transformer_xml = charger_fonction('valider', 'xml');
			$onfocus = "this.value='" . addslashes($url) . "';";
			if (preg_match(',^[a-z][0-9a-z_]*$,i', $url)) {
				$res = $transformer_xml(charger_fonction($url, $dir), true);
				$url_aff = valider_pseudo_url($dir, $script);
			} else {
				$res = $transformer_xml(recuperer_page($url));
				$url_aff = entites_html($url);
			}
			if ($res[1]) {
				list($texte, $err) = emboite_texte($res);
			}
			else {
				$err = '<h3>' . _T('spip_conforme_dtd') . '</h3>';
				list($texte, ) = emboite_texte($res);
			}

			$res =
			"<div style='text-align: center'>" . $err . "</div>" .
			"<div style='margin: 10px; text-align: left'>" . $texte . '</div>';
			$bandeau = "<a href='$url_aff'>$url</a>";
		}
	}

	$commencer_page = charger_fonction('commencer_page', 'inc');
	$debut = $commencer_page($titre);
	$jq = http_script("", 'jquery.js');
	
	echo str_replace('<head>', "<head>$jq", $debut);
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
	$i = 0;
	$mode = (strpos($ext,'php') ===false);
	$table = '';
	rsort($res);
	foreach($res as $l) {
		$i++;
		$class = 'row_'.alterner($i, 'even', 'odd');
		list($nb, $texte, $erreurs, $script, $appel, $temps) = $l;
		if ($texte < 0) {
			$texte = (0- $texte);
			$color = ";color: red";
		} else  {$color = '';}

		$err = (!intval($nb)) ? '' : ($erreurs[0][0] . ($nb==1? '': '  ...'));
		$h = $mode
		? ($appel . '&var_mode=debug&var_mode_affiche=validation')
		: generer_url_ecrire('valider_xml', "var_url=" . urlencode($appel));
		
		$table .= "<tr class='$class'>"
		. "<td style='text-align: right'>$nb</td>"
		. "<td style='text-align: right$color'>$texte</td>"
		. "<td style='text-align: right'>$temps</td>"
		. "<td style='text-align: left'>$err</td>"
		. "<td>$script</td>"
		. "<td><a href='$h'>$appel</a></td>";
	}
	return "<table class='spip'>"
	  . "<tr><th>" 
	  . _T('erreur_texte')
	  . "</th><th>" 
	  . _T('taille_octets', array('taille' => ' '))
	  . "</th><th>"
	  . _T('zbug_profile', array('time' =>''))
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
		return array('/', 0, '', $script,''); 

	$f = charger_fonction($script, $dir, true);
	if(!$f) return false;
	list($texte, $err) = $transformer_xml($f, true);
	$appel = '';
	
	// s'il y a l'attribut minipres, le test est non significatif
	// le script necessite peut-etre des arguments, on lui en donne,
	// en appelant la fonction _args associee si elle existe
	// Si ca ne marche toujours pas, les arguments n'étaient pas bons
	// ou c'est une authentification pour action d'administration;
	// tant pis, on signale le cas par un resultat negatif

	if (strpos($texte, "id='minipres'")) {
		if (!$g = charger_fonction($script . '_args', $dir, true)) {
			$res = 0 - strlen($res);
		} else {
			$args = array(1, 'id_article', 1);
			list($texte, $err) = $transformer_xml($g, $args);
			$appel = 'id_article=1&type=id_article&id=1';
			if (strpos($texte, "id='minipres'")) {
				$res = 0 - strlen($texte);
			} else $res = strlen($texte);
		}
	} else $res = strlen($texte);

	$appel = valider_pseudo_url($dir, $script, $appel);
	return array(count($err), $res, $err, $script, $appel);
}

// http://doc.spip.org/@valider_pseudo_url
function valider_pseudo_url($dir, $script, $args='')
{
	return  ($dir == 'exec')
	? generer_url_ecrire($script, $args, false, true)
	: ("./?$dir=$script" . ($args ? "&$args" : ''));
}

// On essaye de valider tout squelette meme sans Doctype
// a moins qu'un Content-Type dise clairement que ce n'est pas du XML
// http://doc.spip.org/@valider_skel
function valider_skel($transformer_xml, $file, $dir)
{
	if (!lire_fichier ($file, $skel)) return array('/', '/', $file,''); 
	if (!strpos($skel, 'DOCTYPE')) {
		preg_match(",Content[-]Type: *\w+/(\S)+,", $skel, $r);
		if ($r[1] === 'css' OR $r[1] === 'plain')
			return array('/', 'DOCTYPE?', $file,'');
	}
	include_spip('public/parametrer'); // pour la fct suivante
	$skel_nom = calculer_nom_fonction_squel($skel);
	include_spip('public/assembler'); // pour recuperer_fond
	$composer = charger_fonction('composer', 'public', 'html', $file);
	$skel_code = $composer($skel, $skel_nom, 'html', $file);
	$contexte = valider_contexte($skel_code, $file);
	$page = $skel_nom(array('cache'=>''), array($contexte));
	list($texte, $err) = $transformer_xml($page['texte']);
	$res = strlen($texte);
	$script = basename($file,'.html');
	// pas de validation solitaire pour les squelettes internes, a revoir.
	if (substr_count($dir, '/') <= 1) {
		$url = generer_url_public($script, $contexte);
	} else 	$url = '';
	return array(count($err), $res, $err, $script, $url);
}

// Analyser le code pour construire un contexte plausible complet
// i.e. ce qui est fourni par $Pile[0]
// en eliminant les exceptions venant surtout des Inclure
// Il faudrait trouver une typologie pour generer un contexte parfait:
// actuellement ca produit parfois des erreurs SQL a l'appel de $skel_nom
// http://doc.spip.org/@valider_contexte
function valider_contexte($code, $file)
{
	static $exceptions = array('action', 'browser_caret', 'doublons', 'lang');
	preg_match_all('/(\S*)[$]Pile[[]0[]][[].(\w+).[]]/', $code, $r, PREG_SET_ORDER);
	$args = array();

	// evacuer les repetitions et les faux parametres
	foreach($r as $v) {
		list(,$f, $nom) = $v;
		if (!in_array($nom, $exceptions))
			@$args[$nom] .= $f;
	}
	$contexte= array(); // etudier l'ajout de:
	// 'lang' => $GLOBALS['spip_lang'],
	// 'date' => date('Y-m-d H:i:s'));
	foreach ($args as $nom => $f) {
		if (strpos($f,'intval') === false)
		  $val = 'id_article';
		else {
		  // on suppose que arg numerique => primary-key d'une table
		  // chercher laquelle et prendre un numero existant
		  $val = 0;
		  $type = (strpos($nom, 'id_') === 0)  ? substr($nom,3) : $nom;
		  $trouver_table = charger_fonction('trouver_table', 'base');
		  $table = $trouver_table(table_objet_sql($type));
		  if ($table)
		    $val = @sql_getfetsel($nom, $table['table'], '', '','',"0,1");
		    // porte de sortie si ca marche pas, 
		  if (!$val) $val = 1; 
		}
		$contexte[$nom] =  $val;
	}
	return $contexte;
}

// http://doc.spip.org/@valider_dir
function valider_dir($files, $ext, $dir)
{
	$res = array();
	$transformer_xml = charger_fonction('valider', 'xml');
	$valideur = $ext=='html' ? 'valider_skel' : 'valider_script';
	foreach($files as $f) {
		spip_timer($f);
		$val = $valideur($transformer_xml, $f, $dir);
		$n = spip_timer($f); 
		$val[]= $n;
		spip_log("validation de $f en $n secondes");
		$res[]= $val;
	}
	return valider_resultats($res, $ext);
}
?>
