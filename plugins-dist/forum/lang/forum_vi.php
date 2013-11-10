<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/forum?lang_cible=vi
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucun_message_forum' => 'Aucun message de forum', # NEW

	// B
	'bouton_radio_articles_futurs' => 'chỉ tác động đến bài vở mới (không làm thay đổi database) ',
	'bouton_radio_articles_tous' => 'cho tất cả bài vở.',
	'bouton_radio_articles_tous_sauf_forum_desactive' => 'cho tất cả bài vở trừ những bài không có diễn đàn.',
	'bouton_radio_enregistrement_obligatoire' => 'Phải ghi danh (độc giả phải ghi tên và cho địa chỉ email trước khi gửi thư tín vào diễn đàn).',
	'bouton_radio_moderation_priori' => 'Phải được chấp thuận trước (thư tín chỉ được đăng sau khi được quản trị viên chấp thuận).', # MODIF
	'bouton_radio_modere_abonnement' => 'ghi danh mới được đăng',
	'bouton_radio_modere_posteriori' => 'đăng trước, xét sau', # MODIF
	'bouton_radio_modere_priori' => 'xét trước, đăng sau', # MODIF
	'bouton_radio_publication_immediate' => 'Phát hành thư tín ngay lập tức (thư gửi đi sẽ hiện lên ngay, quản trị viên có thể xóa chúng sau đó).',

	// D
	'documents_interdits_forum' => 'Documents interdits dans le forum', # NEW

	// E
	'erreur_enregistrement_message' => 'Votre message n\'a pas pu être enregistré en raison d\'un problème technique', # NEW

	// F
	'form_pet_message_commentaire' => 'Có nhắn tin hay bình luận gì không?',
	'forum' => 'Diễn đàn',
	'forum_acces_refuse' => 'Bạn không có quyền vào các diễn đàn này nữa.',
	'forum_attention_dix_caracteres' => '<b>Cảnh báo!</b> Thư tín quá ngắn - Phải hơn 10 mẫu tự.',
	'forum_attention_message_non_poste' => 'Attention, vous n\'avez pas posté votre message !', # NEW
	'forum_attention_nb_caracteres_mini' => '<b>Attention !</b> votre message doit contenir au moins @min@ caractères.', # NEW
	'forum_attention_trois_caracteres' => '<b>Cảnh báo!</b> Tựa đề quá ngắn - Phải hơn 3 mẫu tự.',
	'forum_attention_trop_caracteres' => '<b>Cảnh báo !</b> nội dung thư tín của bạn quá dài (@compte@ mẫu tự) : để có thể lưu trữ, độ dài của nội dung thư tín không được quá @max@ mẫu tự.', # MODIF
	'forum_avez_selectionne' => 'Bạn đã chọn:',
	'forum_cliquer_retour' => 'Bấm <a href=\'@retour_forum@\'>vô đây</a> để tiếp tục.',
	'forum_forum' => 'diễn đàn',
	'forum_info_modere' => 'Diễn đàn này được điều hợp trước: bài vở đóng góp sẽ xuất hiện sau khi được quản trị viên chấp thuận.', # MODIF
	'forum_lien_hyper' => '<b>Điểm nối hypertext</b> (không bắt buộc)', # MODIF
	'forum_message' => 'Votre message', # NEW
	'forum_message_definitif' => 'Thư tín đã xong: gửi đi',
	'forum_message_trop_long' => 'Thư tín dài quá. Độ dài tối đa 20000 mẫu tự.', # MODIF
	'forum_ne_repondez_pas' => 'Đừng hồi âm email này, hồi âm trong diễn đàn ở địa chỉ sau đây:', # MODIF
	'forum_page_url' => '(Nếu thư tín của bạn có đề cập đến một bài khác trên web, xin điền vào tựa đề và địa chỉ URL của nó dưới đây).',
	'forum_permalink' => 'Lien permanent vers le commentaire', # NEW
	'forum_poste_par' => 'Có thư tín @parauteur@ đi sau bài của bạn.', # MODIF
	'forum_poste_par_court' => 'Message posté@parauteur@.', # NEW
	'forum_poste_par_generique' => 'Message posté@parauteur@ (@objet@ « @titre@ »).', # NEW
	'forum_qui_etes_vous' => '<b>Chi tiết về bạn</b> (không bắt buộc)', # MODIF
	'forum_saisie_texte_info' => 'Ce formulaire accepte les raccourcis SPIP <code>[-&gt;url] {{gras}} {italique} &lt;quote&gt; &lt;code&gt;</code> et le code HTML <code>&lt;q&gt; &lt;del&gt; &lt;ins&gt;</code>. Pour créer des paragraphes, laissez simplement des lignes vides.', # NEW
	'forum_texte' => 'Thân bài của thư tín:', # MODIF
	'forum_titre' => 'Tựa đề:', # MODIF
	'forum_url' => 'URL:', # MODIF
	'forum_valider' => 'Xác nhận lựa chọn này',
	'forum_voir_avant' => 'Xem lại thư tín trước khi đăng', # MODIF
	'forum_votre_email' => 'Địa chỉ email của bạn:', # MODIF
	'forum_votre_nom' => 'Tên (hay bí danh):', # MODIF
	'forum_vous_enregistrer' => 'Trước khi tham gia vào diễn đàn này, bạn phải ghi danh. Nếu chưa ghi danh, bạn phải',
	'forum_vous_inscrire' => 'ghi danh.',

	// I
	'icone_bruler_message' => 'Signaler comme Spam', # NEW
	'icone_bruler_messages' => 'Signaler comme Spam', # NEW
	'icone_legitimer_message' => 'Signaler comme licite', # NEW
	'icone_poster_message' => 'Gửi một thư tín ',
	'icone_suivi_forum' => 'Theo dõi diễn đàn : @nb_forums@ dóng góp',
	'icone_suivi_forums' => 'Quản trị diễn đàn',
	'icone_supprimer_message' => 'Xoá thư tín này',
	'icone_supprimer_messages' => 'Supprimer ces messages', # NEW
	'icone_valider_message' => 'Chấp thuận',
	'icone_valider_messages' => 'Valider ces messages', # NEW
	'icone_valider_repondre_message' => 'Valider & Répondre à ce message', # NEW
	'info_1_message_forum' => '1 message de forum', # NEW
	'info_activer_forum_public' => '<i>Để diễn đàn công cộng hoạt động, xin chọn một trong những phương thức điều hợp sau đây</i>', # MODIF
	'info_appliquer_choix_moderation' => 'Dùng phương thức điều hợp này:',
	'info_config_forums_prive' => 'Dans l’espace privé du site, vous pouvez activer plusieurs types de forums :', # NEW
	'info_config_forums_prive_admin' => 'Un forum réservé aux administrateurs du site :', # NEW
	'info_config_forums_prive_global' => 'Un forum global, ouvert à tous les rédacteurs :', # NEW
	'info_config_forums_prive_objets' => 'Un forum sous chaque article, brève, site référencé, etc. :', # NEW
	'info_desactiver_forum_public' => 'Khóa việc sử dụng diễn đàn công cộng. Diễn đàn công cộng có thể được cho phép theo từng trường hợp một cho các bài vở; tuy nhiên sẽ cấm không được dùng cho các đề mục, tin ngắn, v.v...',
	'info_envoi_forum' => 'Gửi thư tín diễn đàn đến tác giả bài viết',
	'info_fonctionnement_forum' => 'Thao tác của diễn đàn: ',
	'info_forums_liees_mot' => 'Les messages de forum liés à ce mot', # NEW
	'info_gauche_suivi_forum_2' => 'Trang <i>Quản trị Diễn Đàn</i> là một phương tiện quản trị của trang web (không dùng để trao đổi hay sửa đổi). Trang này liệt kê tất cả mọi thư tín trong diễn đàn công cộng của bài này và cho phép bạn quản trị những thư tín này.', # MODIF
	'info_liens_syndiques_3' => ' diễn đàn',
	'info_liens_syndiques_4' => ' là',
	'info_liens_syndiques_5' => 'diễn đàn',
	'info_liens_syndiques_6' => ' là',
	'info_liens_syndiques_7' => 'chờ thông qua.',
	'info_liens_texte' => 'Lien(s) contenu(s) dans le texte du message', # NEW
	'info_liens_titre' => 'Lien(s) contenu(s) dans le titre du message', # NEW
	'info_mode_fonctionnement_defaut_forum_public' => 'Cách thức điều hành định sẵn của diễn đàn công',
	'info_nb_messages_forum' => '@nb@ messages de forum', # NEW
	'info_option_email' => 'Khi một vị khách gửi thư tín vào diễn đàn có liên hệ tới một bài viết, tác giả bài viết đó có thể được thông báo qua email. Bạn có muốn dùng đặc điểm này không? ', # MODIF
	'info_pas_de_forum' => 'không có diễn đàn',
	'info_question_visiteur_ajout_document_forum' => 'Si vous souhaitez autoriser les visiteurs à joindre des documents (images, sons...) à leurs messages de forum, indiquez ci-dessous la liste des extensions de documents autorisés pour les forums (ex: gif, jpg, png, mp3).', # NEW
	'info_question_visiteur_ajout_document_forum_format' => 'Si vous souhaitez autoriser tous les types de documents considérés comme fiables par SPIP, mettez une étoile. Pour ne rien autoriser, n\'indiquez rien.', # NEW
	'info_selectionner_message' => 'Sélectionner les messages :', # NEW
	'interface_formulaire' => 'Interface formulaire', # NEW
	'interface_onglets' => 'Interface avec onglets', # NEW
	'item_activer_forum_administrateur' => 'Mở diễn đàn quản trị',
	'item_config_forums_prive_global' => 'Activer le forum des rédacteurs', # NEW
	'item_config_forums_prive_objets' => 'Activer ces forums', # NEW
	'item_desactiver_forum_administrateur' => 'Khóa diễn đàn quản trị viên',
	'item_non_config_forums_prive_global' => 'Désactiver le forum des rédacteurs', # NEW
	'item_non_config_forums_prive_objets' => 'Désactiver ces forums', # NEW

	// L
	'label_selectionner' => 'Sélectionner :', # NEW
	'lien_reponse_article' => 'Hồi âm bài này',
	'lien_reponse_breve_2' => 'Hồi âm tin ngắn',
	'lien_reponse_message' => 'Réponse au message', # NEW
	'lien_reponse_rubrique' => 'Hồi âm đề mục này',
	'lien_reponse_site_reference' => 'Hồi âm website nối kết:', # MODIF
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
	'onglet_messages_internes' => 'Thư tín nội bộ',
	'onglet_messages_publics' => 'Thư tín công cộng',
	'onglet_messages_vide' => 'Thư tín không lời',

	// R
	'repondre_message' => 'Trả lời mẫu tin này',

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
	'titre_cadre_forum_administrateur' => 'Diễn đàn dành riêng cho quản trị viên',
	'titre_cadre_forum_interne' => 'Diễn đàn nội bộ',
	'titre_config_forums_prive' => 'Forums de l’espace privé', # NEW
	'titre_forum' => 'Diễn đàn',
	'titre_forum_suivi' => 'Quản trị Diễn đàn',
	'titre_page_forum_suivi' => 'Quản trị diễn đàn',
	'titre_selection_action' => 'Sélection', # NEW
	'tout_voir' => 'Voir tous les messages', # NEW

	// V
	'voir_messages_objet' => 'voir les messages' # NEW
);

?>
