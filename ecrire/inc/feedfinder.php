<?php

/**********************************
adaptation en php de feedfinder.py :

"""Ultra-liberal feed finder, de Mark Pilgrim
<http://diveintomark.org/projects/feed_finder/>

Par: courcy.michael@wanadoo.fr

adaptation en php, je ne reprends qu'une partie de cette algorithme

0) A chaque étape on vérifie si les feed indiqué sont rééllement des feeds
1) Si l'uri passé est un feed on retourne le résultat tout simplement
2) Si le header de la page contient des balises LINK qui renvoient vers des feed on les retourne
3) on cherche les liens <a> qui se termine par  ".rss", ".rdf", ".xml", ou ".atom"
4) on cherche les liens <a> contenant "rss", "rdf", "xml", ou "atom"

j'intègre pas l'interrogation  avec xml_rpc de syndic8, mais on peut le faire assez facilement
dans la phase de test sur différentes url je n'ai constaté aucune diffrérence entre les réponses 
donné par feedfinder.py et les miennes donc je ne suis pas sur de voir l'interet

Je ne me préoccupe pas comme l'auteur de savoir si mes liens de feed sont sur le même serveur ou pas

exemple d'utilisation

print_r (get_feed_from_url("http://willy.boerland.com/myblog/"));

on obtient

Array
(
    [0] => http://willy.boerland.com/myblog/atom/feed
    [1] => http://willy.boerland.com/myblog/blogapi/rsd
    [2] => http://willy.boerland.com/myblog/rss.xml
    [3] => http://willy.boerland.com/myblog/node/feed
)

*****************************************************************/

$verif_complete = 0; //mettez le à 1 si vous voulez controler la validité des feed trouvés mais le temps d'execution
                     //est alors plus long

//une fonction qui permet de si un lien est un feed ou nom, si c'est un feed elle retourne son type
//si c'est pas un feed elle retourne 0, cette vérification est évidemment tres tres légère
// http://doc.spip.org/@is_feed
function is_feed($url){

	# methode SPIP
	if (function_exists('recuperer_page')) {
		$feed = recuperer_page($url);
		if (preg_match("/<(\w*) .*/", $buffer, $matches)){
                //ici on détecte la premiere balise
                $type_feed = $matches[1];
                switch ($type_feed) {
                       case "rss": return "rss";
                       case "feed": return "atom";
                       case "rdf": return "rdf";
                }
		}
        return '';
	}

      $fp = @fopen($url, "r");
      if (!$fp ) {
           return 0;
      }
      //vérifion la nature de ce fichier
      while (!feof($fp)) {
           $buffer = fgets($fp, 4096);
           if (preg_match("/<(\w*) .*/", $buffer, $matches)){
                //ici on détecte la premiere balise
                $type_feed = $matches[1];
                switch ($type_feed) {
                       case "rss": fclose($fp); return "rss";
                       case "feed": fclose($fp); return "atom";
                       case "rdf": fclose($fp); return "rdf";
                       default : fclose($fp); return 0;
                }
           }
      }
}

/*****************test is_feed******************************
echo is_feed("http://spip-contrib.net/backend" _EXTENSIO_PHP") . "<br />"; //retourne rss
echo is_feed("http://liberation.fr/rss.php") . "<br />"; //retourne rss
echo is_feed("http://liberation.fr/rss.php") . "<br />"; //retourne rss
echo is_feed("http://willy.boerland.com/myblog/atom/feed") //retourne atom
echo is_feed("http://spip.net/") . "<br />"; //retoune 0
//pas trouver d'exmples avec rdf j'ai encore du mal à saisir ce que rdf apporte de plus que rss
//mais bon j'ai pas aprofondi
************************************************************/

//fonction sans finesse mais efficace
//on parcourt ligne par ligne à la recherche de balise <a> ou <link>
//si dans le corps de celle-ci on trouve les mots rss, xml, atom ou rdf
//alors on recupere la valeur href='<url>', on adapte celle-ci si elle
//est relative et on vérifie que c'est bien un feed si oui on l'ajoute
//au tableau des feed si on ne trouve rien ou si aucun feed est trouvé on retourne 
//un tableau vide
// http://doc.spip.org/@get_feed_from_url
function get_feed_from_url($url, $buffer=false){
         global $verif_complete;
         //j'ai prévenu ce sera pas fin
         if (!preg_match("/^http:\/\/.*/", $url)) $url = "http://www." . $url;
         if (!$buffer) $buffer = @file_get_contents($url);

         $feed_list = array();
         //extraction des <link>
         if (preg_match_all("/<link [^>]*>/i", $buffer, $matches)){
                    //y a t-y rss atom rdf ou xml dans ces balises
                    foreach($matches[0] as $link){
                      if (  strpos($link, "rss")
                         || strpos($link, "rdf")
                         || strpos($link, "atom")
                         || strpos($link, "xml") ){
                            //voila un candidat on va extraire sa partie href et la placer dans notre tableau
                            if (preg_match("/href=['|\"]?([^\s'\"]*)['|\"]?/",$link,$matches2)){
                                 //on aura pris soin de vérifier si ce lien est relatif d'en faire un absolu
                                 if (!preg_match("/^http:\/\/.*/", $matches2[1])){
                                        $matches2[1] = concat_url($url,$matches2[1]);
                                 }
                                 if($verif_complete){
                                        if (is_feed($matches2[1])) $feed_list[] = $matches2[1];
                                 }else  $feed_list[] = $matches2[1];
                            }
                      }
                    }
                    //print_r($matches);
         }
         //extraction des <a>
         if (preg_match_all("/<a [^>]*>/i", $buffer, $matches)){
                    //y a t-y rss atom rdf ou xml dans ces balises
                    foreach($matches[0] as $link){
                       if (  strpos($link, "rss")
                         || strpos($link, "rdf")
                         || strpos($link, "atom")
                         || strpos($link, "xml") ){
                            //voila un candidat on va extraire sa partie href et la placer dans notre tableau
                            if (preg_match("/href=['|\"]?([^\s'\"]*)['|\"]?/",$link,$matches2)){
                                 //on aura pris soin de vérifier si ce lien est relatif d'en faire un absolu
                                 if (!preg_match("/^http:\/\/.*/", $matches2[1])){
                                        $matches2[1] = concat_url($url,$matches2[1]);
                                 }
                                 if($verif_complete){
                                        if (is_feed($matches2[1])) $feed_list[] = $matches2[1];
                                 }else  $feed_list[] = $matches2[1];
                            }
                       }
                    }
         }
         return $feed_list;
}
/************************************ getFeed ****************************
print_r (get_feed_from_url("spip-contrib.net"));
print_r (get_feed_from_url("http://liberation.fr/"));
print_r (get_feed_from_url("cnn.com"));
print_r (get_feed_from_url("http://willy.boerland.com/myblog/"));
*****************************    Résultat *****************************************
Array
(
    [0] => http://www.spip-contrib.net/backend.php
)
Array
(
    [0] => http://www.liberation.fr/rss.php
)
Array
(
    [0] => http://rss.cnn.com/rss/cnn_topstories.rss
    [1] => http://rss.cnn.com/rss/cnn_latest.rss
    [2] => http://www.cnn.com/services/rss/
    [3] => http://www.cnn.com/services/rss/
    [4] => http://www.cnn.com/services/rss/
)
Array
(
    [0] => http://willy.boerland.com/myblog/atom/feed
    [1] => http://willy.boerland.com/myblog/blogapi/rsd
    [2] => http://willy.boerland.com/myblog/rss.xml
    [3] => http://willy.boerland.com/myblog/node/feed
)
************************************************************************/

//petite fonction qui prend en charge les problemes de double slash
//qunad on concatene les lien 
// http://doc.spip.org/@concat_url
function concat_url($url1, $path){
	# methode spip
	if(function_exists('suivre_lien')) {
		return suivre_lien($url1,$path);
	}
        $url = $url1 . "/" . $path;
        //cette opération peut très facilement avoir généré // ou /// 
        $url = str_replace("///", "/", $url);
        $url = str_replace("//", "/", $url); 
        //cas particulier de http://
        $url = str_replace("http:/", "http://", $url);
        return $url;
}

/****************************test concat**********************
echo concat_url("http://spip.net" , "ecrire")."<br />";
echo concat_url("http://spip.net/" , "ecrire")."<br />";
echo concat_url("http://spip.net" , "/ecrire")."<br />";
echo concat_url("http://spip.net/" , "/ecrire")."<br />";
*************************************************************/




?>
