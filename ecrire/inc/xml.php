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

// http://doc.spip.org/@spip_xml_load
function spip_xml_load($fichier, $strict=true, $clean=true, $taille_max = 1048576, $datas='', $profondeur = -1){
	$contenu = "";
	if (preg_match(",^(http|ftp)://,",$fichier)){
		include_spip('inc/distant');
		$contenu = recuperer_page($fichier,false,false,$taille_max, $datas);
	}
	else lire_fichier ($fichier, $contenu);
	$arbre = array();
	if ($contenu)
		$arbre = spip_xml_parse($contenu, $strict, $clean, $profondeur);
		
	return count($arbre)?$arbre:false;
}

if (!defined('_SPIP_XML_TAG_SPLIT')) define ('_SPIP_XML_TAG_SPLIT', "{<([^:>][^>]*?)>}sS");
// http://doc.spip.org/@spip_xml_parse
function spip_xml_parse(&$texte, $strict=true, $clean=true, $profondeur = -1){
	$out = array();
  // enlever les commentaires
  $charset = 'AUTO';
  if ($clean===true){
  	if (preg_match(",<\?xml\s(.*?)encoding=['\"]?(.*?)['\"]?(\s(.*))?\?>,im",$texte,$regs))
  		$charset = $regs[2];
	  $texte = preg_replace(',<!--(.*?)-->,is','',$texte);
	  $texte = preg_replace(',<\?(.*?)\?>,is','',$texte);
		include_spip('inc/charsets');
		$clean = $charset;
		//$texte = importer_charset($texte,$charset);
  }
  if (is_string($clean)) $charset = $clean;
  $txt = $texte;

	// tant qu'il y a des tags
	$chars = preg_split(_SPIP_XML_TAG_SPLIT,$txt,2,PREG_SPLIT_DELIM_CAPTURE);
	while(count($chars)>=2){
		// tag ouvrant
		//$chars = preg_split("{<([^>]*?)>}s",$txt,2,PREG_SPLIT_DELIM_CAPTURE);
	
		// $before doit etre vide ou des espaces uniquements!
		$before = trim($chars[0]);

		if (strlen($before)>0)
			return importer_charset($texte,$charset);//$texte; // before non vide, donc on est dans du texte
	
		$tag = rtrim($chars[1]);
		$txt = $chars[2];
		
		if (strncmp($tag,'![CDATA[',8)==0) return importer_charset($texte,$charset);//$texte;
		if(substr($tag,-1)=='/'){ // self closing tag
			$tag = rtrim(substr($tag,0,strlen($tag)-1));
			$out[$tag][]="";
		}
		else{
			$closing_tag = preg_split(",\s|\t|\n|\r,",trim($tag));
			$closing_tag=reset($closing_tag);
			// tag fermant
			$ncclos = strlen("</$closing_tag>");
			$p = strpos($txt,"</$closing_tag>");
			if ($p!==FALSE  AND (strpos($txt,"<")<$p)){
				$nclose =0; $nopen = 0;
				$d = 0;
				while (
					$p!==FALSE
					AND ($morceau = substr($txt,$d,$p-$d))
					AND (($nopen+=preg_match_all("{<".preg_quote($closing_tag)."(\s*>|\s[^>]*[^/>]>)}is",$morceau,$matches,PREG_SET_ORDER))>$nclose)
					){
					$nclose++;
					$d=$p+$ncclos;
					$p = strpos($txt,"</$closing_tag>",$d);
				}
			}
			if ($p===FALSE){
				if ($strict){
					$out[$tag][]="erreur : tag fermant $tag manquant::$txt"; 
					return $out;
				}
				else return importer_charset($texte,$charset);//$texte // un tag qui constitue du texte a reporter dans $before
			}
			$content = substr($txt,0,$p);
			$txt = substr($txt,$p+$ncclos);
			if ($profondeur==0 OR strpos($content,"<")===FALSE) // eviter une recursion si pas utile
				$out[$tag][] = importer_charset($content,$charset);//$content;
			else
				$out[$tag][]=spip_xml_parse($content, $strict, $clean, $profondeur-1);
		}
		$chars = preg_split(_SPIP_XML_TAG_SPLIT,$txt,2,PREG_SPLIT_DELIM_CAPTURE);
	}
	if (count($out)&&(strlen(trim($txt))==0))
		return $out;
	else
		return importer_charset($texte,$charset);//$texte;
}

// http://doc.spip.org/@spip_xml_aplatit
function spip_xml_aplatit($arbre,$separateur = " "){
	$s = "";
	if (is_array($arbre))
		foreach($arbre as $tag=>$feuille){
			if (is_array($feuille)){
				if ($tag!==intval($tag)){
					$f = spip_xml_aplatit($feuille, $separateur);
					if (strlen($f)) {
						$tagf = explode(" ",$tag);
						$tagf = $tagf[0];
						$s.="<$tag>$f</$tagf>";
					}
					else $s.="<$tag />";
				}
				else
					$s.=spip_xml_aplatit($feuille);
				$s .= $separateur;
			}
			else
				$s.="$feuille$separateur";
		}
	return strlen($separateur) ? substr($s, 0, -strlen($separateur)) : $s;
}

// http://doc.spip.org/@spip_xml_tagname
function spip_xml_tagname($tag){
	if (preg_match(',^([a-z][\w:]*),i',$tag,$reg))
		return $reg[1];
	return "";
}
// http://doc.spip.org/@spip_xml_decompose_tag
function spip_xml_decompose_tag($tag){
	$tagname = spip_xml_tagname($tag);
	$liste = array();
	$p=strpos($tag,' ');
	$tag = substr($tag,$p);
	$p=strpos($tag,'=');
	while($p!==false){
		$attr = trim(substr($tag,0,$p));
		$tag = ltrim(substr($tag,$p+1));
		$quote = $tag{0};
		$p=strpos($tag,$quote,1);
		$cont = substr($tag,1,$p-1);
		$liste[$attr] = $cont;
		$tag = substr($tag,$p+1);
		$p=strpos($tag,'=');
	}
	return array($tagname,$liste);
}

// http://doc.spip.org/@spip_xml_match_nodes
function spip_xml_match_nodes($regexp,&$arbre,&$matches){
	if(is_array($arbre) && count($arbre))
		foreach(array_keys($arbre) as $tag){
			if (preg_match($regexp,$tag))
				$matches[$tag] = &$arbre[$tag];
			if (is_array($arbre[$tag]))
				foreach(array_keys($arbre[$tag]) as $occurences)
					spip_xml_match_nodes($regexp,$arbre[$tag][$occurences],$matches);
		}
	return (count($matches));
}


?>
