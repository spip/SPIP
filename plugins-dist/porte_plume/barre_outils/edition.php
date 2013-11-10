<?php
/**
 * Déclaration de la barre d'outil d'édition de SPIP
 * 
 * @plugin Porte Plume pour SPIP
 * @license GPL
 * @package SPIP\PortePlume\BarreOutils
 */
if (!defined("_ECRIRE_INC_VERSION")) return;


/**
 * Définition de la barre 'edition' pour markitup
 *
 * @return Barre_outils La barre d'outil
 */
function barre_outils_edition(){
	$set = new Barre_outils(array(
		'nameSpace'         => 'edition',
		#'previewAutoRefresh'=> true,
		#'previewParserPath' => url_absolue(generer_url_public('preview')),
		'onShiftEnter'      => array('keepDefault'=>false, 'replaceWith'=>"\n_ "),
		'onCtrlEnter'       => array('keepDefault'=>false, 'replaceWith'=>"\n\n"),
		// garder les listes si on appuie sur entree
		'onEnter'           => array('keepDefault'=>false, 'selectionType'=>'return', 'replaceWith'=>"\n"),
		// Utile quand on saisi du code, mais pas accessible !
		#'onTab'             => array('keepDefault'=>false, 'replaceWith'=>"\t"),
		'markupSet'         => array(
			// H1 - {{{
			array(
				"id"        => 'header1', 
				"name"      => _T('barreoutils:barre_intertitre'), 
				"key"       => "H", 
				"className" => "outil_header1", 
				"openWith" => "\n{{{", 
				"closeWith" => "}}}\n",
				"display"   => true,
				"selectionType" => "line",
			),
			// Bold - {{
			array(
				"id"        => 'bold',
				"name"      => _T('barreoutils:barre_gras'), 
				"key"       => "B", 
				"className" => "outil_bold", 
				"replaceWith" => "function(h){ return espace_si_accolade(h, '{{', '}}');}",
				//"openWith" => "{{", 
				//"closeWith" => "}}",
				"display"   => true,
				"selectionType" => "word",
			),
			// Italic - {
			array(
				"id"        => 'italic',
				"name"      => _T('barreoutils:barre_italic'), 
				"key"       => "I", 
				"className" => "outil_italic", 
				"replaceWith" => "function(h){ return espace_si_accolade(h, '{', '}');}",
				//"openWith" => "{", 
				//"closeWith" => "}",
				"display"   => true,
				"selectionType" => "word",
			),
			
			// montrer une suppression
			array(
				"id"        => 'stroke_through',
				"name"      => _T('barreoutils:barre_barre'), // :-)
				"className" => "outil_stroke_through", 
				"openWith" => "<del>", 
				"closeWith" => "</del>",
				"display"   => true,
				"selectionType" => "word",
			),
			
			// listes -*
			array(
				"id"        => 'liste_ul',
				"name"      => _T('barreoutils:barre_liste_ul'), 
				"className" => "outil_liste_ul", 
				"replaceWith" => "function(h){ return outil_liste(h, '*');}", 
				"display"   => true,
				"selectionType" => "line",
				"forceMultiline" => true,
				"dropMenu"    => array(
					// liste -#
					array(
						"id"        => 'liste_ol',
						"name"      => _T('barreoutils:barre_liste_ol'), 
						"className" => "outil_liste_ol", 
						"replaceWith" => "function(h){ return outil_liste(h, '#');}", 
						"display"   => true,
						"selectionType" => "line",
						"forceMultiline" => true,
					),
					// desindenter
					array(
						"id"        => 'desindenter',
						"name"      => _T('barreoutils:barre_desindenter'), 
						"className" => "outil_desindenter", 
						"replaceWith" => "function(h){return outil_desindenter(h);}", 
						"display"   => true,
						"selectionType" => "line",
						"forceMultiline" => true,
					),
					// indenter
					array(
						"id"        => 'indenter',
						"name"      => _T('barreoutils:barre_indenter'), 
						"className" => "outil_indenter", 
						"replaceWith" => "function(h){return outil_indenter(h);}",  
						"display"   => true,
						"selectionType" => "line",
						"forceMultiline" => true,
					),
				),
			),


			// separation
			array(
				"id" => "sepLink", // trouver un nom correct !
				"separator" => "---------------",
				"display"   => true,
			),
			// lien spip
			array(
				"id"          => 'link',
				"name"        => _T('barreoutils:barre_lien'),
				"key"         => "L", 
				"className"   => "outil_link", 
				"openWith"    => "[", 
				"closeWith"   => "->[!["._T('barreoutils:barre_lien_input')."]!]]",
				"display"     => true,
			),
			// note en bas de page spip
			array(
				"id"          => 'notes',
				"name"        => _T('barreoutils:barre_note'),
				"className"   => "outil_notes", 
				"openWith"    => "[[", 
				"closeWith"   => "]]",
				"display"     => true,
				"selectionType" => "word",
			),
			
			
			// separation
			array(
				"id" => "sepGuillemets",
				"separator" => "---------------",
				"display"   => true,
			),
			
			// quote spip
			// (affichee dans forum)
			array(
				"id"          => 'quote',
				"name"        => _T('barreoutils:barre_quote'),
				"key"         => "Q", 
				"className"   => "outil_quote", 
				"openWith"    => "\n<quote>", 
				"closeWith"   => "</quote>\n",
				"display"     => true,
				"selectionType" => "word",
				"dropMenu"    => array(
					// poesie spip
					array(
						"id"          => 'barre_poesie',
						"name"        => _T('barreoutils:barre_poesie'),
						"className"   => "outil_poesie", 
						"openWith"    => "\n&lt;poesie&gt;", 
						"closeWith"   => "&lt;/poesie&gt;\n",
						"display"     => true,
						"selectionType" => "line",
					),
				),
			),
			// guillemets
			array(
				"id"          => 'guillemets',
				"name"        => _T('barreoutils:barre_guillemets'),
				"className"   => "outil_guillemets", 
				"openWith"    => "&laquo;", 
				"closeWith"   => "&raquo;",
				"display"     => true,
				"lang"        => array('fr','eo','cpf','ar','es'),
				"selectionType" => "word",
				"dropMenu"    => array(
					// guillemets internes
					array(
						"id"          => 'guillemets_simples',
						"name"        => _T('barreoutils:barre_guillemets_simples'),
						"className"   => "outil_guillemets_simples", 
						"openWith"    => "&ldquo;", 
						"closeWith"   => "&rdquo;",
						"display"     => true,
						"lang"        => array('fr','eo','cpf','ar','es'),
						"selectionType" => "word",
					),
				)
			),

			// guillemets de
			array(
				"id"          => 'guillemets_de',
				"name"        => _T('barreoutils:barre_guillemets'),
				"className"   => "outil_guillemets_de", 
				"openWith"    => "&bdquo;", 
				"closeWith"   => "&ldquo;",
				"display"     => true,
				"lang"        => array('bg','de','pl','hr','src'),
				"selectionType" => "word",
				"dropMenu"    => array(
					// guillemets de, simples
					array(
						"id"          => 'guillemets_de_simples',
						"name"        => _T('barreoutils:barre_guillemets_simples'),
						"className"   => "outil_guillemets_de_simples", 
						"openWith"    => "&sbquo;", 
						"closeWith"   => "&lsquo;",
						"display"     => true,
						"lang"        => array('bg','de','pl','hr','src'),
						"selectionType" => "word",
					),
				)
			),

			// guillemets autres langues
			array(
				"id"          => 'guillemets_autres',
				"name"        => _T('barreoutils:barre_guillemets'),
				"className"   => "outil_guillemets_simples", 
				"openWith"    => "&ldquo;", 
				"closeWith"   => "&rdquo;",
				"display"     => true,
				"lang_not"    => array('fr','eo','cpf','ar','es','bg','de','pl','hr','src'),
				"selectionType" => "word",
				"dropMenu"    => array(
					// guillemets simples, autres langues
					array(
						"id"          => 'guillemets_autres_simples',
						"name"        => _T('barreoutils:barre_guillemets_simples'),
						"className"   => "outil_guillemets_uniques", 
						"openWith"    => "&lsquo;", 
						"closeWith"   => "&rsquo;",
						"display"     => true,
						"lang_not"    => array('fr','eo','cpf','ar','es','bg','de','pl','hr','src'),
						"selectionType" => "word",
					),
				)
			),


			// separation
			array(
				"id" => "sepCaracteres",
				"separator" => "---------------",
				"display"   => true,
			),
			// icones clavier
			array(
				"id"          => 'grpCaracteres',
				"name"        => _T('barreoutils:barre_inserer_caracteres'),
				"className"   => 'outil_caracteres',
				"display"     => true,
				
				"dropMenu"    => array(
					// A majuscule accent grave
					array(
						"id"          => 'A_grave',
						"name"        => _T('barreoutils:barre_a_accent_grave'),
						"className"   => "outil_a_maj_grave", 
						"replaceWith"   => "&Agrave;",
						"display"     => true,
						"lang"    => array('fr','eo','cpf'),
					),
					// E majuscule accent aigu
					array(
						"id"          => 'E_aigu',
						"name"        => _T('barreoutils:barre_e_accent_aigu'),
						"className"   => "outil_e_maj_aigu", 
						"replaceWith"   => "&Eacute;",
						"display"     => true,
						"lang"    => array('fr','eo','cpf'),
					),
					// E majuscule accent grave
					array(
						"id"          => 'E_grave',
						"name"        => _T('barreoutils:barre_e_accent_grave'),
						"className"   => "outil_e_maj_grave",
						"replaceWith" => "&Egrave;",
						"display"     => true,
						"lang"        => array('fr','eo','cpf'),
					),
					// e dans le a
					array(
						"id"          => 'aelig',
						"name"        => _T('barreoutils:barre_ea'),
						"className"   => "outil_aelig",
						"replaceWith" => "&aelig;",
						"display"     => true,
						"lang"        => array('fr','eo','cpf'),
					),
					// e dans le a majuscule
					array(
						"id"          => 'AElig',
						"name"        => _T('barreoutils:barre_ea_maj'),
						"className"   => "outil_aelig_maj",
						"replaceWith" => "&AElig;",
						"display"     => true,
						"lang"        => array('fr','eo','cpf'),
					),
					// oe 
					array(
						"id"          => 'oe',
						"name"        => _T('barreoutils:barre_eo'),
						"className"   => "outil_oe", 
						"replaceWith"   => "&oelig;",
						"display"     => true,
						"lang"    => array('fr'),
					),
					// OE 
					array(
						"id"          => 'OE',
						"name"        => _T('barreoutils:barre_eo_maj'),
						"className"   => "outil_oe_maj", 
						"replaceWith"   => "&OElig;",
						"display"     => true,
						"lang"    => array('fr'),
					),
					// c cedille majuscule
					array(
						"id"          => 'Ccedil',
						"name"        => _T('barreoutils:barre_c_cedille_maj'),
						"className"   => "outil_ccedil_maj",
						"replaceWith" => "&Ccedil;",
						"display"     => true,
						"lang"        => array('fr','eo','cpf'),
					),
					// Transformation en majuscule
					array(
						"id"          => 'uppercase',
						"name"        => _T('barreoutils:barre_gestion_cr_changercassemajuscules'),
						"className"   => "outil_uppercase",
						"replaceWith" => 'function(markitup) { return markitup.selection.toUpperCase() }',
						"display"     => true,
						"lang"        => array('fr','en'),
					),
					// Transformation en minuscule
					array(
						"id"          => 'lowercase',
						"name"        => _T('barreoutils:barre_gestion_cr_changercasseminuscules'),
						"className"   => "outil_lowercase",
						"replaceWith" => 'function(markitup) { return markitup.selection.toLowerCase() }',
						"display"     => true,
						"lang"        => array('fr','en'),
					),
				),
			),

			// Groupe de Codes informatiques.
			array(
					"id" => "sepCode",
					"separator" => "---------------",
					"display"   => true,
			),
			array(
					// groupe code et bouton <code>
					"id"          => 'grpCode',
					"name"        => _T('barreoutils:barre_inserer_code'),
					"className"   => 'outil_code',
					"openWith" => "<code>",
					"closeWith" => "</code>",
					"display"     => true,
					"dropMenu"    => array(
						// bouton <cadre>
						array(
							"id"          => 'cadre',
							"name"        => _T('barreoutils:barre_inserer_cadre'),
							"className"   => 'outil_cadre', 
							"openWith" => "<cadre>\n",
							"closeWith" => "\n</cadre>",
							"display"     => true,
						),
					),
			),

/*	inutile (origine de markitup et non de spip)
					
			// separation
			array(
				"id" => "sepPreview", // trouver un nom correct !
				"separator" => "---------------",
				"display"   => true,
			),
			// clean
			array(
				"id"          => 'clean',
				"name"        => _T('barreoutils:barre_clean'), 
				"className"   => "outil_clean",
				"replaceWith" => 'function(markitup) { return markitup.selection.replace(/<(.*?)>/g, "") }',
				"display"     => true,
			),
			// preview
			array(
				"id"        => 'preview',
				"name"      => _T('barreoutils:barre_preview'), 
				"className" => "outil_preview",
				"call"      => "preview",
				"display"   => true,
			),
*/
			
		),
		
	'functions'         => "
				// remplace ou cree -* ou -** ou -# ou -##
				function outil_liste(h, c) {
					if ((s = h.selection) && (r = s.match(/^-([*#]+) (.*)\$/)))	 {
						r[1] = r[1].replace(/[#*]/g, c);
						s = '-'+r[1]+' '+r[2];
					} else {
						s = '-' + c + ' '+s;
					}
					return s;
				}

				// indente des -* ou -#
				function outil_indenter(h) {
					if (s = h.selection) {
						if (s.substr(0,2)=='-*') {
							s = '-**' + s.substr(2);
						} else if (s.substr(0,2)=='-#') {
							s = '-##' + s.substr(2);
						} else {
							s = '-* ' + s;
						}
					}
					return s;
				}
						
				// desindente des -* ou -** ou -# ou -##
				function outil_desindenter(h){
					if (s = h.selection) {
						if (s.substr(0,3)=='-**') {
							s = '-*' + s.substr(3);
						} else if (s.substr(0,3)=='-* ') {
							s = s.substr(3);
						} else if (s.substr(0,3)=='-##') {
							s = '-#' + s.substr(3);
						} else if (s.substr(0,3)=='-# ') {
							s = s.substr(3);
						}
					}
					return s;
				}
				
				// ajouter un espace avant, apres un {qqc} pour ne pas que
				// gras {{}} suivi de italique {} donnent {{{}}}, mais { {{}} }
				function espace_si_accolade(h, openWith, closeWith){
					if (s = h.selection) {
						// accolade dans la selection
						if (s.charAt(0)=='{') {
							return openWith + ' ' + s + ' ' + closeWith;
						}
						// accolade avant la selection
						else if (c = h.textarea.selectionStart) {
							if (h.textarea.value.charAt(c-1) == '{') {
								return ' ' + openWith + s + closeWith + ' ';
							}
						}
					}
					return openWith + s + closeWith;
				} 
				",
	));
	
	$set->cacher(array(
		'stroke_through',
		'clean', 'preview',
	));
	
	return $set;
}



/**
 * Définitions des liens entre css et icones
 *
 * @return array
 *     Couples identifiant de bouton => nom de l'image (ou tableau nom, position haut, position bas)
 */
function barre_outils_edition_icones(){
	return array(
		//'outil_header1' => 'text_heading_1.png',
		'outil_header1' => array('spt-v1.png','-10px -226px'), //'intertitre.png'
		'outil_bold' => array('spt-v1.png','-10px -478px'), //'text_bold.png'
		'outil_italic' => array('spt-v1.png','-10px -586px'), //'text_italic.png'

		'outil_stroke_through' => array('spt-v1.png','-10px -946px'), //'text_strikethrough.png'

		'outil_liste_ul' => array('spt-v1.png','-10px -622px'), //'text_list_bullets.png'
		'outil_liste_ol' => array('spt-v1.png','-10px -658px'), //'text_list_numbers.png'
		'outil_indenter' => array('spt-v1.png','-10px -514px'), //'text_indent.png'
		'outil_desindenter' => array('spt-v1.png','-10px -550px'), //'text_indent_remove.png'
		
		//'outil_quote' => 'text_indent.png',
		'outil_quote' => array('spt-v1.png','-10px -442px'), //'quote.png'
		'outil_poesie' => array('spt-v1.png','-10px -1050px'), //'poesie.png'

		//'outil_link' => 'world_link.png',
		'outil_link' => array('spt-v1.png','-10px -298px'), //'lien.png'
		'outil_notes' => array('spt-v1.png','-10px -334px'), //'notes.png'


		'outil_guillemets' => array('spt-v1.png','-10px -910px'), //'guillemets.png'
		'outil_guillemets_simples' => array('spt-v1.png','-10px -802px'), //'guillemets-simples.png'
		'outil_guillemets_de' => array('spt-v1.png','-10px -766px'), //'guillemets-de.png'
		'outil_guillemets_de_simples' => array('spt-v1.png','-10px -838px'), //'guillemets-uniques-de.png'
		'outil_guillemets_uniques' => array('spt-v1.png','-10px -874px'), //'guillemets-uniques.png'
		
		'outil_caracteres' => array('spt-v1.png','-10px -262px'), //'keyboard.png'
			'outil_a_maj_grave' => array('spt-v1.png','-10px -82px'), //'agrave-maj.png'
			'outil_e_maj_aigu' => array('spt-v1.png','-10px -154px'), //'eacute-maj.png'
			'outil_e_maj_grave' => array('spt-v1.png','-10px -190px'), //'eagrave-maj.png'
			'outil_aelig' => array('spt-v1.png','-10px -46px'), //'aelig.png'
			'outil_aelig_maj' => array('spt-v1.png','-10px -10px'), //'aelig-maj.png'
			'outil_oe' => array('spt-v1.png','-10px -406px'), //'oelig.png'
			'outil_oe_maj' => array('spt-v1.png','-10px -370px'), //'oelig-maj.png'
			'outil_ccedil_maj' => array('spt-v1.png','-10px -118px'),  //'ccedil-maj.png'
			'outil_uppercase' => array('spt-v1.png','-10px -730px'), //'text_uppercase.png'
			'outil_lowercase' => array('spt-v1.png','-10px -694px'), //'text_lowercase.png'

		'outil_code' => array('spt-v1.png','-10px -1086px'),
			'outil_cadre' => array('spt-v1.png','-10px -1122px'),
		
		'outil_clean' => array('spt-v1.png','-10px -982px'), //'clean.png'
		'outil_preview' => array('spt-v1.png','-10px -1018px'), //'eye.png'
	);
}
?>
