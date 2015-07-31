<?php

/*
 * podcast_client
 *
 * Client de podcast pour SPIP
 *
 * Auteur : fil@rezo.net
 * © 2005 - Distribue sous licence GNU/GPL
 *
 * Voir la documentation dans podcast_client.php
 * (ou, plus tard, dans documentation.html)
 */

$nom = 'podcast_client';
$version = 0.1;

// s'inserer dans le pipeline 'post_syndication' @ ecrire/inc_sites.php3
$GLOBALS['spip_pipeline']['post_syndication'] .= '|podcast_client';
$GLOBALS['spip_matrice']['podcast_client'] = dirname(__FILE__).'/podcast_client.php';

?>
