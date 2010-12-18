<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;


// La fonction de notification de base, qui dispatche le travail
// http://doc.spip.org/@inc_notifications_dist
function inc_notifications_dist($quoi, $id=0, $options=array()) {

	// charger les fichiers qui veulent ajouter des definitions
	// ou faire des trucs aussi dans le pipeline, ca fait deux api pour le prix d'une ...
	pipeline('notifications',array('args'=>array('quoi'=>$quoi,'id'=>$id,'options'=>$options)));

	if ($notification = charger_fonction($quoi,'notifications',true)) {
		spip_log("$notification($quoi,$id"
			.($options?",".serialize($options):"")
			.")",'notifications');
		$notification($quoi, $id, $options);
	}
}

/**
 * Nettoyage des emails avant un envoi
 * on passe par reference pour la perf
 *
 * les emails liste par $eclure seront exclus de la liste
 *
 * @param array $emails
 * @param array $exclure
 */
function notifications_nettoyer_emails(&$emails, $exclure = array()){
	// filtrer et unifier
	$emails = array_unique(array_filter(array_map('email_valide',array_map('trim', $emails))));
	if ($exclure AND count($exclure)){
		// nettoyer les exclusions d'abord
		notifications_nettoyer_emails($exclure);
		// faire un diff
		$emails = array_diff($emails,$exclure);
	}
}

/**
 * Envoyer un email de notification
 * Le sujet peut etre vide, dans ce cas il reprendra la premiere ligne non vide du texte
 *
 * @param array/string $emails
 * @param string $texte
 * @param string $sujet
 */
function notifications_envoyer_mails($emails,$texte,$sujet=""){
	// rien a faire si pas de texte !
	if (!strlen($texte))
		return;

	// si on ne specifie qu'un email, le mettre dans un tableau
	if (!is_array($emails))
		$emails = explode(',',$emails);

	notifications_nettoyer_emails($emails);

	// si le sujet est vide, extraire la premiere ligne du corps
	if (!strlen($sujet)){
		// nettoyer un peu les retours chariots
		$texte = str_replace("\r\n", "\r", $texte);
		$texte = str_replace("\r", "\n", $texte);
		// decouper
		$texte = explode("\n",trim($texte));
		// extraire la premiere ligne
		$sujet = array_shift($texte);
		$texte = trim(implode("\n",$texte));
	}

	$envoyer_mail = charger_fonction('envoyer_mail','inc');
	foreach($emails as $email){
		// passer dans un pipeline qui permet un ajout eventuel
		// (url de suivi des notifications par exemple)
		$envoi = pipeline('notifications_envoyer_mails',array('email'=>$email,'sujet'=>$sujet,'texte'=>$texte));
		$email = $envoi['email'];
		$sujet = $envoi['sujet'];
		$texte = $envoi['texte'];
		if (function_exists('job_queue_add'))
			job_queue_add('envoyer_mail', ">$email : $sujet", array($email, $sujet, $texte), 'inc/');
		else
			$envoyer_mail($email, $sujet, $texte);
	}

}

/**
 * Notifier un evenement sur un article
 * recupere le fond designe dans $modele,
 * prend la premiere ligne comme sujet
 * et l'interprete pour envoyer l'email
 *
 * @param int $id_article
 * @param string $modele
 */
function email_notification_article($id_article, $modele) {
	$envoyer_mail = charger_fonction('envoyer_mail','inc'); // pour nettoyer_titre_email

	return recuperer_fond($modele,array('id_article'=>$id_article));
}

// Compatibilite, ne plus utiliser
// http://doc.spip.org/@notifier_publication_article
function notifier_publication_article($id_article) {
	if ($GLOBALS['meta']["suivi_edito"] == "oui") {
		$adresse_suivi = $GLOBALS['meta']["adresse_suivi"];
		$texte = email_notification_article($id_article, "notifications/article_publie");
		notifications_envoyer_mails($adresse_suivi, $texte);
	}
}

// Compatibilite, ne plus utiliser
// http://doc.spip.org/@notifier_proposition_article
function notifier_proposition_article($id_article) {
	if ($GLOBALS['meta']["suivi_edito"] == "oui") {
		$adresse_suivi = $GLOBALS['meta']["adresse_suivi"];
		$texte = email_notification_article($id_article, "notifications/article_propose");
		notifications_envoyer_mails($adresse_suivi, $texte);
	}
}

/**
 * Construitre l'email personalise de notification d'un forum
 *
 * @param array $t
 * @param string $email
 * @return string
 */
function email_notification_forum ($t, $email) {
	static $contexte = array();

	if(!isset($contexte[$t['id_forum']])){
		$url = '';
		$id_forum = $t['id_forum'];

		if ($t['statut'] == 'prive') # forum prive
		{
			if ($t['id_article'])
				$url = generer_url_ecrire('articles', 'id_article='.$t['id_article']).'#id'.$id_forum;
			else if ($t['id_breve'])
				$url = generer_url_ecrire('breves_voir', 'id_breve='.$t['id_breve']).'#id'.$id_forum;
			else if ($t['id_syndic'])
				$url = generer_url_ecrire('sites', 'id_syndic='.$t['id_syndic']).'#id'.$id_forum;
		}
		else if ($t['statut'] == 'privrac') # forum general
		{
			$url = generer_url_ecrire('forum').'#id'.$id_forum;
		}
		else if ($t['statut'] == 'privadm') # forum des admins
		{
			$url = generer_url_ecrire('forum_admin').'#id'.$id_forum;
		}
		else if ($t['statut'] == 'publie') # forum publie
		{
			$url = generer_url_entite($id_forum, 'forum');
		}
		else #  forum modere, spam, poubelle direct ....
		{
			$url = generer_url_ecrire('controle_forum', "debut_id_forum=".$id_forum);
		}

		if (!$url) {
			spip_log("forum $id_forum sans referent",'notifications');
			$url = './';
		}
		if ($t['id_article']) {
			$titre = sql_getfetsel("titre", "spip_articles", "id_article=".sql_quote($t['id_article']));
		}
		if ($t['id_message']) {
			$titre = sql_getfetsel("titre", "spip_messages", "id_message=".sql_quote($t['id_message']));
		}

		$t['titre_source'] = $titre;
		$t['url'] = $url;

		// detecter les url des liens du forum
		// pour la moderation (permet de reperer les SPAMS avec des liens caches)
		$links = array();
		foreach ($t as $champ)
			$links = $links + extraire_balises($champ,'a');
		$links = extraire_attribut($links,'href');
		$links = implode("\n",$links);
		$t['liens'] = $links;

		$contexte[$t['id_forum']] = $t;
	}

	$t = $contexte[$t['id_forum']];
		// Rechercher eventuellement la langue du destinataire
	if (NULL !== ($l = sql_getfetsel('lang', 'spip_auteurs', "email=" . sql_quote($email))))
		$l = lang_select($l);

	$parauteur = (strlen($t['auteur']) <= 2) ? '' :
		(" " ._T('forum_par_auteur', array(
			'auteur' => $t['auteur'])
		) .
		 ($t['email_auteur'] ? ' <' . $t['email_auteur'] . '>' : ''));

	$titre = textebrut(typo($t['titre_source']));
	$forum_poste_par = ($t['id_article']
		? _T('forum_poste_par', array(
			'parauteur' => $parauteur, 'titre' => $titre))
		: $parauteur . ' (' . $titre . ')');

	$t['par_auteur'] = $forum_poste_par;

	$envoyer_mail = charger_fonction('envoyer_mail','inc'); // pour nettoyer_titre_email
	$corps = recuperer_fond("notifications/forum_poste",$t);

	if ($l)
		lang_select();

	return $corps;
}



?>
