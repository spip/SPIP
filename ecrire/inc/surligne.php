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


//
if (!defined("_ECRIRE_INC_VERSION")) return;

// Ces commentaires vont etre substitue's en mode recherche
// voir les champs SURLIGNE dans inc-index-squel

define("MARQUEUR_SURLIGNE",  'debut_surligneconditionnel');
define("MARQUEUR_FSURLIGNE", 'finde_surligneconditionnel');


// http://doc.spip.org/@surligner_mots
function surligner_mots($page) {
  $surlignejs_engines = array(
      array(",".str_replace(array("/","."),array("\/","\."),$GLOBALS['meta']['adresse_site']).",i", ",recherche=([^&]+),i"), //SPIP
      array(",^http://(www\.)?google\.,i", ",q=([^&]+),i"),                            // Google
      array(",^http://(www\.)?search\.yahoo\.,i", ",p=([^&]+),i"),                     // Yahoo
      array(",^http://(www\.)?search\.msn\.,i", ",q=([^&]+),i"),                       // MSN
      array(",^http://(www\.)?search\.live\.,i", ",query=([^&]+),i"),                  // MSN Live
      array(",^http://(www\.)?search\.aol\.,i", ",userQuery=([^&]+),i"),               // AOL
      array(",^http://(www\.)?ask\.com,i", ",q=([^&]+),i"),                            // Ask.com
      array(",^http://(www\.)?altavista\.,i", ",q=([^&]+),i"),                         // AltaVista
      array(",^http://(www\.)?feedster\.,i", ",q=([^&]+),i"),                          // Feedster
      array(",^http://(www\.)?search\.lycos\.,i", ",q=([^&]+),i"),                     // Lycos
      array(",^http://(www\.)?alltheweb\.,i", ",q=([^&]+),i"),                         // AllTheWeb
      array(",^http://(www\.)?technorati\.com,i", ",([^\?\/]+)(?:\?.*)$,i"),           // Technorati  
  );

    
  $ref = $_SERVER['HTTP_REFERER'];
  foreach($surlignejs_engines as $engine) 
    if(preg_match($engine[0],$ref)) 
      if(preg_match($engine[1],$ref,$match)) {
        //good referrer found
        $script = "<script src='".find_in_path("javascript/SearchHighlight.js")."'></script>
        <script type='text/javascript'>
          jQuery(function(){
            jQuery(document).SearchHighlight({
              style_name:'spip_surligne',
              exact:'whole',
              style_name_suffix:false,
              engines:[/^".str_replace(array("/","."),array("\/","\."),$GLOBALS['meta']['adresse_site'])."/i,/recherche=([^&]+)/i],
              startHighlightComment:'".MARQUEUR_SURLIGNE."',
              stopHighlightComment:'".MARQUEUR_FSURLIGNE."'
            })
          });
        </script>";
        $page = preg_replace(",</head>,",$script."\n</head>",$page);
        break;
      }
  return $page;
}

?>
