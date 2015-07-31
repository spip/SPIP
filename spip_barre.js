// Barre de raccourcis
// derive du:
// bbCode control by subBlue design : www.subBlue.com

// Startup variables
var theSelection = false;

// Check for Browser & Platform for PC & IE specific bits
// More details from: http://www.mozilla.org/docs/web-developer/sniffer/browser_type.html
var clientPC = navigator.userAgent.toLowerCase(); // Get client info
var clientVer = parseInt(navigator.appVersion); // Get browser version

var is_ie = ((clientPC.indexOf("msie") != -1) && (clientPC.indexOf("opera") == -1));
var is_nav = ((clientPC.indexOf('mozilla')!=-1) && (clientPC.indexOf('spoofer')==-1)
                && (clientPC.indexOf('compatible') == -1) && (clientPC.indexOf('opera')==-1)
                && (clientPC.indexOf('webtv')==-1) && (clientPC.indexOf('hotjava')==-1));
var is_moz = 0;

var is_win = ((clientPC.indexOf("win")!=-1) || (clientPC.indexOf("16bit") != -1));
var is_mac = (clientPC.indexOf("mac")!=-1);


function barre_raccourci(debut,fin,champ) {
	var txtarea = champ;

	txtarea.focus();
	donotinsert = false;
	theSelection = false;
	bblast = 0;

	if ((clientVer >= 4) && is_ie && is_win)
	{
		theSelection = document.selection.createRange().text; // Get text selection
		if (theSelection) {

			while (theSelection.substring(theSelection.length-1, theSelection.length) == ' ')
			{
				theSelection = theSelection.substring(0, theSelection.length-1);
				fin = fin + " ";
			}
			if (theSelection.substring(0,1) == '{' && debut.substring(0,1) == '{')
			{
				debut = debut + " ";
			}
			if (theSelection.substring(theSelection.length-1, theSelection.length) == '}' && fin.substring(0,1) == '}')
			{
				fin = " " + fin;
			}

			// Add tags around selection
			document.selection.createRange().text = debut + theSelection + fin;
			txtarea.focus();
			theSelection = '';
			return;
		}
	}
	else if (txtarea.selectionEnd && (txtarea.selectionEnd - txtarea.selectionStart > 0))
	{
		mozWrap(txtarea, debut, fin);
		return;
	}
}

function barre_demande(debut,milieu,fin,affich,champ) {
	var inserer = prompt(affich);

	if (inserer != null) {
		if (inserer == "") {inserer = "xxx"; }

		barre_raccourci(debut, milieu+inserer+fin, champ);
	}
}

function barre_inserer(text,champ) {
	var txtarea = champ;
	
	if (txtarea.createTextRange && txtarea.caretPos) {
		var caretPos = txtarea.caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? caretPos.text + text + ' ' : caretPos.text + text;
		txtarea.focus();
	} else {
		//txtarea.value  += text;
		//txtarea.focus();
		mozWrap(txtarea, '', text);
		return;
	}
}


// D'apres Nicolas Hoizey 
function barre_tableau(toolbarfield)
{
	var txtarea = toolbarfield;
	txtarea.focus();
	var cols = prompt("Nombre de colonnes du tableau :", "");
	var rows = prompt("Nombre de lignes du tableau :", "");
	if (cols != null && rows != null) {
		var tbl = '';
		var ligne = '|';
		var entete = '|';
		for(i = 0; i < cols; i++) {
			ligne = ligne + ' valeur |';
			entete = entete + ' {{entete}} |';
		}
		for (i = 0; i < rows; i++) {
			tbl = tbl + ligne + '\n';
		}
		if (confirm('Voulez vous ajouter une ligne d\'en-tête ?')) {
			tbl = entete + '\n' + tbl;
		}
		if ((clientVer >= 4) && is_ie && is_win) {
			var str = document.selection.createRange().text;
			var sel = document.selection.createRange();
			sel.text = str + '\n\n' + tbl + '\n\n';
		} else {
			mozWrap(txtarea, '', "\n\n" + tbl + "\n\n");
		}
	}
	return;
}



// Shows the help messages in the helpline window
function helpline(help, champ) {
	champ.value = help;
}


function setCaretToEnd (input) {
  setSelectionRange(input, input.value.length, input.value.length);
}


function setSelectionRange(input, selectionStart, selectionEnd) {
  if (input.setSelectionRange) {
    input.focus();
    input.setSelectionRange(selectionStart, selectionEnd);
  }
  else if (input.createTextRange) {
    var range = input.createTextRange();
    range.collapse(true);
    range.moveEnd('character', selectionEnd);
    range.moveStart('character', selectionStart);
    range.select();
  }
}

// From http://www.massless.org/mozedit/
function mozWrap(txtarea, open, close)
{
	var selLength = txtarea.textLength;
	var selStart = txtarea.selectionStart;
	var selEnd = txtarea.selectionEnd;
	if (selEnd == 1 || selEnd == 2)
		selEnd = selLength;
	var selTop = txtarea.scrollTop;

	// Raccourcir la selection par double-clic si dernier caractere est espace	
	if (selEnd - selStart > 0 && (txtarea.value).substring(selEnd-1,selEnd) == ' ') selEnd = selEnd-1;
	
	var s1 = (txtarea.value).substring(0,selStart);
	var s2 = (txtarea.value).substring(selStart, selEnd)
	var s3 = (txtarea.value).substring(selEnd, selLength);

	// Eviter melange bold-italic-intertitre
	if ((txtarea.value).substring(selEnd,selEnd+1) == '}' && close.substring(0,1) == "}") close = close + " ";
	if ((txtarea.value).substring(selEnd-1,selEnd) == '}' && close.substring(0,1) == "}") close = " " + close;
	if ((txtarea.value).substring(selStart-1,selStart) == '{' && open.substring(0,1) == "{") open = " " + open;
	if ((txtarea.value).substring(selStart,selStart+1) == '{' && open.substring(0,1) == "{") open = open + " ";

	txtarea.value = s1 + open + s2 + close + s3;
	selDeb = selStart + open.length;
	selFin = selEnd + close.length;
	window.setSelectionRange(txtarea, selDeb, selFin);
	txtarea.scrollTop = selTop;
	txtarea.focus();
	
	return;
}

// Insert at Claret position. Code from
// http://www.faqts.com/knowledge_base/view.phtml/aid/1052/fid/130
     function storeCaret (textEl) {
       if (textEl.createTextRange) 
         textEl.caretPos = document.selection.createRange().duplicate();
     }

