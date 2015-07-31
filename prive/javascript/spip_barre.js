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



function barre_inserer(text,champ) {
	var txtarea = champ;
	if( document.selection ){
		txtarea.focus();
		var r = document.selection.createRange();
		if (r == null) {
			txtarea.selectionStart = txtarea.value.length;
			txtarea.selectionEnd = txtarea.selectionStart;
		}
		else {
			var re = txtarea.createTextRange();
			var rc = re.duplicate();
			re.moveToBookmark(r.getBookmark());
			rc.setEndPoint('EndToStart', re);
			txtarea.selectionStart = rc.text.length;
			txtarea.selectionEnd = rc.text.length + r.text.length;
		}
	} 
	mozWrap(txtarea, '', text);
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

