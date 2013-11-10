<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Fonctions de minification
 * 
 * @package SPIP\Compresseur\Minifier
 */
if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Minifier un contenu CSS
 * 
 * Si $options est vide on utilise la methode regexp simple
 *
 * Si $options est une chaine non vide elle definit un media à appliquer
 * à la css. Si la css ne contient aucun @media ni @import, on encapsule tout
 * dans "@media $option {...}" et on utilise regexp sinon on utilise csstidy
 * pour ne pas faire d'erreur, mais c'est 12 fois plus lent
 *
 * Si $options sous forme de array() on passe par csstidy pour parser le code
 * et produire un contenu plus compact et prefixé eventuellement par un @media
 * options disponibles :
 * - string media : media qui seront utilisés pour encapsuler par @media
 *   les selecteurs sans media
 * - string template : format de sortie parmi 'low','default','high','highest'
 * 
 * @param string $contenu
 *     Contenu CSS
 * @param mixed $options
 *     Options de minification
 * @return string
 *     Contenu CSS minifié
 */
function minifier_css ($contenu, $options='') {
	if (is_string($options) AND $options){
		if ($options=="all") // facile : media all => ne rien preciser
			$options = "";
		elseif (
					strpos($contenu,"@media")==false
			AND strpos($contenu,"@import")==false
			AND strpos($contenu,"@font-face")==false
			){
			$contenu = "@media $options {\n$contenu\n}\n";
			$options="";
		}
		else
			$options = array('media'=>$options);
	}
	if (!is_array($options)){

		// nettoyer la css de tout ce qui sert pas
		// pas de commentaires
		$contenu = preg_replace(",/\*.*\*/,Ums","",$contenu);
		$contenu = preg_replace(",\s//[^\n]*\n,Ums","",$contenu);
		// espaces autour des retour lignes
		$contenu = str_replace("\r\n","\n",$contenu);
		$contenu = preg_replace(",\s+\n,ms","\n",$contenu);
		$contenu = preg_replace(",\n\s+,ms","\n",$contenu);
		// pas d'espaces consecutifs
		$contenu = preg_replace(",\s(?=\s),Ums","",$contenu);
		// pas d'espaces avant et apres { ; ,
		$contenu = preg_replace("/\s?({|;|,)\s?/ms","$1",$contenu);
		// supprimer les espaces devant : sauf si suivi d'une lettre (:after, :first...)
		$contenu = preg_replace("/\s:([^a-z])/ims",":$1",$contenu);
		// supprimer les espaces apres :
		$contenu = preg_replace("/:\s/ms",":",$contenu);
		// pas d'espaces devant }
		$contenu = preg_replace("/\s}/ms","}",$contenu);

		// ni de point virgule sur la derniere declaration
		$contenu = preg_replace("/;}/ms","}",$contenu);
		// pas d'espace avant !important
		$contenu = preg_replace("/\s!\s?important/ms","!important",$contenu);
		// passser les codes couleurs en 3 car si possible
		// uniquement si non precedees d'un [="'] ce qui indique qu'on est dans un filter(xx=#?...)
		$contenu = preg_replace(";([:\s,(])#([0-9a-f])(\\2)([0-9a-f])(\\4)([0-9a-f])(\\6)(?=[^\w\-]);i","$1#$2$4$6",$contenu);
		// remplacer font-weight:bold par font-weight:700
		$contenu = preg_replace("/font-weight:bold(?!er)/ims","font-weight:700",$contenu);
		// remplacer font-weight:normal par font-weight:400
		$contenu = preg_replace("/font-weight:normal/ims","font-weight:400",$contenu);

		// enlever le 0 des unites decimales
		$contenu = preg_replace("/0[.]([0-9]+em)/ims",".$1",$contenu);
		// supprimer les declarations vides
		$contenu = preg_replace(",(^|})([^{}]*){},Ums","$1",$contenu);
		// zero est zero, quelle que soit l'unite
		$contenu = preg_replace("/([^0-9.]0)(em|px|pt|%)/ms","$1",$contenu);

		// renommer les couleurs par leurs versions courtes quand c'est possible
		$colors = array(
			'source'=>array('black','fuchsia','white','yellow','#800000','#ffa500','#808000','#800080','#008000','#000080','#008080','#c0c0c0','#808080','#f00'),
			'replace'=>array('#000' ,'#F0F'   ,'#FFF' ,'#FF0'  ,'maroon' ,'orange' ,'olive'  ,'purple' ,'green'  ,'navy'   ,'teal'   ,'silver' ,'gray'   ,'red')
		);
		foreach($colors['source'] as $k=>$v){
			$colors['source'][$k]=";([:\s,(])".$v."(?=[^\w\-]);ms";
			$colors['replace'][$k] = "$1".$colors['replace'][$k];
		}
		$contenu = preg_replace($colors['source'],$colors['replace'],$contenu);

		// raccourcir les padding qui le peuvent (sur 3 ou 2 valeurs)
		$contenu = preg_replace(",padding:([^\s;}]+)\s([^\s;}]+)\s([^\s;}]+)\s(\\2),ims","padding:$1 $2 $3",$contenu);
		$contenu = preg_replace(",padding:([^\s;}]+)\s([^\s;}]+)\s(\\1)([;}!]),ims","padding:$1 $2$4",$contenu);

		// raccourcir les margin qui le peuvent (sur 3 ou 2 valeurs)
		$contenu = preg_replace(",margin:([^\s;}]+)\s([^\s;}]+)\s([^\s;}]+)\s(\\2),ims","margin:$1 $2 $3",$contenu);
		$contenu = preg_replace(",margin:([^\s;}]+)\s([^\s;}]+)\s(\\1)([;}!]),ims","margin:$1 $2$4",$contenu);

		$contenu = trim($contenu);

		return $contenu;
	}
	else {
		// compression avancee en utilisant csstidy
		// beaucoup plus lent, mais necessaire pour placer des @media la ou il faut
		// si il y a deja des @media ou des @import

		// modele de sortie plus ou moins compact
		$template = 'high';
		if (isset($options['template']) AND in_array($options['template'],array('low','default','high','highest')))
			$template = $options['template'];
		// @media eventuel pour prefixe toutes les css
		// et regrouper plusieurs css entre elles
		$media = "";
		if (isset($options['media']))
			$media = "@media ".$options['media']." ";

		include_spip("lib/csstidy/class.csstidy");
		$css = new csstidy();

		// essayer d'optimiser les font, margin, padding avec des ecritures raccourcies
		$css->set_cfg('optimise_shorthands',2);
		$css->set_cfg('template',$template);
		$css->parse($contenu);
		return $css->print->plain($media);
	}
}


/**
 * Compacte du javascript grace a Dean Edward's JavaScriptPacker
 *
 * Bench du 15/11/2010 sur jQuery.js :
 * JSMIN (https://github.com/rgrove/jsmin-php/)
 *      61% de la taille initiale / 2 895 ms
 * JavaScriptPacker
 *  62% de la taille initiale / 752 ms
 *
 * Closure Compiler
 *  44% de la taille initiale / 3 785 ms
 *
 * JavaScriptPacker + Closure Compiler
 *  43% de la taille initiale / 3 100 ms au total
 *
 * Il est donc plus rapide&efficace
 * - de packer d'abord en local avec JavaScriptPacker
 * - d'envoyer ensuite au closure compiler
 * Cela permet en outre d'avoir un niveau de compression decent si closure
 * compiler echoue
 *
 * Dans cette fonction on ne fait que le compactage local,
 * l'appel a closure compiler est fait une unique fois pour tous les js concatene
 * afin d'eviter les requetes externes
 *
 * @param string $flux
 *     Contenu JS
 * @return string
 *     Contenu JS minifié
 */
function minifier_js($flux) {
	if (!strlen($flux))
		return $flux;

	include_spip('lib/JavascriptPacker/class.JavaScriptPacker');
	$packer = new JavaScriptPacker($flux, 0, true, false);

	// en cas d'echec (?) renvoyer l'original
	if (!strlen($t = $packer->pack())) {
		spip_log('erreur de minifier_js',_LOG_INFO_IMPORTANTE);
		return $flux;
	}
	return $t;
}


/**
 * Minification additionnelle de JS
 * 
 * Compacter du javascript plus intensivement
 * grâce au google closure compiler
 *
 * @param string $content
 *     Contenu JS à compresser
 * @param bool $file
 *     Indique si $content est ou non un fichier, et retourne un fichier dans ce dernier cas
 *     Si $file est une chaîne, c'est un nom de ficher sous lequel on écrit aussi le fichier destination
 * @return string
 *     Contenu JS compressé
 */
function minifier_encore_js($content,$file=false) {
	# Closure Compiler n'accepte pas des POST plus gros que 200 000 octets
	# au-dela il faut stocker dans un fichier, et envoyer l'url du fichier
	# dans code_url ; en localhost ca ne marche evidemment pas
	if ($file) {
		$nom = $content;
		lire_fichier($nom, $content);
		$dest = dirname($nom).'/'.md5($content.$file).'.js';
		if (file_exists($dest) AND (!is_string($file) OR file_exists($file)))
			if (filesize($dest))
				return is_string($file)?$file:$dest;
			else {
				spip_log("minifier_encore_js: Fichier $dest vide",_LOG_INFO);
				return $nom;
			}
	}

	if (!$file AND strlen($content)>200000)
		return $content;

	include_spip('inc/distant');

	$datas=array(
		'output_format' => 'text',
		'output_info' => 'compiled_code',
		'compilation_level' => 'SIMPLE_OPTIMIZATIONS', // 'SIMPLE_OPTIMIZATIONS', 'WHITESPACE_ONLY', 'ADVANCED_OPTIMIZATIONS'
	);
	if (!$file OR strlen($content) < 200000)
		$datas['js_code'] = $content;
	else
		$datas['url_code'] = url_absolue($nom);

	$cc = recuperer_page('http://closure-compiler.appspot.com/compile',
		$trans=false, $get_headers=false,
		$taille_max = null,
		$datas,
		$boundary = -1);

	if ($cc AND !preg_match(',^\s*Error,', $cc)) {
		spip_log('Closure Compiler: success');
		$cc = "/* $nom + Closure Compiler */\n".$cc;
		if ($file){
			ecrire_fichier ($dest, $cc, true);
			ecrire_fichier ("$dest.gz", $cc, true);
			$content = $dest;
		  if (is_string($file)){
			  ecrire_fichier ($file, $cc, true);
			  spip_clearstatcache(true,$file);
			  ecrire_fichier ("$file.gz", $cc, true);
		    $content = $file;
		  }
		}
		else
			$content = &$cc;
	} else {
		if ($file){
			spip_log("minifier_encore_js:Echec appel Closure Compiler. Ecriture fichier $dest vide",_LOG_INFO_IMPORTANTE);
			ecrire_fichier ($dest, '', true);
		}
	}
	return $content;
}


/**
 * Une callback applicable sur chaque balise link qui minifie un fichier CSS
 * 
 * @param string $contenu
 * @param  string $balise
 * @return string
 */
function callback_minifier_css_file($contenu, $balise){
	return minifier_css($contenu, extraire_attribut($balise,'media'));
}

/**
 * Une callback applicable sur chaque balise script qui minifie un JS
 * 
 * @param string $contenu
 * @param  string $balise
 * @return string
 */
function callback_minifier_js_file($contenu, $balise){
	return minifier_js($contenu);
}


/**
 * Minifier du HTML
 * 
 * @param string $flux  HTML à compresser
 * @return string       HTML compressé
 */
function minifier_html($flux){

	// si pas de contenu ni de balise html, ne rien faire
	if (!strlen($flux) OR strpos($flux,"<")===FALSE)
		return $flux;

	static $options = null;
	if (is_null($options)){
		$options = array();
		if ($GLOBALS['meta']['auto_compress_css'] == 'oui')
			$options['cssMinifier'] = 'minifier_css';
		if ($GLOBALS['meta']['auto_compress_js'] == 'oui')
			$options['jsMinifier'] = 'minifier_js';
		include_spip('lib/minify_html/class.minify_html');
	}

	return Minify_HTML_SPIP::minify($flux,$options);
}
