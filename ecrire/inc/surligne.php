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


//
if (!defined('_ECRIRE_INC_VERSION')) return;

// Ces commentaires vont etre substitue's en mode recherche
// voir les champs SURLIGNE dans inc-index-squel

define('MARQUEUR_SURLIGNE',  'debut_surligneconditionnel');
define('MARQUEUR_FSURLIGNE', 'finde_surligneconditionnel');


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
  //avoid a js injection
  if($surcharge_surligne=_request("var_recherche")) {
    $surcharge_surligne = preg_replace(",(?<!\\\\)((?:(?>\\\\){2})*)('),","$1\\\\$2",$surcharge_surligne);
    $surcharge_surligne = str_replace("\\","\\\\",$surcharge_surligne);
    if($GLOBALS['meta']['charset']=='utf-8') {
      include_spip('inc/charsets');
      if(!is_utf8($surcharge_surligne)) $surcharge_surligne = utf8_encode($surcharge_surligne);
    }  
  }
  foreach($surlignejs_engines as $engine) 
    if($surcharge_surligne || (preg_match($engine[0],$ref) && preg_match($engine[1],$ref))) { 
      
      //good referrer found or var_recherche is not null
      include_spip('inc/filtres');
      $script = "
      <script type='text/javascript' src='".url_absolue(find_in_path('javascript/SearchHighlight.js'))."'></script>
      <script type='text/javascript'>/*<![CDATA[*/
      if (window.jQuery)
        (function(\$){\$(function(){
          \$(document).SearchHighlight({
            style_name:'spip_surligne',
            exact:'whole',
            style_name_suffix:false,
            engines:[/^".str_replace(array("/","."),array("\/","\."),$GLOBALS['meta']['adresse_site'])."/i,/recherche=([^&]+)/i],
            highlight:'.surlignable',
            nohighlight:'.pas_surlignable'".
            ($surcharge_surligne?",
            keys:'$surcharge_surligne'":"").",
            min_length: 3
          })
        });
      })(jQuery);
      /*]]>*/</script>
      ";
      // on l'insere juste avant </head>, sinon tout en bas
       if (is_null($l = strpos($page,'</head>')))
       	$l = strlen($page);
       $page = substr_replace($page, $script, $l,0);
      break;
    }
  return $page;
}
?>
