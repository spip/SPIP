<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@envoyer_mail
function envoyer_mail($email, $sujet, $texte, $from = "", $headers = "") {
	$envoyer_mail = charger_fonction('envoyer_mail','inc');
	return $envoyer_mail($email,$sujet,$texte,$from,$headers);
}


// http://doc.spip.org/@nettoyer_titre_email
function nettoyer_titre_email($titre) {
	return str_replace("\n", ' ', supprimer_tags(extraire_multi($titre)));
}


//
// Mail des nouveautes
//
// http://doc.spip.org/@cron_mail
function cron_mail($t) {
	$adresse_neuf = $GLOBALS['meta']['adresse_neuf'];
	$jours_neuf = $GLOBALS['meta']['jours_neuf'];
	// $t = 0 si le fichier de lock a ete detruit
	if (!$t) $t = time() - (3600 * 24 * $jours_neuf);

	$parametrer = charger_fonction('parametrer', 'public');
	$page = $parametrer('nouveautes',
			    array('date' => date('Y-m-d H:i:s', $t),
				  'jours_neuf' => $jours_neuf));
	$page = $page['texte'];
	if (substr($page,0,5) == '<'.'?php') {
# ancienne version: squelette en PHP avec affection des 2 variables ci-dessous
# 1 passe de plus a la sortie
				$mail_nouveautes = '';
				$sujet_nouveautes = '';
				$headers = '';
				eval ('?' . '>' . $page);
	} else {
# nouvelle version en une seule passe avec un squelette textuel:
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
	}

	if (strlen($mail_nouveautes) > 10)
		envoyer_mail($adresse_neuf, $sujet_nouveautes, $mail_nouveautes, '', $headers);
	else
		spip_log("mail nouveautes : rien de neuf depuis $jours_neuf jours");
	return 1;
}

?>