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

$fond = "login";
$delais = 3600;
$forcer_lang = true;

// Compatibilite anciennes versions de SPIP : si un 'var_url' (cible du login)
// est passe, renvoyer vers la meme adresse mais avec 'url'
if (isset($_SERVER['REQUEST_URI'])
AND strpos($_SERVER['REQUEST_URI'], 'var_url'))
	@header('Location: '.str_replace('var_url', 'url', $_SERVER['REQUEST_URI']));

// Fin compatibilite

include ("ecrire/public.php");

?>
