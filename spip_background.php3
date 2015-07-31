<?php

// Du cote de la page HTML, utiliser un background-image en feuille de style
// plutot qu'un <img>, c'est plus discret notamment sous navigateur texte
$image = pack("H*", "47494638396118001800800000ffffff00000021f90401000000002c0000000018001800000216848fa9cbed0fa39cb4da8bb3debcfb0f86e248965301003b");
$size = strlen($image);

Header("Content-Type: image/gif");
Header("Content-Length: ".$size);
Header("Cache-Control: no-cache,no-store");
Header("Pragma: no-cache");
Header("Connection: close");

echo $image;

flush();

include('ecrire/inc_version.php3');

cron(1);	// toutes les 1 seconde (gourmand)


?>