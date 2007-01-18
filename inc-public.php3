<?php
	# fichier fantome pour assurer la compatibilite ascendante
	# une fois tous les fichiers *.php3 supprimes de la racine, vous
	# pouvez eliminer celui-ci aussi
	include('spip.php');
	spip_log('inc-public.php3 '.$GLOBALS['REQUEST_URI'], 'vieilles_defs');
?>
