<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/forum?lang_cible=tr
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucun_message_forum' => 'Aucun message de forum', # NEW

	// B
	'bouton_radio_articles_futurs' => 'sadece gelecek makalelere (veritabanında bir işlem yapılmayacak).',
	'bouton_radio_articles_tous' => 'makalelerin hepsine. ',
	'bouton_radio_articles_tous_sauf_forum_desactive' => 'forumu kapalı olan makaleler dışında hepsine.',
	'bouton_radio_enregistrement_obligatoire' => 'Kayıt zorunlu (kullanıcılar katılabilmek için önceden e-posta adreslerini vererek kayıt olmalı).',
	'bouton_radio_moderation_priori' => 'Önceden onayla yönetim (yazılan iletiler onaylandıktan sonra yayınlanıyor).', # MODIF
	'bouton_radio_modere_abonnement' => 'abonelik sistemiyle yönetim',
	'bouton_radio_modere_posteriori' => 'sonradan onayla yönetim', # MODIF
	'bouton_radio_modere_priori' => 'önceden onayla yönetim', # MODIF
	'bouton_radio_publication_immediate' => 'İletilerin anında yayınlanması 
(katılımlar gönderildiklerinde anında görüntülenir, yöneticiler onları 
sonradan yok edebilir).',

	// D
	'documents_interdits_forum' => 'Forumda yasak olan belgeler',

	// E
	'erreur_enregistrement_message' => 'Votre message n\'a pas pu être enregistré en raison d\'un problème technique', # NEW

	// F
	'form_pet_message_commentaire' => 'Bir ileti, bir yorum ?',
	'forum' => 'Forum',
	'forum_acces_refuse' => 'Artık bu forumlara erişiminiz yok.',
	'forum_attention_dix_caracteres' => '<b>Dikkat !</b> iletiniz on karakterden kısa.',
	'forum_attention_message_non_poste' => 'Attention, vous n\'avez pas posté votre message !', # NEW
	'forum_attention_nb_caracteres_mini' => '<b>Attention !</b> votre message doit contenir au moins @min@ caractères.', # NEW
	'forum_attention_trois_caracteres' => '<b>Dikkat !</b> başlığınız üç karakterden kısa.',
	'forum_attention_trop_caracteres' => '<b>Dikkat !</b> mesajınız çok uzun (@compte@ caractères) : kaydedilebilmesi için @max@ karakteri aşmamalı.', # MODIF
	'forum_avez_selectionne' => 'Şunu seçtiniz :',
	'forum_cliquer_retour' => 'Devam etmek için <a href=\'@retour_forum@\'>buraya tıklayınız</a>.',
	'forum_forum' => 'forum',
	'forum_info_modere' => 'Bu forum önceden onayla yönetilmektedir : katkınız ancak bir yönetici tarafından onaylandıktan sonra görülebilecektir.', # MODIF
	'forum_lien_hyper' => '<b>Hipermetin bağı</b> (seçimlik)', # MODIF
	'forum_message' => 'Votre message', # NEW
	'forum_message_definitif' => 'İletinin son hali : siteye gönder',
	'forum_message_trop_long' => 'İletiniz çok uzun. Bir ileti en fazla 20000 karakter içerebilir.', # MODIF
	'forum_ne_repondez_pas' => 'Bu e-postaya yanıt vermeyin, yanıt için forumu kullanın :', # MODIF
	'forum_page_url' => '(Eğer mesajınız Web\'de yayınlanan bir makaleye, ya da daha fazla bilgi içeren bir sayfaya atıfta bulunuyorsa, lütfen buraya sayfanın başlığını ve URL adresini belirtiniz.)',
	'forum_permalink' => 'Lien permanent vers le commentaire', # NEW
	'forum_poste_par' => 'Makalenize @parauteur@ ileti gönderildi.', # MODIF
	'forum_poste_par_court' => 'Message posté@parauteur@.', # NEW
	'forum_poste_par_generique' => 'Message posté@parauteur@ (@objet@ « @titre@ »).', # NEW
	'forum_qui_etes_vous' => '<b>Sizi tanıyalım ?</b> (seçimlik)', # MODIF
	'forum_saisie_texte_info' => 'Ce formulaire accepte les raccourcis SPIP <code>[-&gt;url] {{gras}} {italique} &lt;quote&gt; &lt;code&gt;</code> et le code HTML <code>&lt;q&gt; &lt;del&gt; &lt;ins&gt;</code>. Pour créer des paragraphes, laissez simplement des lignes vides.', # NEW
	'forum_texte' => 'İletinizin metni :', # MODIF
	'forum_titre' => 'Başlık :', # MODIF
	'forum_url' => 'URL :', # MODIF
	'forum_valider' => 'Bu seçimi onayla',
	'forum_voir_avant' => 'Göndermeden önce iletiyi göster', # MODIF
	'forum_votre_email' => 'E-posta adresiniz :', # MODIF
	'forum_votre_nom' => 'İsminiz (veya takma isminiz) :', # MODIF
	'forum_vous_enregistrer' => 'Bu foruma katılmak için, 
önce kaydolmanız gerekmektedir.
 Lütfen aşağıya size verilmiş olan tanımlayıcıyı giriniz.
 Eğer kaydolmamışsanız, yapmanız gereken',
	'forum_vous_inscrire' => 'önce kaydolmaktır.</a>',

	// I
	'icone_bruler_message' => 'Signaler comme Spam', # NEW
	'icone_bruler_messages' => 'Signaler comme Spam', # NEW
	'icone_legitimer_message' => 'Signaler comme licite', # NEW
	'icone_poster_message' => 'Bir ileti yolla',
	'icone_suivi_forum' => 'Kamu forumunun izlenmesi :@nb_forums@ katılım',
	'icone_suivi_forums' => 'Forumları izle / Yönet',
	'icone_supprimer_message' => 'Bu iletiyi sil',
	'icone_supprimer_messages' => 'Supprimer ces messages', # NEW
	'icone_valider_message' => 'İletiyi onayla',
	'icone_valider_messages' => 'Valider ces messages', # NEW
	'icone_valider_repondre_message' => 'Valider & Répondre à ce message', # NEW
	'info_1_message_forum' => '1 message de forum', # NEW
	'info_activer_forum_public' => 'Kamu forumlarını çalıştırmak için, lütfen varsayılan bir yönetim kipi seçiniz :', # MODIF
	'info_appliquer_choix_moderation' => 'Bu yönetim seçeneğinin uygulanması :',
	'info_config_forums_prive' => 'Sitenin özel alanında bir çok farklı tipteki forumu aktive edebilirsiniz  :', # MODIF
	'info_config_forums_prive_admin' => 'Site yöneticilierine ayrılmış bir forum :',
	'info_config_forums_prive_global' => 'Tüm yazarlara açık genel forum :',
	'info_config_forums_prive_objets' => 'Her makalenin, kısa naberin ve atıfta bulunulan sitenin altında bir forum, vs. :',
	'info_desactiver_forum_public' => 'Kamu forumlarının kullanımının durdurulması.
                                   Kamu forumları makaleler için tek tek izne tâbi olacak,
                                   bölümlerde, haberlerde, vb. ise yasaklanacaktır.',
	'info_envoi_forum' => 'Forumların makale yazarlarına gönderilmesi',
	'info_fonctionnement_forum' => 'Forumun işleyişi :',
	'info_forums_liees_mot' => 'Les messages de forum liés à ce mot', # NEW
	'info_gauche_suivi_forum_2' => '<i>Forumları izleme</i> sayfası sitenizi yönetme aracıdır (sohbet, ya da yazı yazma alanı değildir). Bir makaleye kamu forumundan yapılan tüm katkılarını gösterir ve bu katkıları yönetmenizi sağlar.', # MODIF
	'info_liens_syndiques_3' => 'Forumlar  ',
	'info_liens_syndiques_4' => ' - ',
	'info_liens_syndiques_5' => 'forum',
	'info_liens_syndiques_6' => ' - ',
	'info_liens_syndiques_7' => 'Onay bekliyor',
	'info_liens_texte' => 'Lien(s) contenu(s) dans le texte du message', # NEW
	'info_liens_titre' => 'Lien(s) contenu(s) dans le titre du message', # NEW
	'info_mode_fonctionnement_defaut_forum_public' => 'Kamu forumlarının varsayılan çalışma kipi ',
	'info_nb_messages_forum' => '@nb@ messages de forum', # NEW
	'info_option_email' => 'Bir ziyaretçi bir makaleye bağlı bir forumda yeni bir ileti gönderdiğinde,
           makale yazarları e-posta ile bu iletiden haberdar edilebilirler.
           Her bir forum için bu seçeneği kullanmak isteyip istemediğinizi belirtiniz.',
	'info_pas_de_forum' => 'Forum yok',
	'info_question_visiteur_ajout_document_forum' => 'Ziyaretçilerin forum mesajlarına belge (fotoğraf, ses...) eklemelerine izin vermek isterseniz, aşağıda hangi soyadlarına izin verildiğini belirtiniz (örnek: gif, jpg, png, mp3).', # MODIF
	'info_question_visiteur_ajout_document_forum_format' => 'SPIP tarafından güvenilir bulunan tüm belgelere izin vermek isterseniz bir yıldız koyunuz. Hiçbir şeye izin vermemek için hiçbir şey yazmayınız.', # MODIF
	'info_selectionner_message' => 'Sélectionner les messages :', # NEW
	'interface_formulaire' => 'Interface formulaire', # NEW
	'interface_onglets' => 'Interface avec onglets', # NEW
	'item_activer_forum_administrateur' => 'Yöneticiler forumunun etkinleştirilmesi',
	'item_config_forums_prive_global' => 'Yazarlar forumunu aktive et',
	'item_config_forums_prive_objets' => 'Şu forumları aktive et',
	'item_desactiver_forum_administrateur' => 'Yönetici forumunu iptal et.',
	'item_non_config_forums_prive_global' => 'Yazarlar forumunu dezaktive et',
	'item_non_config_forums_prive_objets' => 'Bu forumları dezaktive et',

	// L
	'label_selectionner' => 'Sélectionner :', # NEW
	'lien_reponse_article' => 'Bu makaleye yanıt ',
	'lien_reponse_breve_2' => 'Bu kısa habere yanıt',
	'lien_reponse_message' => 'Réponse au message', # NEW
	'lien_reponse_rubrique' => 'Bu bölüme yanıt ',
	'lien_reponse_site_reference' => 'Atıfta bulunan siteye yanıt : ', # MODIF
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
	'onglet_messages_internes' => 'İç iletiler',
	'onglet_messages_publics' => 'Kamu iletileri',
	'onglet_messages_vide' => 'Metinsiz iletiler',

	// R
	'repondre_message' => 'Bu mesajı yanıtla',

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_original' => 'orijinal',
	'statut_prop' => 'Proposé', # NEW
	'statut_publie' => 'Publié', # NEW
	'statut_spam' => 'Spam', # NEW

	// T
	'text_article_propose_publication_forum' => 'Sayfanın alt kısmındaki forum yoluyla bu makale hakkındaki görüşünüzü belirtmekten kaçınmayınız.',
	'texte_en_cours_validation' => 'Les articles, brèves, forums ci dessous sont proposés à la publication.', # NEW
	'texte_en_cours_validation_forum' => 'İlgili forumlar aracılığıyla görüşünüzü belirtmekten kaçınmayınız.',
	'texte_messages_publics' => 'Messages publics sur :', # NEW
	'titre_cadre_forum_administrateur' => 'Yöneticiler için özel forum',
	'titre_cadre_forum_interne' => 'İç forum',
	'titre_config_forums_prive' => 'Özel alan forumları',
	'titre_forum' => 'Forum',
	'titre_forum_suivi' => 'Forumların izlenmesi ',
	'titre_page_forum_suivi' => 'Forumların izlenmesi ',
	'titre_selection_action' => 'Sélection', # NEW
	'tout_voir' => 'Voir tous les messages', # NEW

	// V
	'voir_messages_objet' => 'voir les messages' # NEW
);

?>
