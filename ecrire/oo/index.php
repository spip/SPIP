<?php

/**
 * Passage en mode texte seul de l'espace privé pour l'auteur en cours
 * (accessibilité)
 *
 * La page `/oo` offre une lecture en mode "texte seul"
 *
 * @see ecrire/action/preferer.php
 * @see prive/formulaires/configurer_preferences.php
 * 
 * @package SPIP\Core\Auteurs\Preferences
**/ 

header("Location: ../?action=preferer&arg=display:4&redirect=" . urlencode(str_replace('/oo','', $_SERVER['REQUEST_URI'])));
