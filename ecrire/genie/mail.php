<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
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

	$page = recuperer_fond('nouveautes',array('date'=>$GLOBALS['meta']['dernier_envoi_neuf'],'jours_neuf'=>$jours_neuf),array('raw'=>true));

	if (strlen(trim($page['texte']))){
		// recuperer les entetes envoyes par #HTTP_HEADER
		$headers = "";
		if (isset($page['entetes']) AND count($page['entetes'])){
			foreach ($page['entetes'] as $k => $v)
				$headers .= (strlen($v)?"$k: $v":$k)."\n";
		}

		include_spip("inc/notifications");
		notifications_envoyer_mails($adresse_neuf,$page['texte'],"","",$headers);
		ecrire_meta('dernier_envoi_neuf',date('Y-m-d H:i:s',$now));
	}
	else
		spip_log("mail nouveautes : rien de neuf depuis $jours_neuf jours");

	return 1;
}

?>
