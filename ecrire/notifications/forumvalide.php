<?php
/*
 * Plugin Notifications
 * (c) 2009 SPIP
 * Distribue sous licence GPL
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * cette notification s'execute quand on valide un message 'prop'ose,
 * dans ecrire/inc/forum_insert.php ; ici on va notifier ceux qui ne l'ont
 * pas ete a la notification forumposte (sachant que les deux peuvent se
 * suivre si le forum est valide directement ('pos' ou 'abo')
 * http://doc.spip.org/@notifications_forumvalide_dist
 *
 * @param string $quoi
 * @param int $id_forum
 */
function notifications_forumvalide_dist($quoi, $id_forum, $options) {

	$t = sql_fetsel("*", "spip_forum", "id_forum=".intval($id_forum));
	if (!$t
		// forum sur un message prive : pas de notification ici (cron)
		OR @$t['statut'] == 'perso')
		return;

	// plugin notification si present
	$prevenir_auteurs = isset($GLOBALS['notifications']['prevenir_auteurs']) AND $GLOBALS['notifications']['prevenir_auteurs'];
	// sinon voie normale
	if ($t['id_article'] AND !$prevenir_auteurs){
		$s = sql_getfetsel('accepter_forum','spip_articles',"id_article=" . $t['id_article']);
		if (!$s)  $s = substr($GLOBALS['meta']["forums_publics"],0,3);

		$prevenir_auteurs = (strpos(@$GLOBALS['meta']['prevenir_auteurs'],",$s,")!==false
			OR @$GLOBALS['meta']['prevenir_auteurs'] === 'oui'); // compat
	}

	include_spip('inc/texte');
	include_spip('inc/filtres');
	include_spip('inc/autoriser');

	// Qui va-t-on prevenir ?
	$tous = array();
	// Ne pas ecrire au posteur du message, ni au moderateur qui valide le forum,
	$pasmoi = array($t['email_auteur'],$GLOBALS['visiteur_session']['email']);

	// 1. Les auteurs de l'article ; si c'est un article, ceux qui n'ont
	// pas le droit de le moderer (les autres l'ont recu plus tot)
	if ($t['id_article']
	AND $prevenir_auteurs) {
		$result = sql_select("auteurs.*","spip_auteurs AS auteurs, spip_auteurs_articles AS lien","lien.id_article=".intval($t['id_article'])." AND auteurs.id_auteur=lien.id_auteur");

		while ($qui = sql_fetch($result)) {
			if (!autoriser('modererforum', 'article', $t['id_article'], $qui['id_auteur']))
				$tous[] = $qui['email'];
			else
				// Ne pas ecrire aux auteurs deja notifies precedemment
				$pasmoi[] = $qui['email'];

		}
	}


	$options['forum'] = $t;
	$destinataires = pipeline('notifications_destinataires',
		array(
			'args'=>array('quoi'=>$quoi,'id'=>$id_forum,'options'=>$options)
		,
			'data'=>$tous)
	);

	// Nettoyer le tableau
	// en enlevant les exclus
	notifications_nettoyer_emails($destinataires,$pasmoi);

	//
	// Envoyer les emails
	//
	foreach ($destinataires as $email) {
		$texte = email_notification_forum($t, $email);
		notifications_envoyer_mails($email, $texte);
	}

}

?>
