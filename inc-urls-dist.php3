<?

// inc-urls sert a generer les adresses des pages et, dans l'autre
// sens, a recuperer l'id_article (par exemple) a partir de l'URL
// de la page. Les URLs "standard" sont de la forme /article.php3?id_article=1

// Pour selectionner un autre type d'urls :
// 1- preparez votre serveur de maniere a ce qu'il active spip pour les
//     URLs en question, cf. http://www.uzine.net/article765.html
// 2- modifiez la ligne ci-dessous
//    (par exemple "html" pour des URLs en /article1.html)
// 3- renommez ce fichier "inc-urls.php3" pour ne pas risquer de
//    l'effacer lors de votre prochaine mise a jour de spip
// 4- purgez le cache de votre spip (dans sauvegarde/restauration)

$type_urls = "standard";
include_local("inc-urls-".$type_urls.".php3");

?>
