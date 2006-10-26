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

function spip_xml_load($fichier, $clean=true){
	$contenu = "";
	if (preg_match(",^(http|ftp)://,",$fichier)){
		include_spip('inc_distant');
		$contenu = recuperer_page($fichier);
	}
	else lire_fichier ($fichier, $contenu);
	$arbre = array();
	if ($contenu)
		$arbre = spip_xml_parse($contenu);
		
	return count($arbre)?$arbre:false;
}

function spip_xml_parse($texte, $clean=true){
	$out = array();
  // enlever les commentaires
  if ($clean){
	  $texte = preg_replace(',<!--(.*?)-->,is','',$texte);
	  $texte = preg_replace(',<\?(.*?)\?>,is','',$texte);
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
			$txt = trim($txt);
		}
		else{
			// tag fermant
			$chars = preg_split("{(</".preg_quote($closing_tag).">)}s",$txt,2,PREG_SPLIT_DELIM_CAPTURE);
			if (!isset($chars[1])) { // tag fermant manquant
				$out[$tag][]="erreur : tag fermant $tag manquant::$txt"; 
				return $out;
			}
			$content = $chars[0];
			$txt = trim($chars[2]);
			if (strpos($content,"<")===FALSE) // eviter une recursion si pas utile
				$out[$tag][] = $content;
			else
				$out[$tag][]=spip_xml_parse($content, false);
		}
		$chars = preg_split("{<([^>]*?)>}s",$txt,2,PREG_SPLIT_DELIM_CAPTURE);
	}
	if (count($out)&&(strlen($txt)==0))
		return $out;
	else
		return $texte;
}

function spip_xml_aplatit($arbre,$separateur = " "){
	$s = "";
	if (is_array($arbre))
		foreach($arbre as $tag=>$feuille){
			if (is_array($feuille)){
				if ($tag!==intval($tag)){
					$f = spip_xml_aplatit($feuille);
					if (strlen($f))	$s.="<$tag>$f</$tag>";
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