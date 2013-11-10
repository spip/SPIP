<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/sites?lang_cible=ja
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'articles_dispo' => 'En attente', # NEW
	'articles_meme_auteur' => 'Tous les articles de cet auteur', # NEW
	'articles_off' => 'Bloqués', # NEW
	'articles_publie' => 'Publiés', # NEW
	'articles_refuse' => 'Supprimés', # NEW
	'articles_tous' => 'Tous', # NEW
	'aucun_article_syndic' => 'Aucun article syndiqué', # NEW
	'avis_echec_syndication_01' => '組織化に失敗しました：　指定されたバックエンドが判読できないか、どの記事も示されていないかのどちらかです。',
	'avis_echec_syndication_02' => '組織化に失敗しました：　このサイトのバックエンドに到達できません。',
	'avis_site_introuvable' => 'ウェブサイトが見つかりません。',
	'avis_site_syndique_probleme' => '警告: このサイトで遭遇した問題を供給することについて; 従って、そのシステムは一時的に妨害された。どうか、このサイトの供給ファイル（<b>@url_syndic@</b>）のアドレスを確かめて、情報を新しくリカバリーすることに挑戦してみてください。', # MODIF
	'avis_sites_probleme_syndication' => 'これらのサイトは、供給の問題に遭遇した',
	'avis_sites_syndiques_probleme' => 'それらは問題を生じるサイトを配給しました',

	// B
	'bouton_exporter' => 'Exporter', # NEW
	'bouton_importer' => 'Importer', # NEW
	'bouton_radio_modere_posteriori' => 'コメントを投稿後に検査', # MODIF
	'bouton_radio_modere_priori' => '検査したコメントのみ表示', # MODIF
	'bouton_radio_non_syndication' => '供給（シンジケート）しない',
	'bouton_radio_syndication' => '供給（シンジケート）:',

	// C
	'confirmer_purger_syndication' => 'Êtes-vous certain de vouloir supprimer tous les articles syndiqués de ce site ?', # NEW

	// E
	'entree_adresse_fichier_syndication' => '配給のための «backend» ファイルのアドレス:',
	'entree_adresse_site' => '<b>URL</b> [必須]',
	'entree_description_site' => 'サイトの解説',
	'erreur_fichier_format_inconnu' => 'Le format du fichier @fichier@ n\'est pas pris en charge.', # NEW
	'erreur_fichier_incorrect' => 'Impossible de lire le fichier.', # NEW

	// F
	'form_prop_nom_site' => 'サイトの名前',

	// I
	'icone_article_syndic' => 'Article syndiqué', # NEW
	'icone_articles_syndic' => 'Articles syndiqués', # NEW
	'icone_controler_syndication' => 'Publication des articles syndiqués', # NEW
	'icone_modifier_site' => 'このサイトを修正',
	'icone_referencer_nouveau_site' => '新しいサイトを参照する',
	'icone_site_reference' => 'Sites référencés', # NEW
	'icone_supprimer_article' => 'Supprimer cet article', # NEW
	'icone_supprimer_articles' => 'Supprimer ces articles', # NEW
	'icone_valider_article' => 'Valider cet article', # NEW
	'icone_valider_articles' => 'Valider ces articles', # NEW
	'icone_voir_sites_references' => '参照したサイトの表示',
	'info_1_site_importe' => '1 site a été importé', # NEW
	'info_a_valider' => '[検査済み]',
	'info_aucun_site_importe' => 'Aucun site n\'a pu être importé', # NEW
	'info_bloquer' => 'ブロック',
	'info_bloquer_lien' => 'このリンクをブロック',
	'info_derniere_syndication' => 'このサイトの最後の供給（シンジケーション）が実行されたのは',
	'info_liens_syndiques_1' => '供給（シンジケート）リンク',
	'info_liens_syndiques_2' => '適正検査前。',
	'info_nb_sites_importes' => '@nb@ sites ont été importés', # NEW
	'info_nom_site_2' => '<b>サイトの名前</b> [必須]',
	'info_panne_site_syndique' => '故障中のサイトを供給している',
	'info_probleme_grave' => 'エラー',
	'info_question_proposer_site' => '誰が参照サイトを提案できますか？',
	'info_retablir_lien' => 'このリンクを復活',
	'info_site_attente' => 'ウェブサイトの適正検査中',
	'info_site_propose' => 'サイトに提出された:',
	'info_site_reference' => 'オンラインでサイトを参照',
	'info_site_refuse' => '拒否されたウェブサイト',
	'info_site_syndique' => 'このサイトは供給（シンジケート）されています...', # MODIF
	'info_site_valider' => '適正検査済みサイト',
	'info_sites_referencer' => 'サイトを参照',
	'info_sites_refuses' => 'サイトに拒否された',
	'info_statut_site_1' => 'このサイトは:',
	'info_statut_site_2' => '公開中',
	'info_statut_site_3' => '提出中',
	'info_statut_site_4' => 'ゴミ箱の中', # MODIF
	'info_syndication' => '供給（シンジケーション):',
	'info_syndication_articles' => '記事',
	'item_bloquer_liens_syndiques' => '供給（シンジケート）リンクを検査前、妨害する',
	'item_gerer_annuaire_site_web' => 'Webサイトのディレクトリを管理',
	'item_non_bloquer_liens_syndiques' => '供給（シンジケート）から生じるリンクを妨害しない',
	'item_non_gerer_annuaire_site_web' => 'Webサイトディレクトリを無効化',
	'item_non_utiliser_syndication' => '自動的に供給（シンジケート）しない',
	'item_utiliser_syndication' => '自動的に供給（シンジケート）する',

	// L
	'label_exporter_avec_mots_cles_1' => 'Exporter les mots-clés sous forme de tags', # NEW
	'label_exporter_id_parent' => 'Exporter les sites de la rubrique', # NEW
	'label_exporter_publie_seulement_1' => 'Exporter uniquement les sites publiés', # NEW
	'label_fichier_import' => 'Fichier HTML', # NEW
	'label_importer_les_tags_1' => 'Importer les tags sous forme de mot-clé', # NEW
	'label_importer_statut_publie_1' => 'Publier automatiquement les sites', # NEW
	'lien_mise_a_jour_syndication' => '今アップデート',
	'lien_nouvelle_recuperation' => 'データの新しい検索を開始',
	'lien_purger_syndication' => 'Effacer tous les articles syndiqués', # NEW

	// N
	'nombre_articles_syndic' => '@nb@ articles syndiqués', # NEW

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_prop' => 'En attente', # NEW
	'statut_publie' => 'Publié', # NEW
	'syndic_choix_moderation' => 'このサイトからの次のリンクもするべきですか？',
	'syndic_choix_oublier' => '供給(Syndication)ファイルのもう存在しないリンクもしたほうがいいですか？',
	'syndic_choix_resume' => 'いくつかのサイトは、記事の全文を供給します。全文が利用可能な場合、全文を供給(Syndicate)していいですか？:',
	'syndic_lien_obsolete' => '切れたリンク',
	'syndic_option_miroir' => 'それらを自動的にブロック',
	'syndic_option_oubli' => '(@mois@ ヶ月後に) それを削除',
	'syndic_option_resume_non' => '記事のすべての内容 (HTML形式)',
	'syndic_option_resume_oui' => '短い概要 (文章形式)',
	'syndic_options' => '供給機能(Syndication)オプション:',

	// T
	'texte_expliquer_export_bookmarks' => 'Vous pouvez exporter une liste de sites au format Marque-page HTML,
	pour vous permettre ensuite de l\'importer dans votre navigateur ou dans un service en ligne', # NEW
	'texte_expliquer_import_bookmarks' => 'Vous pouvez importer une liste de sites au format Marque-page HTML,
	en provenance de votre navigateur ou d\'un service en ligne de gestion des Marques-pages.', # NEW
	'texte_liens_sites_syndiques' => '供給（シンジケート）サイトから生じるリンクをあらかじめ排除しておけます; 次の設定はそれらを作った後供給（シンジケート）サイトのデフォルト設定を表示します。それは、そのとき、とにかく可能なそれぞれの個別のリンクを排除するか、それぞれのサイトに、特定のサイトから来ているリンクを排除する。', # MODIF
	'texte_messages_publics' => '記事の公開メッセージ:',
	'texte_non_fonction_referencement' => 'あなたはこの自動化機能を使わず、そのサイトに関する要素を手作業で入力することができます...', # MODIF
	'texte_referencement_automatique' => '<b>サイトの自動参照</b><br />下にあなたの望むURLか、そのbackendファイルのアドレスを入力することによってすばやくウェブサイトを参照することができます。SPIPは自動的にそのサイトに関する情報を拾ってくるでしょう（タイトル、記述...）。', # MODIF
	'texte_referencement_automatique_verifier' => 'Veuillez vérifier les informations fournies par <tt>@url@</tt> avant d\'enregistrer.', # NEW
	'texte_syndication' => 'これを許可すると、最新の資料のリストを自動的に検索することが可能になります。これをするには供給（シンジケート）を作動させなければなりません。
  <blockquote><i>若干のホストではこの機能は無効です。そういう場合、あなたはあなたのサイトから内容を供給（シンジケート）することは出来ないでしょう。</i></blockquote>', # MODIF
	'titre_articles_syndiques' => 'このサイトで成功した記事を配給しました。',
	'titre_dernier_article_syndique' => '最新の配給された記事',
	'titre_exporter_bookmarks' => 'Exporter des Marques-pages', # NEW
	'titre_importer_bookmarks' => 'Importer des Marques-pages', # NEW
	'titre_importer_exporter_bookmarks' => 'Importer et Exporter des Marques-pages', # NEW
	'titre_page_sites_tous' => '参照されたサイト',
	'titre_referencement_sites' => 'サイトを参照しているのと、配給',
	'titre_site_numero' => 'サイト番号:',
	'titre_sites_proposes' => '提出したサイト',
	'titre_sites_references_rubrique' => 'このセクション内で参照されたサイト ',
	'titre_sites_syndiques' => '配給されたサイト',
	'titre_sites_tous' => '参照されたサイト',
	'titre_syndication' => 'サイトの配給',
	'tout_voir' => 'Voir tous les articles syndiqués', # NEW

	// U
	'un_article_syndic' => '1 article syndiqué' # NEW
);

?>
