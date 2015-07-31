<?php

if (defined("_INC_PUBLIC")) { // inclusion différée
	$page = inclure_page($fond, $delais, $contexte_inclus);
	if ($page['process_ins'])
    {
    	eval('?' . '>' .  $page['texte']); 
    }
	else
	{ 
		echo $page['texte']; 
	}
} else {
	// premier appel
	define("_INC_PUBLIC", "1");
	

	# Variable indiquant l'extension du fichier du squelette 
	# (peut etre changé dans mes_option via inc_version; en 'xml' pour + tard)
	$GLOBALS['extension_squelette'] = 'html';
	# Variable indiquant le répertoires des images
	$GLOBALS['dossier_images'] = 'IMG';

	include ("ecrire/inc_version.php3");
	if ($INSECURE['fond'] || $INSECURE['delais']) exit;

	if ($HTTP_COOKIE_VARS['spip_session'] OR ($PHP_AUTH_USER AND !$ignore_auth_http)) {
		include_ecrire ("inc_session.php3");
		verifier_visiteur();
	}
 
	if ($forcer_lang) {
		include_ecrire('inc_lang.php3');
		verifier_lang_url();
	}
	if ($lang = $HTTP_GET_VARS['lang']) {
		include_ecrire('inc_lang.php3');
		lang_select($lang);     
	}

	include_ecrire("inc_meta.php3");

	// ajout_forum est une HTTP_GET_VAR installée par retour_forum dans inc-forum.
	// Il s'agit de pirater les HTTP_POST_VARS, afin de mettre en base
	// les valeurs transmises, avant réaffichage du formulaire avec celles-ci.
	// En cas de validation finale ça redirige vers l'URL ayant provoqué l'appel
	// au lieu de laisser l'URL appelée resynthétiser le formulaire.

	if ($ajout_forum) {
		$redirect = '';
		include('inc-messforum.php3');
		if ($redirect) {
			header("Location: $redirect");exit();
		}
	}

	include_local ("inc-public-global.php3");
	include_local ("inc-cache.php3");
	if (file_exists("inc-urls.php3")) {
		include_local ("inc-urls.php3");
		}
		else {
		include_local ("inc-urls-dist.php3");
		}

	if (!isset($delais)) $delais = 1 * 3600;

	$contexte = $GLOBALS['HTTP_GET_VARS'];
	if ($GLOBALS['date'])
		$contexte['date'] = $contexte['date_redac'] = normaliser_date($GLOBALS['date']);
	else
		$contexte['date'] = $contexte['date_redac'] = date("Y-m-d H:i:s");

	$cle = eregi_replace('&(submit|valider|PHPSESSID|(var_[^=&]*)|recalcul)=[^&]*',
			     '',
			     strtr($GLOBALS['REQUEST_URI'], '?', '&'));

	// Analyser les URLs personnalisees (inc-urls-...)
	/* attention c'est assez sale : ça affecte la variable globale $contexte */
	recuperer_parametres_url($fond, $cle);

	$lastmodified = cv_du_cache($cle, $delais);
	$gmoddate = gmdate("D, d M Y H:i:s", $lastmodified);

	spip_log($HTTP_SERVER_VARS['REQUEST_METHOD'] . " $HTTP_IF_MODIFIED_SINCE $GLOBALS[PHP_SELF]" .  $GLOBALS['recalcul']);

	// Code inoperant si le serveur HTTP traite ce champ en amont.
	if ($HTTP_IF_MODIFIED_SINCE && ($GLOBALS['recalcul'] != oui))
	{
		$headers_only = (trim(str_replace('GMT', '', ereg_replace(';.*$', '', $HTTP_IF_MODIFIED_SINCE))) == $gmoddate);
		if ($headers_only) http_status(304);
	}
	else 
	{
		$headers_only  = ($HTTP_SERVER_VARS['REQUEST_METHOD'] == 'HEAD');
	}

	if ($headers_only)
	{
		header("Last-Modified: $gmoddate GMT");
		header("Connection: close");
		spip_log("Close, lastmodified: $gmoddate");
	}
	else
	{
		$fraicheur = $delais;
		$page = ramener_cache(	$cle,
					'cherche_page_incluante', 
					array(	'fond' => $fond,
						'contexte' => $contexte,
						'var_recherche' => $HTTP_GET_VARS['var_recherche']),
			$delais);
		# si la page est neuve, recalculer ces 2 valeurs
		if (!$page['naissance'])
		{
			$lastmodified = cv_du_cache($cle, $fraicheur);
			$gmoddate = gmdate("D, d M Y H:i:s", $lastmodified);
		}
		// interdire au client de cacher un login, un admin ou un recalcul
		if (!$flag_dynamique && $recalcul != 'oui' && !$HTTP_COOKIE_VARS['spip_admin'])
		{
			$expire = gmdate("D, d M Y H:i:s", $lastmodified + $delais)." GMT";
		}
		else 
		{
			$expire = "0";
			header("Cache-Control: no-cache,must-revalidate");
			header("Pragma: no-cache");
		}
			
		header("Last-Modified: $gmoddate GMT");

		if ($xhtml) 
		{
			// Si Mozilla et tidy actif, passer en "application/xhtml+xml"
			// extremement risque: Mozilla passe en mode debugueur strict
			// mais permet d'afficher du MathML directement dans le texte
			// (et sauf erreur, c'est la bonne facon de declarer du xhtml)
			include_ecrire("inc_tidy.php");
			if (version_tidy() > 0) {		
				if (ereg("application/xhtml\+xml", $HTTP_ACCEPT)) 
					@header("Content-Type: application/xhtml+xml; charset=".lire_meta('charset'));
				else 
					@header("Content-Type: text/html; charset=".lire_meta('charset'));
					
				echo '<'.'?xml version="1.0" encoding="'.lire_meta('charset').'"?'.">\n";
			} else {
				@header("Content-Type: text/html; charset=".lire_meta('charset'));
			}
		} else {
			@header("Content-Type: text/html; charset=".lire_meta('charset'));
		}
		
		$texte = admin_page($page['naissance'], $page['texte']);
		
		if ($page['process_ins'] == 'php') {
			eval('?' . '>' . $texte);
		}
		else
		{ 
			$n = strlen($texte);
			# L'envoi du content-Length ci-dessous permet d'envoyer d'autres reponses
			# dans le cadre des connexions persistantes de HTTP1
			# Elle doit s'accompagner du connection-close sinon
			# elle retarde l'affichage de certains navigateurs.
			# On l'a desactivee ici puisqu'il n'y a qu'une seule reponse, 
			# et que certains serveurs la calculent et maintiennent la connexion
			# header("Content-Length: " . $n);
			# header("Connection: close");
			 echo $texte; 
			 spip_log("Page 100% HTML (" . $n  . " octets)");
		}
   }

	# Toutes les heures, menage d'un cache si le processus n'a rien recalcule.
	# On nettoie celui de la page retournee car le systeme vient d'y acceder:
	# il y a de bonnes chances qu'il l'ait toujours dans son cache.

	if ($page['naissance'] && (time() - lire_meta('date_purge_cache') > 3600)) {
		ecrire_meta('date_purge_cache', time());
		retire_vieux_caches($cle, $delais);
	}

	# Mise a jour des fichiers langues de l'espace public
	if ($cache_lang_modifs) {
		include_ecrire('inc_lang.php3');
		ecrire_caches_langues();
	}

	taches_de_fond($page['naissance']);
} // fin du defined
?>