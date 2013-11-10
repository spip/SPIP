<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/forum?lang_cible=zh
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucun_message_forum' => 'Aucun message de forum', # NEW

	// B
	'bouton_radio_articles_futurs' => '只为未来的文章 (数据库无动作).',
	'bouton_radio_articles_tous' => '为所有文章.',
	'bouton_radio_articles_tous_sauf_forum_desactive' => '为所有文章,除了那些论坛尚未激活的.',
	'bouton_radio_enregistrement_obligatoire' => '必须注册 
(在能发表出版物前
用户必须提供电子邮件订阅).',
	'bouton_radio_moderation_priori' => '预存 (
 出版物只能管理员确认
 才能显示出来).', # MODIF
	'bouton_radio_modere_abonnement' => '通过订阅预存', # MODIF
	'bouton_radio_modere_posteriori' => '预存后', # MODIF
	'bouton_radio_modere_priori' => '预存前', # MODIF
	'bouton_radio_publication_immediate' => '直接消息出版物
 (投稿发送后可显示, 管理员可以
 删除它们).',

	// D
	'documents_interdits_forum' => 'Documents interdits dans le forum', # NEW

	// E
	'erreur_enregistrement_message' => 'Votre message n\'a pas pu être enregistré en raison d\'un problème technique', # NEW

	// F
	'form_pet_message_commentaire' => '其它消息和评论?',
	'forum' => '论坛',
	'forum_acces_refuse' => '您已经无权访问这些论坛.',
	'forum_attention_dix_caracteres' => '<b>警告!</b> 您的消息少于十个字符.', # MODIF
	'forum_attention_message_non_poste' => 'Attention, vous n\'avez pas posté votre message !', # NEW
	'forum_attention_nb_caracteres_mini' => '<b>Attention !</b> votre message doit contenir au moins @min@ caractères.', # NEW
	'forum_attention_trois_caracteres' => '<b>警告!</b> 您的标题少于三个字符.', # MODIF
	'forum_attention_trop_caracteres' => '<b>Attention !</b> votre message est trop long (@compte@ caractères) : pour pouvoir être enregistré, il ne doit pas dépasser @max@ caractères.', # NEW
	'forum_avez_selectionne' => '您已选择:',
	'forum_cliquer_retour' => '单击  <a href=\'@retour_forum@\'>这里</a> 继续.',
	'forum_forum' => '论坛',
	'forum_info_modere' => '论坛是预缓冲的: 您的投稿只有被站点管理员确认才能显示.', # MODIF
	'forum_lien_hyper' => '<b>超链接</b> (可选)', # MODIF
	'forum_message' => 'Votre message', # NEW
	'forum_message_definitif' => '最后消息: 发送到站点',
	'forum_message_trop_long' => '您的消息过长. 最大长度为20000个字符.', # MODIF
	'forum_ne_repondez_pas' => '请勿回复该信件,请到如下地址的论坛回复:', # MODIF
	'forum_page_url' => '(如果您的消息引用了web上发表的文章请提供进一步的消息, 请输入页头和它的URL).',
	'forum_permalink' => 'Lien permanent vers le commentaire', # NEW
	'forum_poste_par' => '您文章@parauteur@之后后发表的信息.', # MODIF
	'forum_poste_par_court' => 'Message posté@parauteur@.', # NEW
	'forum_poste_par_generique' => 'Message posté@parauteur@ (@objet@ « @titre@ »).', # NEW
	'forum_qui_etes_vous' => '<b>您是谁?</b> (可选)', # MODIF
	'forum_saisie_texte_info' => 'Ce formulaire accepte les raccourcis SPIP <code>[-&gt;url] {{gras}} {italique} &lt;quote&gt; &lt;code&gt;</code> et le code HTML <code>&lt;q&gt; &lt;del&gt; &lt;ins&gt;</code>. Pour créer des paragraphes, laissez simplement des lignes vides.', # NEW
	'forum_texte' => '消息正文:', # MODIF
	'forum_titre' => '标题:', # MODIF
	'forum_url' => 'URL:', # MODIF
	'forum_valider' => '使选择生效',
	'forum_voir_avant' => '发表前预览', # MODIF
	'forum_votre_email' => '您的邮件地址:', # MODIF
	'forum_votre_nom' => '您的名字(或昵称):', # MODIF
	'forum_vous_enregistrer' => '在参与论坛前,您必须注册. 谢谢您填写已经递交过的个人信息.
如果你您尚未注册, 您必须',
	'forum_vous_inscrire' => '请注册。',

	// I
	'icone_bruler_message' => 'Signaler comme Spam', # NEW
	'icone_bruler_messages' => 'Signaler comme Spam', # NEW
	'icone_legitimer_message' => 'Signaler comme licite', # NEW
	'icone_poster_message' => '发表消息',
	'icone_suivi_forum' => '跟踪公共论坛: @nb_forums@ 出版物',
	'icone_suivi_forums' => '跟踪/管理论坛',
	'icone_supprimer_message' => '删除消息',
	'icone_supprimer_messages' => 'Supprimer ces messages', # NEW
	'icone_valider_message' => '使消息有效',
	'icone_valider_messages' => 'Valider ces messages', # NEW
	'icone_valider_repondre_message' => 'Valider & Répondre à ce message', # NEW
	'info_1_message_forum' => '1 message de forum', # NEW
	'info_activer_forum_public' => '<i>若要激活公共论坛, 请选择默认模式:</i>', # MODIF
	'info_appliquer_choix_moderation' => '应用缓冲选择:',
	'info_config_forums_prive' => 'Dans l’espace privé du site, vous pouvez activer plusieurs types de forums :', # NEW
	'info_config_forums_prive_admin' => 'Un forum réservé aux administrateurs du site :', # NEW
	'info_config_forums_prive_global' => 'Un forum global, ouvert à tous les rédacteurs :', # NEW
	'info_config_forums_prive_objets' => 'Un forum sous chaque article, brève, site référencé, etc. :', # NEW
	'info_desactiver_forum_public' => '停用公共论坛.
 公共论坛只能通过一篇一篇文章的访问;
它们的专栏和简要等将被禁止.',
	'info_envoi_forum' => '发送论坛给文章作者 ',
	'info_fonctionnement_forum' => '论坛操作:',
	'info_forums_liees_mot' => 'Les messages de forum liés à ce mot', # NEW
	'info_gauche_suivi_forum_2' => ' <i>论坛跟踪</i> 页是你站点的一个管理工具(不是讨论或编辑). 它显示这篇文章的所有论坛出版物并允许你管理这些出版物.', # MODIF
	'info_liens_syndiques_3' => '论坛',
	'info_liens_syndiques_4' => '是',
	'info_liens_syndiques_5' => '论坛',
	'info_liens_syndiques_6' => '是',
	'info_liens_syndiques_7' => '未确认.',
	'info_liens_texte' => 'Lien(s) contenu(s) dans le texte du message', # NEW
	'info_liens_titre' => 'Lien(s) contenu(s) dans le titre du message', # NEW
	'info_mode_fonctionnement_defaut_forum_public' => '公众论坛的缺省模式',
	'info_nb_messages_forum' => '@nb@ messages de forum', # NEW
	'info_option_email' => '当一个站点访问者在论坛发表一个关联文章的消息
  , 文章的作者能被电子邮件通知
  . 你愿意用这个选项吗?', # MODIF
	'info_pas_de_forum' => '没有论坛',
	'info_question_visiteur_ajout_document_forum' => 'Si vous souhaitez autoriser les visiteurs à joindre des documents (images, sons...) à leurs messages de forum, indiquez ci-dessous la liste des extensions de documents autorisés pour les forums (ex: gif, jpg, png, mp3).', # NEW
	'info_question_visiteur_ajout_document_forum_format' => 'Si vous souhaitez autoriser tous les types de documents considérés comme fiables par SPIP, mettez une étoile. Pour ne rien autoriser, n\'indiquez rien.', # NEW
	'info_selectionner_message' => 'Sélectionner les messages :', # NEW
	'interface_formulaire' => 'Interface formulaire', # NEW
	'interface_onglets' => 'Interface avec onglets', # NEW
	'item_activer_forum_administrateur' => '激活管理者论坛',
	'item_config_forums_prive_global' => 'Activer le forum des rédacteurs', # NEW
	'item_config_forums_prive_objets' => 'Activer ces forums', # NEW
	'item_desactiver_forum_administrateur' => '使管理论坛不可用',
	'item_non_config_forums_prive_global' => 'Désactiver le forum des rédacteurs', # NEW
	'item_non_config_forums_prive_objets' => 'Désactiver ces forums', # NEW

	// L
	'label_selectionner' => 'Sélectionner :', # NEW
	'lien_reponse_article' => '回应文章',
	'lien_reponse_breve_2' => '回应新闻',
	'lien_reponse_message' => 'Réponse au message', # NEW
	'lien_reponse_rubrique' => '回应专栏',
	'lien_reponse_site_reference' => '回应到参考站点:', # MODIF
	'lien_vider_selection' => 'Vider la selection', # NEW

	// M
	'messages_aucun' => 'Aucun', # NEW
	'messages_meme_auteur' => 'Tous les messages de cet auteur', # NEW
	'messages_meme_email' => 'Tous les messages de cet email', # NEW
	'messages_meme_ip' => 'Tous les messages de cette IP', # NEW
	'messages_off' => 'Supprimés', # NEW
	'messages_perso' => 'Personnels', # NEW
	'messages_privadm' => 'Administrateurs', # NEW
	'messages_prive' => 'Privés', # NEW
	'messages_privoff' => 'Supprimés', # NEW
	'messages_privrac' => 'Généraux', # NEW
	'messages_prop' => 'Proposés', # NEW
	'messages_publie' => 'Publiés', # NEW
	'messages_spam' => 'Spam', # NEW
	'messages_tous' => 'Tous', # NEW

	// O
	'onglet_messages_internes' => '内容消息',
	'onglet_messages_publics' => '公众消息',
	'onglet_messages_vide' => '无文字消息',

	// R
	'repondre_message' => '回复消息',

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_original' => 'original', # NEW
	'statut_prop' => 'Proposé', # NEW
	'statut_publie' => 'Publié', # NEW
	'statut_spam' => 'Spam', # NEW

	// T
	'text_article_propose_publication_forum' => 'N\'hésitez pas à donner votre avis grâce au forum attaché à cet article (en bas de page).', # NEW
	'texte_en_cours_validation' => 'Les articles, brèves, forums ci dessous sont proposés à la publication.', # NEW
	'texte_en_cours_validation_forum' => 'N\'hésitez pas à donner votre avis grâce aux forums qui leur sont attachés.', # NEW
	'texte_messages_publics' => 'Messages publics sur :', # NEW
	'titre_cadre_forum_administrateur' => '管理者私有论坛',
	'titre_cadre_forum_interne' => '内部论坛',
	'titre_config_forums_prive' => 'Forums de l’espace privé', # NEW
	'titre_forum' => '内部论坛',
	'titre_forum_suivi' => '论坛跟踪',
	'titre_page_forum_suivi' => '论坛跟踪',
	'titre_selection_action' => 'Sélection', # NEW
	'tout_voir' => 'Voir tous les messages', # NEW

	// V
	'voir_messages_objet' => 'voir les messages' # NEW
);

?>
