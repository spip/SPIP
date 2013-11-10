// ----------------------------------------------------------------------------
// markItUp! Universal MarkUp Engine, JQuery plugin
// v 1.1.12
// Dual licensed under the MIT and GPL licenses.
// ----------------------------------------------------------------------------
// Copyright (C) 2007-2011 Jay Salvat
// http://markitup.jaysalvat.com/
// ----------------------------------------------------------------------------
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
// ----------------------------------------------------------------------------

/*
 *   Le code original de markitup 1.1.12
 *   a ete modifie pour prendre en compte
 * 
 *   1) la langue utilisee dans les textarea :
 * 		- si un textarea possede un attribut lang='xx' alors
 *   	  markitup n'affichera que les icones qui correspondent a cette langue
 * 		- on peut passer une valeur de langue par defaut a markitup (le textarea peut ne pas en definir)
 *   	  .markitup(set_spip,{lang:'fr'});
 * 		- une option supplementaire optionnelle 'lang' est introduite dans les parametres 
 *   	  des boutons (markupset), par exemple : lang:['fr','es','en']
 * 		- si un bouton n'a pas ce parametre, l'icone s'affiche 
 *   	  quelque soit la langue designee dans le textarea ou les parametres de markitup ;
 *   	  sinon, il faut que la langue soit contenue dedans pour que l'icone s'affiche.

 *   2) gerer des types de selections differentes : 
 * 		- normales comme dans markitup (rien a faire)
 * 		- 'selectionType':'word' : aux mots le plus proche si pas de selection (sinon la selection)
 * 		- 'selectionType':'line' : aux lignes les plus proches
 * 		- and 'return' : ugly hack to generate list (and so on) on key 'return' press
 *
 *   3) eviter a Opera de gerer les evenements apres tabulation ou entree...
 *      il ne sait pas gerer (v11.51)
 * 
 * 		
 */
;(function($) {
	$.fn.markItUp = function(settings, extraSettings) {
		var options, ctrlKey, shiftKey, altKey;
		ctrlKey = shiftKey = altKey = false;

		options = {	id:						'',
					nameSpace:				'',
					root:					'',
					lang:					'',
					previewInWindow:		'', // 'width=800, height=600, resizable=yes, scrollbars=yes'
					previewAutoRefresh:		true,
					previewPosition:		'after',
					previewTemplatePath:	'~/templates/preview.html',
					previewParser:			false,
					previewParserPath:		'',
					previewParserVar:		'data',
					resizeHandle:			true,
					beforeInsert:			'',
					afterInsert:			'',
					onEnter:				{},
					onShiftEnter:			{},
					onCtrlEnter:			{},
					onTab:					{},
					markupSet:			[	{ /* set */ } ]
				};
		$.extend(options, settings, extraSettings);

		// compute markItUp! path
		if (!options.root) {
			$('script').each(function(a, tag) {
				miuScript = $(tag).get(0).src.match(/(.*)jquery\.markitup(\.pack)?\.js$/);
				if (miuScript !== null) {
					options.root = miuScript[1];
				}
			});
		}

		return this.each(function() {
			var $$, textarea, levels, scrollPosition, caretPosition,
				clicked, hash, header, footer, previewWindow, template, iFrame, abort,
				before, after;
			$$ = $(this);
			textarea = this;
			levels = [];
			abort = false;
			scrollPosition = caretPosition = 0;
			caretOffset = -1;

			options.previewParserPath = localize(options.previewParserPath);
			options.previewTemplatePath = localize(options.previewTemplatePath);

			// apply the computed path to ~/
			function localize(data, inText) {
				if (inText) {
					return 	data.replace(/("|')~\//g, "$1"+options.root);
				}
				return 	data.replace(/^~\//, options.root);
			}

			// init and build editor
			function init() {
				id = ''; nameSpace = '';
				if (options.id) {
					id = 'id="'+options.id+'"';
				} else if ($$.attr("id")) {
					id = 'id="markItUp'+($$.attr("id").substr(0, 1).toUpperCase())+($$.attr("id").substr(1))+'"';

				}
				if (options.nameSpace) {
					nameSpace = 'class="'+options.nameSpace+'"';
				}
				currentScrollPosition = $$.scrollTop();
				$$.wrap('<div '+nameSpace+'></div>');
				$$.wrap('<div '+id+' class="markItUp"></div>');
				$$.wrap('<div class="markItUpContainer"></div>');
				$$.addClass("markItUpEditor");
				$$.scrollTop(currentScrollPosition);

				// add the header before the textarea
				header = $('<div class="markItUpHeader"></div>').insertBefore($$);
				$(dropMenus(options.markupSet)).appendTo(header);
				// remove empty dropMenu
				$(header).find("li.markItUpDropMenu ul:empty").parent().remove();
				
				// add the footer after the textarea
				footer = $('<div class="markItUpFooter"></div>').insertAfter($$);

				// add the resize handle after textarea
				
				if (options.resizeHandle === true && $.browser.safari !== true) {
					resizeHandle = $('<div class="markItUpResizeHandle"></div>')
						.insertAfter($$)
						.bind("mousedown", function(e) {
							var h = $$.height(), y = e.clientY, mouseMove, mouseUp;
							mouseMove = function(e) {
								$$.css("height", Math.max(20, e.clientY+h-y)+"px");
								return false;
							};
							mouseUp = function(e) {
								$("html").unbind("mousemove", mouseMove).unbind("mouseup", mouseUp);
								return false;
							};
							$("html").bind("mousemove", mouseMove).bind("mouseup", mouseUp);
					});
					footer.append(resizeHandle);
				}

				// listen key events
				$$.keydown(keyPressed).keyup(keyPressed);
				
				// bind an event to catch external calls
				$$.bind("insertion", function(e, settings) {
					if (settings.target !== false) {
						get();
					}
					if (textarea === $.markItUp.focused) {
						markup(settings);
					}
				});

				// remember the last focus
				$$.focus(function() {
					$.markItUp.focused = this;
				});
			}

			// recursively build header with dropMenus from markupset
			function dropMenus(markupSet) {
				var ul = $('<ul></ul>'), i = 0;
				var lang = ($$.attr('lang')||options.lang);
				
				$('li:hover > ul', ul).css('display', 'block');
				$.each(markupSet, function() {
					var button = this, t = '', title, li, j;
					// pas de langue ou dans la langue ; et uniquement si langue autorisee
					if ((!lang || !button.lang || ($.inArray(lang, button.lang) != -1))
						&& (!button.lang_not || ($.inArray(lang, button.lang_not) == -1))) {
						title = (button.key) ? (button.name||'')+' [Ctrl+'+button.key+']' : (button.name||'');
						key   = (button.key) ? 'accesskey="'+button.key+'"' : '';
						if (button.separator) {
							li = $('<li class="markItUpSeparator">'+(button.separator||'')+'</li>').appendTo(ul);
						} else {
							i++;
							for (j = levels.length -1; j >= 0; j--) {
								t += levels[j]+"-";
							}
							li = $('<li class="markItUpButton markItUpButton'+t+(i)+' '+(button.className||'')+'"><a href="" '+key+' title="'+title+'"><em>'+(button.name||'')+'</em></a></li>')
							.bind("contextmenu", function() { // prevent contextmenu on mac and allow ctrl+click
								return false;
							}).click(function() {
								return false;
							}).bind("focusin", function(){
								$$.focus();
							}).mouseup(function() {
								if (button.call) {
									eval(button.call)();
								}
								setTimeout(function() { markup(button) },1);
								return false;
							}).hover(function() {
									$('> ul', this).show();
									$(document).one('click', function() { // close dropmenu if click outside
											$('ul ul', header).hide();
										}
									);
								}, function() {
									$('> ul', this).hide();
								}
							).appendTo(ul);
							if (button.dropMenu) {
								levels.push(i);
								$(li).addClass('markItUpDropMenu').append(dropMenus(button.dropMenu));
							}
						}
					}
				}); 
				levels.pop();
				return ul;
			}

			// markItUp! markups
			function magicMarkups(string) {
				if (string) {
					string = string.toString();
					string = string.replace(/\(\!\(([\s\S]*?)\)\!\)/g,
						function(x, a) {
							var b = a.split('|!|');
							if (altKey === true) {
								return (b[1] !== undefined) ? b[1] : b[0];
							} else {
								return (b[1] === undefined) ? "" : b[0];
							}
						}
					);
					// [![prompt]!], [![prompt:!:value]!]
					string = string.replace(/\[\!\[([\s\S]*?)\]\!\]/g,
						function(x, a) {
							var b = a.split(':!:');
							if (abort === true) {
								return false;
							}
							value = prompt(b[0], (b[1]) ? b[1] : '');
							if (value === null) {
								abort = true;
							}
							return value;
						}
					);
					return string;
				}
				return "";
			}

			// prepare action
			function prepare(action) {
				if ($.isFunction(action)) {
					action = action(hash);
				}
				return magicMarkups(action);
			}

			// build block to insert
			function build(string) {
				var openWith 			= prepare(clicked.openWith);
				var placeHolder 		= prepare(clicked.placeHolder);
				var replaceWith 		= prepare(clicked.replaceWith);
				var closeWith 			= prepare(clicked.closeWith);
				var openBlockWith 		= prepare(clicked.openBlockWith);
				var closeBlockWith 		= prepare(clicked.closeBlockWith);
				var multiline 			= clicked.multiline;

				if (replaceWith !== "") {
					block = openWith + replaceWith + closeWith;
				} else if (selection === '' && placeHolder !== '') {
					block = openWith + placeHolder + closeWith;
				} else if (multiline === true) {
					string = string || selection;

					var lines = selection.split(/\r?\n/), blocks = [];

					for (var l=0; l < lines.length; l++) {
						line = lines[l];
						var trailingSpaces;
						if (trailingSpaces = line.match(/ *$/)) {
							blocks.push(openWith + line.replace(/ *$/g, '') + closeWith + trailingSpaces);
						} else {
							blocks.push(openWith + line + closeWith);
						}
					}

					block = blocks.join("\n");
				} else {
					block = openWith + (string || selection) + closeWith;
				}

				block = openBlockWith + block + closeBlockWith;
				
				return {	block:block, 
							openWith:openWith, 
							replaceWith:replaceWith, 
							placeHolder:placeHolder,
							closeWith:closeWith
					};
			}


			function selectWord(){
				selectionBeforeAfter(/\s|[.,;:!¡?¿()]/);
				selectionSave();				
			}
			function selectLine(){
				selectionBeforeAfter(/\r?\n/);
				selectionSave();				
			}			
			
			function selectionRemoveLast(pattern){
					// Remove space by default
					if (!pattern) pattern = /\s/;
					last = selection[selection.length-1];
					if (last && last.match(pattern)) {
						set(caretPosition, selection.length-1);
						get();
						$.extend(hash, { caretPosition:caretPosition, scrollPosition:scrollPosition } );
					}				
			}
			
			function selectionBeforeAfter(pattern) {
				if (!pattern) pattern = /\s/;

				sautAvantIE = sautApresIE = 0;
				if ($.browser.msie) {
					 	// calcul du nombre reel de caracteres pour le substr()
					 	// IE ne compte pas les sauts de lignes pour definir les selections
					 	// mais les compte dans la fonction length()
						lenSelection = selection.length - fixIeBug(selection);
						// si le caractere avant mon debut est un saut le ligne,
						// ie ne le prendra pas en compte dans la selection.
						// il faut pouvoir le connaitre.
						if (caretPosition) {
							set(caretPosition - 1, 2);
							sautAvantIE = fixIeBug(document.selection.createRange().text);
						}
						// idem pour le caractere apres la ligne !
						set(caretPosition, 2);
						sautApresIE = fixIeBug(document.selection.createRange().text);
						// selection avant
 						set(0,caretPosition);
 						before = document.selection.createRange().text;
 						// selection apres
 						set(caretPosition + lenSelection, textarea.value.length);
 						after = document.selection.createRange().text;
 						// remettre la veritable selection
 						set(caretPosition, lenSelection);
 						selection = document.selection.createRange().text;
				} else {
					before = textarea.value.substring(0, caretPosition);
					after = textarea.value.substring(caretPosition + selection.length - fixIeBug(selection));
				}
						
				before = before.split(pattern);
				after = after.split(pattern);
				// ajouter ce fichu saut de ligne pour IE
				if (sautAvantIE) before.push("");
				if (sautApresIE) after.unshift("");
					
			}
			
			function selectionSave(){
				nb_before = before ? before[before.length-1].length : 0;
				nb_after = after ? after[0].length : 0;

				nb = nb_before + selection.length + nb_after - fixIeBug(selection);
				caretPosition =  caretPosition - nb_before;

				set(caretPosition, nb);
				get();
				$.extend(hash, { selection:selection, caretPosition:caretPosition, scrollPosition:scrollPosition } );
			}
			
			// define markup to insert
			function markup(button) {
				var len, j, n, i;
				hash = clicked = button;
				get();
				
				$.extend(hash, {	line:"", 
						 			root:options.root,
									textarea:textarea, 
									selection:(selection||''), 
									caretPosition:caretPosition,
									ctrlKey:ctrlKey, 
									shiftKey:shiftKey, 
									altKey:altKey
								}
							);

				// corrections des selections pour que
				// - soit le curseur ne change pas
				// - soit on prend le mot complet (si pas de selection)
				// - soit on prend la ligne (avant, apres la selection)
				if (button.selectionType) {

					if (button.selectionType == "word") {
						if (!selection) {
							selectWord();
						} else {
							// win/ff add space on double click ? (hum, seems strange)
							selectionRemoveLast(/\s/);
						}
					}				
					if (button.selectionType == "line") {
						selectLine();
					}
					// horrible chose, mais tellement plus pratique
					// car on ne peut pas de l'exerieur (json) utiliser
					// les fonctions internes de markitup
					if (button.selectionType == "return"){
						// le calcul de before et after sous IE
						// necessitant de creer des selections
						// c'est extremement vilain a chaque saut de ligne
						// des qu'il y a un texte volumineux.
						// on dit tant pis pour lui.
						if (!$.browser.msie) {
							selectionBeforeAfter(/\r?\n/);
							before_last = before[before.length-1];
							after = '';
							// gestion des listes -# et -* 
							if (r = before_last.match(/^-([*#]+) ?(.*)$/)) {
								if (r[2]) {
									button.replaceWith = "\n-"+r[1]+' ';
									before_last = '';
								} else {
									// supprime le -* present
									// (before le fera)
									button.replaceWith = "\n";
								}
							} else {
								before_last = '';
								button.replaceWith = "\n";
							}
							before[before.length-1] = before_last;
							selectionSave();
						}
					}
				}
				// / fin corrections
				
				// callbacks before insertion
				prepare(options.beforeInsert);
				prepare(clicked.beforeInsert);
				if ((ctrlKey === true && shiftKey === true) || button.multiline === true) {
					prepare(clicked.beforeMultiInsert);
				}			
				$.extend(hash, { line:1 });

				if ((ctrlKey === true && shiftKey === true) || button.forceMultiline === true) {
					lines = selection.split(/\r?\n/);
					for (j = 0, n = lines.length, i = 0; i < n; i++) {
						// si une seule ligne, on se fiche de savoir qu'elle est vide,
						// c'est volontaire si on clique le bouton
						if (n == 1 || $.trim(lines[i]) !== '') {
							$.extend(hash, { line:++j, selection:lines[i] } );
							lines[i] = build(lines[i]).block;
						} else {
							lines[i] = "";
						}
					}
					string = { block:lines.join('\n')};
					start = caretPosition;
					len = string.block.length + (($.browser.opera) ? n-1 : 0);
				} else if (ctrlKey === true) {
					string = build(selection);
					start = caretPosition + string.openWith.length;
					len = string.block.length - string.openWith.length - string.closeWith.length;
					len = len - (string.block.match(/ $/) ? 1 : 0);
					len -= fixIeBug(string.block);
				} else if (shiftKey === true) {
					string = build(selection);
					start = caretPosition;
					len = string.block.length;
					len -= fixIeBug(string.block);
				} else {
					string = build(selection);
					start = caretPosition + string.block.length ;
					len = 0;
					start -= fixIeBug(string.block);
				}

				if ((selection === '' && string.replaceWith === '')) {
					caretOffset += fixOperaBug(string.block);
					
					start = caretPosition + string.openWith.length;
					len = string.block.length - string.openWith.length - string.closeWith.length;

					caretOffset = $$.val().substring(caretPosition,  $$.val().length).length;
					caretOffset -= fixOperaBug($$.val().substring(0, caretPosition));
				}
				$.extend(hash, { caretPosition:caretPosition, scrollPosition:scrollPosition } );

				if (string.block !== selection && abort === false) {
					insert(string.block);
					set(start, len);
				} else {
					caretOffset = -1;
				}
				get();

				$.extend(hash, { line:'', selection:selection });

				// callbacks after insertion
				if ((ctrlKey === true && shiftKey === true) || button.multiline === true) {
					prepare(clicked.afterMultiInsert);
				}

				prepare(clicked.afterInsert);
				prepare(options.afterInsert);

				// refresh preview if opened
				if (previewWindow && options.previewAutoRefresh) {
					refreshPreview(); 
				}
				
				// reinit keyevent
				shiftKey = altKey = ctrlKey = abort = false;
				
			}

			// Substract linefeed in Opera
			function fixOperaBug(string) {
				if ($.browser.opera) {
					return string.length - string.replace(/\n*/g, '').length;
				}
				return 0;
			}
			// Substract linefeed in IE
			function fixIeBug(string) {
				if ($.browser.msie) {
					return string.length - string.replace(/\r*/g, '').length;
				}
				return 0;
			}
				
			// add markup
			function insert(block) {	
				if (document.selection) {
					var newSelection = document.selection.createRange();
					newSelection.text = block;
				} else {
					textarea.value =  textarea.value.substring(0, caretPosition)  + block + textarea.value.substring(caretPosition + selection.length, textarea.value.length);
				}
			}

			// set a selection
			function set(start, len) {
				if (textarea.createTextRange){
					// quick fix to make it work on Opera 9.5
					if ($.browser.opera && $.browser.version >= 9.5 && len == 0) {
						return false;
					}
					range = textarea.createTextRange();
					range.collapse(true);
					range.moveStart('character', start); 
					range.moveEnd('character', len); 
					range.select();
				} else if (textarea.setSelectionRange ){
					textarea.setSelectionRange(start, start + len);
				}
				textarea.scrollTop = scrollPosition;
				textarea.focus();
			}

			// get the selection
			function get() {
				textarea.focus();

				scrollPosition = textarea.scrollTop;
				if (document.selection) {
					selection = document.selection.createRange().text;
					if ($.browser.msie) { // ie
						var range = document.selection.createRange(), rangeCopy = range.duplicate();
						rangeCopy.moveToElementText(textarea);
						caretPosition = -1;
						while(rangeCopy.inRange(range)) {
							rangeCopy.moveStart('character');
							caretPosition ++;
						}
					} else { // opera
						caretPosition = textarea.selectionStart;
					}
				} else { // gecko & webkit
					caretPosition = textarea.selectionStart;
					selection = textarea.value.substring(caretPosition, textarea.selectionEnd);
	
				} 
				return selection;
			}

			// open preview window
			function preview() {
				if (!previewWindow || previewWindow.closed) {
					if (options.previewInWindow) {
						previewWindow = window.open('', 'preview', options.previewInWindow);
						$(window).unload(function() {
							previewWindow.close();
						});
					} else {
						iFrame = $('<iframe class="markItUpPreviewFrame"></iframe>');
						if (options.previewPosition == 'after') {
							iFrame.insertAfter(footer);
						} else {
							iFrame.insertBefore(header);
						}
						previewWindow = iFrame[iFrame.length - 1].contentWindow || frame[iFrame.length - 1];
					}
				} else if (altKey === true) {
					if (iFrame) {
						iFrame.remove();
					} else {
						previewWindow.close();
					}
					previewWindow = iFrame = false;
				}
				if (!options.previewAutoRefresh) {
					refreshPreview(); 
				}
				if (options.previewInWindow) {
					previewWindow.focus();
				}
			}

			// refresh Preview window
			function refreshPreview() {
				renderPreview();
			}

			function renderPreview() {
				var phtml;
				if (options.previewParser && typeof options.previewParser === 'function') {
					var data = options.previewParser( $$.val() );
					writeInPreview( localize(data, 1) ); 
				} else if (options.previewParserPath !== '') {
					$.ajax({
						type: 'POST',
						dataType: 'text',
						global: false,
						url: options.previewParserPath,
						data: options.previewParserVar+'='+encodeURIComponent($$.val()),
						success: function(data) {
							writeInPreview( localize(data, 1) ); 
						}
					});
				} else {
					if (!template) {
						$.ajax( {
							url: options.previewTemplatePath,
							dataType: 'text',
							global: false,
							success: function(data) {
								writeInPreview( localize(data, 1).replace(/<!-- content -->/g, $$.val()) );
							}
						});
					}
				}
				return false;
			}

			function writeInPreview(data) {
				if (previewWindow.document) {			
					try {
						sp = previewWindow.document.documentElement.scrollTop
					} catch(e) {
						sp = 0;
					}	
					previewWindow.document.open();
					previewWindow.document.write(data);
					previewWindow.document.close();
					previewWindow.document.documentElement.scrollTop = sp;
				}
			}
						
			// set keys pressed
			function keyPressed(e) {
				shiftKey = e.shiftKey;
				altKey = e.altKey;
				ctrlKey = (!(e.altKey && e.ctrlKey)) ? (e.ctrlKey || e.metaKey) : false;

				if (e.type === 'keydown') {
					if (ctrlKey === true) {
						li = $('a[accesskey="'+String.fromCharCode(e.keyCode)+'"]', header).parent('li');
						if (li.length !== 0) {
							ctrlKey = false;
							setTimeout(function() {
								li.triggerHandler('mouseup');
							},1);
							return false;
						}
					}
					
 					// si opera, on s'embete pas, il cree plus de problemes qu'autre chose
 					// car il ne prend pas en compte l'arret de ces evenements
					if (!$.browser.opera) {	
						if (e.keyCode === 13 || e.keyCode === 10) { // Enter key
							if (ctrlKey === true) {  // Enter + Ctrl
								ctrlKey = false;
								markup(options.onCtrlEnter);
								return options.onCtrlEnter.keepDefault;
							} else if (shiftKey === true) { // Enter + Shift
								shiftKey = false;
								markup(options.onShiftEnter);
								return options.onShiftEnter.keepDefault;
							} else { // only Enter
								markup(options.onEnter);
								return options.onEnter.keepDefault;
							}
						}
						
						if (e.keyCode === 9) { // Tab key
							if (shiftKey == true || ctrlKey == true || altKey == true) {
								// permettre un retour a l'action naturelle
								// du navigateur via shift+tab 
								return true; 
							}
							if (caretOffset !== -1) {
								get();
								caretOffset = $$.val().length - caretOffset;
								set(caretOffset, 0);
								caretOffset = -1;
								return false;
							} else {
								markup(options.onTab);
								return options.onTab.keepDefault;
							}
						}
					}
				}
			}

			init();
		});
	};

	$.fn.markItUpRemove = function() {
		return this.each(function() {
				var $$ = $(this).unbind().removeClass('markItUpEditor');
				$$.parent('div').parent('div.markItUp').parent('div').replaceWith($$);
			}
		);
	};

	$.markItUp = function(settings) {
		var options = { target:false };
		$.extend(options, settings);
		if (options.target) {
			return $(options.target).each(function() {
				$(this).focus();
				$(this).trigger('insertion', [options]);
			});
		} else {
			$('textarea').trigger('insertion', [options]);
		}
	};

})(jQuery);
