<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Envoi du Mail des nouveautes
 * base sur le squelette nouveautes.html
 *
 * La meta dernier_envoi_neuf permet de marquer la date du dernier envoi
 * et de determiner les nouveautes publiees depuis cette date
 *
 * http://doc.spip.org/@genie_mail_dist
 *
 * @param int $t
 * @return int
 */
function genie_mail_dist($t) {
	$adresse_neuf = $GLOBALS['meta']['adresse_neuf'];
	$jours_neuf = $GLOBALS['meta']['jours_neuf'];

	$now = time();
	if (!isset($GLOBALS['meta']['dernier_envoi_neuf']))
		ecrire_meta('dernier_envoi_neuf',date('Y-m-d H:i:s',$now - (3600 * 24 * $jours_neuf)));

	$page = recuperer_fond('nouveautes',array('date'=>$GLOBALS['meta']['dernier_envoi_neuf'],'jours_neuf'=>$jours_neuf));
	# en une seule passe avec un squelette textuel:
	# 1ere ligne = sujet
	# lignes suivantes jusqu'a la premiere blanche: headers SMTP

	$page = stripslashes(trim($page));
	$page = preg_replace(",\r\n?,", "\n", $page);
	$p = strpos($page,"\n\n");
	$s = strpos($page,"\n");
	if ($p AND $s) {
		if ($p>$s)
			$headers = substr($page,$s+1,$p-$s);
		$sujet_nouveautes = substr($page,0,$s);
		$mail_nouveautes = trim(substr($page,$p+2));
	}

	if (strlen($mail_nouveautes) > 10) {
		$envoyer_mail = charger_fonction('envoyer_mail', 'inc');
		$envoyer_mail($adresse_neuf, $sujet_nouveautes, $mail_nouveautes, '', $headers);
		ecrire_meta('dernier_envoi_neuf',date('Y-m-d H:i:s',$now));
	}
	else
		spip_log("mail nouveautes : rien de neuf depuis $jours_neuf jours");
	return 1;
}

?>
