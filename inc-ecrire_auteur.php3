<?php

global $ecrire_auteur_array ;
$ecrire_auteur_array = array('id_auteur',
			     'email',
			     'sujet_message_auteur',
			     'texte_message_auteur',
			     'email_message_auteur'
);

function ecrire_auteur_stat($args, $filtres)
{
  list($id_auteur, $mail, $sujet, $texte, $adres) = $args;
  return (!email_valide($mail) ? '' :
	  array($id_auteur, $mail,$sujet, $texte, $adres));
}

function ecrire_auteur_dyn($id_auteur, $mail, $sujet, $texte, $adres) {
  global $spip_lang_rtl;
	include_ecrire("inc_lang.php3"); // pour spip_lang_rtl 
	if (!$spip_lang_rt) $spip_lang_rtl = '_rtl'; # INDISPENSABLE cf skel
	$mailko = $texte && !email_valide($adres);
	$validable = $texte && $sujet && (!$mailko);

	if ($validable && ($GLOBALS['valide'] == _T('form_prop_confirmer_envoi'))) { 
		$texte .= "\n\n-- "._T('envoi_via_le_site')." ".lire_meta('nom_site')." (".lire_meta('adresse_site')."/) --\n";
		include_ecrire("inc_mail.php3");
		envoyer_mail($mail, $sujet, $texte, $adres,
				"X-Originating-IP: ".$GLOBALS['REMOTE_ADDR']);
		return _T('form_prop_message_envoye');
	    }


	// repartir de zero pour les boutons car clean_link a pu etre utilisee

	$link = new Link;

	$link->delVar('sujet_message_auteur');
	$link->delVar('texte_message_auteur');
	$link->delVar('email_message_auteur');
	$link->delVar('id_auteur');
	return 
	  array('formulaire_ecrire_auteur', 0,
		array(
		      'action' => $link->getUrl(),
		      'id_auteur' => $id_auteur,
		      'mailko' => $mailko ? $spip_lang_rtl : '',
		      'mail' => entites_html($adres),
		      'sujetko' => ($texte && !$sujet) ? $spip_lang_rtl : '',
		      'sujet' => entites_html($sujet),
		      'texte' => entites_html($texte),
		      'valide' => ($validable ?
				    _T('form_prop_confirmer_envoi') :
				    _T('form_prop_envoyer'))
		      )
		);
}
?>
