<?php

// ACCESSIBILITE
// la page /oo offre une lecture en mode "texte seul"

header("Location: ../?action=preferer&arg=display:4&redirect=" . urlencode(str_replace('/oo','', $_SERVER['REQUEST_URI'])));

?>
