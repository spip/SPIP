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

// http://doc.spip.org/@spip_xml_load
function spip_xml_load($fichier, $strict=true, $clean=true){
	$contenu = "";
	if (preg_match(",^(http|ftp)://,",$fichier)){
		include_spip('inc/distant');
		$contenu = recuperer_page($fichier);
	}
	else lire_fichier ($fichier, $contenu);
	$arbre = array();
	if ($contenu)
		$arbre = spip_xml_parse($contenu, $strict, $clean);
		
	return count($arbre)?$arbre:false;
}

// http://doc.spip.org/@spip_xml_parse
function spip_xml_parse($texte, $strict=true, $clean=true){
	$out = array();
  // enlever les commentaires
  if ($clean){
  	$charset = 'AUTO';
  	if (preg_match(",<\?xml\s(.*?)encoding=['\"]?(.*?)['\"]?(\s(.*))?\?>,im",$texte,$regs))
  		$charset = $regs[2];
	  $texte = preg_replace(',<!--(.*?)-->,is','',$texte);
	  $texte = preg_replace(',<\?(.*?)\?>,is','',$texte);
		include_spip('inc/charsets');
		$texte = importer_charset($texte,$charset);
  }
  $txt = $texte;

	// tant qu'il y a des tags
	$chars = preg_split("{<([^>]*?)>}s",$txt,2,PREG_SPLIT_DELIM_CAPTURE);
	while(count($chars)>=2){
		// tag ouvrant
		//$chars = preg_split("{<([^>]*?)>}s",$txt,2,PREG_SPLIT_DELIM_CAPTURE);
	
		// $before doit etre vide ou des espaces uniquements!
		$before = trim($chars[0]);

		if (strlen($before)>0)
			return $texte; // before non vide, donc on est dans du texte
	
		$tag = $chars[1];
		$closing_tag = explode(" ",trim($tag));$closing_tag=reset($closing_tag);
		$txt = $chars[2];
	
		if(substr($tag,-1)=='/'){ // self closing tag
			$tag = substr($tag,0,strlen($tag)-1);
			$out[$tag][]="";
		}
		else{
			// tag fermant
			$chars = preg_split("{(</".preg_quote($closing_tag).">)}s",$txt,2,PREG_SPLIT_DELIM_CAPTURE);
			if (!isset($chars[1])) { // tag fermant manquant
				if ($strict){
					$out[$tag][]="erreur : tag fermant $tag manquant::$txt"; 
					return $out;
				}
				else return $texte; // un tag qui constitue du texte a reporter dans $before
			}
			$content = $chars[0];
			$txt = $chars[2];
			if (strpos($content,"<")===FALSE) // eviter une recursion si pas utile
				$out[$tag][] = $content;
			else
				$out[$tag][]=spip_xml_parse($content, $strict, false);
		}
		$chars = preg_split("{<([^>]*?)>}s",$txt,2,PREG_SPLIT_DELIM_CAPTURE);
	}
	if (count($out)&&(strlen(trim($txt))==0))
		return $out;
	else
		return $texte;
}

// http://doc.spip.org/@spip_xml_aplatit
function spip_xml_aplatit($arbre,$separateur = " "){
	$s = "";
	if (is_array($arbre))
		foreach($arbre as $tag=>$feuille){
			if (is_array($feuille)){
				if ($tag!==intval($tag)){
					$f = spip_xml_aplatit($feuille);
					if (strlen($f)) {
						$tagf = explode(" ",$tag);
						$tagf = $tagf[0];
						$s.="<$tag>$f</$tagf>";
					}
					else $s.="<$tag/>";
				}
				else
					$s.=spip_xml_aplatit($feuille);
				$s .= $separateur;
			}				
			else
				$s.="$feuille$separateur";
		}
	return substr($s,0,strlen($s)-strlen($separateur));
}
?>